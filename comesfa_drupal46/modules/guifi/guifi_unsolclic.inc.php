<?php

function guifi_unsolclic($id, $format = 'html') {
  $version = "v3.6-beta";
  global $rc_startup;
  global $ospf_zone;

  global $otype;

  $otype = $format;

  $rc_startup = "";
  $dev = array2object(guifi_get_device($id));
//  print_r($dev);
  $loc = node_load(array('nid'=>$dev->nid));
  $zone = node_load(array('nid'=>$loc->zone_id));

  if ($dev->variable['firmware'] == 'n/a') {
	_outln_comment(t("ERROR: I do need a firmware selected at the radio web interface: ").'<a href=/guifi/device/'.$id.'/edit>http://guifi.net/guifi/device/'.$id.'/edit');
        return;
  } else {
	_outln_comment(t("Generated for:"));
	_outln_comment($dev->variable['firmware']);
  }

 // Mainstream is for Linksys type firmwares, if others, branch
 switch ($dev->variable['firmware']) {
 case 'RouterOSv2.9': unsolclic_routeros($dev);
                      exit;
                      break;
 }

  if ($dev->radios[0]->mode == 'client') {
    $links = 0;
    if (!empty($dev->links)) foreach ($dev->links as $link) {
      if ($link['link_type'] == 'ap/client') {
        $links++;
        break; 
      }
    }
    if ($links == 0) {
	_outln_comment(t("ERROR: Radio is in client mode but has no AP selected, please add a link to the AP at: ").'<a href=/guifi/device/'.$id.'/edit>http://guifi.net/guifi/device/'.$id.'/edit');
        return;
    }
  }

  _outln_comment();
  _outln_comment('unsolclic version: '.$version);
  _outln_comment(t("open a telnet/ssh session on your device and run the script below."));
  _outln_comment(t("Note: Use Status/Wireless survey to verify that you have the"));
  _outln_comment(t("antenna plugged in the right connector. The right antena is probably"));
  _outln_comment(t("the one which is at the right, looking the WRT54G from the front"));
  _outln_comment(t("(where it have the leds). If needed, change the antenna connector"));
  _outln_comment(t("at Wireless->Advanced Settings."));
  _outln_comment(t('Security notes:'));
  _outln_comment(t('Once this script is executes, the router password for root/admin users is "guifi"'));
  _outln_comment(t('You must change this password if you want to keep it secret. If you like to still'));
  _outln_comment(t('be managed externally, you must install a trusted ssh key. Upon request, your setup'));
  _outln_comment(t('might be asked for being inspected to check the Wireless Commons compliance.'));
  _outln_comment(t('No firewall rules are allowed in the public network area.'));
  _outln_comment(t('By being in client mode, the router has the firewall enabled to distinguish between'));
  _outln_comment(t('private and public areas, and only SNMP, ssh and https 8080 ports are enabled'));
  _outln_comment(t('for external administration. Everything else is closed, therefore you might'));
  _outln_comment(t('have to open ports to share resources.'));
  _outln_comment();

  // network parameters
  guifi_unsolclic_network_vars($dev,$zone);
  guifi_unsolclic_vlan_vars($dev,$rc_startup);
  guifi_unsolclic_startup($dev,$version,$rc_startup);

  _outln_comment();
  _outln_comment(t('end of script and reboot'));
  _out('nvram commit');
  _out('reboot');
}

function _outln($string = '') {
  global $otype;

  print $string;
  if ($otype == 'html') print "\n<br>"; else print "\n";
}

function _outln_comment($string = '') {
  global $otype;

  print "# ".$string;
  if ($otype == 'html') print "\n<br>"; else print "\n";
}

function _outln_nvram($parameter, $value) {
  global $otype;

  print "nvram set ".$parameter.'="';
 
  if (strlen($value) <= 80) {
    print $value;
  } else { 
    $pos = 0;
    if ($otype == 'html') print "\n<br>"; else print "\n";
    do {
      print substr($value, $pos * 80, 80).'\\';
      $pos ++;
      if ($otype == 'html') print "\n<br>"; else print "\n";
    } while (strlen(substr($value,($pos-1) * 80)) > 80);
  }
  print('"');
  if ($otype == 'html') print "\n<br>"; else print "\n";
}

function _out_nvram($parameter,$value = null) {
  global $otype;
  print "nvram set ".$parameter.'="';
  if (!empty($value))
    print $value;
  if ($otype == 'html') print "\n<br>"; else print "\n";
}

function _out($value = '', $end = '') {
  global $otype;
  print "    ".$value.$end;
  if ($otype == 'html') print "\n<br>"; else print "\n";
}

function guifi_unsolclic_qos() {
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

function guifi_unsolclic_startup($dev, $version, $rc_startup) {
  global $ospf_zone;

  _outln_comment();
  _out_nvram('rc_startup','#!/bin/ash');
  _outln_comment();
  _outln_comment(' unsolclic: '.$version);
  _outln_comment(' radio:     '.$dev->id.'-'.$dev->nick);
  _outln_comment();
  if ($dev->variable[firmware] == 'DD-WRT' AND $dev->radios[0][mode] == 'ap') {
  _out('/bin/sleep 5');
// Write the config for bird, for compatibility with Alchemy and area support
  _out('/bin/kill -9 \`/bin/ps |/bin/grep bird|/usr/bin/cut -c1-5|/usr/bin/head -n 1\`;');
  _out('/bin/rm /tmp/bird/bird.conf');
  _out('/bin/echo -e \'router id '.$dev->ipv4,';');
  _out('protocol kernel { learn; persist; scan time 10; import all; export all; }');
  _out('protocol device { scan time 10; }');
  _out('protocol direct { interface \\"*\\";} ');
  _out('protocol ospf WRT54G_ospf {');
  _out('area '.$ospf_zone.' { tick 8;');
  _out('interface \"*\" { cost 1; hello 10; priority 1; retransmit 7; authentication none; };');
//  _out('interface \"br0\" { cost 1; hello 10; priority 1; retransmit 7; authentication none; };');
//  _out('interface \"vlan*\" { cost 1; authentication simple; password \"guifi\"; };');
  _out('};');
  _out('}');
  _out('\' >/tmp/bird/bird.conf');
  }

  // Bug del Talisman 1.0.5
  if ($dev->variable['firmware'] == 'Talisman') 
    _out("iptables -t nat -A POSTROUTING -j MASQUERADE");
  _outln_comment();
  print $rc_startup;
  if ($dev->variable['firmware'] == 'DD-WRT' AND $dev->radios[0]->mode == 'ap') {
  _out('/bin/sleep 3');
  _out('bird -c /tmp/bird/bird.conf');
  _out('/usr/sbin/wl shortslot_override 0');
  _out('ifconfig eth1 -promisc -allmulti');
  _out('ifconfig br0 -promisc -allmulti');
  _out('ifconfig eth0 promisc','"');
   } else {
  if ($dev->variable[firmware] == 'DD-guifi' AND $dev->radios[0][mode] == 'ap') {
  _out('/bin/sleep 10');
  _out('/usr/sbin/wl shortslot_override 0');
  _out('ifconfig eth1 -promisc -allmulti');
  _out('ifconfig br0 -promisc -allmulti');
  _out('ifconfig eth0 promisc','"');
  } else {
  _out('/bin/sleep 10');
  _out('/usr/sbin/wl shortslot_override 0','"');
   }
}
}
function guifi_get_dns($zone,$max = 3) {

  $dns = array();
  if (!empty($zone->dns_servers))
    $dns = explode(",",$zone->dns_servers);
  while (count($dns) < $max) {
    $zone = db_fetch_object(db_query("SELECT dns_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->dns_servers))
      $dns = array_merge($dns,explode(",",$zone->dns_servers));
    if ($zone->master == 0) {
      break;
    }
  } 
  while (count($dns) > $max)
    array_pop($dns);

  return implode(" ",$dns);
}

function guifi_get_ospf_zone($zone) {

  $ospf = array();
  if (!empty($zone->ospf_zone))
    return $zone->ospf_zone;
  do {
    $zone = db_fetch_object(db_query("SELECT dns_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->ospf_zone))
      return $zone->ospf_zone;
  } while ($zone->master > 0); 

  return '0';
}

function guifi_get_ntp($zone) {
  if (!empty($zone->ntp_servers))
    return $zone->ntp_servers;
  do {
    $zone = db_fetch_object(db_query("SELECT ntp_servers, master FROM {guifi_zone} WHERE id=%d",$zone->master));
    if (!empty($zone->ntp_servers))
      return $zone->ntp_servers;
  } while ($zone->master > 0); 

  return '';
  
}

function guifi_unsolclic_if($id, $itype) {
  return db_fetch_object(db_query("SELECT i.id, a.ipv4, a.netmask FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=0 WHERE device_id = %d AND interface_type = '%s' LIMIT 1",$id,$itype));
}

function guifi_get_alchemy_ifs($dev) {
  $ifs = array (
           'wLan/Lan' => 'br0',
           'wLan' => 'br0',
           'vlan' => 'br0:1',
           'vlwan' => 'br0',
           'vlwan' => 'br0',
           'wds/p2p' => 'wds0.',
           'Wan' => 'vlan1',
           'vlan2' => 'vlan2',
           'vlan3' => 'vlan3',
           'vlan4' => 'vlan4'
               );
  $ret = array();
  if (!empty($dev->radios))       foreach ($dev->radios as $radio_id => $radio) 
  if (!empty($radio[interfaces])) foreach ($radio[interfaces] as $interface_id => $interface) 
  if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $key => $link) {
    if ($link['link_type'] == 'wds')
     $wds_links[] = $link ;
    else {
     if (!isset($ret[$ifs[$interface['interface_type']]]))
       $ret[$ifs[$interface['interface_type']]] = true;
    }
  }
  if (count($wds_links))
  foreach ($wds_links as $key => $wds) 
    $ret['wds0.'.($key+2)] = true;

  if (!empty($dev->interfaces)) foreach ($dev->interfaces as $interface_id => $interface)
     if (!isset($ret[$ifs[$interface['interface_type']]]))
       $ret[$ifs[$interface['interface_type']]] = true;

  return $ret;
}

function guifi_unsolclic_gateway($dev) {
  _outln_comment();
  _outln_comment(t('Gateway routing'));
  _outln_nvram('wk_mode','gateway');
  _outln_nvram('dr_setting','0');
  _outln_nvram('route_default','1');
  _outln_nvram('dr_lan_rx','0');
  _outln_nvram('dr_lan_tx','0');
  _outln_nvram('dr_wan_rx','0');
  _outln_nvram('dr_wan_tx','0');
  _outln_nvram('dr_wan_tx','0');
  _outln_comment(t('Firewall enabled'));
  _outln_nvram('filter','on');
  _outln_nvram('rc_firewall','/usr/sbin/iptables -I INPUT -p udp --dport 161 -j ACCEPT; /usr/sbin/iptables -I INPUT -p tcp --dport 22 -j ACCEPT');
  return;
}

function guifi_unsolclic_ospf($dev,$zone) {
  global $ospf_zone;

  _outln_comment();
  _outln_comment(t('Firewall disabled'));
  _outln_nvram('filter','off');
  $ospf_zone = guifi_get_ospf_zone($zone);
  if ($dev->variable['firmware'] == 'Alchemy') {
    _outln_comment(t('Alchemy OSPF routing'));
    _outln_nvram('wk_mode','ospf');
    _outln_nvram('dr_setting','3');
    _outln_nvram('route_default','1');
    _outln_nvram('dr_lan_rx','1 2');
    _outln_nvram('dr_lan_tx','1 2');
    _outln_nvram('dr_wan_rx','1 2');
    _outln_nvram('dr_wan_tx','1 2');
    if ($ospf_zone != '0') {
      _outln_nvram('expert_mode','1');
      _out_nvram('ospfd_conf');
      _out('!');
      _out('password guifi');
      _out('enable password guifi');
      _out('!');

// TODO: List of routing interfaces, by now, all
      foreach (guifi_get_alchemy_ifs($dev) as $if => $exists) {
        _out('interface '.$if);
      }

      $wlan_lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
      _out('!');
      _out('router ospf');
      _out(' ospf router-id '.$wlan_lan->ipv4);
      _out(' redistribute kernel');
      _out(' redistribute connected');
      _out(' redistribute static');
      _out(' network 0.0.0.0/0 area '.$ospf_zone);
      _out(' default-information originate');
      _out('!');
      _out('line vty');
      _out('!','"');
    }
    return;
  }
  if ($dev->variable['firmware'] == 'Talisman') {
    _outln_comment(t('Talisman OSPF routing'));
    _outln_nvram('wk_mode','router');
    _outln_nvram('routing_lan','on');
    _outln_nvram('routing_wan','on');
    _outln_nvram('routing_ospf','on');
    _outln_nvram('routing_ospf_security','off');
//    _out_nvram('bird_conf');
//    _out('router id ' .$dev->ipv4);
//    _out('protocol kernel { learn; persist; scan time 10; import all; export all; }');
//    _out('protocol device { scan time 10; }');
//    _out('protocol direct { interface \\\"*\\\"; }');
//    _out('protocol ospf WRT54G_ospf {');
//    _out('area '.$ospf_zone.' {');
//    _out('interface \\\"*\\\" { cost 10; };');
//    _out('}; }','"');
    return;
  }
  if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi')) {
    _outln_comment(t('DD-WRT OSPF routing'));
    _outln_nvram('wk_mode','ospf');
    _outln_nvram('routing_lan','on');
    _outln_nvram('routing_wan','on');
    _outln_nvram('routing_ospf','on');
    _outln_nvram('dr_setting','3');
    _outln_nvram('dr_lan_rx','1 2');
    _outln_nvram('dr_lan_tx','1 2');
    _outln_nvram('dr_wan_rx','1 2');
    _outln_nvram('dr_wan_tx','1 2');

  }
}

function guifi_unsolclic_dhcp($dev) {
  $dhcp_statics = array();
  $max = explode(".",$dev->ipv4);

  function merge_static($link, &$dhcp_statics,&$max,&$curr) {
    if (empty($link['interface'][mac]))
      $link['interface'][mac] = 'FF:FF:FF:FF:FF:FF'; 
    $dhcp_statics[] = array($link['interface'][ipv4][ipv4],$link['interface'][mac],guifi_get_hostname($link['interface'][device_id]));
    $curr = explode(".",$link['interface'][ipv4][ipv4]);
    if ($curr[3] > $max[3]) {
      $max[3] = $curr[3];
    }
  }

  $main_ip = guifi_main_ip($dev->id);
  $item = _ipcalc_by_netbits($main_ip[ipv4],$main_ip[maskbits]);
  $max = explode(".",$main_ip[ipv4]);

  // cable links
  if (!empty($dev->interfaces)) foreach ($dev->interfaces as $interface) 
  if (!empty($interface[ipv4])) foreach ($interface[ipv4] as $ipv4) 
  if (!empty($ipv4[links]))     foreach ($ipv4[links] as $link) 
  {
    if ($link['interface'][ipv4][ipv4] != '') {
      $item2 = _ipcalc($link['interface'][ipv4][ipv4], $link['interface'][ipv4][netmask]); 
      if ($item[netid] == $item2[netid])
        merge_static($link,$dhcp_statics,$max,$cur);
    }
  }

  // ap/client links
  if (!empty($dev->radios))       foreach ($dev->radios as $radio) 
  if (!empty($radio[interfaces])) foreach ($radio[interfaces] as $interface) 
  if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4) 
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $link) 
  {
    if (($link['link_type'] == 'ap/client') and (!empty($link['interface'][ipv4][ipv4]))) 
    merge_static($link,$dhcp_statics,$max,$cur);
  }
  $statics = count($dhcp_statics) - 1;
  $totalstatics = count($dhcp_statics);

  _outln_comment();
  _outln_comment('DHCP');
  if ($statics == -1) {
    _outln_nvram('dhcp_start',($max[3] + 5));
    return;
  }

  _outln_comment();
  _outln_comment('DHCP');
  if ($dev->variable['firmware'] == 'Alchemy') {
    _out_nvram('dhcpd_statics');
    for ($i = 0; $i < $statics; $i++) {
      _out(implode(" ",$dhcp_statics[$i]));
    } 
    _out(implode(" ",$dhcp_statics[$statics]),'"');
  }

  if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi')){
        $staticText = "";
        foreach ($dhcp_statics as $static) {
        $staticText .= $static[1]."=".$static[2]."=".$static[0]." ";
        }
    _out('nvram set static_leases="'.$staticText,' "');
    _outln_nvram('static_leasenum',$totalstatics);
    }

  if ($dev->variable['firmware'] == 'Talisman') {
    _out_nvram('dhcp_statics');
    foreach ($dhcp_statics as $static) {
      _out($static[1]."-".$static[0]."-".$static[2]." ");
    }
    _out(null,'"');
  }
  _outln_nvram('dhcp_start',($max[3] + 5));
  return;
}     
  
function guifi_unsolclic_network_vars($dev,$zone) {

   _outln_comment($dev->nick);
   _outln_comment(t('Global network parameters'));
   _outln_nvram('router_name',$dev->nick);
   _outln_nvram('wan_hostname',$dev->nick);

   $wlan_lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
   if ($wlan_lan->ipv4 != '') {
     _outln_nvram('lan_ipaddr',$wlan_lan->ipv4);
     _outln_nvram('lan_gateway','0.0.0.0');
     _outln_nvram('lan_netmask',$wlan_lan->netmask);
   }

   $lan = guifi_unsolclic_if($dev->id,'Lan');
   if ($lan->ipv4 != '') {
     _outln_nvram('lan_ipaddr',$lan->ipv4);
     $item = _ipcalc($lan->ipv4, $lan->netmask);
     _outln_nvram('lan_gateway',$item['netstart']);  
     _outln_nvram('lan_netmask',$lan->netmask);
   }

   $wan = guifi_unsolclic_if($dev->id,'Wan');
   if ($wan) {
     if (empty($wan->ipv4)) 
       _outln_nvram('wan_proto','dhcp');
     else {
       _outln_nvram('wan_proto','static');
       _outln_nvram('wan_ipaddr',$wan->ipv4);
       $item = _ipcalc($wan->ipv4, $wan->netmask);
       _outln_nvram('wan_gateway',$item['netstart']);
       _outln_nvram('wan_netmask',$wan->netmask);
       if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi')){
	  _outln_nvram('fullswitch','1');
          _outln_nvram('wan_dns',guifi_get_dns($zone,3)); 
       }
     }
   } else {
     _outln_nvram('wan_proto','disabled');
   }
 
   _outln_nvram('lan_domain','guifi.net');
   _outln_nvram('wan_domain','guifi.net');
   _outln_nvram('http_passwd','guifi');
   _outln_nvram('time_zone',$zone->time_zone);
   _outln_nvram('sv_localdns',guifi_get_dns($zone,1));
   if ($dev->variable['firmware'] == 'Alchemy') 
     _outln_nvram('wan_dns',guifi_get_dns($zone,3));
   if ($dev->variable['firmware'] == 'Talisman') {
     foreach (explode(' ',guifi_get_dns($zone,3)) as $key => $dns)
       _outln_nvram('wan_dns'.$key,$dns);
   }
   _outln_nvram('wl_net_mode','b-only');
   _outln_nvram('wl0_net_mode','b-only');
   _outln_nvram('wl_afterburner','on');
   _outln_nvram('wl_frameburst','on');
   # _outln_nvram('txpwr','80');
   _outln_nvram('txant','0');
   _outln_nvram('wl0_antdiv','0');
   _outln_nvram('wl_antdiv','0');
   _outln_nvram('block_wan','0');
   
   if ($dev->variable['firmware'] == 'Talisman') {
     _outln_nvram('ident_pass','0');
     _outln_nvram('multicast_pass','0');
     _outln_nvram('wl_closed','0');
     _outln_nvram('block_loopback','0');
   }
   
   _outln_comment();
   _outln_comment(t('Management'));
   _outln_nvram('telnetd_enable','1');
   _outln_nvram('sshd_enable','1');
   _outln_nvram('sshd_passwd_auth','1');
   _outln_nvram('remote_management','1');
   _outln_nvram('remote_mgt_https','1');
   _outln_nvram('snmpd_enable','1');
   _outln_nvram('snmpd_sysname','guifi.net');
   _outln_nvram('snmpd_syscontact','guifi_at_guifi.net');
   _outln_nvram('boot_wait','on');
   _outln_comment(t('This is just a fake key. You must install a trusted key if you like to have you router managed externally'));
   _outln_nvram('sshd_authorized_keys','ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAIEAwWNX4942fQExw4Hph2M/sxOAWVE9PB1I4JnNyhoWuF9vid0XcU34kwWqBBlI+LjDErCQyaR4ysFgDX61V4kUuCKwBOMp+UGxhL648VTv5Qji/YwvIzt7nguUOZ5AGPISqsC0717hc0Aja1mvHkQqg9aXKznmszmyKZGhcm2+SU8= root@bandoler.guifi.net');
   // For DD-WRT
   _outln_nvram('http_enable','1');
   _outln_nvram('https_enable','1');


   _outln_comment();
   _outln_comment('NTP Network time protocol');
   $ntp = guifi_get_ntp($zone);
   if (empty($ntp)) {
     _outln_nvram('ntp_enable','0');
   } else {
     _outln_nvram('ntp_enable','1');
     _outln_nvram('ntp_server',$ntp);
   }
 
   _outln_comment();
   switch ($dev->radios[0][mode]) {
   case "ap": case "AP":
     _outln_comment(t('AP mode'));
     _outln_nvram('wl_mode','ap');
     _outln_nvram('wl0_mode','ap');
     _outln_nvram('wl_channel',$dev->radios[0][channel]);
     _outln_nvram('wl_ssid','guifi.net-'.guifi_to_7bits($dev->radios[0][ssid]));
     _outln_nvram('wl_macmode','disable');
     _outln_nvram('wl0_macmode','disable');
     _outln_nvram('wl_macmode1','disable');
     _outln_nvram('wl0_macmode1','disable');
     guifi_unsolclic_ospf($dev,$zone);
     guifi_unsolclic_dhcp($dev);
     guifi_unsolclic_wds_vars($dev);
     break;
   case 'client':
     _outln_comment(t('Client mode'));
     $ap_macs = array();
     foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface) 
     foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
     if (isset($ipv4[links])) foreach ($ipv4[links] as $key => $link) {
       if ($link['link_type'] == 'ap/client') {
       $ap_macs[] = $link['interface']['mac'];
       
       if (($dev->variable['firmware'] == 'Alchemy') or ($dev->variable['firmware'] == 'Talisman')) {
         _outln_nvram('wl_mode','wet');
         _outln_nvram('wl0_mode','wet');
         _outln_nvram('wl_ssid','guifi.net-'.guifi_get_ap_rssi($link['interface']['device_id'],$link['interface']['radiodev_counter']));
       }

       if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi')) {
         _outln_nvram('wl_mode','sta');
         _outln_nvram('wl0_mode','sta');
         _outln_nvram('wl_ssid','guifi.net-'.guifi_get_ap_rssi($link['interface']['device_id'],$link['interface']['radiodev_counter']));
       }
      }
     }
     if ($dev->variable['firmware'] == 'Alchemy') {
       $filter = implode(" ",$ap_macs);
       if ($filter == "" ) {
         _outln_comment(t('WARNING: AP MAC not set'));
         $filter = "FF:FF:FF:FF:FF:FF";
       }
       _outln_nvram('wl_macmode','allow');
       _outln_nvram('wl0_macmode','allow');
       _outln_nvram('wl_macmode1','other');
       _outln_nvram('wl0_macmode1','other');
       _outln_nvram('wl_maclist',$filter);
       _outln_nvram('wl0_maclist',$filter);
       _outln_nvram('wl_mac_list',$filter);
       _outln_nvram('wl0_mac_list',$filter);
     } else {
       _outln_nvram('wl_macmode','disabled');
       _outln_nvram('wl0_macmode','disabled');
       _outln_nvram('wl_macmode1','disabled');
       _outln_nvram('wl0_macmode1','disabled');
     }
       $lan = guifi_unsolclic_if($dev->id,'Lan');
       if ($lan) {
          guifi_unsolclic_ospf($dev,$zone);
          break;
         } else {
          guifi_unsolclic_gateway($dev);
          break;
         }
   } 
   _outln_comment();
}


function guifi_unsolclic_vlan_vars($dev,&$rc_startup) {
  global $otype;
 
  function vout($if, $ipv4, $link) {
    global $otype; 

    $output = '# '.$if.': '.guifi_get_hostname($link['interface'][device_id]);
    if ($otype == 'html') $output .= "\n<br>"; else $output .= "\n";
    $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
    if (!preg_match("/(Working|Testing|Building)/",$link[flag])) 
      $output .= '# '; 
    $output .= 'ifconfig '.$if.' '.$ipv4[ipv4].' netmask '.$ipv4[netmask].' broadcast '.$item['broadcast']; 
    if ($otype == 'html') $output .= "\n<br>"; else $output .= "\n";

    return $output;
  } 

  $vlans = false;
  $br0 = 0;
  $eth1 = 0;
  $rc = '';
  $bips = array();
  if (!empty($dev->interfaces)) foreach ($dev->interfaces as $interface_id => $interface) 
  if (!empty($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
  if (!empty($ipv4[links]))     foreach ($ipv4[links] as $link_id => $link) 
  {

    // if interface is already created, skip
    if (!in_array($interface['ipv4'],$bips)) {
      $bips[] = $interface['ipv4'];

      switch ($interface['interface_type']) {
        case 'vlan':
        case 'vwlan':
          $br0++;
          $rc .= vout('br0:'.$br0,$ipv4,$link); 
          break; 
        case 'vwan':
          $eth1++;
          $rc .= vout('eth1:'.$eth1,$ipv4,$link); 
          break; 
        case 'vlan2':
        case 'vlan3':
        case 'vlan4':
          $rc .= vout($interface['interface_type'],$ipv4,$link); 
          $vlans = true;
          break;
      }
    }
  }
  if ($rc != '') {
    $rc_startup = '# VLANs -- radio: '.$dev->id.'-'.$dev->nick;
    if ($otype == 'html') $rc_startup .= "\n<br>"; else $rc_startup .= "\n";
    $rc_startup .= $rc;
  }
  if ($vlans) {
    _outln_comment();
    _outln_comment('VLANs -- radio: '.$dev->id.'-'.$dev->nick);
    switch ($dev->variable['model_id']) {
    case "1": //* WRT54Gv1-4 *//
    case "15"://* WHR-HP-G54, WHR-G54S (BUFFALO) *//
    case "17"://* WRT54GSv1-2 *//
     if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi')) {
    _outln_nvram('vlan2hwname','et0');
    _outln_nvram('vlan3hwname','et0');
    _outln_nvram('vlan4hwname','et0');
    _outln_nvram('vlan0ports','0 1 5*');
    _outln_nvram('vlan2ports','2 5');
    _outln_nvram('vlan3ports','3 5');
    _outln_nvram('vlan4ports','4 5');
	 } else {
    _outln_nvram('vlans','1');
    _outln_nvram('port2vlans','2');
    _outln_nvram('port3vlans','3');
    _outln_nvram('port4vlans','4');
    _outln_nvram('port5vlans','0 1 2 3 4 16');
    } 
    break;
// switch has turned ports for these models:
    case "16": //* WRT54GL *//
    case "18": //* WRT54GSv4 *//
    _outln_nvram('vlan2hwname','et0');
    _outln_nvram('vlan3hwname','et0');
    _outln_nvram('vlan4hwname','et0');
    _outln_nvram('vlan0ports','4 3 5*');
    _outln_nvram('vlan1ports','4 5');
    _outln_nvram('vlan2ports','2 5');
    _outln_nvram('vlan3ports','1 5');
    _outln_nvram('vlan4ports','0 5');

   }
  }
} // vlan_vars function

function guifi_unsolclic_wds_vars($dev) {
  
  global $rc_startup;

  $wds_links = array();
  $wds_str = '';
  if (!empty($dev->radios))       foreach ($dev->radios as $radio_id => $radio) 
  if (!empty($radio[interfaces])) foreach ($radio[interfaces] as $interface_id => $interface) 
  if ($interface['interface_type'] == 'wds/p2p') if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4_id => $ipv4) 
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $key => $link) {
    if ($link['link_type'] == 'wds')
     $wds_links[] = $link ;
     $iplocal[] = $ipv4 ;
     $iflocal[] = $interface ;
  }
  if (count($wds_links) == 0)
    return;

  _outln_comment('');
  _outln_comment(t('WDS Links for').' '.$dev->nick);
	  if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi'))
	    $ifcount = 2; else $ifcount = 1;
  foreach ($wds_links as $key => $wds) {
    $hostname = guifi_get_hostname($wds['device_id']);
    _outln_comment($wds['device_id'].'-'.$hostname);
    _outln_nvram('wl_wds'.($key+1).'_desc',$hostname);
    if (preg_match("/(Working|Testing|Building)/",$wds['flag'])) {
      $ifcount++;
      _outln_nvram('wl_wds'.($key+1).'_enable','1');
	  if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi'))
      _outln_nvram('wl_wds'.($key+1).'_if','wds0.4915'.$ifcount);
	  else
      _outln_nvram('wl_wds'.($key+1).'_if','wds0.'.$ifcount);
      $wds_str .= ' '.$wds['interface']['mac'];
      // Bug del Talisman 1.0.5
      if ($dev->variable['firmware'] == 'Talisman') 
        $rc_startup .= "ifconfig wds0.".$ifcount." up\n<br>";
    } else {
      _outln_nvram('wl_wds'.($key+1).'_enable','0');
    }
    _outln_nvram('wl_wds'.($key+1).'_ipaddr',$iplocal[$key][ipv4]);
    _outln_nvram('wl_wds'.($key+1).'_hwaddr',$wds['interface'][mac]);
    _outln_nvram('wl_wds'.($key+1).'_netmask',$iplocal[$key][netmask]);
  }
  if (count($wds_links) >= 11)
    return;

  _outln_comment();
  _outln_comment(t('Free WDS slots'));
  for ($key = count($wds_links) + 1; $key <= 10; $key++) {
    _outln_nvram('wl_wds'.($key).'_desc',t('free'));
    _outln_nvram('wl_wds'.($key).'_enable','0');
    _outln_nvram('wl_wds'.($key).'_ipaddr','172.0.0.0');
    _outln_nvram('wl_wds'.($key).'_hwaddr','00:13:00:00:00:00');
    _outln_nvram('wl_wds'.($key).'_netmask','255.255.255.252');
  }
  _out_nvram('wl0_wds',$wds_str.'"');
  _outln_nvram('wl0_lazywds','0');
  _outln_nvram('wl_lazywds','0');
} // wds_vars function

function unsolclic_routeros($dev) {

  $ospf_interfaces = array();
  $defined_ips = array();

  function bgp_peer($id, $ipv4) {
    $peername=guifi_get_hostname($id);
    _outln('/ routing bgp peer');
    _outln(sprintf(':foreach i in [find name=%s] do={/routing bgp peer remove $i;}',$peername));
    _outln(sprintf('add name="%s" instance=default remote-address=%s remote-as=%s \ ',
           $peername,
           $ipv4,
           $id));
    _outln('multihop=no route-reflect=no ttl=1 in-filter=ospf-in out-filter=ospf-out disabled=no');
  }

  $node = node_load(array('nid'=>$dev->nid));
  $zone = node_load(array('nid'=>$node->zone_id));
  _outln(sprintf(':log info "Unsolclic for %d-%s going to be executed."',$dev->id,$dev->nick));
  _outln_comment();
  _outln_comment(t('Configuration for RouterOS 2.19'));
  _outln_comment(t('Device').': '.$dev->id.'-'.$dev->nick);
  _outln_comment();
  _outln_comment(t('WARNING: Beta version, only AP-AP/Bridge modes supported'));
  _outln_comment();
  _outln_comment(t('Methods to upload/execute this script:'));
  _outln_comment(t('1.-As a script. Upload this output as a script either with:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('a.Winbox (with Linux, wine required)'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('b.Terminal (telnet, ssh...)'));
  _outln_comment('&nbsp;&nbsp;&nbsp;'.t('Then execute the script with:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.t('>&nbsp;/system script run script_name'));
  _outln_comment(t('2.-Imported file:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('Save this output to a file, then upload it to the router'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('using ftp using a name like "script_name.rsc".'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('(note that extension ".rsc" is required)'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('Run the import file using the command:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.t('>&nbsp;/import script_name'));
  _outln_comment(t('3.-Telnet cut&paste:'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('Open a terminal session, and cut&paste this output'));
  _outln_comment('&nbsp;&nbsp;&nbsp;&nbsp;'.t('directly on the terminal input.'));
  _outln_comment();
  _outln_comment(t('Notes:'));
  _outln_comment(t('-routing-test package is required, be sure you have it enabled at system packages'));
  _outln_comment(t('-By default, OSPF is *DEACTIVATED*, and BGP activated, peers should be enabled'));
  _outln_comment(t('&nbsp;&nbsp;manually. To enable ospf, enable the backbone network at'));
  _outln_comment(t('&nbsp;&nbsp;/routing ospf network'));
  _outln_comment(t('-wlans should be enabled manually, be sure to set the correct antenna (a or b)'));
  _outln_comment(t('&nbsp;&nbsp;according in how did you connect the cable to the miniPCI. Keep the'));
  _outln_comment(t('&nbsp;&nbsp;power at the minimum possible and check the channel.'));
  _outln_comment(t('-The script doesn\'t reset the router, you might have to do it manually'));
  _outln_comment(t('-You must have write access to the router'));
  _outln_comment(t('-MAC access (winbox, MAC telnet...) method is recommended'));
  _outln_comment(t('&nbsp;&nbsp;(the script reconfigures some IP addresses, so communication can be lost)'));
  _outln_comment(t('-No changes are done in user passwords on the device'));
  _outln_comment(t('-A Read Only guest account with no password will be created to allow guest access'));
  _outln_comment(t('&nbsp;&nbsp;to the router with no danger of damage but able to see the config.'));
  _outln_comment(t('-Be sure that all packages are activated.'));
  _outln_comment(t('-Don\'t run the script from telnet and being connected through an IP connection at'));
  _outln_comment(t('&nbsp;&nbsp;the wLan/Lan interface: This interface will be destroyed during the script.'));
  _outln_comment();

  _outln('/ system identity set name='.$dev->nick);


  // DNS
  _outln_comment();
  _outln_comment('DNS (client & server cache) zone: '.$node->zone_id);
  list($primary_dns,$secondary_dns) = explode(' ',guifi_get_dns($zone,2));
  if ($secondary_dns != null)
    _outln(sprintf('/ip dns set primary-dns=%s secondary-dns=%s allow-remote-requests=yes',$primary_dns,$secondary_dns));
  else if ($primary_dns != null)
    _outln(sprintf('/ip dns set primary-dns=%s allow-remote-requests=yes',$primary_dns));

  _outln(':delay 1');

  // NTP
  _outln_comment();
  _outln_comment('NTP (client & server cache) zone: '.$node->zone_id);
  list($primary_ntp,$secondary_ntp) = explode(' ',guifi_get_ntp($zone));
  if ($secondary_ntp != null)
    _outln(sprintf('/system ntp client set enabled=yes primary-ntp=%s secondary-ntp=%s',$primary_ntp,$secondary_ntp));
  else if ($primary_ntp != null)
    _outln(sprintf('/system ntp client set enabled=yes primary-ntp=%s',$primary_ntp));
  _outln('/system ntp server set manycast=no enabled=yes');

  _outln(':delay 1');

  // Define wLan/Lan bridge (main interface)
  _outln_comment(t('Remove current wLan/Lan bridge if exists'));
  _outln(':foreach i in [/interface bridge find name=wLan/Lan] \ ');
  _outln('do={:foreach i in [/interface bridge port find bridge=wLan/Lan] \ ');
  _outln('do={/interface bridge port remove $i; \ ');
  _outln(':foreach i in [/ip address find interface=wLan/Lan] \ ');
  _outln('do={/ip address remove $i;};};');
  _outln('/interface bridge remove $i;}');
  _outln_comment(t('Construct main bridge on wlan1 & ether1'));
  _outln('/ interface bridge');
  _outln('add name="wLan/Lan"');
  _outln('/ interface bridge port');
  _outln('add interface=ether1 bridge=wLan/Lan');
  _outln('add interface=wlan1 bridge=wLan/Lan');

  _outln(':delay 1');


  // Going to setup wireless interfaces
  if (isset($dev->radios)) foreach ($dev->radios as $radio_id=>$radio) {

    if ($radio[mode]=='client') 
      $mode = 'station';
    else {
      $mode = 'ap-bridge';
      $ospf_interfaces[] = 'wlan'.($radio_id+1);
    }
    if ($radio[channel] < 5000) 
      $band = '2.4ghz-b';
    else
      $band = '5ghz';

    _outln_comment();
    _outln_comment('Radio#: '.$radio_id.' '.$radio[ssid]);
    _outln(sprintf('/interface wireless set wlan%d name="wlan%d" \ ',$radio_id+1,$radio_id+1));
    _outln(sprintf('    radio-name="%s" mode=%s ssid="guifi.net-%s" \ ',$radio[ssid],$mode,$radio[ssid]));
    _outln(sprintf('    band="%s" \ ',$band));
    _outln(sprintf('    frequency-mode=manual-txpower country=spain antenna-gain=0 \ ',$band));
    if (($radio[channel] != 0) and ($radio[channel] != 5000))
      _outln(sprintf('    frequency=%d \ ',$radio[channel]));
    if ($band == '5GHz')
      _outln('    dfs-mode=radar-detect \ ');
    else
      _outln('    dfs-mode=none \ ');

    _outln('    antenna-mode=ant-b wds-mode=static wds-default-bridge=none wds-default-cost=100 \ ');
    _outln('    wds-cost-range=50-150 wds-ignore-ssid=yes hide-ssid=no');

    if (isset($radio[interfaces])) foreach ($radio[interfaces] as $interface_id=>$interface) {
       _outln(':delay 1');
       _outln_comment($interface[interface_type]);
       if ($interface[interface_type] == 'wds/p2p') {
         _outln_comment(t('Remove all existing wds interfaces'));
         _outln(sprintf(':foreach i in [/interface wireless wds find master-interface=wlan%s] \ ',$radio_id+1));
         _outln('do={:foreach n in [/interface wireless wds get $i name] \ ');
         _outln('do={:foreach inum in [/ip address find interface=$n] \ ');
         _outln('do={/ip address remove $inum;};}; \ ');
         _outln('/interface wireless wds remove $i;}');
         if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id=>$ipv4) 
         if (isset($ipv4[links])) foreach ($ipv4[links] as $link_id=>$link) {
           if (preg_match("/(Working|Testing|Building)/",$link['flag'])) 
             $disabled='no';
           else
             $disabled='yes';
           $wdsname = 'wds_'.guifi_get_hostname($link['device_id']);
           if ($link['interface']['mac'] == null)
             $link['interface'][mac]= 'FF:FF:FF:FF:FF:FF';
           _outln('/ interface wireless wds');
           _outln(sprintf('add name="%s" master-interface=wlan%d wds-address=%s disabled=%s',$wdsname,$radio_id+1,$link['interface'][mac],$disabled));
           $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
           _outln(sprintf('/ ip address add address=%s/%d network=%s broadcast=%s interface=%s disabled=%s comment="%s"',$ipv4[ipv4],$item[maskbits],$item[netid],$item[broadcast],$wdsname,$disabled,$wdsname));
           bgp_peer($link['device_id'],$link['interface']['ipv4']['ipv4']);
         } // each wds link (ipv4)
       } else { // wds
         // wLan, wLan/Lan or client
         if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id=>$ipv4) {
           if ($interface[interface_type] == 'wLan/Lan')
             $iname = $interface[interface_type];
           else
             $iname = 'wlan'.($radio_id+1);
           $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
           _outln(sprintf('/ ip address add address=%s/%d network=%s broadcast=%s interface=%s disabled=no',$ipv4[ipv4],$item[maskbits],$item[netid],$item[broadcast],$iname));
           $defined_ips[$ipv4[ipv4]] = $item;
           $ospf_routerid=$ipv4[ipv4];
         }


         _outln(':delay 1');

         // Not link only (AP), setting DHCP

         $dhcp = array();
         $dhcp[] = '/ip dhcp-server lease';
         $dhcp[] = ':foreach i in [find comment=""] do={remove $i;}';
         $dhcp[] = ':delay 1';
         $maxip = _dec_addr(guifi_ip_op($item[netstart]));
         if (isset($ipv4[links])) foreach ($ipv4[links] as $link_id=>$link) {
           if (isset($link['interface'][ipv4][ipv4]))
           if (_dec_addr($link['interface'][ipv4][ipv4]) >= $maxip)
             $maxip = _dec_addr($link['interface'][ipv4][ipv4]) + 1;
           if ($link['interface'][mac] == null)
             $rmac = 'ff:ff:ff:ff:ff:ff';
           else $rmac = $link['interface'][mac];
           $dhcp[] = sprintf('add address=%s mac-address=%s client-id=%s server=dhcp-%s',$link['interface'][ipv4][ipv4],$rmac,guifi_get_hostname($link[device_id]),$iname);
         }
         if (($maxip + 5) > (_dec_addr($item[netend]) - 5)) {
           $maxip = _dec_addr($item['netend']);
           $dhcp_disabled='yes';
         } else {
           $maxip = $maxip + 5;
           $dhcp_disabled='no';
         }
         _outln_comment();
         _outln_comment('DHCP');
         foreach ($dhcp as $outln) 
          _outln($outln);
         _outln(sprintf(':foreach i in [/ip dhcp-server network find address=%s/%d] do={/ip dhcp-server network remove $i;}',$item[netid],$item[maskbits]));
         //_outln(sprintf('/ip dhcp-server network add address=%s/%d gateway=%s domain=guifi.net dns-server=%s ntp-server=%s comment=dhcp-%s',$item[netid],$item[maskbits],$item[netstart],implode(',',array_merge(array($ipv4[ipv4]),explode(' ',guifi_get_dns($zone)))),guifi_get_ntp($zone),$iname));
         _outln(sprintf(':foreach i in [/ip pool find name=dhcp-%s] do={/ip pool remove $i;}',$iname));
         _outln(sprintf('/ip pool add name=dhcp-%s ranges=%s-%s',$iname,_dec_to_ip($maxip),$item[netend]));
         _outln(sprintf('/ip dhcp-server network add address=%s/%d gateway=%s domain=guifi.net comment=dhcp-%s',$item[netid],$item[maskbits],$item[netstart],$iname));
         _outln(sprintf(':foreach i in [/ip dhcp-server find name=dhcp-%s] do={/ip dhcp-server remove $i;}',$iname));
         _outln(sprintf('/ip dhcp-server add name=dhcp-%s interface=%s address-pool=dhcp-%s disabled=%s',$iname,$iname,$iname,$dhcp_disabled));
         
       } // wLan, wLan/Lan or client
       _outln_comment();
    } // foreach radio->interface
  
    _outln(':delay 1');

  } // foreach radio

  // Now, defining other interfaces (if they aren't yet)
  _outln_comment();
  _outln_comment(t('Other cable connections'));
  if (isset($dev->interfaces)) foreach ($dev->interfaces as $interface_id=>$interface) {
    switch ($interface[interface_type]) {
    case 'vlan':  $iname = 'wLan/Lan'; break;
    case 'vlan2': $iname = 'ether2'; break;
    case 'vlan3': $iname = 'ether3'; break;
    case 'vlan4': $iname = 'wLan/Lan'; break;
    case 'Wan':   $iname = 'wLan/Lan'; break;
    default:
      $iname = $interface[interface_type];
      break;
    }
    $ospf_intrefaces[] = $iname;
    if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id=>$ipv4) {
      if (!isset($defined_ips[$ipv4[ipv4]])) {
        $disabled='yes';
        if (isset($ipv4[links])) {
          unset($comments);
          foreach ($ipv4[links] as $link_id=>$link) {
            if (($disabled='yes') and (preg_match("/(Working|Testing|Building)/",$link['flag']))) 
              $disabled='no';
            $comments[] = guifi_get_hostname($link[device_id]);
            bgp_peer($link['device_id'],$link['interface']['ipv4']['ipv4']);
          }
        } else
          $disabled='no';
        $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
        _outln(sprintf(':foreach i in [/ip address find address=%s/%d] do={/ip address remove $i;}',$ipv4[ipv4],$item[maskbits]));
        _outln(':delay 1');
        _outln(sprintf('/ ip address add address=%s/%d network=%s broadcast=%s interface=%s disabled=%s comment="%s"',$ipv4[ipv4],$item[maskbits],$item[netid],$item[broadcast],$iname,$disabled,implode(',',$comments)));
        $defined_ips[$ipv4[ipv4]] = $item;
      }
    }
  }

  // BGP
  _outln_comment();
  _outln_comment(t('BGP Routing'));
  _outln_comment(t('BGP & OSPF Filters'));
  _outln(':foreach i in [/routing filter find chain=ospf-in] do={/routing filter remove $i;}');
  _outln(':foreach i in [/routing filter find chain=ospf-out] do={/routing filter remove $i;}');
  _outln("/ routing filter");
  _outln('add chain=ospf-out prefix=10.0.0.0/8 prefix-length=8-27 invert-match=no action=accept comment="" disabled=no');
  _outln('add chain=ospf-out invert-match=no action=discard comment="" disabled=no');
  _outln('add chain=ospf-in prefix=10.0.0.0/8 prefix-length=8-27 invert-match=no action=accept comment="" disabled=no');
  _outln('add chain=ospf-in invert-match=no action=reject comment="" disabled=no');
  _outln_comment();
  _outln_comment(t('BGP instance'));
  _outln("/ routing bgp instance");
  _outln(sprintf('set default name="default" as=%d router-id=%s redistribute-static=yes \ ',$dev->id,$ospf_routerid));
  _outln('redistribute-connected=yes redistribute-rip=yes redistribute-ospf=yes \ ');
  _outln('redistribute-other-bgp=yes out-filter=ospf-out \ ');
  _outln('client-to-client-reflection=yes comment="" disabled=no');



  // OSPF
  if (count($ospf_interfaces)) {
       _outln_comment();
       _outln_comment(t('OSPF Routing'));
       _outln(sprintf('/routing ospf set router-id=%s',$ospf_routerid));
       foreach ($ospf_interfaces as $key=>$interface) {
         _outln(sprintf(':foreach i in [/routing ospf interface find interface=%s] do={/routing ospf interface remove $i;}',$interface));
         _outln(sprintf('/routing ospf interface add interface=%s',$interface));
       }
       _outln(':foreach i in [/routing ospf network find area=backbone] do={/routing ospf network remove $i;}');
       _outln('/routing ospf network add network=0.0.0.0/0 area=backbone disabled=yes');
  }

  // Graphing
  _outln_comment();
  _outln_comment(t('Graphing'));
  foreach ($ospf_interfaces as $key=>$interface) {
#     _outln(sprintf(':foreach i in [/tool graphing interface find interface=%s] do={/tool graphing interface remove $i;}',$interface));
     _outln(sprintf('/tool graphing interface add interface=%s',$interface));
  }

  // Bandwidth-server
  _outln_comment();
  _outln_comment(t('Bandwidth-server'));
  _outln('/ tool bandwidth-server set enabled=yes authenticate=no allocate-udp-ports-from=2000');

  // SNMP 
  _outln_comment();
  _outln_comment('SNMP');
  _outln(sprintf('/snmp set contact="guifi@guifi.net" enabled=yes location="%s"',$node->nick));

  // User guest
  _outln_comment();
  _outln_comment('Guest user');
  _outln('/user');
  _outln(':foreach i in [find group=read] do={/user remove $i;}');
  _outln('add name="guest" group=read address=0.0.0.0/0 comment="" disabled=no');


  // End of Unsolclic
  _outln_comment();
  _outln(sprintf(':log info "Unsolclic for %d-%s executed."',$dev->id,$dev->nick));
  _outln('/');
}
?>
