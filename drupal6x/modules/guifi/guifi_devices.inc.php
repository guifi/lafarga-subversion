<?php
/**
 * @file
 * Manage guifi_devices 
 */

/*
 * guifi_device_load(): get a device and all its related information and builds an array 
 */
function guifi_device_load($id,$ret = 'array') {
  guifi_log(GUIFILOG_FULL,'function guifi_device_load()');
  
  $device = db_fetch_array(db_query('
    SELECT d.*
    FROM {guifi_devices} d
    WHERE d.id = %d',
    $id));
  if (empty($device)) {
    drupal_set_message(t('Device (%num) does not exist.',array('%num'=>$id)));
    return;
  }
  if (!empty($device['extra'])) 
    $device['variable'] = unserialize($device['extra']);
  else
    $device['variable'] = array();
 
  // getting device radios
  if ($device['type'] == 'radio') {
    // Get radio
    $qr = db_query('
      SELECT *
      FROM {guifi_radios}
      WHERE id = %d
      ORDER BY id, radiodev_counter',
      $id);

    $device['firewall'] = false; // Default: No firewall

    while ($radio = db_fetch_array($qr)) {
      
      if (!$device['firewall'])
        if ($radio['mode'] == 'client')
           $device['firewall'] = true;

      $device['radios'][$radio['radiodev_counter']] = $radio;

      // get interface
      $qi = db_query('
        SELECT *
        FROM {guifi_interfaces}
        WHERE device_id=%d AND radiodev_counter=%d
        ORDER BY id, interface_type, radiodev_counter',
        $device['id'],
        $radio['radiodev_counter']);
      while ($i = db_fetch_array($qi)) {
        if ($device['radios'][$radio['radiodev_counter']]['mac'] == '')
          $device['radios'][$radio['radiodev_counter']]['mac'] = $i['mac'];
        $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']] = $i;

        // get ipv4
        $ipdec = array();
        $iparr = array();
        $qa = db_query('
          SELECT *
          FROM {guifi_ipv4}
          WHERE interface_id=%d',
          $i['id']);
        
        while ($a = db_fetch_array($qa)) {
          $ipdec[$a['id']] = _dec_addr($a['ipv4']);
          $iparr[$a['id']] = $a;
        }
        asort($ipdec); 
       
        foreach($ipdec as $ka=>$foo) {
          $a = $iparr[$ka];
          $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']] = $a;
          // get linked devices
          $qlsql = sprintf('
            SELECT l2.*
            FROM {guifi_links} l1
              LEFT JOIN {guifi_links} l2 ON l1.id=l2.id
            WHERE l2.device_id != %d
              AND l1.device_id=%d
              AND l1.interface_id=%d
              AND l1.ipv4_id=%d',
            $id,
            $id,
            $i['id'],
            $a['id']);
          $ql = db_query($qlsql);

          $ipdec2 = array();
          $iparr2 = array();
          while ($l = db_fetch_array($ql)) {
            $qrasql = sprintf('
              SELECT *
              FROM {guifi_ipv4}
              WHERE id=%d
                AND interface_id=%d',
              $l['ipv4_id'],
              $l['interface_id']);
            $qra = db_query($qrasql);

            while ($ri = db_fetch_array($qra)) {
              $rinterface = db_fetch_array(db_query('
                SELECT *
                FROM {guifi_interfaces}
                WHERE id=%d',
                $l['interface_id']));
              $ipdec2[$l['id']] = _dec_addr($ri['ipv4']);
              $rinterface['ipv4']=$ri;
              $l['interface']=$rinterface;
              $iparr2[$l['id']] = $l;
            }
          } // each link
          
          asort($ipdec2);
          foreach ($ipdec2 as $ka2=>$foo) {
            $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$iparr2[$ka2]['id']] = $iparr2[$ka2];
          }
        }
      }
    }
  } 

  // getting other interfaces
  $qi = db_query('
    SELECT *
    FROM {guifi_interfaces}
    WHERE device_id=%d
      AND (radiodev_counter is NULL
      OR interface_type NOT IN ("wLan","wds/p2p","Wan","Hotspot"))
    ORDER BY interface_type, id',
    $id);
    
  while ($i = db_fetch_array($qi)) {
    $device['interfaces'][$i['id']] = $i;

    // get ipv4
    $ipdec = array();
    $iparr = array();
    $qa = db_query('
      SELECT *
      FROM {guifi_ipv4}
      WHERE interface_id=%d',
      $i['id']);
    while ($a = db_fetch_array($qa)) {
      $ipdec[$a['id']] = _dec_addr($a['ipv4']);
      $iparr[$a['id']] = $a;
    }
    
    asort($ipdec); 
      
    foreach($ipdec as $ka=>$foo) {
      $a = $iparr[$ka];
      $device['interfaces'][$i['id']]['ipv4'][$a['id']] = $a;
    }
    // get linked devices
    $ql = db_query('
      SELECT l2.*
      FROM {guifi_links} l1
        LEFT JOIN {guifi_links} l2 ON l1.id=l2.id
      WHERE l1.link_type NOT IN ("ap/client","wds/p2p")
        AND l1.device_id=%d
        AND l1.interface_id=%d
        AND l2.device_id!=%d',
      $id,
      $i['id'],
      $id);
    while ($l = db_fetch_array($ql)) {
      $ipdec2 = array();
      $iparr2 = array();
      $qra = db_query('
        SELECT *
        FROM {guifi_ipv4}
        WHERE id=%d
          AND interface_id=%d',
        $l['ipv4_id'],
        $l['interface_id']);
      while ($ra = db_fetch_array($qra)) {
        $ipdec2[$ra['id']] = _dec_addr($ra['ipv4']);
        $lr = $l;
        $lr['interface'] = db_fetch_array(db_query('
          SELECT *
          FROM {guifi_interfaces}
          WHERE id=%d',
          $l['interface_id']));
        $lr['interface']['ipv4'] = $ra;
        $iparr2[$ra['id']] = $lr;
      }
      
      asort($ipdec2);
      
      foreach ($ipdec2 as $ka2=>$foo)
        $device['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$l['id']] = $iparr2[$ka2];
    }
  }

  if ($ret == 'array')
    return $device;
  else {
    foreach ($device as $k => $field)
      $var->$k = $field;
    return array2object($device);
  }
}


/*
 * Device edit funcions
 * guifi_device_form_submit(): Performs submit actions
 */
function guifi_device_form_submit($form, &$form_state) {

  guifi_log(GUIFILOG_TRACE,'function guifi_device_form_submit()',
    $form_state);
  
  if ($form_state['values']['id'])
  if (!guifi_device_access('update',$form_state['values']['id']))
  {
    drupal_set_message(t('You are not authorized to edit this device','error'));
    return;
  }

  // Take the appropiate actions 
  switch ($form_state['clicked_button']['#value']) {
  case t('Reset'):
    drupal_set_message(t('Reset was pressed, ' .
        'if there was any change, was not saved and lost.' .
        '<br>The device information has been reloaded ' .
        'from the current information available at the database'));
    drupal_goto('guifi/device/'.$form_state['values']['id'].'/edit');
    break;
  case t('Save & continue edit'):
  case t('Save & exit'):
    // save
//    print_r($_POST);
//    print_r($form_state['values']);
//    exit;
    $id = guifi_device_save($form_state['values']);
//    exit;
    if ($form_state['clicked_button']['#value'] == t('Save & exit'))
      drupal_goto('guifi/device/'.$id);
    drupal_goto('guifi/device/'.$id.'/edit');
    break;
  default:
//     drupal_set_message(t('Warning: The will be active only for this session. To confirm the changes you will have to press the save buttons.'));
    guifi_log(GUIFILOG_BASIC,
      'exit guifi_device_form_submit without saving...',$form_state['clicked_button']['#value']);
    return;
  }

}


/* guifi_device_form(): Present the guifi device main editing form. */
function guifi_device_form($form_state, $params = array()) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_device_form()',$form_state);
  
  guifi_validate_js("#guifi-device-form");

  // $form['#attributes'] = array('onsubmit' => 'kk');  
  if (empty($form_state['values']))
    $form_state['values'] = $params;
    
  $form_state['#redirect'] = FALSE;

  // if new device, initializing variables
  if (($form_state['values']['nid'] == null) && ($params['add'] != null)) {
    $form_state['values']['nid'] = $params['add'];
    $form_state['values']['new'] = true;
    $form_state['values']['type'] = $params['type'];
    $form_state['values']['links'] = array();
    $form_state['values']['netmask'] = '255.255.255.224';
    if ($form_state['values']['type'] == 'radio') {
      $form_state['values']['variable']['firmware'] = 'DD-guifi';
      $form_state['values']['variable']['model_id'] = '16';
    }
  }
  
  // Check permissions
  if ($params['edit']){
    if (!guifi_device_access('update',$params['edit'])){
      drupal_set_message(t('You are not authorized to edit this device','error'));
      return;
    }
  }
 
  // Loading node where the device belongs to (some information will be used)
  $node = node_load(array('nid'=>$form_state['values']['nid']));

  // Setting the breadcrumb
  drupal_set_breadcrumb(array(l($node->title,
    'node/'.$form_state['values']['nid']),
    l($form_state['values']['nick'],'guifi/device/'.
      $form_state['values']['id'])));

  // if contact is null, then get it from the node or the user logged in drupal
  if (is_null($form_state['values']['notification']))
    if (guifi_notification_validate($node->notification)) {
      $form_state['values']['notification'] = $node->notification;
    } else {
      drupal_set_message(t('The node has not a valid email address as a contact. Using your email as a default. Change the contact mail address if necessary.'));
      $form_state['values']['notification'] = $user->mail;
    }

  // if nick is null, get a default name 
  if ($form_state['values']['nick'] == "") {
    $zone = node_load($node->zone_id);
    $devs = db_fetch_object(db_query("
      SELECT count(*) count
      FROM {guifi_devices}
      WHERE type = '%s'
        AND nid = %d",
      $form_state['values']['type'],$form_state['values']['nid']));
    $form_state['values']['nick'] = 
      $node->nick.ucfirst(guifi_trim_vowels($form_state['values']['type'])).
                                            ($devs->count + 1);
  }

  if (isset($form_state['action'])) {
    guifi_log(GUIFILOG_TRACE,'action',$form_state['action']);
    if (function_exists($form_state['action'])) {
      if (!call_user_func_array($form_state['action'],
        array(&$form,&$form_state)))
          return $form;     
    }
  }

  $form_weight = -20;
  
  if ($form_state['values']['id'])
    $form['id'] = array(
      '#type'=>'hidden',
      '#name'=>'id',
      '#value'=> $form_state['values']['id']
    ); 
  else
    $form['new'] = array(
      '#type'=>'hidden',
      '#name'=>'new',
      '#value'=> TRUE
    );
  $form['type'] = array(
    '#type'=>'hidden',
    '#name'=>'type',
    '#value'=> $form_state['values']['type']
  );
  
  

//  guifi_form_hidden($form,$form_state['values']);

  if ($params['add'] != null){
    drupal_set_title(t('adding a new device at %node',array('%node' => $node->nick)));
  } else {
    drupal_set_title(t('edit device %dname',array('%dname' => $form_state['values']['nick'])));
  } 

  // All preprocess is complete, now going to create the form

  $form['main'] = array(
    '#type' => 'fieldset',
    '#title' => t('Device name, status and main settings').' ('.
      $form_state['values']['nick'].') - '.$form_state['values']['flag'],
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#collapsed' => (is_null($params['edit'])),
  );
  /*
  $form['main']['movenode'] = array(
    '#type'=>'image_button',
    '#src'=>drupal_get_path('module', 'guifi').'/icons/movenode.png',
    '#attributes'=>array('title'=>t('Move device to another node')),
    '#prefix'=>'<div id="select-zone">', 
    '#ahah' => array(
      'path' => 'guifi/js/select-zone/zid,nid',
      'wrapper' => 'select-zone',
      'method' => 'replace',
      'effect' => 'fade',
     ),
  );
  */
  $form['main']['movenode'] = array(
    '#type'=>'textfield',
    '#title'=>t('Node'),
    '#maxlength'=>60,
    '#default_value'=>$form_state['values']['nid'].'-'.
        guifi_get_zone_nick(guifi_get_zone_of_node(
          $form_state['values']['nid'])).', '.
        guifi_get_nodename($form_state['values']['nid']),
    '#autocomplete_path'=> 'guifi/js/select-node',
    '#element_validate' => array('guifi_nodename_validate'),
    '#description'=>t('Select the node where the device is.<br>' .
        'You can find the node by introducing part of the node id number, ' .
        'zone name or node name. A list with all matching values ' .
        'with a maximum of 50 values will be created.<br>' .
        'You can refine the text to find your choice.')
  );
  /*
  $form['main']['zid'] = array(
    '#parents' => array('zid'),
    '#type'=>'hidden',
    '#value'=> guifi_get_zone_of_node($form_state['values']['nid']),
  );
  $form['main']['node_description'] = array(
    '#type'=>'item',
    '#value'=>guifi_get_nodename($form_state['values']['nid']),
    '#description'=>guifi_get_zone_nick(guifi_get_zone_of_node(
       $form_state['values']['nid'])),
        '#ahah' => array(
      'path' => 'guifi/js/select-zone/zid,nid',
      'wrapper' => 'select-zone',
      'method' => 'replace',
      'effect' => 'fade',
    ),    
    
  );
  */
  $form['main']['nid'] = array(
    '#type'=>'hidden',
    '#value'=> $form_state['values']['nid'],
    //'#suffix'=>'</div>'
  );
  
  $form['main']['nick'] = array(
    '#type' => 'textfield',
    '#size' => 20,
    '#maxlength' => 128,
    '#title' => t('nick'),
    '#required' => TRUE,
    '#attributes' => array('class'=>'required'),
    '#default_value' => $form_state['values']['nick'],
    '#description' =>  t('The name of the device.<br />Used as a hostname, SSID, etc...')
  );
  $form['main']['flag'] = array(
      '#type' => 'select',
      '#title' => t("Status"),
      '#required' => TRUE,
      '#default_value' => $form_state['values']['flag'],
      '#options' => guifi_types('status'),
      '#description' => t("Current status of this device."),
   );
  $form['main']['notification'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => TRUE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $form_state['values']['notification'],
    '#description' =>  t('Mailid where changes on the device will be notified, if many, separated by \',\'<br />used for network administration.')
  );
  if (user_access('administer guifi zones')
       and $form_state['values']['type'] == 'radio') {
    $form['main']['graph_server'] = array(
      '#type' => 'select',
      '#title' => t("Server which collects traffic and availability data"),
      '#required' => FALSE,
      '#default_value' => ($node->graph_server ? $node->graph_server : 0),
      '#options' => array('0'=>t('Default'),'-1'=>t('None')) + guifi_services_select('SNPgraphs'),
      '#description' => t("If not specified, inherits zone properties."),
    );
  }

  // create the device-type depenedent form
  // looking for a "guifi_"<device_type>"_form()" function 
  if (function_exists('guifi_'.$form_state['values']['type'].'_form')){
    $form = array_merge($form,
      call_user_func('guifi_'.$form_state['values']['type'].'_form',
        $form_state['values'],
        $form_weight));
  }

  // Cable interfaces/links
//  guifi_interfaces_form($form['if'],$form_state['values'],$form_weight = 2);

  // Comments
  $form_weight = 200;
  
  $form['comment'] = array(
    '#type' => 'textarea',
//    '#parents' => 'comment',
    '#title' => t('Comments'),
    '#default_value' => $form_state['values']['comment'],
    '#description' => t('This text will be displayed as an information of the device.'),
    '#cols' => 60,
    '#rows' => 5,
    '#weight' => $form_weight++,
  );
  
  //  save/validate/reset buttons
  $form['dbuttons'] = guifi_device_buttons(false,'',$form_weight);

  return $form;
}

/* guifi_device_form_validate(): Confirm that an edited device has fields properly filled. */
function guifi_device_form_validate($form,&$form_state) {
//  print "Hola validate!!\n<br>";
//   print_r($edit);

  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_device_form_validate()',$form_state);
  
//   guifi_log(GUIFILOG_NONE,'function guifi_device_form_validate()',$form);
   
  // nick
  if (isset($form['main']['nick'])) {
    guifi_validate_nick($form_state['values']['nick']);

    $query = db_query("
      SELECT nick
      FROM {guifi_devices}
      WHERE lcase(nick)=lcase('%s')
       AND id <> %d",
      strtolower($form_state['values']['nick']),
      $form_state['values']['id']);
    
    while (db_fetch_object($query)) {
      form_set_error('nick', t('Nick already in use.'));      
    }
  }

  // ssid
  if (empty($form_state['values']['ssid'])) {
    $form_state['values']['ssid'] = $form_state['values']['nick'];
  }

  // duplicated ip address
  if (!empty($form_state['values']['ipv4'])) {
    if (db_num_rows(db_query("
      SELECT i.id
      FROM {guifi_interfaces} i,{guifi_ipv4} a
      WHERE i.id=a.interface_id AND a.ipv4='%s'
        AND i.device_id != %d",
      $form_state['values']['ipv4'],
      $form_state['values']['id']))) {
      $message = t('IP %ipv4 already taken in the database. Choose another or leave the address blank.',
        array('%ipv4' => $form_state['values']['ipv4']));
      form_set_error('ipv4',$message);
    }
  }

  // Validates the mac address
  // radio MACs
  /*
  if (isset($edit['radios'])) foreach ($edit[radios] as $radio_id=>$radio) 
  if (!empty($radio['mac'])) {
    $mac = _guifi_validate_mac($radio['mac']);
    if ($mac) {
      $edit[radios][$radio_id]['mac'] = $mac;
      if ($edit[radios][$radio_id][interfaces]) foreach ($edit[radios][$radio_id][interfaces] as $k=>$foo)
        $edit[radios][$radio_id][interfaces][$k][mac]=$mac;
    } else {
      form_set_error('radios]['.$radio_id.'][mac',t('Error in MAC address, use 00:00:00:00:00:00 format.').' '.$radio['mac']);
    }
  }
  */


  // callback to device specific validation routines if there are
  if (function_exists('guifi_'.$form_state['values']['type'].'_validate'))
    call_user_func('guifi_'.$form_state['values']['type'].'_validate',$form_state['vaues'],$form);

  guifi_links_validate($form_state['values'],$form);
}

/* functions to save interfaces, old code to be removed, for reference {
function guifi_save_interfaces($edit,$var,$rc_old = null,$rc_new = null,&$to_mail) {
  global $bridge;

  guifi_log(GUIFILOG_TRACE,'function guifi_save_interfaces ()');
  $log = null;

  // processing all interfaces (loop)
  if (isset($var['interfaces'])) foreach ($var['interfaces'] as $interface_id=>$interface) {
     guifi_log(GUIFILOG_TRACE,t('interface (%id) %type-%mac to be processed.',array('%id'=>$interface_id,'%type'=>$interface['interface_type'],'%mac'=>$interface['mac'])));
     guifi_log(GUIFILOG_FULL,'interface: ',$interface);

     // if deleted
     if ($interface['deleted']) {
       $log .= t('interface (%id) %type-%mac deleted.\n',array('%id'=>$interface_id,'%type'=>$interface['interface_type'],'%mac'=>$interface['mac']));
       continue;
     }

     // check for bridge
     if ($bridge) {
       if  ($interface['interface_type'] == 'wLan/Lan') {
         $interface['interface_type'] = 'wLan';
         $log .= t('bridge wLan/Lan already present, switching to wLan\n');
       } 
     } else {
       if (in_array($interface['interface_type'],array('wLan/Lan','wLan'))) {
         $bridge = true;
         if ($interface['interface_type'] == 'wLan') {
           $interface['interface_type'] = 'wLan/Lan';
           $log .= t('creating wLan/Lan bridge using this wLan\n');
         }
       }
     }

     // new, get a new interface_id
     if ($interface['new']) {
       $new_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_interfaces}'));
       $interface['id']=$new_id['id'];
       $log .= t('interface (%id) %type-%mac created.\n',array('%id'=>$interface['id'],'%type'=>$interface['interface_type'],'%mac'=>$interface['mac']));
     } else
       $log .= t('interface (%id) %type-%mac updated.\n',array('%id'=>$interface['id'],'%type'=>$interface['interface_type'],'%mac'=>$interface['mac']));
  
     // interface SQL insert
     if (is_numeric($rc_new)) 
       // is a radio interface, use radiodev_counter 
       db_query('INSERT INTO {guifi_interfaces} (id, device_id, radiodev_counter, interface_type, mac) VALUES (%d, %d, %d, "%s", "%s")',$interface['id'],$edit['id'],$rc_new,$interface['interface_type'],$interface['mac']);
     else
       // is NOT a radio interface, radiodev_counter is NULL
     db_query('INSERT INTO {guifi_interfaces} (id, device_id, interface_type, mac) VALUES (%d, %d, "%s", "%s")',$interface['id'],$edit['id'],$interface['interface_type'],$interface['mac']);

     // processing ipv4 addresses
     if (isset($interface['ipv4'])) foreach ($interface['ipv4'] as $ipv4_id=>$ipv4) {
       if ($ipv4['deleted']) {
         $log .= t('ipv4 %ipv4/%mask deleted.\n',array('%ipv4'=>$ipv4['ipv4'],'%mask'=>$ipv4['netmask']));
         continue;
       }

       if ($ipv4['new']) {
         // ???? is this really working???
         $qryIDs = db_query('SELECT max(a.id) + 1 id FROM {guifi_ipv4} a, {guifi_interfaces} i WHERE a.interface_id=i.id AND i.id=%d',$interface['id']);
         $id = db_fetch_array($qryIDs);
         if (is_null($id['id']))
           $nextID = 0;
         else
           $nextID = $id['id'];

         $ipv4['id'] = $nextID;
         $log .= t('ipv4 %id %ipv4/%mask created.\n',array('%id'=>$ipv4['id'],'%ipv4'=>$ipv4['ipv4'],'%mask'=>$ipv4['netmask']));

       } else
         $log .= t('ipv4 %id %ipv4/%mask updated.\n',array('%id'=>$ipv4['id'],'%ipv4'=>$ipv4['ipv4'],'%mask'=>$ipv4['netmask']));

       // ipv4 SQL insert
       db_query('INSERT INTO {guifi_ipv4} (id, interface_id, ipv4, netmask) VALUES (%d, %d, "%s", "%s")',$ipv4_id,$interface['id'],$ipv4['ipv4'],$ipv4['netmask']);

       // processing links
       if (isset($ipv4['links'])) foreach ($ipv4['links'] as $link_id=>$link) {
         guifi_log(GUIFILOG_TRACE,t('processing link'),$link);
         if ($link['deleted']) {
           continue;
         }

         if ($link['new']) {
           $new_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_links}'));
           $link['id'] = $new_id['id'];
           $log .= t('link with %hostname created.\n',array('%hostname'=>guifi_get_hostname($link['device_id'])));
         } else
           $log .= t('link with %hostname updated.\n',array('%hostname'=>guifi_get_hostname($link['device_id'])));

         // Insert local link
         db_query('INSERT INTO {guifi_links} (id, nid, device_id, interface_id, ipv4_id, link_type, routing, flag) VALUES (%d, %d, %d, %d, %d, "%s", "%s", "%s")',
           $link['id'],$edit['nid'],$edit['id'],
           $interface['id'],$ipv4['id'],
           $link['link_type'], $link['routing'],$link['flag']);

         // Insert remote link
         db_query('INSERT INTO {guifi_links} (id, nid, device_id, interface_id, ipv4_id, link_type, routing, flag) VALUES (%d, %d, %d, %d, %d, "%s", "%s", "%s")',
           $link['id'],$link['nid'],$link['device_id'],
           $link['interface']['id'],$link['interface']['ipv4']['id'],
           $link['link_type'], $link['routing'],$link['flag']);

         // UPSERT remote ipv4 
         $qexists = db_query("SELECT * FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d",
                            $link['interface']['ipv4']['id'],
                            $link['interface']['id']);
         $row = db_num_rows($qexists);
         if ($row == 1) 
           db_query("UPDATE {guifi_ipv4} SET ipv4='%s', netmask='%s' WHERE id=%d AND interface_id=%d",
                     $link['interface']['ipv4']['ipv4'],
                     $link['interface']['ipv4']['netmask'],
                     $link['interface']['ipv4']['id'],
                     $link['interface']['id']);
         else 
           db_query("INSERT INTO {guifi_ipv4} (id,interface_id,ipv4,netmask) VALUES (%d, %d, '%s', '%s')",
                    $link['interface']['ipv4']['id'],
                    $link['interface']['id'],
                    $link['interface']['ipv4']['ipv4'],
                    $link['interface']['ipv4']['netmask']);
 
         $log .= t('ipv4 %id %ipv4/%mask upserted (for remote interface).\n',array('%id'=>$link['interface']['ipv4']['id'],'%ipv4'=>$link['interface']['ipv4']['ipv4'],'%mask'=>$link['interface']['ipv4']['netmask']));
       } // foreach link
     } // foreach ipv4 address
  } // foreach interface

  return $log;
}
function guifi_save_interfaces2($edit,$var,$rc_old = null,$rc_new = null, $cascade = false) {

      unset($deletes);

      // Updating interfaces
      if (isset($var[interfaces])) foreach ($var[interfaces] as $interface_id=>$interface) {

        guifi_log(GUIFILOG_FULL,sprintf('interface (%d) %s-%s to be processed.',$interface_id,$interface[interface_type],$interface[mac]),$interface);
        guifi_log(GUIFILOG_BASIC,sprintf('interface (%d) %s-%s to be processed.',$interface_id,$interface[interface_type],$interface[mac]));

        if (($interface['deleted']) or ($cascade)) {
            $cascade=true;
            if ($interface['deleted'])
              $cascade_interface=true;
            $deletes[] = sprintf('DELETE FROM {guifi_interfaces} WHERE id=%d',$interface_id);
        } else 
        if ($interface['new'])  {
          $new_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_interfaces}'));
          $interface[id]=$new_id[id];
         
          if (is_numeric($rc_old)) 
            db_query('INSERT INTO {guifi_interfaces} (id, device_id, radiodev_counter, interface_type, mac) VALUES (%d, %d, %d, "%s", "%s")',$interface[id],$edit[id],$rc_new,$interface[interface_type],$interface[mac]);
          else
            db_query('INSERT INTO {guifi_interfaces} (id, device_id, interface_type, mac) VALUES (%d, %d, "%s", "%s")',$interface[id],$edit[id],$interface[interface_type],$interface[mac]);

        } else {
          if (is_numeric($rc_old)) {
            db_query('UPDATE {guifi_interfaces} SET radiodev_counter=%d, interface_type="%s",mac="%s" WHERE id=%d',$rc_new,$interface[interface_type],$interface[mac],$interface_id);
          } else
            db_query('UPDATE {guifi_interfaces} SET interface_type="%s",mac="%s" WHERE id=%d',$interface[interface_type],$interface[mac],$interface_id);
        }

        // Updating interface ipv4
        if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id=>$ipv4) {
          if (($ipv4['deleted']) or ($cascade)) {
//            print "Delete ipv4: ".$ipv4[delete].' cascade: '.$cascade.' ipv4_id: '.$ipv4[id].' interface_id: '.$ipv4[interface_id]."\n<br />";
            $cascade=true;
            if ($ipv4['deleted'])
              $cascade_ipv4=true;
            if (!$ipv4['new'])
              $deletes[] = sprintf('DELETE FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',$ipv4[id],$ipv4[interface_id]);
          } else 
          if ($ipv4['new']) 
            db_query('INSERT INTO {guifi_ipv4} (id, interface_id, ipv4, netmask) VALUES (%d, %d, "%s", "%s")',$ipv4_id,$interface[id],$ipv4[ipv4],$ipv4[netmask]);
          else {
            db_query('UPDATE {guifi_ipv4} SET ipv4="%s", netmask="%s" WHERE id=%d AND interface_id=%d',$ipv4[ipv4],$ipv4[netmask],$ipv4[id],$interface[id]);
            guifi_log(GUIFILOG_FULL,sprintf('ipv4 uptaded: %s/%s',$ipv4['ipv4'],$ipv4['netmask']));
          }
          // Update links (rocal & remote)
          if (isset($ipv4[links])) foreach ($ipv4[links] as $link_id=>$link) {
            if (($link['deleted']) or ($cascade)) {
              $cascade=true;
              if ($link['deleted'])
                $cascade_links=true;
              if (!$link['new']) {
                db_query('DELETE FROM {guifi_links} WHERE id=%d',$link_id);
                // if link type was WDS, delete cascade remote interface/ipv4
                switch ($link[link_type])   {
                  case 'ap/client': 
                    if ($interface[interface_type] == 'Wan') 
                      break;
//                    else
//                      $deletes[] = sprintf('DELETE FROM {guifi_interfaces} WHERE id=%d',$link[interface_id]);
                  case 'wds': 
                    $deletes[] = sprintf('DELETE FROM {guifi_ipv4} WHERE id=%d and interface_id=%d',$link[ipv4_id],$link[interface_id]);
                }
              }
            } else {
              if ($link['new']) {

                // Getting the linked device
                if ($link['linked'])
                  list($link['nid'],$link['device_id'],$link['interface']['radiodev_counter']) =  explode(',',$link['linked']);;
                // if WDS, getting a new ipv4 id over an existing interface
                if ($link['link_type'] == 'wds') {
                  $ipIDs = array();
                  $qryIDs = db_query('SELECT a.id FROM {guifi_ipv4} a, {guifi_interfaces} i WHERE a.interface_id=i.id AND i.interface_type="wds/p2p" AND i.device_id=%d AND i.radiodev_counter=%d',$link['device_id'],$rc_old);
                  while ($id = db_fetch_array($qryIDs))
                    $ipIDs[] = $id['id'];
                  $nextID = 0;
                  while (in_array($nextID,$ipIDs))
                    $nextID = $nextID + 1;
                  $link['ipv4_id']=$nextID;
                } else {
                  $link['ipv4_id']=$link['interface']['radiodev_counter'];
                }
              }

              // that's a remote interface. The remote interface might exist, so first we have to check if is already on the database
              if ($link['interface'][radiodev_counter] >= 0) {
                $sql = sprintf('SELECT id FROM {guifi_interfaces} WHERE device_id=%d and radiodev_counter=%d and interface_type="%s"',$link[device_id],$link['interface'][radiodev_counter],$link['interface'][interface_type]);
              } else {
                $sql = sprintf('SELECT id,radiodev_counter FROM {guifi_interfaces} WHERE device_id=%d and interface_type="%s"',$link[device_id],$link['interface'][interface_type]);
              }
              $qremotei = db_query($sql);
              $remote_interface = db_fetch_array($qremotei);
              guifi_log(GUIFILOG_TRACE,sprintf('Remote link: '),$link);
              guifi_log(GUIFILOG_TRACE,sprintf('sql'),$sql);
              guifi_log(GUIFILOG_TRACE,sprintf('remote_interface'),$remote_interface);

              if ($link['new']) {

                $lnew = db_fetch_array(db_query('SELECT max(id)+1 newid FROM {guifi_links}'));
                db_query('INSERT INTO {guifi_links} (id, nid, device_id, interface_id, ipv4_id, link_type, routing, flag) VALUES (%d, %d, %d, %d, %d, "%s", "%s", "%s")',$lnew[newid],$edit[nid],$edit[id],$interface[id],$ipv4_id,$link[link_type],$link[routing], $link[flag]);

//              if ($link['interface']['new']) {
                if (($link['interface']['new']) and ($remote_interface[id] == null)) {
                  guifi_log(GUIFILOG_BASIC,sprintf('Creating remote link for: '),$link);
                  // There was no interface on the database, so going to  create it
                  $inew = db_fetch_array(db_query('SELECT max(id)+1 newid FROM {guifi_interfaces}'));
                  $link[interface_id] = $inew[newid];
                  if ($link['interface'][radiodev_counter] != null)
                    db_query('INSERT INTO {guifi_interfaces} (id,device_id, radiodev_counter, interface_type, mac) VALUES (%d, %d, %d, "%s", "%s")',$link[interface_id],$link[device_id],$link['interface'][radiodev_counter],$link['interface'][interface_type],$link['interface'][mac]);
                  else
                    db_query('INSERT INTO {guifi_interfaces} (id,device_id, interface_type, mac) VALUES (%d, %d, "%s", "%s")',$link[interface_id],$link[device_id],$link['interface'][interface_type],$link['interface'][mac]);
                } else {
                  if ($remote_interface[id] != null) {
                    // Probably a new ip address might be added/updated, so need to know to which interface
                    $link[interface_id] = $remote_interface[id];
                  }
                  guifi_log(GUIFILOG_BASIC,sprintf('Link updated'),$link);
                }
               
//              }


                if ($link['interface'][ipv4]['new']) {
                  db_query('INSERT INTO {guifi_ipv4} (id, interface_id, ipv4, netmask) VALUES (%d, %d, "%s", "%s")',$link[ipv4_id],$link[interface_id],$link['interface'][ipv4][ipv4],$link['interface'][ipv4][netmask]);
                }

              db_query('INSERT INTO {guifi_links} (id, nid, device_id, interface_id, ipv4_id, link_type, routing, flag) VALUES (%d, %d, %d, %d, %d, "%s", "%s", "%s")',$lnew[newid],$link[nid],$link[device_id],$link[interface_id],$link[ipv4_id],$link[link_type],$link[routing],$link[flag]);

              } else {
                db_query('UPDATE {guifi_links} SET ipv4_id=%d, link_type="%s", routing="%s", flag="%s" WHERE id=%d and interface_id=%d',$ipv4[id],$link[link_type],$link[routing], $link[flag],$link[id],$interface_id);
                db_query('UPDATE {guifi_links} SET ipv4_id=%d, link_type="%s", routing="%s", flag="%s" WHERE id=%d and interface_id=%d',$link[ipv4_id],$link[link_type],$link[routing], $link[flag],$link[id],$link[interface_id]);

                if ($link['link_type'] == 'cable') {
                  // Updtating remote interface_type when link type is cable
                  if ($link['interface'][radiodev_counter] != null) 
                    db_query('UPDATE {guifi_interfaces} SET radiodev_counter=%d, interface_type="%s" WHERE id=%d',$link['interface'][radiodev_counter],$link['interface'][interface_type],$link['interface_id']);
                  else
                    db_query('UPDATE {guifi_interfaces} SET interface_type="%s" WHERE id=%d',$link['interface'][interface_type],$link['interface_id']);
                }
  
                db_query('UPDATE {guifi_ipv4} SET ipv4="%s", netmask="%s" WHERE id=%d AND interface_id=%d',$link['interface'][ipv4][ipv4],$link['interface'][ipv4][netmask],$link[ipv4_id],$link[interface_id]);
              
              }
          
            } // remote interface
            if ($cascade_links)
              $cascade=false;
            unset($cascade_links);
          } // update links 
          if ($cascade_ipv4)
            $cascade=false;
          unset($cascade_ipv4);
        } // Update ipv4
        if ($cascade_interface)
          $cascade=false;
        unset($cascade_interface);
      } // Update interface


  // purge orphan data
  if (count($deletes) > 0) foreach ($deletes as $delete) {
    guifi_log(GUIFILOG_BASIC,sprintf('DELETE while editing %d: %s',$edit['id'],$delete));
    db_query($delete);
  }

}
** } end of code to be removed */

/* guifi_device_edit_save(): Save changes/insert devices */
function guifi_device_save($edit, $verbose = true, $notify = true) {
  global $user;
  global $bridge;

  $bridge = false;
  $to_mail = array();
  $tomail[] = $user->mail;
  $log = "";
  $to_mail = array();

  // device
  $edit['extra'] = serialize($edit['variable']);
  $ndevice = _guifi_db_sql('guifi_devices',array('id'=>$edit['id']),$edit,$log,$to_mail);
  
  guifi_log(GUIFILOG_TRACE,
    sprintf('device saved:'),
    $ndevice);
    
  $movenode = explode('-',$edit['movenode']);
  
  // radios
  $rc = 0;
  if (is_array($edit['radios']))
    ksort($edit['radios']);
  $rc = 0;
  if ($edit['radios']) foreach ($edit['radios'] as $radiodev_counter=>$radio) {
    $radio['id'] = $ndevice['id'];
    $radio['radiodev_counter'] = $rc;
    $radio['nid']=$movenode[0];
    $radio['model_id']=$edit['variable']['model_id'];
    $nradio = _guifi_db_sql('guifi_radios',array('id'=>$radio['id'],'radiodev_counter'=>$radiodev_counter),$radio,$log,$to_mail);
    if (empty($nradio)) 
      continue;

    // interfaces
    if ($radio['interfaces']) foreach ($radio['interfaces'] as $interface_id=>$interface) {
      $interface['device_id'] = $ndevice['id'];
      $interface['mac'] = $radio['mac'];
      $interface['radiodev_counter'] = $nradio['radiodev_counter'];
        
    // force wLan/Lan on radio#0
    if ($interface['interface_type'] == 'wLan/Lan')
      $interface['radiodev_counter'] = 0;

      $log .= guifi_device_interface_save($interface,$interface_id,$ndevice['nid'],$to_mail);

    } // foreach interface
    $rc++;
  } // foreach radio

  if (!empty($edit['interfaces'])) foreach ($edit['interfaces'] as $iid => $interface) {
    $interface['device_id'] = $ndevice['id'];
    $interface['mac'] = $radio['mac'];

    $log .= guifi_device_interface_save($interface,$iid,$ndevice['id'],$to_mail);
  }

  $to_mail = explode(',',$edit['notification']);
  
  if ($edit['new'])
    $subject = t('The device %name has been CREATED by %user.',
      array('%name' => $edit['nick'],
        '%user' => $user->name));
  else
    $subject = t('The device %name has been UPDATED by %user.',
      array('%name' => $edit['nick'],
        '%user' => $user->name));
  
//   drupal_set_message($subject);
  guifi_notify($to_mail,
    $subject,
    $log,
    $verbose,
    $notify);

  guifi_set_node_flag($edit['nid']);
    
  return $ndevice['id'];

}

function guifi_device_interface_save($interface,$iid,$nid,&$to_mail) {
  $log = '';


  guifi_log(GUIFILOG_TRACE,sprintf('guifi_device_edit_interface_save (id=%d)',$iid),$interface);
  
  $ninterface = _guifi_db_sql(
    'guifi_interfaces',
    array('id'=>$iid),$interface,$log,$to_mail);
  if (!isset($ninterface['id']))
    $ninterface['id'] = $iid;

  if (empty($ninterface))
    return $log;

  guifi_log(GUIFILOG_TRACE,'SQL interface',$ninterface);
  // ipv4
  if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id=>$ipv4) {
    $ipv4['interface_id'] = $ninterface['id'];
    guifi_log(GUIFILOG_TRACE,sprintf('SQL ipv4 local (id=%d, iid=%d)',
      $ipv4_id,$ipv4['interface_id']),
      $ipv4);
    $nipv4 = _guifi_db_sql(
      'guifi_ipv4',
      array('id'=>$ipv4_id,'interface_id'=>$ipv4['interface_id']),$ipv4,$log,$to_mail);
    if (empty($nipv4))
      continue;

    // links (local)
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id => $link) {
      $llink = $link;
      $llink['nid'] = $nid;
      $llink['device_id'] = $interface['device_id'];
      $llink['interface_id'] = $ninterface['id'];
      $llink['ipv4_id'] = $nipv4['id'];
      guifi_log(GUIFILOG_TRACE,'going to SQL for local link',$llink);
      $nllink = _guifi_db_sql(
        'guifi_links',
        array('id'=>$link['id'],'device_id'=>$interface['device_id']),$llink,$log,$to_mail);
      if (empty($nllink) or ($llink['deleted']))
        continue;

      // links (remote)
      if ($link['interface'])
        $rinterface = _guifi_db_sql(
          'guifi_interfaces',
          array('id'=>$link['interface']['id'],
            'radiodev_counter'=>$link['interface']['radiodev_counter']),
            $link['interface'],$log,$to_mail);
      if ($link['interface']['ipv4']) {
        if ($ipv4['netmask'] != $link['interface']['ipv4']['netmask']) {
          $log .= t('Netmask on remote link %nname - %type was adjusted to %mask',
            array('%nname'=>guifi_get_hostname($llink['device_id']),
              '%type'=>$interface['interface_type'],
              '%mask'=>$ipv4['netmask']));
          $link['interface']['ipv4']['netmask'] = $ipv4['netmask'];
        }
        $link['interface']['ipv4']['interface_id'] = $link['interface']['id'];
        guifi_log(GUIFILOG_TRACE,sprintf('SQL ipv4 remote (id=%d, iid=%d)',
          $link['interface']['ipv4']['id'],
          $link['interface']['ipv4']['interface_id']),
          $link['interface']);
        $ripv4 = _guifi_db_sql(
          'guifi_ipv4',
          array('id'=>$link['interface']['ipv4']['id'],
            'interface_id'=>$link['interface']['ipv4']['interface_id']),
            $link['interface']['ipv4'],$log,$to_mail);
      }
      if (!$llink['deleted']) {
        $link['id'] = $nllink['id'];
        $link['ipv4_id'] = $ripv4['id'];
        $link['interface_id'] = $rinterface['id'];
        $nrlink = _guifi_db_sql(
          'guifi_links',
          array('id'=>$link['id'],
          'device_id'=>$link['device_id']),
          $link,$log,$to_mail);
        guifi_log(GUIFILOG_TRACE,'going to SQL for remote link',$nllink);
      }
    }
  } // foreach ipv4
  
  return $log;
}

function guifi_device_buttons($continue = false,$action = '', $nopts = 0, &$form_weight = 1000) {
  $form['reset'] = array(
    '#type' => 'button',
    '#executes_submit_callback' => true,
    '#value' => t('Reset'),
    '#weight' => $form_weight++,
  );
  
  if ($continue) { 
    $form['ignore_continue'] = array(
      '#type' => 'button',
      '#executes_submit_callback' => true,
      '#value' => t('Ignore & back to main form'),
      '#weight' => $form_weight++,
    );
    if ($nopts > 0) {
      $form['confirm_continue'] = array(
        '#type' => 'button',
        '#submit' => array($action),
        '#executes_submit_callback' => true,
        '#value' => t('Select device & back to main form'),
        '#weight' => $form_weight++,
      );
    }
    return $form;
  }
  $form['validate'] = array(
    '#type' => 'button',
    '#value' => t('Validate'),
    '#weight' => $form_weight++,
  );
  $form['save_continue'] = array(
    '#type' => 'submit',
    '#value' => t('Save & continue edit'),
    '#weight' => $form_weight++,
  );
  $form['save_exit'] = array(
    '#type' => 'submit',
    '#value' => t('Save & exit'),
    '#weight' => $form_weight++,
  );
  
  return $form;
}
/* guifi_device_delete(): Delete a device */
function guifi_device_delete_confirm($form_state,$params) {

  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this device?'),
    '#value' => $params['name'],
    '#description' => t('WARNING: This action cannot be undone. The device and it\'s related information will be <strong>permanently deleted</strong>, that includes:<ul><li>The device</li><li>The related interfaces</li><li>The links where this device is present</li></ul>If you are really sure that you want to delete this information, press "Confirm delete".'),
    '#weight' => 0,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Confirm delete'),
    '#name'  => 'confirm',
    '#weight' => 1,
  );
  drupal_set_title(t('Delete device: (%name)',array('%name'=>$params['name'])));

  return $form;
}

function guifi_device_delete($device, $notify = true, $verbose = true) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_device_delete()');

  $to_mail = explode(',',$device['notification']);

  if ($_POST['confirm']) {
    $log = _guifi_db_delete('guifi_devices',
        array('id'=>$device['id']),
        $to_mail);
    drupal_set_message($log);

    $subject = t('The device %name has been DELETED by %user.',
      array('%name' => $device['name'],
        '%user' => $user->name));
    drupal_set_message($subject);
    guifi_notify($to_mail,
      $subject,
      $log,
      $verbose,
      $notify);
      
    drupal_goto('node/'.$device['nid']);
  }

  return drupal_get_form('guifi_device_delete_confirm',
    array('name'=>$device['nick'],'id'=>$device['id']));
}

/* guifi_device_add(): Provides a form to create a new device */
function guifi_device_add() {
  guifi_log(GUIFILOG_TRACE,'function guifi_device_add()');
  
  $output = drupal_get_form('guifi_device_form',array('add'=>arg(3),
                                                    'type'=>arg(4)));
  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);                                                   
}

/* guifi_device_create_form(): generates html output form with a listbox, 
 * choose the device type to create 
 */
function guifi_device_create_form($form_state, $nid) {
  
  $types = guifi_types('device');
  
  if (is_array($nid))
    $id = $nid->id;
  else
    $id = $nid;

  if (!guifi_access('create',$id)) {
    $form['text_add'] = array(
     '#type' => 'item',
     '#value' => t('You are not allowed to update this node.'),
     '#weight' => 0
   );
   return $form;
  }
  $form['nid'] = array(
    '#type' => 'hidden',
    '#value' => $id
  );
  $form['text_add'] = array(
    '#type' => 'item',
    '#value' => t('Add a new device'),
    '#prefix' => '<table style="width: 40em"><tr><td style="wiiidth: 200px">',
    '#description' => t('Type of device to be created'),
    '#suffix' => '</td>',
    '#weight' => 0
  );
  $form['device_type'] = array(
    '#type' => 'select',
    '#options' => $types,
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => 1
  );
  $form['submit'] = array(
    '#type' => 'submit', 
    '#value' => t('add'),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 2
  );

  return $form;
}

function guifi_device_create_form_submit($form, &$form_state) {

  $form_state['redirect'] = 
    'guifi/device/add/'.$form_state['values']['nid'].
    '/'.$form_state['values']['device_type'];
}

function guifi_device_create($nid) {
  $form = drupal_get_form('guifi_device_create_form',$nid);
  print theme('page',$form);
}

/* guifi_ADSL_form(): Create form for editiong DSL devices */
function guifi_ADSL_form($edit) {

  if (!isset($edit['variable']['download']))
    $edit['variable']['download'] = 4000000;
  if (!isset($edit['variable']['upload']))
    $edit['variable']['upload'] = 640000;
  $output .= form_select(t('Download'),'variable][download',
                    $edit['variable']['download'],guifi_bandwidth_types(),
                    t('Download bandwidth'));
  $output .= form_select(t('Upload'),'variable][upload',
                    $edit['variable']['upload'],guifi_bandwidth_types(),
                    t('Upload bandwidth'));
        
  $output .= form_textfield(t('MRTG config'), 
                    'variable][mrtg_index', 
                    $edit['variable']['mrtg_index'], 2,5, 
                    t('SNMP interface index for getting traffic information ' .
                        'of this device. User tools like cacti or snmpwalk ' .
                        'to determine the index. Example:').
                        '<br /><pre>snmpwalk -Os -c public -v 1 10.138.25.66 ' .
                        'interface</pre>');
  return $output;
}

function guifi_bandwidth_types() {
  return    array(  '64000'=>'64k',
                               '128000'=>'128k',
                               '256000'=>'256k',
                               '512000'=>'512k',
                               '640000'=>'640k',
                              '1000000'=>'1M',
                              '2000000'=>'2M',
                              '4000000'=>'4M',
                              '8000000'=>'8M',
                             '20000000'=>'20M',
                             '40000000'=>'40M');
}

/****************************************
   device output information functions
*****************************************/
/* guifi_device_print_data(): outputs a detailed device information data */
function guifi_device_print_data($device) {

  $created = db_fetch_object(db_query('
    SELECT u.name
    FROM {users} u
    WHERE u.uid = %d',
    $device[user_created]));
  $changed = db_fetch_object(db_query('
    SELECT u.name
    FROM {users} u
    WHERE u.uid = %d',
    $device[user_changed]));
  $name_created = l($created->name,'user/'.$device['user_created']);
  $name_changed = l($changed->name,'user/'.$device['user_changed']);
  
  $radios = db_query(
      'SELECT * 
       FROM {guifi_radios} 
       WHERE id=%d 
       ORDER BY id',
      $device['id']);

  $rows[] = array(t($device[type]),'<b>' .$device[nick] .'</b>'); 

  // If radio, print model & firmware
  if ($device['type'] == 'radio') { 
    $model = db_fetch_object(db_query("
      SELECT model, nom
      FROM {guifi_model} m LEFT JOIN {guifi_manufacturer} f ON m.fid=f.fid
      WHERE m.mid=%d",
      $device['variable']['model_id']));
    $rows[] = array($model->model,$device['variable']['firmware']); 
    // going to list all device radios
    if (count($device['radios'])) {
      foreach ($device['radios'] as $radio_id=>$radio) {
        $rowsr[] = array(
          $radio['ssid'],
          $radio['mode'],
          $radio['protocol'],
          $radio['channel'],
          $radio['mac'],
          $radio['clients_accepted']
        );
      }
      $rows[] =  array(array('data'=>theme('table',
        array(t('ssid'),t('mode'),t('protocol'),t('ch'),t('wireless mac'),
            t('clients')),$rowsr),'colspan'=>2));
    }
  }

  // If ADSL, print characteristics
  if (($device['type'] == 'ADSL') and ($device['variable'] != '')) {
    $bandwidth = guifi_bandwidth_types();
    $rows[] = array(t('bandwidth'),$bandwidth[$device['variable']['download']].
            '/'.$bandwidth[$device['variable']['upload']]);
    $rows[] = array(t('SNMP index to graph'),$device['variable']['mrtg_index']);
  }

  switch ($device['graph_server']) {
  case -1:
    $graphtxt = t('Graphs disabled.');
    break;
  case 0:
  case NULL:
    $graphtxt = t('Default: Obtained from node');
    break;
  default:
    $qgs = db_query(sprintf('SELECT nick FROM {guifi_services} WHERE id=%d',$device['graph_server']));
    $gs = db_fetch_object($qgs);
    if (!empty($gs->nick)) {
      $graphtxt = '<a href="/node/'.$device['graph_server'].'">'.$gs->nick.'</a>';
    } else
      $graphtxt = t('invalid');
  }
  $rows[] = array(t('graphs provided from'),array('data'=>$graphtxt,'colspan'=>2));


  $ip = guifi_main_ip($device[id]);
  $rows[] = array(t('IP address & MAC'),$ip[ipv4].'/'.$ip[maskbits].' '.$device[mac]);

  $graph_url = guifi_radio_get_url_mrtg($device['id'],FALSE);
  if ($graph_url != NULL)
    $img_url = ' <img src='.$graph_url.'?device='.$device['id'].'&type=availability&format=long>';
  else
    $img_url = NULL;

  $rows[] = array(t('status &#038; availability'),array('data' => t($device[flag]).$img_url,'class' => $device['flag']));
  if (($device['notification']) and (guifi_device_access('update',$device['id'])))
    $rows[] = array(t('changes notified to (visible only if you have privileges)'),'<a href="mailto:'.$device['notification'].'">'.$device['notification'].'</a>');
  $rows[] = array(t('created by:').' '.$name_created .'&nbsp;' .t('on') .'&nbsp;' .format_date($device[timestamp_created]), 
                  t('updated by:').' '.$name_changed .'&nbsp;' .t('on') .'&nbsp;' .format_date($device[timestamp_changed])); 

  return array_merge($rows);
}

/* guifi_device_links_print_data(): outputs the device link data, create an array of rows per each link */
function guifi_device_links_print_data($id) {
  $query = db_query("
    SELECT i.*,a.ipv4,a.netmask
    FROM {guifi_interfaces} i, {guifi_ipv4} a
    WHERE i.id=a.interface_id AND i.device_id=%d
    ORDER BY i.interface_type",
    $id);
  while ($if = db_fetch_object($query)) {
    $ip = _ipcalc($if->ipv4,$if->metmask);
    $rows[] = array($if->interface_type,$if->ipv4.'/'.$ip['netid'],$if->netmask,$if->mac);
  }
  return array_merge($rows);
}
  
/* guifi_device_interfaces_print_data(): outputs the device interfaces data */
function guifi_device_interfaces_print_data($id) {
  $rows = array();
  $query = db_query("
    SELECT i.*,a.ipv4,a.netmask, a.id ipv4_id
    FROM {guifi_interfaces} i, {guifi_ipv4} a
    WHERE i.id=a.interface_id AND i.device_id=%d
    ORDER BY i.interface_type",
    $id);
  while ($if = db_fetch_object($query)) {
    $ip = _ipcalc($if->ipv4,$if->netmask);
    $rows[] = array($if->id.'/'.$if->ipv4_id,$if->interface_type,$if->ipv4.'/'.$ip['maskbits'],$if->netmask,$if->mac);
  }
  return array_merge($rows);
}
  
/* guifi_device_print(): main print function, outputs the device information and call the others */
function guifi_device_print($device = NULL) {
//  print_r($_GET);
//  print arg(0)."\n<br />";
//  print arg(1)."\n<br />";
//  print arg(2)."\n<br />";
//  print arg(3)."\n<br />";
//  print arg(4)."\n<br />";                                

  $output = '<div id="guifi">';

//  $device = guifi_device_load($id);
//  if (empty($device))
//    return print theme('page',null,t('device').': '.$id);
   
  $node = node_load(array('nid' => $device[nid])); 
  $title = t('Node:').' <a href="'.url('node/'.$node->nid).'">'.$node->nick.'</a> &middot; '.t('Device:').'&nbsp;'.$device[nick];

  drupal_set_breadcrumb(guifi_zone_ariadna($node->zone_id));

  switch (arg(4)) {
  case 'all': case 'data': default:
    $table = theme('table', NULL, guifi_device_print_data($device));
    $output .= theme('box', $title, $table);
    if (arg(4) == 'data') break;
  case 'graphs':
    // device graphs
    $table = theme('table', array(t('traffic overview')), guifi_device_graph_overview($device));
    $output .= theme('box', t('device graphs'), $table);
    if (arg(4) == 'graphs') break;
  case 'links':
    // links
    $output .= theme('box', NULL, guifi_device_links_print($device));
    if (arg(4) == 'links') break;
  case 'interfaces':
    $header = array(t('id'),t('type'),t('ip address'),t('netmask'),t('mac'));
    $table = theme('table', $header, guifi_device_interfaces_print_data($device[id]));
    $output .= theme('box', t('interfaces information'), $table);
    break;
  case 'services':
    $output .= theme('box', t('services information'), guifi_list_services($device[id]));
    break;
  }
  
  $output .= '</div>';

//  $title = t('Node:').' <a href="node/'.$node->nid.'">'.$node->nick.'</a> &middot; '.t('Device:').'&nbsp;'.$device[nick];
  drupal_set_title(t('View device %dname',array('%dname'=>$device['nick'])));

  return $output;
}

function guifi_device_links_print($device,$ltype = '%') {
//  guifi_log(GUIFILOG_TRACE,'function guifi_device_links_print()');
//  guifi_log(GUIFILOG_TRACE,'device at function guifi_device_links_print()',$device);
  $oGC = new GeoCalc();
  $dtotal = 0;
  $ltotal = 0;
  if ($ltype == '%')
    $title = t('links');
  else
  $title = t('links').' ('.$ltype.')';

  $rows_wds[] = array(array('data'=>'<strong>'.t('bridge wds/p2p').'</strong>','colspan'=>2));
  $rows_ap_client[] = array(array('data'=>'<strong>'.t('ap/client').'</strong>','colspan'=>2));
  $rows_cable[] = array(array('data'=>'<strong>'.t('cable').'</strong>','colspan'=>2));
  $rows=array();
  $loc1 = db_fetch_object(db_query('SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',$device['nid']));
  $graph_url = guifi_radio_get_url_mrtg($device['id'],FALSE);
  switch ($ltype) {
  case '%':
  case 'wds':
  case 'ap/client':
    if ($device['radios']) foreach ($device['radios'] as $radio_id=>$radio) 
    if ($radio['interfaces']) foreach ($radio['interfaces'] as $interface_id=>$interface) 
    if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id=>$ipv4) 
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id=>$link) {
      guifi_log(GUIFILOG_FULL,'going to list link',$link);
      $loc2 = db_fetch_object(db_query('SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',$link['nid']));
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
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
      $item = _ipcalc( $ipv4['ipv4'],  $ipv4['netmask']);
      $ipdest = explode('.',$link['interface']['ipv4']['ipv4']);
      if ($graph_url != NULL)
        $img_url = ' <img src='.$graph_url.'?device='.$link['device_id'].'&type=availability&format=short>';
      else
        $img_url = NULL;

      $cr = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id=%d",$link['device_id']));
      if ($cr->count > 1) {
        $rn = db_fetch_object(db_query("SELECT ssid FROM {guifi_radios} r WHERE r.id=%d AND r.radiodev_counter=%d",$link['device_id'],$link['interface']['radiodev_counter']));
        $dname = guifi_get_hostname($link['device_id']).'-'.$rn->ssid;
      }
      else
        $dname = guifi_get_hostname($link['device_id']);
      
      if ($interface['interface_type'] == 'wds/p2p')
        $from = '<small>'.$radio['ssid'].'</small>';
      else
        $from = '&nbsp';

      $wrow = array($from,array('data'=>$link_id,'align'=>'right'),
                    '<a href="/guifi/device/'.$link['device_id'].'">'.$dname.'</a>',
                    '<a href="/node/'.$link['nid'].'">'.$loc2->nick.'</a>',
                    $ipv4['ipv4'].'/'.$item['maskbits'],'.'.$ipdest[3],
                    array('data' => t($link['flag']).$img_url,
                          'class' => $link['flag']),
                    $link[routing],
                    $gDist,
                    $dAz.'-'.$dOr);  
      if ($interface['interface_type'] == 'wds/p2p')
        $rows_wds[] = $wrow;
      if ($link['link_type'] == 'ap/client')
        $rows_ap_client[] = $wrow;
      $dtotal = $dtotal + $gDist;;
      $ltotal++;

    }
    if ($ltype != '%') break;
  case 'cable':
    if ($device['interfaces']) foreach ($device['interfaces'] as $interface_id=>$interface) 
    if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id=>$ipv4) 
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id=>$link) {
      $loc2 = db_fetch_object(db_query('SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',$link['nid']));
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
      $item = _ipcalc( $ipv4['ipv4'],  $ipv4['netmask']);
      $ipdest = explode('.',$link['interface']['ipv4']['ipv4']);
      if ($graph_url != NULL)
        $img_url = ' <img src='.$graph_url.'?device='.$link['device_id'].'&type=availability&format=short>';
      else
        $img_url = NULL;
      $rows_cable[] = array($interface['interface_type'].'/'.$link['interface']['interface_type'],
                       array('data'=>$link_id,'align'=>'right'),
                       '<a href="/guifi/device/'.$link['device_id'].'">'.guifi_get_hostname($link['device_id']).'</a>',
                       array('data'=>'-','align'=>'center'),
                       $ipv4['ipv4'].'/'.$item['maskbits'],'.'.$ipdest[3],
                       array('data' => t($link['flag']). $img_url,
                             'class' => $link['flag']),
                       $link[routing],
                       array('data'=>'-','align'=>'center') , 
                       array('data'=>'-','align'=>'center'));  
      $ltotal++;
    }
    if ($ltype == 'cable') break;
  } 

  if (count($rows_wds)> 1)  
    $rows = $rows_wds;
  if (count($rows_ap_client) > 1) 
    $rows = array_merge($rows_ap_client,$rows);
  if (count($rows_cable) > 1) 
    $rows = array_merge($rows,$rows_cable);
  return '<h2>'.$title.'</h2>'.
         '<h3>'.t('Totals').': '.$ltotal.' '.t('links').', '.$dtotal.' '.t('kms.').'</h3>'.
         theme('table',array(t('interface'),t('id'),t('device'),t('node'),t('ip address'),'&nbsp;',t('status'),t('routing'),t('kms.'),t('az.')),$rows);
}

function guifi_device_link_list($id = 0, $ltype = '%') {
  $oGC = new GeoCalc();

  $total = 0;
  if ($ltype == '%')
    $title = t('links');
  else
  $title = t('links').' ('.$ltype.')';
 
  $header = array(t('type'),t('linked devices'), t('ip'), t('status'), t('routing'), t('kms.'),t('az.'));

  $queryloc1 = db_query("
    SELECT
      c.id, c.link_type, c.routing, l.nick, c.device_id, d.nick
      device_nick, a.ipv4 ip, i.interface_type itype, c.flag,
      l.lat, l.lon
    FROM {guifi_links} c
      LEFT JOIN {guifi_devices} d ON c.device_id=d.id
      LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id
      LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=c.ipv4_id
      LEFT JOIN {guifi_location} l ON d.nid = l.id
    WHERE c.device_id = %d
      AND link_type like '%s'
    ORDER BY c.link_type, c.device_id",
    $id,$ltype);
  if (db_num_rows($queryloc1)) {
    while ($loc1 = db_fetch_object($queryloc1)) {
      $queryloc2 = db_query("
        SELECT
          c.id, l.nick, r.ssid, c.device_id, d.nick device_nick,
          a.ipv4 ip, i.interface_type itype, l.lat, l.lon
        FROM {guifi_links} c
          LEFT JOIN {guifi_devices} d ON c.device_id=d.id
          LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id
          LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id
            AND a.id=c.ipv4_id
          LEFT JOIN {guifi_location} l ON d.nid = l.id
          LEFT JOIN {guifi_radios} r ON d.id=r.id
            AND i.radiodev_counter=r.radiodev_counter
        WHERE c.id = %d
          AND c.device_id != %d",
          $loc1->id,
          $loc1->device_id);
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

        $cr = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id=%d",$loc2->device_id));
        if ($cr->count > 1)
          $dname = $loc2->device_nick.'/'.$loc2->ssid;
        else
          $dname = $loc2->device_nick;

        $rows[] = array($loc1->id.'-'.$loc1->link_type.' ('.$loc1->itype.'-'.$loc2->itype.')','<a href="guifi/device/'.$loc2->device_id.'">'.$dname.'</a>',
                     $loc1->ip.'/'.$loc2->ip,
                   array('data' => t($loc1->flag), 'class' => $loc1->flag),
                   array('data' => $gDist,'class' => 'number'),
                   $loc1->routing,
                   $dAz.'-'.$dOr);
      }
    }
    $output .= theme('table', $header, $rows);
    $output = theme('box',$title,$output);
    if ($total)
      $output .= t('Total:').'&nbsp;'.$total.'&nbsp;'.t('kms.');
    return $output;
  }
  return NULL;
}

function guifi_device_item_delete_msg($msg) {
  return t($msg).'<br>'.
    t('Press "<b>Save</b>" to confirm deletion or ' .
      '"<b>Reset</b>" to discard changes and ' .
      'recover the values from the database.');
}

function guifi_device_edit($device) {
  $output = drupal_get_form('guifi_device_form',$device);
  
  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);
}


/*** TESTING FUNCTIONS, for testing purposes only {
function guifi_device_edit($id) {
//  print theme('page', drupal_get_form('guifi_device_form',$id),FALSE);
  print theme('page', guifi_device_form($id) ,FALSE);
  exit(0);
}

function guifi_device_form($id = null, &$edit = null) {
  global $user;

//  if (($edit['id'] == null) and ($id > 0)) {
//    $edit = guifi_device_load($id);
//  }

  if (!isset($edit)) {
    $step = 1;
  }
  else {
    $step = $edit['step'] + 1;
  }
 
  $form['step'] = array(
    '#type' => 'hidden',
    '#value' => $step,
  );

  switch ($step) {
    case 1:
      $form['nick_1'] = array(
      '#parents' => array('nick_1'),
      '#type' => 'textfield',
      '#title' => t('Name_1'),
//      '#default_value' => isset($edit) ? $edit['nick_1'] : '',
      
      );
      break;
    case 2:
      $form['nick_1'] = array(
      '#type' => 'hidden',
      '#parents' => array('nick_1'),
      '#value' => $edit['nick_1'],
      );
      $form['nick_2'] = array(
      '#parents' => array('nick_2'),
      '#type' => 'textfield',
      '#title' => t('Name_2'),
      '#default_value' => isset($edit) ? $edit['nick_2'] : '',
      );
      break;
    case 3:
      $form['nick_2'] = array(
      '#parents' => array('nick_2'),
      '#type' => 'hidden',
      '#value' => $edit['nick_2'],
      );
      $form['nick_1'] = array(
      '#parents' => array('nick_1'),
      '#type' => 'textfield',
      '#type' => 'hidden',
      '#value' => $edit['nick_1'],
      );
      $form['nick_3'] = array(
      '#parents' => array('nick_3'),
      '#type' => 'textfield',
      '#type' => 'textfield',
      '#title' => t('Name_3'),
      '#default_value' => isset($edit) ? $edit['nick_3'] : '',
      );
      break;
  }
 
  $form['#multistep'] = TRUE;
  $form['#redirect'] = FALSE;
 
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );
  $form['AddLink2AP'] = array(
    '#type'=>'submit',
    '#parents'=>array('radios',1,'AddLink2AP'),
    '#value'=>t('Link to AP'),
    '#name'=>'_action,_guifi_link_2ap,'
  );


//  return $form;
//  drupal_prepare_form('guifi_device_form',$form);
  return drupal_render_form('guifi_device_form',$form);

}

function guifi_device_form_submit($form_id, $edit) {
  $final_step = 3;

  drupal_set_message(sprintf('Variables: %d - %s, %s, %s',$edit['step'],$edit['nick_1'],$edit['nick_2'],$edit['nick_3']));

  $edit['nick_2'] = 'hola2';
 
  if ($edit['step'] == $final_step) {
    print "Final del form step...\<br>";
    print_r($edit);
    exit;
    // Process the form here!
  }
}

function guifi_device_form_validate($form_id,$edit,$form) {
  drupal_set_message('form validate' );
  form_set_value($form['nick_1'],'Hola_validat_'.$edit['nick_1']);
}

} END OF TESTING FUNCTIONS */

?>
