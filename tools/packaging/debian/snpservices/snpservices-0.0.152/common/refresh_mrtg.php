<?php

  if (file_exists("/etc/snpservices/config.php")) {
    include_once("/etc/snpservices/config.php");
  } else {
    include_once("/etc/snpservices/config.php.template");
  }

  $hlastnow = @fopen($MRTG."/guifi/refresh/mrtg", "r") or die('Error reading changes\n');
  $last_now = fgets($hlastnow);
  fclose($hlastnow);
  $hlast= @fopen("/tmp/last_update.mrtg", "r");
  if (($hlast) and ($last_now == fgets($hlast))) {
    fclose($hlast);
    echo "No changes.\n";
    exit();
  }

  echo "Getting MRTG CSV file\n";
  $hcnml = @fopen($MRTGConfigSource, "r");
  $wcnml = @fopen("/var/lib/snpservices/data/guifi_mrtg.csv", "w");
  while (!feof($hcnml)) {
       $buffer = fgets($hcnml, 4096);
       fwrite($wcnml,$buffer);
  }
  fclose($hcnml);
  fclose($wcnml);

  $hlast= @fopen("/tmp/last_update.mrtg", "w") or die('Error!');
  fwrite($hlast,$last_now);
  fclose($hlast);
?>
