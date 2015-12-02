<?php

chdir(dirname(__FILE__) . '/../');

include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");

set_time_limit(0);

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
 
$ctl = new control_modules();

include_once(DIR_MODULES . 'veralite/veralite.class.php');

$veralite = new veralite();
$veralite->getConfig();

if (!preg_match('/^http/is', $veralite->config['ZWAVE_API_URL']))
   exit; // no API URL set

$tmp = SQLSelectOne("SELECT ID FROM veradevices LIMIT 1");

if (!$tmp['ID'])
   exit; // no devices added -- no need to run this cycle

$connected = 0;

for ($i = 0; $i < 3; $i++)
{
   if ($veralite->connect())
   {
      $connected = 1;
      break;
   }
   else
   {
      echo "Cannot connect to Veralite API\n";
      sleep(3);
   }
}

if (!$connected)
   exit;

echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

$latest_check=0;
$checkEvery=30; // poll network every 30 seconds

while (1)
{
   setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);

   if ((time()-$latest_check)>$checkEvery) {
    $latest_check=time();
    echo date('Y-m-d H:i:s').' Polling Veralite...';
    $veralite->scanNetwork();
   }

   if (file_exists('./reboot') || IsSet($_GET['onetime']))
   {
      $db->Disconnect();
      exit;
   }

   sleep(1);
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
