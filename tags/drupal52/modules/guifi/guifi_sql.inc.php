<?php
// $Id: guifi.module x$

/**
 * @file guifi_sql.inc.php
 * Manage the SQL statements against the guifi.net schema
 **/
/** _guifi_db_sql(): UPSERT (SQL insert or update) on
 * node, device, radios, interfaces, ipv4, links...
 **/
function _guifi_db_sql($table, $key, $data, &$log = null, &$to_mail = array()) {
  global $user;

  $insert = false;

  guifi_log(GUIFILOG_TRACE,'guifi_sql',$table);
  // delete?
  if ($data['deleted']) {
    $log .= _guifi_db_delete($table,$key,$to_mail);
    return $log;
  }

  // insert?
  if ($data['new'])
    $insert = true;

  $qc = db_query('SHOW COLUMNS FROM '.$table);
  while ($column = db_fetch_object($qc))
    $columns[] = $column->Field;
    
  // cleanup columns which doesn't exists into the database
  foreach ($columns as $cname) {
    if (isset($data[$cname]))
      $sqldata[$cname] = $data[$cname]; 
  }
  $data = $sqldata;

  // processing insert triggers to fill new ids etc...
  if ($insert) 
  switch ($table) {
  case 'guifi_devices':
      $next_id = db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_devices}'));
      if (is_null($next_id['id']))
        $next_id['id'] = 1;
      $data['id'] = $next_id['id'];
  case 'guifi_zone':
  case 'guifi_location':
      $data['user_created'] = $user->id;
      $data['timestamp_created'] = time();
    break;
//  case 'guifi_radios':
//      // radio id already comes (device exists), looking for next radio id  at this device  (radiodev_counter)
//      $next_id = db_fetch_array(db_query('SELECT max(radiodev_counter)+1 id FROM {guifi_radios} WHERE id=%d',$data['id']));
//      if (is_null($next_id['id']))
//        $next_id['id'] = 0;
//      $data['radiodev_counter']=$next_id['id'];
//    break;
  case 'guifi_interfaces':
      $new_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_interfaces}'));
      $data['id']=$new_id['id'];
    break;
  case 'guifi_ipv4':
      $next_id = db_fetch_array(db_query('SELECT max(a.id) + 1 id FROM {guifi_ipv4} a, {guifi_interfaces} i WHERE a.interface_id=i.id AND i.id=%d',$data['interface_id']));
      if (is_null($next_id['id']))
        $next_id['id'] = 0;
      $data['id'] = $next_id['id'];
    break;
  case 'guifi_links':
      // fill only if comes empty (remote id already know the id)
      if ($data['id'] == null) {
        $next_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_links}'));
        if (is_null($next_id['id']))
          $next_id['id'] = 1;
        $data['id']=$next_id['id'];
      }
    break;
  } // insert triggers switch table
  // processing update triggers 
  else switch ($table) {
    case 'guifi_zone':
    case 'guifi_location':
    case 'guifi_devices':
      $data['user_changed'] = $user->uid;
      $data['timestamp_changed'] = time();
    // TODO: update node status here
    break;
  }


 $sql_str = '';
 $values_data = array();
 if ($insert) {
   // insert
   foreach ($data as $k=>$value) {
     if (is_numeric($value)) {
       if (is_float($value)) {
         $values_data[$k] = "%f";
       } else 
         $values_data[$k] = "%d";
     } else
       $values_data[$k] = "'%s'";
   }
   $sql_str .= "INSERT INTO {".$table."} (".implode(',',array_keys($data)).") VALUES (".implode(',',$values_data).")";
   $new_data = $data;
 } else {
   // update

   // constructing where with primary keys
   $where_data = array(); 
   foreach ($key as $k=>$value)
     $where_data[$k] = $k.'='.$value;
   // check what's being changed
   $qc = db_query('SELECT '.implode(',',array_keys($data)).' FROM {'.$table.' WHERE '.implode(' AND ',$where_data));
   if (db_num_rows($qc) != 1) 
   {
     drupal_set_message(t('Can\'t update %table while primary key (%where) doesn\'t give 1 row',array('%table'=>$table,'%where'=>$where)));
     return;
   }
   $orig_data = db_fetch_array($qc);
   // cast floats to compare
   foreach ($data as $k=>$value)
     if (is_float($value)) {
       $orig_data[$k] = (float) $orig_data[$k];
     }
   $new_data = array_diff_assoc($data,$orig_data);
   if (count($new_data) == 0) {
     return $orig_data;
   }

   // constructing update
   $log .= $table.' '.t('UPDATED').":<br />";
   foreach ($new_data as $k=>$value) {
     $log .= "\t - ".$k.': '.$orig_data[$k].' -> '.$value.'<br />';
     if (is_float($value))
       $values_data[$k] = $k.'=%f';
     else if (is_numeric($value))
       $values_data[$k] = $k.'=%d';
     else
       $values_data[$k] = $k."='%s'";
   }
   $sql_str .= "UPDATE {".$table."} SET ".implode(', ',$values_data).
               " WHERE ".implode(' AND ',$where_data);
 }

 // execute SQL statement
 $log .= $sql_str.' ('.implode(', ',$new_data).')<br />';
 db_query($sql_str,$new_data);
 return($data);

}

/** _guifi_db_delete(): Delete SQL statements for node, devices, radios, users, services, interfaces, ipv4, links, zones...
***/
function _guifi_db_delete($table,$key,&$to_mail = array(),$depth = 0,$cascade = true) {
  global $user;
  
  if ($depth == 0)
    $dlinks = array();

  $log = str_repeat('- ',$depth);
  $depth++;

  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_db_delete(%s)',$table));
  if (!in_array($user->mail,$to_mail))
    $to_mail[] = $user->mail;

  switch ($table) {

  // Node (location)
  case 'guifi_location':
    $qc = db_query("SELECT id FROM {guifi_devices} where nid = '%s'",
                    $key['id']);
    // cascade to node devices
    while ($device = db_fetch_array($qc))
      $log .= '<br>'._guifi_db_delete('guifi_devices',$device,$to_mail,$depth);      
    // cascade to node users
    $qc = db_query("SELECT id FROM {guifi_users} where nid = '%s'",
                    $key['id']);
    while ($user = db_fetch_array($qc))
      $log .= '<br>'._guifi_db_delete('guifi_users',$user,$to_mail,$depth);      

    break;
  // delete Device
  case 'guifi_devices':
    $item=db_fetch_object(db_query(
      'SELECT d.nick dname, d.notification, d.nid, d.type, d.comment,
        l.nick nname, l.notification ncontact
       FROM {guifi_devices} d LEFT JOIN {guifi_location} l ON d.nid=l.id 
       WHERE d.id = %d', 
       $key['id']));
    $log .= t('Device (%type) %id-%name at node %nname deleted.',array('%type'=>$item->type,'%id'=>$key['id'],'%name'=>$item->dname,'%nname'=>$item->nname));

    // cascade to device radios
    $qc = db_query('SELECT id, radiodev_counter FROM {guifi_radios} WHERE id=%d',$key['id']);
    while ($radio = db_fetch_array($qc))
      $log .= '<br>'._guifi_db_delete('guifi_radios',$radio,$to_mail,$depth);

    // cascade to device interfaces
    $qc = db_query('SELECT id, radiodev_counter FROM {guifi_interfaces} WHERE device_id=%d',$key['id']);
    while ($interface = db_fetch_array($qc))
      $log .= '<br>'._guifi_db_delete('guifi_interfaces',$interface,$to_mail,$depth);

    break;

  // delete Radio
  case 'guifi_radios':
    $item=db_fetch_object(db_query(
       'SELECT 
          r.protocol, r.ssid sid, r.mode, r.radiodev_counter, 
          d.nick dname, d.notification, d.nid, l.nick nname
        FROM {guifi_radios} r, {guifi_devices} d, {guifi_location} l 
        WHERE  r.id = %d AND r.radiodev_counter = %d AND
          r.id=d.id AND d.nid=l.id', 
        $key['id']), $key['radiodev_counter']);
    $log .= t('Radio (%mode-%protocol) %id-%rc %ssid at device %dname deleted.',array('%mode'=>$item->mode,'%protocol'=>$item->protocol,'%id'=>$key['id'],'%rc'=>$key['radiodev_counter'],'%ssid'=>$item->sid,'%dname'=>$item->dname));
  
    // cascade to radio interfaces
    $qc = db_query('SELECT id, radiodev_counter FROM {guifi_interfaces} WHERE device_id=%d AND radiodev_counter=%d',$key['id'],$key['radiodev_counter']);
    while ($interface = db_fetch_array($qc))
      $log .= '<br>'._guifi_db_delete('guifi_interfaces',$interface,$to_mail,$depth);

    break;

  // delete Interfaces
  case 'guifi_interfaces':
    $item=db_fetch_object(db_query(
       'SELECT i.interface_type, i.radiodev_counter, d.nick dname, d.notification, d.nid, l.nick nname
        FROM {guifi_interfaces} i LEFT JOIN {guifi_devices} d ON i.device_id=d.id LEFT JOIN {guifi_location} l ON d.nid=l.id 
        WHERE i.id = %d', 
        $key['id']));
    $log .= t('interface (%type) %id - %rc at device %dname deleted.',array('%type'=>$item->interface_type,'%id'=>$key['id'],'%rc'=>$item->radiodev_counter,'%dname'=>$item->dname));
  
  
    // cascade ipv4
    $qc = db_query('SELECT id, interface_id FROM {guifi_ipv4} WHERE interface_id=%d',$key['id']);
    while ($ipv4 = db_fetch_array($qc))
      $log .= '<br>'._guifi_db_delete('guifi_ipv4',$ipv4,$to_mail,$depth);
    break;

  // delete ipv4
  case 'guifi_ipv4':
    $item=db_fetch_object(db_query(
       'SELECT a.id, a.interface_id, a.ipv4, i.interface_type, d.nick dname, d.notification, d.nid, l.nick nname
        FROM {guifi_ipv4} a LEFT JOIN {guifi_interfaces} i ON a.interface_id=i.id LEFT JOIN {guifi_devices} d ON i.device_id=d.id LEFT JOIN {guifi_location} l ON d.nid=l.id 
        WHERE a.id = %d AND a.interface_id=%d', 
        $key['id'],$key['interface_id']));
    $log .= t('address (%addr) at device %dname deleted.',array('%addr'=>$item->ipv4,'%dname'=>$item->dname));
 
    if (!$cascade)
      break;   
    // cascade links
    $qc = db_query('SELECT id, device_id FROM {guifi_links} WHERE ipv4_id=%d AND interface_id=%d',$key['id'],$key['interface_id']);
    while ($link = db_fetch_array($qc))
      $log .= '<br>'._guifi_db_delete('guifi_links',$link,$to_mail,$depth);
    break;

  // delete links
  case 'guifi_links':
    $item=db_fetch_object(db_query(
       'SELECT l.id, l.link_type, l.ipv4_id, i.id interface_id, d.nick dname, d.id device_id, d.notification, d.nid, n.nick nname
        FROM {guifi_links} l LEFT JOIN {guifi_interfaces} i ON l.interface_id=i.id LEFT JOIN {guifi_devices} d ON l.device_id=d.id LEFT JOIN {guifi_location} n ON l.nid=n.id 
        WHERE l.id = %d AND l.device_id=%d', 
        $key['id'],$key['device_id']));
    $log .= t('link %id-%did (%type) at %nname-%dname deleted.',array('%id'=>$key['id'],'%did'=>$key['device_id'],'%type'=>$item->link_type,'%nname'=>$item->nname,'%dname'=>$item->dname));
  
    if (!$cascade)
      break;   
    // cascade to remote link
    $qc = db_query('SELECT id, ipv4_id, interface_id, device_id FROM {guifi_links} WHERE id=%d AND device_id !=%d',$key['id'],$key['device_id']);
    while ($link = db_fetch_array($qc)) {
      $log .= '<br>'._guifi_db_delete('guifi_links',$link,$to_mail,$depth,false);

      // cleanup of remote ipv4 addresses when appropriate
      // WDS links, clean both IPv4 addresses
     if ($item->link_type == 'wds') 
        $log .= '<br>'._guifi_db_delete('guifi_ipv4',array('id'=>$link['ipv4_id'],'interface_id'=>$link['interface_id']),$to_mail,$depth,false);
    }
    break;

  // delete services
  case 'guifi_services':
    // cascade interfaces
    break;

  // delete users
  case 'guifi_users':
    break;
  
  case 'guifi_zone':
    break;

  }

  $where_str = '';
  foreach ($key as $k=>$value) {
    if ($where_str != '')
      $where_str .= ' AND '; 
    $where_str .= $k.' = '.$value;
  }
  $count = db_fetch_array(db_query("
    SELECT count(*) c
    FROM {".$table."}
    WHERE ".$where_str));
  if ($count['c'] != 1)
    return $log.'<br>'.t('There was nothing to delete at %table with (%where)',array('%table'=>$table,'%where'=>$where_str));
  if (!in_array($item->notification,$to_mail))
    $to_mail[] = $item->notification;
  if (!in_array($item->ncontact,$to_mail))
    $to_mail[] = $item->ncontact;

  $where_str = '';
  foreach ($key as $k=>$value) {
    if ($where_str != '')
      $where_str .= ' AND '; 
    $where_str .= $k.' = '.$value;
  }
  $delete_str = 'DELETE FROM {'.$table.'} WHERE '.$where_str;
  guifi_log(GUIFILOG_TRACE,$delete_str);
  db_query($delete_str);

  return $log;
}
?>