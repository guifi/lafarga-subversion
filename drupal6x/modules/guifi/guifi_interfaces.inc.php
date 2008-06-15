<?php

function guifi_device_interface_form(&$interface,$ptree) {
  global $hotspot;
  
  guifi_log(GUIFILOG_TRACE,'function guifi_device_interface_form()',$interface);

  // Interface type shoudn't be null
  if ($interface['interface_type'] == null)
    return;
     
  $it = $interface['interface_type'];
    
  $f = array(
    '#type' => 'fieldset',
    '#title' => $it,
    '#collapsible' => true,
    '#collapsed' => !isset($interface['unfold'])
  );
  
  $f['interface'] = guifi_form_hidden_var(
    $interface,
    array('id','interface_type','radiodev_counter'),
    $ptree
  );
  
  if ($interface['deleted']){
    $f['interface']['deleteMsg'] = array(
      '#type' => 'item',
//      '#parents' => array_merge($ptree,array('deleted')),
      '#value' => t('Deleted'),
      '#description' => guifi_device_item_delete_msg( 
         'This interface has been deleted, ' .
         'related addresses and links will be also deleted'),
    );
  } else {
    if (($it != 'wds/p2p') and ($it != 'wLan/Lan'))
      $f['interface']['deleteInterface'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
        '#parents'=>array_merge($ptree,array('deleteInterface')),
        '#attributes'=>array('title'=>t('Delete interface')), 
        '#submit' => array('guifi_interface_delete_submit'),
      );
  }
  if ($it == 'wds/p2p')  
    $f['interface']['AddWDS'] = array(
    '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/wdsp2p.png',
        '#parents'=>array_merge($ptree,array('AddWDS',$ptree[1],$ptree[2])),
        '#attributes'=>array('title'=>t('Add WDS/P2P link to extend the backbone')), 
        '#submit' => array('guifi_radio_add_wds_submit'),
     );  
  
  if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {

      if ($ipv4['deleted'])
        continue;
      
      $f['ipv4'][$ka] =
        guifi_device_ipv4_link_form(
          $ipv4,
          array_merge(
            $ptree,
            array('ipv4',$ka)
          )
        );
    }   // foreach ipv4
  
  if ($it != 'HotSpot')      
    $f['#title'] .= ' - '.count($interface['ipv4']).' '.
      t('address(es)');
  else
    $hotspot = true;

  return $f;
    
//    $f[$it][$ki]['addCableConnection'] = array(
//      '#type'=>'button',
//      '#parents'=>array('interfaces',$ki,'addCableConnection'),
//      '#value'=>t('Add cable connection'),
//      '#name'=>'_action,_guifi_add_cable_link,'.$ki,
//      '#weight'=>$fw++);
//    $f[$it][$ki]['addPublicSubnet'] = array(
//      '#type'=>'button',
//      '#parents'=>array('interfaces',$ki,'addPublicSubnet'),
//      '#value'=>t('Add public subnetwork'),
//      '#name'=>'_action,_guifi_add_subnet,'.$ki,
//      '#weight'=>$fw++);
//    if ($interface['interface_type'] != 'wLan/Lan')
//      $f[$it][$ki]['deleteInterface'] = array(
//        '#type'=>'button',
//        '#parents'=>array('interfaces',$ki,'deleteInterface'),
//        '#value'=>t('Delete Interface'),
//        '#name'=>'_action,_guifi_delete_interface,,'.$ki,
//        '#weight'=>$fw++);
//
//  
//  foreach ($f as $it => $value) {
//    
//    //    guifi_log(GUIFILOG_FULL,'building form for: ',$value);
//    switch ($it) {
//    case 'wLan/Lan':
//    case 'wds/p2p':
//      $title = $it.' - '.$links_count[$it].' '.t('link(s)');
//      break;
//    case 'wLan':
//      $title = $it.' - '.
//        count($value).' '.t('interface(s)').' - '.
//        $links_count[$it].' '.t('link(s)');
//      break;
//    default:
//      $title = $it;
//    }
//    
//    if ($ipv4_count[$it])
//       $title .= ' - '.$ipv4_count[$it].' '.t('address(es)');
//    
//        
//    $form['interfaces'][$ilist[$it]] = array(
//      '#type' => 'fieldset',
//      '#title' => $title,
//      '#weight' => $fw++,
//      '#collapsible' => TRUE,
//      '#collapsed' => TRUE,
//    );
//
//    if (!empty($value)) {
//      foreach ($value as $ki => $fin)
//        if (empty($form['interfaces'][$ki]))
//          $form['interfaces'][$ki] = $fin;
//        else
//          $form['interfaces'][$ki] = array_merge($form['interfaces'][$ki],$fin);
//    } else {
//      if ((!$edit['interfaces'][$ilist[$it]]['new']) and
//        ($it != 'wds/p2p') and 
//        ($it != 'wLan/Lan'))
//        $form['interfaces'][$ki]['delete_address'] = array(
//          '#type' => 'button',
//          '#parents'=>array('interfaces',$ilist[$it],'delete_interface'),
//          '#value'=>t('Delete interface'),
//          '#executes_submit_function' => true,
//          '#weight' => $fw++,
//        );
//    }
//    $form['interfaces'][$ki]['id'] = array(
//      '#type'=>'hidden',
//      '#parents'=>array('interfaces',$ki,'id'),
////      '#value'=>$interface['id']);
//      '#value'=>$ki);
//    $form['interfaces'][$ki]['interface_type'] = array(
//      '#type'=>'hidden',
//      '#parents'=>array('interfaces',$ki,'interface_type'),
//      '#value'=>$interface['interface_type']);
//  }
//  $form['interfaces']['#title'] .= ' - '.
//    $interfaces_count.' '.t('interface(s)').' - '.
//    array_sum($links_count).' '.t('link(s)').' - '.
//    array_sum($ipv4_count).' '.t('address(es)');
//    
//  $form['interfaces']['NewInterfaceName'] = array(
//    '#type'=>'select',
//    '#parents'=>array('NewInterfaceName'),
//    '#value'=> '0',
//    '#options'=>array_merge(
////       array('0'=>t('Select interface name')),
//      guifi_get_free_interfaces($edit['id'],$edit),
//      array('other'=>t('other'))
//    ),
//    '#prefix'=>'<table style="width: 0"><tr><td>',
//    '#suffix'=>'</td>',
//    '#weight'=>$fw++,
//  );
//  $form['interfaces']['AddCableInterface'] = array(
//    '#type'=>'submit',
//    '#parents'=>array('AddCableInterface'),
//    '#value'=>t('Add interface'),
//    '#name'=>'_action,_guifi_add_interface,',
//    '#prefix'=>'<td>',
//    '#suffix'=>'</td></tr></table>',
//    '#weight'=>$fw++);
//  
//  return;
//  
}

/* guifi_interfaces_form(): Main cable interface edit form */
function guifi_interfaces_form(&$form,&$edit,&$fw = 500) {

  global $definedBridgeIpv4;

  $definedBridgeIpv4 = FALSE;
  
  if (empty($edit['interfaces']))
    return;

  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_interfaces_form()'));

  $form['interfaces']['#type'] = 'fieldset';
  $form['interfaces']['#title'] = t('Cable connections');
  $form['interfaces']['#collapsible'] = true;
  $form['interfaces']['#collapsed'] = true;
  $form['interfaces']['#tree'] = true;
  $form['interfaces']['#weight'] = $fw++;
  
  $f = array();           // Variable to store temporary form
  $interfaces_count = 0;  // Interfaces counter
  $ipv4_count = array();  // ipv4 addresses counter
  $links_count = array(); // links counter
  $ilist = array();       // Per interface_type, stores the interface id's
  
  foreach ($edit['interfaces'] as $ki => $interface) {
    if ($interface['interface_type'] == null)
      continue;

    if ($interface['deleted'])
      continue;

    guifi_log(GUIFILOG_TRACE,sprintf('cable interface %d',$ki),$interface);
    $interfaces_count++;

    $it = $interface['interface_type'];
    $ilist[$it] = $ki;

    if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {

      if ($ipv4['deleted'])
        continue;
        
      $ipv4_count[$it] ++;
      
      $links_count[$it] += guifi_ipv4_link_form(
        $f[$it][$ki]['ipv4'][$ka],
        $ipv4,
        $interface,
        array('interfaces',$ki,'ipv4',$ka),
        $fw);

    }   // foreach ipv4
    $f[$it][$ki]['addCableConnection'] = array(
      '#type'=>'button',
      '#parents'=>array('interfaces',$ki,'addCableConnection'),
      '#value'=>t('Add cable connection'),
      '#name'=>'_action,_guifi_add_cable_link,'.$ki,
      '#weight'=>$fw++);
    $f[$it][$ki]['addPublicSubnet'] = array(
      '#type'=>'button',
      '#parents'=>array('interfaces',$ki,'addPublicSubnet'),
      '#value'=>t('Add public subnetwork'),
      '#name'=>'_action,_guifi_add_subnet,'.$ki,
      '#weight'=>$fw++);
    if ($interface['interface_type'] != 'wLan/Lan')
      $f[$it][$ki]['deleteInterface'] = array(
        '#type'=>'button',
        '#parents'=>array('interfaces',$ki,'deleteInterface'),
        '#value'=>t('Delete Interface'),
        '#name'=>'_action,_guifi_delete_interface,,'.$ki,
        '#weight'=>$fw++);

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
    
    if ($ipv4_count[$it])
       $title .= ' - '.$ipv4_count[$it].' '.t('address(es)');
    
        
    $form['interfaces'][$ilist[$it]] = array(
      '#type' => 'fieldset',
      '#title' => $title,
      '#weight' => $fw++,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    if (!empty($value)) {
      foreach ($value as $ki => $fin)
        if (empty($form['interfaces'][$ki]))
          $form['interfaces'][$ki] = $fin;
        else
          $form['interfaces'][$ki] = array_merge($form['interfaces'][$ki],$fin);
    } else {
      if ((!$edit['interfaces'][$ilist[$it]]['new']) and
        ($it != 'wds/p2p') and 
        ($it != 'wLan/Lan'))
        $form['interfaces'][$ki]['delete_address'] = array(
          '#type' => 'button',
          '#parents'=>array('interfaces',$ilist[$it],'delete_interface'),
          '#value'=>t('Delete interface'),
          '#executes_submit_function' => true,
          '#weight' => $fw++,
        );
    }
    $form['interfaces'][$ki]['id'] = array(
      '#type'=>'hidden',
      '#parents'=>array('interfaces',$ki,'id'),
//      '#value'=>$interface['id']);
      '#value'=>$ki);
    $form['interfaces'][$ki]['interface_type'] = array(
      '#type'=>'hidden',
      '#parents'=>array('interfaces',$ki,'interface_type'),
      '#value'=>$interface['interface_type']);
  }
  $form['interfaces']['#title'] .= ' - '.
    $interfaces_count.' '.t('interface(s)').' - '.
    array_sum($links_count).' '.t('link(s)').' - '.
    array_sum($ipv4_count).' '.t('address(es)');
    
  $form['interfaces']['NewInterfaceName'] = array(
    '#type'=>'select',
    '#parents'=>array('NewInterfaceName'),
    '#value'=> '0',
    '#options'=>array_merge(
//       array('0'=>t('Select interface name')),
      guifi_get_free_interfaces($edit['id'],$edit),
      array('other'=>t('other'))
    ),
    '#prefix'=>'<table style="width: 0"><tr><td>',
    '#suffix'=>'</td>',
    '#weight'=>$fw++,
  );
  $form['interfaces']['AddCableInterface'] = array(
    '#type'=>'submit',
    '#parents'=>array('AddCableInterface'),
    '#value'=>t('Add interface'),
    '#name'=>'_action,_guifi_add_interface,',
    '#prefix'=>'<td>',
    '#suffix'=>'</td></tr></table>',
    '#weight'=>$fw++);
  
  return;

}

/* Add cable interface */
/* _guifi_add_interface(): Cofirmation dialog */
function _guifi_add_interface(&$form,&$edit,$action) {
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_interface(%s)',$edit['NewInterfaceName']));
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);

  if ($edit['NewInterfaceName'] != 'other') {
    $form['help'] = array(
     '#type' => 'item',
     '#title' => t('Are you sure that do you want to create a %name interface on this device?',
       array('%name'=>$edit['NewInterfaceName'])),
//      '#description' => t('If you save at this point, this interface will be created and device saved.'),
      '#weight' => $fw++,
    );
  } else {
    $form['NewInterfaceName'] = array(
      '#type'=> 'textfield',
      '#title'=> t('Enter the name of the interface to be defined'),
      '#size'=> 10,
      '#description' => t('If you save at this point, this interface will be created and device saved.'),
      '#weight' => $fw++,
    );
  }
  drupal_set_title(t(
   'Create a %iname interface at %name',
   array('%name'=>$edit['nick'],
     '%iname'=>$edit['NewInterfaceName'])
   ));
  _guifi_device_buttons($form,$action,$fw,TRUE);

  return FALSE;
}

/* _guifi_add_interface_submit(): Action */
function _guifi_add_interface_submit(&$form,&$edit,$action) {
  $radio = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf(
    'function _guifi_add_interface_submit(%s)',
    $edit['NewInterfaceName']));

  $if['new'] = TRUE;
  $if['interface_type']=$edit['NewInterfaceName'];
  $edit['interfaces'][] = $if;
  unset($edit['NewInterfaceName']);

  return TRUE;

  $interface = array();
  $interface['new']=true;
  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
  //   print_r($ips_allocated);
  $net = guifi_get_subnet_by_nid($edit['nid'],'255.255.255.224','public',$ips_allocated);
  guifi_log(GUIFULOG_FULL,"IPs allocated: ".count($ips_allocated)." Obtained new net: ".$net."/27");
  $interface['ipv4'][$radio]=array();
  $interface['ipv4'][$radio]['new']=true;
  $interface['ipv4'][$radio]['ipv4']=guifi_ip_op($net);
  guifi_log(GUIFILOG_FULL,"assigned IPv4: ".$interface['ipv4'][$radio]['ipv4']);
  $interface['ipv4'][$radio]['netmask']='255.255.255.224';
  $interface['ipv4'][$radio]['links']=array();
  $interface['interface_type']='wLan';
  $edit['radios'][$radio]['interfaces'][]=$interface;
  
  return TRUE;
}

/* Add wlan */
/* _guifi_add_subnet(): Cofirmation dialog */
function _guifi_add_subnet(&$form,&$edit,$action) {
  $iid = $action[2];
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_subnet(%d)',$iid));
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);
  
  if (user_access('administer guifi networks'))
    $form['newSubnetMask'] = array(
      '#type' => 'select',
      '#title' => t('Select the network capacity (netmask)'),
      '#default_value' => '255.255.255.224',
      '#options'=>guifi_types('netmask',30,23),
      '#description' => t('To avoid unused addresses, choose a realistic value, you can always add more capacity later.'),
      '#weight' => $fw++,
    );
  else {
    $form['help'] = array(
      '#type' => 'item',
      '#title' => t('Are you sure that you want to allocate a new public subnetwork at this interface?'),
      '#value' => $edit['interfaces'][$iid]['interface_type'],
//       '#description' => t('To avoid unused addresses, choose a realistic value, you can always add more capacity later.'),
      '#weight' => $fw++,
    );
    $form['newSubnetMask'] = array(
      '#type' => 'hidden',
      '#value' => '255.255.255.224',
      '#weight' => $fw++,
    );
  }
  
  drupal_set_title(t(
    'Create a public subnetwork at %name',
    array('%name'=>$edit['interfaces'][$iid]['interface_type'])));
  _guifi_device_buttons($form,$action,$fw,TRUE);

  return FALSE;
}

/* _guifi_add_subnet_submit(): Action */
function _guifi_add_subnet_submit(&$form,&$edit,$action) {
  $iid = $action[2];
  if (empty($edit['newSubnetMask']))
    return TRUE;
  guifi_log(GUIFILOG_TRACE,sprintf('function _guifi_add_subnet_submit(%d)',$iid));




  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
  $net = guifi_get_subnet_by_nid($edit['nid'],$edit['newSubnetMask'],'public',$ips_allocated);
  guifi_log(GUIFULOG_TRACE,"IPs allocated: ".count($ips_allocated)." Obtained new net: ".$net."/".$edit['newSubnetMask']);
  drupal_set_message(t('New subnetwork %net/%mask will be allocated.',
    array('%net'=>$net,
      '%mask'=>$edit['newSubnetMask'])));
  $ipv4['new']=true;
  $ipv4['ipv4']=guifi_ip_op($net);
  guifi_log(GUIFILOG_TRACE,"assigned IPv4: ".$ipv4['ipv4']);
  $ipv4['netmask']=$edit['newSubnetMask'];
  $ipv4['interface_id'] = $iid;
  $edit['interfaces'][$iid]['ipv4'][]=$ipv4;
  
  return TRUE;
}


/* Delete interface */
function guifi_interface_delete(&$form,&$form_state) {
  list($radio_id, $interface_id) = explode(',',$form_state['deleteInterface']);
  guifi_log(GUIFILOG_BASIC,sprintf('function guifi_interface_delete(radio: %d, interface: %d)',
    $radio_id,$interface_id));
  if ($radio_id == '') {
    $form_state['values']['interfaces'][$interface_id]['deleted'] = true;
  } else {
    $form_state['values']['radios'][$radio_id]['interfaces'][$interface_id]['deleted']=true;
    $form_state['values']['radios'][$radio_id]['unfold'] = true;
    $form_state['values']['radios'][$radio_id]['interfaces'][$interface_id]['unfold']=true;
  }
  
  return TRUE;
}

function guifi_interface_delete_submit(&$form,&$form_state) {
  $values      = $form_state['clicked_button']['#parents'];
  $radio_id    = $values[count($values)-4];
  $interface_id= $values[count($values)-2];
  guifi_log(GUIFILOG_BASIC,sprintf('function guifi_interface_delete_submit(radio: %d, interface: %d)',
    $radio_id,$interface_id),$form_state['clicked_button']['#parents']);
  $form_state['deleteInterface']=($radio_id).','.($interface_id);
  $form_state['rebuild'] = true;
  $form_state['action'] = 'guifi_interface_delete';
  return TRUE;
}

function guifi_interface_validate($edit) {
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
