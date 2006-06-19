<?php
include_once "networkutils.php";

if (!$link = mysql_connect('localhost', 'comesfa', 'bandoler')) {
   echo 'Could not connect to mysql';
   exit;
}

if (!mysql_select_db('comesfa46', $link)) {
   echo 'Could not select database';
   exit;
}



print "HtmlDir: /home/comesfa/mrtg/images\n";
print "ImageDir: /home/comesfa/mrtg/images\n";
print "LogDir: /home/comesfa/mrtg/logs\n";
print "LogFormat: rrdtool\n";
print "ThreshDir: /home/comesfa/mrtg/logs\n";
print "Forks: 6\n";
print "SnmpOptions: retries => 2, only_ip_address_matching => 0\n";
print "SnmpOptions: timeout => 0.5\n";

$sql    = 'SELECT title, ip FROM wifi_radio WHERE ip != ""';
$result = mysql_query($sql, $link);

if (!$result) {
   echo "DB Error, could not query the database\n";
   echo 'MySQL Error: ' . mysql_error();
   exit;
}

while ($row = mysql_fetch_assoc($result)) {
   print '# '.$row['title'].' - '.$row['ip']."\n";
   $item = _ipcalc($row['ip'],'255.255.192.0');
   $out = array (' ','.','-','?','&','%','$');
   $rrdfile = str_replace($out,"",$row['title']);
   if ($item['netid'] == '10.138.0.0') {
     print "Target[".$rrdfile."_5]: 5:public@".$row['ip'].':'."\n";
     print "SetEnv[".$rrdfile.'_5]: MRTG_INT_IP="" MRTG_INT_DESCR="eth0"'."\n";
     print "MaxBytes[".$rrdfile.'_5]: 1000000'."\n";
     print "Title[".$rrdfile."_5]: Tràfic a l'eth0 (LAN) de ".$row['title']."\n"; 
     print "PageTop[".$rrdfile."_5]: <H1>Tr&agrave;fic a l'eth0 (LAN) de ".$row['title']."</H1>
     <TABLE>
     <TR><TD>System:</TD>     <TD>".$row['title']."</TD></TR>
     <TR><TD>Maintainer:</TD> <TD>guifi@guifi.net</TD></TR>
     <TR><TD>Description:</TD><TD>eth0  </TD></TR>
     <TR><TD>IP:</TD>         <TD>".$row['ip']."</TD></TR>
     <TR><TD>Max Speed:</TD>  <TD>10.0 Mbits/s</TD></TR>
     </TABLE>"."\n";
     print "Target[".$rrdfile."_6]: 6:public@".$row['ip'].':'."\n";
     print "SetEnv[".$rrdfile.'_6]: MRTG_INT_IP="" MRTG_INT_DESCR="eth0"'."\n";
     print "MaxBytes[".$rrdfile.'_6]: 1000000'."\n";
     print "Title[".$rrdfile."_6]: Tràfic a l'eth1 (wLAN) de ".$row['title']."\n"; 
     print "PageTop[".$rrdfile."_6]: <H1>Tr&agrave;fic a l'eth1 (wLAN) de ".$row['title']."</H1>
     <TABLE>
     <TR><TD>System:</TD>     <TD>".$row['title']."</TD></TR>
     <TR><TD>Maintainer:</TD> <TD>guifi@guifi.net</TD></TR>
     <TR><TD>Description:</TD><TD>eth0  </TD></TR>
     <TR><TD>IP:</TD>         <TD>".$row['ip']."</TD></TR>
     <TR><TD>Max Speed:</TD>  <TD>10.0 Mbits/s</TD></TR>
     </TABLE>"."\n";
     print 'Title['.$rrdfile.'_ping]: Temps del ping'."\n";
     print 'PageTop['.$rrdfile.'_ping]: <H1>Lat&egrave;ncia '.$row['title']."</H1>
     <TABLE>
     <TR><TD>System:</TD>     <TD>".$row['title']."</TD></TR>
     <TR><TD>Maintainer:</TD> <TD>guifi@guifi.net</TD></TR>
     <TR><TD>Description:</TD><TD>ping  </TD></TR>
     <TR><TD>IP:</TD>         <TD>".$row['ip']."</TD></TR>
     </TABLE>"."\n";
     print 'Target['.$rrdfile.'_ping]: `/etc/mrtg/ping.sh '.$row['ip'].'`'."\n";
     print 'MaxBytes['.$rrdfile.'_ping]: 2000'."\n";
     print 'Options['.$rrdfile.'_ping]: growright,unknaszero,nopercent,gauge'."\n";
     print 'LegendI['.$rrdfile.'_ping]: Perduts %'."\n";
     print 'LegendO['.$rrdfile.'_ping]: Temps mig'."\n";
     print 'Legend1['.$rrdfile.'_ping]: Temps max. en ms'."\n";
     print 'Legend2['.$rrdfile.'_ping]: Temps min. en ms'."\n";
     print 'YLegend['.$rrdfile.'_ping]: RTT (ms)'."\n";
   }
  
}

mysql_free_result($result);

?> 
