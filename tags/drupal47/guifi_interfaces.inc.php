<?php

function guifi_interface_form($edit) {

  unset($edit_form);
  unset($rows);

//  print_r($edit);
//  print $edit[edit_details];

  // edit details?
  if ($edit['edit_details'] != "") {
     
    $key_detail = explode(',',$edit['edit_details']);
    if ($key_detail[0] != 'interface')
      return;

    switch (count($key_detail)) {
    case 2:
      form_set_error(null,t('No edit details available for wired interfaces. All editable fields are accessible from the main edit form.'));
      unset($edit[edit_details]);
      break;
    case 3:
      form_set_error(null,t('No edit details available for wired ip addresses. All editable fields are accessible from the main edit form.'));
      unset($edit[edit_details]);
      break;
    case 4:
      $form = guifi_links_form($edit['edit_details'],$edit);
      break;
     } 
  } else

  // list interfaces
  if (!empty($edit['interfaces'])) foreach ($edit['interfaces'] as $key => $interface) {
      if ($interface[deleted]) continue;
      if ($interface[interface_type] == 'HotSpot') continue;
//       print_r($interface);

      // Present interface information & radio group
      if ($edit[type] == 'radio')
        $add_button=form_button(t('Add cable link'),'op['.$key.']');
      else
        // TODO: Link to router. Meanwhile, only link from the radio
        // $add_button=form_button(t('Link to router'),'op['.$key.']');
        $add_button = t("You must link this device from the router side.");
      $row = array(
                    array('data'=>form_radio('', 'edit_details', 'interface,'.$key),'rowspan'=>2,'valign'=>'top','width'=>1),
                    array('data'=>form_item(t('Type'),$interface['interface_type']),'width'=>1,'align'=>'left','valign'=>'top'),
//                    array('data'=>$interface['interface_type'],'width'=>1,'align'=>'left'),
                    array('data'=>form_textfield(t('Mac'), 'interfaces]['.$key.'][mac', $edit['interfaces'][$key]["mac"],17,17),
                          'align'=>'left','width'=>1),
                    array('data'=>$add_button));
      if ((user_access('administer guifi networks')) and ($edit[type] == 'radio'))  {
        $row2 = array(
                    array('data'=>form_select(t('Add network type'),'newip]['.$key,'backbone',array('backbone'=>t('internal network'),'public'=>t('public address')),null),'width'=>1),
                    array('data'=>form_button(t('Add network'),'op['.$key.']'), 'colspan'=>99)
                   );
         $row = array_merge($row,$row2);
      }
      $rows[] = $row;

      $arows = array();
//      $rows[] = array(array('data'=>form_select(null,'newip]['.$key,'backbone',array('backbone'=>t('internal link'),'public'=>t('public address')),null),'align'=>'right','width'=>10),
                      //array('data'=>form_button(t('Add network'),'op['.$key.']'), 'colspan'=>99));
      if (count($interface['ipv4'])>0) 
      foreach ($interface['ipv4'] as $ka=>$ipv4) {
        if ($ipv4[deleted])
          continue;
        if (user_access('administer guifi networks')) {
          $ip = form_textfield(null,'interfaces]['.$key.'][ipv4]['.$ka.'][ipv4' ,
                              $edit['interfaces'][$key]['ipv4'][$ka]['ipv4'],16,16,null);
          $mask = form_select(null,'interfaces]['.$key.'][ipv4]['.$ka.'][netmask',
                              $edit['interfaces'][$key]['ipv4'][$ka]['netmask'],guifi_types('netmask',30,15),null);
        } else {
          $ip = $ipv4[ipv4];
          $mask = $ipv4[netmask];
        }

        $arows[] = array(
                         array('data'=>form_radio('', 'edit_details', 'interface,'.$key.','.$ka),'rowspan'=>2,'valign'=>'top','width'=>1),
                         array('data'=>$ip,'width'=>1),
                         array('data'=>$mask,'width'=>1),
                         array('data'=>form_button(t('Add cable link'),'op['.$key.','.$ka.']'),'width'=>1));
//        print_r($ipv4);
        $lrows = array();
        if (!empty($ipv4[links])) foreach ($ipv4['links'] as $kl=>$link) {
          if ($link[deleted]) continue;
          $ip = _ipcalc($ipv4['ipv4'],$ipv4['netmask']);
//          print_r($link);

          // fill routing field
          if (user_access('administer guifi networks'))
             $routing = form_select(null,'interfaces]['.$key.'][ipv4]['.$ka.'][links]['.$kl.'][routing', $link[routing], guifi_types('routing'));
          else 
             $routing = $link[routing];
         

          $lrows[] = array(
                           form_radio('', 'edit_details', 'interface,'.$key.','.$ka.','.$kl),
                           guifi_get_hostname($link['device_id']),
                           form_select(null,'interfaces]['.$key.'][ipv4]['.$ka.'][links]['.$kl.'][flag', $link['flag'], guifi_types('status')),
                           $link['interface']['interface_type'],
                           $link['interface']['ipv4']['ipv4'],
                           $routing
                          );
        }
        if (count($lrows) > 0) {
          $header = array(null,t('linked device'),t('status'),t('type'),t('ip'),t('routing'));
          $arows[] = array(array('data'=>theme('table',$header,$lrows),'colspan'=>99));
        } else
          $arows[] = array(array('data'=>'&nbsp;','colspan'=>99));
        
//        $lrows[] = array(array('data'=>form_button(t('Add link'), 'op['.$interface[id].']'),'colspan'=>5));
      }
//      $lrows[] = array(
//               array('data'=>form_select(t('Mode'), 'newlink_type', 'AP/client', array('ap/client'=>'AP/Client','wds'=>'WDS/bridge p2p'), NULL),'colspan'=>2,'valign'=>'bottom','align'=>'right'),
//               array('data'=>form_button(t('Add link'), 'op['.$edit[radios][$key][interfaces][$interface_id].']'),'valign'=>'bottom')
//                       );
      $header = array(null,t('ip'),t('netmask'),null);
      $rows[] = array(array('data'=>theme('table',$header,$arows),'colspan'=>99));
      $rows[] = array(array('data'=>null,'colspan'=>99));
  }
//  print_r($rows);
  if (isset($rows)) {

    if (user_access('administer guifi networks'))
      $rows[] = array(array('data'=>form_button(t('Edit selected'), 'op').form_button(t('Delete selected'), 'op'),'colspan'=>8));
    $headers = array(null,t('type'),t('mac'));
    $form .= form_group(t('device interfaces'),theme('table', array(), $rows),t("Use this form section to describe the cable connections between devices or servers in your node. You must define <strong>ONLY</strong> the public servers available to the network, or those which require a public address assigned, <strong>NOT</strong> your private network behind your firewall/NAT.<br />Do not use this section if you don't understand this."));
  }

  // Edit interface form or add new radio
  if (!$edit[edit_details])
  if ($edit[id] > 0) {
    $free = guifi_get_free_interfaces($edit[id],$edit);
    if ( count($free) > 0 ) {
      $erow[] = array(
                 array('data'=>form_select(t('Interface'), 'newinterface_type', 'Lan', guifi_array_combine($free, $free), NULL),'valign'=>'bottom'),
                 array('data'=>form_button(t('Add interface'), 'op'),'valign'=>'bottom')
                   );
      $form .= form_group(t('Add new interface'),theme('table',array(),$erow),t('Add a new interface for wired connections to another devices which are avaiable at the network, i.e. to another radio while building a Supernode.'));
    } else {
      $edit_form .= form_item(null,t('This device has all the possible interfaces already defined.'));
      $form .= form_group(t('Add new interface'),$edit_form,null);
    }
  } else {  
    $edit_form .= form_item(null,t('You can add interfaces to this device once has been saved into de database'));
    $form .= form_group(t('Add new interface'),$edit_form,null);
  }

  return $form;
}

function guifi_add_interface_address(&$edit,$interface) {

  if ($edit[newip][$interface] == 'backbone')
    $mask = '255.255.255.252';
  else
    $mask = '255.255.255.224';

  $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
  $net = guifi_get_subnet_by_nid($edit[nid],$mask,$edit[newip][$interface],$ips_allocated);
  $ip = guifi_ip_op($net);
  $newif = array();
  $newif['new']=true;
  $newif[interface_id]=$interface;
  $newif[ipv4]=$ip;
  $newif[netmask]=$mask;
  $edit[interfaces][$interface][ipv4][]=$newif;

}

function guifi_add_interface(&$edit) {

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
