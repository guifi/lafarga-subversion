<?php

function guifi_links_form($edit_details,$edit) {

  // edit link details
  guifi_log(GUIFILOG_TRACE,'guifi_links_form()',$edit);

//  print_r($edit);
//  print $edit[edit_details];

  unset($rows);
  $det =  explode(',',$edit_details);
  list($radio_id,$interface_id, $ipv4_id, $link_id) = explode(',',$edit_details);
  if ($radio_id == 'interface') {
    $curr_link = $edit[interfaces][$interface_id][ipv4][$ipv4_id][links][$link_id][linked];
    $fprefix =  'interfaces]['.$interface_id.'][ipv4]['.$ipv4_id.'][';
    if ($edit[type] == 'radio')
      $mode = 'cable';
    else
      $mode = 'cable-router';
    $ifvar = $edit[interfaces][$interface_id][ipv4][$ipv4_id];
    $interface_type = $edit['interfaces'][$interface_id]['interface_type'];
  } else {
    $curr_link = $edit[radios][$radio_id][interfaces][$interface_id][ipv4][$ipv4_id][links][$link_id][linked];
    $fprefix =  'radios]['.$radio_id.'][interfaces]['.$interface_id.'][ipv4]['.$ipv4_id.'][';
    $mode =  $edit['radios'][$radio_id]['mode'];
    $ifvar = $edit[radios][$radio_id][interfaces][$interface_id][ipv4][$ipv4_id];
    $interface_type = $edit['radios'][$radio_id]['interfaces'][$interface_id]['interface_type'];
  }

  if (user_access('administer guifi networks')) {
    $local_ip = form_textfield(null,$fprefix.'ipv4' ,
                               $ifvar['ipv4'],16,16,null);
    $local_mask = form_select(null,$fprefix.'netmask',
                               $ifvar['netmask'],guifi_types('netmask',30,15),null);
    $remote_ip = form_textfield(null,$fprefix.'links]['.$link_id.'][interface][ipv4][ipv4' ,
                               $ifvar['links'][$link_id]['interface']['ipv4']['ipv4'],16,16,null);
    $remote_mask = form_select(null,$fprefix.'links]['.$link_id.'][interface][ipv4][netmask',
                               $ifvar['links'][$link_id]['interface']['ipv4']['netmask'],guifi_types('netmask',30,15),null);
  } else {
    $local_ip    = $ifvar['ipv4'];
    $local_mask  = $ifvar['netmask'];
    $remote_ip   = $ifvar['links'][$link_id]['interface']['ipv4']['ipv4'];
    $remote_mask = $ifvar['links'][$link_id]['interface']['ipv4']['netmask'];
  }

  if ($ifvar['links'][$link_id]['new']) {
    $link_choices = guifi_devices_select($edit[nid],$ifvar['links'][$link_id]['link_type'],
                        $mode,$edit[id],$radio_id); 
    $linked = form_select(null,$fprefix.'links]['.$link_id.'][linked',
                               $curr_link,
//                               $ifvar['links'][$link_id]['device_id'],
                               $link_choices,null);
  } else {
    $linked = guifi_get_nodename($ifvar['links'][$link_id]['nid']).'-'.
              guifi_get_hostname($ifvar['links'][$link_id]['device_id']);
  }

  $row[] = array(
               $ifvar['links'][$link_id]['link_type'],
               $linked,
               form_select(null,$fprefix.'links]['.$link_id.'][flag', $ifvar['links'][$link_id]['flag'], guifi_types('status'))
              );
  $lform[] = array(theme('table',array(t('type'),t('linked node-device'),t('status')),$row));
  $row = array();
  if ( (($interface_type != 'Wan') and ($edit[type] == 'radio')) or
     (!$ifvar['links'][$link_id]['new'])) {
    $row[] = array(
                 array('data'=>'<strong>'.t('local').': </strong>','align'=>'right'),
                 $interface_type,                 
                 $local_ip, $local_mask
                );
//  $i = db_fetch_array(db_query('SELECT interface_type FROM {guifi_interfaces}
    if ($mode == 'cable' ) {
      $ilist = array_merge(array($ifvar['links'][$link_id]['interface']['interface_type']),guifi_get_all_interfaces($ifvar['links'][$link_id]['device_id']));
      $edit_type = form_select(null,$fprefix.'links]['.$link_id.'][interface][interface_type',
                                    $ifvar['links'][$link_id]['interface']['interface_type'],
                                    guifi_array_combine($ilist,$ilist));
    } else {
      $edit_type = $ifvar['links'][$link_id]['interface']['interface_type'];
    }
    $row[] = array(
                 array('data'=>'<strong>'.t('remote').': </strong>','align'=>'right'),
                 $edit_type,                
                 $remote_ip, $remote_mask
                );

    $lform[] = array(theme('table',array(null,t('type'),t('ip'),t('mask')), $row));
  }
  $form.= form_group(t('Link details form'),theme('table',null,$lform).
              form_button(t('Back to list'), 'op').
              form_button(t('Reset'), 'op')
              ,null);
  return $form;
}

function guifi_links_validate(&$edit) {

//  print_r($edit);

  guifi_log(GUIFILOG_TRACE,"guifi_links_validate()");
  guifi_log(GUIFILOG_FULL,"edit",$edit);

  // Validating radio links
  if (!empty($edit[radios]))      foreach ($edit[radios] as $radio_id=>$radio)
  if (!empty($radio[interfaces])) foreach ($radio[interfaces] as $interface_id=>$interface)
  if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4_id=>$ipv4)
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $link_id=>$link) {
//    print "Link: ".$key."\n<br>"; print_r($link); print "\n<br>";
    guifi_links_validate_recurse($edit[radios][$radio_id][interfaces][$interface_id][ipv4][$ipv4_id],$link_id,$interface[interface_type],'radios]['.$radio_id.'][interfaces]['.$interface_id.'][ipv4]['.$ipv4_id.'][links]['.$link_id.'][flag');
  }
  // Validating cable/other links
  if (!empty($edit[interfaces])) foreach ($edit[interfaces] as $interface_id=>$interface)
  if (!empty($interface[ipv4]))   foreach ($interface[ipv4] as $ipv4_id=>$ipv4)
  if (!empty($ipv4[links]))       foreach ($ipv4[links] as $link_id=>$link) {
//    print "Link: ".$key."\n<br>"; print_r($link); print "\n<br>";
    guifi_links_validate_recurse($edit[interfaces][$interface_id][ipv4][$ipv4_id],$link_id,$interface[interface_type],'interfaces]['.$interface_id.'][ipv4]['.$ipv4_id.'][links]['.$link_id.'][flag');
  }
}

function guifi_links_validate_recurse(&$link,$link_id,$interface_type,$id_field) {

//    print "Link id: $link_id Interface_type: $interface_type $id_field\n<br>";

    if ($link[links][$link_id]['new']==true) 
    if (!empty($link[links][$link_id][linked])) {
//      print "New Linked: \n<br>"; print_r($link);
//      print "\n<br>";
      list($nid,$device_id,$radiodev_counter) = explode(',',$link[links][$link_id][linked]);
      $link[links][$link_id][nid]=$nid;
      $link[links][$link_id][device_id]=$device_id;
      if ($link[links][$link_id][link_type] == 'wds') {
        // WDS reuse the existing interface, but always create a new IP over it, so we need to get a free IP id for it
        $qryIDs = db_query('SELECT a.id FROM {guifi_ipv4} a, {guifi_interfaces} i WHERE a.interface_id=i.id AND i.interface_type="wds/p2p" AND i.device_id=%d AND i.radiodev_counter=%d',$device_id,$radiodev_counter);
        while ($id = db_fetch_array($qryIDs))
          $ipIDs[] = $id['id'];
        $nextID = 0;
//        print "\n<br>";
//        print_r($ipIDs);
//        print "\n<br>";
        while (in_array($nextID,$ipIDs))
          $nextID = $nextID + 1;
        $ipv4_id=$nextID;
      } else {
        $ipv4_id=$radiodev_counter;
      }
//      print "IPV4_id: " .$ipv4_id;
//      print "\n<br>";
      $link[links][$link_id][ipv4_id]=$ipv4_id;
      $link[links][$link_id][ipv4][id]=$ipv4_id;

      $link[links][$link_id]['interface'][device_id]=$device_id;
      $link[links][$link_id]['interface'][radiodev_counter]=$radiodev_counter;
      if ($link[links][$link_id][link_type] == 'ap/client') {
//        print_r($interface_type);
//        print_r($edit);
//        print_r($link);
        if ($interface_type == 'Wan') {
          if ($radiodev_counter == 0)
             $itype = 'wLan/Lan';
          else
             $itype = 'wLan';
          $query_ri = db_query('SELECT id,mac FROM {guifi_interfaces} WHERE interface_type="%s" AND device_id=%d AND radiodev_counter=%d',$itype,$device_id,$radiodev_counter);
        } else {
           $itype = 'Wan';
           $query_ri = db_query('SELECT id,mac FROM {guifi_interfaces} WHERE interface_type="Wan" AND device_id=%d',$device_id);
        }
//        print "Itype: $itype Device: $device_id Radiodev: $radiodev_counter\n<br>";
//        $ri = db_fetch_array(db_query('SELECT id,mac FROM {guifi_interfaces} WHERE interface_type="%s" AND device_id=%d AND radiodev_counter=%d',$itype,$device_id,$radiodev_counter));
        $ri = db_fetch_array($query_ri);
        $link[links][$link_id]['interface_id']=$ri['id'];
        $link[links][$link_id]['interface'][id]=$ri['id'];
        $link[links][$link_id]['interface'][mac]=$ri[mac];
        $link[links][$link_id]['interface'][ipv4][interface_id]=$ri[id];

        // if WAN, assign local and remote ips
        if ($interface_type == 'Wan') {
          $ipAP = db_fetch_array(db_query('SELECT * FROM {guifi_ipv4} WHERE interface_id=%d AND id=%d',$ri[id],$radiodev_counter));
          $link[links][$link_id]['interface'][ipv4][ipv4]=$ipAP[ipv4];
          $link[links][$link_id]['interface'][ipv4][netmask]=$ipAP[netmask];
          $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
          $item = _ipcalc($ipAP[ipv4],$ipAP[netmask]);
          $link['new'] = true;
          $link[ipv4] = guifi_next_ip($item['netid'],$ipAP[netmask],$ips_allocated);
//          print_r($item);
          $link[netmask] = $ipAP[netmask];
        }
      }
    } // if link is new, get linked device information

    // validating same netmask
//    print_r($link);
    if (($link[ipv4] != '' ) and ($link[links][$link_id]['interface'][ipv4][ipv4] != '') and (!$link[links][$link_id][deleted])) {
      $item1 = _ipcalc($link[ipv4],$link[netmask]);
      $item2 = _ipcalc($link[links][$link_id]['interface'][ipv4][ipv4],$link[links][$link_id]['interface'][ipv4][netmask]);
      if (($item1[netstart] != $item2[netstart]) or ($item1[netend] != $item2[netend])) {
        if (($link[links][$link_id][link_type] == 'ap/client') or
           ($link[links][$link_id][link_type] == 'wds')) {
          form_set_error($id_field,$link[ipv4].'/'.$link[netmask].'-'.$link[links][$link_id]['interface'][ipv4][ipv4].'/'.$link[links][$link_id]['interface'][ipv4][netmask].': '.t('Link ip addresses are not in the same subnet'));
        }
      }
    }

    // devices already linked
    // one ap per each client
    // (see @ guifi_devices_select)

  return; // End validations
  $wlinks = 0;
  $clients = 0;
  $lexists = array();


  if (!empty($edit['links'])) foreach ($edit['links'] as $key => $link) {

    guifi_log(GUIFILOG_TRACE, "Checking link", $link);
    if (($link['newlink']) or ($link['flag'] == 'Dropped')) {
      break;
    }

//    print "\n<br>Checking link: "; print_r($link);

    if ((!empty($link['if_local']['ipv4'])) and
        (empty($link['if_remote']['netmask'])) and
        (!empty($link['if_remote']['ipv4'])))  {
      $link['if_remote']['netmask'] = $link['if_local']['netmask'];
      $edit['links'][$key]['if_remote']['netmask'] = $link['if_local']['netmask'];
    }

    // if interface is not vlan/wds, check if the interface already exists at the device
    if (!($link['if_local']['id'] > 0)) {
      $if = guifi_get_existent_interface($link['if_local']['device_id'],$link['if_local']['interface_type']);
      if ($if)
        $edit['links'][$key]['if_local']['id'] = $if->id;
    }
    if (!($link['if_remote']['id'] > 0)) {
      $if = guifi_get_existent_interface($link['if_remote']['device_id'],$link['if_remote']['interface_type']);
      if ($if)
        $edit['links'][$key]['if_remote']['id'] = $if->id;
    }


    // Checking unique ip
    unset($if_local);
    unset($if_remote);
    if (!empty($link['if_local']['ipv4'])) {
      $if_local = guifi_get_interface($link['if_local']['ipv4']);
      $item_local = _ipcalc($link['if_local']['ipv4'],$link['if_local']['netmask']);
      if ($if_local) {
        // exists, check is at the same interface
        if ($if_local->id != $link['if_local']['id']) {
          if ($item_local['netstart'] != $link['if_local']['ipv4']) {
            form_set_error($id_field,t('IP address (%ip) at local device is already present in another interface within the database.',
                     array('%ip'=>$link['if_local']['ipv4']))); 
            $edit['link_checked'] = $key;
          } else if ($if_local->device_id != $link['if_local']['device_id']){
            form_set_error($id_field,t('IP address (%ip) at local device is already present in another device within the database.',
                     array('%ip'=>$link['if_local']['ipv4']))); 
            $edit['link_checked'] = $key;
          } else {
            $edit['links'][$key]['if_local']['id'] = $if_local->id;
          }
        }
      }
    } 
    if (!empty($link['if_remote']['ipv4'])) {
      $if_remote = guifi_get_interface($link['if_remote']['ipv4']);
      $item_remote = _ipcalc($link['if_remote']['ipv4'],$link['if_remote']['netmask']);
      if ($if_remote) {
        // exists, check is at the same interface
        if ($if_remote->id != $link['if_remote']['id']) {
          if ($item_remote['netstart'] != $link['if_remote']['ipv4']) {
            form_set_error($id_field,t('IP address (%ip) at remote device is already present in another interface within the database.',
                     array('%ip'=>$link['if_remote']['ipv4']))); 
            $edit['link_checked'] = $key;
          } else if ($if_remote->device_id != $link['if_remote']['device_id']){
            form_set_error($id_field,t('IP address (%ip) at remote device is already present in another device within the database.',
                     array('%ip'=>$link['if_remote']['ipv4']))); 
            $edit['link_checked'] = $key;
          } else {
            $edit['links'][$key]['if_remote']['id'] = $if_remote->id;
          }
        }
      } 
    }

    if ((!empty($link['if_local']['ipv4'])) and
        (!empty($link['if_remote']['ipv4']))) { 
      if ($item_local['netstart'] != $item_remote['netstart']) {
            form_set_error(null,t('IP address (%ip1 and %ip2) are not in the same subnetwork.',
                     array('%ip1'=>$link['if_local']['ipv4'],
                           '%ip1'=>$link['if_remote']['ipv4']))); 
            $edit['link_checked'] = $key;
      }
    }

    $mode = db_fetch_object(db_query("SELECT mode FROM {guifi_radios} WHERE id=%d",$link['if_remote']['device_id']));

    // Compatible interfaces
    if (!(guifi_ip_type($link['if_local']['interface_type'],$link['if_remote']['interface_type']))) {
      form_set_error(null,t('Can\'t link a "%itype1" with "%itype2", please review the "Edit link" form',
                array('%itype1'=>theme('placeholder',$link['if_local']['interface_type']),
                      '%itype2'=>theme('placeholder',$link['if_remote']['interface_type']))));
      $edit['link_checked'] = $key;
    }

    // not linking itself
    if ($link['if_local']['device_id'] == $link['if_remote']['device_id']) {
      form_set_error(null, t('Can\'t link a device with itself.'));
      $edit['link_checked'] = $key;
    }

    // Counting for checking number of AP & Clients
    if (array_key_exists($link['link_type'],array('ap/client','wds','bridge')))
      $wlinks++;
    if (($link['link_type'] == 'ap/client') and ($link['flag'] != 'Dropped'))
      $clients++;
    if (($clients > 1)  && ($edit['mode'] == 'client')) {
      form_set_error(null, t('Can\'t have more than one AP per client.') );
      $edit['link_checked'] = $key;
    }

    if (($edit['mode'] == 'ap') && ($link['link_type'] == 'ap/client')) {
      $other= db_fetch_object(db_query("SELECT count(*) count FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l1.device_id = %d AND l2.device_id NOT IN (%d,%d) AND l1.link_type = 'ap/client'",$link['if_remote']['device_id'],$link['if_remote']['device_id'], $link['if_local']['device_id']));
      if ($other->count > 0) {
        form_set_error(null, t('This client already has an AP.') );
        $edit['link_checked'] = $key;
      }
    }


    if (isset($lexists[$link['if_remote']['device_id']])) {
      form_set_error(null, t('This devices are already linked. Delete the link first.'));
      $edit['link_checked'] = $key;
    }
    if ($link['flag'] != 'Dropped')
      $lexists[$link['if_remote']['device_id']] = true;
  

    switch ($link['link_type']) {
    case 'wds':
      if ($edit['mode'] != 'ap') {
        form_set_error(null, guifi_get_hostname($link['if_local']['device_id']).': '.t('Must be in ap mode for accepting WDS links.'));
        $edit['link_checked'] = $key;
      }
      if ($mode->mode != 'ap') {
        form_set_error(null, guifi_get_hostname($link['if_remote']['device_id']).': '.t('Must be in ap mode for accepting WDS links.'));
        $edit['link_checked'] = $key;
      }
      break;
    case 'ap/client':
      if ($edit['mode'] == 'ap') {
        if ($mode->mode == 'ap') {
          form_set_error(null, guifi_get_hostname($link['if_remote']['device_id']).': '.t('Must be in client mode for accepting a link from this AP.'));
          $edit['link_checked'] = $key;
        }
      } else {
        if ($mode->mode != 'ap') {
          form_set_error(null, guifi_get_hostname($link['if_remote']['device_id']).': '.t('Must be in AP mode for accepting a link from this client.'));
          $edit['link_checked'] = $key;
        }
      }
      break;
    } //eof switch link_type

  } // eof foreach link
}

function guifi_delete_link($edit,$op) {

  $output .= guifi_form_hidden('',$edit);

  list($radio_id,$interface_id, $ipv4_id, $link_id) = explode(',',$edit[edit_details]);
  if ($radio_id != 'interface') {
    $link_text = $edit[radios][$radio_id][ssid].' / '.
                  guifi_get_hostname($edit[radios][$radio_id][interfaces][$interface_id][ipv4][$ipv4_id][links][$link_id][device_id]).
                  ' ('.$edit[radios][$radio_id][interfaces][$interface_id][ipv4][$ipv4_id][links][$link_id][link_type].')';
  } else {
    $link_text = $edit[nick].' / '.
                  guifi_get_hostname($edit[interfaces][$interface_id][ipv4][$ipv4_id][links][$link_id][device_id]).
                  ' ('.$edit[interfaces][$interface_id][ipv4][$ipv4_id][links][$link_id][link_type].')';
  }
  switch ($op) {
  case t('Delete selected'):
      $output .= '<h2>'.t('Are you sure you want to delete this link?').'</h2>'.$link_text;
      $output .= '<br>'.form_button(t('Confirm delete'),'op').
                        form_button(t('Back to list'),'op');
      $output .= $message;
    break;
  case t('Confirm delete'):
      if ($radio_id != 'interface')  {
        $output .= form_hidden('radios]['.$radio_id.'][interfaces]['.$interface_id.'][ipv4]['.$ipv4_id.'][links]['.$link_id.'][deleted',true);
        if (($edit[radios][$radio_id][interfaces][$interface_id][ipv4][$ipv4_id][links][$link_id][link_type]=='wds') or
           ($edit[radios][$radio_id][interfaces][$interface_id][interface_type]=='Wan'))
          $output .= form_hidden('radios]['.$radio_id.'][interfaces]['.$interface_id.'][ipv4]['.$ipv4_id.'][deleted',true);
      } else
        $output .= form_hidden('interfaces]['.$interface_id.'][ipv4]['.$ipv4_id.'][links]['.$link_id.'][deleted',true);
      $output .= '<h2>'.t('Link deleted').'</h2>'.$link_text;
      $output .= "\n<br>".t('<strong>Warning:</strong> If you confirm at this point, this operation will delete information from the database and save any other change.');
      $output .= '<br>'.form_button(t('Undo changes'),'op').form_button(t('Save & continue edit'),'op');
    break;
  }
  print theme('page',form($output));
  exit;
    if (isset($edit['link_checked'])) {
      drupal_set_message(t('The link with %name has been deleted. To prevent accidental deletions, the delete will be confirmed only if you submit the changes now. Any other action will revert this deletion. Whith this method you can just delete the links one by one.',array('%name' => theme('placeholder',guifi_get_hostname($edit['links'][$edit['link_checked']]['if_remote']['device_id'])))));
      unset($edit['links'][$edit['link_checked']]);
      $edit['link_checked'] = 0;
   }
}

function guifi_add_link(&$edit,$type,$interface_ipv4_id) {

//  print "New Link ".$type." Interface: ".$interface_ipv4_id."\n<br>";
//  print_r($edit);

  $parse = explode(',',$interface_ipv4_id);
  $interface_id = $parse[0];
  if ((count($parse) == 2) and ($parse[1] != ''))
    $ipv4_id = $parse[1]; 
  else
    unset($ipv4_id);

  // if wireless, find which radio has the interface
  if (($type == 'wds') or ($type == 'ap/client')) 
   foreach ($edit[radios] as $radio_id=>$radio)
   foreach ($radio[interfaces] as $i_key=>$interface)
   if ($i_key == $interface_id) break 2;
//  print "Radio: $radio_id";

  // get list of the current used ips
  $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
//  print "Ips allocated: ".count($ips_allocated)."\n<br>";

  // fill new variables 
  $newlk = array();
  $newlk['new']=true;
  $newlk[link_type]=$type;
  $newlk[flag]='Planned';
  switch ($type) {
  case 'wds':
    $net = guifi_get_subnet_by_nid($edit[nid],'255.255.255.252','backbone',$ips_allocated);
    $ip1 = guifi_ip_op($net);
    $ip2 = guifi_ip_op($ip1);
    guifi_merge_ip(array('ipv4'=>$ip1,'netmask'=>'255.255.255.252'),$ips_allocated,false);
    guifi_merge_ip(array('ipv4'=>$ip2,'netmask'=>'255.255.255.252'),$ips_allocated,true);
    $newlk['interface']['new']=true;
    $newlk['interface'][interface_type]='wds/p2p';
    $newlk['interface'][ipv4]=array();
    $newlk['interface'][ipv4]['new']=true;
    $newlk['interface'][ipv4][ipv4]=$ip2;
    $newlk['interface'][ipv4][netmask]='255.255.255.252';
    $newif = array();
    $newif['new']=true;
    $newif[interface_id]=$interface_id;
    $newif[ipv4]=$ip1;
    $newif[netmask]='255.255.255.252';
    $newif[links] = array();
    $newif[links][] = $newlk;
    $edit[radios][$radio_id][interfaces][$interface_id][ipv4][]=$newif;
    end($edit[radios][$radio_id][interfaces][$interface_id][ipv4]);
    $newlink_id = key($edit[radios][$radio_id][interfaces][$interface_id][ipv4]);
    $edit[edit_details]=implode(',',array($radio_id,$i_key,$newlink_id,0));
    break;
  case 'ap/client':
    $newlk[link_type]='ap/client';
    // if in mode AP
    if ($edit[radios][$radio_id][mode] == 'ap') {
       $base_ip[ipv4]=$edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][ipv4];
       $base_ip[netmask]=$edit[radios][$radio_id][interfaces][$interface_id][ipv4][$radio_id][netmask];
       $item = _ipcalc($base_ip[ipv4],$base_ip[netmask]);
       $ip= guifi_next_ip($item['netid'],$base_ip[netmask],$ips_allocated);
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
    break;
  case 'cable':
//    print $edit[newip][$interface_id];

    if (!isset($edit[newip][$interface_id])) 
      $edit[newip][$interface_id] == 'backbone';
    if (isset($ipv4_id))  {
      $ip1 = $edit[interfaces][$interface_id][ipv4][$ipv4_id][ipv4];
      $mask1 = $edit[interfaces][$interface_id][ipv4][$ipv4_id][netmask];
    } else {
      if ($edit[newip][$interface_id] == 'backbone')
        $mask1 = '255.255.255.252';
      else 
        $mask1 = '255.255.255.224';

      $net = guifi_get_subnet_by_nid($edit[nid],$mask1,$edit[newip][$interface_id],$ips_allocated);
      $ip1 = guifi_ip_op($net);
      guifi_merge_ip(array('ipv4'=>$ip1,'netmask'=>$mask1),$ips_allocated,true);
      $newip['new']=true;
      $newip[ipv4]=$ip1;
      $newip[netmask]=$mask1;
      $newip[interface_id]=$interface_id;
      $edit[interfaces][$interface_id][ipv4][]=$newip;
      end($edit[interfaces][$interface_id][ipv4]);
      $ipv4_id = key($edit[interfaces][$interface_id][ipv4]);
    }
    $item = _ipcalc($ip1,$mask1); 
    $ip2  = guifi_next_ip($ip1,$mask1,$ips_allocated);
    if ($ip2 == null) {
      // Net is full, raised error, exit
      unset($edit[edit_details]);
      return;
    }
    $mask2 = $mask1;
    guifi_merge_ip(array('ipv4'=>$ip2,'netmask'=>$mask2),$ips_allocated,true);
    $newlk['interface']['new']=true;
    $newlk['interface'][interface_type]=$edit[interfaces][$interface_id][interface_type];
    $newlk['interface'][ipv4]=array();
    $newlk['interface'][ipv4]['new']=true;
    $newlk['interface'][ipv4][ipv4]=$ip2;
    $newlk['interface'][ipv4][netmask]=$mask2;
    $edit[interfaces][$interface_id][ipv4][$ipv4_id][links][]=$newlk; 
    end($edit[interfaces][$interface_id][ipv4][$ipv4_id][links]);
    $newlink_id=key($edit[interfaces][$interface_id][ipv4][$ipv4_id][links]);
    $edit[edit_details]='interface,'.implode(',',array($interface_id,$ipv4_id,$newlink_id));

    break;
  }
//  print_r($edit);
  return;
}

?>
