<?php

function unsolclic_kamikaze($dev) {
  $version = "1.0";
  $loc = node_load(array('nid'=>$dev->nid));
  $zone = node_load(array('nid'=>$loc->zone_id));
  $kamikaze_dir = drupal_get_path('module', 'guifi') .'/firmware/kamikaze/';

  if ($dev->radios[0]['mode'] == 'ap') {
    switch ($dev->variable['model_id']) {
      case "1": case "15": case "16": case "17": case "18":	
      // WRT54Gv1-4, WHR-HP-G54, WHR-G54S (BUFFALO), WRT54GL, WRT54GSv1-2, WRT54GSv4
        include_once(''.$kamikaze_dir.'broadcom/kamikaze_ap.inc.php');
        break;
      case "25": case "26":
      //NanoStation2, NanoStation5
        include_once(''.$kamikaze_dir.'atheros/kamikaze_ap.inc.php');
        break;
      default:
        _outln_comment('model id not supported');
        exit;
    }
  }
  
  if ($dev->radios[0]['mode'] == 'client') {
    switch ($dev->variable['model_id']) {
      case "1": case "15": case "16": case "17": case "18":	
      // WRT54Gv1-4, WHR-HP-G54, WHR-G54S (BUFFALO), WRT54GL, WRT54GSv1-2, WRT54GSv4
        include_once(''.$kamikaze_dir.'broadcom/kamikaze_client.inc.php');
        break;
      case "25": case "26":
      //NanoStation2, NanoStation5
        include_once(''.$kamikaze_dir.'atheros/kamikaze_client.inc.php');
        break;
      default:
        _outln_comment('model id not supported');
        exit;
    }
  }

  if ($dev->radios[0]['mode'] == 'ad-hoc') {
    switch ($dev->variable['model_id']) {
      case "1": case "15": case "16": case "17": case "18":	
      // WRT54Gv1-4, WHR-HP-G54, WHR-G54S (BUFFALO), WRT54GL, WRT54GSv1-2, WRT54GSv4
        include_once(''.$kamikaze_dir.'broadcom/kamikaze_ad-hoc.inc.php');
        break;
      case "25": case "26":
      //NanoStation2, NanoStation5
        include_once(''.$kamikaze_dir.'atheros/kamikaze_ad-hoc.inc.php');
        break;
      default:
        _outln_comment('model id not supported');
        exit;
    }
  }

function guifi_kamikaze_common_files($dev,$zone) {
  list($ntp1,$ntp2) = explode(' ',guifi_get_ntp($zone,2));
    $ntp[] .= $ntp1;
    $ntp[] .= $ntp2;

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
quagga:x:51:51:quagga:/tmp/.quagga:/bin/false
';
  _outln_comment();
  _outln_comment();
  _outln_comment(t('File /etc/passwd'));
  _out_file($file_pass,'/etc/passwd');

  if (($dev->variable['model_id']  == 25) or ($dev->variable['model_id']  == 26)) {
// FILE LEDS NANOSTATION
  $file_leds='#!/bin/sh
# Control de LEDs de la nano2 per indicar la qualitat de Link
# joan.llopart +A+ guifi.net
#
# minimod de la feina del Xavi Martinez :-)

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
      if [ \$QUAL -gt 15 ]
        then
          L2T=1
      fi
      if [ \$QUAL -gt 30 ]
        then
          L3T=1
      fi
      if [ \$QUAL -gt 45 ]
        then
          L4T=1
      fi
  fi
# Encenem/apaguem LED nomes si hi ha canvi
  if [ \$L1 -ne \$L1T ]
    then
      if [ \$L1T -ne 0 ]
        then
          gpioctl set 0
        else
          gpioctl clear 0
      fi
    L1=\$L1T 
  fi
  if [ \$L2 -ne \$L2T ]
    then
      if [ \$L2T == 0 ]
        then
          gpioctl clear 1
        else
          gpioctl set 1
      fi
    L2=\$L2T
  fi
  if [ \$L3 -ne \$L3T ]
    then
      if [ \$L3T == 0 ]
        then
          gpioctl clear 3
        else
          gpioctl set 3
      fi
    L3=\$L3T
  fi
  if [ \$L4 -ne \$L4T ]
    then
      if [ \$L4T == 0 ]
        then
          gpioctl clear 4
        else
          gpioctl set 4
      fi
    L4=\$L4T
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
    if ($dev->radios[0]['mode'] == 'ap') {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/ap/'.$firmware_tftp.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware_tftp.'.</a>'));
   }
   else {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/client/'.$firmware_tftp.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware_tftp.'.</a>'));
   }
  _outln_comment();
  _outln_comment(t('Web Browser method:'));
    if ($dev->radios[0]['mode'] == 'ap') {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/ap/'.$firmware.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware.'.</a>'));
   }
   else {
  _outln_comment(t('<a href="'.base_path().'files/openwrt/client/'.$firmware.'"> Click here to download firmware OpenWRT Kamikaze file: '.$firmware.'.</a>'));
   }

  _outln_comment(t('Put the mouse cursor over the link. Right click the link and select "Save Link/Target As..." to save to your Desktop.'));
  _outln_comment();
  _out();

  // print files
  guifi_kamikaze_files($dev, $zone);
  guifi_kamikaze_common_files($dev, $zone);

_outln_comment();
  _outln_comment(t('end of script and reboot'));
  _out('reboot');

}
?>