<?php

/**
 * user editing functions
**/

/**
 * Menu callback; handle the adding of a new user.
 */
function guifi_add_user() {
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
      // Fall through.
    default:
      $edit['valid'] = 1;
      $output .= guifi_edit_user_form($edit);
  }

  print theme('page', $output);
}

/**
 * Menu callback; delete a single user.
 */
function guifi_delete_user($edit) {
  $op = $_POST['op'];
  $result = db_query('SELECT username, nid FROM {guifi_users} WHERE id = %d', $edit['id']);
  $guifi = db_fetch_object($result);
  if (!$guifi) {
    drupal_goto($_GET['q']);
  }
  switch ($op) {
    case t('Delete'):
      db_query('DELETE FROM {guifi_users} WHERE id = %d', $edit['id']);
      drupal_set_message(t('User deleted.'));
      cache_clear_all();
      drupal_goto($_GET['q']);
      break;
    default:
      $message = t('Are you sure you want to delete the user %user?', array('%user' => theme('placeholder', $guifi->username)));
      $hidden = form_hidden('id',$edit['id']);
      $hidden .= form_hidden('nid',$edit['nid']);
      $output = theme('confirm', $message, 'node/'.$edit['nid'].'/view/users', t('This action cannot be undone.'), t('Delete'), null, $hidden );
      print theme('page', $output);
  }
}

/**
 * Menu callback; dispatch to the appropriate user edit function.
 */
function guifi_edit_user($id = 0) {

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
          if ($node->type == 'guifi-node')
            $edit['nid'] = $item;
        } 
      }
      $output .= guifi_edit_user_form($edit);
  }

//  print theme('page', $output);
  return $output;
}

function guifi_user_reset_password($edit) {
  $user = guifi_get_user($edit['id']);
  $edit = object2array($user);

  if (empty($user->email)) {
    form_set_error('email', t('Don\'t know where to email a new password. You need to have an email properly filled to get a new password. You should contact network administrators for getting a new password.'));
    return;
  }

  $edit['pwd1'] = user_password();

  // Mail new password:
  $subject = t('New password for user').' '.$edit['username'].' '.t('at guifi.net');
  $body = t('New passord:').' '.$edit['pwd1'];
  $headers = "From: guifi.net\nReply-to: guifi.net\nX-Mailer: Drupal\nReturn-path: guifi.net\nErrors-to: guifi.net";
  $mail_success = user_mail($edit['email'], $subject, $body, $headers);

    if ($mail_success) {
      watchdog('user', t('Password mailed to %name at %email.', array('%name' => theme('placeholder', $edit['username']), '%email' => theme('placeholder', $edit['email']))));
      drupal_set_message(t('Your password and further instructions have been sent to your e-mail address.'));
      guifi_edit_user_save($edit);
    }
    else {
      watchdog('user', t('Error mailing password to %name at %email.', array('%name' => theme('placeholder', $email['username']), '%email' => theme('placeholder', $edit['email']))), WATCHDOG_ERROR);
      drupal_set_message(t('Unable to send mail. Please contact the site admin.'));
    }
//  drupal_goto('node/'.$user->nid.'/view/users');
  drupal_goto($_GET['q']);


}

/**
 * Get user information 
**/
function guifi_get_user($id) {

  $item = db_fetch_object(db_query('SELECT * FROM {guifi_users} WHERE id = %d', $id));
  $item->services = unserialize($item->services);
  $item->vars = unserialize($item->extra);

  return $item;
}

/**
 * Present the guifi user editing form.
 */
function guifi_edit_user_form($edit) {

  $proxy_list = array('0'=>t('None')) + guifi_services_select('Proxy');

  $form .= form_textfield(t('Firstname'), 'firstname', $edit['firstname'], 60, 128, t('The real user name (Firstname), will be used while building the username. If username results duplicated, add more words (i.e. middle initial).'), NULL, TRUE);
  $form .= form_textfield(t('Lastname'), 'lastname', $edit['lastname'], 60, 128, t('The real user name (Lastname).'), NULL, TRUE);
  $form .= form_item(t('Username'), $edit['username'], t('The resulting username.'));
  $form .= form_select(t('Node'), 'nid', $edit['nid'], guifi_nodes_select(), t('The node where this user belongs to.'));

  $cpwd = form_password(t('Old password'), 'old_pwd', $edit['old_pwd'], 60, 128, t('The current password for this user. Mandatory to submit any change'), NULL, TRUE);
  $cpwd .= form_submit(t('Reset password'));
  if (($edit['id'] > 0) and (user_access('administer guifi users') == false))
    $form.= form_group(t('Validate current password'),$cpwd,null);
  
  $fpwd = form_password(t('New password'), 'pwd1', $edit['pwd1'], 60, 128, t('New password, if wants to change it'), NULL, TRUE);
  $fpwd .= form_password(t('Confirm'), 'pwd2', $edit['pwd2'], 60, 128, t('Retype the new password'), NULL, TRUE);
  $form.= form_group(t('Set password'),$fpwd,null);

  $form .= form_textfield(t('Email'), 'email', $edit['email'], 60, 128, t('Email address. Needed to reset passwords'), NULL, TRUE);
  
  if ((user_access('administer guifi users')) or (user_access('manage guifi users')))
    $form .= form_select(t('Proxy'), 'services][proxy', $edit['services']['proxy'], $proxy_list, t('The proxy where this user has default acces to.'));
  else {
    $form .= form_hidden('services][proxy',$edit['services']['proxy']);
    $form .= form_item(t('Proxy'),$proxy_list[$edit['services']['proxy']]);
  }

  $name_created = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $edit['user_created']));
  if ($edit['user_changed'] > 0)
    $name_changed = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $edit['user_changed']));

  $form .= form_item(t('Created by'),$name_created->name.' '.t('at').' '.format_date($edit['timestamp_created']));
  if ($edit['user_changed'] > 0)
    $form .= form_item(t('Modified by'),$name_changed->name.' '.t('at').' '.format_date($edit['timestamp_changed']));

  $form .= form_hidden('user_created',$edit['user_created']);
  $form .= form_hidden('user_changed',$edit['user_changed']);
  $form .= form_hidden('timestamp_created',$edit['timestamp_created']);
  $form .= form_hidden('timestamp_changed',$edit['timestamp_changed']);

  $form .= form_submit(t('Submit'));
  $form .= form_submit(t('Delete user'));

  $form .= form_hidden('id', $edit['id']);
  $form .= form_hidden('password', $edit['password']);
  $form .= form_hidden('username', $edit['username']);

  return form($form);
}

/**
 * Confirm that an edited user fields properly filled in.
 */
function guifi_edit_user_validate(&$edit) {
  if (($edit['id'] > 0) and (user_access('administer guifi users') == false))
  if (crypt($edit['old_pwd'],$edit['password']) != $edit['password']) {
    form_set_error('old_pwd', t('Unable to submit changes: Password failure.'));
  }

  if (empty($edit['firstname'])) {
   form_set_error('firstname', t('Firstname field cannot be blank .'));
  }
  if (empty($edit['lastname'])) {
   form_set_error('lastname', t('Lastname field cannot be blank .'));
  }
    
  if (!empty($edit['pwd1'])) 
  if ($edit['pwd1'] != $edit['pwd2']) {
    form_set_error('pwd1', t('Unable to set the password: Does not match.'));
  } 

  if (!empty($edit['email'])) {
  if (!valid_email_address($edit['email']))
    form_set_error('email', t('This is not a valid email address.'));
  } else {
    form_set_error('email', t('Email address field cannot be blank.'));
  }
 
  $edit['username'] = str_replace(" ",".",strtolower(guifi_to_7bits($edit['firstname'].'.'.$edit['lastname'])));
  
  if (!empty($edit['username'])) {
    $query = db_query("SELECT username, services FROM {guifi_users} WHERE username ='%s' AND id <> %d",$edit['username'], $edit['id']);
    $proxy_id = db_fetch_object($query);
    $services = unserialize($proxy_id->services);
      if (db_num_rows($query)) {
      $result = db_query("SELECT nick FROM {guifi_services} WHERE id = %d",$services['proxy']);
      $proxy_name = db_fetch_object($result);
    
    form_set_error('username', t('The user ').$edit['username'].t(' is already taken on proxy:').' <a href="/node/'.$services['proxy'].'/">'.$proxy_name->nick.' (http://www.guifi.net/node/' .$services['proxy']. ').</a>'. t(' If the user is a different person, please write the name in this format: In the field "Firstname" type proxy_name.user_name and in the field "Lastname", just type the the lastname. Example: Firstname: ausa.pol , Lastname: sucarrats. The result will be the username: ausa.pol.sucarrats'));
      }
  }
}

/**
 * outputs the user information data
**/
function guifi_list_users($node) {

  $op=$_POST['op'];


  if (!empty($op)) {
    $edit=$_POST['edit'];
    if ((empty($edit['user_checked'])) and ($op == t('Edit selected')))
      form_set_error('',t('You must select a user checkbox for editing it'));
    else
      return guifi_edit_user($edit['user_checked']);
  }

  if ($node->type == 'guifi-node') {
    $query = db_query("SELECT id, firstname, lastname, username, services FROM {guifi_users} WHERE nid = %d ORDER BY lastname, firstname",$node->nid);
  } else
    $query = db_query("SELECT id, firstname, lastname, username, services FROM {guifi_users} ORDER BY lastname, firstname");
  
  $rows[] = array();
  if (db_num_rows($query)) {
    while ($user = db_fetch_object($query)) {
      $services = unserialize($user->services);
      if ($node->type == 'guifi-service') {
        if (($node->service_type != 'Proxy') or ($node->nid != $services['proxy']))
          continue;
      }

      if (!empty($user->lastname))
        $realname = $user->lastname.', '.$user->firstname;
      else
        $realname = $user->firstname;
      $rows[] = array(form_radio('','user_checked',$user->id),
                      $realname,
                      $user->username);
    } 
    $output = '<h2>' .t('Users of') .' ' .$node->title.'</h2>';
    $output .= theme('table', array(null,t('real name'),t('username')), array_merge($rows),null);
    $output .= form_button(t('Edit selected'), 'op');
  } else {
    $output = '<h2>'.t('There is no users to list at').' '.$node->title.'</h2>';
  }
  if ((user_access('administer guifi users')) or (user_access('manage guifi users')))
    $output .= form_button(t('Add user'), 'op');
  return form($output);
}


/**
 * Save changes to the database.
 */
function guifi_edit_user_save($edit) {

  global $user;

  if (!empty($edit['pwd1'])) {
    $edit['password'] = crypt($edit['pwd1'],chr(rand(65,97)).chr(rand(90,122)));
  }

  if ($edit['id']) {
    db_query("UPDATE {guifi_users} SET firstname = '%s', lastname = '%s', username = '%s', password = '%s', email = '%s', services = '%s', extra = '%s', nid = %d, timestamp_changed = %d, user_changed = %d WHERE id = %d", $edit['firstname'], $edit['lastname'], $edit['username'], $edit['password'], $edit['email'], serialize($edit['services']), serialize($edit['vars']), $edit['nid'], time(), $user->uid, $edit['id']);
    drupal_set_message(t('Updated guifi user %user. Change will be efective from now to a couple of hours.', array('%user' => theme('placeholder', $edit['username']))));
  }
  else {
    db_query("INSERT INTO {guifi_users} ( firstname, lastname, username, password, email, services, extra, nid, timestamp_created, user_created) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d)", $edit['firstname'], $edit['lastname'], $edit['username'], $edit['password'], $edit['email'], serialize($edit['services']),  serialize($edit['vars']), $edit['nid'], time(), $user->uid);
    drupal_set_message(t('Created new user %user. Change will be efective from now to a couple of hours.', array('%user' => theme('placeholder', $edit['username']))));
  }
  cache_clear_all();
}

function guifi_dump_passwd($node) {
    
// Aquesta funcio volca a pantalla la llista d'usuaris i passwd que genera guifi_dump_passwd_return 

  $dump = guifi_dump_passwd_return($node);
  print $dump;
  exit;
} 

  function _get_zonename($id) {
    $zone = node_load(array('nid'=>$id));
    return $zone->title;
  }


function guifi_dump_passwd_return($node) {

    /* Aquesta funcio retorna en una variable la llista d'usuaris i passwd dun proxy */
    

  $query = db_query("SELECT id FROM {guifi_users} ORDER BY lastname, firstname");

  $passwd = array();
  $zones = array();
  while ($item = db_fetch_object($query)) {
    $user = guifi_get_user($item->id);
    if ($user->services['proxy'] != $node->nid)
      continue;
    $usernode = db_fetch_object(db_query("SELECT zone_id FROM {guifi_location} WHERE id=%d",$user->nid));
    $passwd[$usernode->zone_id][] = $user->username.':'.$user->password;
    $zones[$usernode->zone_id] = true;
  }

  // Dumping passwd
  // Starts with users in the same zone as the service
  $dump .=  "#\n";
  $dump .=  "# passwd file for proxy: ".$node->nick." at zone "._get_zonename($node->zone_id)."\n";
  $dump .= "#\n";
  if (!empty($passwd[$node->zone_id])) 
    foreach ($passwd[$node->zone_id] as $p) 
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
    foreach ($passwd[$z] as $p) {
       $dump .= $p."\n";
    }
  }

  
  return $dump;
  exit;
}

function guifi_dump_federated($node) {
     
// Llistem sempre els usuaris del proxy sobre el que treballem
    
$dump .= guifi_dump_passwd_return($node);    
   
    
// Si el proxy esta federat IN, afegim tots els usuaris dels proxys que estan federats OUT


if (is_array($node->var[fed]))
 if (in_array('IN',$node->var[fed]))    
    {
     unset($head);	
     $head .= "#\n";	
     $head .= '# Federated User &#038; password list for Proxy : "'.$node->title.'"'."\n";
     $head .= "#\n";
     $head .= "#  Includes users from the following proxys :\n";
     $head .= "#\n";	
     $head .= '#   ' .$node->nid." - ".$node->title."\n";
     $query = db_query("SELECT id,extra FROM {guifi_services} WHERE service_type='Proxy'");
     while ($item = db_fetch_object($query))
       {	
	$extra = unserialize($item->extra);
	if (($item->id!=$node->nid) & (is_array($extra[fed])))
	    {   		
	     $p_node = node_load(array('nid' => $item->id));
	     if ( in_array('OUT',$extra[fed]))
	        {
		 $head .= '#   ' .$p_node->nid." - ".$p_node->title."\n";  
		 $dump .= guifi_dump_passwd_return($p_node);
		}
	    }
       }
     $head .="#\n";
	
     }
   
  print $head;   // Resum dels proxys federats  
  print $dump;   // LLista de usuaris i passwords
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
