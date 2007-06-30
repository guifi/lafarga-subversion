#!/usr/bin/perl

use warnings;
use strict;
use DBI;


my ($dbi, $dbh, $sth);
my ($db_user, $db_pass, $db_name);
my $datasource;

$db_name = "drupal";
$datasource = "DBI:mysql:$db_name:localhost";

$db_user = "drupal";
$db_pass = "drupal_password";
$dbh = DBI->connect($datasource, $db_user, $db_pass);



#
# Converts an ip/mask to a network (base ip + mask)
#
# Args: device ip / network mask
# Returns: network base ip / network mask
#
sub ipmask_to_network
{
    my ($ip, $mask) = @_;
    my $net;
    my ($baseip, $netmask);
    my $cmd;
   
    if (!$ip || !$mask)
    {
	die "Need to specify an ip/mask";
    }

    $cmd = "ipcalc -b ".$ip."/".$mask." | grep Network";
    $net = `$cmd`;
    $net =~ s/Network:\s+//;
    chomp $net;
    $net=~s/\s+//g;
    ($baseip, $netmask) = split('/', $net);
    return ($baseip, $netmask);
}


#
# Splits a network into a number of subnetworks
#
# Args: ip/mask, destination mask
# Returns: Array of networks (hashes) containing ip,mask
#
sub split_network
{
    my ($ip, $mask, $dmask) = @_;
    my ($baseip, $netmask);
    my $net;
    my $cmd;
    my @networks;
    my @ipcalc_output;
    
    if (!$ip || !$mask || !$dmask)
    {
	die "ERROR: Called with wrong arguments, ip=$ip, mask=$mask, ".
	    "dmask=$dmask";
    }

    $cmd = "ipcalc -b ".$ip."/".$mask." $dmask | grep -v /$mask "
	."| grep Network";
    @ipcalc_output = `$cmd`;
    
    foreach $net (@ipcalc_output)
    {
	$net =~ s/Network:\s+//;
	$net =~ s/\s+//g;
	chomp $net;
	($baseip, $netmask) = split('/',$net);
	push @networks, {
	    'ip' => $baseip,
	    'mask' => $netmask
	    };
	
    }

    return @networks;
}

#
# Returns the supernetwork to which a given network belongs.
#
# Args: ip, mask, supernetwork mask.
# Returns: array containing ip, mask of the supernetwork.
#
sub supernetwork
{
    my ($ip, $mask, $dmask) = @_;
    my @ary;
    
    if (!$ip || !$mask || !$dmask)
    {
	die "ERROR: Called with wrong arguments, ip=$ip, mask=$mask, ".
	    "dmask=$dmask";
    }

    @ary = split_network($ip, $mask, $dmask);
    
    return ($ary[0]->{ip}, $ary[0]->{mask});
}

#
# Extracts the networks from each interface, adds them to the network
# table and replaces the information in the "netmask" field with the
# network id. Also, extracts the interface network zone information 
# from the present DB.
#
sub afegir_xarxes_interficies 
{
    my ($sth, $sth2, $sth3);
    my ($ipv4, $net_base, $net_mask);
    my $net;
    my $tmp;
    my $sqlquery;
    my $debug;
    my @ary;
    my @count;

    #
    # Proceed to add the interfaces' networks to the database.
    #

    $sth = $dbh->prepare("select id, ipv4, netmask ".
			 "from guifi_interfaces where ipv4 IS NOT NULL");
    $sth->execute();
    while ($net = $sth->fetchrow_hashref)
    {
	($net_base, $net_mask) = ipmask_to_network($net->{ipv4}, 
						   $net->{netmask});
	
	$sth2 = $dbh->prepare("select count(*) from guifi_networks "
			      ."where base='$net_base' and "
			      ."mask='$net_mask'");
	$sth2->execute();
	@count = $sth2->fetchrow_array();
	
	if ($count[0] == 0) # Network doesn't exist yet, let's add it
	{

	    $debug = "select guifi_location.zone_id from "
		."guifi_location, guifi_devices, "
		."guifi_interfaces WHERE "
		."guifi_interfaces.device_id = "
		."guifi_devices.id AND "
		."guifi_devices.nid = guifi_location.id AND "
		."guifi_interfaces.id = ".$net->{id};
	    
	    $sth3 = $dbh->prepare("select guifi_location.zone_id from "
				  ."guifi_location, guifi_devices, "
				  ."guifi_interfaces WHERE "
				  ."guifi_interfaces.device_id = "
				  ."guifi_devices.id AND "
				  ."guifi_devices.nid = guifi_location.id AND "
				  ."guifi_interfaces.id = ".$net->{id});
	    $sth3->execute();
	    @ary = $sth3->fetchrow_array();
    

	    if (defined($ary[0])) # There's a zone ID
	    {
		$sqlquery = "insert into guifi_networks set base='".
		    $net_base."',mask=".$net_mask.",zone=".$ary[0].
		    ",assigned=1";
	    } 
	    else  # No zone ID! That's an inconsistency, warn the admin.
	    {
		my @ary;

		print "!!!! Warning: Network $net_base/$net_mask "
		    ."not assigned to a zone (probably belongs to an "
		    ."orphaned device or interface). I'm NOT adding it "
		    ."to the network DB.";

		$sqlquery = "select device_id from guifi_interfaces "
		    ."where guifi_interfaces.id=".$net->{id};

		$sth3 = $dbh->prepare($sqlquery);
		$sth3->execute();
		@ary = $sth->fetchrow_array();
		
		print "Here's the device id: $ary[0]\n";

		$sqlquery = "select nid from guifi_devices "
		    ."where id=".$ary[0];

		$sth3 = $dbh->prepare($sqlquery);
		$sth3->execute();
		@ary = $sth->fetchrow_array();
		
		print "Here's the location id: $ary[0]\n";

	    }

	    #
	    # Add the network to the DB
	    #
	    $dbh->do($sqlquery);
	    

	    # Update the interface info, make it point to the network.
            #
	    $sth3 = $dbh->prepare("select id from guifi_networks where "
				  ." base='".$net_base."' and mask='"
				  .$net_mask."'");
	    $sth3->execute();
	    @ary = $sth3->fetchrow_array();
	    
	    if (defined($ary[0]) && $ary[0] > 0)
	    {
		# We temporarily place the network id in the netmask
		# field, to change it later in alter_db()
		$dbh->do("update guifi_interfaces set netmask=$ary[0] "
			 ."where id=".$net->{id});
	    }
	    else 
	    {
		die "Error adding network! ($net_base/$net_mask)";
	    }
	}
	else
	{
	    # Network already defined, we only have to point 
            # the interface as belonging to it

	    $sth3 = $dbh->prepare("select id from guifi_networks where "
				  ." base='".$net_base."' and mask='"
				  .$net_mask."'");
	    $sth3->execute();
	    @ary = $sth3->fetchrow_array();
	    
	    if (defined($ary[0]) && $ary[0] > 0)
	    {
		# We temporarily place the network id in the netmask
		# field, to change it later in alter_db()
		$dbh->do("update guifi_interfaces set netmask=$ary[0] "
			 ."where id=".$net->{id});

	    }

	}
    }
}



#
# Alters the database structure to make it network-hierarchy ready
#
sub alter_db
{
    my ($sth, $net_base, $net_mask, $net);

    #
    # Networks belonging to an interface are NOT divided by
    # definition, and we mark them as assigned, whereas networks
    # currently on the DB are listed there in order to extract smaller
    # subnetworks from them, and are therefore all implicitly divided
    # and listed only for the sake of summarization. 
    # The tree consistency (joining disconnected, undivided leaves with 
    # the divided branches) will have to be checked afterwards.
    #

    $dbh->do("ALTER TABLE guifi_networks add divided tinyint ".
	     "not null default 0");

    $dbh->do("ALTER TABLE guifi_networks add assigned tinyint ".
	     "not null default 0");

    $sth = $dbh->prepare("select * from guifi_networks");
    $sth->execute();
    
    while ($net = $sth->fetchrow_hashref)
    {
	$dbh->do("update guifi_networks set divided=1,assigned=0 ".
		 "where id=".$net->{id});
	($net_base, $net_mask) = ipmask_to_network ($net->{base}, 
						    $net->{mask});
	$dbh->do("update guifi_networks set mask=".$net_mask." where id=".
		 $net->{id});
    }

    $dbh->do("ALTER TABLE guifi_networks add parent int(10) ".
	     "unsigned not null default '0'");
    
}

sub db_split_network
{
    my ($base, $mask) = @_;
    my ($sth, $net, $subnet);
    my @ary;
    my @ary2;
    my $subnet_in_db;

    $sth = $dbh->prepare("select count(*) from guifi_networks where ".
			 "base='$base' AND mask='$mask'");
    $sth->execute();
    @ary = $sth->fetchrow_array();
    
    if ($ary[0] != 1)
    {
	die "ERROR: S'han trobat $ary[0] xarxes $base/$mask a la BD";
    }

    $sth = $dbh->prepare("select * from guifi_networks where base='$base' ".
			 "AND mask='$mask'");
    $sth->execute();
    $net = $sth->fetchrow_hashref;
    
    # print "     Partint $net->{base}/$net->{mask}\n";

    $dbh->do("update guifi_networks set divided=1 where base='$net->{base}' ".
	     "and mask='$net->{mask}'");

    @ary = split_network($net->{base}, $net->{mask}, 
			 $net->{mask}+1);
    
    foreach $subnet (@ary)
    {
#	print "       Subxarxa: $subnet->{ip}/$subnet->{mask}\n";
	$sth = $dbh->prepare("select count(*) from guifi_networks where ".
			     "base='$subnet->{ip}' and mask='$subnet->{mask}'");
	$sth->execute();
	@ary2 = $sth->fetchrow_array();
	
	if ($ary2[0] == 0)
	{
	    # Subnet doesn't exist, add it (with parent's zone and type)
	    $dbh->do("insert into guifi_networks set base='$subnet->{ip}',".
		     "mask='$subnet->{mask}',zone=$net->{zone},".
		     "parent=$net->{id},network_type='$net->{network_type}'");
	    $dbh->do("update guifi_networks set divided=1 ".
		     "where id=$net->{id}");
	}
	elsif ($ary2[0] == 1)
	{
	    # Subnet exists, link it
	    print "   Linking existing net ($subnet->{ip}/$subnet->{mask})\n";

	    $sth = $dbh->prepare("select * from guifi_networks where ".
				 "base='$subnet->{ip}' ".
				 "and mask='$subnet->{mask}'");
	    $sth->execute();
	    
	    $subnet_in_db=$sth->fetchrow_hashref;
	    
	    $dbh->do("update guifi_networks set parent=$net->{id} ".
		     "where id=$subnet_in_db->{id}");	    
	    $dbh->do("update guifi_networks set divided=1 ".
		     "where id=$net->{id}");	    
	}
	elsif ($ary2[0] > 1)
	{
	    # Database inconsistency
	    print "select count(*) from guifi_networks where ".
		"base='$subnet->{ip}' and mask='$subnet->{mask}'\n";
	    die "Error, s'han trobat xarxes duplicades: ".
		"$subnet->{ip}/$subnet->{mask}";
	    
	}
    }
}


#
# Looks for a supernetwork in the db containing the network 
# passed as an argument.
#
sub look_for_supernetwork
{
    my ($base, $mask) = @_;
    my ($supbase, $supmask);
    my $sth;
    my $supernet;
    my $last;

    die "Base and mask need to be specified!" if (!$base || !$mask);

    print "Looking for a supernetwork to $base/$mask...";

    # Start looking for the supernetwork immediately above
    $supmask = $mask-1;

    $last=0;
    while ($supmask > 0 && !$last)
    {

	($supbase, $supmask) = supernetwork($base, $mask, $supmask);

	$sth = $dbh->prepare("select * from guifi_networks where ".
			     "base='$supbase' and mask='$supmask'");
	$sth->execute();
	if ($supernet = $sth->fetchrow_hashref())
	{
	    $last = 1;
	    print " found!\n";
	}
	else
	{
	    $supmask--;
	}
    }
    
    if (!$last)
    {
	print "not found.\n";
	$supernet->{base} = "";
	$supernet->{mask} = "";
    }
    
    
    return ($supernet->{base}, $supernet->{mask});

}



#
# Checks the network tree consistency, linking parents and sons.
#
sub network_tree_consistency
{
    my ($sth, $sth2);
    my ($net);
    my ($sup_base, $sup_mask);
    my $mask;

    #
    # PHASE 1:
    # Manually-added global divided network hierarchy
    #
    print "\n\nPHASE 1: Manual network hierarchy (summarized)\n";

    $sth =$dbh->prepare("select base,mask,zone,divided,parent from ".
			"guifi_networks where divided=1");
    $sth->execute();
    
    while ($net = $sth->fetchrow_hashref())
    {
	# Look for a supernetwork to which $net could belong
	
	($sup_base, $sup_mask) = look_for_supernetwork($net->{base}, 
						       $net->{mask});

	# If there's one, keep splitting it and setting the hierarchy
	# information up until we arrive to the original $net

	if ($sup_base ne "")
	{
	    print "(S) NET TREE: found supernetwork ($sup_base/$sup_mask), ".
		"splitting it until we get to $net->{mask}\n";
	    db_split_network($sup_base, $sup_mask);
	    $mask = $sup_mask+1;
	    while ($mask < $net->{mask})
	    {
		($sup_base, $sup_mask) = supernetwork($net->{base}, 
						      $net->{mask}, $mask);
		print "(S) NET TREE: Building chain... ($sup_base/$mask)\n";
		db_split_network($sup_base, $sup_mask);
		$mask++;
	    }
	    print "(S) NET TREE: Chain complete, check the parent ".
		"of $net->{base}/$net->{mask}. \n";
	}
    }


    #
    # PHASE 2:
    # Interface network hierarchy
    #
    print "\n\n\nPHASE 2: Interface network hierarchy (assigned)\n";

    $sth =$dbh->prepare("select base,mask,zone,divided,parent from ".
			"guifi_networks where assigned=1");
    $sth->execute();
    
    while ($net = $sth->fetchrow_hashref())
    {
	# Look for a supernetwork to which $net could belong
	($sup_base, $sup_mask) = look_for_supernetwork($net->{base}, 
						       $net->{mask});

	# If there's one, keep splitting it and setting the hierarchy
	# information up until we arrive to the original $net

	if ($sup_base ne "")
	{
	    print "(I) NET TREE: found supernetwork ($sup_base/$sup_mask), ".
		"splitting it until we get to $net->{mask}\n";
	    db_split_network($sup_base, $sup_mask);
	    $mask = $sup_mask+1;
	    while ($mask < $net->{mask})
	    {
		($sup_base, $sup_mask) = supernetwork($net->{base}, 
						      $net->{mask}, $mask);
		print "(I) NET TREE: Building chain... ($sup_base/$mask)\n";
		db_split_network($sup_base, $sup_mask);
		$mask++;
	    }
	    print "(I) NET TREE: Chain complete, check the parent ".
		"of $net->{base}/$net->{mask}. \n";
	}
    }
}



alter_db();
afegir_xarxes_interficies();
network_tree_consistency();
