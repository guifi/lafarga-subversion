<?php

function singleclick($radio) {
$version = "v2.2";
$rc_startup = "";

  $result = db_query( "SELECT * "
                      ."FROM {wifi_radio} r "
                      ."WHERE r.rid = " .$radio);
  $row = db_fetch_array($result);
  if ($row["firmware"] == 'n/d') {
	$output = "\n<br>ERROR: I do need a firmware selected at the radio web interface.";
	$output .= "\n<br>ERROR: Necessito que hi hagi un firmware a la base de dades de ràdios.";
        return $output;
  } else {
	$output = "\n<br># Generated for: ";
	$output .= $row["firmware"];
  }
  $output .= "\n<br>#"
          ."\n<br># unsolclic: " .$version 
          ."\n<br># entra amb telnet o ssh a la teva radio i executa-hi aquest arxiu."
          ."\n<br># open a telnet/ssh session on your device and run the script below."
          ."\n<br>#\n<br>" ;
  $output .= singleclick_network_vars($row);
  if (($row["mode"] == 'AP') || ($row["mode"] == 'AP/WDS')) {
    $output .= singleclick_wds_vars($radio);
  }
  if (($row["mode"] == 'Client') || ($row["mode"] == 'AP/Client Link')) {
    $output .= singleclick_client_vars($row);
  }
  $output .= singleclick_vlan_vars($radio,$rc_startup)
            .singleclick_rc_startup($row,$version,$rc_startup)
            ."\n<br>nvram commit"
            ."\n<br>reboot"
            ."\n<br>";
  return $output;
}

function singleclick_client_vars() {
  return $output;
}

function singleclick_qos_vars() {
  $cmd = "\n<br>nvram set ";

  $output = "\n<br>#"
           ."\n<br># QoS" 
           .$cmd ."wshaper_enable=1"
           .$cmd ."wshaper_dev=LAN"
           .$cmd ."action_service=filters"
           .$cmd ."svqos_svcs=\"h323 l7 0:0 10 |"
                 ."\n<br> "
                 ." aim l7 0:0 20 |"
                 ." jabber l7 0:0 20 |"
                 ." http l7 0:0 20 |"
                 ." HTTPS tcp 443:443 20 |"
                 ." irc l7 0:0 20 |"
                 ." Telnet tcp 23:23 20 |"
                 ." Ping icmp 0:0 20 |"
                 ." SSH tcp 22:22 20 |"
                 ." msn l7 0:0 20 |"
                 ."\n<br>"
                 ." audiogalaxy l7 0:0 40 |"
                 ." bearshare l7 0:0 40 |"
                 ." bittorrent l7 0:0 40 |"
                 ." edonkey l7 0:0 40 |"
                 ." FTP tcp 21:21 40 |"
                 ." flash l7 0:0 40 |"
                 ." Gnutella p2p 0:0 40 |"
                 ." jpeg l7 0:0 40 |"
                 ." SFTP tcp 115:115 40 |"
                 ." postscript l7 0:0 40 |"
                 ." pdf l7 0:0 40 |"
                 ." quicktime l7 0:0 40 |"
                 ."\n<br>\"";
// Mentre duri el bug al QoS, res;
  return "";
  return $output;
}

function singleclick_rc_startup($radio, $version, $rc_startup) {
  $cmd = "\n<br>nvram set ";

  $output = $cmd ."rc_startup=\"<br>#!/bin/ash"
	    ."\n<br>#"
            ."\n<br># unsolclic: " .$version
	    ."\n<br>#"
            .$rc_startup
            ."\n<br>/bin/sleep 10"
            ."\n<br>/usr/sbin/wl shortslot_override 0";

//  No longer needed in production Alchemy & Talisman releases  
//  switch ($radio["mode"]) {
//    case 'AP': case 'AP/WDS': case 'AP/Client Link':
//       $output .= "\n<br># pegats en versions pre5.3 a 6.0rc6a"
//	    ."\n<br>/bin/sed -i 's/passive interface lo//' /tmp/ospfd.conf"
//	    ."\n<br>/bin/kill -9 \`/bin/ps |/bin/grep zebra|/usr/bin/cut  -c1-5|/usr/bin/head -n 1\`; /usr/sbin/zebra -d -f /tmp/zebra.conf"
//	    ."\n<br>/bin/kill -9 \`/bin/ps |/bin/grep ospfd|/usr/bin/cut  -c1-5|/usr/bin/head -n 1\`; /usr/sbin/ospfd -d -f /tmp/ospfd.conf";
//       break;
//  } 
  $output .= "\n<br>\"";
	    
            
  return $output;
}

function singleclick_network_vars($radio) {
   $cmd = "\n<br>nvram set ";
   $netmask = _singleclick_netmask_by_hosts($radio["hosts"]);

   $pos = strrpos($radio["ip"],'.');

   $output = "\n# " .$radio["title"]
             ."<br>\n# Global network parameters"
             .$cmd ."router_name=" .$radio["title"]  
             .$cmd ."wan_hostname=" .$radio["title"]  
             .$cmd ."lan_domain=guifi.net"
             .$cmd ."wan_domain=guifi.net"
             .$cmd ."http_passwd=guifi"
	     .$cmd ."time_zone=\"+01 2 2\""
	     .$cmd ."sv_localdns=10.138.0.2"
	     .$cmd ."wan_dns=\"10.138.0.2 10.138.25.68 10.138.0.4\""
	     .$cmd ."wl_net_mode=mixed"
	     .$cmd ."wl_afterburner=on"
	     .$cmd ."wl_frameburst=on"
	     .$cmd ."txpwr=80"
             .$cmd ."block_wan=0";
   if ($radio["firmware"] == 'Talisman Sveasoft') {
   $output .=
              $cmd ."ident_pass=0"
             .$cmd ."multicast_pass=0"
             .$cmd ."wl_closed=0"
             .$cmd ."block_loopback=0";
   }
   $output .=
	      "<br>\n# Management"
	     .$cmd ."telnetd_enable=1"
	     .$cmd ."sshd_enable=1"
	     .$cmd ."sshd_passwd_auth=0"
	     .$cmd ."sshd_authorized_keys=\"ssh-rsa&nbsp<br>"
                   ."AAAAB3NzaC1yc2EAAAABIwAAAIEAzxV/QJ9zg9PMTET9wL5qSdFr7K7EayBVuLT3kmUPgUl6I5JmKoKCsQUThaHh8cqbam3kwwVkcNNzylMy1BbUm+KXIX4gxAN6KylSFBFnPNAgrSU6j24SzQQ1Saqv9egn514ZT3MbZ4Appq86I7b7LlZ9k30rSUetBRDeb/FbhgU=&nbsp;<br>"
                   ."&nbsp;root@linux-seri-1\""
	     .$cmd ."remote_management=1"
	     .$cmd ."remote_mgt_https=1"
	     .$cmd ."boot_wait=on"
	     .$cmd ."snmpd_enable=1"
	     .$cmd ."snmpd_syslocation=" .$radio["title"]
	     .$cmd ."snmpd_sysname=guifi.net"
	     .$cmd ."snmpd_syscontact=guifi_at_guifi.net"
	;
 
   switch ($radio["mode"]) {
   case "AP/WDS": case "AP":
     $output .= "<br>\n# AP mode"
       .$cmd ."wl_mode=ap"
       .$cmd ."wl0_mode=ap"
       .$cmd ."wan_proto=disabled"
       .$cmd ."lan_ipaddr=" .$radio["ip"] 
       .$cmd ."lan_gateway=" .$radio["ip"] 
       .$cmd ."lan_netmask=" .$netmask
       .$cmd ."wl_channel=" .$radio["channel"]
       .$cmd ."wl_ssid=guifi.net-" .$radio["ssid"]
       ;
     break;
   case 'Client':
     $query = db_query("SELECT c.*, r.ip apip, r.hosts aphosts, r.ssid apssid, r.mac mac "
                      ."FROM {wifi_link} c, {wifi_radio} r "
                      ."WHERE (c.rid1 = '%d' or c.rid2 = '%d') "
		      ."AND (((c.rid1 = r.rid) or (c.rid2 = r.rid)) AND r.rid <> '%d') "
                      ."AND c.link_type = 'AP/Client'",
			$radio["rid"],$radio["rid"], $radio["rid"]);
     if (db_num_rows($query) <> 1) {
	return "ERROR: Could not determine AP, please setup a single link for this radio in AP/Client mode";
     }
     $ap = db_fetch_array($query);
     $apnetmask = _singleclick_netmask_by_hosts($ap["aphosts"]);
     $output .= "<br>\n# Client mode"
       .$cmd ."wl_mode=wet"
       .$cmd ."wl0_mode=wet"
//       .$cmd ."wan_proto=dhcp"
       .$cmd ."lan_gateway=" .$ap["apip"] 
       .$cmd ."wl_ssid=guifi54." .$ap["apssid"]
       .$cmd ."wl0_ssid=guifi54." .$ap["apssid"]
       .$cmd ."wl0_maclist=" .$ap["mac"]
       .$cmd ."wl0_mac_list=" .$ap["mac"]
       .$cmd ."wl_maclist=" .$ap["mac"]
       .$cmd ."wl_mac_list=" .$ap["mac"]
       .$cmd ."wl_macmode=allow"
       .$cmd ."wl0_macmode=allow"
       .$cmd ."wl0_macmode1=other"
       .$cmd ."wl_macmode1=other"
       ;
     if ($radio["ip"]) {
        $output .= $cmd ."wan_proto=static"
                 .$cmd ."wan_ipaddr=" .$radio["ip"]
                 .$cmd ."wan_gateway=" .$ap["apip"]
                 .$cmd ."wan_netmask=" .$apnetmask;
     } else
        $output .= $cmd ."wan_proto=dhcp";
     break;
   } 
   switch ($radio["mode"]) {
   case "AP/WDS": case "AP": case "AP/Client Link":
     switch ($radio["firmware"]) {
       case "Alchemy Sveasoft":
          $output .= "<br>\n# Alchemy Routing OSPF"
	     .$cmd ."wk_mode=ospf"
	     .$cmd ."dr_setting=3"
	     .$cmd ."route_default=1"
	     .$cmd ."dr_lan_rx=1 2"
	     .$cmd ."dr_lan_tx=1 2"
	     .$cmd ."dr_wan_rx=1 2"
	     .$cmd ."dr_wan_tx=1 2"
             ;
           break;
       case "Talisman Sveasoft":
          $output .= "<br>\n# Talisman Routing OSPF"
	     .$cmd ."wk_mode=router"
	     .$cmd ."routing_lan=on"
	     .$cmd ."routing_wan=on"
	     .$cmd ."routing_ospf=on"
             .$cmd ."bird_conf=\"router id " .$radio["ip"] .";<br>\n"
                   ."protocol kernel { learn; persist; scan time 10; import all; export all; }<br>\n"
                   ."protocol device { scan time 10; }<br>\n"
                   ."protocol direct { interface \\\"*\\\"; }<br>\n"
                   ."protocol ospf WRT54G_ospf {<br>\n"
                   ."&nbsp;area 0 {<br>\n"
// no pwd for alchemy compat
//                   ."interface \\\"*\\\" { cost 1; authentication simple; password \\\"guifi\\\"; };<br>\n" 
                   ."interface \\\"*\\\" { cost 10; };<br>\n" 
//                   ."interface \\\"br0\\\" { cost 10; };<br>\n"
//                   ."interface \\\"vlan1\\\" { cost 10; };<br>\n"
//                   ."interface \\\"vlan2\\\" { cost 10; };<br>\n"
//                   ."interface \\\"vlan3\\\" { cost 10; };<br>\n"
//                   ."interface \\\"vlan4\\\" { cost 10; };<br>\n"
//                   ."interface \\\"wds*\\\" { cost 10; };<br>\n"
                   ."}; }\"<br>\n"
             ;
           break;
       }
     $output .= "<br>\n# Firewall disabled"
             .$cmd ."filter=off";
     // DHCP
     $output .= "<br>\n# DHCP";
     // Going to find DHCP Statics 
     $query = db_query("SELECT r.ip clientip, r.mac clientmac, r.ssid clientssid "
                      ."FROM {wifi_link} c, {wifi_radio} r "
                      ."WHERE (c.rid1 = '%d' or c.rid2 = '%d') "
                      ."AND (((c.rid1 = r.rid) or (c.rid2 = r.rid)) AND r.rid <> '%d') "
                      ."AND c.link_type = 'AP/Client' ORDER BY 1",
                        $radio["rid"],$radio["rid"], $radio["rid"]);
     $dhcp_statics = "";
     $start_dhcp = substr($radio["ip"], strrpos($radio["ip"],".")+1) + 3;
     while ($client = db_fetch_array($query)) {
	if ($client["clientip"]) {
             $dhcp_statics .= " " .$client["clientmac"] ."-" .$client["clientip"] ."-" .$client["clientssid"] ."&nbsp<br>\n";
     	     $start_dhcp = substr($client["clientip"], strrpos($client["clientip"],".")+1) + 3;
	}
     }
     if ($radio["firmware"] == 'Talisman Sveasoft')
     {
	$dhcp_statics = '"' .$dhcp_statics .'"';
     	$output .=  $cmd ."dhcp_statics=" .$dhcp_statics;
     }
     $output .= $cmd ."dhcp_start=". $start_dhcp ;
      

     break;
   case "Client":
     $output .= "<br>\n# Routing GATEWAY"
	     .$cmd ."wk_mode=gateway"
	     .$cmd ."dr_setting=0"
	     .$cmd ."route_default=1"
	     .$cmd ."dr_lan_rx=0"
	     .$cmd ."dr_lan_tx=0"
	     .$cmd ."dr_wan_rx=0"
	     .$cmd ."dr_wan_tx=0"
             ."<br>\n# Firewall enabled"
             .$cmd ."filter=on"
             .$cmd ."rc_firewall=\"/usr/sbin/iptables -I INPUT -p udp --dport 161 -j ACCEPT<br>\n"
                   ."/usr/sbin/iptables -I INPUT -p tcp --dport 22 -j ACCEPT\"";
             ;
     break;
   }

   return $output;
}

function _singleclick_netmask_by_hosts($hosts) {
        return _netmask_by_hosts($hosts);
	switch($hosts) {
	case '1': $netmask = '255.255.255.255'; break;
	case '2': $netmask = '255.255.255.252'; break;
	case '6': $netmask = '255.255.255.248'; break;
	case '14': $netmask = '255.255.255.240'; break;
	case '30': $netmask = '255.255.255.224'; break;
	case '62': $netmask = '255.255.255.192'; break;
	case '126': $netmask = '255.255.255.128'; break;
	case '254': $netmask = '255.255.255.0'; break;
	case '510': $netmask = '255.255.254.0'; break;
	case '1022': $netmask = '255.255.252.0'; break;
	case '2046': $netmask = '255.255.248.0'; break;
	case '4094': $netmask = '255.255.240.0'; break;
	case '8190': $netmask = '255.255.224.0'; break;
	case '16382': $netmask = '255.255.192.0'; break;
	case '32766': $netmask = '255.255.128.0'; break;
	case '65534': $netmask = '255.255.128.0'; break;
	case '131070': $netmask = '255.254.0.0'; break;
	case '262142': $netmask = '255.252.0.0'; break;
	case '524286': $netmask = '255.248.0.0'; break;
	case '1048574': $netmask = '255.240.0.0'; break;
	case '2097150': $netmask = '255.224.0.0'; break;
	case '4194304': $netmask = '255.192.0.0'; break;
	case '8388606': $netmask = '255.128.0.0'; break;
	case '16777214': $netmask = '255.0.0.0'; break;
	default: $netmaswk = '0.0.0.0'; break;
        }
	return($netmask);
}


function singleclick_vlan_vars($radio = 0,&$rc_startup) {
  $cmd = "\n<br>nvram set ";

  $result = db_query( "SELECT  r.rid, r.title, r.mac, r.ssid, c.link_type, "
                      ." r.ip, c.rid1, c.rid2, c.ip1, c.ip2, c.hosts, c.state " 
                      ."FROM {wifi_radio} r, {wifi_link} c "
                      ."WHERE r.rid = " .$radio
                      ."  AND c.link_type like 'Cable - vlan%' "
                      ."  AND (c.rid1 = " .$radio
                      ."   OR c.rid2 = " .$radio .")"
                      ."ORDER BY c.link_type");

  
  $linknum = 2;



  while ( ($vlan = db_fetch_array($result)) and ($linknum <= 4) ) {
    if ($linknum == 2)  {
       $output = "\n<br># VLANs -- radio: " .$radio ."-" .$vlan["title"];
       $output .= $cmd ."vlans=1";
       $output .= $cmd ."port2vlans=2";
       $output .= $cmd ."port3vlans=3";
       $output .= $cmd ."port4vlans=4";
       $output .= $cmd ."port5vlans=\"0 1 2 3 4 16\"";
       $rc_startup .= "\n<br># VLANs";

    }
    if ($vlan["rid"] == $vlan["rid1"]) {
       $rid_link = $vlan["rid2"];
       $ipaddr = $vlan["ip1"];
    } else {
       $rid_link = $vlan["rid1"];
       $ipaddr = $vlan["ip2"];
    }

    if ($ipaddr <> $vlan["ip"]) {
      $reslink = db_query("SELECT r.title, r.ssid, r.mac "
                                 ."FROM {wifi_radio} r "
                                 ."WHERE rid = " .$rid_link, 
                                 $conn);
      $rlink  = db_fetch_array($reslink);
      $rc_startup .= "\n<br># " .$rlink["title"];

      $netmask = _singleclick_netmask_by_hosts($vlan["hosts"]);
      $psubnet = strrpos($netmask,'.') + 1;
      $hosts_net = 256 - substr($netmask, $psubnet);
      $broadcast = substr($ipaddr,0, strrpos($ipaddr,'.') + 1)
      		.( (floor(substr($ipaddr, strrpos($ipaddr,".")+1) / $hosts_net) * $hosts_net)
                    + ( $hosts_net - 1));

      $rc_startup .= "\n<br>";
      if ($vlan["state"] <> 'Working')
        $rc_startup .= "# ";
      $rc_startup .= "ifconfig " .substr($vlan["link_type"],8) ." " .$ipaddr 
                 ." netmask " .$netmask
                 ." broadcast " .$broadcast;
      $linknum = $linknum + 1;
    }
  }



   return $output;
} // vlan_vars function

function singleclick_wds_vars($radio = 0) {

  $cmd = "\n<br>nvram set ";
  $result = db_query( "SELECT  r.rid, r.title, r.mac, r.ssid, "
                      ." c.rid1, c.rid2, c.ip1, c.ip2, c.hosts " 
                      ."FROM {wifi_radio} r, {wifi_link} c "
                      ."WHERE r.rid = " .$radio
                      ."  AND c.link_type = 'WDS' "
                      ."  AND c.state = 'Working' "
                      ."  AND (c.rid1 = " .$radio
                      ."   OR c.rid2 = " .$radio .")");
  
  $linknum = 1;
  $wds_str = "";

  while ( ($wds = db_fetch_array($result)) and ($linknum <= 10) ) {
    if ($linknum == 1) 
       $output = "\n<br># WDS -- radio: " .$radio ."-" .$wds["title"];

    if ($wds["rid"] == $wds["rid1"]) {
       $rid_link = $wds["rid2"];
       $ipaddr = $wds["ip1"];
    } else {
       $rid_link = $wds["rid1"];
       $ipaddr = $wds["ip2"];
    }
    $reslink = db_query("SELECT r.title, r.ssid, r.mac "
                               ."FROM {wifi_radio} r "
                               ."WHERE rid = " .$rid_link);
    $rlink  = db_fetch_array($reslink);
    $desc   = $rlink["ssid"];
    $hwaddr = $rlink["mac"];   

    $output .= "\n<br># " .$linknum .": " .$rid_link ."-" .$rlink["title"];
    $output .= $cmd ."wl_wds" .$linknum ."_desc=" .$desc;
    $output .= $cmd ."wl_wds" .$linknum ."_enable=1";
    $output .= $cmd ."wl_wds" .$linknum ."_ipaddr=" .$ipaddr;
    $output .= $cmd ."wl_wds" .$linknum ."_hwaddr=" .$hwaddr;
    $output .= $cmd ."wl_wds" .$linknum ."_netmask=" ._singleclick_netmask_by_hosts($wds["hosts"]);
//    $output .= $cmd ."wl_wds" .$linknum ."_netmask=255.255.255.252";
    $output .= $cmd ."wl_wds" .$linknum ."_if=wds0." .($linknum+1);
    $wds_str .= " " .$hwaddr;
    $linknum = $linknum + 1;
  }
  while ($linknum <= 10) {
    $output .= $cmd ."wl_wds" .$linknum ."_enable=0";
    $output .= $cmd ."wl_wds" .$linknum ."_desc=lliure";
    $output .= $cmd ."wl_wds" .$linknum ."_ipaddr=172.25.0.0";
    $output .= $cmd ."wl_wds" .$linknum ."_hwaddr=00:0F:66:00:00:00";
    $output .= $cmd ."wl_wds" .$linknum ."_netmask=255.255.255.252";
    $linknum = $linknum + 1;
  }
  $output .= $cmd ."wl0_wds=\"" .$wds_str ."\"";
  $output .= $cmd ."wl0_lazywds=0";
  $output .= $cmd ."wl_lazywds=0";



   return $output;
} // wds_vars function
?>
