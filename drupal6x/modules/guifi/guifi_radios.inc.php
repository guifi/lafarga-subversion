<?php

/* Radio edit forms & functions */
/* guifi_radio_form(): Main radio form (Common parameters)*/
function guifi_radio_form(&$edit,$form_weight) {
  global $hotspot;
  global $bridge;
  global $user;


  guifi_log(GUIFILOG_TRACE,'function guifi_radio_form()',$edit);

  $querymid = db_query("
    SELECT mid, model, f.nom manufacturer
    FROM guifi_model m, guifi_manufacturer f
    WHERE f.fid = m.fid
      AND supported='Yes'");
  while ($model = db_fetch_array($querymid)) {
     $models_array[$model["mid"]] = $model["manufacturer"] .", " .$model["model"];
  }

  $form['radio_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Device model, firmware & MAC address').' ('.$edit['variable']['firmware'].')',
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
    '#tree' => FALSE,
    '#collapsed' => !is_null($edit['id']),
  );
  $form['radio_settings']['variable'] = array('#tree' => TRUE);
  $form['radio_settings']['variable']['model_id'] = array(
    '#type' => 'select',
    '#title' => t("Radio Model"),
    '#required' => TRUE,
    '#default_value' => $edit['variable']['model_id'],
    '#options' => $models_array,
    '#description' => t('Select the readio model that do you have.'),
    '#prefix' => '<table><tr><td>',
    '#suffix' => '</td>',
    '#weight' => 0,
  );
  $form['radio_settings']['variable']['firmware'] = array(
    '#type' => 'select',
    '#title' => t("Firmware"),
    '#required' => TRUE,
    '#default_value' => $edit['variable']['firmware'],
    '#options' => guifi_types('firmware'),
    '#description' => t('Used for automatic configuration.'),
    '#prefix' => '<td>',
    '#suffix' => '</td>',
    '#weight' => 1,
  );
  $form['radio_settings']['mac'] = array(
    '#type' => 'textfield',
    '#title' => t('Device MAC Address'),
    '#required' => TRUE,
    '#size' => 17,
    '#maxlength' => 17,
    '#default_value' => $edit['mac'],
    '#element_validate' => array('guifi_mac_validate'),
    '#description' => t("Base/Main MAC Address.<br />Some configurations won't work if is blank"),
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
    '#weight' => 2
  );

  $msg = t('Wireless radios settings').' - ';
  switch (count($edit['radios'])) {
  case 0: 
     $msg .= t('No radios');
     break;
  case 1:
     $msg .= t('1 radio');
     break;
  default:
     $msg .= count($edit['radios']).' '.t('radios');
  }
  $form['r'] = array(
    '#type' => 'fieldset',
    '#title' => $msg ,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#tree' => FALSE,
    '#weight' => $form_weight++,
  );
  $form['r']['radios'] = array('#tree'=>TRUE);
  $rc = 0;
  $bridge = false;
  $cinterfaces = 0;
  $cipv4 = 0;
  $clinks = 0;
  if (!empty($edit['radios'])) foreach ($edit['radios'] as $key => $radio) {
    $hotspot = false;
    
//    if ($radio['deleted']) continue;
    
    guifi_radio_radio_form($form,$radio,$key,$form_weight);
    
    $bw = $form_weight - 1000;

    $counters = guifi_radio_radio_interfaces_form($edit, $form, $key, $form_weight);
    $form['r']['radios'][$key]['#title'] .= ' - '.
      $counters['interfaces'].' '.t('interface(s)').' - '.
      $counters['links'].' '.t('link(s)').' - '.
      $counters['ipv4'].' '.t('ipv4 address(es)');
    $cinterfaces += $counters['interfaces'];
    $cipv4 += $counters['ipv4'];
    $clinks += $counters['links'];

    // Going to paint the buttons

    // For AP Mode, clients_accepted
    if ($radio['mode'] == 'ap') { 
      // If no wLan interface, allow to create one
      if ((count($radio['interfaces']) < 2) or (user_access('administer guifi networks'))) {
        $form['r']['radios'][$key]['AddwLan'] = array(
          '#type'=>'image_button',
          '#src'=>drupal_get_path('module', 'guifi').'/icons/insertwlan.png',
          '#parents'=>array('radios',$key,'AddwLan'),
          '#submit' => array('guifi_radio_add_wlan_submit'),
          '#attributes'=>array('title'=>t('Add a public network range to the wLan for clients')), 
          '#weight'=>$bw++);
      }
      if (!$hotspot) {
        $form['r']['radios'][$key]['AddHotspot'] = array(
          '#type'=>'image_button',
          '#src'=>drupal_get_path('module', 'guifi').'/icons/inserthotspot.png',
          '#attributes'=>array('title'=>t('Add a Hotspot for guests')), 
          '#submit' => array('guifi_radio_add_hotspot_submit'),
          '#weight'=>$bw++);
      }
    } else {
      // Mode Client or client-routed, allow to link to AP
      $cr = guifi_count_radio_links($radio);
      if ($cr['ap']==0)
        $form['r']['radios'][$key]['AddLink2AP'] = array(
          '#type'=>'submit',
          '#value'=>t('Link to AP'), 
          '#name'=>'_action,_guifi_link_2ap,'.$key,
          '#weight'=>$bw++);
    }   

    // Only allow delete and move functions if the radio has been saved 
    if ($radio['new']==false)  {
      // Only allow delete radio if several when is not the first 
      if ((count($edit['radios'])==1) or ($key))
      $form['r']['radios'][$key]['delete'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
        '#parents'=>array('radios',$key,'delete'),
        '#attributes'=>array('title'=>t('Delete radio')), 
        '#submit' => array('guifi_radio_delete_submit'),
        '#weight'=>$bw++);
      $form['r']['radios'][$key]['change'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/move.png',
        '#parents'=>array('radios',$key,'move'),
        '#attributes'=>array('title'=>t('Move radio to another device')), 
        '#weight'=>$bw++);
    }

    // if not first, allow to move up  
    if ($rc) 
      $form['r']['radios'][$key]['up'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/up.png',
        '#attributes'=>array('title'=>t('Move radio up')), 
//        '#value'=>t('Up'), 
        '#submit' => array('guifi_radio_swap_submit'),
        '#parents'=>array('radios',$key,'up'),
//        '#default_value'=>'swapRadios,'.($key).','.($key-1),
        '#weight'=>$bw++);
    // if not last, allow to move down
    if (($rc+1) < count($edit['radios'])) 
      $form['r']['radios'][$key]['down'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/down.png',
        '#attributes'=>array('title'=>t('Move radio down')), 
//        '#value'=>t('Down'), 
        '#submit' => array('guifi_radio_swap_submit'),
        '#parents'=>array('radios',$key,'down'),
//        '#default_value'=>'swapRadios,'.($key).','.($key+1),
        '#weight'=>$bw++);

    $rc++;
  } // foreach radio

  // Edit radio form or add new radio
  $cr = 0; $tr = 0; $firewall=false;
  $maxradios = db_fetch_object(db_query('SELECT radiodev_max FROM {guifi_model} WHERE mid=%d',$edit[variable][model_id]));
//    print "Max radios: ".$maxradios->radiodev_max." \n<br />";
  if (isset($edit[radios])) 
  foreach ($edit[radios] as $k=>$radio) {
    $tr++;
    if (!$radio['deleted'])
      $cr++;
    if ($radio['mode'] == 'client') 
      $firewall = true;
  } // foreach $radio

//   print "Max radios: ".$maxradios->radiodev_max." Current: $cr Total: $tr Firewall: $firewall Edit details: $edit[edit_details]\n<br />";
  $modes_arr = guifi_types('mode');
//  print_r($modes_arr);
 
  if ($cr>0)
    if (!$firewall)
      $modes_arr = array_diff_key($modes_arr,array('client'=>0));
    else
      $modes_arr = array_intersect_key($modes_arr,array('client'=>0));
  if ($cr < $maxradios->radiodev_max) {
    if ( (( $edit['id'] > 0 ) && (!isset($edit[edit_details]))) and ($tr < $maxradios->radiodev_max)) {
//      print "Max radios: ".$maxradios->radiodev_max." Current: $cr Total: $tr Firewall: $firewall Edit details: $edit[edit_details]\n<br />";
      $form['r']['newradio_mode'] = array(
        '#type' => 'select',
//        '#parents' => array('r','newradio_mode'),
        '#required' => FALSE,
        '#default_value' =>  'client',
        '#options' => $modes_arr,
        '#prefix' => '<table  style="width: 100%"><th colspan="0">'.t('New radio (mode)').'</th><tr><td  style="width: 0" align="right">',
        '#suffix' => '</td>',
        '#weight' => 20); 
      $form['r']['AddRadio'] = array(
        '#type' => 'button',
//        '#parents' => array('r','AddRadio'),
        '#default_value' => t('Add new radio'),
        '#executes_submit_callback' => true,
        '#name'=>'addRadio',
        '#prefix' => '<td style="width: 10em" align="left">',
        '#suffix' => '</td><td style="width: 100%" align="right">&nbsp</td></tr>',
        '#weight' => 21,
       );
       $form['r']['help_addradio'] = array(
        '#type' => 'item',
        '#description' => t('Usage:<br />Choose <strong>wireless client</strong> mode for a normal station with full access to the network. That\'s the right choice in general.<br />Use the other available options only for the appropiate cases and being sure of what you are doing and what does it means. Note that you might require to be authorized by networks administrators for doing this.<br />Youwill not be able to define you link and get connected to the network until you add at least one radio.'),
        '#prefix' => '<tr><td colspan="3">',
        '#suffix' => '</td></tr></table>',
        '#weight' => 22,
       );
      
    } else {
      $form['r']['AddRadio'] = array(
        '#type' => 'item',
        '#value' => t('You can add radios to this device once has been saved into de database'),
        '#weight' => $form_weight++,
       );
    }
  }
  $form['r']['#title'] .= ' - '.
    $cinterfaces.' '.t('interface(s)').' - '.
    $cipv4.' '.t('address(es)').' - '.
    $clinks.' '.t('link(s)');

  return $form;
}

/* _guifi_radio_form(): radio (loop per radio) form */
function guifi_radio_radio_form(&$form, $radio, $key, &$form_weight = -200) {
    guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_radio_radio_form(key=%d)',$key),$radio); 

    $fw2 = 0;
    
    $form['r']['radios'][$key] = array(
      '#type' => 'fieldset',
      '#title' => t('Radio #').$key.' - '.$radio['mode'].' - '.$radio['ssid'],
      '#collapsible' => true,
      '#collapsed' => !(isset($radio['unfold'])),
      '#tree'=> TRUE,
      '#weight' => $form_weight++,
    );
    if ($radio['deleted']) {
      $form['r']['radios'][$key]['deletedMsg'] = array(
        '#type' => 'item',
        '#value' => t("This radio and has been deleted, deletion will cascade to all properties, including interfaces, links and ip addresses.\n".
                      'Press "Save" to confirm deletion or "Reset" to discard changes and recover the values from the database.'),
        '#weight' => $form_weight++);
      $form['r']['radios'][$key]['deleted'] = array(
        '#type' => 'hidden',
        '#value' => true);
    }
    if ($radio['new']) {
      $form['r']['radios'][$key]['new'] = array(
        '#type' => 'hidden',
        '#parents' => array('radios',$key,'new'),
        '#value' => true);
    }
    $form['r']['radios'][$key]['mode'] = array(
        '#type' => 'hidden',
        '#parents' => array('radios',$key,'mode'),
        '#value' => $radio['mode']);
    if ($radio['mode'] == 'ap')
      $form['r']['radios'][$key]['ssid'] = array(     
        '#type' => 'textfield',
        '#title' => t('SSID'),
        '#parents' => array('radios',$key,'ssid'),
        '#required' => TRUE,
        '#size' => 30,
        '#maxlength' => 30,
        '#default_value' => $radio["ssid"],
        '#description' => t("SSID to identify this radio signal."),
        '#prefix'=>'<table><tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++
      );
    else {
      $inherit_msg = t('Will take it from the connected AP.');
      $form['r']['radios'][$key]['ssid'] = array(     
        '#type' => 'hidden',
        '#parents' => array('radios',$key,'ssid'),
        '#title' => t('SSID'),
        '#default_value' => $radio["ssid"],
        '#description' => $inherit_msg,
        '#prefix'=>'<table><tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++
      );
    }
    $form['r']['radios'][$key]['mac'] = array(
      '#type' => 'textfield',
//      '#parents' => array('radios',$key,'mac'),
      '#title' => t('MAC'),
      '#required' => TRUE,
      '#parents' => array('radios',$key,'mac'),
   //   '#process' => array('_guifi_radio_mac_process'),
      '#size' => 17,
      '#maxlength' => 17,
      '#default_value' => $radio["mac"],
      '#element_validate' => array('guifi_mac_validate'),
      '#description' => t("Wireless MAC Address.<br />Some configurations won't work if is blank"),
      '#prefix'=>'<td>',
      '#suffix'=>'</td></tr>',
      '#weight' => $fw2++
    );
    if ($radio['mode'] == 'ap') {
      $form['r']['radios'][$key]['protocol'] = array(
        '#type' => 'select',
        '#title' => t("Protocol"),
        '#parents' => array('radios',$key,'protocol'),        
//        '#required' => TRUE,
        '#default_value' =>  $radio["protocol"],
        '#options' => guifi_types('protocol'),
        '#description' => t('Select the protocol where this radio will operate.'),
        '#prefix'=>'<tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['channel'] = array(
        '#type' => 'select',
        '#title' => t("Channel"),
        '#parents' => array('radios',$key,'channel'),        
//        '#required' => TRUE,
        '#default_value' =>  $radio["channel"],
        '#options' => guifi_types('channel',null,null,$radio['protocol']), 
        '#description' => t('Select the channel where this radio will operate.'),
        '#prefix'=>'<td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['clients_accepted'] = array(
        '#type' => 'select',
        '#title' => t("Clients accepted?"),
        '#parents' => array('radios',$key,'clients_accepted'),        
//        '#required' => TRUE,
        '#default_value' =>  $radio["clients_accepted"],
        '#options' => drupal_map_assoc(array( 0=>'Yes',1=>'No')),
        '#description' => t('Do this radio accept connections from clients?'),
        '#prefix'=>'<td>',
        '#suffix'=>'</td></tr></table>',
        '#weight' => $fw2++,   
      );
     } else {
      $form['r']['radios'][$key]['protocol'] = array(
        '#type' => 'hidden',
        '#title' => t("Protocol"),
        '#parents' => array('radios',$key,'protocol'),        
        '#value' =>  $radio["protocol"],
        '#description' => $inherit_msg,
        '#prefix'=>'<tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['channel'] = array(
        '#type' => 'hidden',
        '#title' => t("Channel"),
        '#parents' => array('radios',$key,'channel'),        
        '#value' =>  $radio["channel"],
        '#options' => guifi_types('channel',null,null,$radio['protocol']), 
        '#description' => $inherit_msg,
        '#prefix'=>'<td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['clients_accepted'] = array(
        '#type' => 'hidden',
        '#parents' => array('radios',$key,'clients_accepted'),        
        '#value' =>  $radio["clients_accepted"],
        '#weight' => $fw2++,   
        '#prefix'=>'<td>',
        '#suffix'=>'</td></tr></table>',
      ); 
    }

    // Antenna settings group
    $form['r']['radios'][$key]['antenna'] = array(
      '#type' => 'fieldset',
      '#title' => t('Antenna settings'),
      '#collapsible' => true,
      '#collapsed' => false,
      '#tree'=>false,
      '#weight' => $fw2++,
    ); 
    $fw2 = 0;
    $form['r']['radios'][$key]['antenna']['antenna_angle'] = array(
      '#type' => 'select',
      '#title' => t("Type (angle)"),
      '#parents' => array('radios',$key,'antenna_angle'),
      '#default_value' =>  $radio["antenna_angle"],
      '#options' => guifi_types('antenna'),
      '#description' => t('Angle (depends on the type of antena you will use)'),
      '#prefix'=>'<table><tr><td>',
      '#suffix'=>'</td>',
      '#weight' => $fw2++,
    );
    $form['r']['radios'][$key]['antenna']['antenna_gain'] = array(
      '#type' => 'select',
      '#title' => t("Gain"),
      '#parents' => array('radios',$key,'antenna_gain'),
      '#default_value' =>  $radio["antenna_gain"],
      '#options' => drupal_map_assoc(array(2,8,12,14,18,21,24,'more')),
      '#description' => t('Gain (Db)'),
      '#prefix'=>'<td>',
      '#suffix'=>'</td>',
      '#weight' => $fw2++,
    );
    $form['r']['radios'][$key]['antenna']['antenna_azimuth'] = array(
      '#type' => 'textfield',
      '#title' => t('Degrees (º)'),
      '#parents' => array('radios',$key,'antenna_azimuth'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $radio["antenna_azimuth"],
      '#description' => t('Azimuth (0-360º)'),
      '#prefix'=>'<td>',
      '#suffix'=>'</td>',
      '#weight' => $fw2++,
    );
    $form['r']['radios'][$key]['antenna']['antmode'] = array(
      '#type' => 'select',
      '#title' => t("Connector"),
      '#parents' => array('radios',$key,'antmode'),        
  //    '#required' => TRUE,
      '#default_value' =>  $radio["antmode"],
      '#options' => array(
        ''=> 'Don\'t change',
        'Main'=>'Main/Right/Internal',
        'Aux'=>'Aux/Left/External'),
      '#description' => t('Examples:<br>MiniPci: Main/Aux<br>Linksys: Right/Left<br>Nanostation: Internal/External'),
      '#prefix'=>'<td>',
      '#suffix'=>'</td></tr></table>',
      '#weight' => $fw2++,   
    );
}

/* guifi_radio_interfaces_form(): Tadio interfaces form */
function guifi_radio_radio_interfaces_form(&$edit, &$form, $rk, &$weight) {
  global $hotspot;
  global $bridge;

  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_interfaces_form(key=%d)',$rk)); 
  $f = array();

  if (count($edit['radios'][$rk]['interfaces']) == 0)
    return $weight;

  $interfaces_count = 0;
  $ipv4_count = 0;
  $links_count = array();

  foreach ($edit['radios'][$rk]['interfaces'] as $ki => $interface) {
//    guifi_log(GUIFILOG_FULL,'interface',$interface); 
    if ($interface['interface_type'] == null)
      continue;
    if ($interface['deleted'])
      continue;
       
    $interfaces_count++;

    $it = $interface['interface_type'];
    $ilist[$it] = $ki;

    if ($interface['new']) 
      $f[$it][$ki]['new'] = array(
        '#type' => 'hidden',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'new'),
        '#value' => true);
        
    $f[$it][$ki]['id'] = array(
        '#type'=>'hidden',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'id'),
        '#value'=>$ki);
    $f[$it][$ki]['interface_type'] = array(
        '#type'=>'hidden',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'interface_type'),
        '#value'=>$interface['interface_type']);
                
    if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {
      if ($ipv4['deleted'])
        continue;

      $ipv4_count++;
      
      if ($ipv4['new']) 
        $f[$it][$ki]['ipv4'][$ka]['new'] = array(
          '#type' => 'hidden',
          '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'new'),
          '#value' => true);
      
      $links_count[$it] += guifi_ipv4_link_form(
        $f[$it][$ki]['ipv4'][$ka],
        $ipv4,
        $interface,
        array('radios',$rk,'interfaces',$ki,'ipv4',$ka),
        $weight);

    }   // foreach ipv4
    switch ($it) {
    case 'HotSpot':
      $f[$it][$ki]['ipv4'][$ka]['local']['deleteHotspot'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'deleteHotspot'),
        '#attributes'=>array('title'=>t('Delete Hotspot')), 
        '#submit' => array('guifi_interface_delete_submit'),
        '#weight'=>$weight++);      
      $hotspot = true;
      break;
    case 'wds/p2p':
      $f[$it][$ki]['ipv4'][$ka]['local']['AddWDS'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/wdsp2p.png',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'AddWDS'),
        '#attributes'=>array('title'=>t('Add WDS/P2P link to extend the backbone')), 
        '#submit' => array('guifi_radio_add_wds_submit'),
        '#ahah' => array(
          'path' => 'guifi/js/add_wds',
          'wrapper' => 'WDSLinks-'.$ki,
          'method' => 'replace',
          'effect' => 'fade',
         ),
        '#weight'=>$weight++);
        $f[$it][$ki]['ipv4'][$ka]['local']['WDSLinks'] = array(
          '#type' => 'item',
          '#prefix' => '<div id="WDSLinks-'.$ki.'"">',
          '#suffix' => '</div>');             
      break;
    }

  }    // foreach interface

  foreach ($f as $it => $value) {
    //    guifi_log(GUIFILOG_FULL,'building form for: ',$value);
    switch ($it) {
    case 'wLan/Lan':
    case 'wds/p2p':
      $title = $it.' - '.$links_count[$it].' '.t('link(s)');
      break;
    case 'wLan':
      $title = $it.' - '.
        count($value).' '.t('interface(s)').' - '.
        $links_count[$it].' '.t('link(s)');
      break;
    default:
      $title = $it;
    }
    $form['r']['radios'][$rk][$it] = array(
    '#type' => 'fieldset',
    '#title' => $title,
    '#weight' => $weight,
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    );
    $weight++;

    if (!empty($value))
      foreach ($value as $ki => $fin)
        $form['r']['radios'][$rk][$it]['interfaces'][$ki] = $fin; 
    else {
      if ((!$edit['radios'][$rk]['interfaces'][$ilist[$it]]['new']) and 
        ($it != 'wds/p2p') and 
        ($it != 'wLan/Lan'))
        $form['r']['radios'][$rk][$it]['delete_address'] = array(
          '#type' => 'button',
          '#parents'=>array('radios',$rk,'interfaces',$ilist[$it],'delete_interface'),
          '#value'=>t('Delete'),
          '#name'=>implode(',',array(
               '_action',
               '_guifi_delete_radio_interface',
               $rk,$ilist[$it]
               )),
          '#weight' => $weight++,
        );
    }
 }
 
 return array('interfaces'=>$interfaces_count,'ipv4'=>$ipv4_count,'links'=>array_sum($links_count));
}

/* guifi_radio_validate()): Validate radio, called as a hook while validating the form */
function guifi_radio_validate($edit,$form) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_radio_validate()");

/*  if (!(empty($edit['mac']))) { 
    $mac = _guifi_validate_mac($edit['mac']);
    if ($mac) {
      $edit['mac'] = $mac;
    } else {
      form_set_error(
        'mac',
        t('Error in MAC address, use 00:00:00:00:00:00 format.').' '.$edit['mac']);
    }
  }
*/
  if (($edit['variable']['firmware'] != 'n/a') and
    ($edit['variable']['firmware'] != null)) {
    $radio = db_fetch_object(db_query("
      SELECT model
      FROM {guifi_model}
      WHERE mid='%d'",
      $edit['variable']['model_id']));
    if (!guifi_type_relation(
      'firmware',
      $edit['variable']['firmware'],
      $radio->model)) {
      form_set_error('variable][firmware',
        t('This firmware with this radio model is NOT supported.'));
    } 
  }

}

/* guifi_radio_swap_submit(): Action */
function guifi_radio_swap_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_swap_submit()'),$form_state['clicked_button']);
  $old = $form_state['clicked_button']['#parents'][1];
  switch ($form_state['clicked_button']['#parents'][2]) {
    case "up": 
      $new = $old-1; break;
    case "down": 
      $new = $old+1; break;
  }
  $form_state['swapRadios']=$old.','.$new;
  $form_state['action']='guifi_radio_swap';
  $form_state['rebuild'] = true;
  return;
}

/* guifi_radio_swap(): Action */
function guifi_radio_swap($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_swap()'),$form_state['clicked_button']);
  list($old, $new) = explode(',',$form_state['swapRadios']);
  $old_radio = $form_state['values']['radios'][$old];
  $new_radio = $form_state['values']['radios'][$new];
  $form_state['values']['radios'][$new] = $old_radio;
  $form_state['values']['radios'][$old] = $new_radio;
  ksort($form_state['values']['radios']);
  drupal_set_message(t('Radio #%old moved to #%new.',
    array('%old'=>$old,'%new'=>$new)));
  return;
}

/* _guifi_add_wlan_submit(): Action */
function guifi_radio_add_wlan_submit($form, &$form_state) {
  $radio = $form_state['clicked_button']['#parents'][1];
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_add_wlan(%d)',$radio));
  
  $interface = array();
  $interface['new']=true;
  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$form_state['values']);
  //   print_r($ips_allocated);
  $net = guifi_get_subnet_by_nid(
            $form_state['values']['nid'],'255.255.255.224','public',
            $ips_allocated);
  guifi_log(GUIFILOG_FULL,
            "IPs allocated: ".count($ips_allocated).
            " Obtained new net: ".$net."/27");
  $interface['ipv4'][$radio]=array();
  $interface['ipv4'][$radio]['new']=true;
  $interface['ipv4'][$radio]['ipv4']=guifi_ip_op($net);
  $interface['ipv4'][$radio]['netmask']='255.255.255.224';
  $interface['ipv4'][$radio]['links']=array();
  $interface['interface_type']='wLan';
  $form_state['values']['radios'][$radio]['interfaces'][]=$interface;
  $form_state['rebuild'] = true;
  drupal_set_message(t('wLan with %net/%mask added at radio#%radio',
    array('%net'=>$net,'%mask'=>'255.255.255.224','%radio'=>$radio)));
    
  return TRUE;
}

function guifi_radio_add_hotspot_submit($form, &$form_state) {
  $radio = $form_state['clicked_button']['#parents'][1];
  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_add_hotspot_submit(%d)',$radio));

    // filling variables
  $interface=array();
  $interface['new']=true;
  $interface['interface_type']='HotSpot';
  $form_state['values']['radios'][$radio]['interfaces'][] = $interface;
  $form_state['rebuild'] = true;
  drupal_set_message(t('Hotspot added at radio#%radio',
    array('%radio'=>$radio)));
  
  return TRUE;
}

/* Add  aradio to the device */
function guifi_radio_add_radio(&$form_state) {
  guifi_log(GUIFILOG_TRACE, "function guifi_radio_add_radio()",$form_state);

  // wrong form navigation, can't do anything
  if ($form_state['values']['newradio_mode'] == null)
    return TRUE;

  $edit = $form_state['values'];

  // next id
  $rc = 0; // Radio radiodev_counter next pointer
  $tc = 0; // Total active radios

  // fills $rc & $tc proper values
  if (isset($edit['radios'])) foreach ($edit['radios'] as $k=>$r) 
    if ($k+1 > $rc)  {
      $rc = $k+1;
      if (!$edit['radios'][$k][delete])
        $tc++;
    }

    $node=node_load(array('nid'=>$edit['nid']));
    $zone=node_load(array('nid'=>$node->zone_id));
    if (($zone->nick == '') or ($zone->nick == null)) 
      $zone->nick = guifi_abbreviate($zone->nick);
    if (strlen($zone->nick.$edit['nick']) > 10)
      $nick = guifi_abbreviate($edit['nick']);
    else
      $nick = $edit['nick'];
    $ssid=$zone->nick.$nick;

    $radio=array();
    $radio['new']=true;
    $radio['id']=$edit['id'];
    $radio['nid']=$edit['nid'];
    $radio['model_id']=16;
    $radio['mode']=$form_state['values']['newradio_mode'];
    $radio['protocol']='802.11b';
    $radio['channel']=0;
    $radio['antenna_gain']=14;
    $radio['antenna_azimuth']=0;
    $radio['antmode']='Main';
    $radio['interfaces']=array();
    $radio['interfaces'][0]=array();
    $radio['interfaces'][0]['new']=true;
    if ($radio['mode'] == 'ap') {
      $radio['antenna_angle']=120;
      $radio['clients_accepted']="Yes";
      $radio['ssid']=$ssid.'AP'.$rc;
      $radio['interfaces'][0]['interface_type']='wds/p2p';
      // first radio, force wlan/Lan bridge and get an IP
      if ($tc == 0) {
        $radio['interfaces'][1]=array();
        $radio['interfaces'][1]['new']=true;
        $radio['interfaces'][1]['interface_type']='wLan/Lan';
        $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
        $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.224','public',$ips_allocated);
        guifi_log(GUIFILOG_FULL,"IPS allocated: ".count($ips_allocated)." got net: ".$net.'/27');
        $radio['interfaces'][1]['ipv4'][$rc]=array();
        $radio['interfaces'][1]['ipv4'][$rc]['new']=true;
        $radio['interfaces'][1]['ipv4'][$rc]['ipv4']=guifi_ip_op($net);
        guifi_log(GUIFILOG_FULL,"Assigned IP: ".$radio['interfaces'][1]['ipv4'][$rc]['ipv4']);
        $radio['interfaces'][1]['ipv4'][$rc]['netmask']='255.255.255.224';
      } 
      if ($rc == 0)
        $radio['mac']=_guifi_mac_sum($edit['mac'],2);
      else
        $radio['mac']='';
    } else {
      $radio['antenna_angle']=30;
      $radio['clients_accepted']="No";
      $radio['ssid']=$ssid.'CPE'.$rc;
      $radio['interfaces'][0]['new']=true;
      $radio['interfaces'][0]['interface_type']='Wan';
      if ($rc == 0)
        $radio['mac']=_guifi_mac_sum($edit['mac'],1);
      else
        $radio['mac']='';
    }

    $radio['rc'] = $rc;
    return $radio; 
    $form_state['values']['radios'][$rc] = $radio;

}


/* _guifi_device_buttons(): Common function to add confirmation buttons */
function _guifi_device_buttons(&$form,$action,&$fweight = 100,$continue = FALSE) {

  // if continue without save, the _sumit action should be taken while back to the form
  if ($continue)
    $action[1] .= '_submit';
    
  $action_str = implode(',',$action);

  $form['#multistep'] = TRUE;
  $form['#redirect'] = FALSE;

  $form['reset'] = array(
    '#type' => 'submit',
    '#parents' => array('op'),
    '#value' => t('Reset'),
    '#weight' => $fweight++,
  );
/* To do, allow continue when it works safely */
  if ($continue) {
    $form['save_continue'] = array(
      '#type' => 'submit',
      '#parents' => array($action_str),
      '#name' => $action_str,
      '#value' => t('Continue edit'),  
      '#weight' => $fweight++,
  ); 

    return;
  }
/*  */
  $form['save_continue'] = array(
    '#type' => 'submit',
    '#parents' => array($action_str),
    '#name' => $action_str,
    '#value' => t('Save & continue edit'),
    '#weight' => $fweight++,
  );
  $form['save_exit'] = array(
    '#type' => 'submit',
    '#parents' => array($action_str),
    '#name' => $action_str,
    '#value' => t('Save & exit'),
    '#weight' => $fweight++,
  );
}

/* Delete radio interface link */
function _guifi_delete_radio_interface_link(&$form,&$edit,$action) {
  $radio_id    =$action[2];
  $interface_id=$action[3];
  $ipv4_id     =$action[4];
  $link_id     =$action[5];
  $remote_nid  =$action[6];
  $remote_did  =$action[7];
  
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_link(radio: %d, interface: %d)',$radio_id,$interface_id),$action);

  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this link?'),
    '#value' => $edit['radios'][$radio_id]['ssid'].'-'.
  	$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'].'-'.
	guifi_get_nodename($remote_nid).'/'.
	guifi_get_hostname($remote_did),
    '#description' => t('If you save at this point, this link will be deleted, information saved and can\'t be undone.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Delete link (%type)',array('%type'=>$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'])));
  _guifi_device_buttons($form,$action);
  
  return FALSE;
}

function _guifi_delete_radio_interface_link_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  $ipv4_id     =$action[4];
  $link_id     =$action[5];
  $remote_nid  =$action[6];
  $remote_did  =$action[7];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_link_submit(radio: %d, interface: %d)',$radio_id,$interface_id),$action);
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['ipv4'][$ipv4_id]['links'][$link_id]['deleted'] = true;
}

function guifi_radio_delete($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,"function guifi_radio_delete()",$form_state['clicked_button]']);
  drupal_set_message(t('Radio#%num has been deleted.',
    array('%num'=>$form_state['deleteRadio'])));
  $form_state['values']['radios'][$form_state['deleteRadio']]['deleted'] = true;
  unset($form_state['deleteRadio']);
  unset($form_state['action']);
  return;    
}

function guifi_radio_delete_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,"function guifi_radio_delete_submit()",$form_state['clicked_button]']);
  $form_state['deleteRadio'] = $form_state['clicked_button']['#parents'][1];
  $form_state['action'] = 'guifi_radio_delete';
  $form_state['rebuild'] = true;
  return;    
}

/* _guifi_link_2AP(): Link client to an AP */
function _guifi_link_2AP(&$form,&$edit,$action) {


  /*  print "Hola add Link 2AP!!\n<br>";
  print_r($action);
  print_r($edit['filters']);
  /*
  exit;

  /* read input parameters */
  $radio_id=$action[2];
  $interface_id=$action[3];

  $form_weight = 0;


  /*  if (!isset($edit['filters'])) {

  // get list of the current used ips
  $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);

  // Initializing variables
  $newlk = array();
  $newlk['new']=true;
  $newlk['link_type']='wds';
  $newlk['flag']='Planned';
  $newlk['link_type']='ap/client';
  $newlk['routing'] = 'Gateway';
  // if in mode AP
  if ($edit[radios][$radio_id]['mode'] == 'ap') {
    $base_ip[ipv4]=$edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][ipv4];
    $base_ip[netmask]=$edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][netmask];
    $item = _ipcalc($base_ip[ipv4],$base_ip[netmask]);
    $ip= guifi_next_ip($item['netid'],$base_ip[netmask],$ips_allocated);
    if ($ip == null) return;
      $newlk['interface'] = array();
    $newlk['interface'][interface_type] = 'Wan';
    $newlk['interface'][ipv4] = array();
    $newlk['interface'][ipv4]['new'] = true;
    $newlk['interface'][ipv4][ipv4] = $ip;
    $newlk['interface'][ipv4][netmask] = $base_ip[netmask];
  } else {
  // if in mode client
    $edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][ipv4]='';
  }
  $edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][links][]=$newlk;
  end($edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][links]);
  $newlink_id = key($edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][links]);
  $edit[edit_details]=implode(',',array($radio_id,$i_key,$radio_id,$newlink_id));
  }
*/

  // initialize filters
  if (empty($edit['filters'])) 
  $edit['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 5,
    'search' => null,
    'type'   => 'ap/client',
    'mode'   => $edit['radios'][$radio_id]['mode'],
    'from_node' => $edit['nid'],
    'from_device' => $edit['id'],
    'from_radio' => $radio_id, 
    'azimuth' => "0,360",
  ); 


  // Filter form
  guifi_devices_select_filter($form,implode(',',$action),$edit['filters'],$form_weight);

  // Main select form
  $form['links'] = array(
    '#type' => 'select',
    '#parents'=> array('radios',$radio_id,'interfaces',$interface_id,'ipv4',$newlink_id,'links',$newlink_id,'linked'),
    '#title' => t('select the device which do you like to link with'),
    '#options' => guifi_devices_select($edit['filters']),
    '#description' => t('If you save at this point, link will be created and information saved.'),
    '#weight' => $form_weight++,
    );

  // Filter buttons
  _guifi_device_buttons($form,$action,$form_weight);

  drupal_set_title(t('Choose an AP from the list to link with %ssid',array('%ssid'=> $edit['radios'][$radio_id]['ssid'])));

  return FALSE;

}

/* _guifi_add_wds(): Add WDS/p2p link */
function guifi_radio_add_wds_form(&$form,&$form_state) {
  $radio_id    =$form_state['values']['#parents'][1];
  $interface_id=$form_state['values']['#parents'][3];
  guifi_log(GUIFILOG_TRACE,sprintf("function _guifi_add_wds(Radio: %d, Interface: %d)",$radio_id,$interface_id));

  // read input parameters
  $form_weight = 0;

  // store all the form_stat values
  guifi_form_hidden($form,$form_state['values'],$form_weight);

  // initialize filters
  if (empty($form_state['values']['filters']))
  $form_state['values']['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 15,
    'search' => null,
    'type'   => 'wds',
    'mode'   => $form_state['values']['radios'][$radio_id]['mode'],
    'from_node' => $form_state['values']['nid'],
    'from_device' => $form_state['values']['id'],
    'from_radio' => $radio_id,
    'azimuth' => "0,360",
  );

  drupal_set_title(t(
    'Choose an AP from the list to link with %ssid',
    array(
      '%ssid'=> $form_state['values']['radios'][$radio_id]['ssid'])));

  // Filter form
  guifi_devices_select_filter(
    $form,
    implode(',',$action),
    $form_state['values']['filters'],
    $form_weight);

  $choices = guifi_devices_select($form_state['values']['filters']);

  if (count($choices) == 0) {
    $form['help'] = array(
      '#type' => 'item',
      '#parents'=> array('dummy'),
      '#title' => t('No devices available'),
      '#value'=> t('There are no devices to link within the given criteria, you can use the filters to get more results.'),
      '#description' => t('...or go back to the previous page'),
      '#weight' => 0,
    );
    return FALSE;
  }

  $form['links'] = array(
    '#type' => 'select',
    '#parents'=> array('linked'),
    '#title' => t('select the device which do you like to link with'),
    '#options' => $choices,
    '#description' => t('If you save at this point, link will be created and information saved.'),
    '#weight' => 0,
  );


  _guifi_device_buttons($form,$action,$form_weight);

  return FALSE;
}

function guifi_radio_add_wds_submit(&$form,&$form_state) {
  $radio_id    =$form_state['values']['#parents'][1];
  $interface_id=$form_state['values']['#parents'][3];
  guifi_log(GUIFILOG_BASIC,sprintf("function guifi_radio_add_wds(Radio: %d, Interface: %d)",$radio_id,$interface_id));
//  guifi_log(GUIFILOG_FULL,"linked",$form_state['linked']);

  // get list of the current used ips
  $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$form_state['values']);
  
  //
  // initializing WDS/p2p link parameters
  //
  $newlk['new']=true;
  $newlk['interface']=array();
  list($newlk['nid'],
    $newlk['device_id'],
    $newlk['interface']['radiodev_counter']) =  
        explode(',',$form_state['values']['linked']);
        
  guifi_log(GUIFILOG_FULL,"newlk",$newlk);
  
  $newlk['link_type']='wds';
  $newlk['flag']='Planned';
  $newlk['routing'] = 'BGP';
  // get an ip addres for local-remote interfaces
  $net = guifi_get_subnet_by_nid($form_state['values']['nid'],
            '255.255.255.252',
            'backbone',
            $ips_allocated);
  $ip1 = guifi_ip_op($net);
  $ip2 = guifi_ip_op($ip1);
  guifi_merge_ip(array('ipv4'=>$ip1,'netmask'=>'255.255.255.252'),$ips_allocated,false);
  guifi_merge_ip(array('ipv4'=>$ip2,'netmask'=>'255.255.255.252'),$ips_allocated,true);
  // getting remote interface
  $remote_interface = 
    db_fetch_array(db_query(
        "SELECT id " .
        "FROM {guifi_interfaces} " .
        "WHERE device_id = %d " .
        "   AND interface_type = 'wds/p2p' " .
        "   AND radiodev_counter = %d",
        $newlk['device_id'],$newlk['interface']['radiodev_counter']));
  $newlk['interface']['id']=$remote_interface['id'];
  $newlk['interface']['device_id']=$newlk['device_id'];
  $newlk['interface']['interface_type']='wds/p2p';

  // remote ipv4
  $newlk['interface']['ipv4']=array();
  $newlk['interface']['ipv4']['new'] = true;
  $newlk['interface']['ipv4']['interface_id']=$remote_interface['id'];
  $newlk['interface']['ipv4']['ipv4']=$ip2;
  $newlk['interface']['ipv4']['netmask']='255.255.255.252';

  // initializing local interface
  $newif = array();
  $newif['new']=true;
  $newif['ipv4']=$ip1;
  $newif['netmask']='255.255.255.252';
  // agregating into the main array
  $newif['links'] = array();
  $newif['links'][] = $newlk;
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['ipv4'][]=$newif;
}

//function _guifi_radio_mac_process($element, $form_state) {
//  if ($element['#value']=='')
//    $element['#value']  = $element['#default_value'];
//  return $element;
//}

?>
