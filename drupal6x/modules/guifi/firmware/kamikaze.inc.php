<?php

function unsolclic_kamikaze($dev) {
  $version = "1.0";
  $loc = node_load(array('nid'=>$dev->nid));
  $zone = node_load(array('nid'=>$loc->zone_id));

function guifi_kamikaze_files($dev,$zone) {

//SOME VARIABLES
  foreach ($dev->radios[0]['interfaces'] as $interface_id => $interface) 
    foreach ($interface['ipv4'] as $ipv4_id => $ipv4) 
      if (isset($ipv4['links'])) foreach ($ipv4['links'] as $key => $link) {
        if ($link['link_type'] == 'ap/client') {
          $gateway = $link['interface']['ipv4']['ipv4'];
        }
      }
  $wan = guifi_unsolclic_if($dev->id,'Wan');
  $wan_dns=guifi_get_dns($zone,1);
  $apssid = guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']);
  $wireless_model= 0;
  $wireless_iface= 0;

  switch ($dev->variable['model_id']) {
    case "1":		// WRT54Gv1-4 
    case "15":	// WHR-HP-G54, WHR-G54S (BUFFALO) 
    case "17":	// WRT54GSv1-2 
    case "16":	// WRT54GL 
    case "18":	// WRT54GSv4
      $wireless_model='broadcom';
      $wireless_iface='wl0';
      $vlans='config switch eth0
        option vlan0    \"1 2 3 4 5*\"
        option vlan1    \"0 5\"
      ';
      $lan_iface="eth0.0";
      $wan_iface="eth0.1";
      $txant="txant";
      $rxant="rxant";
      break;
    case "20":	// RB133
    case "21":	// RB133C
    case "22":	// Rb112
    case "23":	// Rb153
    case "25":	// NanoStation2
    case "26":	// NanoStation5
    case "30":	// F o n era, Meraki
      $wireless_model='atheros';
      $wireless_iface='wifi0';
      $vlans=NULL;
      $lan_iface="eth0";
      $wan_iface="ath0";
      $txant="txantenna";
      $rxant="rxantenna";
      break;
    default:
      _outln_comment('model id not supported');
      exit;
  }

  if (empty($dev->radios[0][antenna_mode]))
    $dev->radios[0][antenna_mode]= 'Main';
      if ($dev->radios[0][antenna_mode] != 'Main') 
        $dev->radios[0][antenna_mode]= '1';
      else
        $dev->radios[0][antenna_mode]= '0';

// SECTION FILES

// FILE NETWORK
  $file_network='
'.$vlans.'
config interface loopback
        option \'ifname\'  \'lo\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \'127.0.0.1\'
        option \'netmask\'  \'255.0.0.0\'

config interface lan
        option \'ifname\'   \''.$lan_iface.'\'
        option \'type\'     \'bridge\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \'192.168.1.1\'
        option \'netmask\'  \'255.255.255.0\'

config interface wan
        option \'ifname\'   \''.$wan_iface.'\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \''.$wan->ipv4.'\'
        option \'netmask\'  \''.$wan->netmask.'\'
        option \'gateway\'  \''.$gateway.'\'
        option \'dns\'      \''.$wan_dns.'\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/network'));
  _out_file($file_network,'/etc/config/network');

// FILE WIRELESS
  $file_wireless='
config \'wifi-device\' \''.$wireless_iface.'\'
        option \'type\' \''.$wireless_model.'\'
        option \'disabled\' \'0\'
        option \''.$txant.'\' \''.$dev->radios[0][antenna_mode].'\'
        option \''.$rxant.'\' \''.$dev->radios[0][antenna_mode].'\'

config wifi-iface
        option \'device\' \''.$wireless_iface.'\'
        option \'network\' \'wan\'
        option \'mode\' \'sta\'
        option \'ssid\' \''.$apssid.'\'
        option \'encryption \'none\'
        option \'txpower\' \'14\'
';

  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/wireless'));
  _out_file($file_wireless,'/etc/config/wireless');

//FILE NTP  
  $ntp = guifi_get_ntp($zone);
  $file_ntp='
restrict 127.0.0.1
driftfile  /etc/ntp.drift
server '.$ntp.'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/ntp.conf'));
  _out_file($file_ntp,'/etc/ntp.conf');

//FILE SYSTEM
  $file_system='
config system
        option \'hostname\' \''.$dev->nick.'\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/system'));
  _out_file($file_system,'/etc/config/system');

// FILE SNMP
  $file_snmp='
config \'snmp\' \'snmp\'
        option \'privatename\'      \'guifi.net\'
        option \'privatesrc\'       \'guifi.net\'
        option \'publicname\'       \'guifi.net\'
        option \'publicsrc\'        \'guifi.net\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/snmp'));
  _out_file($file_snmp,'/etc/config/snmp');

//FILE PASSWD
  $file_pass='root:WLL3bqv6fH7qM:0:0:root:/tmp:/bin/ash
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/passwd'));
  _out('PASSWD=`grep -v ^root: /etc/passwd`');
  _out_file($file_pass,'/etc/passwd');

//FILE DROPBEAR
  $sshd_config='
config dropbear
        option \'PasswordAuth\' \'on\'
        option \'Port\'         \'22\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/dropbear'));
  _out_file($sshd_config,'/etc/config/dropbear');

//FILE FIREWALL
  $firewall='
config defaults
        option \'syn_flood\' \'1\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'REJECT\'

config zone
        option \'name\' \'lan\'
        option \'input\' \'ACCEPT\'
        option \'output\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'

config zone
        option \'name\' \'wan\'
        option \'output\' \'ACCEPT\'
        option \'masq\' \'1\'
        option \'input\' \'ACCEPT\'
        option \'forward\' \'ACCEPT\'

config forwarding
        option \'src\' \'lan\'
        option \'dest\' \'wan\'

';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/firewall'));
  _out_file($firewall,'/etc/config/firewall');

}

    $model = db_fetch_object(db_query("
      SELECT *
      FROM {guifi_model}
      WHERE mid=%d", $dev->variable['model_id']));


  _outln_comment(''.$model->model.'');
  _outln_comment(' radio:     '.$dev->id.'-'.$dev->nick);
  _outln_comment();
  _outln_comment('unsolclic version: '.$version);
  _outln_comment();
  _outln_comment(t("TEST."));
  _outln_comment();
  _out();

  // print files
  guifi_kamikaze_files($dev, $zone);

_outln_comment();
  _outln_comment(t('end of script and reboot'));
  _out('reboot');
}

?>