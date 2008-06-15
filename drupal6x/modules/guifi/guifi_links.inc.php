<?php

function guifi_device_link_form($link,$ipv4,$tree,$multilink) {
  $lweight = 0;

  // edit link details
  guifi_log(GUIFILOG_TRACE,'guifi_device_link_form()',$link);

  $ki = $tree[count($tree)-3];
  $ka = $tree[count($tree)-1];
  if (count($tree)>4)
    $rk = $tree[1];
  else
    $rk = null;

  // creating hidden form elements for non-edited fields
  if ($link['new'])
    $link['id']= -1;
    
  $f['storage'] = guifi_form_hidden_var(
    $link,
    array('id','nid','device_id','interface_id','link_type'),
    array_merge($tree,array('links',$link['id']))
  );
  
  $f['interface'] = guifi_form_hidden_var(
    $link['interface'],
    array('id','interface_type','radiodev_counter'),
    array_merge($tree,array('links',$link['id'],'interface'))
  );
  
  $f['remote_ipv4'] = guifi_form_hidden_var(
    $link['interface']['ipv4'],
    array('id','interface_id','netmask'),
    array_merge($tree,array('links',$link['id'],'interface','ipv4'))
  );
    
  
//  $f['interface']['ipv4'] = guifi_form_hidden_var(
//    $link['interface']['ipv4'],
//    array('id','interface_id','netmask'),
//    array_merge($tree,array('links',$link['id'],'interface','ipv4'))
//  );  
  
  if ($multilink)
    $prefix='<table><td>';
  else
    $prefix='<td>';
      
       // linked node-device
  if ($link['type'] != 'cable')
    $descr =  guifi_get_ap_ssid($link['device_id'],$link['radiodev_counter']);
  else
    $descr = guifi_get_interface_descr($link['interface_id']);
  
  
  $f['l'] = array(
    '#type' => 'fieldset',
//    '#title' => sprintf('node# %d: ',$link['nid'])
//                .guifi_get_nodename($link['nid']),
    '#title'=>  guifi_get_nodename($link['nid']).'/'.
      guifi_get_hostname($link['device_id']),
//    '#description'=>guifi_get_interface_descr($link['interface_id']),
//    '#weight' => $lweight++,
    '#collapsible' => TRUE,
//    '#tree' => FALSE,
    '#collapsed' => TRUE,
//    '#prefix'=> '<table><tr><td>',
//    '#suffix'=> '</td>',
//    '#suffix'=> '<table><tr>',
  ); 
  /*
  $f['link_name'] = array(
    '#type' => 'item',
    '#parents'=>array_merge($tree,array('links',0,'link_name')),
    '#title' => guifi_get_nodename($link['nid']),
    '#value'=>  guifi_get_hostname($link['device_id']),
    '#description'=>guifi_get_interface_descr($link['interface_id']),
    '#prefix'=> '<table><tr><td>',
    '#suffix'=> '</td>',
    '#weight' => $lweight++,
  );
  */
  
        
  if (user_access('administer guifi networks')) {
    if (!$multilink)
    $f['l']['ipv4'] = array(
      '#type'=> 'textfield',
      '#parents'=>array_merge($tree,array('ipv4')),
      '#size'=> 16,
      '#maxlength'=>16,
      '#default_value'=>$ipv4['ipv4'],
      '#title'=>t('Local IPv4'),
      '#prefix'=> '<table><tr><td>',
//      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
      '#weight'=> $lweight++,
    );
    $f['l']['ipv4_remote'] = array(
      '#type'=> 'textfield',
      '#parents'=>array_merge(
        $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#size'=> 16,
      '#maxlength'=>16,
      '#default_value'=>$link['interface']['ipv4']['ipv4'],
      '#title'=>t('Remote IPv4'),
      '#prefix'=> $prefix,
      '#suffix'=> '</td>',
      '#weight'=> $lweight++,
    );
    if (!$multilink)
      $f['l']['netmask'] = array(
        '#type' => 'select',
        '#parents'=>array_merge($tree,array('netmask')),
        '#title' => t("Network mask"),
        '#default_value' => $ipv4['netmask'],
        '#options' => guifi_types('netmask',30,0),
        '#prefix'=> '<td>',
        '#suffix'=> '</td>',
        '#weight' =>  $lweight++,
      );
   } else {
    $f['l']['ipv4_remote'] = array(
      '#type' =>         'item',
      '#parents'=>       array_merge(
         $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#title'=>         t('Remote IPv4'),
      '#value'=>         $link['interface']['ipv4']['ipv4'],
      '#description' =>  $link['interface']['ipv4']['netmask'],
      '#prefix'=>        '<td>',
      '#suffix'=>        '</td>',
      '#weight' =>       $lweight++,
    );
  } // if network administrator
        
  // Routing
  $f['l']['routing'] = array(
    '#type' =>          'select',
    '#parents'=>        array_merge($tree,array('links',$link['id'],'routing')),
    '#title' =>         t("Routing"),
    '#default_value' => $link['routing'],
    '#options' =>       guifi_types('routing'),
    '#prefix'=>         '<td>',
    '#suffix'=>         '</td>',
    '#weight' =>        $lweight++,
  );
  // Status
  $f['l']['status'] = array(
    '#type' =>          'select',
    '#parents'=>        array_merge($tree,array('links',$link['id'],'flag')),
    '#title' =>         t("Status"),
    '#default_value' => $link['flag'],
    '#options' =>       guifi_types('status'),
    '#prefix'=>         '<td>',
    '#suffix'=>         '</td>',
    '#weight' =>        $lweight++,
  );
  // delete link button
  $f['l']['delete_link'] = array(
    '#type'=>'image_button',
    '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
    '#parents'=>array_merge($tree,array(
      'delete_link',
      $rk,$ki,$ka,
      $link['id'],
      $link['nid'],
      $link['device_id']
    )),
    '#attributes'=>array(
      'title'=>t('Delete %name link',
        array('%name'=>
          guifi_get_interface_descr($link['interface_id'])
        )
      )
    ), 
    '#submit' => array('guifi_radio_interface_link_delete_submit'),
    '#prefix'=> '<td>',
    '#suffix'=> '</td></tr></table>',
    '#weight'=>$lweight++);
/*  
  $f['l']['delete_link'] = array(
    '#type' => 'button',
    '#parents'=>array_merge($tree,array('delete_link')),
    '#value'=>t('Delete'),
    '#name'=>implode(',',array(
       '_action',
       '_guifi_delete_radio_interface_link',
       $rk,$ki,$ka,$link['id'],
       $link['nid'],
       $link['device_id'])),
    '#prefix'=> '<td>',
    '#suffix'=> '</td></tr></table>',
    '#weight' =>  $lweight++,
  );
  */
  
  return $f;
}

function guifi_link_form($link,$ipv4,$tree,$multilink) {
  $lweight = 0;

  // edit link details
  guifi_log(GUIFILOG_BASIC,'guifi_links_form()',$link);

  $ki = $tree[count($tree)-3];
  $ka = $tree[count($tree)-1];
  if (count($tree)>4)
    $rk = $tree[1];
  else
    $rk = null;

  // creating hidden form elements for non-edited fields
  if ($link['new'])
    $link['id']= -1;
    
  $f = guifi_form_hidden_var(
    $link,
    array('id','nid','device_id','interface_id','link_type'),
    array_merge($tree,array('links',$link['id']))
  );
  
  $f['interface'] = guifi_form_hidden_var(
    $link['interface'],
    array('id','interface_type','radiodev_counter'),
    array_merge($tree,array('links',$link['id'],'interface'))
  );  
  
  $f['remote_ipv4'] = guifi_form_hidden_var(
    $link['interface']['ipv4'],
    array('interface_id','netmask'),
    array_merge($tree,array('links',$link['id'],'interface','ipv4'))
  );  
  
  if ($multilink)
    $prefix='<table><td>';
  else
      $prefix='<td>';
      
       // linked node-device
  if ($link['type'] != 'cable')
    $descr =  guifi_get_ap_ssid($link['device_id'],$link['radiodev_counter']);
  else
    $descr = guifi_get_interface_descr($link['interface_id']);
  
  /*  
  $f['l'] = array(
    '#type' => 'fieldset',
    '#title' => guifi_get_nodename($link['nid']),
    '#value'=>  guifi_get_hostname($link['device_id']),
    '#description'=>guifi_get_interface_descr($link['interface_id']),
    '#weight' => $lweight++,
    '#collapsible' => TRUE,
    '#tree' => FALSE,
    '#collapsed' => false,
    '#prefix'=> '<table><tr><td>',
    '#suffix'=> '</td>',
  ); 
  */
  $f['link_name'] = array(
    '#type' => 'item',
    '#parents'=>array_merge($tree,array('links',0,'link_name')),
    '#title' => guifi_get_nodename($link['nid']),
    '#value'=>  guifi_get_hostname($link['device_id']),
    '#description'=>guifi_get_interface_descr($link['interface_id']),
    '#prefix'=> '<table><tr><td>',
    '#suffix'=> '</td>',
    '#weight' => $lweight++,
  );
  
        
  if (user_access('administer guifi networks')) {
    if (!$multilink)
    $f['l']['ipv4'] = array(
      '#type'=> 'textfield',
      '#parents'=>array_merge($tree,array('ipv4')),
      '#size'=> 16,
      '#maxlength'=>16,
      '#default_value'=>$ipv4['ipv4'],
      '#title'=>t('Local IPv4'),
      '#prefix'=> $prefix,
//      '#element_validate' => array(
//         'guifi_ipv4_validate',
//         'guifi_link_ipv4_validate'),      
      '#suffix'=> '</td>',
      '#weight'=> $lweight++,
    );
    $f['l']['ipv4_remote'] = array(
      '#type'=> 'textfield',
      '#parents'=>array_merge(
        $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#size'=> 16,
      '#maxlength'=>16,
      '#default_value'=>$link['interface']['ipv4']['ipv4'],
      '#title'=>t('Remote IPv4'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
      '#weight'=> $lweight++,
    );
    if (!$multilink)
      $f['l']['netmask'] = array(
        '#type' => 'select',
        '#parents'=>array_merge($tree,array('netmask')),
        '#title' => t("Network mask"),
        '#default_value' => $ipv4['netmask'],
        '#options' => guifi_types('netmask',30,0),
        '#prefix'=> '<td>',
        '#suffix'=> '</td>',
        '#weight' =>  $lweight++,
      );
   } else {
    $f['l']['ipv4_remote'] = array(
      '#type' =>         'item',
      '#parents'=>       array_merge(
         $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#title'=>         t('Remote IPv4'),
      '#value'=>         $link['interface']['ipv4']['ipv4'],
      '#description' =>  $link['interface']['ipv4']['netmask'],
      '#prefix'=>        '<td>',
      '#suffix'=>        '</td>',
      '#weight' =>       $lweight++,
    );
  } // if network administrator
        
  // Routing
  $f['l']['routing'] = array(
    '#type' =>          'select',
    '#parents'=>        array_merge($tree,array('links',$link['id'],'routing')),
    '#title' =>         t("Routing"),
    '#default_value' => $link['routing'],
    '#options' =>       guifi_types('routing'),
    '#prefix'=>         '<td>',
    '#suffix'=>         '</td>',
    '#weight' =>        $lweight++,
  );
  // Status
  $f['l']['status'] = array(
    '#type' =>          'select',
    '#parents'=>        array_merge($tree,array('links',$link['id'],'flag')),
    '#title' =>         t("Status"),
    '#default_value' => $link['flag'],
    '#options' =>       guifi_types('status'),
    '#prefix'=>         '<td>',
    '#suffix'=>         '</td>',
    '#weight' =>        $lweight++,
  );
  // delete link button
  $f['l']['delete_link'] = array(
    '#type'=>'image_button',
    '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
    '#parents'=>array_merge($tree,array(
      'delete_link',
      $rk,$ki,$ka,
      $link['id'],
      $link['nid'],
      $link['device_id']
    )),
    '#attributes'=>array(
      'title'=>t('Delete %name link',
        array('%name'=>
          guifi_get_interface_descr($link['interface_id'])
        )
      )
    ), 
    '#submit' => array('guifi_radio_interface_link_delete_submit'),
    '#prefix'=> '<td>',
    '#suffix'=> '</td></tr></table>',
    '#weight'=>$lweight++);
/*  
  $f['l']['delete_link'] = array(
    '#type' => 'button',
    '#parents'=>array_merge($tree,array('delete_link')),
    '#value'=>t('Delete'),
    '#name'=>implode(',',array(
       '_action',
       '_guifi_delete_radio_interface_link',
       $rk,$ki,$ka,$link['id'],
       $link['nid'],
       $link['device_id'])),
    '#prefix'=> '<td>',
    '#suffix'=> '</td></tr></table>',
    '#weight' =>  $lweight++,
  );
  */
  
  return $f;
}

function guifi_links_validate(&$edit,&$form) {

  guifi_log(GUIFILOG_TRACE,"function: guifi_links_validate()");
  guifi_log(GUIFILOG_FULL,"edit",$form);

  // Validating radio links
  if (isset($edit['radios']))
    foreach ($edit['radios'] as $radio_id=>$radio)
    if (isset($radio['interfaces']))
     foreach ($radio['interfaces'] as $interface_id=>$interface)
      if (isset($interface['ipv4']))
        foreach ($interface['ipv4'] as $ipv4_id=>$ipv4)
          if (isset($ipv4['links']))
            foreach ($ipv4['links'] as $link_id=>$link) {
//              print "Link: ".$key."\n<br />"; print_r($link); print "\n<br />";
              guifi_links_validate_recurse(
                $edit['radios'][$radio_id]['interfaces'][$interface_id]['ipv4'][$ipv4_id],
                $form['radios'][$radio_id]['interfaces'][$interface_id]['ipv4'][$ipv4_id],
                $interface['interface_type'],
                array(
                  $radio_id,
                  $interface_id,
                  $ipv4_id,
                  $link_id));
  }
  
  // Validating cable/other links
  if (isset($edit['interfaces']))
    foreach ($edit['interfaces'] as $interface_id=>$interface)
      if (isset($interface['ipv4']))
        foreach ($interface['ipv4'] as $ipv4_id=>$ipv4)
          if (isset($ipv4['links']))
            foreach ($ipv4['links'] as $link_id=>$link) {
              guifi_links_validate_recurse(
                $edit['interfaces'][$interface_id]['ipv4'][$ipv4_id],
                $form['interfaces'][$interface_id]['ipv4'][$ipv4_id],
                $interface['interface_type'],
                array(
                  null,
                  $interface_id,
                  $ipv4_id,
                  $link_id));
  }
}

function guifi_links_validate_recurse(&$link,&$form,$interface_type,$parents = array()) {

    return;
    list($radio_id,$interface_id,$ipv4_id,$link_id) = $parents;
     drupal_set_message('Radio id: '.$radio_id);
    if (is_null($radio_id))
      $str_err = 'interfaces]['.$interface_id.'][ipv4][';
    else
      $str_err = 'radios]['.$radio_id.'][interfaces]['.$interface_id.'][ipv4][';


      
//     print "Link id: $link_id Interface_type: $interface_type $id_field\n<br />";

    if ($link[links][$link_id]['new']==true) 
    if (isset($link[links][$link_id]['linked'])) {

      // passat a guifi_device_save
//      print "New Linked: \n<br />"; print_r($link);
//      print "\n<br />";
      list($nid,$device_id,$radiodev_counter) = explode(',',$link[links][$link_id][linked]);

//       form_set_value(array('#parents'=>array_merge($parents,array('nid'))));
      $link[links][$link_id][nid]=$nid;
      $link[links][$link_id][device_id]=$device_id;
      if ($link[links][$link_id][link_type] == 'wds') {
        $ipIDs = array();
        // WDS reuse the existing interface, but always create a new IP over it, so we need to get a free IP id for it
        $qryIDs = db_query('SELECT a.id FROM {guifi_ipv4} a, {guifi_interfaces} i WHERE a.interface_id=i.id AND i.interface_type="wds/p2p" AND i.device_id=%d AND i.radiodev_counter=%d',$device_id,$radiodev_counter);
        while ($id = db_fetch_array($qryIDs))
          $ipIDs[] = $id['id'];
        $nextID = 0;
//        print "\n<br />";
//        print_r($ipIDs);
//        print "\n<br />";
        while (in_array($nextID,$ipIDs))
          $nextID = $nextID + 1;
        $ipv4_id=$nextID;
      } else {
        $ipv4_id=$radiodev_counter;
      }
//      print "IPV4_id: " .$ipv4_id;
//      print "\n<br />";
      $link[links][$link_id][ipv4_id]=$ipv4_id;
      $link[links][$link_id][ipv4][id]=$ipv4_id;
      // end passat a guifi_device_save

      $link[links][$link_id]['interface'][device_id]=$device_id;
      $link[links][$link_id]['interface'][radiodev_counter]=$radiodev_counter;
      if ($link[links][$link_id][link_type] == 'ap/client') {
        // if WAN, assign local and remote ips
        if ($interface_type == 'Wan') {
          $ips_allocated = guifi_get_ips('0.0.0.0','0.0.0.0',$edit);
          $qAP = db_query('SELECT i.id, i.mac, a.ipv4, a.netmask FROM {guifi_interfaces} i, {guifi_ipv4} a WHERE i.device_id = %d AND i.interface_type in ("wLan/Lan","wLan") AND i.radiodev_counter=%d AND a.interface_id=i.id',$device_id, $radiodev_counter);
          while ($ipAP = db_fetch_array($qAP)) {
            $link[links][$link_id]['interface'][ipv4][ipv4]=$ipAP[ipv4];
            $link[links][$link_id]['interface'][ipv4][netmask]=$ipAP[netmask];
            $item = _ipcalc($ipAP[ipv4],$ipAP[netmask]);
            $link[ipv4] = guifi_next_ip($item['netid'],$ipAP[netmask],$ips_allocated);
            if ($link[ipv4] != null)
              break;
            drupal_set_message(t('Network was full, looking for more networks available...'));
          }

          // if network was full, delete link
          if ($link[ipv4] == null) {                print_r($form);
            drupal_set_message(t('No networks where available for this node, link was not created, contact your network administrator.'),'error');
            unset($link[links][$link_id]);
            return;
          }

          drupal_set_message(t('Got IP address %net/%mask. Link created.',array('%net' => theme('placeholder', $link[ipv4]), '%mask' => theme('placeholder', $ipAP[netmask])  )));

          $link[links][$link_id]['interface_id']=$ipAP['id'];
          $link[links][$link_id]['interface'][id]=$ipAP['id'];
          $link[links][$link_id]['interface'][mac]=$ipAP[mac];
          $link[links][$link_id]['interface'][ipv4][interface_id]=$ipAP['id'];
          $link['new'] = true;
          $link[netmask] = $ipAP[netmask];
        }
      }
    } // if link is new, get linked device information

    // validating same netmask
//    print_r($link);
    if (($link[ipv4] != '' ) and ($link[links][$link_id]['interface'][ipv4][ipv4] != '') and (!$link[links][$link_id][deleted])) {
      $item1 = _ipcalc($link['ipv4'],$link['netmask']);
      $item2 = _ipcalc($link['links'][$link_id]['interface']['ipv4']['ipv4'],$link['netmask']);
      if (($item1[netstart] != $item2[netstart]) or ($item1[netend] != $item2[netend])) {
        if (($link[links][$link_id][link_type] == 'ap/client') or
           ($link[links][$link_id][link_type] == 'wds')) {
//               print_r($form['links'][$link_id]['interface']['ipv4']['ipv4']);
//               drupal_set_message(t('Link ip addresses are not in the same subnet: '.$str_err));
              form_set_error($str_err.$ipv4_id.'][links]['.$link_id.'][interface][ipv4][ipv4',
                t('%ip1/%mask1 in %type link is not at the same subnet as %ip2/%mask2',
                  array('%ip1'=>$link['ipv4'],
                    '%mask1'=>$link['netmask'],
                    '%type'=>$interface_type,
                    '%ip2'=>$link['links'][$link_id]['interface']['ipv4']['ipv4'],
                    '%mask2'=>$link['links'][$link_id]['interface']['ipv4']['netmask']))
                );
              form_set_error($str_err.$ipv4_id.'][ipv4',
                t('%ip1/%mask1 in %type link is not at the same subnet as %ip2/%mask2',
                  array('%ip2'=>$link['ipv4'],
                    '%mask2'=>$link['netmask'],
                    '%type'=>$interface_type,
                    '%ip1'=>$link['links'][$link_id]['interface']['ipv4']['ipv4'],
                    '%mask1'=>$link['links'][$link_id]['interface']['ipv4']['netmask']))
                );
//                 $link[ipv4].'/'.$link[netmask].'-'.$link[links][$link_id]['interface'][ipv4][ipv4].'/'.$link[links][$link_id]['interface'][ipv4][netmask].': '.t('Link ip addresses are not in the same subnet'));
//               form_set_error($str_err.$ipv4_id.'][ipv4',
//                  $link[ipv4].'/'.$link[netmask].'-'.$link[links][$link_id]['interface'][ipv4][ipv4].'/'.$link[links][$link_id]['interface'][ipv4][netmask].': '.t('Link ip addresses are not in the same subnet'));
//           form_set_error(
//              array('#parents'=>array_merge($parents,array('netmask'))),
//              $link[ipv4].'/'.$link[netmask].'-'.$link[links][$link_id]['interface'][ipv4][ipv4].'/'.$link[links][$link_id]['interface'][ipv4][netmask].': '.t('Link ip addresses are not in the same subnet'));
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


  if (isset($edit['links'])) foreach ($edit['links'] as $key => $link) {

    guifi_log(GUIFILOG_TRACE, "Checking link", $link);
    if (($link['newlink']) or ($link['flag'] == 'Dropped')) {
      break;
    }

//    print "\n<br />Checking link: "; print_r($link);

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
      $output .= '<br />'.form_button(t('Confirm delete'),'op').
                        form_button(t('Back to list'),'op');
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
      $output .= "\n<br />".t('<strong>Warning:</strong> If you confirm at this point, this operation will delete information from the database and save any other change.');
      $output .= '<br />'.form_button(t('Undo changes'),'op').form_button(t('Save & continue edit'),'op');
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

//  print "New Link ".$type." Interface: ".$interface_ipv4_id."\n<br />";
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
//  print "Ips allocated: ".count($ips_allocated)."\n<br />";

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
    $newlk['routing'] = 'BGP';
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
    $newlk['routing'] = 'Gateway';
    // if in mode AP
    if ($edit[radios][$radio_id][mode] == 'ap') {
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
    break;
  case 'cable':
//    print $edit[newip][$interface_id];

    if (!isset($edit[newip][$interface_id])) 
      $edit[newip][$interface_id] == 'backbone';
    if (isset($ipv4_id))  {
      $ip1 = $edit[interfaces][$interface_id][ipv4][$ipv4_id][ipv4];
      $mask1 = $edit[interfaces][$interface_id][ipv4][$ipv4_id][netmask];
    } else {
      if ($edit[newip][$interface_id] == 'backbone') {
        $newlk['routing'] = 'BGP';
        $mask1 = '255.255.255.252';
      } else {
        $newlk['routing'] = 'n/a';
        $mask1 = '255.255.255.224';
      }

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
