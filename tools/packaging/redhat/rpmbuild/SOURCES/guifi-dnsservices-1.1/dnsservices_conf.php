#!/usr/bin/php
<?php
#
# set the configuration CNML
#

function getInput($length=255) {
  $fr = fopen("php://stdin", "r");
  $input = fgets($fr, $length);
  $input = rtrim($input);
  fclose($fr);

  return $input;
}


$DNSDataServerurl = "http://guifi.net";
$DNSGraphServerId = 0;

$questionurl = "DNSDataServer_url: without ending backslash, Usually always is [".$DNSDataServerurl."]";
$questionid = "Select your DNS Server Id to share your domains.[".$DNSGraphServerId."]";
$questionfetch = "Force to fetch DNS configuration now (CAUTION: This remove your actual configuration) [y]";


echo($questionurl.": ");
$r0 = getInput();

echo($questionid.": ");
$r1 = getInput();

echo($questionfetch.": ");
$r2 = getInput();

$DNSDataServerurl = $r0=="" ? $DNSDataServerurl : $r0;
$DNSGraphServerId = $r1=="" ? $DNSGraphServerId : $r1;

$config=<<< EOF
<?php

// DNSDataServer_url: without ending backslash, the url where the data is
\$DNSDataServer_url =  '$DNSDataServerurl';

// DNSGraphServerID: Default Graph Server ID
\$DNSGraphServerId = $DNSGraphServerId;

\$master_dir = "/var/named";
\$slave_dir = "/var/named/slaves";

\$chroot = "/var/named/chroot";

?>
EOF;

$h = fopen("/etc/dnsservices/config.php", "w") or die(date("YmdHi")." Unable to fetch CNML.\n");
fwrite($h,$config);
fclose($h);
if ($r2=="y"||$r2=="") {
  shell_exec("if [ -f /tmp/last_update.dns ] ; then /bin/rm /tmp/last_update.dns; fi");
  shell_exec("if [ -f /tmp/last_dns ] ; then /bin/rm /tmp/last_dns; fi");
  shell_exec("cd /var/named/chroot/etc/ && chown named:named /etc/dnsservices");
  echo "Retrieving CNML from ".$DNSDataServerurl." to configure new named.conf file\n";
  echo "Please wait. This may take several minutes.\n";
  $output = shell_exec("sudo -u named /usr/bin/php /usr/share/dnsservices/dnsservices.php");
  echo $output;
  $output = shell_exec("service named restart");
  echo $output;
}
echo "Successful: DNS Server has been configured\n";

?>
