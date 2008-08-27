<?php
/*
 * Created on 16/08/2008 by rroca
 *
 * functions for various tools
 */

function guifi_tools_ip_search($ipv4 = null) {

  $output = drupal_get_form('guifi_tools_ip_search_form',$ipv4);

  if (is_null($ipv4))
    return $output;

  $output .= '<h2>'.t('Query result for "ipv4 LIKE %ipv4"',
    array('%ipv4'=>"'".$ipv4."'")).'</h2>';

  $headers = array(t('id'),
    array('data'=>t('nipv4')),
    t('mask'),t('interface'),t('device'),t('node'));
  $sql = 'SELECT *,inet_aton(ipv4) AS nipv4 FROM {guifi_ipv4} WHERE ipv4 LIKE "'.$ipv4.'" ORDER BY inet_aton(ipv4)';
//  $sql .= tablesort_sql($header);
  $sqla = pager_query($sql,50);
  while ($ipv4 = db_fetch_object($sqla)) {
    $row = array();
    $row[] = $ipv4->id.'/'.$ipv4->interface_id;
    $row[] = $ipv4->ipv4;
    $row[] = $ipv4->netmask;

    // interface
    if ($interface = db_fetch_object(db_query(
         'SELECT * from {guifi_interfaces} WHERE id=%d',
         $ipv4->interface_id))) {
      $row[] = $interface->id.'/'.$interface->radiodev_counter.' '.
        $interface->interface_type;
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    // device
    if ($device = db_fetch_object(db_query(
         'SELECT * from {guifi_devices} WHERE id=%d',
         $interface->device_id))) {
      $row[] = $device->id.'-'.
        l($device->nick,'guifi/device/'.$device->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    // node
    if ($node = db_fetch_object(db_query(
         'SELECT id from {guifi_location} WHERE id=%d',
         $device->nid))) {
      $node = node_load(array('nid'=>$node->id));
      $row[] = $node->id.'-'.
        l($node->title,'node/'.$node->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    $rows[] = $row;
  }

  $output .= theme('table',$headers,$rows);
  $output .= theme_pager(null, 50);
  return $output;
}

// IP search
function guifi_tools_ip_search_form($form_state, $params = array()) {

  $form['ipv4'] = array(
    '#type' => 'textfield',
    '#title' => t('Network IPv4 address'),
    '#required' => true,
    '#default_value' => $params,
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t('Enter a valid ipv4 network address or pattern ' .
        'to get the related information available at the database for it.<br>' .
        'You can use valid SQL wilcards (%), for example, to query all the ' .
        'addresses begining with "10.138.0" you can use "10.138.0%"...'),
    '#weight' => 0,
  );
  $form['submit'] = array('#type' => 'submit','#value'=>t('Get information'));

  return $form;
}

function guifi_tools_ip_search_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/ip/ipsearch/'.urlencode($form_state['values']['ipv4']));
   return;
}

// MAC Search
function guifi_tools_mac_search($mac = null) {
  $output = drupal_get_form('guifi_tools_mac_search_form',$mac);

  if (is_null($mac))
    return $output;

  $output .= '<h2>'.t('Query result for "ipv4 LIKE %ipv4"',
    array('%ipv4'=>"'".$mac."'")).'</h2>';

  $headers = array(t('mac'),t('interface'),t('device'),t('node'));
  $sqlm = pager_query('SELECT * FROM {guifi_interfaces} WHERE mac LIKE "'.$mac.'"',50);
  while ($interface = db_fetch_object($sqlm)) {
    $row = array();
    $row[] = $interface->mac;
    $row[] = $interface->id.'/'.$interface->radiodev_counter.' '.
      $interface->interface_type;

    // device
    if ($device = db_fetch_object(db_query(
         'SELECT * from {guifi_devices} WHERE id=%d',
         $interface->device_id))) {
      $row[] = $device->id.'-'.
        l($device->nick,'guifi/device/'.$device->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    // node
    if ($node = db_fetch_object(db_query(
         'SELECT id from {guifi_location} WHERE id=%d',
         $device->nid))) {
      $node = node_load(array('nid'=>$node->id));
      $row[] = $node->id.'-'.
        l($node->title,'node/'.$node->id);
    } else {
      $row[] = t('Orphan');
      $rows[] = $row;
      continue;
    }

    $rows[] = $row;
  }

  $output .= theme('table',$headers,$rows);
  $output .= theme_pager(null, 50);
  return $output;
}

function guifi_tools_mac_search_form($form_state, $params = array()) {

  $form['mac'] = array(
    '#type' => 'textfield',
    '#title' => t('MAC address'),
    '#required' => true,
    '#default_value' => $params,
    '#size' => 20,
    '#maxlength' => 20,
    '#description' => t('Enter a valid MAC address or pattern ' .
        'to get the related information available at the database for it.<br>' .
        'You can use valid SQL wilcards (%), for example, to query all the MAC ' .
        'addresses begining with "00:0B" you can use "00:0B%"...'),
    '#weight' => 0,
  );
  $form['submit'] = array('#type' => 'submit','#value'=>t('Get information'));

  return $form;
}

function guifi_tools_mac_search_form_submit($form, &$form_state) {
   drupal_goto('guifi/menu/ip/macsearch/'.$form_state['values']['mac']);
   return;
}

function guifi_tools_ip_rangesearch($params) {

  $output .=  drupal_get_form('guifi_tools_ip_rangesearch_form',$params);

  if (empty($params))
    return $output;

  // for testing, load a device with quite a few ip's'
  // $device = guifi_device_load(115);

  $tgetipsbegin = microtime(true);

  $ips_allocated = guifi_ipcalc_get_ips('0.0.0.0','0.0.0.0');

  $tgetipsend = microtime(true);

  $toutput = t('Got & sorted %num ips in %secs seconds',
    array('%num'=>number_format(count($ips_allocated)),
          '%secs'=>number_format($tgetipsend-$tgetipsbegin,4))).
    '<br />';

  list($mask,$network_type,$zone_id,$allocate) = explode(',',$params);

  if (!user_access('administer guifi networks'))
    $allocate = 'No';

  $net = guifi_ipcalc_get_subnet_by_nid($zone_id,
            $mask,
            $network_type,
            $ips_allocated,
            $allocate,   // never allocate the obatined range at guifi_networks
            true);   // verbose output

  $tgetsubnetbynid = microtime(true);

  $toutput .= t('Got %base/%net in %secs seconds',
    array('%base'=>$net,
          '%net'=>$mask,
          '%secs'=>number_format($tgetsubnetbynid-$tgetipsend,4))).
    '<br />';
  $toutput .= t('Total elapsed was %secs seconds',
    array('%secs'=>number_format($tgetsubnetbynid-$tgetipsbegin,4))).
    '<br />';

  $item=_ipcalc($net,$mask);
  if ($net) {
    foreach ($item as $k=>$value) {
      $header[] = t($k);
      $row[] = $value;
    }
    $qoutput .= theme('box',
      t('Space found at %net',array('%net'=>$net)),
      theme('table',$header,array($row)));
  } else
    drupal_set_message(t('Was not possible to find %type space for %mask',
      array('%type'=>$network_type,
        '%mask'=>$mask)),
      'error');

  return $qoutput.
         theme('box',t('Find available space for a subnetwork'),$output).
         theme('box',t('Performance'),'<small>'.$toutput.'</small>');
}

// IP search
function guifi_tools_ip_rangesearch_form($form_state, $params = array()) {

  if (empty($params)) {
    $mask = '255.255.255.224';
    $network_type = 'public';
    $zone_id = guifi_zone_root();
  } else
    list($mask,$network_type,$zone_id,$allocate) = explode(',',$params);

  $form['mask'] = array(
    '#type' => 'select',
    '#title' => t("Mask"),
    '#required' => true,
    '#default_value' => $mask,
    '#options' => guifi_types('netmask',30,0),
    '#description' => t('The mask of the network to search for. The number of the available hosts of each masks is displayed in the list box.'),
  );
  $form['network_type'] = array(
    '#type' => 'select',
    '#title' => t("Type"),
    '#required' => true,
    '#default_value' => $network_type,
    '#options' => drupal_map_assoc(array('public','backbone')),
    '#description' => t('The type of network addresses you are looking for. <ul><li><em>public:</em> is for addresses which will allow the users connect to the services, therefore must be unique across all the network and assigned with care for not being wasted.</li><li><em>backbone:</em> internal addresses for network operation, could be shared across distinct network segments, do not neet to be known as a service address to the users</li></ul>'),
  );
  $form['zone_id'] = guifi_zone_select_field($zone_id,'zone_id');
  $form['allocate'] = array(
    '#type' => 'select',
    '#title' => t("Allocate"),
    '#required' => true,
    '#access' => user_access('administer guifi networks'),
    '#default_value' => 'No',
    '#options' => drupal_map_assoc(array('Yes','No')),
    '#description' => t('If yes, the network found will be allocated at the database being assigned to the zone'),
  );

  $form['submit'] = array('#type' => 'submit','#value'=>t('Find space for the subnetwork'));

  return $form;
}

function guifi_tools_ip_rangesearch_form_submit($form, $form_state) {
   drupal_goto('guifi/menu/ip/networksearch/'.
     $form_state['values']['mask'].','.
     $form_state['values']['network_type'].','.
     $form_state['values']['zone_id'].','.
     $form_state['values']['allocate']
   );
   return;
}


// Mail search & massive update
function guifi_tools_mail_search($mail = null) {

  $output = drupal_get_form('guifi_tools_mail_search_form',$mail);

  // if a vaild mail has given, allow massive update
  if ((!empty($mail)) and (valid_email_address($mail)))
    $output .= drupal_get_form('guifi_tools_mail_update_form',$mail);

  // Close the form table
  $output .= '</table></table>';

  if (is_null($mail))
    return $output;

  $output .= '<h2>'.t('Report for notification having LIKE "%mail"',
    array('%mail'=>"'".$mail."'")).'</h2>';

  $headers = array(t('table'),t('notification'),t('title'));

  $tables = array('guifi_zone','guifi_location','guifi_devices','guifi_services','guifi_users');

  foreach ($tables as $table) {
    $sqlm = db_query('SELECT * FROM {%s} WHERE notification LIKE "%s"',$table,$mail);
    while ($amails = db_fetch_object($sqlm)) {
      $row = array();
      $row[] = $table;
      $row[] = $amails->notification;

      // Check that the user has update access and creates the link
      $continue = false;
      if (!user_access('administer guifi networks'))
        switch ($table) {
          case 'guifi_users':
            if (guifi_user_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_devices':
            if (guifi_device_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_zone':
            if (guifi_zone_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_location':
            if (guifi_node_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_service':
            if (guifi_service_access('update',$amails->id))
              $continue = true;
            break;
        } else
        $continue = true;

      if (!$continue)
        continue;

      switch ($table) {
        case 'guifi_users':
          $row[] = l($amails->username,'guifi/user/'.$amails->id.'/edit');
          break;
        case 'guifi_devices':
          $row[] = l($amails->nick,'guifi/device/'.$amails->id.'/edit');
          break;
        default:
          $row[] = l($amails->nick,'node/'.$amails->id.'/edit');
      }

      $rows[] = $row;
    } // foreach row with the email found

  } // foreach table

  if (count($rows))
    $output .= theme('table',$headers,$rows);
  return $output;
}

function guifi_tools_mail_search_form($form_state, $params = array()) {

//  $form['submit'] = array(
//    '#type' => 'submit',
//    '#value'=>t('Search'),
//    '#prefix'=> '<table><tr><td align="right">',
//    '#suffix'=> '</td>',
//  );
  $form['mail'] = array(
    '#type' => 'textfield',
    '#title' => t('e-mail address'),
    '#required' => true,
    '#default_value' => $params,
    '#size' => 50,
    '#maxlength' => 50,
    '#description' => t('Enter a valid e-mail address to look for ' .
        'to get a report of where it appears in all tables.' .
        '<br>' .
        'You can use valid SQL wilcards (%), for example, to query all the mail ' .
        'addresses containing "guifi" you can use "%guifi%"...<br>' .
        'Note that:<ul><li>If you use wildcards, massive update option ' .
        'will not be enabled</li><li>You will get a list restricted to the items ' .
        'which you are granted to update</li></ul>'),
     '#prefix'=> '<table><tr><td>',
     '#suffix'=> '</td>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value'=>t('Search'),
    '#prefix'=> '<td align="left">',
    '#suffix'=> '</td></tr>',
  );
  return $form;
}

function guifi_tools_mail_update_form($form_state, $params = array()) {

  $form['mail_search'] = array(
    '#type'=>'value',
    '#value'=>$params);
//  $form['submit'] = array(
//    '#type' => 'submit',
//    '#value'=>t('Replace with'),
//    '#prefix'=> '<tr><td align="right">',
//    '#suffix'=> '</td>',
//  );
  $form['mail_replacewith'] = array(
    '#type' => 'textfield',
    '#title' => t('New e-mail address'),
    '#required' => false,
    '#default_value' => $params,
    '#size' => 50,
    '#maxlength' => 50,
    '#description' => t('Enter a valid e-mail address to replace %mail for ' .
        'all the rows of the report below.',
        array('%mail'=>$params)),
    '#prefix'=> '<td>',
    '#suffix'=> '</td>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value'=>t('Replace with'),
    '#prefix'=> '<td align="left">',
    '#suffix'=> '</td></tr>',
  );

  return $form;
}

function guifi_tools_mail_update_form_validate($form, &$form_state) {
  if (!valid_email_address($form_state['values']['mail_replacewith']))
    form_set_error('mail_replacewith',
      t('%email is not valid',
        array('%email'=>$form_state['values']['mail_replacewith'])));
  if ($form_state['values']['mail_search'] ==
    $form_state['values']['mail_replacewith'])
    form_set_error('mail_replacewith',
      t('%email is equal to current value',
        array('%email'=>$form_state['values']['mail_replacewith'])));
}

function guifi_tools_mail_search_form_submit($form, &$form_state) {
  drupal_goto('guifi/menu/ip/mailsearch/'.$form_state['values']['mail']);
}


function guifi_tools_mail_update_form_submit($form, &$form_state) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'guifi_tools_mail_update_submit()',
    $form_state['values']);

  // perform the massive update to the granted rows, using guifi db api
  // instead of straight SQL to create the notificaton messages.

  $tables = array('guifi_zone','guifi_location','guifi_devices','guifi_services','guifi_users');

  foreach ($tables as $table) {
    $sqlm = db_query('SELECT * FROM {%s} WHERE notification LIKE "%s"',
      $table,
      $form_state['values']['mail_search']);

    while ($amails = db_fetch_object($sqlm)) {
      // Check that the user has update access and creates the link
      $continue = false;
      if (!user_access('administer guifi networks'))
        switch ($table) {
          case 'guifi_users':
            $title = $amails->username;
            $type = t('User');
            if (guifi_user_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_devices':
            $title = $amails->nick;
            $type = t('Device');
            if (guifi_device_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_zone':
            $title = $amails->nick;
            $type = t('Zone');
            if (guifi_zone_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_location':
            $title = $amails->nick;
            $type = t('Node');
            if (guifi_node_access('update',$amails->id))
              $continue = true;
            break;
          case 'guifi_service':
            $title = $amails->nick;
            $type = t('Service');
            if (guifi_service_access('update',$amails->id))
              $continue = true;
            break;
        } else
        $continue = true;

      if (!$continue)
        continue;

      // here we have update access, so perform the update

      // Notify prevuious mail id, just in case...
      $to_mail = $amails->notification;

      $amails->notification = str_ireplace(
        $form_state['values']['mail_search'],
        strtolower($form_state['values']['mail_replacewith']),
        $amails->notification
        );

      if ($to_mail == $amails->notification) {
        //no changes, so next
        continue;
      }

      $n = _guifi_db_sql(
        $table,
        array('id'=>$amails->id),
        (array)$amails,
        $log,$to_mail);
      guifi_notify(
        $to_mail,
        t('The notification %notify for %type %title has been CHANGED to %new by %user.',
          array('%notify' => $form_state['values']['mail_search'],
            '%new'=>$form_state['values']['mail_replacewith'],
            '%type'=>$type,
            '%title'=>$title,
            '%user' => $user->name)),
            $log);

    } // foreach row with the email found

  } // foreach table

  drupal_goto('guifi/menu/ip/mailsearch/'.$form_state['values']['mail_replacewith']);
}

// Administrative tools
function guifi_admin_notify($view = 'false') {
  if ($view == 'false')
    $send = true;
  else
    $send = false;

  $output = guifi_notify_send($send);
  if ($output == '')
    $output = t('Queue is empty');
  $now = time();
  if ($send) {
    variable_set('guifi_notify_last',$now);
    $output = '<h1>'.t('Notifications sent at %date',
      array('%date'=>format_date($now))).'</h1>'.$output;
  } else {
    $output = '<h1<'.t('Messages to be sent.').'</h1>'.$output;
  }
  return $output;
}

// development tools



?>
