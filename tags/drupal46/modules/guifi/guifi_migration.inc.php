<?php
function guifi_upgrade() {

  function _node_insert($title, $type, $body) {
    $id = db_next_id('node_nid');
    db_query("INSERT INTO {node} (nid, title, type, teaser, body, uid, status, created, changed, comment, promote, moderate) VALUES ('%d','%s','%s','%s','%s','%d','%d','%d','%d','%d','%d','%d')",$id,$title,$type,$teaser,$body,1,1,time(),time(),2,0,0);
    return $id;
  }
  
  function _zone_insert($name,$master) {
    $body = NULL;
    $id = _node_insert($name, 'guifi-zone', $body);
    switch ($name) {
    case 'Vic': $nick = 'Vic'; break;
    case 'Sant Hipòlit de Voltregà': $nick = 'SHV'; break;
    case 'Gurb': $nick = 'Gurb'; break;
    case 'Sant Bartomeu del Grau': $nick = 'SBG'; break;
    case 'Masies de Voltregà': $nick = 'MdV'; break;
    case 'Masies de Roda': $nick = 'MdR'; break;
    case 'Tona': $nick = 'Tona'; break;
    case 'Manlleu': $nick = 'MNLL'; break;
    case 'Torelló': $nick = 'TORLL'; break;
    case 'Barcelona': $nick = 'BCN'; break;
    case 'Taradell': $nick = 'TRDLL'; break;
    case 'Santa Eugènia de Berga': $nick = 'SEB'; break;
    case 'Calldetenes': $nick = 'CALLD'; break;
    case 'Sant Julià de Vilatorta': $nick = 'SJV'; break;
    case 'Tavèrnoles': $nick = 'TAVRN'; break;
    case 'Roda': $nick = 'Roda'; break;
    case 'Sant Martí de Sescorts': $nick = 'SMS'; break;
    case 'Hostalets de Balenyà': $nick = 'HdB'; break;
    case 'Seva': $nick = 'Seva'; break;
    case 'Folgueroles': $nick = 'FOLG'; break;
    case 'Malla': $nick = 'Malla'; break;
    case 'Espinelves': $nick = 'ESPN'; break;
    case 'Cantonigròs': $nick = 'CTONIG'; break;
    case 'Collsuspina': $nick = 'COLLSP'; break;
    case 'L\'Esquirol': $nick = 'ESQRL'; break;
    case 'Olost': $nick = 'Olost'; break;
    case 'Orís': $nick = 'Oris'; break;
    case 'Viladrau': $nick = 'VLDR'; break;
    }
    db_query("INSERT INTO {guifi_zone} (id, nick, title, body, master, time_zone, user_created, timestamp_created) VALUES ('%d','%s','%s','%s','%d','%s','%d','%d')",$id,$nick,$name,$body,$master,"+01 2 2",1,time());
    if ($master == 0) {
      db_query("INSERT INTO {guifi_networks} (base, mask, zone, user_created, timestamp_created) VALUES ('10.138.0.0','255.252.0.0',%d,1,%d)",$id, time());
      db_query("INSERT INTO {guifi_networks} (base, mask, network_type, zone, user_created, timestamp_created) VALUES ('172.25.0.0','255.255.0.0','backbone',%d,1,%d)",$id, time());
      db_query("INSERT INTO {url_alias} (src,dst) VALUES ('node/%d','guifi_zones')",$id);
      db_query("UPDATE {menu} SET path = 'guifi_zones' WHERE title = 'llistat de nodes'",$id);
      db_query("DELETE FROM {menu} WHERE path in ('wifi/links','wifi/radios')",$id);
      db_query("DELETE FROM {cache}",$id);
    }
    if ($name == 'Osona') {
      db_query("INSERT INTO {guifi_networks} (base, mask, zone, user_created, timestamp_created) VALUES ('10.138.0.0','255.255.192.0',%d,1,%d)",$id,time());
      db_query("INSERT INTO {guifi_networks} (base, mask, network_type, zone, user_created, timestamp_created) VALUES ('172.25.0.0','255.255.248.0','backbone',%d,1,%d)",$id,time());
    }
    return $id;
  }
  
  function _interface_insert($id, $did, $it, $ipv4 ='dhcp', $mask, $mac, $zoneid) {
    $ipdata= _ipcalc($ipv4, '255.255.255.0');
    $query = db_query("SELECT device_id FROM {guifi_interfaces} WHERE ipv4='%s'",$ipv4);
    if  (db_num_rows($query)) {
      $conflict =  db_fetch_object($query);
      print "IP conflict: ".$ipv4." is present at ".guifi_get_hostname($did)."(".$did.")and ".guifi_get_hostname($conflict->device_id)."(".$conflict->device_id.")\n<br />";  
      return false;
    }

    list($oct1, $oct2, $oct3, $oct4) = explode('.',$ipv4); 
    $querynet = db_query("SELECT * FROM {guifi_networks} WHERE base = '%s' AND mask = '255.255.255.0'",$ipdata['netid']);
    if (($zoneid) && ($oct1 == 10) && (db_num_rows($querynet) == 0)) {
      db_query("INSERT INTO {guifi_networks} (base, zone, user_created, timestamp_created) VALUES ('%s',%d,1,%d)",$ipdata['netid'],$zoneid,time());
    }
    if (empty($ipv4)) {
      db_query("INSERT INTO {guifi_interfaces} (id, device_id, interface_type, netmask, mac) VALUES (%d, %d, '%s', '%s', '%s')",$id,$did,$it,$mask,$mac);
    } else
      db_query("INSERT INTO {guifi_interfaces} (id, device_id, interface_type, ipv4, netmask, mac) VALUES (%d, %d, '%s', '%s', '%s', '%s')",$id,$did,$it,$ipv4,$mask,$mac);
  }
  
  print "Migrating the database...\n<br />";
  print "Truncating existing data...\n<br />";
   
  db_query("DELETE FROM {guifi_location}");
  db_query("DELETE FROM {guifi_zone}");
  db_query("DELETE FROM {guifi_networks}");
  db_query("DELETE FROM {node} WHERE type in ('guifi-zone','guifi-node','guifi-service')");


  print "Migrating zones...\n<br />";
  $master = _zone_insert('Catalunya',0);

  $zones_tree = taxonomy_get_tree(variable_get('wifi_vocabulary',0));
  $device_id = 0;
  $if = 0;
  foreach ($zones_tree as $term) {
    print $term->name ."\n<br />";
    if ($term->depth == 0) {
      $comarca = _zone_insert($term->name,$master);
    } else {
      $zoneid = _zone_insert($term->name,$comarca);
      $queryloc = db_query("SELECT l.*,n.uid uid,n.created created FROM {term_node} t, {wifi_location} l, {node} n WHERE state != 'Dropped' AND tid='%d' AND t.nid=l.nid AND t.nid=n.nid",$term->tid);
      while ($loc = db_fetch_array($queryloc)) {
        if ($loc["lat"] == 0)
          $loc["lat"] = NULL;
        if ($loc["lon"] == 0)
          $loc["lon"] = NULL;
        if ($loc["stable"] != 'Yes')
          $loc["stable"] = 'No';


        db_query("INSERT INTO {guifi_location} (id, nick, zone_id, zone_description, lat, lon, elevation, contact, status_flag, stable, user_created, user_changed, timestamp_created, timestamp_changed) VALUES ('%d','%s','%d','%s','%.9f','%.9f','%d','%s','%s','%s','%d','%d','%d','%d')",
  $loc["nid"],
  $loc["nick"],
        $zoneid,
        $loc["zone"],
  $loc["lat"],
  $loc["lon"],
  $loc["elevation"],
  $loc["sponsor"],
  $loc["state"],
  $loc["stable"],
  $loc["uid"],
        1,
  $loc["created"],
  time()
        );
        db_query("UPDATE {node} SET type = 'guifi-node' WHERE nid='%d'",$loc["nid"]);

        $query_radio = db_query("SELECT * FROM {wifi_radio} WHERE nid=%d AND state <> 'Dropped' AND mode <> ''",$loc["nid"]);
        while ($radio = db_fetch_array($query_radio)) {
//          $device_id = $device_id + 1;

          // If not in NATted client mode, Insert wLan interface
          if ($radio["mode"] != 'Client') {
            $if = $if + 1;
            $mac = _guifi_validate_mac($radio["mac"]);
            if ($mac == false)
              $mac = '';
            _interface_insert($if,$radio["rid"],'wLan/Lan',$radio["ip"],_singleclick_netmask_by_hosts($radio["hosts"]),$mac,$zoneid);
          }

          // Validate/Convert MACs
          $mac = _guifi_validate_mac($radio["int_mac"]);
          if (($mac == false)) {
            $mac = _guifi_validate_mac($radio["mac"]);
            if ($mac != false)
              $mac = _guifi_mac_sum($mac, -2);         
          }
          if ($mac == false)
            $mac = '';

          // Calculate Azimuth
          switch ($radio["antenna_orientation"]) {
            case 'N': $dAz = 0; break;
            case 'NE': $dAz = 45; break;
            case 'E': $dAz = 90; break;
            case 'SE': $dAz = 135; break;
            case 'S': $dAz = 180; break;
            case 'SW': $dAz = 225; break;
            case 'W': $dAz = 270; break;
            case 'NW': $dAz = 315; break;
            default: $dAz = 360; break;
          }

          // Validate/Convert Antenna
          switch ($radio["antenna_type"]) {
            case 'stock': 
              $angle = 0;
              break; 
            case 'directive': 
              $angle = 6;
              $queryant = db_query('SELECT * FROM {wifi_link} WHERE rid1 = %d OR rid2 = %d',$radio["rid"],$radio["rid"]);
              $link = db_fetch_array($queryant);
              if ($link["rid1"] == $radio["rid"])
                $rid2 = $link["rid2"];
              else
                $rid2 = $link["rid1"];
              $ridloc = db_query("SELECT l.*,ssid FROM {wifi_radio} r, {wifi_location} l WHERE r.rid=%d AND r.nid=l.nid",$rid2);
              $loc2 = db_fetch_array($ridloc);
              $oGC = new GeoCalc();
              $dAz = round($oGC->GCAzimuth($loc["lat"], $loc["lon"], $loc2["lat"], $loc2["lon"]));
              break; 
            case 'sector': 
              $angle = 120;
              break; 
            case 'patch': 
              $angle = 90;
              if ($radio["antenna_gain"] >=10)
                $angle = 60;
              if ($radio["antenna_gain"] >=12)
                $angle = 30;
              break; 
          }

          switch ($radio["mode"]) {
          case 'Client':
            $mode = 'client';  
            break;
          case 'Bridge':
            $mode = 'bridge';
            break;
          default:
            $mode = 'ap';
          }
          db_query("INSERT INTO {guifi_devices} (id, nid, nick, type, contact, flag, mac, comment, user_created, user_changed, timestamp_created, timestamp_changed) VALUES (%d, %d, '%s','%s','%s','%s','%s','%s', '%d','%d','%d','%d')",
          $radio["rid"],
          $loc["nid"],
          $radio["title"],
          'radio',
          $radio["sponsor"],
          $radio["state"],
          $mac,
          $radio["comments"],
          $loc["uid"],
          1,
          $radio["created"],
          time());

          switch ($radio['firmware']) {
           case 'Alchemy Sveasoft':
             $firmware = 'Alchemy'; break;
           case 'Talisman Sveasoft':
             $firmware = 'Talisman'; break;
           default:
             $firmware = 'n/a'; break;
          }
          

          db_query("INSERT INTO {guifi_radios} (id, nid, model_id, ssid, mode, protocol, firmware, antenna_angle, antenna_gain, antenna_azimuth, channel) VALUES (%d, %d, %d, '%s', '%s','%s','%s','%s','%s','%s','%s')",
          $radio["rid"],
          $loc["nid"],
          $radio["mid"],
          $radio["ssid"],
          $mode,
          $radio["protocol"],
          $firmware,
          $angle,
          $radio["antenna_gain"],
          $dAz,
          $radio["channel"]);
   
        }
      }
    }
  }        

  // Migrating links
  print "\n<br />Migrating links...\n<br />";

  $query= db_query("SELECT r1.mac mac1, r1.mode mode1, r1.ip ipr1, r1.hosts hosts1, r2.mac mac2, r2.mode mode2, r2.ip ipr2, r2.hosts hosts2, r1.nid nid1, r2.nid nid2, l.* FROM {wifi_link} l, {wifi_radio} r1, {wifi_radio} r2 WHERE l.state != 'Dropped' AND l.rid1=r1.rid AND l.rid2=r2.rid ORDER BY l.cid");
  while ($link = db_fetch_array($query)) {
    $if = $if+1; $if1 = $if;
    $mac1 = '';
    $mac2 = '';
    unset($itype);
    switch ($link["link_type"]) {
      case 'WDS':
        $mac1 = _guifi_validate_mac($link["mac1"]);
        $mac2 = _guifi_validate_mac($link["mac2"]);
        $itype = 'wds/p2p';
      case 'Bridge':
        if (!isset($itype))
          $itype = 'Lan';
      case 'Cable - vlan2':
        if (!isset($itype))
          $itype = 'vlan2';
      case 'Cable - vlan3':
        if (!isset($itype))
          $itype = 'vlan3';
      case 'Cable - vlan4':
        if (!isset($itype))
          $itype = 'vlan4';
      case 'tunnel':
        if (!isset($itype))
          $itype = 'tunnel';
        $if = $if+1; $if2 = $if;
        $words = str_word_count($link["link_type"],1);
        $ltype = $words[0];
        _interface_insert($if1,$link["rid1"],$itype,$link["ip1"],_singleclick_netmask_by_hosts($link["hosts"]),$mac1,NULL);
        _interface_insert($if2,$link["rid2"],$itype,$link["ip2"],_singleclick_netmask_by_hosts($link["hosts"]),$mac2,NULL);
        break;
      case 'AP/Client':
        if ($link["mode2"] == 'Client') {
          $ip = $link["ipr2"];
          $rid = $link["rid2"];
          $rid2 = $link["rid1"];
          $mac = _guifi_validate_mac($link["mac2"]);
          $netmask = _singleclick_netmask_by_hosts($link["hosts2"]);
        } else {
          $ip = $link["ipr1"];
          $rid = $link["rid1"];
          $rid2 = $link["rid2"];
          $mac = _guifi_validate_mac($link["mac1"]);
          $netmask = _singleclick_netmask_by_hosts($link["hosts1"]);
        }
        _interface_insert($if1,$rid,'Wan',$ip,$netmask,$mac,NULL);
        
        $queryap = db_query("SELECT id FROM {guifi_interfaces} WHERE device_id=%d AND interface_type = 'wLan/Lan'",$rid2);
        $ap = db_fetch_array($queryap);
        if ($link["mode2"] == 'Client') {
          $if2 = $if1;
          $if1 = $ap["id"];
        } else 
          $if2 = $ap["id"];

        $ltype = 'AP/Client';
    }
    $ltype = strtolower($ltype);
    db_query("INSERT INTO {guifi_links} (id, nid, device_id, interface_id, flag, link_type) VALUES (%d, %d, %d, %d, '%s', '%s')", $link["cid"],$link["nid1"],$link["rid1"],$if1,$link["state"],$ltype);
    db_query("INSERT INTO {guifi_links} (id, nid, device_id, interface_id, flag, link_type) VALUES (%d, %d, %d, %d, '%s', '%s')", $link["cid"],$link["nid2"],$link["rid2"],$if2,$link["state"],$ltype);
  }

  $iid = db_fetch_object(db_query("SELECT max(id) max FROM {guifi_interfaces}"));
  db_query("INSERT INTO {sequences} VALUES ('guifi_interface_id',%d)",$iid->max + 1);
  db_query("UPDATE {sequences} SET name='guifi_link_id' WHERE name='wifi_link_id'");
  db_query("UPDATE {sequences} SET name='guifi_device_id' WHERE name='wifi_radio_id'");

  print "\nDatabase migrated.";
}

?>
