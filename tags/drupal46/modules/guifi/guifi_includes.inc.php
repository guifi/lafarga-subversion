<?php

// Miscellaneous and auxiliar routines for guifi.module

/**
 *  _guifi_mac_sum adds an integer to a MAC address
 *    use negative op for substract
 *    returns the resulting new MAC
**/

function _guifi_mac_sum($mac, $op) {
  $mac = _guifi_validate_mac($mac);
  if ($mac) {
    $mac_first = substr($mac,0,6);
    $mac_last = str_replace(":","",substr($mac,-11));
    $dec_mac = base_convert($mac_last,16,10) + $op;
    return $mac_first .strtoupper(substr(chunk_split(sprintf("%08x",$dec_mac),2,':'),0,11));
  } else
    return false;
}

/**
 * _guifi_validate_mac validates a MAC address
 *
*/
function _guifi_validate_mac($mac) {
  if (($mac == '00:00:00:00:00:00') || ($mac == false) || ($mac == NULL))
    return false;
  $mac = str_replace(":","",$mac);
  $mac = str_replace("-","",$mac);
  $mac = str_replace("/","",$mac);
  $mac = str_replace(" ","",$mac);
  $mac = str_replace(".","",$mac);
  if (strlen($mac) != 12)
    return false;
  foreach (explode(':',substr(chunk_split($mac,2,':'),0,17)) as $item) {
    if (($item != '00') && (hexdec($item) == 0))
      return false;
  }
  return strtoupper(substr(chunk_split($mac,2,':'),0,17));
}

/**
 * guifi_rrdfile
**/

function guifi_rrdfile($nick) {
   return str_replace(array (' ','.','-','?','&','%','$'),"",strtolower($nick));
}

/**
 * guifi_traffic_rrdfile
**/

function guifi_traffic_rrdfile($nick,$mrtg_index = '') {
   if (mrtg_index != '')
     return variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($nick).'_'.$mrtg_index.'.rrd';
   else
     return variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($nick).'.rrd';
}

/**
 * _guifi_tostrunits convert a number to string format in B,KB,MB...
**/
function _guifi_tostrunits($num) {
  $base = array('B','KB','MB','GB','TB','PB');
  $str = sprintf("%3d B",$num);
  foreach ($base as $key => $unit) {
    if ($num > pow(1024,$key)) 
      $str = sprintf("%7.2f %s",$num/pow(1024,$key),$unit);
    else
      return $str;
  }
}

/**
 *
**/
function guifi_availabilitystr($device) {
  $pings = guifi_get_pings($device->nick);
  if ($pings['samples'] > 0) {
    $available = sprintf("%.2f%%",$pings['succeed']);
    if ($pings['last_succeed'] == 0)
      $last = 'Down';
    else
      $last = 'Up';
    $last_str = sprintf("<a href=\"guifi/graph_detail?type=pings&radio=%d\">%s (%s)</a>",$device->id,t($last),$pings['last_sample']);
  } else {
     $last = 'number';
    $last_str = t('n/a');
    $available = t('n/a');
  }
  $var['last_str'] = $last_str;
  $var['available'] = $available;
  $var['last'] = $last;

  return $var;
}

function guifi_main_interface($mode = null) {
  switch ($mode) {
    case 'ap': return 'wLan/Lan';
    case 'client': return 'Wan';
    case null: return 'Lan';
    default: return 'Lan';
  }
}

function guifi_main_ip($device_id) {
  $qips = db_query('SELECT a.ipv4,a.netmask,a.id, i.interface_type FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON a.interface_id=i.id WHERE i.device_id=%d ORDER BY a.id',$device_id);
  $ip_array=array();
  while ($ip = db_fetch_object($qips)) {
    if ($ip->ipv4 != null) {
      $item = _ipcalc($ip->ipv4,$ip->netmask);
      switch ($ip->interface_type) {
      case 'wLan/Lan':
        $ip_array[0+$ip->id]=array('ipv4'=>$ip->ipv4,'maskbits'=>$item[maskbits]); break;
      case 'Lan':
        $ip_array[100]=array('ipv4'=>$ip->ipv4,'maskbits'=>$item[maskbits]); break;
      case 'Wan':
        $ip_array[200]=array('ipv4'=>$ip->ipv4,'maskbits'=>$item[maskbits]); break;
      case 'wLan':
        if (!isset($ip_array[3])) $ip_array[300]=array('ipv4'=>$ip->ipv4,'maskbits'=>$item[maskbits]); break;
      case 'wds/p2p':
        if (!isset($ip_array[4])) $ip_array[400]=array('ipv4'=>$ip->ipv4,'maskbits'=>$item[maskbits]); break;

      }
    }
  }
  ksort($ip_array);
  reset($ip_array);
  return current($ip_array);
}

function guifi_types($type,$start = 24,$end = 0,$relations = null) {
  if ($type == 'netmask') {
    for ($n = $start; $n > $end; $n--) {
      $item = _ipcalc_by_netbits('0.0.0.0',$n);
      $masks[$item['netmask']] = $item['netmask'].' - '.$item['hosts'].' '.t('hosts');
    }
    if ($end == 0)
      $masks['0.0.0.0'] = t('0 - all hosts');
    return $masks;
  }

  $values = array();
  if ($relations == null) $query = db_query("SELECT text, description FROM {guifi_types} WHERE type='%s' ORDER BY id",$type);
  else
    $query = db_query("SELECT text, description FROM {guifi_types} WHERE type='%s' AND RELATIONS LIKE '%s' ORDER BY id",$type,'%'.$relations.'%');
  while ($type = db_fetch_object($query)) {
    $values[$type->text] = t($type->description);
  }
  return $values;
}

function guifi_get_mac($id,$itype) {
  $dev = db_fetch_object(db_query("SELECT mac from {guifi_devices} WHERE id = %d",$id));
  $mac = db_fetch_object(db_query("SELECT relations FROM {guifi_types} WHERE type='interface' AND text='%s'",$itype));

//  print "Assign MAC: ".$id."-".$itype." Device: ".$dev->id." op ".$mac->relations."\n<br />";

  if (!empty($dev->mac))
    return _guifi_mac_sum($dev->mac,$mac->relations);
  else
    return null;
}

/*
 * guifi_type_relation()
 * Validates if a relationship is valid or not
 * 
 * @type type code
 * @subject type code of the subject to check
 * @related type code of the relationship to be checked
 * 
 * @return true if relation is valid,m false if is invalid
*/
function guifi_type_relation($type,$subject,$related) {
  $relations = db_fetch_object(db_query("SELECT text, relations FROM {guifi_types} WHERE type='%s' AND text='%s'",$type,$subject));
  $pattern = str_replace("/","\/",$relations->relations);

//  print "preg_match: ".$pattern." ".$subject." related ".$related." relations ".$re"\n<br />";
  return preg_match("/(".$pattern.")/",$related);
}

function guifi_form_column($form) {
  return "  <td>\r  ".$form."\r  </td>\r";
}

function guifi_form_column_group($title,$form,$help) {
  return form_group($title,"\n<table>\r  <tr>\r  ".$form."  </tr>\r</table>\n",$help);
}

function guifi_url($url,$text = null) {
  if ($text == null)
   $text = $url;
  if (!preg_match("/^http:\/\//",$url))
    $url = 'http://'.$url;
  return '<a href="'.$url.'">'.$text.'</a>';
}

function guifi_servers_select() {
  $query = db_query("SELECT d.id, d.nick FROM {guifi_devices} d WHERE d.type IN ('server','cam')");

  $var[0] = t('Not assigned');
  while ($device = db_fetch_object($query)) {
    $var[$device->id] = $device->nick;
  } 
  return $var;
}

function guifi_devices_select($nid, $link_type, $radio_mode, $did, $rid, $kms = null) {
  
  $var = array();

  function _set_value($device,&$var,$id,$rid) {
    $prefix = '';

    if (isset($device->radiodev_counter)) 
      $ql = db_query('SELECT l1.id FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l1.device_id=%d AND l1.ipv4_id=%d AND l2.device_id=%d AND l2.ipv4_id=%d',$device->id, $device->radiodev_counter,$id,$rid);
     else
      $ql = db_query('SELECT l1.id FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l1.device_id=%d AND l2.device_id=%d',$device->id, $id);

    
    if ((db_num_rows($ql) > 0) or 
      // link already exists
       (!user_access('administer guifi zones') and ($device->clients_accepted=='No'))) {
      // backhaul and not zone administrator, can't link to backhaul nodes
      return;
    }
    if ($device->clients_accepted == 'No') 
      $backhaul = '**'.t('backhaul').'**';

    $zone = db_fetch_object(db_query('SELECT title FROM {guifi_zone} WHERE id=%d',$device->zone_id));
    if ($device->distance) {
      $value= $zone->title.', '.$device->ssid.$backhaul.' '.$device->distance.' '.t('kms').')';
    } else
      $value= $zone->title.', '.$device->ssid;

    if (isset($device->radiodev_counter))
      $var[$device->nid.','.$device->id.','.$device->radiodev_counter] = $value;
    else
      $var[$device->nid.','.$device->id] = $value;

  } // eof function _set_value

  $found = false;

  if ($kms == null)
    $kms = variable_get('guifi_max_distance',25);
  if ($link_type == 'cable') {
    if ($radio_mode != 'cable-router')
      $query = db_query("SELECT l.lat, l.lon, r.nick ssid, r.id, r.nid, z.id zone_id  FROM {guifi_devices} r,{guifi_location} l, {guifi_zone} z WHERE l.id=%d AND r.nid=l.id AND l.zone_id=z.id",$nid);
    else
      $query = db_query("SELECT l.lat, l.lon, r.nick ssid, r.id r.nid, z.id zone_id, r.type  FROM {guifi_devices} r,{guifi_location} l, {guifi_zone} z WHERE r.type IN ('radio','nat') AND l.id=%d AND r.nid=l.id AND l.zone_id=z.id",$nid);
  } else
    $query = db_query("SELECT l.lat, l.lon, r.id, r.clients_accepted, r.nid, z.id zone_id, r.radiodev_counter, r.ssid, r.mode FROM {guifi_radios} r,{guifi_location} l, {guifi_zone} z WHERE l.id<>%d AND r.nid=l.id AND l.zone_id=z.id",$nid);

 
  $devdist = array();
  $devarr = array(); 
  $k = 0;
  while ($device = db_fetch_object($query)) {
    $k++;
    if ($link_type!='cable') {
      $oGC = new GeoCalc();
      $node = db_fetch_object(db_query('SELECT lat, lon FROM {guifi_location} WHERE id=%d',$nid));
      $distance = round($oGC->EllipsoidDistance($device->lat, $device->lon, $node->lat, $node->lon),3);
    } else
      $distance = 0;
    if ($distance < $kms) {
      $devdist[$k] = $distance;
      $devarr[$k] = $device;
      $devarr[$k]->distance=$distance;
    }
  }
  asort($devdist);

  if (!empty($devdist)) foreach ($devdist as $id=>$foo) {    
    $device = $devarr[$id];

    switch ($link_type) {
      case 'ap/client':
          if (($radio_mode == 'ap') and ($device->mode == 'client')) {
            $cr = guifi_count_radio_links($device->id);
            if ($cr[ap] < 1)
              _set_value($device,$var,$did,$rid);
          } else 
          if (($radio_mode == 'client') and ($device->mode == 'ap')) 
            _set_value($device,$var,$did,$rid);
        break; 
      case 'wds':
        if ($device->mode == 'ap')
          _set_value($device,$var,$did,$rid);
        break; 
      case 'cable':
          _set_value($device,$var,$did,$rid);
        break; 
      } // eof switch link_type
  } // eof while query device,node,zone

  return $var;
}

function guifi_get_all_interfaces($id,$type = 'radio', $db = true) {
  if (($db) and ($type == 'radio')) 
    $model = db_fetch_array(db_query('SELECT m.interfaces FROM {guifi_radios} r LEFT JOIN {guifi_model} m ON m.mid=r.model_id WHERE r.id=%d',$id));
  else 
    $model[interfaces] = 'Lan';
  return explode('|',$model[interfaces]);
}

function guifi_get_free_interfaces($id,$edit = array()) {
  if ($edit[type] == 'radio') 
    $model = db_fetch_array(db_query('SELECT m.interfaces FROM {guifi_model} m WHERE mid=%d',$edit[variable][model_id]));
  else 
    $model[interfaces] = 'Lan';
  $possible = explode('|',$model[interfaces]);
  $qi = db_query('SELECT interface_type FROM {guifi_interfaces} WHERE device_id=%d',$id);
  $used = array();
  while ($i = db_fetch_object($qi)) {
    $used[] = $i->interface_type;
  }
  if ($edit != null) 
  if (count($edit['interfaces']) > 0) foreach ($edit[interfaces] as $k=>$value) {
    if ($value[deleted]) continue;
    $used[] = $value[interface_type];
  }

//  print "Possible: ";
//  print_r($possible);
//  print "\n<br />Used: ";
//  print_r($used);
  return array_diff($possible, $used);
}


function _guifi_set_namelocation($location) {
  $prefix = '';
  foreach (array_reverse(guifi_get_zone_parents($location->zone_id)) as $parent) {
    if ($parent > 0) {
      $result = db_fetch_array(db_query('SELECT z.id, z.title, z.master FROM {guifi_zone} z WHERE z.id = %d',$parent));
      if ($result['master']) {
        $prefix .= $result['title'].', ';
      }
    }

  }
  return $prefix.$location->nick;
} // eof function _set_value



function guifi_nodes_select() {

  $var = array();

  $found = false;

  $query = db_query("SELECT l.id, l.nick, z.id zone_id FROM {guifi_location} l, {guifi_zone} z WHERE l.zone_id=z.id ORDER BY z.id, l.id, l.nick");

  
  while ($location = db_fetch_object($query)) {
    $var[$location->id] = _guifi_set_namelocation($location,$new_pointer,$found);
  } // eof while query node,zone

  asort($var);

  return $var;
}

function guifi_services_select($stype) {

  $var = array();

  $found = false;

  $query = db_query(sprintf('SELECT s.id, n.title nick, z.id zone_id FROM {node} n,{guifi_services} s, {guifi_zone} z WHERE s.id=n.nid AND s.service_type="%s" AND s.zone_id=z.id ORDER BY z.id, s.id, s.nick',$stype));

  
  while ($service = db_fetch_object($query)) {
    $var[$service->id] = _guifi_set_namelocation($service,$new_pointer,$found);
  } // eof while query service,zone

  asort($var);

  return $var;
}

function guifi_validate_nick($nick) {
  if  ($nick != htmlentities($nick, ENT_QUOTES))
    form_set_error('nick', t('No special characters allowed for nick name, use just 7 bits chars.'));

  if (str_word_count($nick) > 1)     form_set_error('nick', t('Nick name have to be a single word.'));
   if (isset($nick)) {
    if (trim($nick) == '') {
      form_set_error('nick', t('You have to specify a nick.'));
    }
  }
}

function guifi_get_deviceuser($id) {
  $device = db_fetch_object(db_query("SELECT d.user_created FROM {guifi_devices} d WHERE d.id=%d",$id));
  return ($device->user_created);
}

function guifi_get_nodeuser($id) {
  $node = db_fetch_object(db_query("SELECT d.user_created FROM {guifi_location} d WHERE d.id=%d",$id));
  return ($node->user_created);
}

function guifi_get_hostname($id) {
  $device = db_fetch_object(db_query("SELECT d.nick FROM {guifi_devices} d WHERE d.id=%d",$id));
  return guifi_to_7bits($device->nick);
}

function guifi_get_ap_ssid($id,$radiodev_counter) {
  $radio = db_fetch_object(db_query("SELECT r.ssid, d.id FROM {guifi_radios} r LEFT JOIN {guifi_devices} d ON r.id=d.id WHERE r.id=%d AND r.radiodev_counter=%d",$id,$radiodev_counter));
  return guifi_to_7bits($radio->ssid);
}

function guifi_get_nodename($id) {
  $node = db_fetch_object(db_query("SELECT d.nick FROM {guifi_location} d WHERE d.id=%d",$id));
  return guifi_to_7bits($node->nick);
}

function guifi_get_zone_of_node($id) {
  $node = db_fetch_object(db_query("SELECT d.zone_id FROM {guifi_location} d WHERE d.id=%d",$id));
  return $node->zone_id;
}

/**
 * guifi_get_ips
 *  gets a the allocated ips
 * @return ordered array 
**/
function guifi_get_ips($start = '0.0.0.0', $mask = '0.0.0.0',$edit = null) {
 
  $start_dec = _dec_addr($start); 
  $item = _ipcalc($start,$mask);
  $end_dec = _dec_addr($item['broadcast']);  

  $ips = array();
  $query = db_query("SELECT ipv4, netmask FROM {guifi_ipv4}");
  while ($ip = db_fetch_array($query)) {
    if ( ($ip['ipv4'] != 'dhcp') and (!empty($ip['ipv4'])) )  {
      $ip_dec = _dec_addr($ip['ipv4']);
      if ( ($ip_dec >= $start_dec) and ($ip_dec <= $end_dec) ) 
        guifi_merge_ip($ip, $ips,false);
    }
  }
  

  // going to get current device ips
  if ($edit != null)
    _ips_recurse($edit,$ips) ;

  sort($ips);

  return $ips; 
}

function _ips_recurse($var,&$ips) {
  foreach ($ips as $ip)
    $ipK[$ip[ipv4]] = $ip[ipv4];
 
//  print_r($ipK);

  foreach ($var as $k=>$value) {
//    print "K: $k\n<br />";
    if ($k == 'ipv4')  
    if (is_string($value)) {
//      print "Net: $value ".print_r($ips[_dec_addr($value)])."\n<br />";
      if (($ipK[$value] == null)  and ($value != null)) {
        unset($ip);
//        print "New Net: $value\n<br />";
//        $ip[ipv4] = $var[ipv4];
        $ip[ipv4] = $value;
        $ip[netmask] = $var[netmask];
//        print "Dec addr: "._dec_addr($ip['ipv4'])."\n<br />";
//        print_r($ip);
        guifi_merge_ip($ip, $ips, false);
      } 
    } 
    if (is_array($value))
      _ips_recurse($value,$ips);
  }
}

function guifi_merge_ip($ip, &$ips_allocated, $sort = true) {
  $ip_calc = _ipcalc($ip['ipv4'],$ip['netmask']);
  $ips_allocated[] = array_merge(array('dec' => _dec_addr($ip['ipv4'])),$ip,$ip_calc);
  if ($sort)
    sort($ips_allocated);
}


/**
 * guifi_get_nets
 *  gets a the allocated networks for a given range
 * @return ordered array 
**/
function guifi_get_nets($start = '0.0.0.0', $mask = '255.255.0.0') {
  
  $start_dec = _dec_addr($start); 
  $item = _ipcalc($start,$mask);
  $end_dec = _dec_addr($item['broadcast']);  
  
  $nets = array();
  $query = db_query("SELECT base, mask from {guifi_networks}");
  while ($net = db_fetch_array($query)) {
    $net_dec = _dec_addr($net['base']);
    if ( ($net_dec >= $start_dec) and ($net_dec <= $end_dec) ) {
      $nets[] = array_merge(array('dec' => $net_dec),$net);
    }
  }
  sort($nets);
  
  return $nets;
}

/**
 * guifi_find_subnet
 *  finds in the given range, the next free range to allocate a subnet
 *  without allocated ip's
 * @base_ip 
 *   base ip address of the subnet to look into
 * @mask_range
 *   range of the subnet to look into
 * @mask_allocated
 *   subnet mask to allocate
 * @ips_allocated
 *   ordered array having the allocated addresses, if not given
 *   will take a look into the database
 *
 * @return
 *   base ip address of the next available slot to allocate
 *   in the  searched subnet, or 0 (false) if it's full.
**/
function guifi_find_subnet($base_ip, $mask_range, $mask_allocated, $ips_allocated = null) {

  if ($ips_allocated == null) {
    $ips_allocated = guifi_get_ips($base_ip,$mask_range);
  }
//  print_r($ips_allocated);

  $net_dec = _dec_addr($base_ip); 
  $item = _ipcalc($base_ip,$mask_range);
  $end_dec = _dec_addr($item['broadcast']) + 1;  
  $item = _ipcalc($base_ip,$mask_allocated);
  $increment = $item['hosts'] + 2;

  $key = 0;
  $elem = count($ips_allocated);
  reset($ips_allocated);
 
//  print "Going 2 find the subnet bucle for ".$base_ip."/".$mask_range." - ".$mask_allocated."\n<br />";
 
  // Shifts until reaches the searched zone
  while (($ips_allocated[$key]['dec'] < $net_dec) and ($key < $elem))
    $key++;
  
  if ($key == $elem)
   return false;

  while ($net_dec < $end_dec) {

    // is there any ip allocated in the range net_dec-increment?
    $found = false; 
    $last  = $net_dec + $increment;
    do {
      $ip = $ips_allocated[$key];
      $key++;
//      print "net_dec: $net_dec last: $last Ip_dec: $ip[dec]"._dec_to_ip($ip[dec])." \n<br />";
//      print_r($ip);

      if (($ip['dec'] > $net_dec) and ($ip['dec'] < $last)) {
        $found = true;
        // if ip's broadcast > checked range, jumps to it's broadcast
        $net_ends = _dec_addr($ip['broadcast']);
        if ($net_ends > $last)
          $last = $net_ends + 1;          
        break;
      }
      // ip is already higher
      if ($ip['dec'] > $last)
        break;
    } while (($ip['dec'] < $last) and ($key < $elem));
    // There was space for the subnet here, search ends
    if (!$found) {
//      print_r($ips_allocated);
//      print "\n<br />Found: $net_dec "._dec_to_ip($net_dec)."\n<br />";
      return _dec_to_ip($net_dec);
    }

    // going to look next range
    $net_dec = $last;    

  }

  // No space available 
  return false;
  
}

/**
 * guifi_next_subnet
 *  finds in the given range, the next free range to allocate a new subnet
 * @base_ip 
 *   base ip address of the subnet to look into
 * @mask_range
 *   range of the subnet to look into
 * @mask_allocated
 *   subnet mask to allocate
 *
 * @return
 *   base ip address of the next available slot to allocate
 *   in the searched subnet, or 0 (false) if it's full.
**/
function guifi_next_subnet($base_ip, $mask_range, $mask_allocated) {

  $nets_allocated = guifi_get_nets($base_ip,$mask_range);

  $net_dec = _dec_addr($base_ip); 
  $item = _ipcalc($base_ip,$mask_range);
  $end_dec = _dec_addr($item['broadcast']) + 1;  
  $item = _ipcalc($base_ip,$mask_allocated);
  $increment = $item['hosts'] + 2;

//  print "\n<br />Going to check: ".$net_dec. ' up to '.$end_dec;
  $key = 0;
  $elem = count($nets_allocated);
  while ($net_dec < $end_dec) {
    // is there any ip allocated in the range net_dec-increment?
    $found = false; 
    $last  = $net_dec + $increment;
    $net = $nets_allocated[$key];
//    print "\n<br />Checking: ".$net_dec. ' ('._dec_to_ip($net_dec).') with '.$net['dec'].' ('._dec_to_ip($net['dec']).")\n";
    if (($net['dec'] >= $net_dec) and ($net['dec'] < $last) ) {
      $found = true;
      // if net's broadcast > checked range, jumps to it's broadcast
      $net_ends = _dec_addr($net['broadcast']);
      if ($net_ends > $last)
        $last = $net_ends + 1;          
    } 
      
    // There was space for the subnet here, search ends
    if (!$found)
      return _dec_to_ip($net_dec);

    // going to look next range
    $net_dec = $last;    
    while (($nets_allocated[$key]['dec'] < $net_dec) and ($key < $elem))
      $key++;
  }

  // No space available 
  return false;
  
}

/**
 * guifi_next_ip
 *  finds the next available ip within a subnet
 * @base_ip: 
 *   network base ip to look at
 * @netmask_range
 *   mask range to look at
 * @ips_allocated
 *   array with a list of current ip's, if ommited
 *   will take a look into the databass
 * 
 * @ @return 
 *   the next available IP or false if none available
*/
function guifi_next_ip($base_ip = '0.0.0.0', $mask_range = '0.0.0.0', $ips_allocated = null) {
  
  if ($ips_allocated == null) {
    $ips_allocated = guifi_get_ips($base_ip,$mask_range);
  }

  $ip_dec = _dec_addr($base_ip) + 1; 
  $item = _ipcalc($base_ip,$mask_range);
  $end_dec = _dec_addr($item['broadcast']) + 1;  

  $key = 0;
  $elem = count($ips_allocated);

  // Shift array until reaches base ip
  while (($ips_allocated[$key]['dec'] < $ip_dec) and ($key < $elem)) {
    $key++;
  }
  while (($ips_allocated[$key]['dec'] == $ip_dec) and ($key < $elem) and ($ips_allocated[$key]['dec'] < $end_dec ) ) {
    $key++;
    $ip_dec++;
  }

  if ($ip_dec < $end_dec-1)
    return _dec_to_ip($ip_dec);

  drupal_set_message(t('Fatal error: Network %net/%mask is full',array('%net' => theme('placeholder', $base_ip), '%mask' => theme('placeholder', $mask_range)  )),'error');
  return false;
}

function guifi_get_subnet_by_nid($nid,$mask_allocate = '255.255.255.224', $network_type = 'public',$ips_allocated = null) {

  // print "Going to allocate network ".$mask_allocate."-".$network_type;

  global $user;

  $zone_fetch = db_fetch_object(db_query("SELECT l.zone_id id, z.master FROM {guifi_location} l LEFT JOIN {guifi_zone} z ON l.zone_id=z.id WHERE l.id=%d",$nid));
  $zone = guifi_get_zone($zone_fetch->id);
  $rzone = $zone;

  $depth = 0;
  $root_zone = $zone->id;
  do {
    $result = db_query('SELECT n.id, n.base, n.mask FROM {guifi_networks} n WHERE n.valid = 1 AND n.zone = "%s" AND network_type="%s" ORDER BY n.id',$zone->id,$network_type);

    // if there are already networks defined, increase network mask, up to /20 level
    // here, getting the total # of nets defined
    if ($zone->id == $rzone->id)
      $tnets = db_num_rows($result);

    while ($net = db_fetch_object($result)) {
      //  print "Going to find a slot ".$mask_allocate." at: ".$net->base."/".$net->mask."\n<br />";
      $item = _ipcalc($net->base,$net->mask);
      if ($ip = guifi_find_subnet($net->base, $net->mask, $mask_allocate, $ips_allocated)) {
        if ($depth) {
          user_mail(variable_get('guifi_contact','netadmin@guifi.net'),t('guifi: Network allocated for ').$rzone->title,
                    strip_tags(t('A new network (%base / %mask) has been allocated for zone %name, got from %name2 by %user.', 
                          array('%base' => theme('placeholder', $ip),
                                '%mask' => theme('placeholder', $mask_allocate),
                                '%name' => theme('placeholder', $rzone->title),
                                '%name2' => theme('placeholder', $zone->title),
                                '%user' => theme('placeholder', $user->name)
                               ))
                    ),'From: webmestre@guifi.net');
          if ($network_type == 'public')
            db_query("INSERT INTO {guifi_networks} (base, mask, zone, network_type, user_created, timestamp_created, valid) VALUES ('%s', '%s', %d, '%s', %d, %d, 1)", $ip, $mask_allocate, $root_zone, $network_type, $user->uid, time());
        }
//         print "IP found: $ip[ipv4]";
        return $ip; 
      }
    }

    // Network was not allocated

    // already allocated networks should be considered as allocated ip
    if ($root_zone == $zone->id ) {
      $query = db_query('SELECT base ipv4 FROM {guifi_networks}');
      while ($nip = db_fetch_array($query)) {
        $nip[ipv4] = guifi_ip_op($nip[ipv4],1);
        $nip[netmask] = $mask_allocate;
        guifi_merge_ip($nip, $ips_allocated,true);
      }
    }

    // calculating the needed mask
    if ($network_type == 'public') {
      $depth++;

      if (($tnets > 0) and ($tnets < 5))
        // between 1 and 4, 24 - nets defined
        $maskbits = 24 - $tnets;
      else if ($tnets >= 5)
        // greater than 4, /20 - 255.255.240.0
        $maskbits = 20;
      else
        // first net, /24 - 255.255.255.0
        $maskbits = 24;

      $mitem = _ipcalc_by_netbits($net->base,$maskbits);
      $mask_allocate = $mitem[netmask];

//      if ($depth == 1) 
//       $mask_allocate = '255.255.254.0';
//      else
//       $mask_allocate = '255.255.252.0';
    }

    // Take a look at the parent network zones
    $master = $zone->master;
    if ( $zone->master > 0)
      $zone = guifi_get_zone($zone->master);
  } while ( $master  > 0);

  return false;
}

function guifi_ip_op($ip, $op = 1) {
  return _dec_to_ip(_dec_addr($ip) + $op);
}

function guifi_get_interface($ipv4) {
  $if = db_fetch_object(db_query("SELECT i.*,a.ipv4, a.netmask FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id WHERE ipv4='%s'",$ipv4));
  if (!empty($if))
    return $if;
  else
    return 0;
}

function guifi_get_existent_interface($device_id, $interface_type) {
  if (preg_match('(wds|vlan|vwan|vwlan)',$interface_type))
    return 0;

  $if = db_fetch_object(db_query("SELECT * FROM {guifi_interfaces} WHERE device_id=%d AND interface_type='%s'",$device_id,$interface_type));
  if (!empty($if))
    return $if;
  else
    return 0;
}

function guifi_ip_type($itype1, $itype2) {

  $guifi_ipconf = array(
     'wLan/Lan'=>array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan|Lan|wLan\/Lan/','ntype' => 'public'),
     'Lan'=>     array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan|Lan|wLan\/Lan/','ntype' => 'public'),
     'wLan'=>    array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan|Lan|wLan\/Lan/','ntype' => 'public'),
     'Wan'=>     array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan'=>    array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan'=>    array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vwlan'=>   array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vwan'=>    array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan1'=>   array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan2'=>   array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan3'=>   array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'vlan4'=>   array('preg'=>'/vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Wan/',              'ntype' => 'backbone'),
     'wds/p2p'=> array('preg'=>'/wds\/p2p/',                                                 'ntype' => 'backbone'),
     'tunnel'=>  array('preg'=>'/tunnel/',                                                   'ntype' => 'backbone')
     ); // eof variable_get

  if ((empty($itype1)) or (empty($itype2)))
   return false;

  // if found, return network type for this interface configuration
  if (preg_match($guifi_ipconf[$itype1]['preg'],$itype2))
    return $guifi_ipconf[$itype1]['ntype'];
  if (preg_match($guifi_ipconf[$itype2]['preg'],$itype1))
    return $guifi_ipconf[$itype2]['ntype'];

  // not supported configuration, don't know how to assign ip address
  return false;

}


function guifi_rename_graphs($old, $new) {

  $ext = array ('_6.rrd','_ping.rrd');

  $fold = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($old);
  $fnew = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($new);

  foreach ($ext as $fext) {
//    print "Going to rename ".$fold.$fext." to ".$fnew.$fext."\n<br />";
    if (file_exists($fold.$fext)) {
      rename($fold.$fext,$fnew.$fext);
//      print $fold.$fext." renamed to ".$fnew.$fext."\n<br />";
    }
  }
}

function guifi_set_node_flag($id) {

  $status = 'Planned';
  $query = db_query("SELECT d.id, d.flag FROM {guifi_devices} d WHERE d.nid = %d",$id);
  if (db_num_rows($query) > 0) 
  while ($device = db_fetch_object($query)) {
    if ($status != 'Working') 
      $status = $device->flag;
  } // eof while devices
  db_query("UPDATE {guifi_location} SET status_flag = '%s' WHERE id = %d",$status,$id);   
}


define('GUIFILOG_NONE',0);
define('GUIFILOG_BASIC',1);
define('GUIFILOG_TRACE',2);
define('GUIFILOG_FULL',3);

function guifi_log($level, $var, $var2 = null) {
  global $user;

  if ($level > variable_get('guifi_loglevel',GUIFILOG_NONE))
    return;

  $output = $var;
  if ($var2 != null) 
  if (gettype($var2) != 'string') {
    $output .= ": ".print_r($var2,true); 
//    $output .= ": ".serialize($var2); 
  } else {
    $output .= ": ".$var2; 
  }

  $filename = variable_get('guifi_log','/tmp/guifi.log');
  if (is_writable($filename)) {
    if (!$handle = fopen($filename, 'a+')) {
      watchdog('guifi',"Cannot open log file (".$filename.")\n",WATCHDOG_ERROR);
      return;
    }
    if (fwrite($handle, variable_get('site_name','guifi')." ".date('d-m-Y H:i:s')." (".$user->name."): ".$output."\n") === FALSE) {
      watchdog('guifi',"Cannot write to log file (".$filename.")\n",WATCHDOG_ERROR);
      return;
    }
    fclose($handle);
  } else {
    watchdog('guifi',"The file ".$filename." is not writable.\n",WATCHDOG_ERROR);
  }
}

function guifi_to_7bits($str) {
 $str = str_replace(array('Ã ','Ã¡','Ã¤','Ã?','Ã','Ã?'),'a',$str);
 $str = str_replace(array('Ã¨','Ã©','Ã«','Ã?','Ã?','Ã?'),'e',$str);
 $str = str_replace(array('Ã¬','Ã­','Ã¯','Ã','Ã?','Ã'),'i',$str);
 $str = str_replace(array('Ã²','Ã³','Ã¶','Ã?','Ã?','Ã?'),'o',$str);
 $str = str_replace(array('Ã¹','Ãº','Ã¼','Ã?','Ã?','Ã?'),'u',$str);
 $str = str_replace(array('Ã±','Ã?'),'n',$str);
 $str = str_replace(array('Ã§','Ã?'),'c',$str);

 return $str;
}

function guifi_zone_childs_recurse($id, $childs, $children) {
  if ($children[$id]) {
    foreach ($children[$id] as $foo => $zone) {
        $childs[$zone->id] = $zone->title;
        $childs = guifi_zone_childs_recurse($zone->id, $childs, $children);
    }
  }
  

  return $childs;
}

function guifi_zone_childs($zid) {
  $result = db_query('SELECT id, master, title FROM {guifi_zone} ORDER BY id');

  $childs = array();

  while ($zone = db_fetch_object($result)) {
    if (!$children[$zone->master]) {
      $children[$zone->master] = array();
    }
    array_push($children[$zone->master], $zone);
  }

  $childs = guifi_zone_childs_recurse($zid, $childs, $children);
  
  return $childs;
}

function guifi_zone_tree_recurse($zid, $children) {
  $childs = array();
  if ($children[$zid]) {
    foreach ($children[$zid] as $id => $zone) {
      $childs[$id] = $zone;
      $childs[$id]->childs = guifi_zone_tree_recurse($id,$children);
    }
  }
  
  return $childs;
}

function guifi_nodexchange_tree($zid) {
  $result = db_query('SELECT id, master, title FROM {guifi_zone} ORDER BY title');
  while ($zone = db_fetch_object($result)) {
    $zones[$zone->id] = $zone;
  }
  $result = db_query('SELECT * FROM {guifi_location}');
  while ($node = db_fetch_object($result)) {
    $zones[$node->zone_id]->nodes[] = $node;
  }


  $childs = array();

  foreach ($zones as $zoneid=>$zone) {
    if (!$children[$zone->master]) {
      $children[$zone->master][$zoneid] = $zone;
    }
    $children[$zone->master][$zoneid] = $zone;
    if ($zoneid == $zid)
      $childs[$zid] = $zone;
  }

  $childs[$zid]->childs = guifi_zone_tree_recurse($zid,$children);

  return $childs;
}

function guifi_cnml_tree($zid) {
  $result = db_query('SELECT z.id, z.master parent_id, z.title, n.body, z.time_zone, z.ntp_servers, z.dns_servers, z.graph_server, z.homepage, z.minx, z.miny, z.maxx, z.maxy,z.timestamp_created, z.timestamp_changed FROM {guifi_zone} z, {node} n WHERE z.id=n.nid ORDER BY z.title');
  while ($zone = db_fetch_object($result)) {
    $zones[$zone->id] = $zone;
  }
  $result = db_query('SELECT l.*,n.body FROM {guifi_location} l, {node} n WHERE l.id=n.nid ORDER BY l.nick');
  while ($node = db_fetch_object($result)) {
    $zones[$node->zone_id]->nodes[] = $node;
  }


  $childs = array();

  foreach ($zones as $zoneid=>$zone) {
    if (!$children[$zone->parent_id]) {
      $children[$zone->parent_id][$zoneid] = $zone;
    }
    $children[$zone->parent_id][$zoneid] = $zone;
    if ($zoneid == $zid)
      $childs[$zid] = $zone;
  }

  $childs[$zid]->childs = guifi_zone_tree_recurse($zid,$children);

  return $childs;
}


function guifi_form_hidden($var_txt,$var) {
  if (is_array($var)) 
    foreach ($var as $key=>$value)
      $ret .= guifi_form_hidden($var_txt.'['.$key.']',$value);
  else
    $ret .= "<input type=\"hidden\" name=\"edit".$var_txt."\" value=\"".$var."\" />\n";
  return $ret;
}

function guifi_count_radio_links($radio) {

  $ret[ap]=0;
  $ret[wds]=0;
//  print_r($radio);

  if (is_numeric($radio)) {
    $qc = db_query('SELECT l1.link_type type,count(*) c FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id = l2.id WHERE l1.device_id=%d AND l2.device_id != %d GROUP BY l1.link_type',$radio,$radio);
    while ($c = db_fetch_object($qc)) {
      switch ($c->type) {
      case 'ap/client': $ret[ap]++; break;
      case 'wds/p2p': $ret[wds]++; break;
      }
    }
  } else {
    if (isset($radio[interfaces])) foreach ($radio[interfaces] as $ki=>$interface)
    if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ka=>$ipv4)
    if (isset($ipv4[links])) foreach ($ipv4[links] as $kl=>$link) 
    if (!$link[deleted]) {
      if ($link[link_type] = 'wds/p2p')
        $ret[wds]++;
      if ($link[link_type] = 'ap/client')
        $ret[ap]++;
    }
  }

  return $ret;
}

function guifi_next_interface($edit = null) {
   $next = 0;
   $int = db_fetch_object(db_query('SELECT max(id)+1 id FROM {guifi_interfaces}'));
   $next=$int->id;

   if (isset($edit))
     $next = _interface_recurse($edit,$next);

   return $next;
}

function _interface_recurse($var,$next = 0) {

  foreach ($var as $k=>$value) {
    if ($k == 'interfaces') foreach ($value as $k1=>$value1) {
      if ($k1 >= $next)
        $next = $k1 + 1;
      if (is_array($value1))
        $next = _interface_recurse($value1,$next);
    }

    if ($k == 'interface') foreach ($value as $k1=>$value1) {
      if (is_numeric(isset($value1[id])))
      if ($$value1[id] >= $next)
        $next = $value1[id] + 1;
      if (is_array($value1))
        $next = _interface_recurse($value1,$next);
    }

    if (is_array($value))
      $next = _interface_recurse($value,$next);
  }

  return $next;
}

function guifi_array_combine($arr1, $arr2) {
  reset($arr2);
  reset($arr1);
  unset($result);
  $result = array();
  if ((count($arr1) == count($arr2)) and count($arr1)) {
    foreach ($arr1 as $key=>$kvalue) {
     $result[$kvalue] = current($arr2);
     next($arr2); 
    }
  }
  return $result;
}

function guifi_refresh($parameter) {
  echo variable_get('guifi_refresh_'.$parameter,time());
  exit;
}

?>
