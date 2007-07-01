<?php
// $Id: guifi.module x$

/**
 * @file
 * Manage guifi_node 
 */

/**
 * Implementation of hook_access().
 */
function guifi_node_access($op, $node) {
  global $user; 
  if ($op == 'create') {
    return user_access('create guifi nodes');
  }

  if ($op == 'update') {
    if ((user_access('administer guifi zones')) || ($node->uid == $user->uid)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}


/**
 * zone editing functions
**/

/**
 * Present the guifi zone editing form.
 */
function guifi_node_form(&$node) {
  global $user;
  
  // A partir d'ara l'ordre el definirem per aquesta variable.
  // Així ens estalviem canviar-ho tot cada cop que inserim un nou element.
  $form_weight = -20;
  
  // ----
  // El títol el primer de tot
  // ------------------------------------------------
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#required' => TRUE,
    '#default_value' => $node->title,
    '#weight' => $form_weight++,
  );
  
  
  // ----
  // Comprovacions i petits canvis
  // ------------------------------------------------
  
  //  print "Nid: ".$node->nid." title: ".$node->title." GetLat: ".$_GET['Lat'];
  if ( (empty($node->nid)) and (is_numeric($node->title)) ) {
    $zone = guifi_get_zone($node->title);
    $node->zone_id = $node->title;
    $default = t('<nodename>');
    $node->title = null;
    $node->nick = $zone->nick.$default;
  }
  if (empty($node->nid)) {
    $node->lat = $_GET['Lat'];
    $node->lon = $_GET['Lon'];
    $node->contact = $user->mail;
    $node->status_flag = 'Planned';
    
    $form['license'] = array(
      '#type' => 'item',
      '#title' => t('License and usage agreement'),
      '#value' => variable_get('guifi_license',null),
      '#description' => t('You must accept this agreement to be authorized to create new nodes.'),
      '#weight' => $form_weight++,
    );
    $form['agreement']= array(
      '#type' => 'radio',
      '#title' => t('Yes, I have read this and accepted'),
      '#default_value' => 'Yes',
      '#weight' => $form_weight++,
    );
  } else
    $form['agreement']= array(
      '#type' => 'hidden',
      '#default_value' => 'Yes',
      '#weight' => $form_weight++,
    );
  
  
  // ----
  // Dades del node
  // ------------------------------------------------
  $form['nick'] = array(
    '#type' => 'textfield',
    '#title' => t('Nick'),
    '#required' => TRUE,
    '#size' => 20,
    '#maxlength' => 20, 
    '#default_value' => $node->nick,
    '#description' => t("Unique identifier for this node. Avoid generic names such 'MyNode', use something that really identifies your node.<br />Short name, single word with no spaces, 7-bit chars only, will be used for  hostname, reports, etc.") . ($error['nick'] ? $error["nick"] : ''),
    '#weight' => $form_weight++,
  );
  $form['contact'] = array(
    '#type' => 'textfield',
    '#title' => t('Contact'),
    '#required' => FALSE,
    '#size' => 60,
    '#maxlength' => 128, 
    '#default_value' => $node->contact,
    '#description' => t("Who did possible this node or who to contact with regarding this node if it is distinct of the owner of this page.") . ($error['contact'] ? $error["contact"] : ''),
    '#weight' => $form_weight++,
  );
  $form['zone_id'] = array(
    '#type' => 'select',
    '#title' => t("Zone"),
    '#required' => FALSE,
    '#default_value' => $node->zone_id,
    '#options' => guifi_zones_listbox(),
    '#description' => t('The zone where this node where this node belongs to.'),
    '#weight' => $form_weight++,
  );
  
  
  // Position
  if ($node->lat != NULL) {
    $node->latdeg = floor($node->lat);
    $node->latmin = (($node->lat - floor($node->lat)) * 60);
    $node->latseg = round(($node->latmin - floor($node->latmin)) * 60,4);
    $node->latmin = floor($node->latmin);
  }
  if ($node->lon != NULL) {
    $node->londeg = floor($node->lon);
    $node->lonmin = (($node->lon - floor($node->lon)) * 60);
    $node->lonseg = round(($node->lonmin - floor($node->lonmin)) * 60,4);
    $node->lonmin = floor($node->lonmin);
  }
  
  $form['position'] = array(
    '#type' => 'fieldset',
    '#title' => t('Position'),
    '#weight' => $form_weight++,
  );
  $form['position']['longitude'] = array(
    '#type' => 'item',
    '#title' => t('Longitude'),
    '#value' => '<input type="text" name="edit[londeg]" size="12" maxlength="24" value="' .  $node->londeg .'"/> ' . '<input type="text" name="edit[lonmin]" size="12" maxlength="24" value="'. $node->lonmin .'"/> ' . '<input type="text" name="edit[lonseg]" size="12" maxlength="24" value="'. $node->lonseg .'"/>"',
    '#weight' => $form_weight++,
  );
  $form['position']['latitude'] = array(
    '#type' => 'item',
    '#title' => t('Latitude'),
    '#value' => '<input type="text" name="edit[latdeg]" size="12" maxlength="24" value="' .  $node->latdeg .'"/> ' . '<input type="text" name="edit[latmin]" size="12" maxlength="24" value="'. $node->latmin .'"/> ' . '<input type="text" name="edit[latseg]" size="12" maxlength="24" value="'. $node->latseg .'"/>"',
    '#description' => t('Latitude &#038; Longitude: positive means EAST/NORTH, negative WEST/SOUTH.<br />If you provide data in decimal, leave the following fields empty and a conversion will be made.'),
    '#weight' => $form_weight++,
  );
  $form['position']['zone_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Zone description'),
    '#required' => FALSE,
    '#size' => 60,
    '#maxlength' => 128, 
    '#default_value' => $node->zone_description,
    '#description' => t("Zone, address, neighborhood. Something that describes your area within your location.<br />If you don't know your lat/lon, please provide street and number or crossing street.") . ($error['zone'] ? $error["zone"] : ''),
    '#weight' => $form_weight++,
  );

  $form['elevation'] = array(
    '#type' => 'textfield',
    '#title' => t('Antenna elevation'),
    '#required' => FALSE,
    '#size' => 20,
    '#maxlength' => 20, 
    '#default_value' => $node->elevation,
    '#description' => t("Antenna height over the floor level.") . ($error['elevation'] ? $error["elevation"] : ''),
    '#weight' => $form_weight++,
  );
  
  // Si ets administrador pots definir el servidor de dades
  if (user_access('administer guifi zones')) 
    $form['graph_server'] = array(
      '#type' => 'select',
      '#title' => t("Server which collects traffic and availability data"),
      '#required' => FALSE,
      '#default_value' => ($node->graph_server ? $node->graph_server : 0),
      '#options' => array('0'=>t('Default'),'-1'=>t('None')) + guifi_services_select('SNPgraphs'),
      '#description' => t("If not specified, inherits zone properties."),
      '#weight' => $form_weight++,
    );
  
  $form['stable'] = array(
    '#type' => 'select',
    '#title' => t("It's supposed to be a stable online node?"),
    '#required' => FALSE,
    '#default_value' => ($node->stable ? $node->stable : 'Yes'),
    '#options' => array('Yes' => t('Yes, is intended to be kept always on,  avalable for extending the mesh'), 'No' => t("I'm sorry. Will be connected just when I'm online")),
    '#description' => t("That helps while planning a mesh network. We should know which locations are likely available to provide stable links."),
    '#weight' => $form_weight++,
  );
  
  $form['body'] = array(
    '#type' => 'textarea', 
    '#title' => t('Body'), 
    '#default_value' => $node->body, 
    '#cols' => 60, 
    '#rows' => 20, 
    '#required' => TRUE,
    '#description' => t("Textual description of the wifi") . ($error['body'] ? $error['body'] : ''),
    '#weight' => $form_weight++,
  );
  // Això no sé benbé què és
  //  $output .= implode("", taxonomy_node_form("wifi", $node));
  $form['status_flag']= array(
    '#type' => 'hidden',
    '#default_value' => $node->status_flag,
    '#weight' => $form_weight++,
  );
  
  return $form;
  
  
  
  
  
  // *** Codi vell. Per esborrar

  // Position
  if ($node->lat != NULL) {
    $node->latdeg = floor($node->lat);
    $node->latmin = (($node->lat - floor($node->lat)) * 60);
    $node->latseg = round(($node->latmin - floor($node->latmin)) * 60,4);
    $node->latmin = floor($node->latmin);
  }
  if ($node->lon != NULL) {
    $node->londeg = floor($node->lon);
    $node->lonmin = (($node->lon - floor($node->lon)) * 60);
    $node->lonseg = round(($node->lonmin - floor($node->lonmin)) * 60,4);
    $node->lonmin = floor($node->lonmin);
  }
  $degminsec = form_item(t('Longitude'),
                '<input type="text" name="edit[londeg]" size="12" maxlength="24" value="'. $node->londeg .'"/> '
                .'<input type="text" name="edit[lonmin]" size="12" maxlength="24" value="'. $node->lonmin .'"/> '
                .'<input type="text" name="edit[lonseg]" size="12" maxlength="24" value="'. $node->lonseg .'"/>"'
                , NULL, NULL, FALSE);
  $degminsec .= form_item(t('Latitude'),
                '<input type="text" name="edit[latdeg]" size="12" maxlength="24" value="'. $node->latdeg .'"/> '
                .'<input type="text" name="edit[latmin]" size="12" maxlength="24" value="'. $node->latmin .'"/> '
                .'<input type="text" name="edit[latseg]" size="12" maxlength="24" value="'. $node->latseg .'"/>"'
                , t('Latitude &#038; Longitude: positive means EAST/NORTH, negative WEST/SOUTH.<br />If you provide data in decimal, leave the following fields empty and a conversion will be made.'), NULL, FALSE);
  $degminsec .= form_textfield(t("Zone description"), "zone_description", $node->zone_description, 60, 128, t("Zone, address, neighborhood. Something that describes your area within your location.<br />If you don't know your lat/lon, please provide street and number or crossing street.") . ($error['zone'] ? $error["zone"] : ''));
  $output .= form_group('Position',$degminsec,null);
  $output .= form_textfield(t("Antenna elevation"), "elevation", $node->elevation, 20, 20, t("Antenna height over the floor level.") . ($error['elevation'] ? $error["elevation"] : ''));

  if (user_access('administer guifi zones')) $output .= form_select(t("Server which collects traffic and availability data"), "graph_server", ($node->graph_server ? $node->graph_server : 0), array('0'=>t('Default'),'-1'=>t('None')) + guifi_services_select('SNPgraphs'), t("If not specified, inherits zone properties."));

  $stable_types = array('Yes' => t('Yes, is intended to be kept always on,  avalable for extending the mesh'),
                        'No' => t("I'm sorry. Will be connected just when I'm online"));

  $output .= form_select(t("It's supposed to be a stable online node?"), "stable", ($node->stable ? $node->stable : 'Yes'), $stable_types, t("That helps while planning a mesh network. We should know which locations are likely available to provide stable links."));

//  $output .= implode("", taxonomy_node_form("wifi", $node));

  $output .= form_textarea(t("Body"), "body", $node->body, 60, 20, t("Textual description of the wifi") . ($error['body'] ? $error['body'] : ''));
  
  $output .= form_hidden('status_flag',$node->status_flag);

  return $output;
}

/**
 * Confirm that an edited guifi item has fields properly filled in.
 */
function guifi_edit_node_validate(&$node) {
  guifi_validate_nick($node->nick);

  if ($node->agreement != 'Yes')
      form_set_error('agreement', t('You must read and accept the license &#038; terms and conditions to be allowed to create nodes.'));

  if (!empty($node->nick)) { 
    $query = db_query("SELECT nick FROM {guifi_location} WHERE lcase(nick)='%s' AND id <> %d",strtolower($node->nick),$node->nid);
    if (db_num_rows($query))
      form_set_error('nick', t('Nick already in use.'));
  }

  // not at root zone
  if (($node->zone_id == 0) && (!empty($node->nick))) {
    form_set_error('zone_id', t('Can\'t be assigned to root zone, please assign the node to an appropiate zone.'));
  }
  
  if ($node->elevation == 0)
    $node->elevation = NULL;
  if (($node->elevation < -1) && ($node->elevation != NULL))
    form_set_error('elevation', t('Elevation must be above the floor! :)'));
  if (($node->elevation > 100) && ($node->elevation != NULL))
    form_set_error('elevation', t('Do you mean that you are flying over the earth??? :)'));

  if ($node->latdeg != NULL)
        $node->lat = $node->latdeg;
  if ($node->latmin != NULL)
        $node->lat = $node->lat + ($node->latmin / 60);   if ($node->latseg != NULL)
        $node->lat = $node->lat + ($node->latseg / 3600);   if ($node->londeg != NULL)
        $node->lon = $node->londeg;
  if ($node->lonmin != NULL)
        $node->lon = $node->lon + ($node->lonmin / 60);
  if ($node->lonseg != NULL)
        $node->lon = $node->lon + ($node->lonseg / 3600);

  if ($node->lat == 0)
    $node->lat = NULL;
  if ($node->lon == 0)
    $node->lon = NULL;

  if (($node->lat == NULL) and ($node->lon == NULL)) {
    form_set_error('lon', t('Please provide lon/lat information for the node. You can obtain by using the maps and clicking over the point where the node is located.'));
  if (($node->lat > 180) or ($node->lat < -180))
    form_set_error('lat', t('Latitude must be between -180 and 180 degrees.'));
  if (($node->lon > 90) or ($node->lon < -90))
    form_set_error('lon', t('Longitude must be between -90 and 90 degrees.'));
  }

}

/**
 * Save changes to a guifi item into the database.
 */

function guifi_node_insert($node) {
  global $user;

  db_query("INSERT INTO {guifi_location} ( id, zone_id, zone_description, nick, lat, lon, elevation, graph_server, contact, status_flag, stable, timestamp_created, user_created) VALUES (%d, %d, '%s', '%s', %.10f, %.10f, %d, %d, '%s', '%s', '%s',  %d, %d)", $node->nid, $node->zone_id, $node->zone_description, $node->nick, $node->lat, $node->lon, $node->elevation, $node->graph_server, $node->contact, $node->status_flag, $node->stable, time(), $user->uid);


  // Refresh maps
//  touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());

  cache_clear_all();
}

function guifi_node_update($node) {
  global $user;

  // Refresh maps?
  $pn = db_fetch_object(db_query('SELECT * FROM {guifi_location} l WHERE l.id=%d',$node->nid));  
  if (($pn->lat != $node->lat) || ($pn->lon != $node->lon) || ($pn->status_flag != $node->status_flag)) {
//    touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
    variable_set('guifi_refresh_cnml',time());
    variable_set('guifi_refresh_maps',time());

    cache_clear_all();
  }

  db_query("UPDATE {guifi_location} SET zone_id = %d, zone_description = '%s', nick = '%s', lat = %.10f, lon = %.10f, elevation = %d, graph_server = '%s', contact = '%s', status_flag = '%s', stable = '%s', timestamp_changed = %d, user_changed = %d WHERE id = %d", $node->zone_id, $node->zone_description, $node->nick, $node->lat, $node->lon, $node->elevation, $node->graph_server, $node->contact, $node->status_flag, $node->stable, time(), $user->uid, $node->nid);

}

/**
 * outputs the zone information data
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
    $rows[] = array(t('created by'),$name_created->name,format_date($node->timestamp_created)); 
  else
    $rows[] = array(t('created by'),$name_created->name,null); 
  if ($node->timestamp_changed > 0) 
    $rows[] = array(t('last update'),$name_changed->name,format_date($node->timestamp_changed)); 
  return array_merge($rows);
}

/**
 * outputs the node information
**/
function guifi_node_view(&$node) {
  
  $output = '<div id="guifi">';
  $output .= guifi_zone_ariadna($node->zone_id);

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
      $output .= theme('table', array(), guifi_node_print_data($node));
      if ($op == 'data') break;
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
      $output .= guifi_node_distances($node->nid);
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

  $node->body .= theme('box', t('node information'), $output);

  if ($op != 'default')
    print theme('page',$output,t('node').': '.$node->title.' ('.t($op).')');
}

function guifi_node_radio_list($id = 0) {
  
  $header = array('<h2>'.t('device').'</h2>', t('type'), t('status'),t('available'),t('last'));

  // Form for adding a new device
  $form = guifi_device_create_form($id);

  $query = db_query("SELECT d.id FROM {guifi_devices} d WHERE nid=%d",$id);
  if (db_num_rows($query))
  while ($d = db_fetch_object($query)) {
     $device = guifi_get_device($d->id);
     $status_str = guifi_availabilitystr($device);
     if (guifi_device_access('update',$device[id])) {
       $edit_radio = "<td><form method=get name=\"radio form edit\" action=\"guifi/device/" .$device[id] ."/edit\">";
       $edit_radio .= form_hidden('id',$device[id]);
       $edit_radio .= form_button(t('Edit'),'Submit');
       $edit_radio .= "</form><td/>";

     }
     if ($device->variable['firmware'] != "n/d") {
       $unsolclic = '<td><a href="guifi/device/' .$device[id] .'/view/unsolclic" title="' .t("Get radio configuration with singleclick") .'">' .$device[variable]['firmware'] .'</a></td>';
     }
     $ip = guifi_main_ip($device[id]);
//     print_r($ip);
     $graph_url = guifi_node_get_url_mrtg($id,FALSE);
     if ($graph_url != NULL)
       $img_url = ' <img src='.$graph_url.'?device='.$device['id'].'&type=availability&format=short>';
     else
       $img_url = NULL;
     $rows[] = array('<a href=guifi/device/'.$device[id].'>'.$device[nick].'</a>',$device[type],
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

function guifi_node_distances($id) {

  $node = node_load($id);

  drupal_set_title(t('distances from').' '.$node->nick);


  // deso la lat/lon d'origen
  $lat1 = $node->lat;
  $long1 = $node->lon;
  
  // deso el nom del node d'origen per anomenar els perfils
  $node1 = $node->nick;  

  // Vaig a llistar els nodes i la calcular la distacia
  $result = db_query("SELECT id, lat, lon, nick, status_flag  FROM {guifi_location} WHERE id !=%d AND (lat != '' AND lon != '') AND (lat != 0 AND lon != 0)",$id);

  $oGC = new GeoCalc();
  $nodes = array();
  $rows = array();
  $totals[] = NULL;

  while ($node = db_fetch_array($result)) {

     // Calculo la distancia, nomes llista si es < 100 qms.
     $distance = round($oGC->EllipsoidDistance($lat1, $long1, $node["lat"], $node["lon"]),3);
     if ($distance <  variable_get('guifi_max_distance',25))
       $nodes[] = array_merge(array('distance'=>$distance),$node);
  }
  asort($nodes);

  $header = array(t('Site'), t('Kms.'), t('degrees'), t('Direction'),t('Status'),t('Heights'));
//    $output .= "<br />\n<h2>" .t("Report of distances to other locations, from closest to farest, limited at ") .variable_get('wifi_max_distance',25) ." " .t("Kms.") ."</h2><table>";
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
    
    $height_url = "modules/guifi/guifi_heights.php?x1=".$UTMnode1[0]."&y1=".$UTMnode1[1]."&x2=".$UTMnode2[0]."&y2=".$UTMnode2[1]."&node1=".$node1."&node2=".$node["nick"]."&width=640&height=320";

    $rows[] = array(
                    "<a href=/node/" .$node["id"] .">" .$node["nick"]."</a>",
                    $node['distance'],
                    $dAz,
                    $dOr,
                    array('data'=>$node['status_flag'],'class'=>$node['status_flag']),
                    "<a href=".$height_url.">".t('show heights')."</a>",  
                   );  
    $totals[$node['status_flag']]++;
    $totals[0]++;
  } // eof while distance < max:distance
  // Totals:
  $output = theme('table', $header, $rows);
  $header = array(t('Totals'),$totals[0]);
  $rows = array();
  foreach ($totals as $key => $value) {
    if ($key) 
      $rows[] = array(t($key),array('data'=>$value,'class'=>$key));
  }
  $output .= theme('table', $header, $rows);
  return $output;

  $output .= "<tr><td>&nbsp;</td></tr><tr><td class=\"poblacio\">Totals:</td><td class=\"poblacio\">" .$totals[0] ."</td></tr>";
  while (list($key, $val) = each($totals)) {
    if ($key)
      $output .= "<tr>" ._wifi_state_class($key) .t($key) ."</td>"
               ._wifi_state_class($key) .$totals[$key] ."</td></tr>\n";
  }
}

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

function guifi_node_add($id) {
  $zone = guifi_get_zone($id);
  // Set the defaults for a node of this zone
  // Callback to node/guifi-node/add
  drupal_goto('node/add/guifi-node?edit[title]='.$zone->id);
}


?>
