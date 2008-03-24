<?php
// $Id: guifi.module x$

/**
 * @file
 * Manage guifi_zone and guifi_networks
 */


/** zone editing functions
**/

/** guifi_zone_load(): Load the zone from the guifi database.
 */
function guifi_zone_load($node) {
  guifi_log(GUIFILOG_FULL,
    'function guifi_zone_load()',
    $node);
    
  if (is_object($node))
    $k = $node->nid;
  else
    $k = $node;

  $loaded = db_fetch_object(
    db_query("
    SELECT * FROM {guifi_zone} WHERE id = '%d'", 
    $k));
  if (($loaded->nick == '') or ($loaded->nick == null))
    $loaded->nick = guifi_abbreviate($loaded->title);

  return $loaded;
}

/** guifi_zone_form(): Present the guifi zone editing form.
 */
function guifi_zone_form(&$node, &$param) {
  $form_weight = -20;
  
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#required' => TRUE,
    '#default_value' => $node->title,
    '#weight' => $form_weight++,
  );
  
  
  $form['master'] = array(
    '#type' => 'select',
    '#title' => t('Parent zone'),
    '#required' => FALSE,
    '#default_value' => $node->master,
    '#options' => guifi_zones_listbox(),
    '#description' => t('The parent zone where this zone belongs to.'),
    '#weight' => $form_weight++,
  );
  $form['body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Description of the zone'), 
    '#default_value' => $node->body, 
    '#cols' => 60, 
    '#rows' => 10, 
    '#required' => FALSE,
    '#description' =>
      t('This text will be displayed as the page. Should contain information of the zone.'),
    '#weight' => $form_weight++,
  );
  
  // Els que no són administradors ja en tenen prou amb aquestes dades.
  if (!user_access('administer guifi zones'))
    return $form;

  $form['nick'] = array(
    '#type' => 'textfield',
    '#title' => t('Short abreviation'),
    '#required' => FALSE,
    '#default_value' => $node->nick,
    '#size' => 10,
    '#maxlength' => 10, 
    '#description' => t('Single word, 7-bits characters. Used while default values as hostname, SSID, etc...'),
    '#weight' => $form_weight++,
  );
  $form['time_zone'] = array(
    '#type' => 'select',
    '#title' => t('Time zone'),
    '#required' => FALSE,
    '#default_value' => $node->time_zone,
    '#options' => guifi_types('tz'),
    '#weight' => $form_weight++,
  );
  $form['homepage'] = array(
    '#type' => 'textfield',
    '#title' => t('Zone homepage'),
    '#required' => FALSE,
    '#default_value' => $node->homepage,
    '#size' => 60,
    '#maxlength' => 128, 
    '#description' => t('URL of the local community homepage, if exists. Usefull for those who want to use this site just for network administration, but have their own portal.'),
    '#weight' => $form_weight++,
  );
  $form['notification'] = array(
    '#type' => 'textfield',
    '#title' => t('email notification'),
    '#required' => TRUE,
    '#default_value' => $node->notification,
    '#size' => 60,
    '#maxlength' => 1024, 
    '#description' => t('Mails where changes at the zone will be notified. Usefull for decentralized administration. If more than one, separated by \',\''),
    '#weight' => $form_weight++,
  );
  
  // Separació Paràmetre globals de xarxa
  $form['zone_network_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Zone global network parameters'),
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['zone_network_settings']['sep-global-param'] = array(
    '#value' => '<hr /><h2>'.t('zone global network parameters').'</h2>',
    '#weight' => $form_weight++,
  );
  $form['zone_network_settings']['dns_servers'] = array(
    '#type' => 'textfield',
    '#title' => t('DNS Servers'),
    '#required' => FALSE,
    '#default_value' => $node->dns_servers,
    '#size' => 60,
    '#maxlength' => 128, 
    '#description' => t('The Name Servers of this zone, will inherit parent DNS servers if blank. Separated by ",".'),
    '#weight' => $form_weight++,
  );
  $form['zone_network_settings']['ntp_servers'] = array(
    '#type' => 'textfield',
    '#title' => t('NTP Servers'),
    '#required' => FALSE,
    '#default_value' => $node->ntp_servers,
    '#size' => 60,
    '#maxlength' => 128, 
    '#description' => t('The network time protocol (clock) servers of this zone, will inherit parent NTP servers if blank. Separated by ",".'),
    '#weight' => $form_weight++,
  );
  $form['zone_network_settings']['ospf_zone'] = array(
    '#type' => 'textfield',
    '#title' => t('OSPF zone id'),
    '#required' => FALSE,
    '#default_value' => $node->ospf_zone,
    '#size' => 60,
    '#maxlength' => 128, 
    '#description' => t('The id that will be used when creating configuration files for the OSPF routing protocol so all the routhers within the zone will share a dynamic routing table.'),
    '#weight' => $form_weight++,
  );
  $form['zone_network_settings']['mrtg_servers'] = array(
    '#type' => 'textfield',
    '#title' => t('MRTG zone url'),
    '#required' => FALSE,
    '#default_value' => $node->mrtg_servers,
    '#size' => 60,
    '#maxlength' => 128, 
    '#description' => t('This URL will be used for the obtaining of graphs from external servers to guifi.'),
    '#weight' => $form_weight++,
  );
  
  // Aquesta condició sempre es complirà, doncs ja s'ha fet anteriorment
  if (user_access('administer guifi zones')) 
    $form['zone_network_settings']['graph_server'] = array(
      '#type' => 'select',
      '#title' => t("Server which collects traffic and availability data"),
      '#required' => FALSE,
      '#default_value' => ($node->graph_server ? $node->graph_server : 0),
      '#options' => array('0'=>'Default','-1'=>'None') + guifi_services_select('SNPgraphs'),
      '#description' => t("If not specified, inherits parent zone properties."),
      '#weight' => $form_weight++,
    );
  
  
  // Separació Paràmetres dels mapes
  $form['zone_mapping'] = array(
    '#type' => 'fieldset',
    '#title' => t('Zone mapping parameters'),
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['zone_mapping']['sep-maps-param'] = array(
    '#value' => '<hr /><h2>'.t('zone mapping parameters').'</h2>',
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['MIN_help'] = array(
    '#type' => 'item',
    '#title' => t('Bottom left corner'),
    '#description' => t('Coordinates (Lon/Lat) of the bottom left corner of the map.'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['minx'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->minx,
    '#size' => 12,
    '#maxlength' => 24, 
    '#prefix' => '<table style="width: 32em"><tr><td style="width: 12em">',
    '#suffix' => '</ td>',
    '#description' => t('Longutude'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['miny'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->miny,
    '#size' => 12,
    '#prefix' => '<td style="width: 12em">',
    '#suffix' => '</td></tr></table>',
    '#description' => t('Latitude'),
    '#maxlength' => 24, 
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['MAX_help'] = array(
    '#type' => 'item',
    '#title' => t('Upper right corner'),
    '#description' => t('Coordinates (Lon/Lat) of the upper right corner of the map.'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['maxx'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->maxx,
    '#size' => 12,
    '#maxlength' => 24, 
    '#prefix' => '<table style="width: 32em"><tr><td style="width: 12em">',
    '#suffix' => '</ td>',
    '#description' => t('Longutude'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['maxy'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->maxy,
    '#size' => 12,
    '#maxlength' => 24, 
    '#prefix' => '<td style="width: 12em">',
    '#suffix' => '</td></tr></table>',
    '#description' => t('Latitude'),
    '#weight' => $form_weight++,
  );

  return $form;
}

/** guifi_zone_prepare(): Default values
 */
function guifi_zone_prepare(&$node) {
  global $user;

  // Init default values
  if ($node->id=='') {
    if ($node->notification == '') 
      $node->notification = $user->mail;
    $node->time_zone = '+01 2 2';
  }
  
}

/** guifi_zone_map_help Print help text for embedded maps
 */
function guifi_zone_map_help($rid) {
  $output = '<a href="'.variable_get("guifi_maps", 'http://maps.guifi.net').'/world.phtml?REGION_ID='.$rid.'" target=_top>'.t('View the map in full screen and rich mode').'</a>';
  $output .= '<p>'.t('Select the lens controls to zoom in/out or re-center the map at the clicked position. If the image has enough high resolution, you can add a node at the red star position by using the link that will appear.').'</p>';
  return $output;
}

/** guifi_zone_simple_map(): Print de page show de zone map and nodes without zoom.
 */
function guifi_zone_simple_map($node) {
  $output .= '
    <IFRAME FRAMEBORDER="0" ALIGN=right SRC="'.
    variable_get(
      "guifi_maps",
      'http://maps.guifi.net').
    '/world.phtml?IFRAME=Y&MapSize=300,240&REGION_ID='.
    $node->id.
    '" WIDTH="350" HEIGHT="290" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="AUTO">';
  $output .= t('Sorry, your browser can\'t display the embedded map');
  $output .= '</IFRAME>';
  return $output;
}

/** * guifi_zone_map(): Print de page show de zone map and nodes.
 */
function guifi_zone_map($nid) {
  $output = guifi_zone_map_help($node->id); 
  $output .= '<IFRAME FRAMEBORDER="0" SRC="'.variable_get("guifi_maps", 'http://maps.guifi.net').'/world.phtml?IFRAME=Y&MapSize=600,450&REGION_ID='.$node->id.'" ALIGN="CENTER" WIDTH="670" HEIGHT="500" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="AUTO">';
  $output .= t('Sorry, your browser can\'t display the embedded map');
  $output .= '</IFRAME>';
  return $output;
}

/** guifi_zone_validate(): Confirm that an edited guifi item has fields properly filled in.
 */
function guifi_zone_validate($node,$form) {

  function validate_limits($x, $y, $message,$field) {
    if (!is_numeric($x))
      form_set_error($field, $message.' '.
        t("Lon must be numeric."));
    if (!is_numeric($y))
      form_set_error($field, $message.' '.
        t("Lat must be numeric."));
    if ((($x == null) and ($y != null)) || (($x != null) and ($y == null)))
      form_set_error($field, $message.' '.
        t("Both coordinates (Lon/Lat) must be filled."));
    if (($x > 180) || ($x < -180))
      form_set_error($field, $message.' '.
        t("Longitude has to be between -180 and 180"));
    if (($y > 90) || ($y < -90))
      form_set_error($field, $message.' '.
        t("Latitude has to be between -90 and 90"));
  }

  $emails = guifi_notification_validate($node->notification);
  if (!$emails)
    form_set_error('notification',
      t('Error while validating email address'));
  else 
    form_set_value($form['notification'],$emails);
  
  if (($node->nick == "") or (is_null($node->nick))) {
    $nick = guifi_abbreviate($node->title);
    drupal_set_message(t('Zone nick has been set to:').' '.$nick);
    form_set_value($form['nick'],$nick);  
  }

  if  ($node->ospf_zone != htmlentities($node->ospf_zone, ENT_QUOTES))
    form_set_error(
      'ospf_zone', 
      t('No special characters allowed for OSPF id, use just 7 bits chars.')
    );

  if (str_word_count($node->ospf_zone) > 1)
    form_set_error(
      'ospf_zone', 
      t('OSPF zone id have to be a single word.'));

  if (empty($node->title)) {
    form_set_error(
      'name', 
      t('You must specify a name for the zone.'));
  }

  if ($node->nid == $node->master)  {
    form_set_error('master', t("Master zone can't be set to itself"));
    $node->master = 0;
    unset($node->map);
    unset($node->valid);
  }
  
  if (!(($node->maxx == 0) && ($node->maxy == 0) && 
      ($node->minx == 0) && ($node->miny == 0))  )
  if (($node->maxx != null) && ($node->maxy != null) && 
      ($node->minx != null) && ($node->miny != null)) {
    validate_limits($node->minx, $node->miny,t('Min:'),'minx');
    validate_limits($node->maxx, $node->maxy,t('Max:'),'maxx');
    if ($node->minx >= $node->maxx)
      form_set_error('minx', t("Min Lon should be less than max Lon"));
    if ($node->miny >= $node->maxy)
      form_set_error('minx', t("Min Lat should be less than max Lat"));
  }
}

/** guifi_zone_insert(): Insert a zone into the database.
 */
function guifi_zone_insert($node) {
  $node->new=true;
  $node->id   = $node->nid;
  $node->minx = (float)$node->minx;
  $node->maxx = (float)$node->maxx;
  $node->miny = (float)$node->miny;
  $node->maxy = (float)$node->maxy;
  $to_mail = explode(',',$node->notification);
  $nzone = _guifi_db_sql(
    'guifi_zone',
    array('id'=>$node->id),(array)$node,$log,$to_mail);
    
  guifi_notify(
    explode(',',$node->notification),
    t('A new zone %nick-%name has been created',
      array('%nick'=>$node->nick,'%name'=>$node->title)),
    $log);


 // if box set, maps should be rebuilt to add the new zone box in the lists
 if (($node->minx) || ($node->miny) || ($node->maxx) || ($node->maxy)) {
//   touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
   variable_set('guifi_refresh_cnml',time());
   variable_set('guifi_refresh_maps',time());

   cache_clear_all();
 }
}

/** guifi_zone_update(): Save zone changes into the database.
 */
function guifi_zone_update($node) {

  global $user;

  // if box changed, maps should be rebuilt
  $pz = db_fetch_object(db_query('SELECT * FROM {guifi_zone} z WHERE z.id = %d',$node->nid));
  if (($pz->maxx != $node->maxx) || ($pz->maxy != $node->maxy) || ($pz->minx != $node->minx) || ($pz->miny != $node->miny)) {
//    touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
    variable_set('guifi_refresh_cnml',time());
    variable_set('guifi_refresh_maps',time());

    cache_clear_all();
  }

  $node->minx = (float)$node->minx;
  $node->maxx = (float)$node->maxx;
  $node->miny = (float)$node->miny;
  $node->maxy = (float)$node->maxy;
  $to_mail = explode(',',$node->notification);
  $nzone = _guifi_db_sql(
    'guifi_zone',
    array('id'=>$node->nid),
    (array)$node,
    $log,
    $to_mail);
  guifi_notify(
    explode(',',$node->notification),
    t('Zone %nick-%name has been updated',
      array('%nick'=>$node->nick,'%name'=>$node->title)),
    $log);


}

/** guifi_zone_delete(): Delete a zone
**/
function guifi_zone_delete(&$node) {
  global $user;
  
  $delete = true;
  $qn = db_fetch_object(db_query("
    SELECT count(*) count
    FROM {guifi_networks}
    WHERE zone=%d",
    $node->nid));
  if ($qn->count) {
    drupal_set_message(t('FATAL ERROR: Can\'t delete a zone which have networks allocated. Database broken. Contact your system administrator'),'error');
    $delete = false;
  }
  $ql = db_fetch_object(db_query("
    SELECT count(*) count
    FROM {guifi_location}
    WHERE zone_id=%d",
    $node->nid));
  if ($ql->count) {
    drupal_set_message(t('FATAL ERROR: Can\'t delete a zone whith nodes. Database broken. Contact your system administrator'),'error');
    $delete = false;
  }

  $to = explode(',',$node->notification);
  $to[] = variable_get('guifi_contact','webmestre@guifi.net');
  if (!$delete) {
    $messages = drupal_get_messages(null,FALSE);
    guifi_notify(
    $to,
    t('ALERT: Zone %nick-%name has been deleted, but have errors:',
      array('%nick'=>$node->nick,'%name'=>$node->title)),
    implode("\n",$messages['error']));
    return;
  }

  // perform deletion
  $node->deleted = true;
  $nzone = _guifi_db_sql(
    'guifi_zone',
    array('id'=>$node->id),
    (array)$node,
    $log,
    $to);
  guifi_notify(
    $to,
    t('Zone %nick-%name has been deleted',
      array('%nick'=>$node->nick,'%name'=>$node->title)),
    $log);
  cache_clear_all();
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());

  return;
}
/** guifi_get_zone_parents(): Get the guifi zone parents
 */
function guifi_get_zone_parents($id) {
 
  $parent=$id;
  $parents[] = $id;
  while ($parent > 0) {
    $result = db_query('
      SELECT z.master master 
      FROM {guifi_zone} z 
      WHERE z.id = %d',
      $parent);
    $row = db_fetch_object($result);
    $parent = $row->master;
    $parents[] = $parent;
  }
 
  return $parents;
}

/** guifi_zone_ariadna(): Get an array of zone hierarchy to breadcumb
**/
function guifi_zone_ariadna($id = 0, $link = 'node/') {
  $ret = array();
  foreach (array_reverse(guifi_get_zone_parents($id)) as $parent) 
  if ($parent > 0) {
    $result = db_fetch_array(db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.id = %d ',$parent));
    $ret[] = l($result['title'],$link.$result['id']);
  }
  $query = db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.master = %d ORDER BY z.weight, z.title',$id);
  $t = db_result($query);
  $c = 1;
  if ($t) 
  $ret[] = '<div class="breadcumb">';
    while ($zone = db_fetch_array($query)) {
      if ($c == 1)
        $prefix = '(';
      else 
        $prefix = '';
      if ($c == $t)
        $suffix = ')';
      else 
        $suffix = '';
      $ret[] = l($zone['title'],$link.$zone['id']);
      $c++;
    }
    $ret[] = '</div><hr />';
  return $ret;
}

/** guifi_zone_print_data(): outputs the zone information data
**/
function guifi_zone_print_data($zone) {
  
  $name_created = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $zone->user_created));
  $name_changed = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $zone->user_changed));

  $rows[] = array(t('zone name'),$zone->nick.' - <b>' .$zone->title .'</b>'); 
  if ($zone->homepage)
    $rows[] = array(t('homepage'),l($zone->homepage,$zone->homepage)); 
  if (($zone->notification) and (user_access('administer guifi zones')))
    $rows[] = array(t('changes notified to (visible only if you are privileged):'),'<a href="mailto:'.$zone->notification.'">'.$zone->notification.'</a>'); 
  $rows[] = array(t('network global information').':',null);
  $rows[] = array(t('DNS Servers'),$zone->dns_servers); 
  $rows[] = array(t('NTP Servers'),$zone->ntp_servers); 
  $rows[] = array(t('OSPF zone'),$zone->ospf_zone);
  $rows[] = array(t('MRTG Servers'),$zone->mrtg_servers); 
  $tz = db_fetch_object(db_query("SELECT description FROM {guifi_types} WHERE type = 'tz' AND text = '%s'",$zone->time_zone));
  $rows[] = array(t('Time zone'),$tz->description); 
  $rows[] = array(t('log information').':',null);
  if ($zone->timestamp_created > 0) 
    $rows[] = array(t('created by'),l($name_created->name,'user/'.$zone->user_created) .'&nbsp;' .t('at') .'&nbsp;' .format_date($zone->timestamp_created)); 
  if ($zone->timestamp_changed > 0) 
    $rows[] = array(t('updated by'),l($name_changed->name,'user/'.$zone->user_changed) .'&nbsp;' .t('at') .'&nbsp;' .format_date($zone->timestamp_changed)); 

  return array_merge($rows);
}

/** guifi_zone_print():  outputs the zone information
**/
function guifi_zone_print($id) {

  $zone = guifi_zone_load($id);

  $table = theme('table', null, guifi_zone_print_data($zone));
  $output .= theme('box', t('zone information'), $table);

  return $output;
}

/** guifi_zone_ipv4(): outputs the zone networks
**/
function guifi_zone_ipv4($id) {

  $zone = guifi_zone_load($id);
  $header = array(t('zone'),t('network'),t('mask'),t('start'),t('end'),t('hosts'),t('type'));
  
  $rows = guifi_ipv4_print_data($zone);
  if (user_access('administer guifi networks')) { 
    $rows[] = array(l('add network','node/'.$zone->id.'/view/ipv4/add'));
    $header = array_merge($header,array(t('operations')));
  }

  $table = theme('table', $header, $rows);
  $output .= theme('box', t('zone &#038; parent(s) network allocation(s)'), $table);

  return $output;
}


/** guifi_zone_node_totals(): summary of a zone
**/
function guifi_zone_totals($zones) {
 
  $result = db_query("SELECT status_flag, count(*) total FROM {guifi_location} l WHERE l.zone_id in (%s) GROUP BY status_flag",implode(',',$zones));
  while ($sum = db_fetch_object($result)) {
    $summary[$sum->status_flag] = $sum->total;
    $summary['Total'] = $summary['Total'] + $sum->total;
  }

  return $summary;
}

/** guifi_zone_nodes(): list nodes of a given zone and its childs
*/
function guifi_zone_nodes($nid) {

  $output = '<h2>' .t('Nodes listed at') .' ' .$node->title .'</h2>';

  // Going to list child zones totals
  $result = db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.master = %d ORDER BY z.weight, z.title',$nnid);
  if (db_result($result) > 0) {
    $header = array(
      array('data' => t('Zone name')),
      array('data' => t('Online'),'class' => 'Online'),
      array('data' => t('Planned'),'class' => 'Planned'),
      array('data' => t('Building'),'class' => 'Building'),
      array('data' => t('Testing'),'class' => 'Testing'),
      array('data' => t('Total'),'class' => 'Total'));
    while ($zone = db_fetch_object($result)) {
      $summary = guifi_zone_totals(guifi_get_zone_child_tree($zone->id));
      $rows[] = array(
        array('data' => guifi_zone_l($zone->id,$zone->title,'node/'),'class' => 'zonename'),
        array('data' => number_format($summary['Working'] ,0,null,variable_get('guifi_thousand','.')),'class' => 'Working','align'=>'right'),
        array('data' => number_format($summary['Planned'] ,0,null,variable_get('guifi_thousand','.')),'class' => 'Planned','align'=>'right'),
        array('data' => number_format($summary['Building'],0,null,variable_get('guifi_thousand','.')),'class' => 'Building','align'=>'right'),
        array('data' => number_format($summary['Testing'] ,0,null,variable_get('guifi_thousand','.')),'class' => 'Testing','align'=>'right'),
        array('data' => number_format($summary['Total']   ,0,null,variable_get('guifi_thousand','.')),'class' => 'Total','align'=>'right'));
      if (!empty($summary))
      foreach ($summary as $key => $sum)
        $totals[$key] = $totals[$key] + $sum; 
    }
    $rows[] = array(
      array(
        'data' => NULL,               
        'class' => 'zonename'),
      array('data' => number_format($totals['Working'] ,0,null,variable_get('guifi_thousand','.')), 'class' => 'Online','align'=>'right'),
      array('data' => number_format($totals['Planned'] ,0,null,variable_get('guifi_thousand','.')), 'class' => 'Planned','align'=>'right'),
      array('data' => number_format($totals['Building'],0,null,variable_get('guifi_thousand','.')),'class' => 'Building','align'=>'right'),
      array('data' => number_format($totals['Testing'] ,0,null,variable_get('guifi_thousand','.')), 'class' => 'Testing','align'=>'right'),
      array('data' => number_format($totals['Total']   ,0,null,variable_get('guifi_thousand','.')),'class' => 'Total','align'=>'right'));
     $output .= theme('table', $header, $rows);
  }

  // Going to list the zone nodes
  $rows = array();
  $result = pager_query('
    SELECT l.id,l.nick, l.notification, l.zone_description,
      l.status_flag, count(*) radios 
    FROM {guifi_location} l LEFT JOIN {guifi_radios} r ON l.id = r.nid 
    WHERE l.zone_id = %d 
    GROUP BY 1,2,3,4,5 
    ORDER BY radios DESC, l.nick',
    50,0,
    'SELECT count(*)
    FROM {guifi_location}
    WHERE zone_id = %d',
    $nid);
  if (db_result($result) > 0) {
    $header = array(
      array('data' => t('nick (shortname)')),
      array('data' => t('supernode')),
      array('data' => t('area')),
      array('data' => t('status')));
    while ($loc = db_fetch_object($result)) {
      if ($loc->radios == 1)
        $loc->radios = t('No'); 
      $rows[] = array(
        array('data' => guifi_zone_l($loc->id,$loc->nick,'node/')),
        array('data' => $loc->radios),
        array('data' => $loc->zone_description),
        array('data' => t($loc->status_flag),'class' => $loc->status_flag));
    }
//     $output .= theme('table', $header, array_merge($rows));
    $output .= theme('table', $header, $rows);
    $output .= theme_pager(null, 50);

  }

  return $output;
}

/** guifi_get_zone_childs(): get a tree of the zones
**/
function guifi_get_zone_child_tree($parent = 0, $depth = 30, $maxdepth = NULL) {

  $children = array($parent);
  $result = db_query('SELECT z.id, z.title, z.master FROM {guifi_zone} z ORDER BY z.master');
  while ($zone = db_fetch_object($result)) {
    if (in_array($zone->master,$children)) 
      $children[] = $zone->id;
  }
  return $children;
}

/** guifi_zone_availability(): List zone nodes/devices with it's availability status
 */
function guifi_zone_availability_recurse($node, $depth = 0,$maxdepth = 3) {
  
  $rows = array(); 
  $depth ++;
  $result = db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.master = %d ORDER BY z.weight, z.title',$node->nid);
  if (db_result($result) > 0) 
    while ($zone = db_fetch_object($result)) {
      $rows[] = array(
                      array('data' => '<a href="node/'.$zone->id.'/view/availability">'.$zone->title,'class' => 'fullwidth'));
      $child = node_load(array('nid' => $zone->id));
      $rows[] = array(guifi_zone_availability_recurse($child,$depth,$maxdepth));
    } // end while zones
      
  if ($depth < $maxdepth)  {
    $result = db_query('SELECT l.id,l.nick, l.notification, l.zone_description, l.status_flag, count(*) radios FROM {guifi_location} l LEFT JOIN {guifi_radios} r ON l.id = r.nid WHERE l.zone_id = %d GROUP BY 1,2,3,4,5 ORDER BY radios DESC, l.nick',$node->nid);
    if (db_result($result) > 0) 
      while ($loc = db_fetch_object($result)) {
        $qdevices = db_query("SELECT d.id, d.nick, d.flag FROM {guifi_devices} d WHERE nid=%d",$loc->id);
        $i = 0;
        if (db_result($qdevices) > 0) 
          while ($radio = db_fetch_object($qdevices)) {
            if ($i == 0) 
              $nnick = '<a href="node/'.$loc->id.'">'.$loc->nick.'</a>';
            else
              $nnick = null; 
            $i++;

            $url_device = url('guifi/device/'.$radio->id, NULL, NULL, FALSE);


            $graph_url = guifi_radio_get_url_mrtg($radio->id,FALSE);
            if ($graph_url != NULL)
              $img_url = ' <img src='.$graph_url.'?device='.$radio->id.'&type=availability&format=long>';
            else
              $img_url = NULL;

            $rows[] = array(
                            array('data' => $nnick,'class'=>'quarterwidth'),
                            array('data' => '<a href="'.$url_device.'">'.$radio->nick.'</a>', 'class' => 'quarterwidth'),
                            array('data' => t($radio->flag).$img_url,'class' => $radio->flag)
              );

          } // while radios 
      } // while nodes
  } // if in depth

  if (count($rows)) {
    if ($depth > 1)
      $header = array(t('node'),t('device'),t('status'));
    return theme('table',$header,$rows,array('width'=>'100%'));
  } else 
  return;
}

function guifi_zone_availability($nid) {
  $node = node_load(array('nid'=>$nid));
  $output = '<h2>' .t('Availability of ') .' ' .$node->title .'</h2>';
  $rows[] = array(guifi_zone_availability_recurse($node));
  $output .= theme('table', null, array_merge($rows),array('width'=>'100%'));
  return $output;
}

/**  guifi_zone_view(): zone view page
**/
function guifi_zone_view($node, $teaser = FALSE, $page = FALSE, $block = FALSE) {
  
  node_prepare($node);
  if ($teaser)
    return $node;
  if ($block)
    return $node;
  
  if ($page) {
    $node->content['body']['#value'] = 
      theme_table(null,array(
          array(theme_table(null,array(array($node->body,
                                             guifi_zone_simple_map($node))))),
          array(guifi_zone_print($node->nid)),
          array(guifi_zone_nodes($node))
        )
      );
        
    return $node;
  }
  
}

/** Miscellaneous utilities related to zones
**/

/** guifi_zones_listbox(): Creates a list of the zones
**/
// TODO Apply filters for this
function guifi_zones_listbox_recurse($id, $indent, $listbox, $children, $exclude) {
  if ($children[$id]) {
    foreach ($children[$id] as $foo => $zone) {
      if (!$exclude || $exclude != $zone->id) {
        $listbox[$zone->id] = $indent .' '. $zone->title;
        $listbox = guifi_zones_listbox_recurse($zone->id, $indent .'--', $listbox, $children, $exclude);
      }
    }
  }

  return $listbox;
}

function guifi_zones_listbox($exclude = 0) {
  $result = db_query('SELECT z.id, z.title, z.master, z.weight FROM {guifi_zone} z ORDER BY z.weight, z.title');

  while ($zone = db_fetch_object($result)) {
    if (!$children[$zone->master]) {
      $children[$zone->master] = array();
    }
    array_push($children[$zone->master], $zone);
  }

  $listbox = array();

  $listbox[0] = '<'. t('root zone') .'>';
 
  $listbox = guifi_zones_listbox_recurse(0, '', $listbox, $children, $exclude);

  return $listbox;
}

/** guifi_zone_l(): Creates a link to the zone
**/
function guifi_zone_l($id, $title, $linkto) {
  return l($title, $linkto. $id);
}


?>
