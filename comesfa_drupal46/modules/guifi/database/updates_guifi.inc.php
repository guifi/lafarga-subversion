<?php
/* $Id: updates.inc.php, 2006/04/01 19:43:04 Exp $ */

/**
 * @file
 * All incremental database updates performed between guifi releases.
 */

// Define the various updates in an array("SVN commit version" => "function");
$sql_updates = array(
 343 => 'guifiupdate_01',
 352 => 'guifiupdate_02',
 353 => 'guifiupdate_03',
 354 => 'guifiupdate_04',
 356 => 'guifiupdate_05'
);


function guifi_cleandb() {

  // Cleaning orphan devices
  $qd = db_query('SELECT d.id, d.nid FROM {guifi_devices} d');
  while ($d = db_fetch_object($qd)) {
    $r = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_location} WHERE id=%d',$d->nid));
    if ($r->c == 0) {
      db_query(sprintf("DELETE FROM {guifi_interfaces} WHERE device_id = %d",$d->id));
      db_query(sprintf("DELETE FROM {guifi_devices} WHERE id = %d",$d->id));
      db_query(sprintf("DELETE FROM {guifi_links} WHERE device_id = %d",$d->id));
    }
  }


  // Cleaning orphan interfaces
  $qi = db_query('SELECT i.device_id,i.id FROM {guifi_interfaces} i');
  while ($i = db_fetch_object($qi)) {
    // orhpan from device?
    $r = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_devices} WHERE id = %d',$i->device_id));
    if ($r->c == 0) 
      db_query(sprintf("DELETE FROM {guifi_interfaces} WHERE id = %d",$i->id));
  }

  // Cleaning orphan ipv4
  $qi = db_query('SELECT i.interface_id FROM {guifi_ipv4} i');
  while ($i = db_fetch_object($qi)) {
    // orhpan from interface?
    $r = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_interfaces} WHERE id = %d',$i->interface_id));
    if ($r->c == 0) 
      db_query(sprintf("DELETE FROM {guifi_ipv4} WHERE interface_id = %d",$i->id));
  }

  // Cleaning orphan links
  $ql = db_query('SELECT l.id, count(*) c FROM {guifi_links} l GROUP BY l.id HAVING count(*) != 2');
  while ($l = db_fetch_object($ql)) {
      db_query(sprintf("DELETE FROM {guifi_links} WHERE id = %d",$l->id));
  }
  $ql = db_query('SELECT l.id, l.nid,l.device_id,l.interface_id FROM {guifi_links} l');
  while ($l = db_fetch_object($ql)) {
    $rn = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_location} WHERE id=%d',$l->nid));
    $rd = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_devices} WHERE id=%d',$l->device_id));
    $ri = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_interfaces} WHERE id=%d',$l->interface_id));
    $ra = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',$l->ipv4_id,$l->interface_id));
    if (($rn->c == 0) or ($rd->c == 0) or ($ri->c == 0) or ($ra->c == 0)) {
      db_query(sprintf("DELETE FROM {guifi_ipv4} WHERE id = %d AND interface_id=%d",$l->ipv4_id,$l->interface_id));
      db_query(sprintf("DELETE FROM {guifi_interfaces} WHERE device_id = %d",$l->device_id));
      db_query(sprintf("DELETE FROM {guifi_devices} WHERE id = %d",$l->device_id));
      db_query(sprintf("DELETE FROM {guifi_links} WHERE id = %d",$l->id));
    }
  }

  // delete WDS interfaces with no known link from ipv4 (orphan addresses)
  $qi = db_query('SELECT i.id interface_id, a.id ipv4_id FROM {guifi_interfaces} i LEFT JOIN {guifi_ipv4} a ON a.interface_id=i.id WHERE i.interface_type="wds/p2p"');
  while ($i = db_fetch_object($qi)) {
    $l = db_fetch_object(db_query('SELECT count(*) c FROM {guifi_links} WHERE interface_id=%d AND ipv4_id=%d',$i->interface_id,$i->ipv4_id));
    if ($l->c == 0) {
      db_query(sprintf("DELETE FROM {guifi_ipv4} WHERE id = %d AND interface_id=%d",$i->ipv4_id,$i->interface_id));
    }
  }
  

}

function guifiupdate_01() {

  $ret = array();
  $ret[] = update_sql("TRUNCATE TABLE {cache}");
//  guifi_cleandb();
  

  // Setting radiodev_counter
  $ql = db_query('SELECT * FROM {guifi_links} WHERE interface_id = %d',$i->id);
  while ($link = db_fetch_object($ql)) {
    if (($link->link_type == 'ap/client') or ($link->link_type == 'wds')) 
      $ret[] = update_sql(sprintf("UPDATE {guifi_interfaces} SET radiodev_counter = 0 WHERE id = %d",$i->id));
  }
  
  $ret[] = update_sql("INSERT INTO `guifi_manufacturer` VALUES (9, 'Buffalo', 'http://www.buffalotech.com');");
  $ret[] = update_sql("INSERT INTO `guifi_model` VALUES (15, 9, 'WHR-HP-G54, WHR-G54S', 'Extern', 0, 251, '802.11b/g', 'Si', 'Hack', 'Hack', 'Hack', 'RP-SMA', '1', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'http://www.buffalo-technology.com/products/product-detail.php?productid=124&categoryid=28', 'El canvi de potència, mode client i WDS, via hack.');");
  $ret[] = update_sql("UPDATE `guifi_types` SET relations='WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2|WHR-HP-G54, WHR-G54S' WHERE type='firmware' AND text LIKE 'DD-%'");
  $ret[] = update_sql("CREATE TABLE `guifi_ipv4` (
                       `id` int(11) NOT NULL,
                       `interface_id` int(11) NOT NULL default '0',
                       `ipv4` varchar(16) default NULL,
                       `netmask` varchar(16) NOT NULL default '255.255.255.0',
                       PRIMARY KEY  (`interface_id`,`id`),
                       UNIQUE KEY `ipv4` (`ipv4`)
                     )");
  $qi = db_query('SELECT * FROM {guifi_interfaces}');
  while ($i = db_fetch_object($qi)) {
     if ($i->ipv4 != null)
       db_query('INSERT INTO {guifi_ipv4} (id,interface_id,ipv4,netmask) VALUES (%d,%d,"%s","%s")',0,$i->id,$i->ipv4,$i->netmask);
     else
       db_query('INSERT INTO {guifi_ipv4} (id,interface_id,netmask) VALUES (%d,%d,"%s")',0,$i->id,$i->netmask);
  }
  $ret[] = update_sql("ALTER TABLE `guifi_interfaces` DROP `ipv4`, DROP `netmask`;");
  return $ret;
}

function guifiupdate_02() {
  $ret = array();
  $ret[] = update_sql("TRUNCATE TABLE {cache}");

  $ret[] = update_sql("DELETE FROM {guifi_types} WHERE text='channel'");

  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','0','Auto 2.4GHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','1','1.- 2412 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','2','2-. 2417 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','3','3.- 2422 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','4','4.- 2422 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5','5.- 2432 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','6','6.- 2437 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','7','7.- 2442 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','8','8.- 2447 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','9','9.- 2452 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','10','10.- 2457 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','11','11.- 2462 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','12','12.- 2467 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','13','13.- 2472 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','14','14.- 2477 MHz','802.11b|802.11g|802.11abg|802.11bg|802.11g+|802.11n');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5000','Auto 5GHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5180','1.- 5180 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5200','2.- 5200 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5220','3.- 5220 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5240','4.- 5240 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5260','5.- 5260 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5280','6.- 5280 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5300','7.- 5300 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5320','8.- 5320 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5500','9.- 5500 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5520','10.- 5520 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5540','11.- 5540 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5560','12.- 5560 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5580','13.- 5580 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5600','14.- 5600 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5620','15.- 5620 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5640','16.- 5640 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5660','17.- 5660 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5680','18.- 5680 MHz','802.11a|802.11abg');");
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel','5700','19.- 5700 MHz','802.11a|802.11abg');");

  return $ret;
}

function guifiupdate_03() {
  $ret = array();

  $qr = db_query('SELECT id, model_id, firmware FROM {guifi_radios}');
  while ($radio = db_fetch_object($qr)) {
    $variable['model_id'] = $radio->model_id;
    $variable['firmware'] = $radio->firmware;
    db_query('UPDATE {guifi_devices} SET extra = "%s" WHERE id=%d',serialize($variable),$radio->id);
  }
  $ret[] = update_sql("ALTER TABLE `guifi_radios` DROP `firmware`;");
  
  // Setting radiodev_counter
  $ql = db_query('SELECT * FROM {guifi_links}');
  while ($link = db_fetch_object($ql)) {
    if (($link->link_type == 'ap/client') or ($link->link_type == 'wds')) 
      db_query(sprintf("UPDATE {guifi_interfaces} SET radiodev_counter = 0 WHERE id = %d",$link->interface_id));
  }

  // collapsing WDS wireless interfaces
  $ret[] = update_sql('ALTER TABLE `guifi_links` ADD `ipv4_id` INT( 11 ) NOT NULL AFTER `interface_id`');
  $qi = db_query('SELECT device_id, count(*), min(id) id FROM {guifi_interfaces} WHERE radiodev_counter=0 AND interface_type = "wds/p2p" GROUP BY device_id HAVING count(*) > 1');
  while ($i = db_fetch_object($qi)) {
    $qdi = db_query('SELECT id, device_id FROM {guifi_interfaces} WHERE device_id=%d AND interface_type = "wds/p2p"',$i->device_id);
    $c = 0;
    while ($di = db_fetch_object($qdi)) if ($di->id != $i->id) {
      $c++;
      db_query('UPDATE {guifi_ipv4} SET interface_id = %d, id = %d  WHERE interface_id=%d',$i->id,$c,$di->id);
      db_query('UPDATE {guifi_links} SET interface_id = %d, ipv4_id = %d WHERE interface_id=%d',$i->id,$c,$di->id);
      db_query('DELETE FROM {guifi_interfaces} WHERE id=%d',$di->id);
    } 
  }
  $ret[] = update_sql('UPDATE guifi_interfaces SET radiodev_counter=0 WHERE interface_type="wLan/Lan"');
  $ret[] = update_sql('UPDATE guifi_model SET radiodev_max=1 WHERE radiodev_max=0');
  $ret[] = update_sql('ALTER TABLE `guifi_model` ADD `interfaces` VARCHAR( 240 ) NOT NULL DEFAULT "wLan/Lan" AFTER `hack` ');
  $ret[] = update_sql('UPDATE guifi_model SET interfaces = "wLan/Lan|vlan|vlan2|vlan3|vlan4|Wan" WHERE mid in (1,15,16,17,18)');
  $ret[] = update_sql('UPDATE guifi_model SET interfaces = "wLan/Lan|ether2|ether3|ether4|ether5|ether6|ether7|ether8|ether9" WHERE mid in (19)');
  $ret[] = update_sql("INSERT INTO `guifi_types` (`type` , `text` , `description` ) VALUES ( 'service', 'teamspeak', 'TeamSpeak Server - Voice conference')");
  $ret[] = update_sql("ALTER TABLE `guifi_radios` DROP PRIMARY KEY, ADD PRIMARY KEY( `id`, `radiodev_counter`)");

  guifi_cleandb();
  $ret[] = update_sql("TRUNCATE TABLE {cache}");

  return $ret;
}

function guifiupdate_04() {
  $ret[] = update_sql("INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'games',    'Generic games server');");
  $ret[] = update_sql("ALTER TABLE `guifi_model` ADD `supported` ENUM( 'Yes', 'No', 'Deprecated' ) NOT NULL DEFAULT 'Yes';");
  $ret[] = update_sql("INSERT INTO `guifi_manufacturer` VALUES (0,'Other', null);");
  $ret[] = update_sql("INSERT INTO `guifi_model` VALUES (0, 0, 'Other not listed', NULL, 1, NULL, '802.11a/b/g', 'Si', 'Si', 'Si', 'Si', NULL, NULL, 'Si', 'Si', NULL, NULL, NULL, 'wLan/Lan', NULL, 'To be used for unknown or not listed devices', 'Yes');");
  $ret[] = update_sql("UPDATE guifi_model SET supported='Deprecated' WHERE model IN ('USR5450','USR8054','DWL-2000AP+','DWL-2100AP','Prestige 650W','C54APT','USR5410','C54c','C54i','WAP11','Prestige 650HW-31E','WAP54G');");
  $ret[] = update_sql("ALTER TABLE `guifi_radios` ADD `clients_accepted` ENUM( 'Yes', 'No' ) NOT NULL DEFAULT 'Yes';");
  $ret[] = update_sql("UPDATE guifi_radios SET clients_accepted='No' WHERE protocol IN (NULL,'802.11abg','legacy','802.11a')");
  $ret[] = update_sql("UPDATE guifi_radios SET clients_accepted='No' WHERE mode IN ('client','bridge','NAT Client')");
//  $ret[] = update_sql("");
  $ret[] = update_sql("TRUNCATE TABLE {cache}");

  return $ret;
}

function guifiupdate_05() {
  $ret = array();

  $ret[] = update_sql("UPDATE guifi_types SET description='Routed client', text='routedclient' WHERE text='NAT Client'");
  $ret[] = update_sql("TRUNCATE TABLE {cache}");
  return $ret;
}

?>
