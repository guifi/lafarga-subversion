<?php

#http://wiki.openwrt.org/OpenWrtDocs/KamikazeConfiguration
#http://downloads.openwrt.org/kamikaze/7.09/docs/openwrt.html
function unsolclic_kamikaze($dev) {
  $version = "0.3";
  $loc = node_load(array('nid'=>$dev->nid));
  $zone = node_load(array('nid'=>$loc->zone_id));

# http://wiki.openwrt.org/MiniHowtos/QoSHowto
# http://www.mikrotik.com/download/l7-protos.rsc
function guifi_unsolclic_qos() {
  $cmd = "\n<br />nvram set ";

  $output = "\n<br />#"
           ."\n<br /># QoS" 
           .$cmd ."wshaper_enable=1"
           .$cmd ."wshaper_dev=LAN"
           .$cmd ."action_service=filters"
           .$cmd ."svqos_svcs=\"h323 l7 0:0 10 |"
                 ."\n<br /> "
                 ." aim l7 0:0 20 |"
                 ." jabber l7 0:0 20 |"
                 ." http l7 0:0 20 |"
                 ." HTTPS tcp 443:443 20 |"
                 ." irc l7 0:0 20 |"
                 ." Telnet tcp 23:23 20 |"
                 ." Ping icmp 0:0 20 |"
                 ." SSH tcp 22:22 20 |"
                 ." msn l7 0:0 20 |"
                 ."\n<br />"
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
                 ."\n<br />\"";
// Mentre duri el bug al QoS, res;
  return "";
  return $output;
}

function guifi_unsolclic_startup($dev, $version, $rc_startup) {
  global $ospf_zone;

  _outln_comment();
  //_out_nvram('rc_startup','#!/bin/ash');
  //_outln_comment();
  
  
  //_outln_comment(' unsolclic: '.$version);
  _outln_comment(' radio:     '.$dev->id.'-'.$dev->nick);
  _outln_comment();
/* 
  _out('/bin/echo -e \'router id '.$dev->ipv4,';');
  _out('protocol kernel { learn; persist; scan time 10; import all; export all; }');
  _out('protocol device { scan time 10; }');
  _out('protocol direct { interface \\"*\\";} ');
  _out('protocol ospf WRT54G_ospf {');
  _out('area '.$ospf_zone.' { tick 8;');
  
  _out('interface \"*\" { cost 1; hello 10; priority 1; retransmit 7; authentication none; };');*/
//  _out('interface \"br0\" { cost 1; hello 10; priority 1; retransmit 7; authentication none; };');
//  _out('interface \"vlan*\" { cost 1; authentication simple; password \"guifi\"; };');



  //_outln_comment();
  //print $rc_startup;
/*  if ($dev->variable['firmware'] == 'DD-WRT') {*/
//  _out('/bin/sleep 3');
//  _out('bird -c /tmp/bird/bird.conf');
//  _out('/usr/sbin/wl shortslot_override 0');

$file_startup='
#!/bin/sh /etc/rc.common
START=90
touch /etc/quagga/zebra.conf &
mkdir -p -m 777 /var/run/quagga &
ifconfig eth1 -promisc -allmulti &
ifconfig br0 -promisc -allmulti &
ifconfig eth0 promisc &
';
_out_file($file_startup,'/etc/init.d/custom-user-startup');
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
  /*_outln_comment();
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
  _outln_nvram('rc_firewall','/usr/sbin/iptables -I INPUT -p udp --dport 161 -j ACCEPT; /usr/sbin/iptables -I INPUT -p tcp --dport 22 -j ACCEPT');*/
  return;
}

function guifi_unsolclic_ospf($dev,$zone) {
  global $ospf_zone;

  _outln_comment();
  _outln_comment(t('Firewall disabled'));
  //_outln_nvram('filter','off');
  _out('rm -f /etc/rc.d/S??firewall');


  $ospf_zone = guifi_get_ospf_zone($zone);
  
    //switch ($dev->variable['model_id']) {
    //  case "30": //* F o n era{2100,2200,+} *//

        _outln_comment();
        _outln_comment(t('Quagga OSPF routing'));
  /*if ($dev->variable['firmware'] == 'Talisman') {
    _outln_nvram('route_default','1');    

    _outln_nvram('wk_mode','router');
    _outln_nvram('routing_ospf_security','off');

    _outln_nvram('wk_mode','ospf');
    _outln_nvram('routing_lan','on');
    _outln_nvram('routing_wan','on');
    _outln_nvram('routing_ospf','on');

    _outln_nvram('dr_setting','3');
    _outln_nvram('dr_lan_rx','1 2');
    _outln_nvram('dr_lan_tx','1 2');
    _outln_nvram('dr_wan_rx','1 2');
    _outln_nvram('dr_wan_tx','1 2');

  }*/
        //_outln_nvram('expert_mode','1');
        //_out_nvram('ospfd_conf');
        
        _out('/bin/mkdir -p /etc/quagga/');

$wlan_lan = guifi_unsolclic_if($dev->id,'wLan/Lan');

// TODO: List of routing interfaces, by now, all
$ospf_ifaces='';
foreach (guifi_get_alchemy_ifs($dev) as $if => $exists) {
 $ospf_ifaces.='!
interface '.$if.'
';
}

$file_ospfd='!
password guifi
enable password guifi
'.$ospf_ifaces.'!
router ospf
 ospf router-id '.$wlan_lan->ipv4.'
 redistribute kernel
 redistribute connected
 redistribute static
 network 0.0.0.0/0 area '.$ospf_zone.'
 default-information originate
!
line vty
! ';
	_out_file($file_ospfd,'/etc/quagga/ospfd.conf');

  //}

}

function guifi_unsolclic_olsr($dev,$zone) {
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
  //_outln_nvram('dhcp_start',($max[3] + 5));



$config_dhcp='
config dhcp
        option interface        lan
        option start    '.($max[3] + 5).'
        option limit    32
        option leasetime        12h

config dhcp
        option interface        wan
        option ignore   1
';
_out_file($config_dhcp,'/etc/config/dhcp');



  if ($statics == -1) {
    return;
  }

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


  return;
}     
  
function guifi_unsolclic_network_vars($dev,$zone) {

  _outln_comment($dev->nick);
  _outln_comment(t('Global network parameters'));

$file_network='
config interface loopback
        option ifname   lo
        option proto    static
        option ipaddr   127.0.0.1
        option netmask  255.0.0.0
';

   //_outln_nvram('router_name',$dev->nick);
   //_outln_nvram('wan_hostname',$dev->nick);
$_hostname='
config system
        option hostname '.$dev->nick.'
';
_out_file($_hostname,'/etc/config/system');


  $wlan_lan = guifi_unsolclic_if($dev->id,'wLan/Lan');
  //_out("WLAN");_out($wlan_lan);

   if ($wlan_lan->ipv4 != '') {
     //_outln_nvram('lan_ipaddr',$wlan_lan->ipv4);
     //_outln_nvram('lan_gateway','0.0.0.0');
     //_outln_nvram('lan_netmask',$wlan_lan->netmask);


$guifi_dns=array(guifi_get_dns($zone,1),'10.139.50.1');
foreach ($guifi_dns as $dns)
  $file_dns=$dns.' ';

$file_network.='
config interface lan
        option ifname   eth0
        option type     bridge
        option proto    static
        option ipaddr   '.$wlan_lan->ipv4.'
        option netmask  '.$wlan_lan->netmask.'
        option dns      "'.$file_dns.'"
'; 
//option gateway 0.0.0.0 

   }

   $lan = guifi_unsolclic_if($dev->id,'Lan');
   _out("LAN");_out($dev->id);_out($wlan_lan);print_r($dev->id);print_r($lan);
   if ($lan->ipv4 != '') {
     //_outln_nvram('lan_ipaddr',$lan->ipv4);
     $item = _ipcalc($lan->ipv4, $lan->netmask);
     //_outln_nvram('lan_gateway',$item['netstart']);  
     //_outln_nvram('lan_netmask',$lan->netmask);

$file_network.='
config interface lan
        option ifname   eth0
        option type     bridge
        option proto    static
        option ipaddr   '.$lan->ipv4.'
        option netmask  '.$lan->netmask.'
        option gateway  '.$item['netstart'].'
';
   }

   $wan = guifi_unsolclic_if($dev->id,'Wan');
   _out("WAN".$wan);
   if ($wan) {
$file_network.='
config interface wan
        option ifname   eth0
        #option type     bridge
';

     if (empty($wan->ipv4)) { 
       #_outln_nvram('wan_proto','dhcp');
$file_network.='
        option proto    dhcp';
     } else {
       #_outln_nvram('wan_proto','static');
       #_outln_nvram('wan_ipaddr',$wan->ipv4);
       #_outln_nvram('wan_netmask',$wan->netmask);
       #if (($dev->variable['firmware'] == 'DD-WRT') or ($dev->variable['firmware'] == 'DD-guifi')){
       #  _outln_nvram('fullswitch','1');
       #  _outln_nvram('wan_dns',guifi_get_dns($zone,3)); 
       #}

$file_network.='
        option proto    static
        option ipaddr   '.$wan->ipv4.'
        option netmask  '.$wan->netmask.'
';

      $wan_dns=guifi_get_dns($zone,3);
      if (!empty($wan_dns)) {
$file_network.='        option dns      "'.$wan_dns.'"
';
      }

     }
   } else {
     #_outln_nvram('wan_proto','disabled');
   }

//_out_file($file_network,'/etc/config/network');

   //_outln_nvram('lan_domain','guifi.net');
   //_outln_nvram('wan_domain','guifi.net');
   //_outln_nvram('http_passwd','guifi');

// password: 'guifi'
_out('PASSWD=`grep -v ^root: /etc/passwd`');
$file_pass='root:WLL3bqv6fH7qM:0:0:root:/tmp:/bin/ash
';
_out_file($file_pass.'$PASSWD','/etc/passwd');




   //_outln_nvram('time_zone',$zone->time_zone);
   
/*$guifi_dns=array(guifi_get_dns($zone,1),'10.139.50.1');
foreach ($guifi_dns as $dns)
$file_dns='nameserver '.$dns.'
';
   _out_file($file_dns,'/etc/resolv.conf');
*/

#  _outln_nvram('wl_afterburner','on');		# eing? xD
#  _outln_nvram('wl_frameburst','on');		# eing? xD
   // Setting outpur power (mW)
   #_outln_nvram('txpwr','28');
    if (empty($dev->radios[0][antenna_mode]))
         $dev->radios[0][antenna_mode]= 'Main';
        if ($dev->radios[0][antenna_mode] != 'Main') 
          $dev->radios[0][antenna_mode]= '1';
        else
          $dev->radios[0][antenna_mode]= '0';
#   _outln_nvram('txant',$dev->radios[0][antenna_mode]);
#   _outln_nvram('wl0_antdiv','0');
#   _outln_nvram('wl_antdiv','0');
#   _outln_nvram('block_wan','0');
   
   #if ($dev->variable['firmware'] == 'Talisman') {
   #  _outln_nvram('ident_pass','0');
   #  _outln_nvram('multicast_pass','0');
   #  _outln_nvram('wl_closed','0');
   #  _outln_nvram('block_loopback','0');
   #}
   
   _outln_comment();
   _outln_comment(t('Management'));
   ////_outln_nvram('telnetd_enable','1');
   
   //_outln_nvram('sshd_enable','1');
   //_outln_nvram('sshd_passwd_auth','1');
$sshd_config='config dropbear
        option PasswordAuth on
        option Port         22
';
   _out_file($sshd_config,'/etc/config/dropbear');
   
   //_outln_nvram('remote_management','1');
//   _outln_nvram('remote_mgt_https','1');
   //_outln_nvram('snmpd_enable','1');
   //_outln_nvram('snmpd_sysname','guifi.net');
   //_outln_nvram('snmpd_syscontact','guifi_at_guifi.net');
   
$file_snmp='
config "snmp" "snmp"
        option privatename      \'guifi.net\'
        option privatesrc       \'guifi.net\'
        option publicname       \'guifi.net\'
        option publicsrc        \'guifi.net\'
';
_out_file($file_snmp,'/etc/config/snmp');
   

   $ntp = guifi_get_ntp($zone);
   if (empty($ntp)) {
     //_outln_nvram('ntp_enable','0');
   } else {
   _outln_comment();
   _outln_comment('NTP Network time protocol');
     //_outln_nvram('ntp_enable','1');
     //_outln_nvram('ntp_server',$ntp);
$file_ntp='
restrict 127.0.0.1
driftfile  /etc/ntp.drift
server '.$ntp.'
';
_out_file($file_ntp,'/etc/ntp.conf');

   }
 
    _outln_comment();
    //echo '<br>'.$dev->radios[0][mode].'-------<br>';

    switch ($dev->radios[0][mode]) {
    case "ap": case "AP":
      _outln_comment(t('AP mode'));
      //_outln_nvram('wl0_mode','ap');

global $wireless_model, $file_wireless;

$bssid='option bssid ';
$mode = 'ap';
      
      /*_outln_nvram('wl_macmode','disable');
      _outln_nvram('wl0_macmode','disable');
      _outln_nvram('wl_macmode1','disable');
      _outln_nvram('wl0_macmode1','disable');*/
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
              $gateway = $link['interface']['ipv4']['ipv4'];
       // TODO!!!
              $mode = 'sta';
              //_outln_nvram('wl0_mode','sta');
              _out('	option ssid	guifi.net-'.guifi_get_ap_ssid($link['interface']['device_id'].'    '.$link['interface']['radiodev_counter']));
       
              _outln_nvram('wan_gateway',$gateway);
            }
          }
     if ($dev->variable['firmware'] == 'Alchemy') {
       $filter = implode(" ",$ap_macs);
       if ($filter == "" ) {
         _outln_comment(t('WARNING: AP MAC not set'));
         $filter = "FF:FF:FF:FF:FF:FF";
       }
       /*_outln_nvram('wl_macmode','allow');
       _outln_nvram('wl0_macmode','allow');
       _outln_nvram('wl_macmode1','other');
       _outln_nvram('wl0_macmode1','other');
       _outln_nvram('wl_maclist',$filter);
       _outln_nvram('wl0_maclist',$filter);
       _outln_nvram('wl_mac_list',$filter);
       _outln_nvram('wl0_mac_list',$filter);*/
     } else {
       /*_outln_nvram('wl_macmode','disabled');
       _outln_nvram('wl0_macmode','disabled');
       _outln_nvram('wl_macmode1','disabled');
       _outln_nvram('wl0_macmode1','disabled');*/
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

$file_wireless='
config wifi-device wifi0
 option type '.$wireless_model.'
 option channel '.$dev->radios[0][channel].'
 option mode \'11b\'
 option diversity \'0\'
 option disabled \'0\'
 option txantenna \''.$dev->radios[0][antenna_mode].'\'
 option rxantenna \'0\'

config wifi-iface
 option device wifi0
 option network lan
 option mode '.$mode.'
 option ssid guifi.net-'.guifi_to_7bits($dev->radios[0][ssid]).'
 '.$bssid.'
 option encryption none
 option hidden \'0\'
 option isolate \'0\'
 option txpower \'14\'
 option bgscan \'1\'
';

      _out_file($file_network,'/etc/config/network');
      _out_file($file_wireless,'/etc/config/wireless');


   _outln_comment();
}


function guifi_unsolclic_vlan_vars($dev,&$rc_startup) {
  global $otype;
 
  function vout($if, $ipv4, $link) {
    global $otype; 

    $output = '# '.$if.': '.guifi_get_hostname($link['interface'][device_id]);
    if ($otype == 'html') $output .= "\n<br />"; else $output .= "\n";
    $item = _ipcalc($ipv4[ipv4],$ipv4[netmask]);
    if (!preg_match("/(Working|Testing|Building)/",$link[flag])) 
      $output .= '# '; 
    $output .= 'ifconfig '.$if.' '.$ipv4[ipv4].' netmask '.$ipv4[netmask].' broadcast '.$item['broadcast']; 
    if ($otype == 'html') $output .= "\n<br />"; else $output .= "\n";

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
    if ($otype == 'html') $rc_startup .= "\n<br />"; else $rc_startup .= "\n";
    $rc_startup .= $rc;
  }
  /*if ($vlans) {
    _outln_comment();
    _outln_comment('VLANs -- radio: '.$dev->id.'-'.$dev->nick);
    switch ($dev->variable['model_id']) {
    case "1": // WRT54Gv1-4 
    case "15":// WHR-HP-G54, WHR-G54S (BUFFALO) 
    case "17":// WRT54GSv1-2 
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
    case "16": // WRT54GL 
    case "18": // WRT54GSv4 
    _outln_nvram('vlan2hwname','et0');
    _outln_nvram('vlan3hwname','et0');
    _outln_nvram('vlan4hwname','et0');
    _outln_nvram('vlan0ports','4 3 5*');
    _outln_nvram('vlan1ports','4 5');
    _outln_nvram('vlan2ports','2 5');
    _outln_nvram('vlan3ports','1 5');
    _outln_nvram('vlan4ports','0 5');

   }
  }*/
} // vlan_vars function

function guifi_unsolclic_wds_vars($dev) {
  
  global $rc_startup, $file_wireless, $file_network;
print_r($dev);
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
  _out(">>> ".count($wds_links));
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
    } else {
      _outln_nvram('wl_wds'.($key+1).'_enable','0');
    }
    _outln_nvram('wl_wds'.($key+1).'_ipaddr',$iplocal[$key][ipv4]);
    _outln_nvram('wl_wds'.($key+1).'_hwaddr',$wds['interface'][mac]);
    _outln_nvram('wl_wds'.($key+1).'_netmask',$iplocal[$key][netmask]);
  
$file_wireless.='
config wifi-iface
        option device wifi0
        option network lan
        option mode \'wds\'
        option bssid'.$wds['interface'][mac].'
        option encryption none
        option hidden \'0\'
        option isolate \'0\'
        option txpower \'18\'
        option bgscan \'1\'
';  
 #option wds \'0\'


$file_network.='
config interface lan
        option ifname   ath'.$key.'
        option type     bridge
        option proto    static
        option ipaddr   '.$iplocal[$key][ipv4].'
        option netmask  '.$iplocal[$key][netmask].'
        option dns      "'.$file_dns.'"
';



  
  }
  if (count($wds_links) >= 11)
    return;

  _outln_comment();
  /*_outln_comment(t('Free WDS slots'));
  for ($key = count($wds_links) + 1; $key <= 10; $key++) {
    _outln_nvram('wl_wds'.($key).'_desc',t('free'));
    _outln_nvram('wl_wds'.($key).'_enable','0');
    _outln_nvram('wl_wds'.($key).'_ipaddr','172.0.0.0');
    _outln_nvram('wl_wds'.($key).'_hwaddr','00:13:00:00:00:00');
    _outln_nvram('wl_wds'.($key).'_netmask','255.255.255.252');
  }*/
  _out_nvram('wl0_wds',$wds_str.'"');
  _outln_nvram('wl0_lazywds','0');
  _outln_nvram('wl_lazywds','0');




} // wds_vars function

global $wireless_model;

  switch ($dev->variable['model_id']) {
    // DONT USE BROADCOM CHIPS!, PRIVATE DRIVERS! :P
    case "1":	// WRT54Gv1-4 
    case "15":	// WHR-HP-G54, WHR-G54S (BUFFALO) 
    case "17":	// WRT54GSv1-2 
    case "16":	// WRT54GL 
    case "18":	// WRT54GSv4
      $wireless_model='broadcom';
      break;
    case "25":	// NanoStation2
    case "26":	// NanoStation5
    case "30":	// F o n era, Meraki
      $wireless_model='atheros';
      break;
    default:
      _outln_comment('model id not supported');
      exit;
  }
  _outln_comment();
  _outln_comment('unsolclic version: '.$version);
  _outln_comment(' !!! WARNING: firmware only tested in F o n era !!!');
  _outln_comment(t("open a telnet/ssh session on your device and run the script below."));
  _outln_comment(t("Note: Use Status/Wireless survey to verify that you have the"));
  _outln_comment(t("antenna plugged in the right connector. The right antena is probably"));
  _outln_comment(t("the one which is at the right, looking the WRT54G from the front"));
  _outln_comment(t("(where it have the leds). If needed, change the antenna connector"));
  _outln_comment(t("at Wireless->Advanced Settings."));
  _outln_comment(t('Security notes:'));
  _outln_comment(t('Once this script is executes, the router password for root/admin users is "guifi"'));
  _outln_comment(t('You must change this password if you want to keep it secret. Upon request, your setup'));
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
  //guifi_unsolclic_qos();

  _outln_comment();
  _outln_comment(t('end of script and reboot'));
  //_out('nvram commit');
  _out('reboot');


}

?>
