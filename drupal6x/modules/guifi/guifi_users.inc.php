<?php

/**
 * user editing functions
**/

/**
 * Menu callback; handle the adding of a new user.
 */
function guifi_user_add($node) {
  global $user;

  guifi_log(GUIFILOG_TRACE,'function guifi_user_edit()',$node);

  $guser['services']['proxy'] = guifi_zone_get_service($node->zone_id,'proxy_id');
  $guser['nid'] = $node->id;
  $guser['notification']=$node->notification;
  $guser['status']='New';
  $guser['content_filters']=array();

  return drupal_get_form('guifi_user_form',$guser);
}

/**
 * Menu callback; delete a single user.
 */
 function guifi_user_delete($id) {
  $result = db_query(
      'SELECT username, nid ' .
      'FROM {guifi_users} ' .
      'WHERE id = %d',
      $id);
  $guifi = db_fetch_object($result);

  if ($_POST['confirm']) {
    db_query('DELETE FROM {guifi_users} WHERE id = %d', $id);
    drupal_set_message(t('User %username deleted.',array('%username'=>$guifi->username)));
    drupal_goto('node/'.$guifi->nid.'/view/users');
  }
  return drupal_get_form('guifi_user_delete_confirm',$guifi->username,$guifi->nid);
}

/**
 * Hook callback; delkete a network
 */
function guifi_user_delete_confirm($form_state,$username,$nid) {
  return confirm_form(array(),
                     t('Are you sure you want to delete the user %username?', array('%username' => $username)),
                     'node/'.$nid.'/view/users',
                     t('This action cannot be undone.'),
                     t('Delete'),
                     t('Cancel'));
}

/**
 * Menu callback; dispatch to the appropriate user edit function.
 */
function guifi_user_edit($id = 0) {

  guifi_log(GUIFILOG_TRACE,'function guifi_user_edit()',$id);

  $output = drupal_get_form('guifi_user_form',$id);

  return $output;

  $op = $_POST['op'];
  $edit = $_POST['edit'];

  $output = '';

  switch ($op) {
    case t('Submit'):
      guifi_edit_user_validate($edit);
      if (!form_get_errors()) {
        guifi_edit_user_save($edit);
        drupal_goto($_GET['q']);
      }
      $output .= guifi_edit_user_form($edit);
      break;
    case t('Delete user'):
      guifi_edit_user_validate($edit);
      if (form_get_errors()) {
        $output .= guifi_edit_user_form($edit);
        break;
      }
    case t('Delete'):
      guifi_delete_user($edit);
      break;
    case t('Reset password'):
      guifi_user_reset_password($edit);
      $output .= guifi_edit_user_form($edit);
      break;
    default:
      if ($id > 0) {

        $item = guifi_get_user($id);

        $edit = object2array($item);

      }
      else {
        $edit['id'] = 0; //Add a new user
        $item = arg(1);
        if (is_numeric($item)) {
          $node = node_load(array('nid'=>$item));
          if ($node->type == 'guifi_node')
            $edit['nid'] = $item;
        }
      }
      $output .= guifi_edit_user_form($edit);
  }

//  print theme('page', $output);
  return $output;
}

function guifi_user_reset_password($edit) {
  global $user;

  if (is_numeric($edit))
    $edit = guifi_user_load($edit);
  else
    $edit = guifi_user_load($edit['id']);

  if (empty($edit['notification'])) {
    form_set_error('notification', t('Don\'t know where to email a new password. ' .
        'You need to have an email properly filled to get a new password. ' .
        'You should contact network administrators ' .
        'for getting a new password.'));
    return;
  }

  $edit['pass'] = user_password();

  $params['account']=$user;
  $params['username']=$edit['username'];
  $params['pass']=$edit['pass'];
  $mail_success = drupal_mail(
    'guifi_user_password',
    'reset',
    $edit['notification'],
    user_preferred_language($user),
    $params);

    if ($mail_success) {
      watchdog('user',
        'Password mailed to %name for %email.',
        array('%name' => $edit['notification'], '%email' => $edit['username']));
      drupal_set_message(t('Your password and further instructions ' .
          'have been sent to your e-mail address.'));
      $edit['password'] = crypt($edit['pass']);
      guifi_user_save($edit);
    }
    else {
      watchdog('user',
        'Error mailing password to %name at %email.',
        array('%name' => $edit['username'], '%email' => $edit['notification']),
        WATCHDOG_ERROR);
      drupal_set_message(t('Unable to send mail to %email. ' .
          'Please contact the site admin.',
          array('%email'=>$edit['notification'])));
    }
  drupal_goto('node/'.$edit['nid'].'/view/users');
}

function guifi_user_password_mail($key, &$message, $params) {
  $language = $message['language'];
  $variables = user_mail_tokens($params['account'], $language);
  switch($key) {
    case 'reset':
      $message['subject'] = t('New password for user !username at guifi.net',
        array('!username'=>$params['username']),
        $language->language);
      $message['body'] = t(
          "!loggeduser has requested to change the password for the account " .
          "!username, and has been set to:\n\t !pass",
        array('!username'=>$params['username'],
          '!pass'=>$params['pass'],
          '!loggeduser'=>$params['account']->name),
        $language->language);
      break;
  }
}

/**
 * Get user information
**/
function guifi_user_load($id) {

  $item = db_fetch_array(db_query('SELECT * FROM {guifi_users} WHERE id = %d', $id));
  $item['services'] = unserialize($item['services']);
  $item['vars'] = unserialize($item['extra']);
  $item['content_filters'] = unserialize($item['content_filters']);

  return $item;
}


function guifi_user_form($form_state, $params = array()) {
  _user_password_dynamic_validation();

  guifi_log(GUIFILOG_TRACE,'function guifi_user_form()',$form_state);

  guifi_validate_js("#guifi-user-form");

  if (empty($form_state['values'])) {
    if (is_numeric($params))
      $form_state['values'] = guifi_user_load($params);
    else
      $form_state['values'] = $params;
  }

  if (isset($form_state['values']['id'])) {
    $f['id'] = array('#type'=>'hidden','#value'=>$form_state['values']['id']);
    drupal_set_title(t('edit user').' '.$form_state['values']['username']);
  } else {
    $f['new'] = array('#type'=>'hidden','#value'=>true);
    drupal_set_title(t('add user').' @ '.guifi_get_nodename($form_state['values']['nid']));
  }

  $f['firstname'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 128,
    '#title' => t('Firstname'),
    '#required' => TRUE,
    '#attributes' => array('class'=>'required'),
    '#default_value' => $form_state['values']['firstname'],
    '#description' => t('The real user name (Firstname), ' .
        'will be used while building the username.<br>' .
        'If username results duplicated, add more words ' .
        '(i.e. middle initial).<br>' .
        'Please enter real data, if fake information is entered, ' .
        'administrators might <strong>remove</strong> this user')
  );
  $f['lastname'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 128,
    '#title' => t('Lastname'),
    '#required' => TRUE,
    '#attributes' => array('class'=>'required'),
    '#default_value' => $form_state['values']['lastname'],
    '#description' => t('The real user name (Lastname).')
  );
  if (!empty($form_state['values']['username']))
    $f['username'] = array(
      '#type' => 'item',
      '#value' => $form_state['values']['username'],
      '#description' => t('The resulting username.')
    );

  if ((user_access('administer guifi users')) or
      (user_access('manage guifi users'))) {
    $f['status'] = array(
      '#type'=>'select',
      '#title'=>t('Status'),
      '#options'=>guifi_types('user_status'),
      '#default_value'=>$form_state['values']['status']
    );
    $f['node'] = array(
      '#type'=>'textfield',
      '#title'=>t('Node'),
      '#maxlength'=>60,
      '#default_value'=>$form_state['values']['nid'].'-'.
        guifi_get_zone_nick(guifi_get_zone_of_node(
        $form_state['values']['nid'])).', '.
        guifi_get_nodename($form_state['values']['nid']),
        '#autocomplete_path'=> 'guifi/js/select-node',
        '#element_validate' => array('guifi_nodename_validate'),
        '#description'=>t('Select the node where the user is.<br>' .
          'You can find the node by introducing part of the node id number, ' .
          'zone name or node name. A list with all matching values ' .
          'with a maximum of 50 values will be created.<br>' .
        'You can refine the text to find your choice.')
    );
  } else {
    $f['status'] = array(
      '#type'=>'item',
      '#title'=>t('Status'),
      '#value'=>$form_state['values']['status']
    );
    $f['node'] = array (
      '#type'=>'item',
      '#title'=>t('Node'),
      '#value'=>$form_state['values']['nid'].'-'.
        guifi_get_zone_nick(guifi_get_zone_of_node(
        $form_state['values']['nid'])).', '.
        guifi_get_nodename($form_state['values']['nid']),
    );
    if (!isset($f['new']))
      $f['previous_pwd'] = array(
        '#type'=>'password',
        '#title'=>t('Current password'),
        '#description'=>t('To proceed for any change, you have to ' .
          'know the current password.')
      );
    if (!isset($f['new']))
      $f['resetPwd'] = array (
        '#type'=>'submit',
        '#value'=>t('Reset password')
      );
  }

  $f['nid'] = array(
    '#type'=>'hidden',
    '#value'=> $form_state['values']['nid'],
  );

  $f['pass'] = array(
    '#type' => 'password_confirm',
    '#required' => isset($f['new']),
    '#title' => t('Set a new password'),
    '#description' => t('To change/set the current user password, enter the new password in both fields.'),
    '#size' => 25,
  );
  $f['notification'] = array(
    '#type' => 'textfield',
    '#size' => 60,
    '#maxlength' => 1024,
    '#title' => t('contact'),
    '#required' => TRUE,
    '#element_validate' => array('guifi_emails_validate'),
    '#default_value' => $form_state['values']['notification'],
    '#description' =>  t('Mailid where changes on this user will be notified, ' .
        'if many, separated by \',\'<br />' .
        'Also where the user can be contacted.')
  );

  // services
  $f['services'] = array(
   '#type'=>'fieldset',
   '#title'=>t('services'),
   '#collapsible'=>true,
   '#collapsed'=>false,
   '#tree'=>true,
  );

  if ((user_access('administer guifi users'))
      or (user_access('manage guifi users'))) {

    $f['services']['proxystr'] = array(
      '#type'=>'textfield',
      '#title'=>t('proxy'),
      '#maxlength'=>60,
      '#default_value'=> guifi_service_str($form_state['values']['services']['proxy']),
      '#autocomplete_path'=> 'guifi/js/select-service/proxy',
      '#element_validate' => array('guifi_service_name_validate',
        'guifi_user_proxy_validate'),
      // '#description'=>_service_descr('proxy')
    );
  } else {
    $f['services']['proxystr'] = array(
      '#type'=>'item',
      '#title'=>t('proxy'),
      '#value'=>guifi_service_str($form_state['values']['services']['proxy'])
    );
  }

  $f['services']['proxy'] = array(
    '#type'=>'hidden',
    '#value'=> $form_state['values']['services']['proxy'],
  );

  $f['services']['filters'] = array(
    '#type'=>'checkboxes',
    '#parents'=>array('content_filters'),
    '#title'=>t('content filters'),
    '#options'=> guifi_types('filter'),
    '#multiple'=>true,
    '#default_value'=> $form_state['values']['content_filters'],
    '#description'=>t('Content to be filtered.<br>Check the type of content ' .
        'which will be filtered to this user. ' .
        'Note that this filters will work only on those sites ' .
        'which have enabled this feature, ' .
        'so don\'t think that is safe to rely on this.')
  );

  $f['submit'] = array (
    '#type'=>'submit',
    '#value'=>t('Save')
  );
  if (!isset($f['new']))
    $f['delete'] = array (
      '#type'=>'submit',
      '#value'=>t('Delete')
    );


  return $f;
}


function guifi_user_proxy_validate($element, &$form_state) {
  $s = &$form_state['values']['services']['proxy'];
  switch ($element['#value']) {
  case t('No service'):
    $s = '-1';
    break;
  case t('Take from parents'):
    $n = node_load($form_state['values']['nid']);
    $s = guifi_zone_get_service($n->zone_id,'proxy_id');
    break;
  default:
    $nid = explode('-',$element['#value']);
    $s = $nid[0];
  }
}

/**
 * Confirm that an edited user fields properly filled in.
 */

function _guifi_user_queue_device_form_submit($form, $form_state) {
    guifi_log(GUIFILOG_TRACE,'function guifi_user_queue_device_form_submit()',$form_state);

    $u = guifi_user_load($form_state['clicked_button']['#post']['id']);

    $u['status'] = $form_state['values']['status'];
    $u['id'] = $form_state['clicked_button']['#post']['id'];

    guifi_user_save($u);
}

function guifi_user_form_validate($form, &$form_state) {

  guifi_log(GUIFILOG_TRACE,'function guifi_user_form_validate()',$form_state);

  $edit = &$form_state['values'];

  if ((isset($edit['id'])) and (isset($edit['previous_pwd']))) {
    if ($form_state['clicked_button']['#value'] != t('Reset password')) {
      if (empty($edit['previous_pwd']))
        form_set_error('previous_pwd',
          t('You need to specify the current password to submit any change'));
      $prevUser = guifi_user_load($edit['id']);
      if ((crypt($edit['previous_pwd'],$prevUser['password']) != $prevUser['password'])) {
        form_set_error('previous_pwd',t('Unable to submit changes: Password failure.'));
      }
    }
  }

  if (empty($edit['firstname'])) {
   form_set_error('firstname', t('Firstname field cannot be blank .'));
  }
  if (empty($edit['lastname'])) {
   form_set_error('lastname', t('Lastname field cannot be blank .'));
  }

  $edit['firstname']=trim($edit['firstname']);
  $edit['lastname']=trim($edit['lastname']);
  $edit['username']=str_replace(" ",".",strtolower(guifi_to_7bits($edit['firstname']).'.'.guifi_to_7bits($edit['lastname'])));
//  $edit['username'] = str_replace(" ",".",strtolower(guifi_to_7bits($edit['firstname'].'.'.$edit['lastname'])));

  if (!empty($edit['username'])) {
    if (isset($edit['id']))
      $query = db_query(
        "SELECT username, services " .
        "FROM {guifi_users} " .
        "WHERE username ='%s' " .
        " AND id <> %d",
        $edit['username'], $edit['id']);
    else
      $query = db_query(
        "SELECT username, nid, services " .
        "FROM {guifi_users} " .
        "WHERE username ='%s'",
        $edit['username']);

    while ($proxy_id = db_fetch_object($query)) {
      $services = unserialize($proxy_id->services);
      $qry2 = db_query(
        "SELECT nick " .
        "FROM {guifi_services} " .
        "WHERE id = %d",
        $services['proxy']);
      $proxy_name = db_fetch_object($qry2);

      form_set_error('username', t('The user %username is already defined ' .
        'at the node %nodename ' .
        'for service %servicename. Use middle initial, 2nd lastname or a prefix ' .
        'with the proxy to get a unique username.',
        array('%username'=>$edit['username'],
          '%nodename'=>guifi_get_nodename($edit['nid']),
          '%servicename'=>$proxy_name->nick
        ))
      );
    }
  }

  if (!empty($edit['pass']))
    $edit['password'] = crypt($edit['pass']);

}

function guifi_users_queue($zone) {

  function _guifi_user_queue_device_form($form_state, $params = array()) {

    guifi_log(GUIFILOG_TRACE,'function guifi_user_form()',$params);

    if (empty($form_state['values'])) {
      $form_state['values'] = $params;
    }
    $f['status'] = array(
      '#type'=>'select',
//      '#title'=>t('Status'),
      '#options'=>guifi_types('user_status'),
      '#default_value'=>$form_state['values']['status'],
      '#prefix'=>'<table><tr><td>',
      '#suffix'=>'</td>'
    );
    $f['id'] = array('#type'=>'hidden','#value'=>$form_state['values']['id']);
    $f['submit'] = array (
      '#type'=>'submit',
      '#value'=>t('Save'),
      '#name'=>$form_state['values']['id'],
      '#submit'=>array('_guifi_user_queue_device_form_submit'),
      '#prefix'=>'<td>',
      '#suffix'=>'</td></tr></table>'
    );
    // $f['#submit'][] = '_guifi_user_queue_device_form_submit';
    return $f;
  }

  function _guifi_user_queue_devices($u) {

    $query = db_query(
      'SELECT d.id ' .
      'FROM {guifi_devices} d ' .
      'WHERE d.nid=%d' .
      '  AND type="radio"',
      $u['nid']
    );
    $rows = array();
    while ($d = db_fetch_array($query)) {
     $d = guifi_device_load($d);

     $ip = guifi_main_ip($d['id']);
     $graph_url = guifi_graphs_get_node_url($u['nid'],FALSE);
     if ($graph_url != NULL)
       $img_url = ' <img src='.$graph_url.'?device='.$d['id'].'&type=availability&format=short>';
     else
       $img_url = 'NULL';
      $rows[] = array(
        l($d['nick'],'guifi/device/'.$d['id']),
        l($ip['ipv4'].'/'.$ip['maskbits'],
          guifi_device_admin_url($d,$ip['ipv4'])),
        array('data' => $d['flag'], 'class' => $d['flag']),
        array('data' => $img_url, 'class' => $d['flag']),
      );
    }
    return theme('table',null,$rows);
  }

  global $user;
  $owner = $user->uid;

  guifi_log(GUIFILOG_TRACE,'function guifi_users_node_list()',$zone);

  drupal_set_breadcrumb(guifi_zone_ariadna($zone->id));
  $title = t('Queue of pending users @') .' ' .$zone->title;
  drupal_set_title($title);

  $childs = array_keys(guifi_zone_childs($zone->id));
  $childs[] = $zone->id;

  $sql =
    'SELECT u.*, l.id nid, l.nick nnick, l.status_flag nflag ' .
    'FROM {guifi_users} u, {guifi_location} l ' .
    'WHERE u.nid=l.id' .
    '  AND (l.status_flag != "Working" OR u.status != "Approved") ' .
    '  AND l.zone_id IN ('.implode(',',$childs).')';
  $query = pager_query($sql);

  $rows = array();
  $header = array();

  while ($u = db_fetch_array($query)) {
    $rows[] = array(
      $u['username'],
      format_date($u['timestamp_created']),
      array('data'=>l($u['nnick'],'node/'.$u['nid']),'class'=>$u['nflag']),
      drupal_get_form('_guifi_user_queue_device_form',$u),
      _guifi_user_queue_devices($u)
    );
  }
  $output .= theme('table', $header, $rows);
  $output .= theme_pager(null, 50);

  return $output;
}

function guifi_users_node_list($node) {

  $output = drupal_get_form('guifi_users_node_list_form',$node);

  // To gain space, save bandwith and CPU, omit blocks
  print theme('page', $output, FALSE);
}

/**
 * outputs the user information data
**/
function guifi_users_node_list_form($form_state, $params = array()) {
  global $user;
  $owner = $user->uid;

  guifi_log(GUIFILOG_TRACE,'function guifi_users_node_list_form()',$form_state);

  if (empty($form_state['values'])) {
    if (is_numeric($params))
      $node = node_load($params);
    else
      $node = $params;
  }

//  $form_state['#redirect'] = FALSE;

//  if (!empty($op)) {
//    $edit=$_POST['edit'];
//    if ((empty($edit['user_checked'])) and ($op == t('Edit selected')))
//      form_set_error('',t('You must select a user checkbox for editing it'));
//    else
//      return guifi_edit_user($edit['user_checked']);
//  }

  drupal_set_breadcrumb(guifi_zone_ariadna($node->zone_id));
  $title = t('Users @') .' ' .$node->title;
  drupal_set_title($title);

  if ($node->type == 'guifi_node') {
    $query = db_query(
      "SELECT id, firstname, lastname, username, services, status " .
      "FROM {guifi_users} " .
      "WHERE nid = %d " .
      "ORDER BY lastname, firstname",
      $node->nid);
  } else
    $query = db_query(
      "SELECT id, firstname, lastname, username, services, status " .
      "FROM {guifi_users} " .
      "ORDER BY lastname, firstname");

  $rows[] = array();
  $num_rows = FALSE;

  $f = array(
    '#type'=> 'fieldset',
    '#collapsible' => false,
    '#title' => t('Users')
  );

  $options = array();

  while ($guser = db_fetch_object($query)) {
    $services = unserialize($guser->services);
    if ($node->type == 'guifi_service') {
      if (($node->service_type != 'Proxy') or ($node->nid != $services['proxy']))
        continue;
    }
    if (!empty($guser->lastname))
      $realname = $guser->lastname.', '.$guser->firstname;
    else
      $realname = $guser->firstname;

    $options[$guser->id] = $realname.' ('.$guser->username.')'.' '.
      $guser->status;
    if (!isset($default_user))
      $default_user = $guser->id;
  }

  if (count($options)) {
    $f['user_id'] = array(
      '#type'=>'radios',
      '#title'=>$title,
      '#options'=>$options,
      '#default_value'=>$default_user
    );
    if ((user_access('administer guifi users')) or (user_access('manage guifi users')) or ($node->uid == $owner))
      $f['editUser'] = array(
        '#type'=>'submit',
        '#value'=>t('Edit selected user')
      );
  } else
    $f['empty'] = array(
      '#type'=> 'item',
      '#title'=> t('There are no users to list at').' '.$node->title
    );
    if ((user_access('administer guifi users')) or (user_access('manage guifi users')) or ($node->uid == $owner))
      $f['addUser'] = array(
        '#type'=>'submit',
        '#value'=>t('Add user')
      );
  return $f;
}

function guifi_users_node_list_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_users_node_list_form_submit()',$form_state);

  switch ($form_state['clicked_button']['#value']) {
    case t('Edit selected user'):
      if (empty($form_state['values']['user_id'])) {
        drupal_set_message(t('You must select a user from the list'));
        break;
      }
      drupal_goto('guifi/user/'.$form_state['values']['user_id'].'/edit');
      break;
    case t('Add user'):
      drupal_goto('node/'.arg(1).'/user/add');
      break;
  }
}

function guifi_user_form_submit($form, &$form_state) {
  guifi_log(GUIFILOG_TRACE,'function guifi_user_form_submit()',$form_state);

  switch ($form_state['clicked_button']['#value']) {
    case t('Save'):
      guifi_user_save($form_state['values']);
      drupal_goto("node/".$form_state['values']['nid']."/view/users");
//        drupal_set_message(t('User save.'));
      break;
    case t('Reset password'):
      guifi_user_reset_password($form_state['values']);
      drupal_goto("node/".$form_state['values']['nid']."/view/users");
      break;
    case t('Delete'):
      drupal_goto("guifi/user/".$form_state['values']['id']."/delete");
      break;
  }
}


/**
 * Save changes to the database.
 */
function guifi_user_save($edit) {
  global $user;

  $n = node_load($edit['nid']);

  $to_mail = $n->notification;
  $log ='';

  if (isset($edit['services'])) {
    if (isset($edit['services']['proxystr']))
      unset($edit['services']['proxystr']);
    $edit['services'] = serialize($edit['services']);
  }
  if (isset($edit['var']))
    $edit['extra'] = serialize($edit['var']);
  if (isset($edit['content_filters']))
    $edit['content_filters'] = serialize($edit['content_filters']);

  guifi_log(GUIFILOG_TRACE,'function guifi_user_save()',$edit);

  _guifi_db_sql('guifi_users',array('id'=>$edit['id']),$edit,$log,$to_mail);

//  drupal_set_message($log);
//  drupal_set_message($to_mail);
  drupal_set_message(t('%user saved. Note that in some cases the change will not take effect until after some time.', array('%user' => $edit['username'])));
  guifi_notify(
    $to_mail,
    t('The user !username has been UPDATED by !user.',array('!username' => $edit['username'], '!user' => $user->name)),
    $log);
}

function guifi_dump_passwd($node) {

// Aquesta funcio volca a pantalla la llista d'usuaris i passwd que genera guifi_dump_passwd_return

  print guifi_dump_passwd_return($node);
  exit;
}

function _get_zonename($id) {
  $zone = node_load(array('nid'=>$id));
  return $zone->title;
}


function guifi_dump_passwd_return($node,$federated = FALSE) {

  /* Aquesta funcio retorna en una variable la llista d'usuaris i passwd dun proxy */

  $passwd = array();

  // query ALL zones, kept in memory zones array
  $zones = array();
  $query = db_query("SELECT id, title FROM {guifi_zone}");
  while ($item = db_fetch_object($query))
    $zones[$item->id] = $item->title;

  // query ALL node zones, kept in memory node_zones array
  $node_zones = array();
  $query = db_query("SELECT id, zone_id FROM {guifi_location}");
  while ($item = db_fetch_object($query))
    $node_zones[$item->id] = $item->zone_id;

  // query ALL users, kept in memory users array
  $query = db_query("SELECT * FROM {guifi_users} WHERE status='Approved'");
  $users = array();
  while ($item = db_fetch_object($query)) {
    $user = object();
    $user->username = $item->username;
    $user->password = $item->password;
    $user->nid = $item->nid;
    $services = unserialize($item->services);
    $user->prId = $services['proxy'];
    $user->zId = $node_zones[$item->nid];
    $users[$user->prId][] = $user;
  }

  $passwd = array();

  // dumping requested proxy users, starting by the users from the same zone
  foreach ($users[$node->id] as $user)
    $passwd[$user->zId][] = $user->username.':'.$user->password;

  $dump .=  "#\n";
  $dump .=  "# passwd file for proxy: ".$node->nick." at zone ".$zones[$node->zone_id]."\n";
  $dump .=  "# users: ".count($passwd[$node->zone_id])."\n";
  $dump .=  "#\n";
  if (count($passwd[$node->zone_id]))
    foreach ($passwd[$node->zone_id] as $p)
      $dump .= $p."\n";
  else
      $dump .= '# '.t('there are no users at this proxy')."\n";
  unset($passwd[$node->zone_id]);

  // now dumping all other zones from the principal proxy
  foreach ($passwd as $zid=>$zp) {
    $dump .= "# At zone ".$zones[$zid]."\n";
    foreach ($zp as $p)
       $dump .= $p."\n";
  }

  if ($federated == FALSE)
    return $dump;

  unset($users[$node->id]);
  unset($passwd);
  foreach ($users as $prId=>$prUsers)
    if (in_array($prId,$federated))
      foreach ($prUsers as $user)
        $passwd[$node_zones[$user->nid]][] = $user->username.':'.$user->password;
  $dump .=  "#\n";
  $dump .=  "# passwd file for ALL OTHER proxys\n";
  $dump .=  "#\n";
  foreach ($passwd as $zid=>$zp) {
    $dump .= "#\n";
    $dump .= "# At zone ".$zones[$zid]."\n";
    $dump .= "#\n";
    foreach ($zp as $p)
       $dump .= $p."\n";
  }

  return $dump;
}

function _guifi_dump_federated($node) {


// Si el proxy esta federat IN, afegim tots els usuaris dels proxys que estan federats OUT


if (is_array($node->var[fed]))
  if (in_array('IN',$node->var[fed])) {
    $head  = "#\n";
    $head .= '# Federated User &#038; password list for Proxy : "'.$node->title.'"'."\n";
    $head .= "#\n";
    $head .= "#  Includes users from the following proxys :\n";
    $head .= "#\n";
    $head .= '#   ' .$node->nid." - ".$node->title."\n";
    $query = db_query("SELECT id,extra FROM {guifi_services} WHERE service_type='Proxy'");
    while ($item = db_fetch_object($query))
    {
      $extra = unserialize($item->extra);
	  if (($item->id!=$node->nid) & (is_array($extra[fed]))) {
        $p_node = node_load(array('nid' => $item->id));
	    if ( in_array('OUT',$extra[fed])) {
	      $head .= '#   ' .$p_node->nid." - ".$p_node->title."\n";
          $federated_out[] = $item->id;
	    }
	  }
    }
    $head .="#\n";
  }

  // Resum dels proxys federats
  $output .= $head;
  // LLista de usuaris i passwords
  $output .= guifi_dump_passwd_return($node,$federated_out);
  return $output;
}

function guifi_dump_federated($node) {
  $output = _guifi_dump_federated($node);
  print $output;
  exit;
}

function guifi_dump_federated_md5($node) {
  $dump = _guifi_dump_federated($node);
  print md5($dump);
  exit;
}

function guifi_dump_ldif($node) {

  function user_dump_ldif($user) {
    /* Format:
dn: uid=eloi.alsina,ou=People,dc=guifi,dc=net
uid: eloi.alsina
cn: Eloi Alsina
objectClass: account
objectClass: posixAccount
objectClass: top
userPassword: {crypt}]lGnmr4S7ObLo
uidNumber: 0
gidNumber: 0
homeDirectory: eloi
host: esperanca

dn: cn=Eloi Alsina,uid=eloi.alsina,ou=People,dc=guifi,dc=net
givenName: eloi
sn: alsina
cn: Eloi Alsina
mail: eloi.alsina@guifi.net
homePhone: 938892062
mobile: 6639393906
homePostalAddress: Mas Seri Xic
objectClass: inetOrgPerson
objectClass: top
     */
     $dump  = "dn: uid=".$user->username.",ou=People,dc=guifi,dc=net\n";
     $dump .= "uid:".$user->username."\n";
     $dump .= "cn:".$user->lastname.", ".$user->firstname."\n";
     $dump .= "objectClass: account\n";
     $dump .= "objectClass: posixAccount\n";
     $dump .= "objectClass: top\n";
     $dump .= "userPassword: {crypt}".$user->password."\n";
     $dump .= "uidNumber: 99\n";
     $dump .= "gidNumber: 99\n";
     $dump .= "homeDirectory: /home/nobody\n";
     $dump .= "description: proxy(".$user->services['proxy'].")\n";

     return $dump;
  }


  $query = db_query("SELECT id FROM {guifi_users} ORDER BY lastname, firstname");

  $passwd = array();
  $zones = array();
  while ($item = db_fetch_object($query)) {
    $user = guifi_get_user($item->id);
    if ($user->services['proxy'] != $node->nid)
      continue;
    $usernode = db_fetch_object(db_query("SELECT zone_id FROM {guifi_location} WHERE id=%d",$user->nid));
    $ldap[$usernode->zone_id][] = user_dump_ldif($user);
    $zones[$usernode->zone_id] = true;
  }

  // Dumping passwd
  // Starts with users in the same zone as the service
  $dump .=  "#\n";
  $dump .=  "# LDIF file for LDAP guifi.net directories: ".$node->nick." at zone "._get_zonename($node->zone_id)."\n";
  $dump .= "#\n";
  if (!empty($ldap[$node->zone_id]))
    foreach ($ldap[$node->zone_id] as $p)
      $dump .= $p."\n";
  else
      $dump .= '# '.t('there are no users at this proxy')."\n";

  // Now dumping other zones
  foreach ($zones as $z=>$dummy) {
    if ($z == $node->zone_id)
      continue;
    $dump .= "#\n";
    $dump .= "# At zone "._get_zonename($z)."\n";
    $dump .= "#\n";
    foreach ($ldap[$z] as $p) {
       $dump .= $p."\n";
    }
  }


  print $dump;
  exit;
}


?>
