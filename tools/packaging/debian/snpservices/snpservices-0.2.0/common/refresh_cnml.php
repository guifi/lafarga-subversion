<?php

  if (file_exists("config.php")) {
    include_once("./config.php");
  } else {
    include_once("config.php.template");
  }

  $minX = 999;
  $minY = 999;
  $maxX = -999;
  $maxY = -999;

  $members = array(); 

  $hlastnow = @fopen($SNPDataServer_url."/guifi/refresh/cnml", "r") 
    or die('Error reading last timestamp\n');
  $last_now = fgets($hlastnow);
  fclose($hlastnow);
  $hlast= @fopen("/tmp/last_update.cnml", "r");
  if (($hlast) and ($last_now == fgets($hlast))) {
    fclose($hlast);
    echo "No changes.\n";
    exit();
  }

  echo "Getting CNML file\n";
  $hcnml = @fopen($SNPDataServer_url."/guifi/cnml/".$rootZone."/detail", "r")
    or die ('Error redaing CNML source\n');
  $wcnml = @fopen("../data/guifi.cnml", "w");
  while (!feof($hcnml)) {
    $buffer = fgets($hcnml, 4096);
    fwrite($wcnml,$buffer);
  }
  fclose($hcnml);
  fclose($wcnml);

  $hlast= @fopen("/tmp/last_update.cnml", "w") or die('Error!');
  fwrite($hlast,$last_now);
  fclose($hlast);
?>
