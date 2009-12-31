<?php

function guifi_links_form($link,$ipv4,$tree,$multilink) {
  $lweight = 0;

  // edit link details
  guifi_log(GUIFILOG_TRACE,'guifi_links_form()',$link);

  $ki = $tree[count($tree)-3];
  $ka = $tree[count($tree)-1];

  if (count($tree)>4)
    $rk = $tree[1];
  else
    $rk = null;

  // creating hidden form elements for non-edited fields
  if ($link['new'])
    $link['id']= -1;

  // link hidden vars
  $f['storage'] = guifi_form_hidden_var(
    $link,
    array('id','nid','device_id','interface_id','link_type'),
    array_merge($tree,array('links',$link['id']))
  );

  // remote interface hidden vars
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

       // linked node-device
  if ($link['type'] != 'cable')
    $descr =  guifi_get_ap_ssid($link['device_id'],$link['radiodev_counter']);
  else
    $descr = guifi_get_interface_descr($link['interface_id']);


  $f['l'] = array(
    '#type' => 'fieldset',
    '#title'=>  guifi_get_nodename($link['nid']).'/'.
      guifi_get_hostname($link['device_id']),
    '#collapsible' => TRUE,
    '#collapsed' => !isset($link['unfold']),
  );
  if ($link['deleted'])
    $f['l']['#description'] = guifi_device_item_delete_msg('<b>Link deleted</b>.');

  $f['l']['beginTable'] = array('#value'=>'<table style="width: 0">');

  if (user_access('administer guifi networks')) {
    if (!$multilink)
    $f['l']['ipv4'] = array(
      '#type'=> 'textfield',
      '#parents'=>array_merge($tree,array('ipv4')),
      '#size'=> 16,
      '#maxlength'=>16,
      '#default_value'=>$ipv4['ipv4'],
      '#title'=>t('Local IPv4'),
      '#element_validate' => array('guifi_validate_ip'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
    );
    $f['l']['ipv4_remote'] = array(
      '#type'=> 'textfield',
      '#parents'=>array_merge(
        $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#size'=> 16,
      '#maxlength'=>16,
      '#default_value'=>$link['interface']['ipv4']['ipv4'],
      '#title'=>t('Remote IPv4'),
      '#element_validate' => array(
        'guifi_validate_ip',
        'guifi_links_validate_subnet'),
      '#prefix'=> '<td>',
      '#suffix'=> '</td>',
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
      );
   } else {
     if (!$multilink) {
       $f['l']['ipv4'] = array(
         '#type'=>'value',
         '#parents'=>array_merge($tree,array('ipv4')),
         '#value'=>$ipv4['ipv4']);
       $f['l']['netmask'] = array(
         '#type'=>'value',
         '#parents'=>array_merge($tree,array('netmask')),
         '#value'=>$ipv4['netmask']);
     }

    $f['l']['ipv4_remote'] = array(
      '#type'=>'value',
      '#parents'=>array_merge(
        $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#value'=>$link['interface']['ipv4']['ipv4']);

    $f['l']['ipv4_remote_display'] = array(
      '#type' =>         'item',
      '#parents'=>       array_merge(
         $tree,array('links',$link['id'],'interface','ipv4','ipv4')),
      '#title'=>         t('Remote IPv4'),
      '#value'=>         $link['interface']['ipv4']['ipv4'],
      '#description' =>  $link['interface']['ipv4']['netmask'],
      '#prefix'=>        '<td>',
      '#suffix'=>        '</td>',
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
  );

  // remote interface (cable links)
  if ($link['link_type']=='cable') {
    $f['l']['remote_interface_type'] =array(
      '#type' =>          'textfield',
      '#parents'=>        array_merge(
                            $tree,
                            array('links',
                              $link['id'],
                              'interface',
                              'interface_type'
                            )
                          ),
      '#title' =>         t("Remote interface"),
      '#default_value' => $link['interface']['interface_type'],
//      '#options' =>       guifi_get_possible_interfaces($remote_did),
      '#size'=>           10,
      '#maxzise'=>        60,
      '#prefix'=>         '<td>',
      '#suffix'=>         '</td>',
    );
  }

  // delete link button
  if ($link['deleted'])
    $f['deleted_link'] = array(
      '#type'=> 'hidden',
      '#parents'=> array_merge($tree,array('deleted_link')),
      '#value'=> true,
    );
  else
    $f['l']['delete_link'] = array(
      '#type'=>'image_button',
      '#src'=>drupal_get_path('module', 'guifi').'/icons/drop.png',
      '#parents'=>array_merge($tree,array(
        'delete_link',
        $link['id'],
        $link['nid'],
        $link['device_id']
      )),
      '#attributes'=>array(
        'title'=>t('Delete link with').': '.
            guifi_get_interface_descr($link['interface_id'])
        ),
      '#executes_submit_callback'=>true,
      '#submit' => array('guifi_links_delete_submit'),
      '#prefix'=> '<td>',
     );
  $f['l']['endTable'] = array(
    '#value'=> '</td></tr></table>'
  );

  return $f;
}

function guifi_links_delete_submit(&$form,&$form_state) {
  $values = $form_state['clicked_button']['#parents'];

  $remote_did = array_pop($values);
  $remote_nid = array_pop($values);
  $link_id = array_pop($values);
  $dummy =  array_pop($values);
  $ipv4_id = array_pop($values);
  $dummy =  array_pop($values);
  $interface_id = array_pop($values);
  $dummy =  array_pop($values);

  if ($values['0']=='radios') {
    $radio_id = array_pop($values);
    $fbase = &$form_state['values']['radios'][$radio_id];
    $fbase['unfold'] = true;
  } else
    $fbase = &$form_state['values'];

  guifi_log(GUIFILOG_TRACE,
    sprintf('function guifi_radio_interface_link_delete_submit(radio: %d-%s, interface: %d, ipv4: %d, lid: %d, rnid: %d rdid: %d)',
      $radio_id,
      $form_state['values']['radios'][$radio_id]['mode'],
      $interface_id,
      $ipv4_id,$link_id,$remote_nid,$remote_did),
    $values);

  $fbase['interfaces'][$interface_id]['unfold'] = true;
  $fipv4 = &$fbase['interfaces'][$interface_id]['ipv4'][$ipv4_id];
  $fipv4['unfold'] = true;

  $flink = &$fipv4['links'][$link_id];
  $flink['unfold'] = true;
  $flink['deleted'] = true;

  $flink['ipv4']['unfold'] = true;

  // if P2P link or AP/Client link and radio is the client
  // delete also the local IP
  if (
       ($flink['ipv4']['netmask'] == '255.255.255.252') or
       ($form_state['values']['radios'][$radio_id]['mode']=='client')
     ) {
    $fipv4['deleted'] = true;
  }

  $form_state['rebuild'] = true;

  drupal_set_message(t('%type link with %node/%device deleted.',
    array(
      '%type' => $fbase['interfaces'][$interface_id]['interface_type'],
      '%node' =>   guifi_get_nodename($remote_nid),
      '%device' => guifi_get_hostname($remote_did)
    )
  ));

  return true;
}

function guifi_links_validate_subnet($remoteIp,&$form_state) {
  if ($form_state['clicked_button']['#value'] == t('Reset'))
    return;

  $keys         = count($remoteIp['#parents']);
  $radio_id     = $remoteIp['#parents'][$keys - 10];
  $interface_id = $remoteIp['#parents'][$keys - 8];
  $ipv4_id      = $remoteIp['#parents'][$keys - 6];
  $link_id      = $remoteIp['#parents'][$keys - 4];

  if ($keys == 11)
    $ipv4 = &$form_state['values']['radios'][$radio_id]
                                  ['interfaces'][$interface_id]
                                  ['ipv4'][$ipv4_id];
  else
    $ipv4 = &$form_state['values']['interfaces'][$interface_id]
                                  ['ipv4'][$ipv4_id];

  if ($ipv4['links'][$link_id]['deleted'])
    return;

  $item1 = _ipcalc($ipv4['ipv4'],$ipv4['netmask']);
  $item2 = _ipcalc($remoteIp['#value'],$ipv4['netmask']);
  if (($item1[netstart] != $item2[netstart]) or ($item1[netend] != $item2[netend])) {
    form_error($remoteIp,
      t('Error in linked ipv4 addresses (%addr1/%mask - %addr2), not at same subnet.',
          array(
            '%addr1'=>$ipv4['ipv4'],
            '%addr2'=>$remoteIp['#value'],
            '%mask'=>$ipv4['netmask']
          )
        ),
        'error');
  }


  return;
//  $longIp = ip2long($ip['#value']);
//
//  if (($longIp==false) or (count(explode('.',$ip['#value']))!=4))
//    form_error($ip,
//      t('Error in ipv4 address (%addr), use "10.138.0.1" format.',
//        array('%addr'=>$ip['#value'])),'error');
//  else
//    $ip['#value'] = long2ip($longIp);
//
//  return $ip;
}

?>
