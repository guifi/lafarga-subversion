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

  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_load()',
    $node);

  if (is_object($node)) {
    $k = $node->nid;
    if ($node->type != 'guifi_zone')
      return false;
  } else
  if (is_numeric($node))
    $k = $node;


  $loaded = db_fetch_object(
    db_query("
    SELECT * FROM {guifi_zone} WHERE id = '%d'",
    $k));
    
  if (($loaded->nick == '') or ($loaded->nick == null))
    $loaded->nick = guifi_abbreviate($loaded->title);
    
  // if zone map not set, take from parents
  if ( ($loaded->minx==0) and 
       ($loaded->miny==0) and 
       ($loaded->maxx==0) and 
       ($loaded->maxy==0) ) {
    $coords = guifi_zone_get_coords($loaded->master);

    $loaded->minx = $coords['minx'];
    $loaded->miny = $coords['miny'];
    $loaded->maxx = $coords['maxx'];
    $loaded->maxy = $coords['maxy'];
  }
     
  if ($loaded->id != null)
    return $loaded;

  return false;
}

function guifi_zone_get_coords($zid) {
  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_get_coords()',
    $zid);

  if (empty($zid))
    return FALSE;
    
  $coords = db_fetch_array(db_query(
    'SELECT master, minx, miny, maxx, maxy ' .
    'FROM {guifi_zone} ' .
    'WHERE id=%d',
    $zid));
  
  if ($coords['minx']==0 and $coords['miny']==0
      and $coords['maxx']==0 and $coords['maxy']==0)
    return guifi_zone_get_coords($coords['master']);
  else {
    unset($coords['master']);
    return $coords;
  }
}

function guifi_zone_root() {
  $root = db_fetch_object(db_query(
    "SELECT id " .
    "FROM {guifi_zone} " .
    "WHERE master = 0"));
  return $root->id;
}

function guifi_zone_select_field($zid,$fname) {
  $parents = array();
  $parent=$zid;
  $c = 1;
  while ($parent > 0) {
    $result = db_query('
      SELECT z.id zid, z.master master, z.title title
      FROM {guifi_zone} z
      WHERE z.id = %d',
      $parent);
    $row = db_fetch_object($result);
    $parent = $row->master;

    if ($row->zid == $zid) {
      $master = $parent;
      continue;
    }
    $parents[$row->zid] = $row->title;
    $c++;
  }

  $parents = array_reverse($parents,true);

  $lzones['0'] = t('(root zone)');
  $ident = $c;
  foreach ($parents as $k=>$value) {
    $lzones[$k] = str_repeat('-',($c+1)-$ident).$value;
    $ident--;
  }


  ob_start();
  print "<br>Zid: $zid Master: $master <br>";
  print_r($lzones);
  $txt = ob_get_clean();
  ob_end_clean();

  $has_peers = false;
  $has_childs = false;
  $qpeer = db_query(
    'SELECT z.id, z.title ' .
    'FROM {guifi_zone} z ' .
    'WHERE z.master=%d '.
    'ORDER BY z.title',
    $master);
  $qchilds = db_query(
    'SELECT z.id, z.title ' .
    'FROM {guifi_zone} z ' .
    'WHERE z.master=%d '.
    'ORDER BY z.title',
    $zid);

  while ($peer = db_fetch_object($qpeer)) {
    $lzones[$peer->id] = str_repeat('-',$c).$peer->title;
    if ($peer->id == $zid) {
      while ($child = db_fetch_object($qchilds)) {
        $has_childs = true;
        $lzones[$child->id] = str_repeat('-',$c+1).$child->title;
      }
    } else {
      $has_peers = true;
    }
  }

  $msg = t('Select to navigate through the available zones, only parent, peer and child zones are shown in the list.<br>By selecting any other zone, the list will be refreshed with the corresponding parents, peers and childs.');
  if ($has_childs)
    $msg .= '<br>'.t('<strong>Attention!</strong>: The currently selected zone has childs, click to view');

//  $msg .= $txt;

  if ($fname == 'master')
    $title = t('Parent zone');
  else
    $title = t('Zone');

  $var = explode(',',$fname);
  if (count($var)>1) {
    $zidFn = $var[0];
    $nidFn = $var[1];
  } else {
    $zidFn = $fname;
  }
//  $msg .= ("select zone zid: $zid, nid: $_POST[zid], fname: $fname, zidFn: $zidFn" .
//      " var[0]: $var[0] count(var): ".count($var));

  return array(
    '#type' => 'select',
    '#title' => $title,
    '#parents' => array($zidFn),
    '#default_value' => $zid,
    '#options' => $lzones,
    '#description' => $msg,
    '#prefix'=>'<div id="select-zone">',
    '#suffix'=>'</div>',
    '#ahah'=>array(
      'path' => 'guifi/js/select-zone/'.$fname,
      'wrapper' => 'select-zone',
      'method' => 'replace',
      'effect' => 'fade',
     ),
    );
//'#weight' => $form_weight++,

}

/** guifi_zone_form(): Present the guifi zone editing form.
 */
function guifi_zone_form(&$node, &$param) {
  guifi_log(GUIFILOG_TRACE,
    'function guifi_zone_form()',
    $node);

  drupal_set_breadcrumb(guifi_zone_ariadna($node->id));
  $form_weight = -20;

  $type = node_get_types('type',$node);

  if (($type->has_title)) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => check_plain($type->title_label),
      '#required' => true,
      '#default_value' => $node->title,
      '#weight' => $form_weight++,
    );
  }
  
  $form['jspath'] = array(
   '#type'=>'hidden',
   '#value'=> base_path().drupal_get_path('module','guifi').'/js/'
  );

  $form['master'] = guifi_zone_select_field($node->master,'master');
  $form['master']['#weight'] = $form_weight++;

  if (($type->has_body)) {
    $form['body_field'] = node_body_field(
      $node,
      $type->body_label,
      $type->min_word_count
    );
    $form['body_field']['#weight'] = $form_weight++;
  }

  // That's it for non-admin users, they don't need to edit more information
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
  $form['zone_mode'] = array(
    '#type' => 'select',
    '#title' => t('Zone dynamic mesh mode'),
    '#required' => TRUE,
    '#default_value' => $node->zone_mode,
    '#options' => drupal_map_assoc(array(t('infrastructure'),t('ad-hoc'))),
    '#description' => t('<ul><li>Select <strong>Infrastructure</strong> ' .
        'for traditional dynamic protocols in infrastructure mode ' .
        'like OSPF, BGP, etc. This mode is very much used on static nodes ' .
        'with known and permanent links already planned or backbones, ' .
        'point-to-point links...</li>' .
        '<li>Select <strong>Ad-hoc</strong> for dynamic mesh routing protocols ' .
        'like BATMAN or OLSR. This mode doesn\'t require planned ' .
        'and known links, and can grow spontaneously just by density. ' .
        'I.e. appropiated for networks deployed at street level ' .
        'in urban areas.</li></ul>'),
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

  // Service parameters
  $form['zone_services'] = array(
    '#type' => 'fieldset',
    '#title' => t('Zone services'),
    '#weight' => $form_weight++,
    '#collapsible' => false,
    '#collapsed' => TRUE,
  );

  $proxystr = guifi_service_str($node->proxy_id);

  function _service_descr($type) {
    return t('Select the default %type for to be used at this ' .
        'zone.<br>' .
        'You can find the %type by introducing part of the id number, ' .
        'zone name or proxy name. A list with all matching values ' .
        'with a maximum of 50 values will be created.<br>' .
        'You can refine the text to find your choice.',
        array('%type'=>$type));
  }

  $form['zone_services']['proxystr'] = array(
    '#type'=>'textfield',
    '#title'=>t('default proxy'),
    '#maxlength'=>60,
    '#default_value'=> $proxystr,
    '#autocomplete_path'=> 'guifi/js/select-service/proxy',
    '#element_validate' => array('guifi_service_name_validate',
      'guifi_zone_service_validate'),
    '#description'=>_service_descr('proxy')
  );
  $form['zone_services']['proxy_id'] = array(
    '#type'=>'hidden',
    '#value'=> $node->proxy_id,
  );

  $graphstr = guifi_service_str($node->graph_server);

  $form['zone_services']['graph_serverstr'] = array(
    '#type' => 'textfield',
    '#title' => t('default graphs server'),
    '#maxlength'=>60,
    '#required' => FALSE,
    '#default_value' => $graphstr,
    '#autocomplete_path'=> 'guifi/js/select-service/SNPgraphs',
    '#element_validate' => array('guifi_service_name_validate',
      'guifi_zone_service_validate'),
    '#description'=>_service_descr('graph server')
  );
  $form['zone_services']['graph_server'] = array(
    '#type'=>'hidden',
    '#value'=> $node->graph_server,
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

function guifi_zone_service_validate($element, &$form_state) {
  switch ($element['#name']) {
    case 'proxystr':
      $s = &$form_state['values']['proxy_id']; break;
    case 'graph_serverstr':
      $s = &$form_state['values']['graph_server']; break;
  }
  switch ($element['#value']) {
  case t('No service'):
    $s = '-1';
    break;
  case t('Take from parents'):
    $s = '';
    break;
  default:
    $nid = explode('-',$element['#value']);
    $s = $nid[0];
  }
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
  $output  = '<form><input type="hidden" id="minx" value="'.$node->minx.'"/>';
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
    form_set_value($element,$emails,$form_state);
}

function guifi_zone_validate($node) {
  
  // if node master is root, check that there is not another zone as zoot
  if ($node->master == 0) {
      $qry = db_query(
           'SELECT id, title, nick
            FROM {guifi_zone}
            WHERE master = 0');
     while ($rootZone = db_fetch_object($qry))
     {
        if ($node->nid != $rootZone->id)
          form_set_error('master',
            t('The root zone is already set to "%s". Only one root zone can be present at the database. Delete/change the actual root zone before assigning a new one or choose another partent.',
              array('%s'=>$rootZone->title)));
     }
  }
  
  // check that master is not being assigned to itself
  if (!empty($node->nid))
  if ($node->master == $node->nid)
    form_set_error('master',
      t("Master zone can't be set to itself"));
  
  // check that zone area is consistent
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
  $log = '';

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
  $log = '';

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
  $log = '';

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
/** guifi_zone_get_parents(): Get the guifi zone parents
 */
function guifi_zone_get_parents($id) {

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
function guifi_zone_ariadna($id = 0, $link = 'node/%d') {
  $ret = array();
$ret[] = l(t('Home'), NULL);
  foreach (array_reverse(guifi_zone_get_parents($id)) as $parent)
  if ($parent > 0) {
    $parentData = db_fetch_array(db_query(
      'SELECT z.id, z.title ' .
      'FROM {guifi_zone} z ' .
      'WHERE z.id = %d ',
      $parent));
    $ret[] = l($parentData['title'],sprintf($link,$parentData['id']));
  }
  $ret[count($ret)-1] = '<b>'.$ret[count($ret)-1].'</b>';

  $child = array();
  $query = db_query('SELECT z.id, z.nick, z.title ' .
      'FROM {guifi_zone} z ' .
      'WHERE z.master = %d ' .
      'ORDER BY z.weight, z.title',
      $id);
  while ($zoneChild = db_fetch_array($query)) {
    $child[] = l($zoneChild['nick'],sprintf($link,$zoneChild['id']),
      array(
        'attributes'=>array('title'=>$zoneChild['title'])
      ));
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
    $rows[] = array(t('changes notified to (visible only if you have privileges)'),'<a href="mailto:'.$zone->notification.'">'.$zone->notification.'</a>');
  $rows[] = array(t('default proxy'),
              l(guifi_service_str($zone->proxy_id),
                guifi_zone_get_service($zone,'proxy_id',true))
              );
  $rows[] = array(t('default graph server'),
              l(guifi_service_str($zone->graph_server),
                guifi_zone_get_service($zone,'graph_server',true))
              );
  $rows[] = array(t('network global information').':',null);
  $rows[] = array(t('Mode'),$zone->zone_mode);
  $rows[] = array(t('DNS Servers'),$zone->dns_servers);
  $rows[] = array(t('NTP Servers'),$zone->ntp_servers);
  $rows[] = array(t('OSPF zone'),$zone->ospf_zone);
  $tz = db_fetch_object(db_query("SELECT description FROM {guifi_types} WHERE type = 'tz' AND text = '%s'",$zone->time_zone));
  $rows[] = array(t('Time zone'),$tz->description);
  $rows[] = array(t('log information').':',null);
  if ($zone->timestamp_created > 0)
    $rows[] = array(t('created by'),l($name_created->name,'user/'.$zone->user_created) .'&nbsp;' .t('at') .'&nbsp;' .format_date($zone->timestamp_created));
  if ($zone->timestamp_changed > 0)
    $rows[] = array(t('updated by'),l($name_changed->name,'user/'.$zone->user_changed) .'&nbsp;' .t('at') .'&nbsp;' .format_date($zone->timestamp_changed));

  return array_merge($rows);
}

function guifi_zone_get_service($id, $type ,$path = false) {
  if (is_numeric($id))
    $z = guifi_zone_load($id);
  else
    $z = $id;

  $ret = null;
  if (!empty($z->$type))
    $ret = $z->$type;
  else
    if ($z->master)
      $ret = guifi_zone_get_service($z->master,$type);

  if ($path)
    if ($ret)
      $ret = 'node/'.$ret;

  return $ret;
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

  $result = db_query(
    "SELECT status_flag, count(*) total " .
    "FROM {guifi_location} l " .
    "WHERE l.zone_id in (%s) " .
    "GROUP BY status_flag",
    implode(',',$zones));
  while ($sum = db_fetch_object($result)) {
    $summary[$sum->status_flag] = $sum->total;
    $summary['Total'] = $summary['Total'] + $sum->total;
  }

  return $summary;
}

/** guifi_zone_nodes(): list nodes of a given zone and its childs
*/
function guifi_zone_nodes($node,$embeded = false) {

  if (!isset($node->id))
    $node->id=$node->nid;

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
    $summary = guifi_zone_totals(guifi_zone_childs($zone->id));
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

function guifi_zone_childs($zid) {
  return array_keys(guifi_zone_childs_tree($zid,9999));
}


function guifi_zone_childs_tree($parents, $maxdepth = 1, $depth = 0) {
 
  if (is_numeric($parents)) 
    $parents = array($parents=>array('depth'=>0,'master'=>0));
  guifi_log(GUIFILOG_TRACE,'function guifi_zone_childs_tree(childs)',$parents);
    
  // check only current depth
  $current_depth = array();
  foreach ($parents as $k=>$v) {
    if ($v['depth'] == $depth)
      $current_depth[] = $k;
  }
  $depth++;

  $result = db_query('SELECT z.id, z.master ' .
                     'FROM {guifi_zone} z ' .
                     'WHERE z.master IN ('.implode(',',$current_depth).')');
  
  $childs = $parents;
  $found = false;                   
  while ($child = db_fetch_object($result)) { 
    $childs[$child->id] = array('depth'=>$depth,'master'=>$child->master);
    $found = true;
  }
  
  if ($found and ($depth < $maxdepth))
    $childs = guifi_zone_childs_tree($childs,$maxdepth,$depth);
 
  return $childs;
}

function guifi_zone_availability($zone,$desc = "all") {
  guifi_log(GUIFILOG_TRACE,'function guifi_zone_availability()',$zone);
  
  function _guifi_zone_availability_devices($nid) {
    $qry  = db_query( 
      'SELECT d.id did, d.nick dnick, d.flag dflag ' .
      'FROM {guifi_devices} d ' .
      'WHERE d.type = "radio" ' .
      '  AND d.nid=%d ' .
      'ORDER BY d.nick',
      $nid);
    
    $rows = array();
    
    while ($d = db_fetch_array($qry)) {
      $dev = guifi_device_load($d['did']);
      
      if (guifi_device_access('update',$dev))
        $edit = 
          l(guifi_img_icon('edit.png'),'guifi/device/'.$d['did'].'/edit',
            array(
              'html'=>true,
              'title' => t('edit device'),
              'attributes'=>array('target'=>'_blank'))).
          l(guifi_img_icon('drop.png'),'guifi/device/'.$d['did'].'/delete',
            array(
              'html'=>true,
              'title' => t('delete device'),
              'attributes'=>array('target'=>'_blank')));
      else
        $edit = null;
            
      $ip = guifi_main_ip($d['did']);
      $graph_url = guifi_graphs_get_radio_url($d['did'],FALSE);
      if ($graph_url != NULL)
        $img_url = ' <img src='.$graph_url.'?device='.$d['did'].'&type=availability&format=long>';
      else
        $img_url = null;
        
      $rows[] = array(
        array('data'=>
          $edit.l($d['dnick'],'guifi/device/'.$d['did'])
             ),
        array(
          'data'=>l($ip['ipv4'].'/'.$ip['maskbits'],
          guifi_device_admin_url($d['did'],$ip['ipv4']),
          array('attributes'=>array('title'=>t('Connect to the device on a new window'),
            'target'=>'_blank'))),
          'align'=>'right'
        ),
        array('data'=>$d['dflag'].$img_url,'class'=>$d['dflag'])
      );
    }
    guifi_log(GUIFILOG_TRACE,'function guifi_zone_availability_device()',$rows);
        
    return $rows;  
  }
  
  drupal_set_breadcrumb(guifi_zone_ariadna($zone->id,'node/%d/view/availability'));
  $childs = guifi_zone_childs($zone->id);
  $childs[] = $zone->id;
  
  switch ($desc) {
    case t('pending'):
      $msg = t('Pending/to review');
      $qstatus = "Working"; 
      break;
    case t('all'):
      $msg = t('Availability');
      $qstatus = "all";
  }
  
  $output = '<h2>' .$msg.' @  ' .$zone->title .'</h2>';
  
  $sql = 
    'SELECT z.id zid, z.title ztitle, z.nick znick, ' .
    '  l.id nid, l.nick nnick, l.status_flag nstatus ' .
    'FROM {guifi_zone} z, {guifi_location} l ' .
    'WHERE z.id=l.zone_id ' .
    '  AND l.status_flag != "'.$qstatus.'"' .
    '  AND z.id IN ('.implode(',',$childs).') '.
    'ORDER BY z.title, z.id, l.nick';
  guifi_log(GUIFILOG_TRACE,'function guifi_zone_availability()',$sql);
  
  $Msql = pager_query($sql,variable_get("guifi_pagelimit", 50));
  
  $rows = array();
  $currZ = -1;
  while ($d = db_fetch_array($Msql)) {
    if ($currZ != $d['zid']) {
      $rows[] = array(array('data'=>
        l($d['znick'],'node/'.$d['zid']
          ).' <strong>'.
        $d['ztitle'].'</strong>',
        'colspan'=>0
      ));
      $currZ = $d['zid'];
    }
    $drows = _guifi_zone_availability_devices($d['nid']);
    
    $nsr = count($drows);
    if (empty($nsr))
      $nsr = 1;
      
    if (guifi_node_access('update',$d['nid']))
      $edit = 
        l(guifi_img_icon('edit.png'),'node/'.$d['nid'].'/edit',
          array('html'=>true,'attributes'=>array('target'=>'_blank'))).
        l(guifi_img_icon('drop.png'),'node/'.$d['nid'].'/delete',
          array('html'=>true,'attributes'=>array('target'=>'_blank')));
    else
      $edit = null;
    
    $rows[] = array(
      array('data'=>$d['nid'],
       'align'=>'right',
       'rowspan'=>$nsr),
      array('data'=> $edit.
        l($d['nnick'],'node/'.$d['nid'],
          array('attributes'=>array('target'=>'_blank'))),
       'rowspan'=>$nsr),
      array('data'=>$d['nstatus'],
       'class'=>$d['nstatus'],
       'rowspan'=>$nsr)        
    );
    end($rows);
    $krow = key($rows);
      
    if (count($drows)) {
      $rows[$krow] = array_merge($rows[$krow],array_shift($drows));
     
      foreach ($drows as $k2 => $v)
        $rows[] = $v;
    }
        
  }
  
  
//  $rows[] = array(guifi_zone_availability_recurse($node));
  $output .= theme('table', null, $rows,array('width'=>'100%'));
  $output .= theme_pager(null, variable_get("guifi_pagelimit", 50));
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
    $node->content['table']= array(
    '#value' =>
      theme_table(null,array(
          array(theme_table(null,array(array(array('data'=>'<small>'.guifi_zone_print($node->nid).'</small>','width'=>'50%'),
                                             array('data'=>guifi_zone_simple_map($node),'width'=>'50%'))))),
 //         array(guifi_zone_print($node->nid)),
          array(guifi_zone_nodes($node,true))
        ),array('width'=>'100%')
      ),
     '#weight' => 1,
      );

    return $node;
  }

}

/** Miscellaneous utilities related to zones
**/

/** guifi_zones_listbox(): Creates a list of the zones
**/

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
  $result = db_query(
    'SELECT z.id, z.title, z.master, z.weight ' .
    'FROM {guifi_zone} z ' .
    'ORDER BY z.weight, z.title');

  while ($zone = db_fetch_object($result)) {
    if (!isset($children[$zone->master])) {
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
