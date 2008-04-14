<?php
/**
 * @file
 * Manage guifi_devices 
 */


/**
 * device editing functions
**/

/**
 * Menu callback; handle the adding of a new device.
 */
function guifi_add_device() {
  $op = $_POST['op'];
  $edit = $_POST['edit'];
  $output = '';
  global $user;

//  print_r($_GET);
//  print_r($_POST);
//  print_r($edit);

  switch ($op) {
    case t('Save & exit'):
    case t('Submit'):
//      print "Device Submitted";
      guifi_edit_device_validate($edit);
      if (!form_get_errors()) {
        guifi_edit_device_save($edit);
//        print "\n<br />node save done\n<br />";
        drupal_goto('node/'.$edit[nid].'/view/devices');
      }
    case t('Save & continue edit'):
//      print "Device Submitted";
      guifi_edit_device_validate($edit);
      if (!form_get_errors()) {
        $edit[id] = guifi_edit_device_save($edit);
//        print "\n<br />node save done\n<br />";
        drupal_goto('guifi/device/'.$edit[id].'/edit');
      }
    case t('Preview'):
    case t('Validate'):
      guifi_edit_device_validate($edit);
      if (!form_get_errors()) {
        drupal_set_message(t('Note that this is only a preview, you must submit the form to commit the changes.'));
      }
      $output .= guifi_edit_device_form($edit);
      break;
    default:
      $node = node_load(array('nid'=>$edit['nid']));

      // new device, setting default values
      $edit['links'] = array();
      $edit['netmask'] = '255.255.255.224';
      $devs = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_devices} WHERE type = '%s' AND nid = %d",$edit['type'],$edit['nid']));
      $edit['nick'] = $node->nick.ucfirst($edit['type']).($devs->count + 1);
      if ($edit['type'] == 'radio') {
        $edit[variable]['firmware'] = 'DD-guifi';
        $edit[variable]['model_id'] = '16';
//        $edit['mode'] = 'client';
//        $edit['protocol'] = '802.11bg';
      }
      if (valid_email_address($node->contact))
        $edit['contact'] = $node->contact;
      else {
        drupal_set_message(t('The node has not a valid email address as a contact. Using your email as a default. Change the contact mail address if necessary.'));
        $edit['contact'] = $user->mail;
      }
      drupal_set_title(t('adding a new device at %node',array('%node' => theme('placeholder', $node->nick))));
      $output .= guifi_edit_device_form($edit,$node);
  }

  print theme('page', $output );
}

/**
 * Menu callback; delete a single custom item.
 */
function guifi_delete_device($id) {

  global $user;

  $op = $_POST['op'];
  $result = db_query('SELECT nick name, nid, type, comment FROM {guifi_devices} WHERE id = %d', $id);
  $guifi = db_fetch_object($result);
  if (!$guifi) {
    drupal_goto('admin/guifi');
  }
  switch ($op) {
    case t('Confirm delete'):
      guifi_log(GUIFILOG_BASIC,sprintf('device (%s) %d-%s deleted.',$guifi->type,$id,$guifi->name));
      while ($interface = db_fetch_object('SELECT id FROM {guifi_interfaces} WHERE device_id=%d',$id))  {
        db_query('DELETE FROM {guifi_ipv4} WHERE id = %d', $interface->id);
      }
      db_query('DELETE FROM {guifi_devices} WHERE id = %d', $id);
      db_query('DELETE FROM {guifi_radios} WHERE id = %d', $id);
      db_query('DELETE FROM {guifi_interfaces} WHERE device_id = %d', $id);
      $query = db_query("SELECT id FROM {guifi_links} WHERE device_id = %d", $id);
      while ($link = db_fetch_object($query))
        db_query('DELETE FROM {guifi_links} WHERE id = %d', $link->id);
      $message = t('Device %name deleted.', array('%name' => theme('placeholder', $guifi->name)));
      drupal_set_message($message);
      user_mail(variable_get('guifi_contact','netadmin@guifi.net'),strip_tags(t('guifi: device %name deleted',array('%name'=>theme('placeholder',$guifi->name)))),
                    strip_tags(t('The device %name has been deleted by %user.',array('%name' => theme('placeholder', $guifi->name),
                                '%user' => theme('placeholder', $user->name))
                      )
                    ),'From: webmestre@guifi.net');

      drupal_goto('node/'.$guifi->nid);
      break;
    default:
      $message = t('Are you sure you want to delete the guifi device %name?', array('%name' => theme('placeholder', $guifi->name)));
      $output = theme('confirm', $message, 'node/'.$guifi->nid, t('WARNING: This action cannot be undone. The device and it\'s related information will be <strong>permanently deleted</strong>, that includes:<ul><li>The device</li><li>The related interfaces</li><li>The links where this device is present</li></ul>If you are really sure that you want to delete this information, press "Confirm delete".'), t('Confirm delete'));
      print theme('page', $output);
  }
}

/**
 * Menu callback; dispatch to the appropriate guifi item edit function.
 */
function guifi_edit_device($id = 0) {

  $op = $_POST['op'];
  if (is_array($op)) {
    list($interface) = array_keys($op);
    list($op) = array_values($op);
  }
  $edit = $_POST['edit'];
//  print($op);

  $output = '';
  switch ($op) {
    case t('Save & exit'):
      guifi_edit_device_validate($edit);
      if (!form_get_errors()) {
        guifi_edit_device_save($edit);
        drupal_goto('guifi/device/'.$edit['id']);
      }
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Save & continue edit'):
      guifi_edit_device_validate($edit);
      if (!form_get_errors()) {
        guifi_edit_device_save($edit);
        drupal_goto('guifi/device/'.$edit['id'].'/edit');
      }
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Preview'):
    case t('Validate'):
      guifi_edit_device_validate($edit);
      if (!form_get_errors()) {
        drupal_set_message(t('Note that this is only a preview, you must submit the form to commit the changes.'));
      }
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add radio'):
      $edit = guifi_add_radio($edit);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add wLan for clients'):
      $edit = guifi_add_radio_wlan($edit,$interface);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add Hotspot for guests'):
      $edit = guifi_add_hotspot($edit,$interface);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add interface'):
      guifi_add_interface($edit);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add network'):
      guifi_add_interface_address($edit,$interface);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Link to AP'):
      guifi_add_link($edit,'ap/client',$interface);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add AP/Client link'):
      guifi_add_link($edit,'ap/client',$interface);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add WDS/bridge p2p link'):
      guifi_add_link($edit,'wds',$interface);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Add cable link'):
    case t('Link to router'):
      guifi_add_link($edit,'cable',$interface);
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Back to list'):
      unset($edit['edit_details']);
    case t('Edit selected'):
      guifi_edit_device_validate($edit);
      $output .= guifi_edit_device_form($edit);
      break;
    case t('Delete selected'):
    case t('Confirm delete'):
      // print_r($edit[edit_details]);
      $parse = explode(',',$edit[edit_details]);
      if (!isset($edit[edit_details])) {
        form_set_error(null,t('Nothing selected.'));
      } else if ($parse[0] != 'interface') {
        // is radio
        if (count($parse) == 1) 
          guifi_delete_radio($edit,$op);
        else if (count($parse) == 2)
          guifi_delete_radio_interface($edit,$op);
        else
          guifi_delete_link($edit,$op);
      } else {
        // interface
        guifi_delete_interface($edit,$op);
      }
      unset($edit[edit_details]);
      $output .= guifi_edit_device_form($edit);
      break;
//    case t('Delete'):
//      guifi_delete_device($edit['id']);
//      break;
    default:
      if ($id > 0) {
        $edit = guifi_get_device($id);
      }
      else {
        $edit['id'] = 0; // In case a negative ID was passed in.
      }
      $output .= guifi_edit_device_form($edit);
  }

  print theme('page', $output);
}

/**
 * Get device information 
**/
function guifi_get_device($id,$ret = 'array') {
  $device = db_fetch_array(db_query('SELECT d.* FROM {guifi_devices} d  WHERE d.id = %d', $id));
  if (empty($device)) {
    drupal_set_message(t('Device does not exist.'));
    return;
  }
  if (!empty($device['extra'])) 
    $device['variable'] = unserialize($device['extra']);
  else
    $device['variable'] = array();
 
  unset($purge);
  // getting device radios
  if ($device['type'] == 'radio') {
    // Get radio
    $qr = db_query('SELECT * FROM {guifi_radios} WHERE id = %d ORDER BY id, radiodev_counter', $id);
    if (db_num_rows($qr) == 0) {
//      drupal_set_message(t('Fatal Error: This device (%id %name) has no radio. Report this error to your network administrator.',array('%id' => theme('placeholder', $id), '%name' => theme('placeholder', $device['nick']))));
      return $device;
    }
    $device['firewall'] = false; // Default: No firewall

    while ($radio = db_fetch_array($qr)) {
      
      if (!$device['firewall'])
        if ($radio['mode'] == 'client')
           $device['firewall'] = true;

      $device['radios'][$radio['radiodev_counter']] = $radio;

      // get interface
      $qi = db_query('SELECT * FROM {guifi_interfaces} WHERE device_id=%d AND radiodev_counter=%d ORDER BY id, interface_type, radiodev_counter',$device['id'],$radio['radiodev_counter']);
      while ($i = db_fetch_array($qi)) {
//        print  "\n<br />Interface: ";
//        print_r($i);
        if ($device['radios'][$radio['radiodev_counter']]['mac'] == '')
          $device['radios'][$radio['radiodev_counter']]['mac'] = $i['mac'];
        $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']] = $i;

        // get ipv4
        $ipdec = array();
        $iparr = array();
        $qa = db_query('SELECT * FROM {guifi_ipv4} WHERE interface_id=%d',$i['id']);
        while ($a = db_fetch_array($qa)) {
          $ipdec[$a['id']] = _dec_addr($a['ipv4']);
          $iparr[$a['id']] = $a;
        }
        asort($ipdec); 
       
        foreach($ipdec as $ka=>$foo) {
          $a = $iparr[$ka];
          $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']] = $a;
          // get linked devices
          $qlsql = sprintf('SELECT l2.* FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l2.device_id != %d AND l1.device_id=%d AND l1.interface_id=%d AND l1.ipv4_id=%d',$id,$id,$i['id'],$a['id']);
          $ql = db_query($qlsql);

          // if orphan WDS link, delete
          // There was an ip for an WDS link, but no link, so links nowhere
          if ((db_num_rows($ql) == 0) and ($i['interface_type'] == 'wds/p2p')) {
             guifi_log(GUIFILOG_BASIC,sprintf('Going to DELETE detected orphan WDS link when loading %d: %s',$id,$qlsql)); 
             $purge[] = sprintf('DELETE FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',$a['id'],$a['interface_id']);
             unset($device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]);
          } else {
            $ipdec2 = array();
            $iparr2 = array();
            while ($l = db_fetch_array($ql)) {
              $qrasql = sprintf('SELECT * FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',$l['ipv4_id'],$l['interface_id']);
              $qra = db_query($qrasql);

              // if orphan WDS link, delete
              // There was no ip for remote peer on the WDS link
              if ((db_num_rows($qra) == 0) and ($i['interface_type'] == 'wds/p2p')) {
                guifi_log(GUIFILOG_BASIC,sprintf('Going to DELETE detected orphan (no IP) WDS link when loading %d: %s',$id,$qrasql)); 
                $purge[] = sprintf('DELETE FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',$a['id'],$a['interface_id']);
                $purge[] = sprintf('DELETE FROM {guifi_links} WHERE id=%d',$l['id']);
                unset($device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]);
              } else {
                while ($ri = db_fetch_array($qra)) {
                  $rinterface = db_fetch_array(db_query('SELECT * FROM {guifi_interfaces} WHERE id=%d',$l['interface_id']));
//                  if (!is_numeric($rinterface[radiodev_counter])) {
                    // Interface is wireless, therefore, set to radio# if wasn't 
//                    $rinterface[radiodev_counter] = 0;
//                    db_query('UPDATE {guifi_interfaces} SET radiodev_counter=0 WHERE id=%d',$l[interface_id]);
//                    db_query('UPDATE {guifi_ipv4} SET id=0 WHERE interface_id=%d AND id is null',$l[interface_id]);
//                    db_query('UPDATE {guifi_links} SET ipv4_id=0 WHERE id=%d AND interface_id=%d',$l[id],$l[interface_id]);
//                  }
                  $ipdec2[$l['id']] = _dec_addr($ri['ipv4']);
                  $rinterface['ipv4']=$ri;
                  $l['interface']=$rinterface;
                  $iparr2[$l['id']] = $l;
                }
              } 
            } // each link
            asort($ipdec2);
            foreach ($ipdec2 as $ka2=>$foo) {
              $device['radios'][$radio['radiodev_counter']]['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$iparr2[$ka2]['id']] = $iparr2[$ka2];
            }
          }
        }
      }
    }
  } 

  // getting other interfaces
  $qi = db_query('SELECT * FROM {guifi_interfaces} WHERE device_id=%d AND (radiodev_counter is NULL OR interface_type NOT IN ("wLan","wds/p2p","Wan")) ORDER BY interface_type, id',$id);
  while ($i = db_fetch_array($qi)) {
    $device['interfaces'][$i['id']] = $i;

    // get ipv4
    $ipdec = array();
    $iparr = array();
    $qa = db_query('SELECT * FROM {guifi_ipv4} WHERE interface_id=%d',$i['id']);
    while ($a = db_fetch_array($qa)) {
      $ipdec[$a['id']] = _dec_addr($a['ipv4']);
      $iparr[$a['id']] = $a;
    }
    asort($ipdec); 
      
    foreach($ipdec as $ka=>$foo) {
      $a = $iparr[$ka];
      $device['interfaces'][$i['id']]['ipv4'][$a['id']] = $a;
    }
    // get linked devices
    $ql = db_query('SELECT l2.* FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l1.link_type NOT IN ("ap/client","wds/p2p") AND l1.device_id=%d AND l1.interface_id=%d AND l2.device_id!=%d',$id,$i['id'],$id);
    while ($l = db_fetch_array($ql)) {
      $ipdec2 = array();
      $iparr2 = array();
      $qra = db_query('SELECT * FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',$l['ipv4_id'],$l['interface_id']);
      while ($ra = db_fetch_array($qra)) {
        $ipdec2[$ra['id']] = _dec_addr($ra['ipv4']);
        $lr = $l;
        $lr['interface'] = db_fetch_array(db_query('SELECT * FROM {guifi_interfaces} WHERE id=%d',$l['interface_id']));
        $lr['interface']['ipv4'] = $ra;
        $iparr2[$ra['id']] = $lr;
      } 
      asort($ipdec2);
      foreach ($ipdec2 as $ka2=>$foo)
        $device['interfaces'][$i['id']]['ipv4'][$a['id']]['links'][$l['id']] = $iparr2[$ka2];
    }
  }

  // print_r($device); 

  // purge orphan data
  if (count($purge) > 0) foreach ($purge as $delete) {
    guifi_log(GUIFILOG_BASIC,sprintf('PURGE while loading %d: %s',$id,$delete));
    //db_query($delete);
  }
 
  if ($ret == 'array')
    return $device;
  else {
    foreach ($device as $k => $field)
      $var->$k = $field;
    return array2object($device);
  }
}

/**
 * Present the guifi device editing form.
 */
function guifi_edit_device_form($edit, $node = null) {


  if ($node == null)
    $node = node_load(array('nid'=>$edit['nid']));

  $form = '<div class="node-form">';
  // default to all current values, just in case we miss some in the form
  $form .= guifi_form_hidden(null,$edit);

  $rows[] = array(
                  array('data'=>form_textfield(t('nick'), 'nick', $edit['nick'], 20, 128, t('The name of the device.<br />Used ad hostname, SSID, etc...'), NULL, TRUE),'valign'=>'top'),
                  array('data'=>form_textfield(t('contact'), 'contact', $edit['contact'], 20, 128, t('Mailid where changes on the device will be notified,<br />used for network administration.'), NULL, TRUE),'valign'=>'top'),
                  array('data'=>form_select(t('Status'), 'flag', $edit['flag'], guifi_types('status'), t('Current status of this device.')),'valign'=>'top'),
                 );
  $form .=  form_group(t('Main device information'),theme('table',null,$rows));
  unset($rows);
  if (user_access('administer guifi zones') and $edit['type'] == 'radio') {
    $rows[] = array(
                  array('data'=>form_select(t("Server which collects traffic and availability data"), "graph_server", ($edit['graph_server'] ? $edit['graph_server'] : 0), array('0'=>t('Default'),'-1'=>t('None')) + guifi_services_select('SNPgraphs'), t("If not specified, inherits node properties."))),
                   );
    $form .=  form_group(t('Monitoring parameters'),theme('table',null,$rows));
  }


  $form .= "</div>\n";

  if (function_exists('guifi_'.$edit['type'].'_form'))
    $form .= call_user_func('guifi_'.$edit['type'].'_form',$edit);

  // Cable interfaces/links
  $form .= guifi_interface_form($edit);

  if (!isset($edit['edit_details'])) {
    $form .= form_textarea(t('Comments'), 'comment', $edit['comment'], 70, 5, t('This text will be displayed as an information of the device.'), NULL, TRUE);
    $form .= form_submit(t('Reset'));
    $form .= form_button(t('Validate'),'op');
    $form .= form_button(t('Save & continue edit'),'op');
    $form .= form_button(t('Save & exit'),'op');
  }

  drupal_set_title($edit['nick']);

  return form($form);
}

/**
 * Confirm that an edited guifi item has fields properly filled in.
 */
function guifi_edit_device_validate(&$edit) {
  
  global $user;

//  print "Valido el device";
//  print_r($edit);

  // nick
  guifi_validate_nick($edit['nick']);

  $query = db_query("SELECT nick FROM {guifi_devices} WHERE lcase(nick)=lcase('%s') AND id <> %d",strtolower($edit['nick']),$edit['id']);
  if (db_num_rows($query))
     form_set_error('nick', t('Nick already in use.'));

  // contact
  if (empty($edit['contact'])) {
     form_set_error('contact', t('You must set a contact adrdress for this device.'));
  }
  if (!valid_email_address($edit['contact'])) {
    $message = t('The e-mail address %mail is not valid. Changed to your drupal email address.', array('%mail' => theme('placeholder', $edit['contact'])));
    $edit['contact'] = $user->mail;
    form_set_error('contact',$message);
  }

  // ssid
  if (empty($edit['ssid'])) {
    $edit['ssid'] = $edit['nick'];
  }

  // duplicated ip address
  if (!empty($edit['ipv4'])) {
    if (db_num_rows(db_query("SELECT i.id FROM {guifi_interfaces} i,{guifi_ipv4} a WHERE i.id=a.interface_id AND a.ipv4='%s' AND i.device_id != %d",$edit['ipv4'],$edit['id']))) {
      $message = t('IP %ipv4 already taken in the database. Choose another or leave the address blank.', array('%ipv4' => theme('placeholder', $edit['ipv4'])));
      form_set_error('ipv4',$message);
    }
  }

  // Validates the mac address
  // radio MACs
  if (isset($edit['radios'])) foreach ($edit[radios] as $radio_id=>$radio) 
  if (!empty($radio['mac'])) {
    $mac = _guifi_validate_mac($radio['mac']);
    if ($mac) {
      $edit[radios][$radio_id]['mac'] = $mac;
      if ($edit[radios][$radio_id][interfaces]) foreach ($edit[radios][$radio_id][interfaces] as $k=>$foo)
        $edit[radios][$radio_id][interfaces][$k][mac]=$mac;
    } else {
      form_set_error('radios]['.$radio_id.'][mac',t('Error in MAC address, use 00:00:00:00:00:00 format.'));
    }
  }
  if (!empty($edit['mac'])) {
    $mac = _guifi_validate_mac($edit['mac']);
    if ($mac) {
      $edit['mac'] = $mac;
    } else {
      form_set_error('mac',t('Error in MAC address, use 00:00:00:00:00:00 format.'));
    }
  }


  // callback to device specific validation routines if there are
  if (function_exists('guifi_'.$edit['type'].'_validate'))
    $form .= call_user_func('guifi_'.$edit['type'].'_validate',$edit);

  guifi_links_validate($edit);
}

function guifi_save_interfaces($edit,$var,$radiodev_counter = null,$cascade = false) {

      unset($deletes);

      // Updating interfaces
      if (isset($var[interfaces])) foreach ($var[interfaces] as $interface_id=>$interface) {

        guifi_log(GUIFILOG_FULL,sprintf('interface (%d) %s-%s to be processed.',$interface_id,$interface[interface_type],$interface[mac]),$interface);
        guifi_log(GUIFILOG_BASIC,sprintf('interface (%d) %s-%s to be processed.',$interface_id,$interface[interface_type],$interface[mac]));

        if (($interface['deleted']) or ($cascade)) {
            $cascade=true;
            if ($interface['deleted'])
              $cascade_interface=true;
            $deletes[] = sprintf('DELETE FROM {guifi_interfaces} WHERE id=%d',$interface_id);
        } else 
        if ($interface['new'])  {
          $new_id=db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_interfaces}'));
          $interface[id]=$new_id[id];
         
          if (is_numeric($radiodev_counter)) 
            db_query('INSERT INTO {guifi_interfaces} (id, device_id, radiodev_counter, interface_type, mac) VALUES (%d, %d, %d, "%s", "%s")',$interface[id],$edit[id],$radiodev_counter,$interface[interface_type],$interface[mac]);
          else
            db_query('INSERT INTO {guifi_interfaces} (id, device_id, interface_type, mac) VALUES (%d, %d, "%s", "%s")',$interface[id],$edit[id],$interface[interface_type],$interface[mac]);

        } else {
          if (is_numeric($radiodev_counter )) {
            db_query('UPDATE {guifi_interfaces} SET radiodev_counter=%d, interface_type="%s",mac="%s" WHERE id=%d',$radiodev_counter,$interface[interface_type],$interface[mac],$interface_id);
          } else
            db_query('UPDATE {guifi_interfaces} SET interface_type="%s",mac="%s" WHERE id=%d',$interface[interface_type],$interface[mac],$interface_id);
        }

        // Updating interface ipv4
        if (isset($interface[ipv4])) foreach ($interface[ipv4] as $ipv4_id=>$ipv4) {
          if (($ipv4['deleted']) or ($cascade)) {
//            print "Delete ipv4: ".$ipv4[delete].' cascade: '.$cascade.' ipv4_id: '.$ipv4[id].' interface_id: '.$ipv4[interface_id]."\n<br />";
            $cascade=true;
            if ($ipv4['deleted'])
              $cascade_ipv4=true;
            if (!$ipv4['new'])
              $deletes[] = sprintf('DELETE FROM {guifi_ipv4} WHERE id=%d AND interface_id=%d',$ipv4[id],$ipv4[interface_id]);
          } else 
          if ($ipv4['new']) 
            db_query('INSERT INTO {guifi_ipv4} (id, interface_id, ipv4, netmask) VALUES (%d, %d, "%s", "%s")',$ipv4_id,$interface[id],$ipv4[ipv4],$ipv4[netmask]);
          else {
            db_query('UPDATE {guifi_ipv4} SET ipv4="%s", netmask="%s" WHERE id=%d AND interface_id=%d',$ipv4[ipv4],$ipv4[netmask],$ipv4[id],$interface[id]);
            guifi_log(GUIFILOG_FULL,sprintf('ipv4 uptaded: %s/%s',$ipv4['ipv4'],$ipv4['netmask']));
          }
          // Update links (rocal & remote)
          if (isset($ipv4[links])) foreach ($ipv4[links] as $link_id=>$link) {
            if (($link['deleted']) or ($cascade)) {
              $cascade=true;
              if ($link['deleted'])
                $cascade_links=true;
              if (!$link['new']) {
                db_query('DELETE FROM {guifi_links} WHERE id=%d',$link_id);
                // if link type was WDS, delete cascade remote interface/ipv4
                switch ($link[link_type])   {
                  case 'ap/client': 
                    if ($interface[interface_type] == 'Wan') 
                      break;
//                    else
//                      $deletes[] = sprintf('DELETE FROM {guifi_interfaces} WHERE id=%d',$link[interface_id]);
                  case 'wds': 
                    $deletes[] = sprintf('DELETE FROM {guifi_ipv4} WHERE id=%d and interface_id=%d',$link[ipv4_id],$link[interface_id]);
                }
              }
            } else {

              // that's a remote interface. The remote interface might exist, so first we have to check if is already on the database
              if ($link['interface'][radiodev_counter] >= 0) {
                $sql = sprintf('SELECT id FROM {guifi_interfaces} WHERE device_id=%d and radiodev_counter=%d and interface_type="%s"',$link[device_id],$link['interface'][radiodev_counter],$link['interface'][interface_type]);
              } else {
                $sql = sprintf('SELECT id,radiodev_counter FROM {guifi_interfaces} WHERE device_id=%d and interface_type="%s"',$link[device_id],$link['interface'][interface_type]);
              }
              $qremotei = db_query($sql);
              $remote_interface = db_fetch_array($qremotei);
              guifi_log(GUIFILOG_TRACE,sprintf('Remote link: '),$link);
              guifi_log(GUIFILOG_TRACE,sprintf('sql'),$sql);
              guifi_log(GUIFILOG_TRACE,sprintf('remote_interface'),$remote_interface);

              if ($link['new']) {
                $lnew = db_fetch_array(db_query('SELECT max(id)+1 newid FROM {guifi_links}'));
                db_query('INSERT INTO {guifi_links} (id, nid, device_id, interface_id, ipv4_id, link_type, routing, flag) VALUES (%d, %d, %d, %d, %d, "%s", "%s", "%s")',$lnew[newid],$edit[nid],$edit[id],$interface[id],$ipv4_id,$link[link_type],$klink[routing], $link[flag]);

//              if ($link['interface']['new']) {
                if (($link['interface']['new']) and ($remote_interface[id] == null)) {
                  guifi_log(GUIFILOG_BASIC,sprintf('Creating remote link for: '),$link);
                  // There was no interface on the database, so going to  create it
                  $inew = db_fetch_array(db_query('SELECT max(id)+1 newid FROM {guifi_interfaces}'));
                  $link[interface_id] = $inew[newid];
                  if ($link['interface'][radiodev_counter] != null)
                    db_query('INSERT INTO {guifi_interfaces} (id,device_id, radiodev_counter, interface_type, mac) VALUES (%d, %d, %d, "%s", "%s")',$link[interface_id],$link[device_id],$link['interface'][radiodev_counter],$link['interface'][interface_type],$link['interface'][mac]);
                  else
                    db_query('INSERT INTO {guifi_interfaces} (id,device_id, interface_type, mac) VALUES (%d, %d, "%s", "%s")',$link[interface_id],$link[device_id],$link['interface'][interface_type],$link['interface'][mac]);
                } else {
                  if ($remote_interface[id] != null) {
                    // Probably a new ip address might be added/updated, so need to know to which interface
                    $link[interface_id] = $remote_interface[id];
                  }
                  guifi_log(GUIFILOG_BASIC,sprintf('Link updated'),$link);
                }
               
//              }


                if ($link['interface'][ipv4]['new']) {
                  db_query('INSERT INTO {guifi_ipv4} (id, interface_id, ipv4, netmask) VALUES (%d, %d, "%s", "%s")',$link[ipv4_id],$link[interface_id],$link['interface'][ipv4][ipv4],$link['interface'][ipv4][netmask]);
                }

              db_query('INSERT INTO {guifi_links} (id, nid, device_id, interface_id, ipv4_id, link_type, routing, flag) VALUES (%d, %d, %d, %d, %d, "%s", "%s", "%s")',$lnew[newid],$link[nid],$link[device_id],$link[interface_id],$link[ipv4_id],$link[link_type],$link[routing],$link[flag]);

              } else {
                db_query('UPDATE {guifi_links} SET ipv4_id=%d, link_type="%s", routing="%s", flag="%s" WHERE id=%d and interface_id=%d',$ipv4[id],$link[link_type],$link[routing], $link[flag],$link[id],$interface_id);
                db_query('UPDATE {guifi_links} SET ipv4_id=%d, link_type="%s", routing="%s", flag="%s" WHERE id=%d and interface_id=%d',$link[ipv4_id],$link[link_type],$link[routing], $link[flag],$link[id],$link[interface_id]);

                if ($link['link_type'] == 'cable') {
                  // Updtating remote interface_type when link type is cable
                  if ($link['interface'][radiodev_counter] != null) 
                    db_query('UPDATE {guifi_interfaces} SET radiodev_counter=%d, interface_type="%s" WHERE id=%d',$link['interface'][radiodev_counter],$link['interface'][interface_type],$link['interface_id']);
                  else
                    db_query('UPDATE {guifi_interfaces} SET interface_type="%s" WHERE id=%d',$link['interface'][interface_type],$link['interface_id']);
                }
  
                db_query('UPDATE {guifi_ipv4} SET ipv4="%s", netmask="%s" WHERE id=%d AND interface_id=%d',$link['interface'][ipv4][ipv4],$link['interface'][ipv4][netmask],$link[ipv4_id],$link[interface_id]);
              
              }
          
            } // remote interface
            if ($cascade_links)
              $cascade=false;
            unset($cascade_links);
          } // update links 
          if ($cascade_ipv4)
            $cascade=false;
          unset($cascade_ipv4);
        } // Update ipv4
        if ($cascade_interface)
          $cascade=false;
        unset($cascade_interface);
      } // Update interface


  // purge orphan data
  if (count($deletes) > 0) foreach ($deletes as $delete) {
    guifi_log(GUIFILOG_BASIC,sprintf('DELETE while editing %d: %s',$edit['id'],$delete));
    db_query($delete);
  }

}

/**
 * Save changes to a guifi item into the database.
 */
function guifi_edit_device_save($edit) {

  global $user;

  $main_itype = guifi_main_interface($edit['mode']);

  if ($edit['id']) {
    // Checking if nick has changed, so rrd graph files shoud be renamed
    $pdevice = guifi_get_device($edit['id']);
    if ($pdevice['nick'] != $edit['nick']) {
      guifi_rename_graphs($pdevice['nick'],$edit['nick']);
    }

    db_query("UPDATE {guifi_devices} SET nick = '%s', type = '%s', graph_server=%d, contact = '%s', mac = '%s', comment = '%s', flag = '%s', extra = '%s', user_changed = %d, timestamp_changed = %d WHERE id = %d", $edit['nick'], $edit['type'], $edit['graph_server'], $edit['contact'], $edit['mac'], $edit['comment'], $edit['flag'], serialize($edit['variable']), $user->uid, time(), $edit['id']);
    

    $cascade = false;
    // Updating radios
    if (isset($edit[radios])) foreach ($edit[radios] as $radiodev_counter=>$radio) {
      if ($radio['deleted']) {
        db_query('DELETE FROM {guifi_radios} WHERE id=%d AND radiodev_counter=%d',$radio[id],$radiodev_counter);
        $cascade_radio = true;
        $cascade = true;
      } else
      if ($radio['new']) {
        // Insert a new radio
//        print "Inserting new radio # $radiodev_counter\n<br />";
        db_query("INSERT INTO {guifi_radios} (id, nid, model_id, radiodev_counter, ssid, mode, protocol, channel, antenna_angle, antenna_gain, antenna_azimuth,clients_accepted, antmode) VALUES (%d, %d, %d, %d, '%s','%s','%s','%s', %d, %d, %d,'%s','%s')", $edit[id], $edit['nid'], $edit['variable']['model_id'], $radiodev_counter, $radio['ssid'], $radio['mode'], $radio['protocol'], $radio['channel'],$radio['antenna_angle'],$radio['antenna_gain'],$radio['antenna_azimuth'],$radio['clients_accepted'],$radio['antmode']);
      } else {
        db_query("UPDATE {guifi_radios} SET model_id = %d, radiodev_counter=%d, ssid ='%s', mode ='%s', protocol ='%s', channel ='%s', antenna_angle =%d, antenna_gain =%d, antenna_azimuth =%d, clients_accepted='%s', antmode='%s' WHERE id = %d AND radiodev_counter=%d", $edit['variable']['model_id'], $radiodev_counter, $radio['ssid'], $radio['mode'], $radio['protocol'], $radio['channel'],$radio['antenna_angle'],$radio['antenna_gain'],$radio['antenna_azimuth'],$radio['clients_accepted'], $radio['antmode'], $radio['id'],$radiodev_counter );
      }
      guifi_save_interfaces($edit,$radio,$radiodev_counter,$cascade);
     $cascade=false;
    } 

    guifi_save_interfaces($edit,$edit,'',false);
    
    guifi_log(GUIFILOG_BASIC,sprintf('device (%s) %d-%s updated.',$edit['type'],$edit['id'],$edit['nick']));

    drupal_set_message(t('Updated guifi device %nick.', array('%nick' => theme('placeholder', $edit['nick']))));
  } else {
    $next_id = db_fetch_array(db_query('SELECT max(id)+1 id FROM {guifi_devices}'));
    $edit[id] = $next_id[id];
    db_query("INSERT INTO {guifi_devices} ( id, nid, nick, type, graph_server, contact, mac, comment, flag, extra, user_created, timestamp_created) VALUES (%d, %d, '%s','%s',%d, '%s','%s','%s','%s', '%s', '%d','%d')", $edit[id], $edit['nid'], $edit['nick'], $edit['type'], $edit['graph_server'], $edit['contact'], $edit['mac'], $edit['comment'], $edit['flag'], serialize($edit['variable']),  $user->uid, time());


    guifi_log(GUIFILOG_BASIC,sprintf('device (%s) %d-%s created.',$edit['type'],$edit[id],$edit['nick']));
    drupal_set_message(t('Created new guifi device %name.', array('%name' => theme('placeholder', $edit['nick']))));
  
  } // End foreach radio
//  print "Save done.";
//  exit;

  guifi_set_node_flag($edit['nid']);
//  touch(variable_get('guifi_rebuildmaps','/tmp/ms_tmp/REBUILD'));
  variable_set('guifi_refresh_cnml',time());
  variable_set('guifi_refresh_maps',time());
  cache_clear_all();

  return $edit[id];
}

/**
 * outputs the device information data
**/
function guifi_device_print_data($device) {

  $name_created = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $device[user_created]));
  $name_changed = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $device[user_changed]));
  
  $radios = db_query('SELECT * FROM {guifi_radios} WHERE id=%d ORDER BY id',$device['id']);

  $rows[] = array(t('name'),'<b>' .$device[nick] .'</b>'); 
  $rows[] = array(t('type'),t($device[type])); 

  // If radio, going to list all device radios
  if (count($device['radios'])) {
     
    unset($rowsr);
    
    $querymid = db_query("SELECT mid, model, f.nom manufacturer FROM guifi_model m, guifi_manufacturer f WHERE f.fid = m.fid AND supported='Yes'");
    while ($model = db_fetch_array($querymid)) {
      $models_array[$model["mid"]] = $model["manufacturer"] .", " .$model["model"];
    }
    
    foreach ($device['radios'] as $radio_id=>$radio) {
      $rowsr[] = array($radio['ssid'],$radio['mode'],$radio['protocol'],$radio['channel'],$radio['mac'],$radio['clients_accepted']);
    }
    $rows[] =  array(array('data'=>theme('table',array(t('ssid'),t('mode'),t('protocol'),t('ch'),t('wireless mac'),t('clients')),$rowsr),'colspan'=>2));
    $rows[] =  array($models_array[$device["variable"]["model_id"]],$device['variable']['firmware']);
  }

  // If ADSL, print characteristics
  if (($device['type'] == 'ADSL') and ($device['variable'] != '')) {
    $bandwidth = guifi_bandwidth_types();
    $rows[] = array(t('bandwidth'),$bandwidth[$device['variable']['download']].'/'.$bandwidth[$device['variable']['upload']]);
    $rows[] = array(t('SNMP index to graph'),$device['variable']['mrtg_index']);
  }
  if (($device['type'] == 'generic') and ($device['variable'] != '')) {
    $rows[] = array(t('SNMP index to graph'),$device['variable']['mrtg_index']);
  }
  switch ($device['graph_server']) {
  case -1:
    $graphtxt = t('Graphs disabled.');
    break;
  case 0:
  case NULL:
    $graphtxt = t('Default: Obtained from node');
    break;
  default:
    $qgs = db_query(sprintf('SELECT nick FROM {guifi_services} WHERE id=%d',$device['graph_server']));
    $gs = db_fetch_object($qgs);
    if (!empty($gs->nick)) {
      $graphtxt = '<a href="/node/'.$device['graph_server'].'">'.$gs->nick.'</a>';
    } else
      $graphtxt = t('invalid');
  }
  $rows[] = array(t('graphs provided from'),array('data'=>$graphtxt,'colspan'=>2));


  $ip = guifi_main_ip($device[id]);
  if (!empty($ip->ipv4)) {
    $rows[] = array(t('IP address'),$ip[ipv4].'/'.$ip[maskbits]);
  }
  $rows[] = array(t('mac'),t($device[mac])); 

  $graph_url = guifi_radio_get_url_mrtg($device['id'],FALSE);
  if ($graph_url != NULL)
    $img_url = ' <img src='.$graph_url.'?device='.$device['id'].'&type=availability&format=long>';
  else
    $img_url = NULL;

  $rows[] = array(t('status &#038; availability'),array('data' => t($device[flag]).$img_url,'class' => $device['flag']));
  if ($device[contact])
    $rows[] = array(t('changes notified to'),t('protected, edit to view')); 
  $rows[] = array(null,null);
  $rows[] = array(null,t('log information'));
  if ($device[timestamp_created] > 0) 
    $rows[] = array(t('created by'),$name_created->name .'&nbsp;' .t('at') .'&nbsp;' .format_date($device[timestamp_created])); 
  if ($device[timestamp_changed] > 0) 
    $rows[] = array(t('updated by'),$name_changed->name .'&nbsp;' .t('at') .'&nbsp;' .format_date($device[timestamp_changed])); 

  return array_merge($rows);
}


/**
 * outputs the device links data
**/
function guifi_links_print_data($id) {
  $query = db_query("SELECT i.*,a.ipv4,a.netmask FROM {guifi_interfaces} i, {guifi_ipv4} a WHERE i.id=a.interface_id AND i.device_id=%d ORDER BY i.interface_type",$id);
  while ($if = db_fetch_object($query)) {
    $rows[] = array($if->interface_type,$if->ipv4.'/'.$ip['netid'],$if->netmask,$if->mac);
  }
  return array_merge($rows);
}
  
/**
 * outputs the device interfaces data
**/
function guifi_interfaces_print_data($id) {
  $rows = array();
  $query = db_query("SELECT i.*,a.ipv4,a.netmask, a.id ipv4_id FROM {guifi_interfaces} i, {guifi_ipv4} a WHERE i.id=a.interface_id AND i.device_id=%d ORDER BY i.interface_type",$id);
  while ($if = db_fetch_object($query)) {
    $ip = _ipcalc($if->ipv4,$if->netmask);
    $rows[] = array($if->id.'/'.$if->ipv4_id,$if->interface_type,$if->ipv4.'/'.$ip['maskbits'],$if->netmask,$if->mac);
  }
  return array_merge($rows);
}
  
/**
 * outputs the device information
**/
function guifi_device_print($id) {
//  print_r($_GET);
//  print arg(0)."\n<br />";
//  print arg(1)."\n<br />";
//  print arg(2)."\n<br />";
//  print arg(3)."\n<br />";
//  print arg(4)."\n<br />";


  $output = '<div id="guifi">';

  $device = guifi_get_device($id);
  if (empty($device))
    return print theme('page',null,t('device').': '.$id);
    
  $node = node_load(array('nid' => $device[nid])); 
  
  $title = t('Node:').' <a href="node/'.$node->nid.'">'.$node->nick.'</a> &middot; '.t('Device:').'&nbsp;'.$device[nick];
  $output .= guifi_zone_ariadna($node->zone_id);

  switch (arg(4)) {
  case 'all': case 'data': default:
    $table = theme('table', NULL, guifi_device_print_data($device));
    $output .= theme('box', $title, $table);
    if (arg(4) == 'data') break;
  case 'graphs':
    // device graphs
    $table = theme('table', array(t('traffic overview')), guifi_device_graph_overview($device));
    $output .= theme('box', t('device graphs'), $table);
    if (arg(4) == 'graphs') break;
  case 'links':
    // links
    $output .= theme('box', NULL, guifi_device_links_print($device));
    if (arg(4) == 'links') break;
  case 'interfaces':
    $header = array(t('id'),t('type'),t('ip address'),t('netmask'),t('mac'));
    $table = theme('table', $header, guifi_interfaces_print_data($device[id]));
    $output .= theme('box', t('interfaces information'), $table);
    break;
  case 'services':
    $output .= theme('box', t('services information'), guifi_list_services($device[id]));
    break;
  }
  
  $output .= '</div>';

  $title = t('Node:').' <a href="node/'.$node->nid.'">'.$node->nick.'</a> &middot; '.t('Device:').'&nbsp;'.$device[nick];
  
  drupal_set_title($device['nick']);

  return print theme('page',$output,t('device').': '.$device[nick]);
}

function guifi_device_links_print($device,$ltype = '%') {
  $oGC = new GeoCalc();
  $dtotal = 0;
  $ltotal = 0;
  if ($ltype == '%')
    $title = t('links');
  else
  $title = t('links').' ('.$ltype.')';

//  print_r($device);
  unset($rows);
  unset($rows_wds);
  unset($rows_ap_client);
  unset($rows_cable);

  $rows_wds[] = array(array('data'=>'<strong>'.t('bridge wds/p2p').'</strong>','colspan'=>2));
  $rows_ap_client[] = array(array('data'=>'<strong>'.t('ap/client').'</strong>','colspan'=>2));
  $rows_cable[] = array(array('data'=>'<strong>'.t('cable').'</strong>','colspan'=>2));
  $rows=array();
  $loc1 = db_fetch_object(db_query('SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',$device['nid']));
  $graph_url = guifi_radio_get_url_mrtg($device['id'],FALSE);
  switch ($ltype) {
  case '%':
  case 'wds':
  case 'ap/client':
    if ($device['radios']) foreach ($device['radios'] as $radio_id=>$radio) 
    if ($radio['interfaces']) foreach ($radio['interfaces'] as $interface_id=>$interface) 
    if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id=>$ipv4) 
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id=>$link) {
      $loc2 = db_fetch_object(db_query('SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',$link['nid']));
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
      $dAz = round($oGC->GCAzimuth($loc1->lat, $loc1->lon, $loc2->lat,$loc2->lon));
          // Calculo orientacio
          if ($dAz < 23) $dOr =t("N"); else
          if ($dAz < 68) $dOr =t("NE"); else
          if ($dAz < 113) $dOr =t("E"); else
          if ($dAz < 158) $dOr =t("SE"); else
          if ($dAz < 203) $dOr =t("S"); else
          if ($dAz < 248) $dOr =t("SW"); else
          if ($dAz < 293) $dOr =t("W"); else
          if ($dAz < 338) $dOr =t("NW"); else
            $dOr =t("N");
      $item = _ipcalc( $ipv4['ipv4'],  $ipv4['netmask']);
      $ipdest = explode('.',$link['interface']['ipv4']['ipv4']);
      if ($graph_url != NULL)
        $img_url = ' <img src='.$graph_url.'?device='.$link['device_id'].'&type=availability&format=short>';
      else
        $img_url = NULL;

      $cr = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id=%d",$link['device_id']));
      if ($cr->count > 1) {
        $rn = db_fetch_object(db_query("SELECT ssid FROM {guifi_radios} r WHERE r.id=%d AND r.radiodev_counter=%d",$link['device_id'],$link['interface']['radiodev_counter']));
        $dname = guifi_get_hostname($link['device_id']).'-'.$rn->ssid;
      }
      else
        $dname = guifi_get_hostname($link['device_id']);
      $wrow = array('&nbsp;',array('data'=>$link_id,'align'=>'right'),
                    '<a href="/guifi/device/'.$link['device_id'].'">'.$dname.'</a>',
                    '<a href="/node/'.$link['nid'].'">'.$loc2->nick.'</a>',
                    $ipv4['ipv4'].'/'.$item['maskbits'],'.'.$ipdest[3],
                    array('data' => t($link['flag']).$img_url,
                          'class' => $link['flag']),
                    $link[routing],
                    $gDist,
                    $dAz.'-'.$dOr);  
      if ($interface['interface_type'] == 'wds/p2p')
        $rows_wds[] = $wrow;
      if ($link['link_type'] == 'ap/client')
        $rows_ap_client[] = $wrow;
      $dtotal = $dtotal + $gDist;;
      $ltotal++;
    }
    if ($ltype != '%') break;
  case 'cable':
    if ($device['interfaces']) foreach ($device['interfaces'] as $interface_id=>$interface) 
    if ($interface['ipv4']) foreach ($interface['ipv4'] as $ipv4_id=>$ipv4) 
    if ($ipv4['links']) foreach ($ipv4['links'] as $link_id=>$link) {
      $loc2 = db_fetch_object(db_query('SELECT lat, lon, nick FROM {guifi_location} WHERE id=%d',$link['nid']));
      $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
      $item = _ipcalc( $ipv4['ipv4'],  $ipv4['netmask']);
      $ipdest = explode('.',$link['interface']['ipv4']['ipv4']);
      if ($graph_url != NULL)
        $img_url = ' <img src='.$graph_url.'?device='.$link['device_id'].'&type=availability&format=short>';
      else
        $img_url = NULL;
      $rows_cable[] = array($interface['interface_type'].'/'.$link['interface']['interface_type'],
                       array('data'=>$link_id,'align'=>'right'),
                       '<a href="/guifi/device/'.$link['device_id'].'">'.guifi_get_hostname($link['device_id']).'</a>',
                       array('data'=>'-','align'=>'center'),
                       $ipv4['ipv4'].'/'.$item['maskbits'],'.'.$ipdest[3],
                       array('data' => t($link['flag']). $img_url,
                             'class' => $link['flag']),
                       $link[routing],
                       array('data'=>'-','align'=>'center') , 
                       array('data'=>'-','align'=>'center'));  
      $ltotal++;
    }
    if ($ltype == 'cable') break;
  } 

  if (count($rows_wds)> 1)  
    $rows = $rows_wds;
  if (count($rows_ap_client) > 1) 
    $rows = array_merge($rows_ap_client,$rows);
  if (count($rows_cable) > 1) 
    $rows = array_merge($rows,$rows_cable);
  return '<h2>'.$title.'</h2>'.
         '<h3>'.t('Totals').': '.$ltotal.' '.t('links').', '.$dtotal.' '.t('kms.').'</h3>'.
         theme('table',array(t('interface'),t('id'),t('device'),t('node'),t('ip address'),'&nbsp;',t('status'),t('routing'),t('kms.'),t('az.')),$rows);
}

function guifi_device_link_list($id = 0, $ltype = '%') {
  $oGC = new GeoCalc();

  $total = 0;
  if ($ltype == '%')
    $title = t('links');
  else
  $title = t('links').' ('.$ltype.')';
 
  $header = array(t('type'),t('linked devices'), t('ip'), t('status'), t('routing'), t('kms.'),t('az.'));

  $queryloc1 = db_query("SELECT c.id, c.link_type, c.routing, l.nick, c.device_id, d.nick device_nick, a.ipv4 ip, i.interface_type itype, c.flag, l.lat, l.lon FROM {guifi_links} c LEFT JOIN {guifi_devices} d ON c.device_id=d.id LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=c.ipv4_id LEFT JOIN {guifi_location} l ON d.nid = l.id WHERE c.device_id = %d AND link_type like '%s' ORDER BY c.link_type, c.device_id",$id,$ltype);
  if (db_num_rows($queryloc1)) {
    while ($loc1 = db_fetch_object($queryloc1)) {
      $queryloc2 = db_query("SELECT c.id, l.nick, r.ssid, c.device_id, d.nick device_nick, a.ipv4 ip, i.interface_type itype, l.lat, l.lon FROM {guifi_links} c LEFT JOIN {guifi_devices} d ON c.device_id=d.id LEFT JOIN {guifi_interfaces} i ON c.interface_id = i.id LEFT JOIN {guifi_ipv4} a ON i.id=a.interface_id AND a.id=c.ipv4_id LEFT JOIN {guifi_location} l ON d.nid = l.id LEFT JOIN {guifi_radios} r ON d.id=r.id AND i.radiodev_counter=r.radiodev_counter WHERE c.id = %d AND c.device_id != %d",$loc1->id,$loc1->device_id);
      while ($loc2 = db_fetch_object($queryloc2)) {
        $gDist = round($oGC->EllipsoidDistance($loc1->lat, $loc1->lon, $loc2->lat, $loc2->lon),3);
        if ($gDist) {
          $total = $total + $gDist;
          $dAz = round($oGC->GCAzimuth($loc1->lat, $loc1->lon, $loc2->lat,$loc2->lon));
          // Calculo orientacio
          if ($dAz < 23) $dOr =t("N"); else
          if ($dAz < 68) $dOr =t("NE"); else
          if ($dAz < 113) $dOr =t("E"); else
          if ($dAz < 158) $dOr =t("SE"); else
          if ($dAz < 203) $dOr =t("S"); else
          if ($dAz < 248) $dOr =t("SW"); else
          if ($dAz < 293) $dOr =t("W"); else
          if ($dAz < 338) $dOr =t("NW"); else
            $dOr =t("N");
        }
        else
          $gDist = 'n/a';

        $cr = db_fetch_object(db_query("SELECT count(*) count FROM {guifi_radios} r WHERE id=%d",$loc2->device_id));
        if ($cr->count > 1)
          $dname = $loc2->device_nick.'/'.$loc2->ssid;
        else
          $dname = $loc2->device_nick;

        $rows[] = array($loc1->id.'-'.$loc1->link_type.' ('.$loc1->itype.'-'.$loc2->itype.')','<a href="guifi/device/'.$loc2->device_id.'">'.$dname.'</a>',
                     $loc1->ip.'/'.$loc2->ip,
                   array('data' => t($loc1->flag), 'class' => $loc1->flag),
                   array('data' => $gDist,'class' => 'number'),
                   $loc1->routing,
                   $dAz.'-'.$dOr);
      }
    }
    $output .= theme('table', $header, $rows);
    $output = theme('box',$title,$output);
    if ($total)
      $output .= t('Total:').'&nbsp;'.$total.'&nbsp;'.t('kms.');
    return $output;
  }
  return NULL;
}

/**
 * guifi_device_create_form
 * generates html output form qith a lisbox, choose the 
 * device type to create
**/
function guifi_device_create_form($nid) {

  $types = guifi_types('device');

  foreach ($types as $key => $type) {
    if ($key == 'radio')
      $list .= '<option value="'.$key.'" selected>'.t($type).'</option>';
    else
       $list .= '<option value="'.$key.'">'.t($type).'</option>';
  }
  
  $action = url('guifi/add/device', NULL, NULL, FALSE);
  $output = '<form action="'.$action.'" method="post" onchange="submit" >';
  $output .= t('Add a new device. Type: ');
  $output .= '<select name="edit[type]">'.$list.'</select>';
  $output .= '<input type="submit" class="form-submit" name="op" value="'.t('add').'"  />';
  $output .= '<input type="hidden" name="edit[nid]" value="'.$nid.'"  />';
  $output .= '</form>';
 
  return $output;
}

function guifi_create_device($nid) {
  $form = guifi_device_create_form($nid);
  print theme('page',$form);
}

function guifi_bandwidth_types() {
  return    array(  '64000'=>'64k',
                               '128000'=>'128k',
                               '256000'=>'256k',
                               '512000'=>'512k',
                               '640000'=>'640k',
                              '1000000'=>'1M',
                              '2000000'=>'2M',
                              '4000000'=>'4M',
                              '8000000'=>'8M',
                             '20000000'=>'20M',
                             '40000000'=>'40M');
}

function guifi_ADSL_form($edit) {

  if (!isset($edit['variable']['download']))
    $edit['variable']['download'] = 4000000;
  if (!isset($edit['variable']['upload']))
    $edit['variable']['upload'] = 640000;
  $output .= form_select(t('Download'),'variable][download',$edit['variable']['download'],guifi_bandwidth_types(),
                              t('Download bandwidth'));
  $output .= form_select(t('Upload'),'variable][upload',$edit['variable']['upload'],guifi_bandwidth_types(),
                              t('Upload bandwidth'));
        
  $output .= form_textfield(t('MRTG config'), 'variable][mrtg_index', $edit['variable']['mrtg_index'], 2,5, t('SNMP interface index for getting traffic information of this device. User tools like cacti or snmpwalk to determine the index. Example:').'<br /><pre>snmpwalk -Os -c public -v 1 10.138.25.66 interface</pre>');
  return $output;
}

function guifi_generic_form($edit) {
  $output .= form_textfield(t('MRTG config'), 'variable][mrtg_index', $edit['variable']['mrtg_index'], 2,5, t('SNMP interface index for getting traffic information of this device. User tools like cacti or snmpwalk to determine the index. Example:').'<br /><pre>snmpwalk -Os -c public -v 1 10.138.25.66 interface</pre>');
  return $output;
}

?>
