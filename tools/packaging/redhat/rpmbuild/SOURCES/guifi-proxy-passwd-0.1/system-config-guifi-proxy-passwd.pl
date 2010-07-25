#!/usr/bin/perl
#
# set the proxy service id at the guifi-proxy-passwd shell script
#

$oldfile = "/usr/share/guifi-proxy-passwd/proxypasswd.sh";
$newfile = "/usr/sbin/proxypasswd.sh";
$old = "ServiceId";
$defproxy= 2619;
print "Enter the number of the Proxy Service Id [$defproxy]: ";
$new = <STDIN>;
chomp($new);
if ($new eq "") { $new=$defproxy; }

print "Setting the Proxy Server Id to $new\n";
open(OF, $oldfile) or die "Can't open $oldfile : $!";
open(NF, ">$newfile") or die "Can't open $newfile : $!";

# read in each line of the file
while ($line = <OF>) {
    $line =~ s/$old/$new/;
    print NF $line;
}

unlink("/usr/sbin/guifi_passwd_auth");
if ( `uname -i` eq "i386" ) {
  `ln -s /usr/lib/squid/ncsa_auth /usr/sbin/guifi_passwd_auth`;
} else {
  `ln -s /usr/lib64/squid/ncsa_auth /usr/sbin/guifi_passwd_auth`;
}

close(OF);
close(NF);
chmod('0777',$newfile);
print "Successful: New Proxy Server Id has been set to $new\n";
print "Running proxypass.sh, check that you get a correct /usr/etc/passwd\n";
`/usr/sbin/proxypasswd.sh`;
$passwd = `wc -l /tmp/passwd | cut -f1 -d' '`;
if ( $passwd > 0 ) {
  print "/usr/etc/passwd got data, checking squid proxy server\n";
  `chkconfig squid on`;
  $squid = `ps -ef | grep squid | grep -v grep |wc -l`;
  if ( $squid == 0 ) {
    print "Squid is not running. Starting squid.\n";
    print `/sbin/service squid start`;
  } else {
    print "Squid already running. Reloading squid.\n";
    print `/sbin/service squid reload`;
  }
} else {
  print "\n\nWARNING: \n";
  print "/usr/etc/passwd didn't got data:\nDid you specified a valid proxy server id?\nDo you have network access to guifi.net site?\n\n";
}
print "Done. Press <Enter> to finish.";
$end = <STDIN>;
