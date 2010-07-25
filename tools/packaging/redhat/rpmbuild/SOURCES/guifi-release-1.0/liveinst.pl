#!/usr/bin/perl
#
# install image into hard disk
#


$oldfile = "/usr/share/guifi-release/liveinst-guifi.cfg";
$newfile = "/home/liveuser/liveinst-guifi.cfg";
$oldhostname = "%HOSTNAME%";
$oldip = "%IP%";
$oldnetmask = "%NETMASK%";
$oldinternet = "%INTERNET%";
$oldguifi = "%GUIFI%";
$olddns1 = "%DNS%";

$defhostname = 'grafiques.XXX.guifi.net';
$defip = '10.1.0.2';
$defnetmask = '255.255.255.224';
$definternet = '10.1.0.3';
$defguifi = '10.1.0.1';
$defdns1 = '80.58.0.33';

print "Enter the HOSTNAME [$defhostname]: ";
$hostname = <STDIN>;
chomp($hostname);
if ( $hostname eq "" ) { $hostname = $defhostname };

print "Enter the IP ADDRESS [$defip]: ";
$ip = <STDIN>;
chomp($ip);
if ( $ip eq "" ) { $ip = $defip };

print "Enter the NETMASK [$defnetmask]: ";
$netmask = <STDIN>;
chomp($netmask);
if ( $netmask eq "" ) { $netmask = $defnetmask };

print "Enter the INTERNET GATEWAY (DSL/cable) [$definternet]: ";
$internet = <STDIN>;
chomp($internet);
if ( $internet eq "" ) { $internet = $definternet };

print "Enter the guifi.net GATEWAY (supernode) [$defguifi]: ";
$guifi = <STDIN>;
chomp($guifi);
if ( $guifi eq "" ) { $guifi = $defguifi };

print "Enter the DNS 1 [$defdns1]: ";
$dns1 = <STDIN>;
chomp($dns1);
if ( $dns1 eq "" ) { $dns1 = $defdns1 };

print "\n\nGoing to install with this settings:\n\n";
print "HOSTNAME: $hostname\n";
print "IP address/netmask: $ip / $netmask\n";
print "Gateways (Internet: $internet / guifi.net: $guifi)\n";
print "DNS ($dns1)\n";
print "Are the above settings correct? [y/N]? ";
$ack = <STDIN>;
chomp($ack);

if (( $ack eq "Y") or ( $ack eq "y")) {
  open(OF, $oldfile) or die "Can't open $oldfile : $!";
  open(NF, ">$newfile") or die "Can't open $newfile : $!";

  # read in each line of the file
  while ($line = <OF>) {
    $line =~ s/$oldhostname/$hostname/;
    $line =~ s/$oldip/$ip/;
    $line =~ s/$oldnetmask/$netmask/;
    $line =~ s/$oldinternet/$internet/;
    $line =~ s/$oldguifi/$guifi/;
    $line =~ s/$olddns1/$dns1/;
    print NF $line;
  }
  close(OF);
  close(NF);
} else { exit; }

print "guifi.net Setup done. Launching liveinst.\nContinue with the live install to disk procedure.\n";

`liveinst --kickstart=/home/liveuser/liveinst-guifi.cfg > /dev/null 2>/dev/null`;
