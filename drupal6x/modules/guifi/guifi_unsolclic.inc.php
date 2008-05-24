<?php

// Generador dels unsolclic
function guifi_unsolclic($id, $format = 'html') {
  global $rc_startup;
  global $ospf_zone;
  global $otype;

  $otype = $format;

  $rc_startup = "";
  $dev = (object) guifi_device_load($id);
//  print_r($dev);


  if ($dev->variable['firmware'] == 'n/a') {
	_outln_comment(t("ERROR: I do need a firmware selected at the radio web interface: ").'<a href=/guifi/device/'.$id.'/edit>http://guifi.net/guifi/device/'.$id.'/edit');
        return;
  } else {
	_outln_comment(t("Generated for:"));
	_outln_comment($dev->variable['firmware']);
  }

  // Mainstream is for Linksys type firmwares, if others, branch
  // include specific firmware here
  include_once("firmware/mikrotik-routeros.inc.php");	// RouterOs Firmware
  include_once("firmware/wrt-sveasoft-dd.inc.php");	// WRT based firmwares (DD-guifi, DD-WRT, Alchemy and Talisman)
  include_once("firmware/nanostation.inc.php");		// Ubutiqui NanoStation2 and Nanostation5 Firmware
  include_once("firmware/kamikaze.inc.php");		// OpenWRT Kamikaze Firmware
  include_once("firmware/firmware-todo.inc.php");	// TODO firmwares
 
  switch ($dev->variable['firmware']) {
    case 'RouterOSv2.9':
    case 'RouterOSv3.x':
      unsolclic_routeros($dev);
      exit;
      break;
    case 'DD-guifi':
    case 'DD-WRT':
    case 'Alchemy':
    case 'Talisman':
      unsolclic_wrt($dev);
      exit;
      break;
    case 'NanoStation':
      unsolclic_nano($dev);
      exit;
      break;
    case 'kamikaze':
      unsolclic_kamikaze($dev);
      exit;
      break;
    case 'Freifunk-BATMAN':
    case 'Freifunk-OLSR':
    case 'whiterussian':
      unsolclic_todo($dev);
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
}
function _outln($string = '') {
  global $otype;

  print $string;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _outln_comment($string = '') {
  global $otype;

  print "# ".$string;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _outln_nvram($parameter, $value) {
  global $otype;

  print "nvram set ".$parameter.'="';
 
  if (strlen($value) <= 80) {
    print $value;
  } else { 
    $pos = 0;
    if ($otype == 'html') print "\n<br />"; else print "\n";
    do {
      print substr($value, $pos * 80, 80).'\\';
      $pos ++;
      if ($otype == 'html') print "\n<br />"; else print "\n";
    } while (strlen(substr($value,($pos-1) * 80)) > 80);
  }
  print('"');
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _out_nvram($parameter,$value = null) {
  global $otype;
  print "nvram set ".$parameter.'="';
  if (!empty($value))
    print $value;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function _out($value = '', $end = '') {
  global $otype;
  print "    ".$value.$end;
  if ($otype == 'html') print "\n<br />"; else print "\n";
}

function guifi_unsolclic_if($id, $itype) {
  return db_fetch_object(db_query("SELECT i.id, a.ipv4, a.netmask FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=0 WHERE device_id = %d AND interface_type = '%s' LIMIT 1",$id,$itype));
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
?>
