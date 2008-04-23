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
//  print "Hello World (Zone) !!!";

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

  if ($loaded->id != null)
    return $loaded;
  return false;
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
    '#element_validate' => array('guifi_zone_title_validate'),    
    '#weight' => $form_weight++,
  );
  
  
  $form['master'] = array(
    '#type' => 'select',
    '#title' => t('Parent zone'),
    '#required' => FALSE,
    '#default_value' => $node->master,
    '#options' => guifi_zones_listbox(),
    '#element_validate' => array('guifi_zone_master_validate'),    
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
    '#element_validate' => array('guifi_zone_nick_validate'),
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
    '#element_validate' => array('guifi_emails_validate'),
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
    '#element_validate' => array('guifi_zone_ospf_validate'),
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
    '#collapsed' => FALSE,
  );
 
  $form['zone_mapping']['sep-maps-param'] = array(
    '#value' => '<hr /><h2>'.t('zone mapping parameters').'</h2>',
    '#weight' => $form_weight++,
  );
  // if gmap key defined, prepare scripts anf launch google maps integration
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_zonelimits.js','module');
    $form['zone_mapping']['GMAP'] = array(
      '#type' => 'item',
      '#title' => t('Map'),
      '#description' => t('Drag the South-West/North-East corners over the map to change the zone boundaries.'),
      '#suffix' => '<div id="map" style="width: 100%; height: 437px; margin:5px;"></div>',
      '#weight' => $form_weight++,
    );
    $form['guifi_wms'] = array(
      '#type' => 'hidden',
      '#value' => variable_get('guifi_wms_service',''),
    );
  }


  $form['zone_mapping']['MIN_help'] = array(
    '#type' => 'item',
    '#title' => t('Bottom-left (SW) corner'),
    '#description' => t('Coordinates (Lon/Lat) of the bottom-left (South-West) corner of the map.'),
    '#weight' => $form_weight++,
  );

  $form['zone_mapping']['minx'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->minx,
    '#size' => 12,
    '#maxlength' => 24, 
    '#prefix' => '<table style="width: 32em"><tr><td style="width: 12em">',
    '#suffix' => '</ td>',
    '#element_validate' => array('guifi_lon_validate'),
    '#description' => t('Longitude'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['miny'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->miny,
    '#size' => 12,
    '#prefix' => '<td style="width: 12em">',
    '#suffix' => '</td></tr></table>',
    '#element_validate' => array('guifi_lat_validate'),    
    '#description' => t('Latitude'),
    '#maxlength' => 24, 
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['MAX_help'] = array(
    '#type' => 'item',
    '#title' => t('Upper-right (NE) corner'),
    '#description' => t('Coordinates (Lon/Lat) of the upper-right (North-East) corner of the map.'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['maxx'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->maxx,
    '#size' => 12,
    '#maxlength' => 24, 
    '#prefix' => '<table style="width: 32em"><tr><td style="width: 12em">',
    '#suffix' => '</ td>',
    '#element_validate' => array('guifi_lon_validate'),
    '#description' => t('Longitude'),
    '#weight' => $form_weight++,
  );
  $form['zone_mapping']['maxy'] = array(
    '#type' => 'textfield',
    '#default_value' => $node->maxy,
    '#size' => 12,
    '#maxlength' => 24, 
    '#prefix' => '<td style="width: 12em">',
    '#suffix' => '</td></tr></table>',
    '#element_validate' => array('guifi_lat_validate'),    
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
    $node->minx = -70;
    $node->miny = -50;
    $node->maxx = 70;
    $node->maxy = 50;
  } 
}

/** guifi_zone_map_help Print help text for embedded maps
 */
function guifi_zone_map_help($rid) {
  $output = '<a href="'.variable_get("guifi_maps", 'http://maps.guifi.net').'/world.phtml?REGION_ID='.$rid.'" target=_top>'.t('View the map in full screen and rich mode').'</a>';
  $output .= '<p>'.t('Select the lens controls to zoom in/out or re-center the map at the clicked position. If the image has enough high resolution, you can add a node at the red star position by using the link that will appear.').'</p>';
  return $output;
}


function guifi_zone_hidden_map_fileds($node) {
  $output  = '<from><input type="hidden" id="minx" value="'.$node->minx.'"/>';
  $output .= '<input type="hidden" id="miny" value="'.$node->miny.'"/>';
  $output .= '<input type="hidden" id="maxx" value="'.$node->maxx.'"/>';
  $output .= '<input type="hidden" id="maxy" value="'.$node->maxy.'"/>';
  $output .= '<input type="hidden" id="zone_id" value="'.$node->id.'"/>';
  $output .= '<input type="hidden" id="guifi-wms" value="'.variable_get('guifi_wms_service','').'"/></form>';
  return $output;  
}

/** guifi_zone_simple_map(): Print de page show de zone map and nodes without zoom.
 */
function guifi_zone_simple_map($node) {
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_zone.js','module');
    $output = '<div id="map" style="width: 100%; height: 380px; margin:5px;"></div>';
    $output .= guifi_zone_hidden_map_fileds($node);
  } else {
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
  }
  return $output;
}


/** * guifi_zone_map(): Print de page show de zone map and nodes.
 */
function guifi_zone_map($node) {
  
//  $node = guifi_zone_load($node);
  drupal_set_breadcrumb(guifi_zone_ariadna($node->id));
  
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_zone.js','module');
    $output = '<div id="map" style="width: 100%; height: 480px; margin:5px;"></div>';
    $output .= guifi_zone_hidden_map_fileds($node);
  } else {
    $output = guifi_zone_map_help($node->id); 
    $output .= '<IFRAME FRAMEBORDER="0" SRC="'.variable_get("guifi_maps", 'http://maps.guifi.net').'/world.phtml?IFRAME=Y&MapSize=600,450&REGION_ID='.$node->id.'" ALIGN="CENTER" WIDTH="670" HEIGHT="500" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="AUTO">';
    $output .= t('Sorry, your browser can\'t display the embedded map');
    $output .= '</IFRAME>';
  }
  return $output;
}

/** guifi_zone_validate(): Confirm that an edited guifi item has fields properly filled in.
 */
 
function guifi_zone_title_validate($element, &$form_state) {
  if (empty($element['#value'])) 
    form_error($element,t('You must specify a title for the zone.'));
}

function guifi_zone_nick_validate($element, &$form_state) {
  if (empty($element['#value'])) {
    $nick = guifi_abbreviate($form_state['values']['title']);
    drupal_set_message(t('Zone nick has been set to:').' '.$nick);
    $form_state['values']['nick'] = $nick;
  }
}

function guifi_lat_validate($element, &$form_state) {
  if (empty($element['#value'])) 
    form_error($element,t('Latitude must be specified.'));
  if (!is_numeric($element['#value']))
    form_error($element,t('Latitude must be numeric'));
  if (($element['#value'] > 90) || ($element['#value'] < -90))
    form_error($element,t('Latitude must be between -90 and 90.'));
}

function guifi_lon_validate($element, &$form_state) {
  if (empty($element['#value'])) 
    form_error($element,t('Longitude must be specified.'));
  if (!is_numeric($element['#value']))
    form_error($element,t('Longitude must be numeric'));
  if (($element['#value'] > 180) || ($element['#value'] < -180))
    form_error($element,t('Longitude must be between -180 and 180.'));
}

function guifi_zone_ospf_validate($element, &$form_state) {
  if  ($element['#value'] != htmlentities($element['#value'], ENT_QUOTES))
    form_error(
      t('No special characters allowed for OSPF id, use just 7 bits chars.')
    );

  if (str_word_count($element['#value']) > 1)
    form_error(
      t('OSPF zone id have to be a single word.'));
}

function guifi_emails_validate($element, &$form_state) {
  if (empty($element['#value'])) 
    form_error($element,t('You should specify at least one notification email address.'));
  $emails = guifi_notification_validate($element['#value']);
  if (!$emails)
    form_error($element,
      t('Error while validating email address'));
  else 
    $form_state['values']['notification'] = $emails;
}

function guifi_zone_master_validate($element, &$form_state) {
//  print_r($form_state);
//  print "\n<br>";
//  print_r($element);
  
  // if root, check that there is not another zone as zoot
  if ($element['#value'] == 0) {
  	  $qry = db_query(
           'SELECT id, title, nick 
  			FROM {guifi_zone} 
  			WHERE master = 0');
  	 while ($rootZone = db_fetch_object($qry)) 
  	 {
        if ($form_state['values']['nid'] != $rootZone->id)
          form_error($element,
            t('The root zone is already set to "%s". Only one root zone can be present at the database. Delete/change the actual root zone before assigning a new one or choose another partent.',
              array('%s'=>$rootZone->title)));
  	 }
  }
  
  if (!empty($form_state['values']['nid']))
  if ($element['#value'] == $form_state['values']['nid'])
    form_error($element,
      t("Master zone can't be set to itself"));
}

function guifi_zone_validate($form) {
  if ($node->minx > $node->maxx)
    form_set_error('minx', t("Longitude: Min should be less than Max").
      ' '.$node->minx.'/'.$node->maxx);
  if ($node->miny > $node->maxy)
    form_set_error('miny', t("Latitude: Min should be less than Max"),
      ' '.$node->miny.'/'.$node->maxy);
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
    $parentData = db_fetch_array(db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.id = %d ',$parent));
    $ret[] = l($parentData['title'],$link.$parentData['id']);
  }
  $ret[count($ret)-1] = '<b>'.$ret[count($ret)-1].'</b>';
  
  $child = array();
  $query = db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.master = %d ORDER BY z.weight, z.title',$id);
  while ($zoneChild = db_fetch_array($query)) {
    $child[] = l($zoneChild['title'],$link.$zoneChild['id']);
  } 
  if (count($child)) {
    $child[0] = '<br><small>('.$child[0];
    $child[count($child)-1] = $child[count($child)-1].')</small>';
    $ret = array_merge($ret,$child);
  }
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
  drupal_set_breadcrumb(guifi_zone_ariadna($zone->id));

  $table = theme('table', null, guifi_zone_print_data($zone));
  $output .= theme('box', t('zone information'), $table);

  return $output;
}

/** guifi_zone_ipv4(): outputs the zone networks
**/
function guifi_zone_ipv4($zone) {

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
function guifi_zone_nodes($node,$embeded = false) {
  
  if (!$embeded)
    drupal_set_breadcrumb(guifi_zone_ariadna($node->id));

  $output = '<h2>' .t('Nodes listed at') .' ' .$node->title .'</h2>';

  // Going to list child zones totals
  $result = db_query('SELECT z.id, z.title FROM {guifi_zone} z WHERE z.master = %d ORDER BY z.weight, z.title',$node->id);

  $rows = array();
  
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
    
   if (count($rows)>1) 
     $output .= theme('table', $header, $rows);

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
  $node->id);
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
  if (count($rows)>0) {
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
          array(theme_table(null,array(array(array('data'=>'<small>'.guifi_zone_print($node->nid).'</small>','width'=>'50%'),
                                             array('data'=>guifi_zone_simple_map($node),'width'=>'50%'))))),
          array($node->body),
 //         array(guifi_zone_print($node->nid)),
          array(guifi_zone_nodes($node,true))
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
