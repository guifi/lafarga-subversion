<?php

function guifi_radio_form(&$edit) {

//  print_r($edit);
//  print ($edit[edit_details]);

  global $user;

  $querymid = db_query("SELECT mid, model, f.nom manufacturer FROM guifi_model m, guifi_manufacturer f WHERE f.fid = m.fid AND supported='Yes'");
  while ($model = db_fetch_array($querymid)) {
     $models_array[$model["mid"]] = $model["manufacturer"] .", " .$model["model"];
  }

  $rows[] = array(
                array('data'=>form_select(t('Radio Model'), 'variable][model_id', $edit["variable"]["model_id"], $models_array, t('Radio model')),'valign'=>'top'),
                array('data'=>form_select(t('Firmware'), 'variable][firmware', $edit["variable"]["firmware"], guifi_types('firmware') , t('Used for automatic configuration.')),'valign'=>'top'),
                array('data'=>form_textfield(t("Device MAC Address"), "mac", $edit["mac"], 17, 17,  t('Base/Main MAC Address.<br>Some configurations won\'t work if s blank')),'valign'=>'top')
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
      if ($radio[deleted]) continue;
//      print_r($radio);
//      $form .= guifi_form_hidden('radios]['.$key.']',$radio);

      // Present radio information & radio group
      if ($radio['mode'] == 'ap')
        $rowspan = 5;
      else
        $rowspan = 3;
      $row = array(
                    array('data'=>'#'.$key.form_radio('', 'edit_details', $key),'rowspan'=>$rowspan,'valign'=>'top','width'=>1),
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
      $rows[] = $row;

      // radio interfaces
//      print_r($edit);
      if (count($radio['interfaces'])>0) 
      foreach ($radio['interfaces'] as $ki=>$interface) {

        unset($wlan_addr);

//     print "type: $interface[interface_type]\n<br>";
        switch ($interface[interface_type]) {
        case 'wds/p2p':
          $add_link = t('Add WDS/bridge p2p link');
          break;
        case 'wLan/Lan':
        case 'wLan':
          $add_link = t('Add AP/Client link');

          if (user_access('administer guifi networks')) {
            $wlan_addr[] = array('data'=>form_textfield(null,'radios['.$key.'][interfaces]['.$ki.'][ipv4]['.$key.'][ipv4]',
                                                           $interface[ipv4][$key][ipv4],16,16,null),'width=1');
            $wlan_addr[] = array('data'=>form_select(null,'radios['.$key.'][interfaces]['.$ki.'][ipv4]['.$key.'][netmask]',
                                                           $interface[ipv4][$key][netmask],guifi_types('netmask',30,15),null),'colspan'=>3,'width=1');

          } else
            $wlan_addr = array('data'=>$interface[ipv4][$key][ipv4].'/'.$interface[ipv4][$key][netmask],'width'=>1);;

          break;
        case 'Wan':
          $cr = guifi_count_radio_links($radio);
          if ($cr['ap']==0) 
            $add_link = t('Link to AP');
         break;
        }
       
        if ($add_link != '') {
          if (isset($wlan_addr))
            $rows[] = array_merge(array(array('data'=>$interface['interface_type'],'width'=>1)), $wlan_addr,
                                  array(
                                    array('data'=>form_button($add_link, 'op['.$interface[id].']'),'colspan'=>8)
                                        ));
          else
            $rows[] = array(array('data'=>$interface['interface_type'],'width'=>1),
                            array('data'=>form_button($add_link, 'op['.$interface[id].']'),'colspan'=>8));
        } else
          $rows[] = array(array('data'=>$interface['interface_type'],'colspan'=>8));

        if (count($interface['ipv4']))
        unset($lrows);
        $lrows = array();
        if (count($interface[ipv4]) > 0) foreach ($interface['ipv4'] as $ka=>$ipv4) 
        if (!empty($ipv4[links])) foreach ($ipv4['links'] as $kl=>$link) {
          if ($link[deleted]) continue;
          $ip = _ipcalc($ipv4['ipv4'],$ipv4['netmask']);
//          print_r($link);
          $lrows[] = array(
                           form_radio('', 'edit_details', $key.','.$ki.','.$ka.','.$kl),
                           guifi_get_nodename($link['nid']).'-'.
                           guifi_get_hostname($link['device_id']),
                           form_select(null,'radios]['.$key.'][interfaces]['.$ki.'][ipv4]['.$ka.'][links]['.$kl.'][flag', $link['flag'], array_diff(guifi_types('status'),array('Dropped'=>t('Dropped')))),
                           $ipv4['ipv4'].'/'.$ip['maskbits'],
                           $link['interface']['ipv4']['ipv4']
                          );
        }
        $header = array(null,t('node-device'),t('status'),t('local ip'),t('remote ip'));
        if (count($lrows) > 0)
          $rows[] = array(array('data'=>theme('table',$header,$lrows),'colspan'=>8));
        else
          $rows[] = array(array('data'=>theme('table',null,$lrows),'colspan'=>8));
      }

      // If AP & no wLan interface, allow to create one
      if ((count($radio[interfaces]) < 2) and ($radio[mode] == 'ap')) {
        $rows[] = array(array('data'=>form_button(t('Add wLan for clients'), 'op['.$key.']'),'colspan'=>8));
        $rows[] = array(array('data'=>'&nbsp;','colspan'=>8));
      }
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
//    print "Max radios: ".$maxradios->radiodev_max." \n<br>";
  if (isset($edit[radios])) 
  foreach ($edit[radios] as $k=>$radio) {
    $tr++;
    if (!$radio[deleted])
      $cr++;
    if ($radio['mode'] == 'client') 
      $firewall = true;
  }
//  print "Max radios: ".$maxradios->radiodev_max." Current: $cr Total: $tr Firewall: $firewall\n<br>";
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
    $form .= form_group(t('Add new radio'),theme('table',null,$erow),t('Usage:<br>Choose <strong>wireless client</strong>mode for a normal station with full access to the network. That\'s the right choice in general.<br>Use the other available options only for the appropiate cases and being sure of what you are doing and what does it means. Note that you might require to be authorized by networks administrators for doing this.<br>Youwill not be able to define you link and get connected to the network until you add at least one radio.'));
  } else {  
    $edit_form .= form_item(null,t('You can add radios to this device once has been saved into de database'));
    $form .= form_group(t('Add new radio'),$edit_form,null);
  }

  return $form;
}



function guifi_add_radio_wlan($edit,$radio) {
   $interface_id=guifi_next_interface($edit);

   $edit['radios'][$radio]['interfaces'][$interface_id]=array();
   $edit['radios'][$radio]['interfaces'][$interface_id]['new']=true;
   $edit['radios'][$radio]['interfaces'][$interface_id][device_id]=$edit[id];
   $edit['radios'][$radio]['interfaces'][$interface_id][id]=$interface_id;
   $edit['radios'][$radio]['interfaces'][$interface_id][radiodev_counter]=$rc;
   $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
//   print_r($ips_allocated);
   $net = guifi_get_subnet_by_nid($edit[nid],'255.255.255.224','public',$ips_allocated);
//   print "IPS allocated: ".count($ips_allocated)." net: ".$net;
   $edit['radios'][$radio]['interfaces'][$interface_id][ipv4][$radio]=array();
   $edit['radios'][$radio]['interfaces'][$interface_id][ipv4][$radio]['new']=true;
   $edit['radios'][$radio]['interfaces'][$interface_id][ipv4][$radio][id]=$rc;
   $edit['radios'][$radio]['interfaces'][$interface_id][ipv4][$radio][interface_id]=$interface_id;
   $edit['radios'][$radio]['interfaces'][$interface_id][ipv4][$radio][ipv4]=guifi_ip_op($net);
   $edit['radios'][$radio]['interfaces'][$interface_id][ipv4][$radio][netmask]='255.255.255.224';
   $edit['radios'][$radio]['interfaces'][$interface_id][ipv4][$radio][links]=array();
   $edit['radios'][$radio]['interfaces'][$interface_id][interface_type]='wLan';

   return $edit;
}


function guifi_add_radio($edit) {
 // next id
 $rc = 0; // Radio radiodev_counter next pointer
 $tc = 0; // Total active radios

 // fills $rc & $tc proper values
 if (isset($edit[radios])) foreach ($edit['radios'] as $k=>$r) if ($k+1 > $rc)  {
   $rc = $k+1;
   if (!$edit[radios][$k][delete])
     $tc++;
 }
 $interface_id=guifi_next_interface($edit);

 // setting default values
 $edit['radios'][$rc]=array();
 $edit['radios'][$rc]['new']=true;
 $edit['radios'][$rc]['id']=$edit['id'];
 $edit['radios'][$rc]['nid']=$edit['nid'];
 $edit['radios'][$rc]['model_id']=16;
 $edit['radios'][$rc]['radiodev_counter']=$rc;
 $edit['radios'][$rc]['ssid']=$edit['nick'].'-'.$rc;
 $edit['radios'][$rc]['mode']=$edit['newradio_mode'];
 $edit['radios'][$rc]['protocol']='802.11bg';
 $edit['radios'][$rc]['channel']=0;
 $edit['radios'][$rc]['antenna_angle']=30;
 $edit['radios'][$rc]['antenna_gain']=14;
 $edit['radios'][$rc]['antenna_azimuth']=0;
 if ($rc == 0)
   $edit['radios'][$rc]['mac']=_guifi_mac_sum($edit[mac],2);
 else
   $edit['radios'][$rc]['mac']='';
 $edit['radios'][$rc]['interfaces']=array();
 switch ($edit[newradio_mode]) {
 case 'ap':
   $edit['radios'][$rc]['interfaces'][$interface_id]=array();
   $edit['radios'][$rc]['interfaces'][$interface_id]['new']=true;
   $edit['radios'][$rc]['interfaces'][$interface_id][device_id]=$edit[id];
   $edit['radios'][$rc]['interfaces'][$interface_id][id]=$interface_id;
   $edit['radios'][$rc]['interfaces'][$interface_id][radiodev_counter]=$rc;
   $edit['radios'][$rc]['interfaces'][$interface_id][interface_type]='wds/p2p';

   // if first radio, force wlan
   if ($tc == 0) {
     $interface_id++;
     $edit['radios'][$rc]['interfaces'][$interface_id]=array();
     $edit['radios'][$rc]['interfaces'][$interface_id]['new']=true;
     $edit['radios'][$rc]['interfaces'][$interface_id][device_id]=$edit[id];
     $edit['radios'][$rc]['interfaces'][$interface_id][id]=$interface_id;
     $edit['radios'][$rc]['interfaces'][$interface_id][radiodev_counter]=$rc;
     $edit['radios'][$rc]['interfaces'][$interface_id][interface_type]='wLan/Lan';
     $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
     $net = guifi_get_subnet_by_nid($edit[nid],'255.255.255.224','public',$ips_allocated);
//   print "IPS allocated: ".count($ips_allocated)." net: ".$net;
     $edit['radios'][$rc]['interfaces'][$interface_id][ipv4][$rc]=array();
     $edit['radios'][$rc]['interfaces'][$interface_id][ipv4][$rc]['new']=true;
     $edit['radios'][$rc]['interfaces'][$interface_id][ipv4][$rc][id]=$rc;
     $edit['radios'][$rc]['interfaces'][$interface_id][ipv4][$rc][interface_id]=$interface_id;
     $edit['radios'][$rc]['interfaces'][$interface_id][ipv4][$rc][ipv4]=guifi_ip_op($net);
     $edit['radios'][$rc]['interfaces'][$interface_id][ipv4][$rc][netmask]='255.255.255.224';
     $edit['radios'][$rc]['interfaces'][$interface_id][ipv4][$rc][links]=array();
   } 

   break;
 case 'client':
 case 'routedclient':
   $edit['radios'][$rc]['clients_accepted']='No';
   $edit['radios'][$rc]['interfaces'][$interface_id]=array();
   $edit['radios'][$rc]['interfaces'][$interface_id]['new']=true;
   $edit['radios'][$rc]['interfaces'][$interface_id][device_id]=$edit[id];
   $edit['radios'][$rc]['interfaces'][$interface_id][id]=$interface_id;
   $edit['radios'][$rc]['interfaces'][$interface_id][radiodev_counter]=$rc;
   $edit['radios'][$rc]['interfaces'][$interface_id][interface_type]='Wan';
   break;
 }

 return $edit;
}


function guifi_delete_radio($edit,$op) {
  $radio_id=$edit[edit_details];
//  if ($radio_id == 0) {
//    drupal_set_message(t("Can't delete main radio."),'error');
//    unset($edit[edit_details]);
//    unset($_POST[op]);
//    guifi_edit_device($edit[id]);
//  }

  switch ($op) {
  case t('Delete selected'):
      $output .= '<h2>'.t('Are you sure you want to delete this radio?').'</h2>'.$edit[radios][$radio_id][ssid];
      $output .= '<br>'.form_button(t('Confirm delete'),'op').
                        form_button(t('Back to list'),'op');
      $output .= $message;
    break;
  case t('Confirm delete'):
      if ($edit[radios][$radio_id]['new'])
        unset($edit[radios][$radio_id]);
      else
        $output .= form_hidden('radios]['.$radio_id.'][deleted',true);
      $output .= '<h2>'.t('Radio deleted').'</h2>'.$link_text;
      $output .= '<br>'.form_button(t('Back to list'),'op');
      drupal_set_message(t('The radio %name has been deleted. To prevent accidental deletions, the delete will be confirmed only when you submit the changes.',array('%name' => theme('placeholder',$edit['radios'][$radio_id]['ssid']))));
    break;
  }
  $output .= guifi_form_hidden('',$edit);
  print theme('page',form($output));
  exit;
}


function guifi_radio_validate($edit) {
  
  if (!empty($edit['mac'])) { 
    $mac = _guifi_validate_mac($edit['mac']);
    if ($mac) {
      $edit['mac'] = $mac;
    } else {
      form_set_error('mac',t('Error in MAC address, use 00:00:00:00:00:00 format.'));
    }
  }

  if (($edit['variable']['firmware'] != 'n/a') and ($edit['variable']['firmware'] != null)) {
    $radio = db_fetch_object(db_query("SELECT model FROM {guifi_model} WHERE mid='%d'",$edit['variable']['model_id']));
    if (!guifi_type_relation('firmware',$edit['variable']['firmware'],$radio->model)) {
      form_set_error('variable][firmware',t('This firmware with this radio model is NOT supported.'));
    } 
  }

}

?>
