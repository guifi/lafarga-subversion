<?php

/* guifi_interfaces_form(): Main cable interface edit form */
function guifi_interfaces_form(&$form,&$edit,&$fw = 500) {
  
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
  $ipv4_count = 0;        // ipv4 addresses counter
  $links_count = array(); // links counter
  $ilist = array();       // Per interface_type, stores the interface id's
  
  foreach ($edit['interfaces'] as $ki => $interface) {
    if ($interface['interface_type'] == null)
      continue;

    $interfaces_count++;

    $it = $interface['interface_type'];
    $ilist[$it] = $ki;

    if (count($interface['ipv4']) > 0)
    foreach ($interface['ipv4'] as $ka => $ipv4) {

      $ipv4_count++;
      
      $links_count[$it] += guifi_link_ipv4_form(
        $f[$it][$ki]['ipv4'][$ka],
        $ipv4,
        $interface,
        array('interfaces',$ki,'ipv4',$ka),
        $weight);

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
    $form['interfaces'][$ilist[$it]] = array(
      '#type' => 'fieldset',
      '#title' => $title,
      '#weight' => $weight,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $weight++;

    if (!empty($value))
      foreach ($value as $ki => $fin)
        $form['interfaces'][$ki] = $fin;
    else {
      if ((!$edit['interfaces'][$ilist[$it]]['new']) and
        ($it != 'wds/p2p') and 
        ($it != 'wLan/Lan'))
        $form['interfaces'][$ki]['delete_address'] = array(
          '#type' => 'button',
          '#parents'=>array('interfaces',$ilist[$it],'delete_interface'),
          '#value'=>t('Delete interface'),
          '#name'=>implode(',',array(
               '_action',
               '_guifi_delete_radio_interface',
               $rk,$ilist[$it]
               )),
          '#weight' => $weight++,
        );
    }
  }
  $form['interfaces']['#title'] .= ' - '.
    $interfaces_count.' '.t('interface(s)').' - '.
    array_sum($links_count).' '.t('link(s)').' - '.
    $ipv4_count.' '.t('address(es)');
  $form['interfaces']['NewInterfaceName'] = array(
    '#type'=>'select',
    '#parents'=>array('NewInterfaceName'),
    '#options'=>array_merge(
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
  guifi_log(GUIFILOG_TRACE,'function _guifi_add_interface(%d)');
  $fw = 0;
  guifi_form_hidden($form,$edit,$fw);

  if ($edit['NewInterfaceName'] != 'other') {
    $form['help'] = array(
     '#type' => 'item',
     '#title' => t('Are you sure that do you want to create a %name interface on this device?',
       array('%name'=>$edit['NewInterfaceName'])),
  //     '#value' => t('Radio').' #'.$radio.'-'.$edit['radios'][$radio]['ssid'],
     '#description' => t('If you save at this point, this interface will be created and device saved.'),
      '#weight' => 0,
    );
  } else {
    $form['NewInterfaceName'] = array(
      '#type'=> 'textfield',
      '#title'=> t('Enter the name of the interface to be defined'),
      '#size'=> 10,
      '#description' => t('If you save at this point, this interface will be created and device saved.'),
      '#weight' => 0,
    );
  }
  drupal_set_title(t(
   'Create a %iname interface at %name',
   array('%name'=>$edit['nick'],
     '%iname'=>$edit['NewInterfaceName'])
   ));
  _guifi_device_buttons($form,$action);

  return FALSE;
}

/* _guifi_add_interface_submit(): Action */
function _guifi_add_interface_submit(&$edit,$action) {
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
/*
function _guifi_add_interface_submit(&$edit) {

 $int = db_fetch_object(db_query('SELECT max(id)+1 id FROM {guifi_interfaces}'));
 $interface_id=$int->id;

 $edit[interfaces][$interface_id]=array();
 $edit[interfaces][$interface_id]['new']=true;
 $edit[interfaces][$interface_id][device_id]=$edit[id];
 $edit[interfaces][$interface_id][id]=$interface_id;
 $edit[interfaces][$interface_id][radiodev_counter]=null;
 $edit[interfaces][$interface_id][interface_type]=$edit[newinterface_type];

 return $edit;
}
*/


function guifi_delete_interface(&$edit,$op) {

  $parse = explode(',',$edit[edit_details]);


  // That should never occur
  if ($parse[0] != 'interface')
    return;
 
  if ($edit[interfaces][$parse[1]][radiodev_counter] != null) {
    form_set_error(null,t('You can\'t delete items which are also being used in the wireless interfaces.'));
    unset($edit[edit_details]);
    return;
  }

  switch (count($parse)) {
  case 2:
      $msg .= '<h2>'.t('Are you sure you want to delete this interface?').'</h2>'.$edit[interfaces][$parse[1]][interface_type];
    break;
  case 3:
      $msg = '<h2>'.t('Are you sure you want to delete this ip address?').'</h2>'.
                 $edit[interfaces][$parse[1]][interface_type].'-'.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][ipv4].'/'.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][netmask];
    break;
  case 4:
      $msg = '<h2>'.t('Are you sure you want to delete this link?').'</h2>'.
                 $edit[interfaces][$parse[1]][interface_type].' '.
                 guifi_get_hostname($edit[id]).'-'.
                 guifi_get_hostname($edit[interfaces][$parse[1]][ipv4][$parse[2]][links][$parse[3]][device_id]).' ('.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][ipv4].'/'.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][links][$parse[3]]['interface'][ipv4][ipv4].
                 ')';
    break;   
  }

  switch ($op) {
  case t('Delete selected'):
      $output .= $msg.'<br />'.form_button(t('Confirm delete'),'op').
                        form_button(t('Back to list'),'op');
      $output .= $message;
    break;
  case t('Confirm delete'):
    switch (count($parse)) {
      case 2:
        $type = 'Interface';
        $name = $edit[interfaces][$parse[1]][interface_type];
        if ($edit[interfaces][$parse[1]]['new'])
          unset($edit[interfaces][$parse[1]]);
        else
          $output .= form_hidden('interfaces]['.$parse[1].'][deleted',true);
        break;
      case 3:
        $type = 'IP Adddress';
        $name = $edit[interfaces][$parse[1]][interface_type].'-'.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][ipv4].'/'.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][netmask];
        if ($edit[interfaces][$parse[1]][ipv4][$parse[2]]['new'])
          unset($edit[interfaces][$parse[1]][ipv4][$parse[2]]);
        else
          $output .= form_hidden('interfaces]['.$parse[1].'][ipv4]['.$parse[2].'][deleted',true);
        break;
      case 4:
        $type = 'Cable link';
        $name = $edit[interfaces][$parse[1]][interface_type].' '.
                 guifi_get_hostname($edit[id]).'-'.
                 guifi_get_hostname($edit[interfaces][$parse[1]][ipv4][$parse[2]][links][$parse[3]][device_id]).' ('.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][ipv4].'/'.
                 $edit[interfaces][$parse[1]][ipv4][$parse[2]][links][$parse[3]]['interface'][ipv4][ipv4].
                 ')';
        if ($edit[interfaces][$parse[1]][ipv4][$parse[2]][links][$parse[3]]['new'])
          unset($edit[interfaces][$parse[1]][ipv4][$parse[2]][links][$parse[3]]);
        else
          $output .= form_hidden('interfaces]['.$parse[1].'][ipv4]['.$parse[2].'][links]['.$parse[3].'][deleted',true);
        break;
    }
    $output .= '<h2>'.t('%name deleted',array('%name' => theme('placeholder',$type))).'</h2>';
    $output .= '<br />'.form_button(t('Back to list'),'op');
    drupal_set_message(t('%type% %name% has been deleted. To prevent accidental deletions, the delete will be confirmed only when you submit the changes.',
            array('%type%' => theme('placeholder',$type),'%name%' => theme('placeholder',$name))
            ));
    break;
  }
  $output .= guifi_form_hidden('',$edit);
  print theme('page',form($output));
  exit;
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
