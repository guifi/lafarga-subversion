<?php

function guifi_interfaces_form(&$interface,$ptree) {
  global $hotspot;
  
  guifi_log(GUIFILOG_TRACE,'function guifi_interfaces_form()',$interface);

  // Interface type shoudn't be null
  if ($interface['interface_type'] == null)
    return;
     
  $it = $interface['interface_type'];
  if ($it == 'Wan') {
    $interface['unfold'] = true;
    $msg = t('Connection to AP');
  } else
    $msg = $it;
    
  $f = array(
    '#type' => 'fieldset',
    '#title' => $msg,
    '#collapsible' => true,
    '#collapsed' => !isset($interface['unfold'])
  );
  
  $f['interface'] = guifi_form_hidden_var(
    $interface,
    array('id','interface_type','radiodev_counter'),
    $ptree
  );
  
  // Cable interface buttons
  if (($ptree[0]=='interfaces') 
       and (!$interface['deleted']) 
     ) {
    if ($interface['interface_type']!='wLan/Lan') 
    $f['interface']['interface_type'] = array(
      '#type'=>'textfield',
      '#title'=>t('Name'),
      '#parents'=>array_merge($ptree,array('interface_type')),
      '#size'=>10,
      '#maxlength'=>60,
      '#default_value'=>$interface['interface_type'],
      '#description'=>t('Will rename the current interface name.')
    );    

    $f['interface']['AddCableLink'] = array(
      '#type'=>'image_button',
      '#src'=>drupal_get_path('module', 'guifi').'/icons/addprivatecablelink.png',
      '#parents'=>array_merge($ptree,array('AddCableLink')),
      '#attributes'=>array('title'=>t('Link to another device at the node using a private network')), 
      '#ahah' => array(
        'path' => 'guifi/js/add-cable-link/'.$interface['id'],
        'wrapper' => 'editInterface-'.$interface['id'],
        'method' => 'replace',
        'effect' => 'fade',
       )
//      '#submit' => array('guifi_radio_add_wds_submit'),
    );    

    if (!$interface['new'])
    $f['interface']['AddPublicSubnet'] = array(
      '#type'=>'image_button',
      '#src'=>drupal_get_path('module', 'guifi').'/icons/insertwlan.png',
      '#parents'=>array_merge($ptree,array('AddPublicSubnet')),
      '#attributes'=>array('title'=>t('Allocate a Public Subnetwork to the interface')), 
      '#ahah' => array(
        'path' => 'guifi/js/add-subnet-mask/'.$interface['id'],
        'wrapper' => 'editInterface-'.$interface['id'],
        'method' => 'replace',
        'effect' => 'fade',
       )
//      '#submit' => array('guifi_radio_add_wds_submit'),
    );    
  }
    
  // wds/p2p link, allow to create new links
  if ($it == 'wds/p2p')  
    $f['interface']['AddWDS'] = array(
      '#type'=>'image_button',
      '#src'=>drupal_get_path('module', 'guifi').'/icons/wdsp2p.png',
      '#parents'=>array_merge($ptree,array('AddWDS',$ptree[1],$ptree[2])),
      '#attributes'=>array('title'=>t('Add WDS/P2P link to extend the backbone')), 
      '#submit' => array('guifi_radio_add_wds_submit'),
    );
     
  if ($interface['deleted']){
    $f['interface']['deleteMsg'] = array(
      '#type' => 'item',
      '#value' => t('Deleted'),
      '#description' => guifi_device_item_delete_msg( 
         'This interface has been deleted, ' .
         'related addresses and links will be also deleted'),
    );
  } else {
    if (($it != 'wds/p2p') and ($it != 'wLan/Lan') and ($it != 'Wan'))
      $f['interface']['deleteInterface'] = array(
        '#type'=>'image_button',
        '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
        '#parents'=>array_merge($ptree,array('deleteInterface')),
        '#attributes'=>array('title'=>t('Delete interface')), 
        '#submit' => array('guifi_interfaces_delete_submit'),
      );
  }
  $f['interface']['AddPublicSubnetMask'] = array(
      '#type' => 'hidden',
      '#value' => '255.255.255.224',
      '#parents'=> array_merge($ptree,array('AddPublicSubnetMask')),
      '#prefix' => '<div id="editInterface-'.$interface['id'].'">',
      '#suffix' => '</div>'
  );
  
  $ipv4Count = 0;
  if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {
      
      if (!$ipv4['deleted'])
        $ipv4Count++;

      $f['ipv4'][$ka] =
        guifi_device_ipv4_link_form(
          $ipv4,
          array_merge(
            $ptree,
            array('ipv4',$ka)
          )
        );
    }   // foreach ipv4

  // Mode Client or client-routed, allow to link to AP
  if ( ($it == 'Wan') and ($ipv4Count == 0) )
    $f['interface']['Link2AP'] = array(
      '#type'=>'image_button',
      '#src'=>drupal_get_path('module', 'guifi').'/icons/link2ap.png',
      '#parents'=>array('Link2AP',$ptree[1],$interface['id']),
      '#attributes'=>array('title'=>t('Create a simple (ap/client) link to an Access Point')), 
      '#submit' => array('guifi_radio_add_link2ap_submit'),
    );   
  
  if ($it != 'HotSpot')      
    $f['#title'] .= ' - '.$ipv4Count.' '.
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
function guifi_interfaces_cable_form(&$edit) {

  global $definedBridgeIpv4;

  $definedBridgeIpv4 = FALSE;
  
  if (empty($edit['interfaces']))
    return;

  guifi_log(GUIFILOG_TRACE,sprintf('function guifi_interfaces_form()'));
  
  $collapse = true;
  switch (count($edit['interfaces'])) {
  case 0: 
     $msg .= t('No interfaces');
     break;
  case 1:
     $msg .= t('1 interface');
     break;
  default:
     $msg .= count($edit['interfaces']).' '.t('interfaces');
  }
  foreach ($edit['interfaces'] as $value)
    if ($value['unfold'])
      $collapse = false;
    
  $form['interfaces']['#type'] = 'fieldset';
  $form['interfaces']['#title'] = $msg;
  $form['interfaces']['#collapsible'] = true;
  $form['interfaces']['#collapsed'] = $collapse;
  $form['interfaces']['#tree'] = true;
  $form['interfaces']['#prefix'] = '<img src="/'.
    drupal_get_path('module', 'guifi').
//    '/modules/guifi'.
    '/icons/interface.png"> '.t('Cable connections section');

  $form['interfaces']['ifs'] = array(
    '#prefix'=>'<div id="add-interface">',
    '#suffix'=>'</div>'
  );

  foreach ($edit['interfaces'] as $iid=>$interface) {  
    $form['interfaces']['ifs'][$interface['interface_type']][$iid] = 
      guifi_interfaces_form($interface,array('interfaces',$iid));
  } // foreach interface
  
  $form['interfaces']['addInterface'] = array(
        '#type'=>'image_button',
        '#src'=> drupal_get_path('module', 'guifi').'/icons/addinterface.png',
        '#parents'=>array('addInterface'),
        '#attributes'=>array('title'=>t('Add Interface for cable connections')), 
        '#ahah' => array(
          'path' => 'guifi/js/add-interface',
          'wrapper' => 'add-interface',
          'method' => 'replace',
          'effect' => 'fade',
         )
  );
  
  return $form;
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

/* _guifi_add_subnet_submit(): Action */
function guifi_interfaces_add_subnet_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];
  $iid    = $values[count($values)-2];
  $mask   = $form_state['values']['interface'][$iid]['newNetmask'];
  guifi_log(GUIFILOG_TRACE,
    sprintf('function guifi_interfaces_add_subnet_submit(%d)',$iid),
    $mask);

  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$form_state['values']);
  $net = guifi_get_subnet_by_nid($form_state['values']['nid'],$mask,'public',$ips_allocated);
//  guifi_log(GUIFULOG_TRACE,"IPs allocated: ".count($ips_allocated)." Obtained new net: ".$net."/".$edit['newSubnetMask']);
  drupal_set_message(t('New subnetwork %net/%mask will be allocated.',
    array('%net'=>$net,
      '%mask'=>$mask)));
  $ipv4['new']=true;
  $ipv4['ipv4']=guifi_ip_op($net);
  guifi_log(GUIFILOG_TRACE,"assigned IPv4: ".$ipv4['ipv4']);
  $ipv4['netmask']=$mask;
  $ipv4['interface_id'] = $iid;
  $form_state['values']['interfaces'][$iid]['ipv4'][]=$ipv4;
  $form_state['values']['interfaces'][$iid]['unfold']=true;
  $form_state['rebuild'] = true;
  
  return TRUE;
}

function guifi_interfaces_add_cable_p2p_link_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];
  $iid    = $values[1];
  $to_did = $form_state['values']['interfaces'][$iid]['to_did'];
  $rdevice = guifi_device_load($to_did);
  guifi_log(GUIFILOG_BASIC,
    sprintf('function guifi_interfaces_add_cable_p2p_link_submit(%d)',$iid),
//      $to_did);
      $form_state['values']['interfaces'][$iid]);
    
  $dlinked = db_fetch_array(db_query(
    "SELECT d.id, d.type " .
    "FROM {guifi_devices} d " .
    "WHERE d.id=%d",
    $to_did));

  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$form_state['values']);
  
  // get backbone /30 subnet
  $mask = '255.255.255.252';
  $net = guifi_get_subnet_by_nid(
    $form_state['values']['nid'],
    $mask,
    'backbone',
    $ips_allocated);
    
  $ip1 = guifi_ip_op($net);
  $ip2 = guifi_ip_op($ip1);
  guifi_merge_ip(array('ipv4'=>$ip1,'netmask'=>$mask),$ips_allocated,false);
  guifi_merge_ip(array('ipv4'=>$ip2,'netmask'=>$mask),$ips_allocated,true);
  
  $newlk['new']=true;
  $newlk['interface']=array();
  $newlk['link_type']='cable';
  $newlk['flag']='Planned';
  $newlk['nid']=$form_state['values']['nid'];
  $newlk['device_id'] = $to_did;
  if ($dlinked['type']=='radio')
    $newlk['routing'] = 'BGP';
  else  
    $newlk['routing'] = 'Gateway';
    
  $newlk['interface']['new'] = true;
  $newlk['interface']['device_id'] = $to_did;
  $free = guifi_get_free_interfaces($to_did,$rdevice);
  $newlk['interface']['interface_type']= array_shift($free);
//  $newlk['interface']['interface_type']= 'ether3';
  $newlk['interface']['ipv4']['new'] = true;
  $newlk['interface']['ipv4']['ipv4'] = $ip2;
  $newlk['interface']['ipv4']['netmask'] = $mask;
  
    
  $ipv4['new']=true;
  $ipv4['ipv4']=$ip1;
  $ipv4['netmask']=$mask;
  $ipv4['interface_id'] = $iid;
  $ipv4['links'][]=$newlk;
  
  $form_state['values']['interfaces'][$iid]['ipv4'][]=$ipv4;
  $form_state['values']['interfaces'][$iid]['unfold']=true;
  $form_state['rebuild'] = true;
  
  return TRUE;
}

function guifi_interfaces_add_cable_public_link_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];
  $iid    = $values[1];
  $ipv4_id= $values[3];
  $to_did = $form_state['values']['interfaces'][$iid]['ipv4'][$ipv4_id]['to_did'];
  $rdevice = guifi_device_load($to_did);
  
  guifi_log(GUIFILOG_BASIC,
    sprintf('function guifi_interfaces_add_cable_public_link_submit(%d)',$iid),
//      $form_state['values']);
      $form_state['clicked_button']['#parents']);

  $ips_allocated=guifi_get_ips('0.0.0.0','0.0.0.0',$form_state['values']);
  
  // get next available ip address
  $base_ip=
    $form_state['values']['interfaces'][$iid]['ipv4'][$ipv4_id];
  $item = _ipcalc($base_ip['ipv4'],$base_ip['netmask']);
  $ip= guifi_next_ip($item['netid'],$base_ip['netmask'],$ips_allocated);

  $newlk['new']=true;
  $newlk['interface']=array();
  $newlk['link_type']='cable';
  $newlk['flag']='Planned';
  $newlk['nid']=$form_state['values']['nid'];
  $newlk['device_id'] = $to_did;
  if ($rdevice['type']=='radio')
    $newlk['routing'] = 'BGP';
  else  
    $newlk['routing'] = 'Gateway';
    
  $newlk['interface']['new'] = true;
  $newlk['interface']['device_id'] = $to_did;
  $free = guifi_get_free_interfaces($to_did,$rdevice);
  $newlk['interface']['interface_type']= array_shift($free);
  $newlk['interface']['ipv4']['new'] = true;
  $newlk['interface']['ipv4']['ipv4'] = $ip;
  $newlk['interface']['ipv4']['netmask'] = $base_ip['netmask'];
  
  $form_state['values']['interfaces'][$iid]['ipv4'][$ipv4_id]['links'][]=$newlk;
  $form_state['values']['interfaces'][$iid]['unfold']=true;
//  print_r($form_state['values']); 
  $form_state['rebuild'] = true;
  
  return TRUE;
}

/* Delete interface */
function guifi_interfaces_delete_submit(&$form,&$form_state) {
  $values      = $form_state['clicked_button']['#parents'];
  $radio_id    = $values[count($values)-4];
  $interface_id= $values[count($values)-2];
  guifi_log(GUIFILOG_BASIC,sprintf('function guifi_interface_delete_submit(radio: %d, interface: %d)',
    $radio_id,$interface_id),$form_state['clicked_button']['#parents']);
  if ($values[0]=='interfaces') {
    $interface = &$form_state['values']['interfaces'][$interface_id];
  } else {
    $form_state['values']['radios'][$radio_id]['unfold'] = true;
    $interface = &$form_state['values']['radios'][$radio_id]['interfaces'][$interface_id];
  }
  $interface['unfold'] = true;
  $interface['deleted'] = true;
//  $form_state['deleteInterface']=($radio_id).','.($interface_id);
  $form_state['rebuild'] = true;
//  $form_state['action'] = 'guifi_interface_delete';
  return TRUE;
}

?>
