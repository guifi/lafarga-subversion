<?php

if (file_exists("../common/config.php")) {
   include_once("../common/config.php");
} else {
  include_once("../common/config.php.template");
}

$now = time();
$mlast= @fopen("/tmp/last_mrtg", "r");
if ($mlast)
  $last = fgets($mlast);
else 
  $last = 0;
print "Last: ".date('Y/m/d H:i:s',((int)$last)+(60*60))."\n";
print "Now: ".date('Y/m/d H:i:s',(int)$now)."\n";
print "ServerId: ".$SNPGraphServerId."\n";
#
# Looks if the files has been refreshed at least between 60 an 90 mins ago
# range 60..90 depends on $SNPGraphServerId mod 30
# if is still fresh, does not even looks to the server to check if it has changed
#
$secs = $SNPGraphServerId % 285;
$mins = $SNPGraphServerId % 30;
$nanos = $SNPGraphServerId % 10;
if (($last) and ($now < ($last +  ((60 + $mins) * 60)))) {
  fclose($mlast);
  echo "Still fresh.\n";
  exit();
}

#
# Local file is not fresh, so looks to the server and cheks if has changed
#
$hlastnow = @fopen($SNPDataServer_url."/guifi/refresh/cnml", "r") or die('Error reading changes\n');
$last_now = fgets($hlastnow);
fclose($hlastnow);
$hlast= @fopen("/tmp/last_update.mrtg", "r");
if (($hlast) and ($last_now == fgets($hlast))) {
  fclose($hlast);
  echo "No changes.\n";
  $hlast= @fopen("/tmp/last_mrtg", "w+") or die('Error!');
  fwrite($hlast,$now);
  fclose($hlast);
  exit();
}
print "Sever CNML dated as: ".date('Y/m/d H:i:s',$last_now)."\n";

#
# Server CNML has changed, so going to call the server for the new file
# Befoge calling, sleep $SNPGrahServerId mod 285 (4 min, 45 segs) to spread across that
# timeslot.
#

print "Waiting for  ".$secs.".".$nanos." seconds\n";
time_nanosleep($secs,($nanos * 10000000));
print date('Y/m/d H:i:s')."\n";

$hf = @fopen($MRTGConfigSource,"r") or die('Error reading MRTG csv input\n"');
$cf = @fopen('../data/mrtg.cfg','w+');

fputs($cf,sprintf($rrdtool_header,$rrdimg_path,$rrdimg_path,$rrddb_path,$rrddb_path));

while ( $buffer = fgets($hf, 4096) ) {
	if (substr($buffer,0,1) == '#')
	  continue;
	  
//  $buffer = substr($buffer,0,-1);
  $buffer = str_replace("\n","",$buffer);
  $dev=explode(',',$buffer);

  fputs($cf,sprintf($mrtg_ping_template,
                 $dev[0],
                 $dev[1],
                 $dev[0],
                 $dev[1],
                 $dev[1],
                 $dev[2],
                 $dev[0],
                 $dev[2],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0],
                 $dev[0])
       );
  if (!isset($dev[3]) or empty($dev[3]))
    continue;
  $t = explode('|',$dev[3]); 
  //print_r($t);

  foreach ($t as $k=>$r)  {
    // is the snmp Index given??
    if (is_numeric($r)) {
      $rn = $dev[0].'-'.$r;
      $trap = $r;
      $wn = 'wLan';
    } // end if numeric snmp Index
    else {
      $rn = $dev[0].'-'.$k;
      // snmp is given by interface name
      $d = explode(';',$r);
      $wn = $d[1];
      $trap = '\\'.$d[0];
    }
    fputs($cf,sprintf($mrtg_traffic_template."\n",
                   $rn,
                   $trap,
                   $dev[2],
                   $rn,
                   $dev[2],
                   $dev[1],
                   $rn, $rn,
                   $wn,
                   $dev[1],
                   $rn,
                   $wn,
                   $dev[1],
                   $dev[1],
                   $wn)
         );

  } // foreach interface
  
                 
  
}
fclose($hf);
fclose($cf);

$hlast= @fopen("/tmp/last_update.mrtg", "w+") or die('Error!');
fwrite($hlast,$last_now);
fclose($hlast);
$hlast= @fopen("/tmp/last_mrtg", "w+") or die('Error!');
fwrite($hlast,$now);
fclose($hlast);

?>
