<?php
// $Id: guifi.module x$

/**
 * @file guifi_node.incp.php
 * Manage guifi_node 
 * rroca
 */

/* main node (locations) hooks */
/** guifi_node_access(): construct node permissions
 
  guifi_node_access($op:string,$node:Obj-node):boolean
  globals
    $user:Obj-user
  functions
    ???->user_access(p1:string):boolean
*/
function guifi_node_access($op, $node) {
  global $user; 
  
  if ($op == 'create') {
    return user_access('create guifi nodes');
  }

  if ($op == 'update') {
    if ((user_access('administer guifi zones')) || ($node->uid == $user->uid)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }
}

/** guifi_node_add(): creates a new node

  guifi_node_add($id:int):Void
  functions
    guifi_zone.inc->guifi_zone_load($node:int*):obj-zone
*/
function guifi_node_add($id) {
  $zone = guifi_zone_load($id);
  // Set the defaults for a node of this zone
  // Callback to node/guifi-node/add
  drupal_goto('node/add/guifi-node?edit[title]='.$zone->id);
}


/** guifi_node_load(): load and constructs node array from the database

  guifi_node_load($node:obj-node):obj-location
**/
function guifi_node_load($node) {
      return db_fetch_object(db_query("SELECT * FROM {guifi_location} WHERE id = '%d'", $node->nid));
}

/** node editing functions
**/

/** guifi_node_form(): Present the node preparing form.
  *                   modifica el objecte $node pasat per parametro reomplint camps buits
  *                   crida a la funcio guifi_node_form_supernode que presenta el formulari
  *                   i retorna la form que retorna dita funcio.
 
  guifi_node_form($node:obj-node,$param:Not_use):form
  globals
    $user:Obj-user
  functions
    guifi_zone.inc->guifi_zone_load($node:int*):obj-zone
    guifi_includes.inc->guifi_coord_dtodms($coord:float):Array($deg:int,$min:int,$seg:int) or NULL
*/
function guifi_node_form($node, $param){   //$node pasat per referencia a partir versio 5 de php tot es pasa per referencia no necesita &
  global $user;
  
  if(empty($node->nid)){
    if(is_numeric($node->title)){
      $zone = guifi_zone_load($node->title);
      $node->zone_id = $node->title;
      $default = t('<nodename>');
      $node->title = null;
      $node->nick = $zone->nick.$default;
    }
    $node->notification = $user->mail;
    $node->status_flag = 'Planned';
  }
  // Position
  // if not came at get/post, fill lat/lon
  if (isset($_POST['lat'])){$node->lat = $_POST['lat'];}
  if (isset($_POST['lon'])){$node->lon = $_POST['lon'];}
  if (isset($_GET['lat'])){$node->lat = $_GET['lat'];}
  if (isset($_GET['lon'])){$node->lon = $_GET['lon'];}
  $coord=guifi_coord_dtodms($node->lat);
  if($coord != NULL) {
    $node->latdeg = $coord[0];
    $node->latmin = $coord[1];
    $node->latseg = $coord[2];
  }
  $coord=guifi_coord_dtodms($node->lon);
  if ($coord != NULL) {
    $node->londeg = $coord[0];
    $node->lonmin = $coord[1];
    $node->lonseg = $coord[2];
  }

  $output = guifi_node_form_supernode($node, $param);
  return $output;
}

/** guifi_node_form_supernode(): Present the node editing form.
 
  guifi_node_form($node:obj-node,$param:Not_use):form
  globals
    $user:Obj-user
*/
function guifi_node_form_supernode($node, $param) {   
  global $user;

  // A partir d'ara l'ordre el definirem per aquesta variable.
  // Així ens estalviem canviar-ho tot cada cop que inserim un nou element.
  $form_weight = -20;
  
  // ----
  // El títol el primer de tot
  // ------------------------------------------------
  $form['title'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node name and general settings'),
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['title']['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#required' => TRUE,
    '#default_value' => $node->title,
    '#weight' => 0,
  );
  $form['title']['nick'] = array(
    '#type' => 'textfield',
    '#title' => t('Nick'),
    '#required' => TRUE,
    '#size' => 20,
    '#maxlength' => 20, 
    '#default_value' => $node->nick,
    '#description' => t("Unique identifier for this node. Avoid generic names such 'MyNode', use something that really identifies your node.<br />Short name, single word with no spaces, 7-bit chars only, will be used for  hostname, reports, etc.") . ($error['nick'] ? $error["nick"] : ''),
    '#weight' => 2,
  );
  $form['title']['notification'] = array(
    '#type' => 'textfield',
    '#title' => t('Contact'),
    '#required' => FALSE,
    '#size' => 60,
    '#maxlength' => 1024, 
    '#default_value' => $node->notification,
    '#description' => t("Who did possible this node or who to contact with regarding this node if it is distinct of the owner of this page. Use valid emails, if you like to have more than one, separated by commas.'") . ($error['notification'] ? $error["notification"] : ''),
    '#weight' => 3,
  );
  
  // ----
  // El títol settings
  // ------------------------------------------------
  
  $form['title']['settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node settings'),
    '#weight' => 4,
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
    // Si ets administrador pots definir el servidor de dades
  if (user_access('administer guifi zones')){ 
    $form['title']['settings']['graph_server'] = array(
      '#type' => 'select',
      '#title' => t("Server which collects traffic and availability data"),
      '#required' => FALSE,
      '#default_value' => 
        ($node->graph_server ? $node->graph_server : 0),
      '#options' => array(
        '0'=>t('Default'),
        '-1'=>t('None')) + guifi_services_select('SNPgraphs'),
      '#description' => t("If not specified, inherits zone properties."),
      '#weight' => 0,
    );
  }
  $form['title']['settings']['stable'] = array(
    '#type' => 'select',
    '#title' => t("It's supposed to be a stable online node?"),
    '#required' => FALSE,
    '#default_value' => ($node->stable ? $node->stable : 'Yes'),
    '#options' => array(
      'Yes' => t('Yes, is intended to be kept always on,  avalable for extending the mesh'), 
      'No' => t("I'm sorry. Will be connected just when I'm online")),
    '#description' => 
      t("That helps while planning a mesh network. We should know which locations are likely available to provide stable links."),
    '#weight' => 1,
  );

  // ----
  // license si es un node nou
  // agreement
  // ------------------------------------------------
  
  if (empty($node->nid)) {
    $form['license'] = array(
      '#type' => 'item',
      '#title' => t('License and usage agreement'),
      '#value' => variable_get('guifi_license',null),
      '#description' => t('You must accept this agreement to be authorized to create new nodes.'),
      '#weight' => $form_weight++,
    );
    $form['agreement']= array(
      '#type' => 'radios',
//      '#title' => t('Yes, I have read this and accepted'),
      '#default_value' => 'No',
      '#options' => array('Yes'=>t('Yes, I have read this and accepted')),
      '#weight' => $form_weight++,
    );
  } else {
    $form['agreement']= array(
      '#type' => 'hidden',
      '#default_value' => 'Yes',
      '#weight' => $form_weight++,
    );
  };
  
  // ----
  // position
  // ------------------------------------------------

  $form['position'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node postion settings'),
    '#weight' => $form_weight++,
    '#collapsible' => false,
//    '#collapsed' => TRUE,
  );
  $form['position']['zone_id'] = array(
    '#type' => 'select',
    '#title' => t("Zone"),
    '#required' => FALSE,
    '#default_value' => $node->zone_id,
    '#options' => guifi_zones_listbox(),
    '#description' => t('The zone where this node where this node belongs to.'),
    '#weight' => 0,
  );
  if (module_exists('gmap')) {
    $form['position']['gmap_node'] = array();
    $form['position']['lat'] = array(
      '#parents' => array('lat'),
      '#type' => 'textfield',
      '#title' => t('Latitude'),
      '#default_value' => $node->lat,
      '#size' => 30,
      '#maxlength' => 120,
    );
    $form['position']['lon'] = array(
      '#parents' => array('lon'),
      '#type' => 'textfield',
      '#title' => t('Longitude'),
      '#default_value' => $node->lon,
      '#size' => 30,
      '#maxlength' => 120,
      '#description' => t('The latitude and longitude will be entered here when you click on a location in the interactive map above. You can also fill in the values manually.'),
    );
    if ((($node->lat == 0) and ($node->lon == 0)) or ($node->lat == null) or ($node->lon == null)){ 
      $map_macro = '[gmap |id=usermap|center=17,-4|zoom=2|width=100%|height=400px]';
    } else {
      $map_macro = '[gmap|id=usermap|center=17,-4|zoom=13|width=100%|height=400px|type=Hybrid]';
    }
    $form['position']['gmap_node']['#value'] = 
      gmap_set_location($map_macro,$form['position'], array('latitude' => 'lat', 'longitude' => 'lon'));
  } else {
    $form['position']['longitude'] = array(
      '#type' => 'item',
      '#title' => t('Longitude'),
      '#prefix' => '<table><th>&nbsp</th><th>'.
        t('degrees (decimal values allowed)').
          '</th><th>'.
          t('minutes').
          '</th><th>'.
          t('seconds').
          '</th><tr><td>',
      '#suffix' => '</td>',
      '#weight' => 1,
    );  
    $form['position']['londeg'] = array(
      '#type' => 'textfield',
      '#default_value' => $node->londeg,
      '#size' => 12, 
      '#maxlength' => 24, 
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#weight' => 2,
    );
    $form['position']['lonmin'] = array(
      '#type' => 'textfield',
      '#default_value' => $node->lonmin,
      '#size' => 12, 
      '#maxlength' => 24, 
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#weight' => 3,
    );
    $form['position']['lonseg'] = array(
      '#type' => 'textfield',
      '#default_value' => $node->lonseg,
      '#size' => 12, 
      '#maxlength' => 24, 
      '#prefix' => '<td>',
      '#suffix' => '</td></tr>',
      '#weight' => 4,
    );
    $form['position']['latitude'] = array(
      '#type' => 'item',
      '#title' => t('Latitude'),
      '#prefix' => '<tr><td>',
      '#suffix' => '</td>',
      '#weight' => 5,
    );
    $form['position']['latdeg'] = array(
      '#type' => 'textfield',
      '#default_value' => $node->latdeg,
      '#size' => 12, 
      '#maxlength' => 24, 
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#weight' => 6,
    );
    $form['position']['latmin'] = array(
      '#type' => 'textfield',
      '#default_value' => $node->latmin,
      '#size' => 12, 
      '#maxlength' => 24, 
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#weight' => 7,
    );
    $form['position']['latseg'] = array(
      '#type' => 'textfield',
      '#default_value' => $node->latseg,
      '#size' => 12, 
      '#maxlength' => 24, 
      '#prefix' => '<td>',
      '#suffix' => '</td></tr></table>',
      '#weight' => 8,
    );
  }
  $form['position']['zone_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Zone description'),
    '#required' => FALSE,
    '#size' => 60,
    '#maxlength' => 128, 
    '#default_value' => $node->zone_description,
    '#description' => t("Zone, address, neighborhood. Something that describes your area within your location.<br />If you don't know your lat/lon, please provide street and number or crossing street.") . ($error['zone'] ? $error["zone"] : ''),
    '#weight' => 4,
  );

  $form['position']['elevation'] = array(
    '#type' => 'textfield',
    '#title' => t('Antenna elevation'),
    '#required' => FALSE,
    '#size' => 5,
    '#length' => 20,
    '#maxlength' => 20, 
    '#default_value' => $node->elevation,
    '#description' => t("Antenna height over the floor level.") . ($error['elevation'] ? $error["elevation"] : ''),
    '#weight' => 5,
  );
  
  // ----
  // body 
  // ------------------------------------------------
 
  $form['body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Body'), 
    '#default_value' => $node->body, 
    '#cols' => 60, 
    '#rows' => 20, 
    '#required' => FALSE,
    '#description' => t("Textual description of the wifi") . ($error['body'] ? $error['body'] : ''),
    '#weight' => $form_weight++,
  );
  
  // ----
  // flags
  // ------------------------------------------------

  // Això no sé benbé què és
  //  $output .= implode("", taxonomy_node_form("wifi", $node));
  $form['status_flag']= array(
    '#type' => 'hidden',
    '#default_value' => $node->status_flag,
    '#weight' => $form_weight++,
  );
  
  return $form;
  //return $output;
}

/** _guifi_line_edit_device_form(): creates an url for editing the form
 
  _guifi_line_edit_device_form($id:int):form
  globals
    $form
*/
function _guifi_line_edit_device_form($id) {
  unset($form);
  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $form['submit'] = array('#type' => 'submit', '#value' => t('Edit'));
  $form['#action'] = url('guifi/device/'. $id.'/edit');
  return $form;
}
/** guifi_node_validate(): Confirm that an edited guifi item has fields properly filled in.
 
  guifi_node_validate($node:obj-node,$form:obj-form):void si hi ha un error cancela la gravacio
  functions
    ???->guifi_validate_nick($nick:string):????
    guifi_includes.inc->guifi_coord_dmstod($deg:int,$min:int,$seg:int):$coord:float or NULL
 */
function guifi_node_validate($node,$form) {
  guifi_validate_nick($node->nick);

  if ($node->agreement != 'Yes'){
    form_set_error('agreement', 
      t('You must read and accept the license &#038; terms and conditions to be allowed to create nodes.'));
  }

  if (!empty($node->nick)) { 
    $query = db_query("SELECT nick FROM {guifi_location} WHERE lcase(nick)='%s' AND id <> %d",
      strtolower($node->nick),$node->nid);
    if (db_num_rows($query)){
      form_set_error('nick', t('Nick already in use.'));
    }
  }

  $emails = guifi_notification_validate($node->notification);
  if (!$emails){
    form_set_error('notification',
      t('Error while validating email address'));
  } else { 
    form_set_value($form['title']['notification'],$emails);
  }

  // not at root zone
  if (($node->zone_id == 0) && (!empty($node->nick))){
    form_set_error('zone_id', 
      t('Can\'t be assigned to root zone, please assign the node to an appropiate zone.'));
  }
  
  if ($node->elevation == 0){$node->elevation = NULL;}
  if (($node->elevation < -1) && ($node->elevation != NULL)){
    form_set_error('elevation', 
      t('Elevation must be above the floor! :)'));
  }
  if (($node->elevation > 100) && ($node->elevation != NULL)){
    form_set_error('elevation', 
      t('Do you mean that you are flying over the earth??? :)'));
  }
  $coord=guifi_coord_dmstod($node->latdeg,$node->latmin,$node->latseg);
  if($coord!=NULL){
    $lat=$coord;
  } else {
    $lat=$node->lat;
  }
  $coord=guifi_coord_dmstod($node->londeg,$node->lonmin,$node->lonseg);
  if($coord!=NULL){
    $lon=$coord;
  } else {
    $lon=$node->lon;
  }

  if ($lat == 0){$lat = NULL;}
  if ($lon == 0){$lon = NULL;}

  if (($lat == NULL) or ($lon == NULL)){
    form_set_error('lon', 
      t('Please provide lon/lat information for the node. You can obtain by using the maps and clicking over the point where the node is located.'));
  }
  if (($lat > 180) or ($lat < -180)){
    form_set_error('lat', 
      t('Latitude must be between -180 and 180 degrees.'));
  }
  if (($lon > 90) or ($lon < -90)){
    form_set_error('lon', 
      t('Longitude must be between -90 and 90 degrees.'));
  }
}
 
/** guifi_node_insert(): Create a new node in the database
 
  guifi_node_insert($node:Obj-node):void
  functions
    ???->_guifi_db_sql(???):????
    ???->guifi_notify(???):void
    guifi_includes.inc->guifi_coord_dmstod($deg:int,$min:int,$seg:int):$coord:float or NULL
 */
function guifi_node_insert($node) {

  $coord=guifi_coord_dmstod($node->latdeg,$node->latmin,$node->latseg);
  if($coord!=NULL){
    $node->lat=$coord;
  }
  $coord=guifi_coord_dmstod($node->londeg,$node->lonmin,$node->lonseg);
  if($coord!=NULL){
    $nose->lon=$coord;
  }

  if ($node->lat == 0){$node->lat = NULL;}
  if ($node->lon == 0){$node->lon = NULL;}
  
  $to_mail = explode(',',$node->notification);
  $node->new=true;
  $node->id  = $node->nid;
  $node->lat = (float)$node->lat;
  $node->lon = (float)$node->lon;
  $nnode = _guifi_db_sql(
    'guifi_location',
    array('id'=>$node->nid),(array)$node,$log,$to_mail);
  guifi_notify(
    $to_mail,
    t('The node %name has been CREATED by %user.',array('%name' => $node->title, '%user' => $user->name)),
    $log);

  // Refresh maps
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());

  cache_clear_all();
}

/** guifi_node_update(): Update a node in the database
 
  guifi_node_update($node:Obj-node):void
  functions
    ???->_guifi_db_sql(???):????
    ???->guifi_notify(???):void
    guifi_includes.inc->guifi_coord_dmstod($deg:int,$min:int,$seg:int):$coord:float or NULL
*/
function guifi_node_update($node) {
  
  $coord=guifi_coord_dmstod($node->latdeg,$node->latmin,$node->latseg);
  if($coord!=NULL){
    $node->lat=$coord;
  }
  $coord=guifi_coord_dmstod($node->londeg,$node->lonmin,$node->lonseg);
  if($coord!=NULL){
    $node->lon=$coord;
  }

  if ($node->lat == 0){$node->lat = NULL;}
  if ($node->lon == 0){$node->lon = NULL;}

  $to_mail = explode(',',$node->notification);

  // Refresh maps?
  $pn = db_fetch_object(db_query(
    'SELECT * 
    FROM {guifi_location} l 
    WHERE l.id=%d',
    $node->nid));  
  if (($pn->lat != $node->lat) || ($pn->lon != $node->lon) || ($pn->status_flag != $node->status_flag)) {
  // touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
    variable_set('guifi_refresh_cnml',time());
    variable_set('guifi_refresh_maps',time());
    cache_clear_all();
  }

  $node->lat = (float)$node->lat;
  $node->lon = (float)$node->lon;
  $nnode = _guifi_db_sql(
    'guifi_location',
    array('id'=>$node->id),
    (array)$node,$log,$to_mail);
  guifi_notify(
    $to_mail,
    t('The node %name has been UPDATED by %user.',array('%name' => $node->title, '%user' => $user->name)),
    $log);
}

/** guifi_node_delete(): deletes a given node
 
  guifi_node_delete($node:Obj-node):void
  functions
    ???->_guifi_db_delete(???):????
    ???->guifi_notify(???):void
**/
function guifi_node_delete(&$node) {
  
  $to_mail = explode(',',$node->notification);
  $log = _guifi_db_delete('guifi_location',array('id'=>$node->nid),$to_mail,$depth);
  drupal_set_message($log);
  guifi_notify(
           $to_mail, 
           t('The node %name has been DELETED by %user.',array('%name' => $node->title, '%user' => $user->name)),
           $log);
  cache_clear_all();
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());

  return;
}
/** node visualization (view) function calls */

/** guifi_node_print_data(): outputs the node information (d)ata
 
  guifi_node_print_data($node:Obj-node):array
  functions
    ???->user_access(p1:string):boolean
**/
function guifi_node_print_data($node) {
  
  $name_created = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $node->user_created));
  $name_changed = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $node->user_changed));
  $zone         = db_fetch_object(db_query('SELECT id, title, master, valid FROM {guifi_zone} WHERE id = %d', $node->zone_id));

  $url_map = sprintf(' <a href="http://www.mapquest.com/maps/map.adp?latlongtype=decimal&latitude=%f&longitude=%f" target="_blank">%s</a>',$node->lat,$node->lon,t('external map'));

  $map = '<IFRAME FRAMEBORDER="0" ALIGN=right SRC="'.variable_get("guifi_maps", 'http://maps.guifi.net').'/world.phtml?IFRAME=Y&MapSize=300,240&Lat='.$node->lat.'&Lon='.$node->lon.'&Layers=all" WIDTH="350" HEIGHT="290" MARGINWIDTH="0" MARGINHEIGHT="0" SCROLLING="AUTO">';
  $map .= t('Sorry, your browser can\'t display the embedded map');
  $map .= '</IFRAME>';
  
  $rows[] = array(t('node'),$node->nid .' ' .$node->nick,'<b>' .$node->title .'</b>',
                  array('data'=>$map,'rowspan'=>8)); 
  $rows[] = array(t('zone'),$zone->title,$node->zone_description); 
  $rows[] = array(t('position (lat/lon)'),sprintf('<a href="http://maps.guifi.net/world.phtml?Lat=%f&Lon=%f&Layers=all" target="_blank">Lat:%f<br />Lon:%f</a>',
                   $node->lat,$node->lon,$node->lat,$node->lon),$node->elevation .'&nbsp;'.t('meters above the ground')); 
  $rows[] = array(t('available for mesh &#038; status'),$node->stable,array('data' => t($node->status_flag),'class' => $node->status_flag)); 

  if (($node->notification) and (user_access('administer guifi networks')))
    $rows[] = array(
      t('changes notified to (visible only if you are privileged):'),
      array(
        'data'=>
          '<a href="mailto:'.$node->notification.'">'.$node->notification.'</a>',
          'colspan'=>2));
      
  switch ($node->graph_server) {
  case -1: 
    $graphtxt = t('Graphs disabled.'); 
    break;
  case 0: 
  case NULL:
    $graphtxt = t('Default: Obtained from parents'); 
    break;
  default:
    $qgs = db_query(sprintf('SELECT nick FROM {guifi_services} WHERE id=%d',$node->graph_server));
    $gs = db_fetch_object($qgs);
    if (!empty($gs->nick)) 
      $graphtxt = '<a href=/node/'.$node->graph_server.'>'.$gs->nick.'</a>';
    else
      $graphtxt = t('invalid');
  }
  $rows[] = array(t('graphs provided from'),array('data'=>$graphtxt,'colspan'=>2)); 

  
  $rows[] = array(null,null,null);
  $rows[] = array(array('data'=>'<b>' .t('user and log information').'</b>','colspan'=>'3'));
  if ($node->timestamp_created > 0)
    $rows[] = array(t('created by'),l($name_created->name,'user/'.$node->user_created) .'&nbsp;' .t('at') .'&nbsp;' .format_date($node->timestamp_created));
  if ($node->timestamp_changed > 0)
    $rows[] = array(t('updated by'),l($name_changed->name,'user/'.$node->user_changed) .'&nbsp;' .t('at') .'&nbsp;' .format_date($node->timestamp_changed));
  return array_merge($rows);
}

/** guifi_node_view(): outputs the node information
 
  guifi_node_view($node:obj-node*,$teaser:boolean,$page:boolean):obj-node*
  functions
    ???->node_load($node:obj-node):obj-node
    ???->node_prepare($node:obj-node):obj-node
**/
function guifi_node_view(&$node, $teaser = FALSE, $page = FALSE) {

  if (is_numeric($node)) {
    $node = node_load($node);
  }
  
  $output = '<div id="guifi">';

  $node = node_prepare($node);

//  print_r($node->content);

  switch (arg(3)) {
    case 'data':
    case 'graphs':
    case 'devices':
    case 'interfaces':
    case 'links':
    case 'services':
    case 'users':
    case 'distances':
      $op = arg(3);
      break;
    default: 
      $op = "default";
      break;
  }
  switch ($op) {
    case 'all': case 'data': case 'default':
      // node details
      if ($op == 'data') {
        $output .= theme('table',null,guifi_node_print_data($node));
        break;
      }
      if ($page)
        $node->content['body']['#value'] =  $node->body.theme('table', NULL, guifi_node_print_data($node));
      if ($teaser)
        $node->content['body']['#value'] =  $node->body;
      if ($op == 'view') {
        $output .= $node->body;
        break;
      }
    case 'graphs':
      // node graphs
      $output .= theme('table', array(t('traffic overview')), guifi_node_graph_overview($node));
      if ($op == 'graphs') break;
    case 'devices':
      // listing node devices
      $output .= guifi_node_radio_list($node->nid);
      if ($op == 'devices') break;
    case 'links':
      // listing node links
      $output .= guifi_node_link_list($node->nid,'cable');
      $output .= guifi_node_link_list($node->nid,'wds');
      $output .= guifi_node_link_list($node->nid,'ap/client');
      break;
    case 'distances':
      // listing node neighbours
      $output .= drupal_get_form('guifi_node_distances',$node->nid);
      break;
    case 'services':
      // listing node services
      $output .= guifi_list_services($node);
      break;
    case 'users':
      // listing node users
      $output .= guifi_list_users($node);
      break;
  }
  $output .= "</div>";

  drupal_set_breadcrumb(guifi_zone_ariadna($node->zone_id));
  if ($op == 'default') {
    $node->content['body']['#value'] .= $output; 
    return $node;
  }
  drupal_set_title(t('node').': '.$node->title.' ('.t($op).')');
  print theme('page', $output,FALSE);
  exit(0);

//  drupal_set_title($node->title.' ('.t($op).')');
//  print theme('page',$output);
//  exit(0);
//}

//  $node->body .= theme('box', t('node information'), $output);

//  if ($op == 'default')
//    print theme('page',$output,t('node').': '.$node->title.' ('.t($op).')');
}

/** guifi_node_radio_list(): list of node devices
 
  guifi_node_radio_list($id:int):form
  functions
    ???->guifi_get_device(???):????
    ???->guifi_availabilitystr(???):????
    ???->guifi_device_access(???):????
    ???->guifi_main_ip(???):????
    ???->guifi_node_get_url_mrtg(???):????
**/
function guifi_node_radio_list($id = 0) {
  
  $header = array('<h2>'.t('device').'</h2>', t('type'), t('status'),t('available'),t('last'));

  // Form for adding a new device
  $form = drupal_get_form('guifi_device_create_form',$id);

  $query = db_query("SELECT d.id FROM {guifi_devices} d WHERE nid=%d",$id);
  if (db_num_rows($query))
  while ($d = db_fetch_object($query)) {
     $device = guifi_get_device($d->id);
     $status_str = guifi_availabilitystr($device);
     if (guifi_device_access('update',$device[id])) {
       // form to allow editing the device
       $edit_radio = '<td>'.drupal_get_form('_guifi_line_edit_device_form',$device['id']);
       $edit_radio .= "<td/>";

     }
     if ($device->variable['firmware'] != "n/d") {
       $unsolclic = '<td><a href="'.url('guifi/device/' .$device[id] .'/view/unsolclic').'" title="' .t("Get radio configuration with singleclick") .'">' .$device[variable]['firmware'] .'</a></td>';
     }
     $ip = guifi_main_ip($device[id]);
//     print_r($ip);
     $graph_url = guifi_node_get_url_mrtg($id,FALSE);
     if ($graph_url != NULL)
       $img_url = ' <img src='.$graph_url.'?device='.$device['id'].'&type=availability&format=short>';
     else
       $img_url = NULL;
     $rows[] = array('<a href="'.url('guifi/device/'.$device[id]).'">'.$device[nick].'</a>',$device[type],
                 array('data' => $ip[ipv4].'/'.$ip[maskbits], 'align' => 'left'),
                 array('data' => t($device[flag]),'class' => $device['flag']),
                 array('data' => $img_url, 
                                 'class' => $device['flag']),
                 $edit_radio,
                 $unsolclic
                    );
  }

  return '<h4>'.t('devices').'</h4>'.theme('table', $header, $rows).$form;
}

/** guifi_node_print_distances(): list of neighbors
 
  guifi_node_distances($id:int,$edit:???):form
  functions
    ???->guifi_log(???):????
    ???->node_load(???):????
    ???->guifi_devices_select_filter(???):????
**/
function guifi_node_distances($id,$edit = NULL) {
  global $base_url;
  
  guifi_log(GUIFILOG_TRACE,'function guifi_node_distances()',$edit);

  $node = node_load(array('nid' => $id));
  $form = array();
  $form['#multistep'] = TRUE;
  $form['#redirect'] = FALSE;


  drupal_set_title(t('distances from').' '.$node->nick);

  

  // initialize filters
  if (empty($edit['filters'])) 
    $edit['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 30,
    'search' => null,
    'max'    => 50,
    'skip'   => 0,
    'status' => "All",
    'from_node' => $edit['nid'],
    'azimuth' => "0,360",
    );
  $filters = $edit['filters'];

  // deso la lat/lon d'origen
  $lat1 = $node->lat;
  $long1 = $node->lon;
  
  // deso el nom del node d'origen per anomenar els perfils
  $node1 = $node->nick;  

  // Vaig a llistar els nodes i la calcular la distacia
  $result = db_query("SELECT n.id, n.lat, n.lon, n.nick, n.status_flag, n.zone_id  FROM {guifi_location} n WHERE n.id !=%d AND (n.lat != '' AND n.lon != '') AND (n.lat != 0 AND n.lon != 0)",$id);

  $oGC = new GeoCalc();
  $nodes = array();
  $rows = array();
  $totals[] = NULL;

  if ($edit['op'] == t('Next page'))
     $filters['skip'] = $filters['skip'] + $filters['max'];
  if ($edit['op'] == t('Previous page'))
     $filters['skip'] = $filters['skip'] - $filters['max'];

  $nc = 0;
  $allow_next = false;
  if ($filters['skip'])
   $allow_prev = true;
  else
   $allow_prev = false;

  while ($node = db_fetch_array($result)) {
     $distance = round($oGC->EllipsoidDistance($lat1, $long1, $node["lat"], $node["lon"]),3);

     // Apply filters
     if ($distance <=  $filters['dmax'])
     if ($distance >=  $filters['dmin'])
     if (($filters['status'] == 'All') or ($filters['status'] == $node['status_flag'])) 
     {
       $nodes[] = array_merge(array('distance'=>$distance),$node);
     }
  }

  // Filter form
  $fw = 0;
  guifi_devices_select_filter($form,null,$filters,$fw);


  if (count($nodes)==0) {
    $form['empty'] = array(
      '#type'=> 'item',
      '#title'=> t('The list is empty'),
      '#value'=> t('Th given query has returned no rows.'),
      '#description'=> t('Use the filters to get some results'),
      '#weight'=>$fw++,
    );
    return $form;
  }

  asort($nodes);

  // header
  
  $form['z'] = array(
    '#type'=>'fieldset',
    '#tree'=>true,
    '#weight'=>$fw++
  );
  $form['z'][-1]['h_node'] = array(
    '#type'=> 'item',
    '#title'=> t('Node'),
    '#description'=> t('Zone'),
    '#prefix'=>'<table><tr><th>',
    '#suffix'=>'</th>',
    '#weight'=>$fw++,
  );
  $form['z'][-1]['h_distance'] = array(
    '#type'=> 'item',
    '#title'=> t('Distance'),
    '#value'=> t('Status'),
    '#description'=> t('Azimuth'),
    '#prefix'=>'<th>',
    '#suffix'=>'</th>',
    '#weight'=>$fw++,
  );
  $form['z'][-1]['h_heights'] = array(
    '#type'=> 'item',
    '#title'=> t('Heights image'),
    '#description'=> t('Click over the image to view in large format'),
    '#prefix'=>'<th>',
    '#suffix'=>'</th></tr>',
    '#weight'=>$fw++,
  );


  $nc = 0;
  $tc = count($nodes);

  foreach ($nodes as $key => $node) {

    $dAz = round($oGC->GCAzimuth($lat1, $long1, $node["lat"], $node["lon"]));
    // Calculo orientacio
    if ($dAz < 23) $dOr =t("North"); else
    if ($dAz < 68) $dOr =t("North East"); else
    if ($dAz < 113) $dOr =t("East"); else
    if ($dAz < 158) $dOr =t("South East"); else
    if ($dAz < 203) $dOr =t("South"); else
    if ($dAz < 248) $dOr =t("South West"); else
    if ($dAz < 293) $dOr =t("West"); else
    if ($dAz < 338) $dOr =t("North West"); else
      $dOr =t("North");
//    $output .=  _wifi_state_class($rows[$key]["state"]) .t($rows[$key]["state"]) ."</td>";

    // conversio de les coordenades a UTM
    
    $UTMnode1 = guifi_WG842UTM($long1,$lat1,5,31,1);
    $UTMnode2 = guifi_WG842UTM($node["lon"],$node["lat"],5,31,1);
    
    // genero URL del Perfil
    
    $height_url = base_path(). drupal_get_path('module', 'guifi') .'/guifi_heights.php?x1='
.$UTMnode1[0]."&y1=".$UTMnode1[1]."&x2=".$UTMnode2[0]."&y2=".$UTMnode2[1];
    $height_url_long = $height_url."&node1=".$node1."&node2=".$node["nick"]."&width=1100&height=700";
    $height_url_small = $height_url."&width=200&height=100";
    $zone = node_load(array('nid'=>$node['zone_id']));

    if ($filters['search'])
    if (!(stristr($zone->nick.$node['nick'],$filters['search'])))
     continue;

    if ($filters['azimuth']) {
      $l = false;
      foreach (explode('-',$filters['azimuth']) as $minmax) {
        list($min,$max) = explode(',',$minmax);
        if (($dAz <= $max) and ($dAz >= $min))
          $l = true;
      }
      if (!$l)
       continue;
    }

   // All filters applied, see if fits in the current chink (skip/max)
   if ($nc >= $filters['skip'] + $filters['max']) {
     $allow_next = true;
     break;
    }
    $nc++;
    if ($nc < $filters['skip']) 
        continue;

    $suffix = '</td></tr>';
    if ((!$allow_pre) and (!$allow_next))
      if ($nc == $tc)
        $suffix = '</td></tr></table>';
//    $form['z'][$nc]['d_nid'] = array (
//      '#type'=>'hidden',
//      '#parents'=> array('z',$nc,'d_nid'),
//      '#value' => $node['id'],
//      '#weight'=>$fw++,
//    );
    $form['z'][$nc]['d_node'] = array(
      '#type'=> 'item',
      '#parents'=> array('z',$nc,'d_node'),
      '#title'=> l($node['nick'],'node/'.$node['id']),
      '#description'=> l($zone->nick,'node/'.$node['zone_id']),
      '#prefix'=>'<tr><td>',
      '#suffix'=>'</td>',
      '#weight'=>$fw++,
    );
    $form['z'][$nc]['d_distance'] = array(
      '#type'=> 'item',
      '#parents'=> array('z',$nc,'d_distance'),
      '#title'=> $node['distance'].' '.t('kms'),
      '#value'=> $node['status_flag'],
      '#description'=> $dAz.'º - '.$dOr,
      '#prefix'=>'<td>',
      '#suffix'=>'</td>',
      '#weight'=>$fw++,
    );
    $form['z'][$nc]['d_status'] = array(
      '#type'=> 'item',
      '#parents'=> array('z',$nc,'d_status'),
      '#value'=> '<a href="'.$height_url_long.'" alt="'.t('Click to view in large format').'" target="_blank"><img src="'.$height_url_small.'"></a>',
//      '#prefix'=> '<td><img src="'.$height_url_small.'">',
      '#prefix'=> '<td>',
      '#suffix'=>$suffix,
      '#weight'=>$fw++,
    );
  } // eof while distance < max:distance
  if (!$allow_next) 
    $suffix = '</td></tr></table>';
  else
    $suffix = '<td>';
  if ($allow_prev) {
    $prefix = '<td>';
    $form['z'][$nc++]['prev'] = array(
    '#type' => 'submit',
    '#parents'=>array('z',$nc++,'prev'),
    '#value' => t('Previous page'),
    '#name'=> 'op',
    '#prefix'=> '<tr><td>',
    '#suffix'=>$suffix,
    '#weight' => $fw++,
    );
  } else
    $prefix = '<tr><td>';
  if ($allow_next)
    $form['z'][$nc++]['next'] = array(
    '#type' => 'submit',
    '#parents'=>array('z',$nc++,'next'),
    '#value' => t('Next page'),
    '#prefix'=> $prefix,
    '#suffix'=>'</td></tr></table>',
    '#name'=> 'op',
    '#weight' => $fw++,
  );
  return $form;
}

/** guifi_node_link_list(): list of node links
 
  guifi_node_link_list($id:int,$ltype:???):form
  functions
**/
function guifi_node_link_list($id = 0, $ltype = '%') {
  $oGC = new GeoCalc();

  $total = 0;
  if ($ltype == '%') 
    $output = '<h4>'.t('links').'</h4>'; 
  else
    $output = '<h4>'.t('links').' ('.$ltype.')</h4>'; 
  $header = array(t('linked nodes (device)'), t('ip'), t('status'), t('kms.'),t('az.'));
  
  $listed = array('0');
  $queryloc1 = db_query("SELECT c.id, l.id nid, l.nick, c.device_id, d.nick device_nick, a.ipv4 ip, c.flag, l.lat, l.lon, r.ssid FROM {guifi_links} c LEFT JOIN {guifi_devices} d ON c.device_id=d.id LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id LEFT JOIN {guifi_location} l ON d.nid = l.id LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=c.ipv4_id LEFT JOIN {guifi_radios} r ON d.id=r.id AND i.radiodev_counter=r.radiodev_counter WHERE d.nid = %d AND link_type like '%s' ORDER BY c.device_id, i.id",$id,$ltype);
  if (db_num_rows($queryloc1)) {
    $devant = ' ';
    while ($loc1 = db_fetch_object($queryloc1)) {
      $queryloc2 = db_query("SELECT c.id, l.id nid, l.nick, r.ssid, c.device_id, d.nick device_nick, a.ipv4 ip, l.lat, l.lon FROM {guifi_links} c LEFT JOIN {guifi_devices} d ON c.device_id=d.id LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id LEFT JOIN {guifi_location} l ON d.nid = l.id LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id = c.ipv4_id LEFT JOIN {guifi_radios} r ON d.id=r.id AND i.radiodev_counter=r.radiodev_counter WHERE c.id = %d AND c.device_id <> %d AND c.id NOT IN (%s)",$loc1->id,$loc1->device_id,implode(",",$listed));
//      $queryloc2 = db_query("SELECT c.id, l.id nid, l.nick, c.device_id, d.nick device_nick, c.id ip, l.lat, l.lon FROM {guifi_links} c LEFT JOIN {guifi_devices} d ON c.device_id=d.id LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id LEFT JOIN {guifi_location} l ON d.nid = l.id WHERE c.id = %d AND c.device_id <> %d AND c.id NOT IN (%s)",$loc1->id,$loc1->device_id,implode(",",$listed));
      $listed[] = $loc1->device_id;
      $devact = $loc1->device_nick;
      if ($loc1->ssid)
        $devact.= ' - '.$loc1->ssid;
      while ($loc2 = db_fetch_object($queryloc2)) {
        $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
        if ($gDist) {
          $total = $total + $gDist;
          $dAz = round($oGC->GCAzimuth($loc1->lat, $loc1->lon, $loc2->lat,$loc2->lon));
          // Calculo orientacio
          if ($dAz < 23) $dOr =t("N"); else
          if ($dAz < 68) $dOr =t("NE"); else
          if ($dAz < 113) $dOr =t("E"); else
          if ($dAz < 158) $dOr =t("SE"); else
          if ($dAz < 203) $dOr =t("S"); else
          if ($dAz < 248) $dOr =t("SW"); else
          if ($dAz < 293) $dOr =t("W"); else
          if ($dAz < 338) $dOr =t("NW"); else
            $dOr =t("N");
        }
        else
          $gDist = 'n/a';
        if ($loc1->nid <> $loc2->nid) {
          $cr = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id=%d",$loc2->device_id));
          if ($cr->count > 1) 
            $dname = $loc2->device_nick.'/'.$loc2->ssid;
          else
            $dname = $loc2->device_nick;
           
          $linkname = $loc1->id.'-'.'<a href=node/'.$loc2->nid.'>'.$loc2->nick.'</a> (<a href=guifi/device/'.$loc2->device_id.'>'.$dname.'</a>)';
        }
        else
          $linkname = $loc1->id.'-'.'<a href=guifi/device/'.$loc1->device_id.'>'.$loc1->device_nick.'</a>/<a href=guifi/device/'.$loc2->device_id.'>'.$loc2->device_nick.'</a>';

        $graph_url = guifi_node_get_url_mrtg($id,FALSE);
        if ($graph_url != NULL)
          $img_url = ' <img src='.$graph_url.'?device='.$loc2->device_id.'&type=availability&format=short>';
        else
          $img_url = NULL;

        if ($devant != $devact) {
          $devant = $devact;
          $rows[] = array(array('data'=> '<b><a href=/guifi/device/'.$loc1->device_id.'>'.$devact.'</a></b>','colspan'=>5));
        }
        $rows[] = array($linkname,
                     $loc1->ip.'/'.$loc2->ip, 
                   array('data' => t($loc1->flag).$img_url,
                                   'class' => $loc1->flag),
                   array('data' => $gDist,'class' => 'number'),
                   $dAz.'-'.$dOr);
      }
    }
    $output .= theme('table', $header, $rows);
    if ($total)
      $output .= t('Total:').'&nbsp;'.$total.'&nbsp;'.t('kms.');
    return $output;
  }
  return NULL;
}


?>
