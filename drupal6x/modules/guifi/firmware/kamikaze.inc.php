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
  $dns = guifi_get_dns($zone,2);
  list($ntp1,$ntp2) = explode(' ',guifi_get_ntp($zone,2));
    $ntp[] .= $ntp1;
    $ntp[] .= $ntp2;
  $apssid = 'guifi.net-'.guifi_get_ap_ssid($link['interface']['device_id'],$link['interface']['radiodev_counter']);
  $wireless_model = 0;
  $wireless_iface = 0;

  switch ($dev->variable['model_id']) {
    case "1": case "15": case "16": case "17": case "18":	
    // WRT54Gv1-4, WHR-HP-G54, WHR-G54S (BUFFALO), WRT54GL, WRT54GSv1-2, WRT54GSv4
      $wireless_model='broadcom';
      $wireless_iface='wl0';
      $vlans='config switch eth0
        option vlan0    \"1 2 3 4 5*\"
        option vlan1    \"0 5\"
      ';
      $mode=NULL;
      $lan_iface='eth0.0';    
      $wan_iface='eth0.1';     
      $txant='txant';
      $rxant='rxant';
      $packages='broadcom/packages';
      break;
    case "25": case "26":
    // NanoStation2, Nanostation5
      $wireless_model='atheros';
      $wireless_iface='wifi0';
      $vlans=NULL;
      if ($dev->variable['model_id']  == 25)
        $mode='option \'mode\' \'11b\'';
      if ($dev->variable['model_id']  == 26)
        $mode='option \'mode\' \'11a\'';
      $lan_iface='eth0';
      $wan_iface='ath0';
      $txant='txantenna';
      $rxant='rxantenna';
      $packages='atheros/packages';
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
        option \'dns\'      \''.$dns.'\'

config interface wan
        option \'ifname\'   \''.$wan_iface.'\'
        option \'proto\'    \'static\'
        option \'ipaddr\'   \''.$wan->ipv4.'\'
        option \'netmask\'  \''.$wan->netmask.'\'
        option \'gateway\'  \''.$gateway.'\'
        option \'dns\'      \''.$dns.'\'
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
        '.$mode.'

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

  $file_ntp='
config \'ntpserver\'
       option \'hostname\' \''.$ntp1.'\'
       option \'port\' \'123\'

config \'ntpserver\'
       option \'hostname\' \''.$ntp2.'\'
       option \'port\' \'123\'

config \'ntpserver\'
       option \'hostname\' \'1.openwrt.pool.ntp.org\'
       option \'port\' \'123\'

config \'ntpclient\'
       option \'interval\' \'60\'

config \'ntpdrift\'
       option \'freq\' \'0\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/ntpclient'));
  _out_file($file_ntp,'/etc/config/ntpclient');

//FILE SYSTEM
if (($dev->variable['model_id']  == 25) or ($dev->variable['model_id']  == 26)) {
  $resetbutton='
config \'button\'
        option \'button\' \'reset\'
        option \'action\' \'released\'
        option \'handler\' \'logger reboot\'
        option \'min\' \'0\'
        option \'max\' \'4\'
        
config \'button\'
        option \'button\' \'reset\'
        option \'action\' \'released\'
        option \'handler\' \'logger factory default\'
        option \'min\' \'5\'
        option \'max\' \'30\'
';
} else {
 $resetbutton=NULL;
 }
  $file_system='
config \'system\'
        option \'hostname\' \''.$dev->nick.'\'
        option \'zonename\' \'Europe/Andorra\'
        option \'timezone\' \'CET-1CEST,M3.5.0,M10.5.0/3\'
        '.$resetbutton.'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/system'));
  _out_file($file_system,'/etc/config/system');

//FILE PASSWD
  $file_pass='
root:WLL3bqv6fH7qM:0:0:root:/root:/bin/ash
nobody:*:65534:65534:nobody:/var:/bin/false
daemon:*:65534:65534:daemon:/var:/bin/false
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/passwd'));
  _out_file($file_pass,'/etc/passwd');


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
        option \'forward\' \'REJECT\'

config zone
        option \'name\' \'wan\'
        option \'output\' \'ACCEPT\'
        option \'input\' \'ACCEPT\'
        option \'forward\' \'REJECT\'
        option \'masq\' \'1\'

config forwarding
        option \'src\' \'lan\'
        option \'dest\' \'wan\'

config rule
        option \'dst\'              \'wan\'
        option \'src_dport\'        \'22\'
        option \'target\'           \'ACCEPT\'
        option \'protocol\'         \'tcp\'

config rule
        option \'dst\'              \'wan\'
        option \'src_dport\'        \'80\'
        option \'target\'           \'ACCEPT\'
        option \'protocol\'         \'tcp\'

config rule
        option \'dst\'              \'wan\'
        option \'src_dport\'        \'161\'
        option \'target\'           \'ACCEPT\'
        option \'protocol\'         \'udp\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/firewall'));
  _out_file($firewall,'/etc/config/firewall');

//FILE OPKG
  $opkg_conf='
src/gz guifi http://ausa.guifi.net/drupal/files/openwrt/client/'.$packages.'
dest root /
dest ram /tmp
lists_dir ext /var/opkg-lists
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/opkg.conf'));
  _out_file($opkg_conf,'/etc/opkg.conf');

/* NOT NEEDED FILES??
// FILE SNMPD
  $file_snmpd='
config \'snmp\' \'snmp\'
        option \'privatename\'      \'guifi.net\'
        option \'privatesrc\'       \'guifi.net\'
        option \'publicname\'       \'guifi.net\'
        option \'publicsrc\'        \'guifi.net\'
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/config/snmpd'));
  _out_file($file_snmpd,'/etc/config/snmpd');

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

*/

  if (($dev->variable['model_id']  == 25) or ($dev->variable['model_id']  == 26)) {
// FILE LEDS NANOSTATION
  $file_leds='#!/bin/sh
# Control de LEDs de la nano2 per indicar la qualitat de Link
# joan.llopart +A+ guifi.net
#
# minimod de la feina del Xavi Martinez :-)

gpioctl dirout 0
gpioctl dirout 1
gpioctl dirout 3
gpioctl dirout 4
L1=0
L2=0
L3=0
L4=0

#Iniciem el loop

while [ 1 ] 
do

# Pillem la qualitat de l\'enllac
 QUAL=\`awk \'/ath0/ {print \$3}\' /proc/net/wireless\`
#Li traiem el punt final
 QUAL=\${QUAL%.*}
# Inicialment, tots a 0
 L1T=0
 L2T=0
 L3T=0
 L4T=0
# Comprobem un a un
 if [ \$QUAL != 0 ]
 then
   L1T=1
  if [ \$QUAL -gt 10 ]
  then
   L2T=1
  fi
  if [ \$QUAL -gt 20 ]
  then
   L3T=1
  fi
  if [ \$QUAL -gt 30 ]
  then
   L4T=1
  fi
 fi # \$QUAL!=0

# Encenem/apaguem LED nomes si hi ha canvi
 if [ \$L1 -ne \$L1T ]
  then
   gpioctl clear 0
   L1=\$L1T
  else
   gpioctl set 0
 fi
 if [ \$L2 -ne \$L2T ]
  then
   gpioctl clear 1
   L2=\$L2T
  else
   gpioctl set 1
 fi
 if [ \$L3 -ne \$L3T ]
  then
   gpioctl clear 3
   L3=\$L3T
  else
   gpioctl set 3
 fi
 if [ \$L4 -ne \$L4T ]
  then
   gpioctl clear 4
   L4=\$L4T
  else
   gpioctl set 4
 fi

 sleep 1

done

';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/leds.sh'));
  _out_file($file_leds,'/etc/leds.sh');
  _out('chmod +x /etc/leds.sh');

// FILE RC LEDS NANOSTATION
  $file_rcleds='#!/bin/sh /etc/rc.common
START=80
start() {
        /etc/leds.sh &
        }

stop() {
       ps ax > /tmp/ledspid
       PID=\`awk \'/leds.sh/ {print \$1}\' /tmp/ledspid\`
       kill -9 \$PID
       rm /tmp/ledspid
       sleep 1
       gpioctl clear 0
       gpioctl clear 1
       gpioctl clear 3
       gpioctl clear 4
       }

';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/init.d/nanoleds'));
  _out_file($file_rcleds,'/etc/init.d/nanoleds');
  _out('chmod +x /etc/init.d/nanoleds');
  _out('ln -s /etc/init.d/nanoleds /etc/rc.d/S80nanoleds');

  }
}

  switch ($dev->variable['model_id']) {
    case "1":	// WRT54Gv1-4
    case "15":	// WHR-HP-G54, WHR-G54S (BUFFALO)
    case "16":	// WRT54GL
      $firmware_tftp = 'broadcom/openwrt-wrt54g-squashfs.bin';
      $firmware = 'broadcom/openwrt-brcm-2.4-squashfs.trx';
      break;
    case "17":	// WRT54GSv1-2
      $firmware_tftp = 'broadcom/openwrt-wrt54gs-squashfs.bin';
      $firmware = 'broadcom/openwrt-brcm-2.4-squashfs.trx';
      break;
    case "18":	// WRT54GSv4
      $firmware_tftp = 'broadcom/openwrt-wrt54gs_v4-squashfs.bin';
      $firmware = 'broadcom/openwrt-brcm-2.4-squashfs.trx';
      break;
    case "25":	// NanoStation2
      $firmware_tftp = 'atheros/openwrt-ns2-squashfs.bin';
      $firmware = 'atheros/openwrt-ns2-squashfs.bin';
      break;
    case "26":	// NanoStation5
      $firmware_tftp = 'atheros/openwrt-ns5-squashfs.bin';
      $firmware = 'atheros/openwrt-ns5-squashfs.bin';
      break;
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
  _outln_comment(t('TFTP method:'));
  _outln_comment(t('<a href="'.base_path().'files/openwrt/client/'.$firmware_tftp.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware_tftp.'.</a>'));
  _outln_comment();
  _outln_comment(t('Web Browser method:'));
  _outln_comment(t('<a href="'.base_path().'files/openwrt/client/'.$firmware.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware.'</a>'));

  _outln_comment(t('Put the mouse cursor over the link. Right click the link and select "Save Link/Target As..." to save to your Desktop.'));
  _outln_comment();
  _out();

  // print files
  guifi_kamikaze_files($dev, $zone);

_outln_comment();
  _outln_comment(t('end of script and reboot'));
  _out('reboot');
}

?>