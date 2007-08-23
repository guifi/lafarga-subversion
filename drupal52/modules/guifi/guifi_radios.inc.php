<?php

function guifi_radio_form(&$edit) {
  global $hotspot;
  global $bridge;
  global $user;


  guifi_log(GUIFILOG_TRACE,'function guifi_radio_form()');
  unset($sidebar_left);

  $querymid = db_query("SELECT mid, model, f.nom manufacturer FROM guifi_model m, guifi_manufacturer f WHERE f.fid = m.fid AND supported='Yes'");
  while ($model = db_fetch_array($querymid)) {
     $models_array[$model["mid"]] = $model["manufacturer"] .", " .$model["model"];
  }

  // Begin Drupal 4.7 code
  $form['radio_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Device model, firmware & MAC address').' ('.$edit['variable']['firmware'].')',
    '#weight' => $form_weight++,
    '#collapsible' => TRUE,
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
    '#required' => FALSE,
    '#size' => 17,
    '#maxlength' => 17,
    '#default_value' => $edit['mac'],
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
    '#weight' => $form_weight++,
  );
//  $form['r']['radios'] = array('#tree'=>TRUE);
  $rc = 0;
  $bridge = false;
  if (!empty($edit['radios'])) foreach ($edit['radios'] as $key => $radio) {
    $hotspot = false;
      if ($radio['deleted']) continue;
    _guifi_radio_form($form,$radio,$key,$form_weight);

    $buttons_weight = guifi_radio_interfaces_form($edit, $form, $key, 6);

    // Going to paint the buttons

    // For AP Mode, clients_accepted
    if ($radio['mode'] == 'ap') { 
      // If no wLan interface, allow to create one
      if ((count($radio['interfaces']) < 2) or (user_access('administer guifi networks'))) {
        $form['r']['radios'][$key]['AddwLan'] = array(
          '#type'=>'submit',
          '#parents'=>array('radios',$key,'AddwLan'),
          '#value'=>t('Add wLan for clients'),
          '#name'=>'_action,_guifi_add_wlan,'.$key, 
          '#weight'=>$buttons_weight++);
      }
      if (!$hotspot) {
        $form['r']['radios'][$key]['AddHotspot'] = array(
          '#type'=>'submit',
          '#parents'=>array('radios',$key,'AddHotSpot'),
          '#value'=>t('Add Hotspot for guests'), 
          '#name'=>'_action,_guifi_add_hotspot,'.$key,
          '#weight'=>$buttons_weight++);
      }
    } else {
      // Mode Client or client-routed, allow to link to AP
      $cr = guifi_count_radio_links($radio);
      if ($cr['ap']==0)
        $form['r']['radios'][$key]['AddLink2AP'] = array(
          '#type'=>'submit',
          '#parents'=>array('radios',$key,'AddLink2AP'),
          '#value'=>t('Link to AP'), 
          '#name'=>'_action,_guifi_link_2ap,'.$key,
          '#weight'=>$buttons_weight++);
    }   

    // Only allow delete and move functions if the radio has been saved 
    if ($radio['new']==false)  {
      // Only allow delete radio if several when is not the first 
      if ((count($edit['radios'])==1) or ($key))
      $form['r']['radios'][$key]['delete'] = array(
        '#type'=>'submit',
        '#parents'=>array('radios',$key,'delete'),
        '#value'=>t('Delete radio'), 
        '#name'=>'_action,_guifi_delete_radio,'.$key,
        '#weight'=>$buttons_weight++);
      $form['r']['radios'][$key]['change'] = array(
        '#type'=>'submit','#value'=>t('Move to another device'), 
        '#parents'=>array('radios',$key,'change'),
        '#name'=>'_action,_guifi_move_radio'.$key,
        '#weight'=>$buttons_weight++);
    }

    // if not first, allow to move up  
    if ($rc) 
      $form['r']['radios'][$key]['up'] = array(
        '#type'=>'submit','#value'=>t('Up'), 
        '#parents'=>array('radios',$key,'up'),
        '#name'=>'_action,_guifi_move_radio_updown,'.$key.','.($key-1),
        '#weight'=>$buttons_weight++);
    // if not last, allow to move down
    if (($rc+1) < count($edit['radios'])) 
      $form['r']['radios'][$key]['down'] = array(
        '#type'=>'submit','#value'=>t('Down'), 
        '#parents'=>array('radios',$key,'down'),
        '#name'=>'_action,_guifi_move_radio_updown,'.$key.','.($key+1),
        '#weight'=>$buttons_weight++);

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
        '#parents' => array('r','newradio_mode'),
        '#required' => FALSE,
        '#default_value' =>  'client',
        '#options' => $modes_arr,
        '#prefix' => '<table  style="width: 100%"><th colspan="0">'.t('New radio (mode)').'</th><tr><td  style="width: 0" align="right">',
        '#suffix' => '</td>',
        '#weight' => 20); 
      $form['r']['AddRadio'] = array(
        '#type' => 'button',
        '#parents' => array('r','AddRadio'),
        '#value' => t('Add new radio'),
        '#name'=>'_action,_guifi_add_radio',
        '#prefix' => '<td style="width: 10em" align="left">',
        '#suffix' => '</td><td style="width: 100%" align="right">&nbsp</td></tr>',
        '#weight' => 21,
       );
       $form['r']['help_addradio'] = array(
        '#type' => 'item',
        '#description' => t('Usage:<br />Choose <strong>wireless client</strong> mode for a normal station with full access to the network. That\'s the right choice in general.<br />Use the other available options only for the appropiate cases and being sure of what you are doing and what does it means. Note that you might require to be authorized by networks administrators for doing this.<br />Youwill not be able to define you link and get connected to the network until you add at least one radio.'),
        '#prefix' => '<tr><td colspan="0">',
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
    

 //   $erow[] = array(
 //              array('data'=>form_select(t('Mode'), 'newradio_mode', 'client', $modes_arr, NULL),'valign'=>'bottom'),
 //              array('data'=>form_button(t('Add radio'), 'op'),'valign'=>'bottom')
 //                  );
 //   $form .= form_group(t('Add new radio'),theme('table',array(),$erow),t('Usage:<br />Choose <strong>wireless client</strong>mode for a normal station with full access to the network. That\'s the right choice in general.<br />Use the other available options only for the appropiate cases and being sure of what you are doing and what does it means. Note that you might require to be authorized by networks administrators for doing this.<br />Youwill not be able to define you link and get connected to the network until you add at least one radio.'));
//  } else {  
//    $edit_form .= form_item(null,t('You can add radios to this device once has been saved into de database'));
//    $form .= form_group(t('Add new radio'),$edit_form,null);


  return $form;

  // End Drupal 4.7 code
  $rows[] = array(
                array('data'=>form_select(t('Radio Model'), 'variable][model_id', $edit["variable"]["model_id"], $models_array, t('Radio model')),'valign'=>'top'),
                array('data'=>form_select(t('Firmware'), 'variable][firmware', $edit["variable"]["firmware"], guifi_types('firmware') , t('Used for automatic configuration.')),'valign'=>'top'),
                array('data'=>form_textfield(t("Device MAC Address"), "mac", $edit["mac"], 17, 17,  t('Base/Main MAC Address.<br />Some configurations won\'t work if s blank')),'valign'=>'top')
                 );

  $form = form_group(t('Radio main information'),theme('table',null,$rows));

  unset($edit_form);
  unset($rows);

  // edit details?
  if ($edit['edit_details'] != "") {
    $key_detail = explode(',',$edit['edit_details']);
//    $form .= guifi_form_hidden('radios]['.$key_detail[0].']',$edit['radios'][$key_detail[0]]);

    if (!is_numeric($key_detail[0]))
      // not editing radio details
      return;

    switch (count($key_detail)) {
    case 1:
      // Radio details
      if ($edit[radios][$key_detail[0]][mode] == 'ap') {
        $ssid=form_textfield(t('SSID'), 'radios]['.$key_detail[0].'][ssid', $edit['radios'][$key_detail[0]]["ssid"],20,80,t('How will appear to the surveys'));
        $clients_accepted = form_select(t('Clients'), 'radios]['.$key_detail[0].'][clients_accepted', $edit['radios'][$key_detail[0]]["clients_accepted"],
                           drupal_map_assoc(array( 0=>'Yes',1=>'No')),
                           t('Do this radio accept wiereless connections?'));
      } else
        $ssid=form_item(t('SSID'), $edit['radios'][$key_detail[0]]["ssid"]);
      $form .= t('Editing Radio#').': '.$key_detail[0];
      // Edit radio details form
      // Wireless
      $radiorows[] = array(
//                      form_select(t('Mode'), 'radios]['.$key_detail[0].'][mode', $edit['radios'][$key_detail[0]]["mode"], guifi_types('mode'), NULL),
//                      form_item(t('Mode'), $edit['radios'][$key_detail[0]]["mode"]),
                      form_textfield(t('Wireless MAC'), 'radios]['.$key_detail[0].'][mac', $edit['radios'][$key_detail[0]]["mac"],17,17),
                      form_select(t('Protocol'), 'radios]['.$key_detail[0].'][protocol', $edit['radios'][$key_detail[0]]["protocol"], guifi_types('protocol'), NULL),
                      form_select(t('Channel'), 'radios]['.$key_detail[0].'][channel', $edit['radios'][$key_detail[0]]["channel"],
                           guifi_types('channel',null,null,$edit['radios'][$key_detail[0]]['protocol']), NULL)
                      );
      $radiorows[] = array(
                      array('data'=>form_select(t('Antenna Type'), 'radios]['.$key_detail[0].'][antenna_angle', $edit['radios'][$key_detail[0]]["antenna_angle"], guifi_types('antenna'), t('angle coverage')),'valign'=>'top'),
                      array('data'=>form_select(t('Gain'), 'radios]['.$key_detail[0].'][antenna_gain', $edit['radios'][$key_detail[0]]["antenna_gain"], drupal_map_assoc(array(2,8,12,14,18,21,24,'more')), t('dB')),'valign'=>'top'),
                      array('data'=>form_textfield(t('Orientation'), 'radios]['.$key_detail[0].'][antenna_azimuth', $edit['radios'][$key_detail[0]]["antenna_azimuth"], 3,3, t('Azimuth in degrees')),'valign'=>'top')
                     );
      $radiorows[] = array(array('data'=>$ssid,'colspan'=>1),array('data'=>$clients_accepted,'colspan'=>7));
//      $radiorows[] = array(array('data'=>$ssid,'colspan'=>1),
//                     array('data'=>form_select(null, 'radios]['.$key_detail[0].'][clients_accepted', $edit['radios'][$key_detail[0]]["clients_accepted"], 
//                           drupal_map_assoc(array( 0=>'Yes',1=>'No')),
//                           null),'colspan'=>7));
      $radiorows[] = array(
                           form_button(t('Back to list'), 'op')
                          );

      $form .= form_group(t('Wireless Configuration').'-'.t('Mode').': '.$edit['radios'][$key_detail[0]]["mode"],theme('table',null,$radiorows));
      break;
    case 4:
      $form = guifi_links_form($edit['edit_details'],$edit);
      break;
    }
  } 

  // list radios
  if (!isset($edit[edit_details]))
  if (!empty($edit['radios'])) foreach ($edit['radios'] as $key => $radio) {
      if ($radio['deleted']) continue;
      unset($rrows);
      $rrows = array();
//      print_r($radio);
//      $form .= guifi_form_hidden('radios]['.$key.']',$radio);

      // Present radio information & radio group
      $row = array(
                    array('data'=>'#'.$key.form_radio('', 'edit_details', $key),'rowspan'=>"0",'valign'=>'top','width'=>1),
                    array('data'=>$radio['mode'],'width'=>1),
//                    form_textfield(null, 'radios]['.$key.'][ssid', $edit['radios'][$key]["ssid"],20,20),
                    array('data'=>form_select(null, 'radios]['.$key.'][channel', $edit['radios'][$key]["channel"], 
                           //  drupal_map_assoc(array( 0=>'Auto',1=>1,2,3,4,5,6,7,8,9,10,11,12,13,14))
                           guifi_types('channel',null,null,$edit['radios'][$key]['protocol']), NULL),'width'=>1),
                    array('data'=>form_textfield(null, 'radios]['.$key.'][ssid', $edit['radios'][$key]["ssid"],20,80),'width'=>1),
                    array('data'=>form_textfield(null, 'radios]['.$key.'][mac',  $edit['radios'][$key]["mac"],17,17),'width'=>1),
                    array('data'=>form_select(null, 'radios]['.$key.'][clients_accepted', $edit['radios'][$key]["clients_accepted"], 
                           drupal_map_assoc(array( 0=>'Yes',1=>'No')),
                           null),'width'=>1),
                    array('data'=>$radio['protocol'],'width'=>1),
                    array('data'=>form_select(null, 'radios]['.$key.'][antenna_gain', $edit['radios'][$key]["antenna_gain"], drupal_map_assoc(array(2,8,12,14,18,21,24,'more')), null),'width'=>1),
                    array('data'=>$radio['antenna_angle'].'º','align'=>'right','width'=>1),
                    array('data'=>$radio['antenna_azimuth'].'º','align'=>'right','width'=>1)
                   );
      $rrows[] = $row;

      // radio interfaces
//      print_r($edit);

      $hotspot = false;

      if (count($radio['interfaces'])>0) 
      foreach ($radio['interfaces'] as $ki=>$interface) {
        if ($interface[deleted]) continue;

        unset($wlan_addr);

//     print "type: $interface[interface_type]\n<br />";
        switch ($interface[interface_type]) {
        case 'wds/p2p':
          $iname = $interface[interface_type];
          $add_link = t('Add WDS/bridge p2p link');
          break;
        case 'HotSpot':
          $iname = form_radio('', 'edit_details', $key.','.$ki).$interface[interface_type];
          $hotspot = true;
          break;
        case 'wLan/Lan':
        case 'wLan':
          if ($interface[interface_type] == 'wLan')
            $iname = form_radio('', 'edit_details', $key.','.$ki).$interface[interface_type];
          else 
            $iname = $interface[interface_type];
          $add_link = t('Add AP/Client link');

		  if (user_access('administer guifi networks')) {
            $wlan_addr[] = array('data'=>form_textfield(null,'radios['.$key.'][interfaces]['.$ki.'][ipv4]['.$key.'][ipv4]',
                                                           $interface[ipv4][$key][ipv4],16,16,null),'width=1');
            $wlan_addr[] = array('data'=>form_select(null,'radios['.$key.'][interfaces]['.$ki.'][ipv4]['.$key.'][netmask]',
                                                           $interface[ipv4][$key][netmask],guifi_types('netmask',30,15),null),'colspan'=>1,'width=1');

          } else
            $wlan_addr = array('data'=>$interface[ipv4][$key][ipv4].'/'.$interface[ipv4][$key][netmask],'width'=>1);;

          break;
        case 'Wan':
          $cr = guifi_count_radio_links($radio);
          if ($cr['ap']==0) 
            $add_link = t('Link to AP');
         break;
        }

//        print_r($interface);
//        print "\n<br>";

        if ($add_link != '') {
          if (isset($wlan_addr)) {
            $rrows[] = array_merge(array(array('data'=>$iname,'width'=>1)),
                                  $wlan_addr,
                                  array(array('data'=>form_button($add_link, 'op['.$interface[id].']'),'colspan'=>'1'))
                                 );
          } else
            $rrows[] = array(array('data'=>$iname,'width'=>1),
                            array('data'=>form_button($add_link, 'op['.$interface[id].']'),'colspan'=>0));
        } else
          $rrows[] = array(array('data'=>$iname,'colspan'=>0));

        if (count($interface['ipv4']))
        unset($lrows);
        $lrows = array();
        if (count($interface[ipv4]) > 0) foreach ($interface['ipv4'] as $ka=>$ipv4) 
        if (!empty($ipv4[links])) foreach ($ipv4['links'] as $kl=>$link) {
          if ($link[deleted]) continue;

          // fill routing field
          if (user_access('administer guifi networks'))
             $routing = form_select(null,'radios]['.$key.'][interfaces]['.$ki.'][ipv4]['.$ka.'][links]['.$kl.'][routing', $link['routing'], guifi_types('routing'));
          else
             $routing = $link[routing];

          $ip = _ipcalc($ipv4['ipv4'],$ipv4['netmask']);
//          print_r($link);
          $lrows[] = array(
                           form_radio('', 'edit_details', $key.','.$ki.','.$ka.','.$kl),
                           guifi_get_nodename($link['nid']).'-'.
                           guifi_get_hostname($link['device_id']),
                           form_select(null,'radios]['.$key.'][interfaces]['.$ki.'][ipv4]['.$ka.'][links]['.$kl.'][flag', $link['flag'], array_diff(guifi_types('status'),array('Dropped'=>t('Dropped')))),
                           $ipv4['ipv4'].'/'.$ip['maskbits'],
                           $link['interface']['ipv4']['ipv4'],
                           $routing
                          );
        }
        $header = array(null,t('node-device'),t('status'),t('local ip'),t('remote ip'),t('routing'));
        if (count($lrows) > 0)
          $rrows[] = array(array('data'=>theme('table',$header,$lrows),'colspan'=>0));
        else
          $rrows[] = array(array('data'=>theme('table',null,$lrows),'colspan'=>0));
      } // foreach interface

      // If AP & no wLan interface, allow to create one
      if (((count($radio[interfaces]) < 2) and ($radio[mode] == 'ap')) or 
         (user_access('administer guifi networks'))) {
        $buttons = form_button(t('Add wLan for clients'), 'op['.$key.']');
      }
      if ((!$hotspot) and ($radio[mode] == 'ap')) {
        $buttons .= form_button(t('Add Hotspot for guests'), 'op['.$key.']');
      }
      $rrows[] = array(array('data'=>$buttons,'colspan'=>0));

      $rows[] = array(array('data'=>theme('table',null,$rrows),'colspan'=>0));
      
  }
  if (isset($rows)) {
    

    // if net admin or device/node owner, edit allowed
    if ((user_access('administer guifi networks')) || 
        (guifi_get_deviceuser($edit['user_created'] == $user->uid)) || 
        (guifi_get_nodeuser($edit['nid'] == $user->uid)))
      $rows[] = array(array('data'=>form_button(t('Edit selected'), 'op').form_button(t('Delete selected'), 'op'),'colspan'=>8));


    $headers = array(null,t('mode'),t('channel'),'ssid',t('wireless mac'),t('clients'),t('protocol'),t('ant. gain'),'<p align="right">'.t('angle').'</p>','<p align="right">'.t('azimuth').'</p>');
    $form .= form_group(t('device radios'),theme('table', $headers, $rows),t('Use this form section to describe all wireless linked devices.'));
  }

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
  }
//  print "Max radios: ".$maxradios->radiodev_max." Current: $cr Total: $tr Firewall: $firewall\n<br />";
  $modes_arr = guifi_types('mode');
//  print_r($modes_arr);
  if ($cr>0)
    if (!$firewall)
      $modes_arr = array_diff_key($modes_arr,array('client'=>0));
    else
      $modes_arr = array_intersect_key($modes_arr,array('client'=>0));
  if ($cr < $maxradios->radiodev_max)
  if ( (( $edit['id'] > 0 ) && (!isset($edit[edit_details]))) and ($tr < $maxradios->radiodev_max)) {
    $erow[] = array(
               array('data'=>form_select(t('Mode'), 'newradio_mode', 'client', $modes_arr, NULL),'valign'=>'bottom'),
               array('data'=>form_button(t('Add radio'), 'op'),'valign'=>'bottom')
                   );
    $form .= form_group(t('Add new radio'),theme('table',null,$erow),t('Usage:<br />Choose <strong>wireless client</strong>mode for a normal station with full access to the network. That\'s the right choice in general.<br />Use the other available options only for the appropiate cases and being sure of what you are doing and what does it means. Note that you might require to be authorized by networks administrators for doing this.<br />Youwill not be able to define you link and get connected to the network until you add at least one radio.'));
  } else {  
    $edit_form .= form_item(null,t('You can add radios to this device once has been saved into de database'));
    $form .= form_group(t('Add new radio'),$edit_form,null);
  }

  return $form;
}

function _guifi_radio_form(&$form, $radio, $key, &$form_weight = -200) {


    if ($radio['new']) {
//      drupal_set_message(t('Adding a new radio, setting the partameters.').
//                     '<br>'.t('Mode:').' '.$radio['mode'].' #'.$key.' '.$form_weight.
//                     '&nbsp'); 
      $collapsed = false;
    } else
      $collapsed = true;

    $fw2 = 0;

    $form['r']['radios'][$key] = array(
      '#type' => 'fieldset',
      '#title' => t('Radio #').$key.' - '.$radio['mode'].' - '.$radio['ssid'],
      '#collapsible' => $collapsed,
      '#collapsed' => $collapsed,
      '#tree'=> TRUE,
      '#weight' => $form_weight++,
    );
    if ($radio['mode'] == 'ap')
      $form['r']['radios'][$key]['ssid'] = array(     
        '#type' => 'textfield',
        '#parents'=> array('radios',$key,'ssid'),
        '#title' => t('SSID'),
        '#required' => TRUE,
        '#size' => 30,
        '#maxlength' => 30,
        '#default_value' => $radio['ssid'],
        '#description' => t("SSID to identify this radio signal."),
        '#prefix'=>'<table><tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++
      );
    else {
      $inherit_msg = t('Will take it from the connected AP.');
      $form['r']['radios'][$key]['ssid'] = array(     
        '#type' => 'hidden',
        '#parents'=> array('radios',$key,'ssid'),
        '#title' => t('SSID'),
        '#value' => $radio['ssid'],
        '#description' => $inherit_msg,
        '#prefix'=>'<table><tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++
      );
    }
    $form['r']['radios'][$key]['mac'] = array(
      '#type' => 'textfield',
      '#parents'=> array('radios',$key,'mac'),
      '#title' => t('MAC'),
      '#size' => 17,
      '#maxlength' => 17,
      '#default_value' => $radio['mac'],
      '#description' => t("Wireless MAC Address.<br />Some configurations won't work if is blank"),
      '#prefix'=>'<td>',
      '#suffix'=>'</td></tr>',
      '#weight' => $fw2++
    );
    if ($radio['mode'] == 'ap') {
      $form['r']['radios'][$key]['protocol'] = array(
        '#type' => 'select',
        '#parents'=> array('radios',$key,'protocol'),
        '#title' => t("Protocol"),
        '#required' => TRUE,
        '#default_value' =>  $radio["protocol"],
        '#options' => guifi_types('protocol'),
        '#description' => t('Select the protocol where this radio will operate.'),
        '#prefix'=>'<tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['channel'] = array(
        '#type' => 'select',
        '#parents'=> array('radios',$key,'channel'),
        '#title' => t("Channel"),
        '#required' => TRUE,
        '#default_value' =>  $radio["channel"],
        '#options' => guifi_types('channel',null,null,$radio['protocol']), 
        '#description' => t('Select the channel where this radio will operate.'),
        '#prefix'=>'<td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['clients_accepted'] = array(
        '#type' => 'select',
        '#parents'=> array('radios',$key,'clients_accepted'),
        '#title' => t("Clients accepted?"),
        '#required' => TRUE,
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
        '#parents'=> array('radios',$key,'protocol'),
        '#title' => t("Protocol"),
        '#value' =>  $radio["protocol"],
        '#description' => $inherit_msg,
        '#prefix'=>'<tr><td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['channel'] = array(
        '#type' => 'hidden',
        '#parents'=> array('radios',$key,'channel'),
        '#title' => t("Channel"),
        '#value' =>  $radio["channel"],
        '#options' => guifi_types('channel',null,null,$radio['protocol']), 
        '#description' => $inherit_msg,
        '#prefix'=>'<td>',
        '#suffix'=>'</td>',
        '#weight' => $fw2++,   
      );
      $form['r']['radios'][$key]['clients_accepted'] = array(
        '#type' => 'hidden',
        '#parents'=> array('radios',$key,'clients_accepted'),
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
      '#collapsible' => $collapsed,
      '#collapsed' => $collapsed,
      '#weight' => $fw2++,
    ); 
    $fw2 = 0;
    $form['r']['radios'][$key]['antenna']['antenna_angle'] = array(
      '#parents' => array('radios',$key,'antenna_angle'),
      '#type' => 'select',
      '#title' => t("Type (angle)"),
      '#default_value' =>  $radio["antenna_angle"],
      '#options' => guifi_types('antenna'),
      '#description' => t('Angle (depends on the type of antena you will use)'),
      '#prefix'=>'<table><tr><td>',
      '#suffix'=>'</td>',
      '#weight' => $fw2++,
    );
    $form['r']['radios'][$key]['antenna']['antenna_gain'] = array(
      '#parents' => array('radios',$key,'antenna_gain'),
      '#type' => 'select',
      '#title' => t("Antenna gain"),
      '#default_value' =>  $radio["antenna_gain"],
      '#options' => drupal_map_assoc(array(2,8,12,14,18,21,24,'more')),
      '#description' => t('db gain'),
      '#prefix'=>'<td>',
      '#suffix'=>'</td>',
      '#weight' => $fw2++,
    );
    $form['r']['radios'][$key]['antenna']['antenna_azimuth'] = array(
      '#parents' => array('radios',$key,'antenna_azimuth'),
      '#type' => 'textfield',
      '#title' => t('Degrees (º)'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $radio['antenna_azimuth'],
      '#description' => t('Azimuth in degrees')." (0-360º)",
      '#prefix'=>'<td>',
      '#suffix'=>'</td></tr></table>',
      '#weight' => $fw2++,
    );
}


function guifi_radio_interfaces_form(&$edit, &$form, $rk, $weight = 6) {
  global $hotspot;
  global $bridge;

  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_radio_interfaces_form(key=%d)',$rk)); 
  $f = array();

  if (count($edit['radios'][$rk]['interfaces']) == 0)
    return $weight;

  unset($ilist);
  foreach ($edit['radios'][$rk]['interfaces'] as $ki => $interface) {
//    guifi_log(GUIFILOG_FULL,'interface',$interface); 
    if ($interface['interface_type'] == null)
      continue;

    $it = $interface['interface_type'];
    $ilist[$it] = $ki;
    $links_count[$it] = 0;

    if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {

//      print_r($ipv4);
      if ($interface['interface_type'] == 'wLan/Lan')
        $bridge = true; 
      if (($ipv4['netmask'] != '255.255.255.252')  or (count($ipv4['links']) == 0))
      {
        // multilink set
        $multilink = TRUE; 
        $f[$it][$ki]['ipv4'][$ka] = array(
          '#type' => 'fieldset',
          '#title' => $ipv4['ipv4'].' / '.$ipv4['netmask'].' '.(count($ipv4['links'])).' '.t('link(s)'),
          '#weight' => $weight++,
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
          '#weight' => $weight++,
        );
        $prefix = '<table><tr><td>';
        if (user_access('administer guifi networks')) {
          $f[$it][$ki]['ipv4'][$ka]['local']['ipv4'] = array(
            '#type'=> 'textfield',
            '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'ipv4'),
            '#size'=> 16,
            '#maxlength'=>16,
            '#default_value'=>$interface['ipv4'][$ka]['ipv4'],
            '#title'=>t('Local IPv4'),
            '#prefix'=> $prefix,
            '#suffix'=> '</td>',
            '#weight'=> 0,
          );
          $f[$it][$ki]['ipv4'][$ka]['local']['netmask'] = array(
            '#type' => 'select',
            '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'netmask'),
            '#title' => t("Network mask"),
            '#default_value' => $interface['ipv4'][$ka]['netmask'],
            '#options' => guifi_types('netmask',30,0),
            '#prefix'=> '<td>',
            '#suffix'=> '</td>',
            '#weight' =>1,
          );
        } else {
          $f[$it][$ki]['ipv4'][$ka]['local']['ipv4'] = array(
            '#type' => 'item',
            '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'ipv4'),
            '#title' => t('Local IPv4'),
            '#value'=>  $interface['ipv4'][$ka]['ipv4'],
            '#description'=> $interface['ipv4'][$ka]['netmask'],
            '#prefix'=> $prefix,
            '#suffix'=> '</td>',
            '#weight' =>0,
          );
        }
      } else {
        // singlelink set
        $multilink = FALSE;
        $prefix = '<td>';
      } 

      // foreach link
      $lweight = 25;
      if (count($ipv4['links'])) foreach($ipv4['links'] as $kl => $link)  {

        // linked node-device
        if ($link['type'] != 'cable')
          $descr =  guifi_get_ap_ssid($link['device_id'],$link['radiodev_counter']);
        else
          $descr = guifi_get_interface_descr($link['interface_id']);
        $f[$it][$ki]['ipv4'][$ka]['links'][$kl]['link_name'] = array(
          '#type' => 'item',
          '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'links',0,'link_name'),
          '#title' => guifi_get_nodename($link['nid']),
          '#value'=>  guifi_get_hostname($link['device_id']),
          '#description'=>guifi_get_interface_descr($link['interface_id']),
          '#prefix'=> '<table><tr><td>',
          '#suffix'=> '</td>',
          '#weight' => $lweight++,
        );
          
        if (user_access('administer guifi networks')) {
          if (!$multilink)
          $f[$it][$ki]['ipv4'][$ka]['links'][$kl]['ipv4'] = array(
            '#type'=> 'textfield',
            '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'ipv4'),
            '#size'=> 16,
            '#maxlength'=>16,
            '#default_value'=>$interface['ipv4'][$ka]['ipv4'],
            '#title'=>t('Local IPv4'),
            '#prefix'=> $prefix,
            '#suffix'=> '</td>',
            '#weight'=> $lweight++,
          );
          $f[$it][$ki]['ipv4'][$ka]['links'][$kl]['ipv4_remote'] = array(
            '#type'=> 'textfield',
            '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'links',$kl,'interface','ipv4','ipv4'),
            '#size'=> 16,
            '#maxlength'=>16,
            '#default_value'=>$interface['ipv4'][$ka]['links'][$kl]['interface']['ipv4']['ipv4'],
            '#title'=>t('Remote IPv4'),
            '#prefix'=> '<td>',
            '#suffix'=> '</td>',
            '#weight'=> $lweight++,
          );

          if (!$multilink)
          $f[$it][$ki]['ipv4'][$ka]['links'][$kl]['netmask'] = array(
            '#type' => 'select',
            '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'netmask'),
            '#title' => t("Network mask"),
            '#default_value' => $interface['ipv4'][$ka]['netmask'],
            '#options' => guifi_types('netmask',30,0),
            '#prefix'=> '<td>',
            '#suffix'=> '</td>',
            '#weight' =>  $lweight++,
          );

        } else {
          $f[$it][$ki]['ipv4'][$ka]['links'][$kl]['ipv4_remote'] = array(
            '#type' =>         'item',
            '#parents'=>       array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'links',$kl,'interface','ipv4','ipv4'),
            '#title'=>         t('Remote IPv4'),
            '#value'=>         $interface['ipv4'][$ka]['links'][$kl]['interface']['ipv4']['ipv4'],
            '#description' =>  $interface['ipv4'][$ka]['links'][$kl]['interface']['ipv4']['netmask'], 
            '#prefix'=>        '<td>',
            '#suffix'=>        '</td>',
            '#weight' =>       $lweight++,
          );
        } // network administrator
        
        // Routing
        $f[$it][$ki]['ipv4'][$ka]['links'][$kl]['routing'] = array(
          '#type' =>          'select',
          '#parents'=>        array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'links',$kl,'routing'),
          '#title' =>         t("Routing"),
          '#default_value' => $interface['ipv4'][$ka]['links'][$kl]['routing'],
          '#options' =>       guifi_types('routing'),
          '#prefix'=>         '<td>',
          '#suffix'=>         '</td>',
          '#weight' =>        $lweight++,
        );

        // Status
        $f[$it][$ki]['ipv4'][$ka]['links'][$kl]['status'] = array(
          '#type' =>          'select',
          '#parents'=>        array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'links',$kl,'flag'),
          '#title' =>         t("Status"),
          '#default_value' => $interface['ipv4'][$ka]['links'][$kl]['flag'],
          '#options' =>       guifi_types('status'),
          '#prefix'=>         '<td>',
          '#suffix'=>         '</td>',
          '#weight' =>        $lweight++,
        );

        // delete link button
        $f[$it][$ki]['ipv4'][$ka]['local']['links'][$kl]['delete_link'] = array(
          '#type' => 'button',
          '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'delete_link'),
          '#value'=>t('Delete'),
          '#name'=>implode(',',array(
             '_action',
             '_guifi_delete_radio_interface_link',
             $rk,$ki,$ka,$kl,
             $link['nid'],
             $link['device_id'])),
          '#prefix'=> '<td>',
          '#suffix'=> '</td></tr></table>',
          '#weight' =>  $lweight++,
        );

      } // foreach link

      // Deleting the IP address
      switch ($it) {
      case 'wLan/Lan':
      $f[$it][$ki]['ipv4'][$ka]['local']['delete_address'] = array(
          '#type' => 'item',
          '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'comment_address'),
          '#value'=>t('Main public address'),
          '#description' => t('wLan/Lan public IP address is required. No delete allowed.'),
          '#prefix'=> '<td>',
          '#suffix'=> '</td></tr></table>',
          '#description' => t('Can\'t delete this interface or radio, if you like to delete this radio, create another radio, add a wLan interface to it, set it as the first radio, and then you will be able to delete this one.'),
          '#weight' =>  3,
        );
        break;
      case 'wds/p2p':
        break;
      default:
        $f[$it][$ki]['ipv4'][$ka]['local']['delete_address'] = array(
          '#type' => 'button',
          '#parents'=>array('radios',$rk,'interfaces',$ki,'ipv4',$ka,'delete_address'),
          '#value'=>t('Delete'),
          '#name'=>implode(',',array(
             '_action',
             '_guifi_delete_radio_interface_ipv4',
             $rk,$ki,$ka,
             $interface['ipv4'][$ka]['ipv4'],
             $interface['ipv4'][$ka]['netmask'])),
          '#prefix'=> '<td>',
          '#suffix'=> '</td></tr></table>',
          '#weight' =>  3,
        );
      }  // switch ($it)
    }   // foreach ipv4
    switch ($it) {
    case 'HotSpot':
      $f[$it][$ki]['ipv4'][$ka]['local']['deleteHotspot'] = array(
        '#type'=>'button',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'deleteHotspot'),
        '#value'=>t('Delete Hotspot'), 
        '#name'=>'_action,_guifi_delete_radio_interface,'.$rk.','.$ki,
        '#weight'=>4);
      $hotspot = true;
      break;
    case 'wds/p2p':
      $f[$it][$ki]['ipv4'][$ka]['local']['AddWDS'] = array(
        '#type'=>'button',
        '#parents'=>array('radios',$rk,'interfaces',$ki,'AddWDS'),
        '#value'=>t('Add WDS/bridge p2p link'), 
        '#name'=>'_action,_guifi_add_wds,'.$rk.','.$ki,
        '#weight'=>4);
      break;
    }

  }    // foreach interface

  foreach ($f as $it => $value) {
    //    guifi_log(GUIFILOG_FULL,'building form for: ',$value);
    $form['r']['radios'][$rk][$it] = array(
    '#type' => 'fieldset',
    '#title' => $it.' - '.$links_count[$it].' '.t('interface(s)'),
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
 return $weight;
}

/***
* Hook to move radio up/down 
***/
function _guifi_move_radio_updown(&$form,&$edit,$action) {
  $old=$action[2];
  $new=$action[3];

  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_move_radio_updown(%d,%d)',$old,$new));
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to swap this radios?'),
    '#value' => '#'.$old.'-'.$edit['radios'][$old]['ssid'].' <-> '.
	'#'.$new.'-'.$edit['radios'][$new]['ssid'],
    '#description' => t('If you save at this point, this radios will be swapped and the device saved.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Swap radios'));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/***
* Hook for move radio up/down 
* make the changes
***/
function _guifi_move_radio_updown_submit(&$edit,$action) {
  $old = $action[2];
  $new = $action[3];

  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_move_radio_updown_submit(%d,%d)',$old,$new));

  $old_radio = $edit['radios'][$old];
  unset($edit['radios'][$old]);
  $new_radio = $edit['radios'][$new];
  unset($edit['radios'][$new]);
  $edit['radios'][$new] = $old_radio;
  $edit['radios'][$old] = $new_radio;

  ksort($edit['radios']);
}

/***
* Hook for add a wLan for clients (confirmation dialog)
***/
function _guifi_add_wlan(&$form,&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_wlan(%d)',$radio));
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to create a new wLan interface for clients at this radio?'),
    '#value' => t('Radio').' #'.$radio.'-'.$edit['radios'][$radio]['ssid'],
    '#description' => t('If you save at this point, this interface will be created and device saved.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Create a wLan interface at %name',array('%name'=>$edit['radios'][$radio]['ssid'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/***
* Hook for add a wLan for clients (submit & save)
***/
function _guifi_add_wlan_submit(&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_wlan_submit(%d)',$radio));


  $interface = array();
  $interface['new']=true;
  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
  //   print_r($ips_allocated);
  $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.224','public',$ips_allocated);
  guifi_log(GUIFULOG_FULL,"IPs allocated: ".count($ips_allocated)." Obtained new net: ".$net."/27");
  $interface['ipv4'][$radio]=array();
  $interface['ipv4'][$radio]['new']=true;
  $interface['ipv4'][$radio]['ipv4']=guifi_ip_op($net);
  guifi_log(GUIFILOG_FULL,"assigned IPv4: ".$edit['radios'][$radio]['interfaces'][$interface_id]['ipv4'][$radio]['ipv4']);
  $interface['ipv4'][$radio]['netmask']='255.255.255.224';
  $interface['ipv4'][$radio]['links']=array();
  $interface['interface_type']='wLan';
  $edit['radios'][$radio]['interfaces'][]=$interface;
  
  return TRUE;
}

/***
* Hook for add a Hotspot for guests (confirmation dialog)
***/
function _guifi_add_hotspot(&$form,&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_hotspot(%d)',$radio));
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to create a Hotspot interface for guests at this radio?'),
    '#value' => t('Radio').' #'.$radio.'-'.$edit['radios'][$radio]['ssid'],
    '#description' => t('If you save at this point, this interface will be created and device saved.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Create a Hotspot interface at %name',array('%name'=>$edit['radios'][$radio]['ssid'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/***
* Hook for add a Hotspot for guests (save)
***/
function _guifi_add_hotspot_submit(&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_hotspot_submit(%d)',$$radio));

  // obtaining a public IP fot the hotspot NAT

  // filling variables
  $interface=array();
  $interface['new']=true;
  $interface['interface_type']='HotSpot';
  $edit['radios'][$radio]['interfaces'][] = $interface;
  
  return TRUE;
}

/***
* Hook for adding a new radio to a device
***/
function _guifi_add_radio(&$form,&$edit,$action) {
  guifi_log(GUIFILOG_TRACE, "function _guifi_add_radio()");

  // wrong form navigation, can't do anything
  if ($edit['r']['newradio_mode'] == null)
    return TRUE;


  // next id
  $rc = 0; // Radio radiodev_counter next pointer
  $tc = 0; // Total active radios

  // fills $rc & $tc proper values
  if (isset($edit[radios])) foreach ($edit['radios'] as $k=>$r) 
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
    $radio['mode']=$edit['r']['newradio_mode'];
    $radio['protocol']='802.11b';
    $radio['channel']=0;
    $radio['antenna_gain']=14;
    $radio['antenna_azimuth']=0;
    if ($radio['mode'] == 'ap') {
      $radio['antenna_angle']=120;
      $radio['clients_accepted']="Yes";
      $radio['ssid']=$ssid.'AP'.$rc;
      if ($rc == 0)
        $radio['mac']=_guifi_mac_sum($edit['mac'],2);
      else
        $radio['mac']='';
    } else {
      $radio['antenna_angle']=30;
      $radio['clients_accepted']="No";
      $radio['ssid']=$ssid.'CPE'.$rc;
      if ($rc == 0)
        $radio['mac']=_guifi_mac_sum($edit['mac'],1);
      else
        $radio['mac']='';
    }

    $form_weight = 0;
    guifi_form_hidden($form,$edit,$form_weight);
    _guifi_radio_form($form,$radio,$rc,$form_weight);
    $action[2] = $rc;
    $action[3] = $tc;
    _guifi_device_buttons($form,$action,$form_weight);

    drupal_set_title(t('Create a radio at %dname',array('%dname'=> $edit['nick'])));

    return FALSE;
}

function _guifi_add_radio_submit(&$edit,$action) {
  guifi_log(GUIFILOG_NONE, "function _guifi_add_radio_submit()");

  $rc = $action[2];
  $tc = $action[3];

  $edit['radios'][$rc]['new']=true;
  $edit['radios'][$rc]['mode']=$edit['r']['newradio_mode'];
  $edit['radios'][$rc]['interfaces']=array();
  $edit['radios'][$rc]['interfaces'][0]=array();
  $edit['radios'][$rc]['interfaces'][0]['new']=true;
  switch ($edit['r']['newradio_mode']) {
    case 'ap':
      $edit['radios'][$rc]['interfaces'][0]['interface_type']='wds/p2p';
      // first radio, force wlan/Lan bridge and get an IP
      if ($tc == 0) {
        $edit['radios'][$rc]['interfaces'][1]=array();
        $edit['radios'][$rc]['interfaces'][1]['new']=true;
        $edit['radios'][$rc]['interfaces'][1]['interface_type']='wLan/Lan';
        $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
        $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.224','public',$ips_allocated);
        guifi_log(GUIFILOG_FULL,"IPS allocated: ".count($ips_allocated)." got net: ".$net.'/27');
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]=array();
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['new']=true;
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['ipv4']=guifi_ip_op($net);
        guifi_log(GUIFILOG_FULL,"Assigned IP: ".$edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['ipv4']);
        $edit['radios'][$rc]['interfaces'][1]['ipv4'][$rc]['netmask']='255.255.255.224';
      } 
      break;
    case 'client':
    case 'routedclient':
        $edit['radios'][$rc]['interfaces'][0]['new']=true;
        $edit['radios'][$rc]['interfaces'][0]['interface_type']='Wan';
      break;
  }
}


function _guifi_delete_interface($edit,$op) {
  $parse=explode(',',$edit[edit_details]);

  switch ($op) {
  case t('Delete selected'):
    $output .= '<h2>'.t('Are you sure you want to delete this interface?').'</h2>'.$edit[radios][$parse[0]][ssid].' '.
									     $edit[radios][$parse[0]][interfaces][$parse[1]][interface_type];
$output .= '<br />'.form_button(t('Confirm delete'),'op').
		form_button(t('Back to list'),'op');
$output .= $message;
break;
case t('Confirm delete'):
if ($edit[radios][$parse[0]][interfaces][$parse[1]]['new'])
unset($edit[radios][$parse[0]][interfaces][$parse[1]]);
else
$output .= form_hidden('radios]['.$parse[0].'][interfaces]['.$parse[1].'][deleted',true);
$output .= '<h2>'.t('Interface deleted').'</h2>'.$link_text;
$output .= '<br />'.form_button(t('Back to list'),'op');
drupal_set_message(t('The interface %name has been deleted. To prevent accidental deletions, the delete will be confirmed only when you submit the changes.',array('%name' => theme('placeholder',$edit['radios'][$parse[0]]['ssid'].' '.$edit[radios][$parse[0]][interfaces][$parse[1]][interface_type]))));
break;
}
$output .= guifi_form_hidden('',$edit);
print theme('page',form($output));
}
/***
* function to add confirm buttons
***/
function _guifi_device_buttons(&$form,$action,&$fweight = 100) {

  $action_str = implode(',',$action);

  $form['reset'] = array(
    '#type' => 'submit',
    '#parents' => array('op'),
    '#value' => t('Reset'),
    '#weight' => $fweight++,
  );
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

/***
* Hook to delete radio interface (confirmation dialog)
***/
function _guifi_delete_radio_interface(&$form,&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface(radio: %d, interface: %d)',$radio_id,$interface_id));

  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this interface?'),
    '#value' => $edit['radios'][$radio_id]['ssid'].'-'.
	$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'],
    '#description' => t('If you save at this point, this interface and links will be deleted, information saved and can\'t be undone.'),
    '#weight' => 0,
  );
  drupal_set_title(t('Delete radio interface (%type)',array('%type'=>$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/***
* Hook to delete radio interface (delete)
***/
function _guifi_delete_radio_interface_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_submit(radio: %d, interface: %d)',$radio_id,$interface_id));
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['deleted'] = true;
}


/***
* Hook to delete radio interface ipv4 (confirmation dialog)
***/
function _guifi_delete_radio_interface_ipv4(&$form,&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  $ipv4=str_replace('_','.',$action[4]);
  $mask=str_replace('_','.',$action[5]);
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_ipv4(radio: %d, interface: %d)',$radio_id,$interface_id));

  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);

  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this interface?'),
    '#value' => $edit['radios'][$radio_id]['ssid'].'-'.
	$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'].'-'.
	$ipv4.'/'.$mask,
    '#description' => t('If you save at this point, this network addres and its links will be deleted, information saved and can\'t be undone.'),
  '#weight' => 0,
  );
  drupal_set_title(t('Delete radio interface (%type)',array('%type'=>$edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'])));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/***
* Hook to delete radio interface ipv4 (submit)
***/
function _guifi_delete_radio_interface_ipv4_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_delete_radio_interface_ipv4(radio: %d, interface: %d)',$radio_id,$interface_id));
  $edit['radios'][$radio_id]['interfaces'][$interface_id]['deleted'] = true;
}


/***
* Hook to delete radio interface link (dialog)
***/
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
/***
* Hook to delete radio interface link (submit)
***/
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



/***
* Hook to delete radio
***/
function _guifi_delete_radio(&$form,&$edit,$action) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_delete_radio()");
  $radio_id=$action[2];
  $form_weight = 0;

  $form['help'] = array(
    '#type' => 'item',
    '#title' => t('Are you sure you want to delete this radio?'),
    '#value' => t('Radio #').$radio_id.' - '.$edit['radios'][$radio_id]['ssid'],
    '#description' => t('If you save at this point, radio and all its interfaces and links will be deleted, information saved and can\'t be undone.'),
    '#weight' => $form_weight++,
  );

  guifi_form_hidden($form,$edit,$form_weight);
  drupal_set_title(t('delete radio#').$radio_id.' - '.$edit['radios'][$radio_id]['ssid']);
  _guifi_device_buttons($form,$action,$form_weight);

  return FALSE;
}

function _guifi_delete_radio_submit(&$edit,$action) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_delete_radio_submit()");
  $radio_id=$action[2];
  $edit['radios'][$radio_id]['deleted'] = true;
}


function guifi_radio_validate($edit) {
  guifi_log(GUIFILOG_TRACE,"function _guifi_radio_validate()");

  if (!(empty($edit['mac']))) { 
    $mac = _guifi_validate_mac($edit['mac']);
    if ($mac) {
      $edit['mac'] = $mac;
    } else {
      form_set_error('mac',t('Error in MAC address, use 00:00:00:00:00:00 format.').' '.$edit['mac']);
    }
  }

  if (($edit['variable']['firmware'] != 'n/a') and ($edit['variable']['firmware'] != null)) {
    $radio = db_fetch_object(db_query("SELECT model FROM {guifi_model} WHERE mid='%d'",$edit['variable']['model_id']));
    if (!guifi_type_relation('firmware',$edit['variable']['firmware'],$radio->model)) {
      form_set_error('variable][firmware',t('This firmware with this radio model is NOT supported.'));
    } 
  }

}

/***
* Hook to link client to an AP 
***/
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

/***
* Hook to add WDS/p2p linki, dialog form (choose an AP to link with)
***/
function _guifi_add_wds(&$form,&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf("function _guifi_add_wds(Radio: %d, Interface: %d)",$radio_id,$interface_id));

  // read input parameters
  $form_weight = 0;

  guifi_form_hidden($form,$edit,$form_weight);

  // initialize filters
  if (empty($edit['filters']))
  $edit['filters'] = array(
    'dmin'   => 0,
    'dmax'   => 15,
    'search' => null,
    'type'   => 'wds',
    'mode'   => $edit['radios'][$radio_id]['mode'],
    'from_node' => $edit['nid'],
    'from_device' => $edit['id'],
    'from_radio' => $radio_id,
    'azimuth' => "0,360",
  );

  drupal_set_title(t('Choose an AP from the list to link with %ssid',array('%ssid'=> $edit['radios'][$radio_id]['ssid'])));

  // Filter form
  guifi_devices_select_filter($form,implode(',',$action),$edit['filters'],$form_weight);

  $choices = guifi_devices_select($edit['filters']);

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

/***
* Hook to add WDS/p2p link, (create the link)
***/
function _guifi_add_wds_submit(&$edit,$action) {
  $radio_id=$action[2];
  $interface_id=$action[3];
  guifi_log(GUIFILOG_TRACE,sprintf("function _guifi_add_wds(Radio: %d, Interface: %d)",$radio_id,$interface_id));
  guifi_log(GUIFILOG_FULL,"linked",$edit['linked']);

  // get list of the current used ips
  $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
  
  //
  // initializing WDS/p2p link parameters
  //
  $newlk['new']=true;
  $newlk['interface']=array();
  list($newlk['nid'],$newlk['device_id'],$newlk['interface']['radiodev_counter']) =  explode(',',$edit['linked']);
  guifi_log(GUIFILOG_FULL,"newlk",$newlk);
  $newlk['link_type']='wds';
  $newlk['flag']='Planned';
  $newlk['routing'] = 'BGP';
  // get an ip addres for local-remote interfaces
  $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.252','backbone',$ips_allocated);
  $ip1 = guifi_ip_op($net);
  $ip2 = guifi_ip_op($ip1);
  guifi_merge_ip(array('ipv4'=>$ip1,'netmask'=>'255.255.255.252'),$ips_allocated,false);
  guifi_merge_ip(array('ipv4'=>$ip2,'netmask'=>'255.255.255.252'),$ips_allocated,true);
  // getting remote interface
  $remote_interface = db_fetch_array(db_query("SELECT id FROM {guifi_interfaces} WHERE device_id = %d AND interface_type = 'wds/p2p' AND radiodev_counter = %d",$newlk['device_id'],$newlk['interface']['radiodev_counter']));
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

?>
