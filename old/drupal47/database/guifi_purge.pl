#!/usr/bin/perl

use strict;
use DBI;

my ($dbi, $dbh, $sth, $sth2);
my ($db_user, $db_pass, $db_name);
my $datasource;

my ($sqlquery, $delete_query, $iface, $device, $link, $radio);
my $answer;
my @ary;

$db_name = "proves";
$datasource = "DBI:mysql:$db_name:localhost";

$db_user = "proves";
$db_pass = "bandoler";
$dbh = DBI->connect($datasource, $db_user, $db_pass);



#
# STEP 1: CHECK FOR ORPHANED INTERFACES
#

print "\n >> Treballant sobre la bd $db_name\n\n";
print "\n-----------------------------------------\n";
print " [1] Buscant interfícies orfes...";
print "\n-----------------------------------------\n";

$sqlquery = "select * from guifi_interfaces";
$sth = $dbh->prepare($sqlquery);
$sth->execute();

while ($iface = $sth->fetchrow_hashref())
{
    $sqlquery = "select * from guifi_devices "
	. "where id=".$iface->{device_id};
    
    $sth2 = $dbh->prepare($sqlquery);
    $sth2->execute();
    @ary = $sth2->fetchrow_array();
    

    if (!defined($ary[0]))
    {
	print "\nINCONSISTÈNCIA: la interfície $iface->{id} ($iface->{ipv4},".
	    " mask $iface->{netmask}, tipus $iface->{interface_type}) ".
	    "no té assignada un trasto vàlid.\n";
	print "   Número de device_id inexistent: $iface->{device_id}\n\n";
	print "   ELIMINAR INTERFÍCIE I ENLLAÇOS? [s/N] ";

	$answer = <STDIN>;
	chomp $answer;
	
	if ($answer eq "s" || $answer eq "S")
	{
	    print " ... Eliminant interfície i enllaços de $iface->{id}.\n";
	    $dbh->do("delete from guifi_interfaces where id=$iface->{id}");
	    $dbh->do("delete from guifi_links where interface_id="
		     .$iface->{id});
	    print "\n                                   [ OK ]\n";
	}
	else
	{
	    print " * Conservant la interfície.\n\n";
	}

    }


}

print "\n ... fi de fase 1.\n\n";



#
# STEP 2: CHECK FOR ORPHANED DEVICES
#

print "\n-----------------------------------------\n";
print " [2] Buscant trastos orfes...";
print "\n-----------------------------------------\n";

$sqlquery = "select * from guifi_devices";
$sth = $dbh->prepare($sqlquery);
$sth->execute();

while ($device = $sth->fetchrow_hashref())
{
    $sqlquery = "select * from guifi_location "
	. "where id=".$device->{nid};
    
    $sth2 = $dbh->prepare($sqlquery);
    $sth2->execute();
    @ary = $sth2->fetchrow_array();
    

    if (!defined($ary[0]))
    {
	print "\nINCONSISTÈNCIA: el trasto $device->{id} ($device->{nick},".
	    " contacte $device->{contact}, $device->{type}) ".
	    "no té assignat un node físic vàlid.\n";
	print "   guifi_location.id inexistent: $device->{nid}\n\n";
	print "   ELIMINAR TRASTO I LES SEVES INTERFÍCIES? [s/N] ";

	$answer = <STDIN>;
	chomp $answer;

	if ($answer eq "s" || $answer eq "S")
	{
	    $sth2 = $dbh->prepare("select * from guifi_interfaces ".
				  "where device_id=".$device->{id});
	    $sth2->execute();

	    while ($iface = $sth2->fetchrow_hashref)
	    {
		print " ... Eliminant interfície i enllaços de $iface->{id} ".
		    "($iface->{ipv4}, $iface->{netmask}).\n";
		$dbh->do("delete from guifi_interfaces "
			 ."where id=$iface->{id}");
		$dbh->do("delete from guifi_links where interface_id="
			 .$iface->{id})
	    }
	    
	    print " .... Eliminant trasto $device->{id} ($device->{nick})\n";
	    $dbh->do("delete from guifi_devices where id=$device->{id}");
	    $dbh->do("delete from guifi_radios where id=$device->{id}");
	    $dbh->do("delete from guifi_links where device_id=$device->{id}");
	    $dbh->do("delete from guifi_services where "
		     ."device_id=$device->{id}");
	    print "\n                                   [ OK ]\n"
	}
	else
	{
	    print " * Conservant el trasto.\n\n";
	}

    }

}


print "\n ... fi de fase 2.\n\n";



#
# STEP 3: CHECK FOR ORPHANED LINKS
#

print "\n-----------------------------------------\n";
print " [3] Buscant enllaços orfes...";
print "\n-----------------------------------------\n";

$sqlquery = "select * from guifi_links";
$sth = $dbh->prepare($sqlquery);
$sth->execute();

while ($link = $sth->fetchrow_hashref())
{
    foreach (qw/device_id interface_id nid/)
    {
	if ($_ eq "device_id")
	{
	    $sqlquery = "select * from guifi_devices "
		. "where id=".$link->{device_id};
	}
	elsif ($_ eq "interface_id")
	{
	    $sqlquery = "select * from guifi_interfaces "
		. "where id=".$link->{interface_id};
	}
	elsif ($_ eq "nid")
	{
	    $sqlquery = "select * from guifi_location "
		. "where id=".$link->{nid};
	}
	
	$sth2 = $dbh->prepare($sqlquery);
	$sth2->execute();
	@ary = $sth2->fetchrow_array();
	
	if (!defined($ary[0]))
	{
	    print "\nINCONSISTÈNCIA: l'enllaç $link->{id} ".
		" (tipus $link->{link_type}) és orfe.\n";
	    
	    if ($_ eq "device_id")
	    {
		print "   guifi_device inexistent: $link->{device_id}\n\n";
		$delete_query = "delete from guifi_links where id=$link->{id}"
		                . " and device_id=$link->{device_id}";
	    }
	    elsif ($_ eq "interface_id")
	    {
		print "   guifi_interface inexistent: "
		     ."$link->{interface_id}\n\n";
		$delete_query = "delete from guifi_links where id=$link->{id}"
		                . " and interface_id=$link->{interface_id}";

	    }
	    elsif ($_ eq "nid")
	    {
		print "   guifi_location inexistent: $link->{nid}\n\n";
		$delete_query = "delete from guifi_links where id=$link->{id}"
		                . " and nid=$link->{nid}";
	    }

	    print "   ELIMINAR ENLLAÇ? [s/N] ";
	    
	    $answer = <STDIN>;
	    chomp $answer;
	    
	    if ($answer eq "s" || $answer eq "S")
	    {
		print " ... Eliminant enllaç $link->{id}.\n";
		$dbh->do($delete_query);
		print "\n                                   [ OK ]\n";
	    }
	    else
	    {
		print " * Conservant l'enllaç.\n\n";
	    }
	    
	}
    }
    
    
}

print "\n ... fi de fase 3.\n\n";


#
# PHASE 4: Check for inconsistent entries to guifi_radios
# 

print "\n-----------------------------------------\n";
print " [4] Buscant ràdios orfes...";
print "\n-----------------------------------------\n";

$sqlquery = "select * from guifi_radios";
$sth = $dbh->prepare($sqlquery);
$sth->execute();


while ($radio = $sth->fetchrow_hashref())
{
    $sqlquery = "select * from guifi_location "
	. "where id=".$radio->{nid};
    
    $sth2 = $dbh->prepare($sqlquery);
    $sth2->execute();
    @ary = $sth2->fetchrow_array();
    

    if (!defined($ary[0]))
    {
	print "\nINCONSISTÈNCIA: la ràdio $radio->{id} ($radio->{ssid},".
	    " $radio->{mode}) ".
	    "no té assignat un node físic vàlid.\n";
	print "   guifi_location.id inexistent: $radio->{nid}\n\n";
	print "   ELIMINAR GUIFI_RADIO? [s/N] ";

	$answer = <STDIN>;
	chomp $answer;

	if ($answer eq "s" || $answer eq "S")
	{
	    print " .... Eliminant radio $radio->{id} ($radio->{ssid})\n";
	    $dbh->do("delete from guifi_radios where id=$radio->{id}");
	    print "\n                                   [ OK ]\n"
	}
	else
	{
	    print " * Conservant la ràdio.\n\n";
	}

    }

    else 
    {
        
        $sqlquery = "select * from guifi_devices "
            . "where id=".$radio->{id};
        
        $sth2 = $dbh->prepare($sqlquery);
        $sth2->execute();
        @ary = $sth2->fetchrow_array();
        
        
        if (!defined($ary[0]))
        {
            print "\nINCONSISTÈNCIA: la ràdio $radio->{id} ($radio->{ssid},".
                " $radio->{mode}) ".
                "no es correspon a cap entrada a guifi_devices.\n";
            print "   guifi_devices.id inexistent: $radio->{id}\n\n";
            print "   ELIMINAR GUIFI_RADIO? [s/N] ";
            
            $answer = <STDIN>;
            chomp $answer;
            
            if ($answer eq "s" || $answer eq "S")
            {
                print " .... Eliminant radio $radio->{id} ($radio->{ssid})\n";
                $dbh->do("delete from guifi_radios where id=$radio->{id}");
                print "\n                                   [ OK ]\n"
                }
            else
            {
                print " * Conservant la ràdio.\n\n";
            }
            
        }
    }

}

print "\n ... fi de fase 4.\n\n";
