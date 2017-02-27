<?php
chdir(dirname(__FILE__) . '/../');

include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");

set_time_limit(0);

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

include_once("./load_settings.php");

if (defined('DISABLE_WEBSOCKETS') && DISABLE_WEBSOCKETS==1) {
 echo "Web-sockets disabled\n";
 exit;
}


SQLExec("TRUNCATE TABLE cached_ws");
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

while (1)
{
   if (time() - $checked_time > 0)
   {
      $checked_time = time();
      $queue=SQLSelect("SELECT * FROM cached_ws");
      if ($queue[0]['PROPERTY']) {
       SQLExec("TRUNCATE TABLE cached_ws");
       $total=count($queue);
       $sent_ok=1;
       for($i=0;$i<$total;$i++) {
        $sent=postToWebSocket($queue[$i]['PROPERTY'], $queue[$i]['DATAVALUE'], $queue[$i]['POST_ACTION']);
        if (!$sent) {
         $sent_ok=0;
        }
       }
       if ($sent_ok) {
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
       } else {
        echo date("H:i:s") . ' Error while posting to websocket.';
       }
      }

   }
   if (file_exists('./reboot') || IsSet($_GET['onetime']))
   {
      $db->Disconnect();
      exit;
   }
   sleep(1);
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));


$db->Disconnect(); // closing database connection
