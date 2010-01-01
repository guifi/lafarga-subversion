<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Eduard Duran <eduard.duran@iglu.cat>.
// It's licensed under the GENERAL PUBLIC LICENSE v2.0 unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.gnu.org/licenses/gpl-2.0.html
// GENERAL PUBLIC LICENSE v2.0 is also included in the file called "LICENSE.txt".

function guifi_api() {
  $gapi = new GuifiAPI();
  $gapi->parseRequest($_GET);
  $gapi->executeRequest();
  $gapi->printResponse();
  return null;
}

/**
 * Try to autenticate the user, using any method
 * 
 * @param GuifiAPI $gapi Guifi API
 * @param $parameters Parameters to login
 * @return boolean Whether the user authenticated or not
 */
function guifi_api_auth_login(&$gapi, $parameters) {
  global $user;
  
  if (!guifi_api_check_fields(&$gapi, array('method' ), $parameters)) {
    return false;
  }
  $method = $parameters['method'];
  
  switch ($method) {
    case 'password':
      if (!guifi_api_check_fields(&$gapi, array('username', 'password' ), $parameters)) {
        return false;
      }
      
      $account = user_load(array('name' => $parameters['username'], 'pass' => trim($parameters['password']), 'status' => 1 ));
      
      if ($account->uid) {
        $user = $account;
        $time = time();
        $rand_key = rand(100000, 999999);
        $token = base64_encode($user->uid . ':' . md5($user->mail . $user->pass . $user->created . $user->uid . $time . $rand_key) . ':' . $time);
        db_query("DELETE FROM {guifi_api_tokens} WHERE uid = %d", $user->uid);
        db_query("INSERT INTO {guifi_api_tokens} (uid, token, created, rand_key) VALUES (%d, '%s', FROM_UNIXTIME(%d), %d)", $user->uid, $token, $time, $rand_key);
        $gapi->addResponseField('authToken', $token);
        return true;
      } else {
        $gapi->addError(403, "Either the supplied username or password are not correct");
        return false;
      }
      
      break;
  }
  return false;
}

function _guifi_api_prepare_node($type, $title) {
  global $user;
  $edit = array();
  $edit['type'] = $type;
  $edit['name'] = $user->name;
  $edit['uid'] = $user->uid;
  $edit['comment'] = variable_get('comment_' . $edit['type'], 2);
  $edit['status'] = 1;
  $edit['format'] = FILTER_FORMAT_DEFAULT;
  $edit['title'] = $title;
  
  if (!node_access('create', $edit['type'])) {
    return false;
  }
  
  $node = node_submit($edit);
  return $node;
}

function _guifi_api_zone_check_parameters(&$gapi, &$parameters) {
  extract($parameters);
  
  if (isset($minx) || isset($maxx) || isset($miny) || isset($maxy)) {
    if (isset($minx) && isset($maxx) && isset($miny) && isset($maxy)) {
      if (!is_numeric($minx)) {
        $gapi->addError(403, "minx: $minx");
      }
      if (!is_numeric($maxx)) {
        $gapi->addError(403, "maxx: $maxx");
      }
      if (!is_numeric($miny)) {
        $gapi->addError(403, "miny: $miny");
      }
      if (!is_numeric($maxy)) {
        $gapi->addError(403, "maxy: $maxy");
      }
      if ($minx > $maxx || $miny > $maxy) {
        $gapi->addError(403, "Coordinates are wrong");
      }
    } else {
      $gapi->addError(403, "all coordinates should be specified");
    }
  }
  
  if (isset($ospf_zone)) {
    if (($ospf_zone != htmlentities($ospf_zone)) || str_word_count($ospf_zone) > 1) {
      $gapi->addError(403, "ospf_zone: $ospf_zone");
      return false;
    }
  }
  
  if (isset($notification)) {
    if (!guifi_notification_validate($notification)) {
      $gapi->addError(403, "notification: $notification");
      return false;
    }
  }
  
  if (isset($graph_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'SNPgraphs'", $graph_server));
    if (!$server->id) {
      $gapi->addError(403, "graph_server: $graph_server");
      return false;
    }
  }
  
  if (isset($proxy_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'Proxy'", $proxy_server));
    if (!$server->id) {
      $gapi->addError(403, "proxy_server: $proxy_server");
      return false;
    } else {
      $parameters['proxy_id'] = $proxy_server;
    }
  }
  
  if (isset($zone_mode)) {
    $zone_modes = array('infrastructre', 'ad-hoc' );
    if (!in_array($zone_mode, $zone_modes)) {
      $gapi->addError(403, "zone-mode: $zone_mode");
      return false;
    }
  }
  
  return true;
}

/**
 * Adds a Guifi Zone to the DB
 *
 * @param GuifiAPI $gapi
 * @param mixed $parameters Paramaters passed to specify zone properties
 */
function guifi_api_zone_add(&$gapi, $parameters) {
  global $user;
  
  if (!guifi_api_check_fields(&$gapi, array('title', 'master', 'minx', 'miny', 'maxx', 'maxy' ), $parameters)) {
    return false;
  }
  
  extract($parameters);
  
  $node = _guifi_api_prepare_node('guifi_zone', $title);
  
  // Set defaults
  $node->nick = guifi_abbreviate($title);
  $node->notification = $user->mail;
  $node->dns_servers = '';
  $node->ntp_servers = '';
  $node->graph_server = '';
  $node->homepage = '';
  $node->time_zone = '+01 2 2';
  $node->ospf_zone = '';
  
  if (!_guifi_api_zone_check_parameters($gapi, &$parameters)) {
    return false;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  
  if (!guifi_zone_access('create', $node)) {
    $gapi->addError(501);
    return false;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  
  node_save($node);
  
  $gapi->addResponseField('zone_id', $node->id);
  return true;
}

/**
 * Updates a Guifi Zone to the DB
 *
 * @param GuifiAPI $gapi
 * @param mixed $parameters Paramaters passed to specify zone properties
 */
function guifi_api_zone_update(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('zone_id' ), $parameters)) {
    return false;
  }
  
  extract($parameters);
  
  $node = node_load($zone_id);
  
  if (!_guifi_api_zone_check_parameters($gapi, &$parameters)) {
    return false;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  
  if (!guifi_zone_access('update', $node)) {
    $gapi->addError(501);
    return false;
  }
  
  if ($node->type != 'guifi_zone') {
    $gapi->addError(500, "zone_id = $node->id is not a zone");
    return false;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  node_save($node);
  
  return true;
}

function guifi_api_zone_remove(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('zone_id' ), $parameters)) {
    return false;
  }
  
  $node = node_load($parameters['zone_id']);
  
  if (!$node->id) {
    $gapi->addError(500, "zone_id = {$parameters['zone_id']}");
    return false;
  }
  
  if ($node->type != 'guifi_zone') {
    $gapi->addError(500, "zone_id = $node->id is not a zone");
    return false;
  }
  
  if (node_access('delete', $node) && guifi_zone_access('update', $node)) {
    node_delete($node->id);
  } else {
    $gapi->addError(501);
    return false;
  }
  
  return true;
}

function _guifi_api_node_check_parameters(&$gapi, &$parameters) {
  extract($parameters);
  
  if (isset($lat) || isset($lon)) {
    if (isset($lat) && isset($lon)) {
      if (!is_numeric($lat)) {
        $gapi->addError(403, "lat: $lat");
      }
      if (!is_numeric($lon)) {
        $gapi->addError(403, "lon: $lon");
      }
    } else {
      $gapi->addError(403, "all coordinates should be specified");
    }
  }
  
  if (isset($notification)) {
    if (!guifi_notification_validate($notification)) {
      $gapi->addError(403, "notification: $notification");
      return false;
    }
  }
  
  if (isset($graph_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'SNPgraphs'", $graph_server));
    if (!$server->id) {
      $gapi->addError(403, "graph_server: $graph_server");
      return false;
    }
  }
  
  return true;
}

function guifi_api_node_add(&$gapi, $parameters) {
  global $user;
  
  if (!guifi_api_check_fields(&$gapi, array('title', 'zone_id', 'lat', 'lon' ), $parameters)) {
    return false;
  }
  
  extract($parameters);
  
  $title = $parameters['title'];
  
  $node = _guifi_api_prepare_node('guifi_node', $title);
  
  // Set defaults
  $node->nick = guifi_abbreviate($title);
  $node->notification = $user->mail;
  $node->graph_server = 0;
  $node->status_flag = 'Planned';
  $node->zone_description = '';
  $node->elevation = 0;
  $node->stable = 'Yes';
  
  if (!_guifi_api_node_check_parameters($gapi, &$parameters)) {
    return false;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  
  if (!guifi_node_access('create', $node)) {
    $gapi->addError(501);
    return false;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  
  node_save($node);
  
  $gapi->addResponseField('node_id', $node->id);
  return true;
}

/**
 * Updates a guifi.net node
 * @param GuifiAPI $gapi
 * @param $parameters
 * @return unknown_type
 */
function guifi_api_node_update(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('node_id' ), $parameters)) {
    return false;
  }
  
  extract($parameters);
  
  $node = node_load($node_id);
  
  if (!_guifi_api_node_check_parameters($gapi, &$parameters)) {
    return false;
  }
  
  foreach ($parameters as $key => $value) {
    $node->$key = $value;
  }
  
  if (!guifi_node_access('update', $node)) {
    $gapi->addError(501);
    return false;
  }
  
  if ($node->type != 'guifi_node') {
    $gapi->addError(500, "zone_id = $node->id is not a zone");
    return false;
  }
  
  node_validate($node);
  if ($errors = form_get_errors()) {
    foreach ($errors as $err) {
      $gapi->addError(403, $err);
    }
  }
  node_save($node);
  
  return true;
}

/**
 * Removes a node from guifi.net
 * @param GuifiAPI $gapi
 * @param $parameters
 * @return unknown_type
 */
function guifi_api_node_remove(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('node_id' ), $parameters)) {
    return false;
  }
  
  $node = node_load($parameters['node_id']);
  
  if (!$node->id) {
    $gapi->addError(500, "node_id = {$parameters['node_id']}");
    return false;
  }
  
  if ($node->type != 'guifi_node') {
    $gapi->addError(500, "node_id = $node->id is not a node");
    return false;
  }
  
  if (node_access('delete', $node) && guifi_node_access('update', $node)) {
    node_delete($node->id);
  } else {
    $gapi->addError(501);
    return false;
  }
  
  return true;
}

function _guifi_api_device_check_parameters(&$gapi, &$parameters) {
  extract($parameters);
  
  if (isset($status)) {
    if (guifi_validate_types('status', $status)) {
      $parameters['flag'] = $status;
    } else {
      $gapi->addError(403, "status: $status");
      return false;
    }
  }
  
  if (isset($mac)) {
    if (!_guifi_validate_mac($mac)) {
      $gapi->addError(403, "mac: $mac");
      return false;
    }
  }
  
  switch ($type) {
    case 'radio':
      if (!guifi_api_check_fields($gapi, array('mac', 'model_id', 'firmware' ), $parameters)) {
        return false;
      }
      $model = db_fetch_object(db_query("SELECT model name FROM {guifi_model} WHERE mid = '%d' LIMIT 1", $model_id));
      if (!guifi_validate_types('firmware', $firmware, $model->name)) {
        $gapi->addError(403, "firmware is not supported: $firmware");
        return false;
      }
      break;
    case 'mobile':
      break;
    case 'server':
      break;
    case 'nat':
      break;
    case 'generic':
      break;
    case 'adsl':
      if (!guifi_api_check_fields($gapi, array('download', 'upload', 'mrtg_index' ), $parameters)) {
        return false;
      }
      break;
    case 'cam':
      break;
    case 'phone':
      break;
  }
  
  return true;
}

/**
 * Method the API uses to add a device into the DB
 *
 * @param GuifiAPi $gapi
 * @param mixed $parameters Parameters of the device to be added
 */
function guifi_api_device_add(&$gapi, $parameters) {
  global $user;
  if (!guifi_api_check_fields(&$gapi, array('node_id', 'type' ), $parameters)) {
    return false;
  }
  
  extract($parameters);
  
  $device = new StdClass();
  $device->type = $type;
  $device->nid = $node_id;
  $device->notification = $user->mail;
  
  $node = node_load(array('nid' => $device->nid ));
  $device->nick = guifi_device_get_default_nick($node, $device->type, $device->nid);
  
  if (empty($type)) {
    $gapi->addError(402, 'type');
    return false;
  } else if (empty($node_id)) {
    $gapi->addError(402, 'node_id');
    return false;
  }
  
  if (!_guifi_api_device_check_parameters($gapi, &$parameters)) {
    return false;
  }
  
  foreach ($parameters as $key => $value) {
    $device->$key = $value;
  }
  
  $device->new = true;
  $device->variable = array('model_id' => $device->model_id, 'firmware' => $device->firmware );
  
  if (!guifi_device_access('create', $device)) {
    $gapi->addError(501);
    return false;
  }
  
  $device = object2array($device);
  $device_id = guifi_device_save($device);
  
  //  $data = _guifi_db_sql('guifi_devices', array('id' => $device->id ), $device, $log, $to_mail);
  //  $device->id = $data['id'];
  $gapi->addResponseField('device_id', $device_id);
  return true;
}

function guifi_api_device_update(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('device_id' ), $parameters)) {
    return false;
  }
  
  $device = guifi_device_load($parameters['device_id'], '');
  
  if (!$device->id) {
    $gapi->addError(500, "device_id = {$parameters['device_id']}");
    return false;
  }
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return false;
  }
  
  if (!_guifi_api_device_check_parameters(&$gapi, &$parameters)) {
    return false;
  }
  
  foreach ($parameters as $key => $value) {
    $device->$key = $value;
  }
  
  $device->variable = array('model_id' => $device->model_id, 'firmware' => $device->firmware );
  
  $device = object2array($device);
  $device_id = guifi_device_save($device);
}

/**
 * Remove a device from guifi.net
 * 
 * @param GuifiAPI $gapi
 * @param $parameters Parameters to remove the device (device_id, basically)
 * @return boolean Whether the device was removed or not
 */
function guifi_api_device_remove(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('device_id' ), $parameters)) {
    return false;
  }
  
  global $user;
  
  $device = guifi_device_load($parameters['device_id']);
  if (!$device['id']) {
    $gapi->addError(500, "device_id = {$parameters['device_id']}");
    return false;
  }
  
  guifi_log(GUIFILOG_TRACE, 'function guifi_device_delete()');
  
  $to_mail = explode(',', $device['notification']);
  
  $log = _guifi_db_delete('guifi_devices', array('id' => $device['id'] ), $to_mail);
  drupal_set_message($log);
  
  $subject = t('The device %name has been DELETED by %user.', array('%name' => $device['nick'], '%user' => $user->name ));
  guifi_notify($to_mail, $subject, $log, $verbose, $notify);
  guifi_node_set_flag($device['nid']);
  
  return true;
}

function _guifi_api_radio_check_parameters(&$gapi, $parameters) {
  extract($parameters);
  
  if (isset($mac)) {
    if (!_guifi_validate_mac($mac)) {
      $gapi->addError(403, "mac: $mac");
      return false;
    }
  }
  
  if (isset($antenna_angle)) {
    $antenna_angles = array(0, 6, 60, 90, 120, 360 );
    if (!in_array($anntena_angle, $antenna_angles)) {
      $gapi->addError(403, "antenna_angle: $antenna_angle");
      return false;
    }
  }
  
  if (isset($antenna_gain)) {
    if (is_numeric($antenna_gain)) {
      $antenna_gain = (int) $antenna_gain;
    }
    $antenna_gains = array(2, 8, 12, 14, 18, 21, 24, 'more' );
    if (!in_array($antenna_gain, $antenna_gains)) {
      $gapi->addError(403, "antenna_gain: $antenna_gain");
      return false;
    }
  }
  
  if (isset($antenna_azimuth)) {
    $antenna_azimuth = (int) $antenna_azimuth;
    if (!is_numeric($antenna_azimuth) || $antenna_azimuth > 360 || $antenna_azimuth < 0) {
      $gapi->addError(403, "antenna_azimuth: $antenna_azimuth");
      return false;
    }
  }
  
  if (isset($antenna_mode)) {
    $antenna_modes = array('Main', 'Aux' );
    if (!in_array($anntena_mode, $antenna_modes)) {
      $gapi->addError(403, "antenna_mode: $antenna_mode");
      return false;
    }
  }
  
  switch ($mode) {
    case 'ap':
      if (isset($clients_accepted)) {
        $clients_accepted_values = array('Yes', 'No' );
        if (!in_array($clients_accepted, $clients_accepted_modes)) {
          $gapi->addError(403, "clients_accepted: $clients_accepted");
          return false;
        }
      }
    case 'ad-hoc':
      if (isset($protocol)) {
        if (!guifi_validate_types('protocol', $protocol)) {
          $gapi->addError(403, "protocol is not supported: $protocol");
          return false;
        }
      }
      if (isset($channel)) {
        if (!guifi_validate_types('channel', $channel, $protocol)) {
          $gapi->addError(403, "channel is not supported: $channel");
          return false;
        }
      }
      break;
  }
  
  if (isset($graph_server)) {
    $server = db_fetch_object(db_query("SELECT id FROM {guifi_services} WHERE id = '%d' AND service_type = 'SNPgraphs'", $graph_server));
    if (!$server->id) {
      $gapi->addError(403, "graph_server: $graph_server");
      return false;
    }
  }
  
  return true;
}

/**
 * Adds a radio to a device of guifi.net
 * @param GuifiAPI $gapi
 * @param mixed[] $parameters Parameters of the radio
 * @return unknown_type
 */
function guifi_api_radio_add(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('mode', 'device_id' ), $parameters)) {
    return false;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return false;
  }
  
  $maxradios = db_fetch_object(db_query('SELECT radiodev_max FROM {guifi_model} WHERE mid=%d', $device['variable']['model_id']));
  $maxradios = $maxradios->radiodev_max;
  
  if (count($device['radios']) >= $maxradios) {
    $gapi->addError(404, "This device already has the maximum number of radios allowed: $maxradios");
    return false;
  }
  
  if (count($device['radios']) > 0) {
    if (!guifi_api_check_fields(&$gapi, array('mac' ), $parameters)) {
      return false;
    }
  }
  
  $device['newradio_mode'] = $parameters['mode'];
  
  $radio = _guifi_radio_prepare_add_radio($device);
  
  $fields = array('mac', 'antenna_angle', 'antenna_gain', 'antenna_azimuth', 'antenna_mode' );
  if ($parameters['mode'] == 'ap') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel', 'clients_accepted' ));
  } else if ($parameters['mode'] == 'ad-hoc') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel' ));
  }
  
  if (!_guifi_api_radio_check_parameters(&$gapi, $parameters)) {
    return false;
  }
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return false;
  }
  
  foreach ($fields as $field) {
    if (isset($parameters[$field])) {
      $radio[$field] = $parameters[$field];
    }
  }
  
  $device['radios'][] = $radio;
  
  guifi_device_save($device);
  
  $gapi->addResponseField('radiodev_counter', count($device['radios']) - 1);
  $interfaces = array();
  if (!empty($radio['interfaces'])) {
    foreach ($radio['interfaces'] as $if) {
      $interface = array();
      $interface['interface_type'] = $if['interface_type'];
      
      if (!empty($if['ipv4'])) {
        $interface['ipv4'] = array();
        foreach ($if['ipv4'] as $if_ipv4) {
          $ipv4 = array();
          $ipv4['ipv4_type'] = $if_ipv4['ipv4_type'];
          $ipv4['ipv4'] = $if_ipv4['ipv4'];
          $ipv4['netmask'] = $if_ipv4['netmask'];
          
          $interface['ipv4'][] = $ipv4;
        }
      }
      
      $interfaces[] = $interface;
    }
  }
  if (!empty($interfaces)) {
    $gapi->addResponseField('interfaces', $interfaces);
  }
  return true;
}

function guifi_api_radio_update(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('device_id', 'radiodev_counter' ), $parameters)) {
    return false;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  $radiodev_counter = $parameters['radiodev_counter'];
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return false;
  }
  
  if (!isset($device['radios'][$radiodev_counter])) {
    $gapi->addError(500, "radio not found: $radiodev_counter");
    return false;
  }
  
  $radio = $device['radios'][$radiodev_counter];
  
  $fields = array('antenna_angle', 'antenna_gain', 'antenna_azimuth', 'antenna_mode' );
  if ($radio['mode'] == 'ap') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel', 'clients_accepted' ));
  } else if ($radio['mode'] == 'ad-hoc') {
    $fields = array_merge($fields, array('ssid', 'protocol', 'channel' ));
  }
  
  if (!_guifi_api_radio_check_parameters(&$gapi, $parameters)) {
    return false;
  }
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return false;
  }
  
  foreach ($fields as $field) {
    if (isset($parameters[$field])) {
      $radio[$field] = $parameters[$field];
    }
  }
  
  $device['radios'][$radiodev_counter] = $radio;
  
  guifi_device_save($device);
  
  return true;
}

function guifi_api_radio_remove(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('device_id', 'radiodev_counter' ), $parameters)) {
    return false;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return false;
  }
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return false;
  }
  
  $radiodev_counter = intval($parameters['radiodev_counter']);
  
  if (isset($device['radios'][$radiodev_counter])) {
    $device['radios'][$radiodev_counter]['deleted'] = true;
  } else {
    $gapi->addError(500, "radio not found: $radiodev_counter");
    return false;
  }
  
  guifi_device_save($device);
  return true;
}

function guifi_api_interface_add(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('device_id', 'radiodev_counter' ), $parameters)) {
    return false;
  }
  
  $device = guifi_device_load($parameters['device_id']);
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return false;
  }
  
  if (!$device['id']) {
    $gapi->addError(500, "device not found: {$parameters['device_id']}");
    return false;
  }
  
  $radiodev_counter = intval($parameters['radiodev_counter']);
  
  if (!isset($device['radios'][$radiodev_counter])) {
    $gapi->addError(500, "radio not found: $radiodev_counter");
    return false;
  }
  
  $interface = _guifi_radio_add_wlan($radiodev_counter, $device['nid']);
  
  $old_interfaces = array_keys($device['radios'][$radiodev_counter]['interfaces']);
  
  $device['radios'][$radiodev_counter]['interfaces'][] = $interface;
  
  $device_id = guifi_device_save($device);
  $device = guifi_device_load($device_id);
  
  $new_interfaces = array_keys($device['radios'][$radiodev_counter]['interfaces']);
  
  $interface_id = array_shift(array_diff($new_interfaces, $old_interfaces));
  
  $interface = $device['radios'][$radiodev_counter]['interfaces'][$interface_id];
  
  if (!empty($interface['ipv4'])) {
    $ipv4 = array();
    foreach ($interface['ipv4'] as $if_ipv4) {
      $new_ipv4 = array();
      $new_ipv4['ipv4_type'] = $if_ipv4['ipv4_type'];
      $new_ipv4['ipv4'] = $if_ipv4['ipv4'];
      $new_ipv4['netmask'] = $if_ipv4['netmask'];
      $ipv4[] = $new_ipv4;
    }
    
    $gapi->addResponseField('ipv4', $ipv4);
  }
  
  $gapi->addResponseField('interface_id', $interface_id);
  
  return true;
}

function guifi_api_interface_remove(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('interface_id' ), $parameters)) {
    return false;
  }
  
  $interface_id = $parameters['interface_id'];
  
  $device_info = db_fetch_object(db_query('SELECT device_id, radiodev_counter FROM {guifi_interfaces} WHERE id = %d', $interface_id));
  $device_id = $device_info->device_id;
  $radiodev_counter = $device_info->radiodev_counter;
  
  if (!$device_id) {
    $gapi->addError(500, "interface not found: {$interface_id}");
    return false;
  }
  
  $device = guifi_device_load($device_id);
  
  if (!guifi_device_access('update', $device)) {
    $gapi->addError(501);
    return false;
  }
  
  if (isset($device['radios'][$radiodev_counter]['interfaces'][$interface_id])) {
    $interface = $device['radios'][$radiodev_counter]['interfaces'][$interface_id];
    
    if ($interface['interface_type'] != 'wLan') {
      $gapi->addError(404, "only extra wLan can be removed");
      return false;
    } else {
      $device['radios'][$radiodev_counter]['interfaces'][$interface_id]['deleted'] = true;
    }
  } else {
    $gapi->addError(500, "interface not found: $interface_id");
    return false;
  }
  
  guifi_device_save($device);
  return true;
}

function _guifi_api_link_check_parameters(&$gapi, &$parameters) {
  extract($parameters);
  
  if (isset($status)) {
    if (guifi_validate_types('status', $status)) {
      $parameters['flag'] = $status;
    } else {
      $gapi->addError(403, "status: $status");
      return false;
    }
  } else {
    $parameters['flag'] = 'Planned';
  }
  
  if (isset($routing)) {
    if (!guifi_validate_types('routing', $routing)) {
      $gapi->addError(403, "routing: $routing");
    }
  }
  
  return true;
}

function _guifi_api_link_validate_local_ipv4($l_ipv4, $r_ipv4) {
  $item1 = _ipcalc($l_ipv4['ipv4'], $l_ipv4['netmask']);
  $item2 = _ipcalc($r_ipv4['ipv4'], $r_ipv4['netmask']);
  
  if (($item1['netstart'] != $item2['netstart']) or ($item1['netend'] != $item2['netend'])) {
    return false;
  } else {
    return true;
  }
}

function guifi_api_link_add(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('from_device_id', 'from_radiodev_counter' ), $parameters)) {
    return false;
  }
  
  $from_device_id = $parameters['from_device_id'];
  $from_radiodev_counter = $parameters['from_radiodev_counter'];
  
  $from_device = guifi_device_load($from_device_id);
  
  if (!$from_device['id']) {
    $gapi->addError(500, "from_device_id not found: {$from_device_id}");
    return false;
  }
  
  if (!$from_device['id']) {
    $gapi->addError(500, "from_device not found: {$parameters['from_device_id']}");
    return false;
  }
  
  if (!guifi_device_access('update', $from_device)) {
    $gapi->addError(501);
    return false;
  }
  
  if (!guifi_api_check_fields(&$gapi, array('to_device_id', 'to_radiodev_counter' ), $parameters)) {
    return false;
  }
  $to_device_id = $parameters['to_device_id'];
  $to_radiodev_counter = $parameters['to_radiodev_counter'];
  
  $to_device = guifi_device_load($to_device_id);
  
  if (!$to_device['id']) {
    $gapi->addError(500, "to_device_id not found: {$parameters['to_device_id']}");
    return false;
  }
  
  $from_radio = &$from_device['radios'][$from_radiodev_counter];
  
  if ($from_radio['mode'] == 'client') {
    $from_interface_id = array_pop(array_keys($from_radio['interfaces']));
    $from_interface = &$from_device['radios'][$from_radiodev_counter]['interfaces'][$from_interface_id];
  } else if ($from_radio['mode'] == 'ap') {
    // If radio mode is AP, find the wds/p2p interface (could be others, like wLan/Lan)
    foreach ($from_radio['interfaces'] as $from_interface_id => $from_interface) {
      if ($from_interface['interface_type'] == 'wds/p2p') {
        break;
      }
    }
  }
  
  if (!_guifi_api_link_check_parameters(&$gapi, $parameters)) {
    return false;
  }
  
  // Check if the link is allowed
  // Between Wan and wLan - wLan/Lan?
  if ($from_interface['interface_type'] == 'Wan') {
    /* client2ap link */
    if (!empty($from_interface['ipv4'])) {
      $gapi->addError(404, "radio already has a link: $from_radiodev_counter");
      return false;
    }
    
    $ipv4 = _guifi_radio_add_link2ap($to_device['nid'], $to_device_id, $to_radiodev_counter, $parameters['ipv4'], -1);
    
    if ($ipv4 == -1) {
      $str = "radio is full or IPv4 parameters are wrong";
      if ($parameters['ipv4']) {
        $str .= " (ipv4: {$parameters['ipv4']})";
      }
      $gapi->addError(404, $str);
      return false;
    }
    
    $ipv4['links'][-1]['flag'] = $parameters['flag'];
    
    $from_interface['ipv4'][] = $ipv4;
    
    guifi_device_save($from_device);
    
    $from_device = guifi_device_load($from_device['id']);
    $from_interface = array_pop($from_device['radios'][$from_radiodev_counter]['interfaces']);
    $link_id = array_pop(array_keys($from_interface['ipv4'][0]['links']));
    
    $ipv4_return = array();
    $ipv4_return['ipv4_type'] = $ipv4['ipv4_type'];
    $ipv4_return['ipv4'] = $ipv4['ipv4'];
    $ipv4_return['netmask'] = $ipv4['netmask'];
    
    //    $gapi->addResponseField('device', $from_device);
    $gapi->addResponseField('link_id', $link_id);
    $gapi->addResponseField('ipv4', $ipv4_return);
    //    $gapi->addResponseField('ipv4', $ipv4);
    

    return true;
  
  } else if ($from_interface['interface_type'] == 'wds/p2p') {
    /* WDS link */
    $new_interface = array();
    $new_interface[$from_interface_id]['ipv4'][] = _guifi_radio_add_wds_get_new_interface($from_device['nid']);
    $new_link = &$new_interface[$from_interface_id]['ipv4'][0]['links'][0];
    $new_link['id'] = -1;
    $new_link['flag'] = $parameters['flag'];
    if (!empty($parameters['routing'])) {
      $new_link['routing'] = $parameters['routing'];
    }
    
    // getting remote interface
    $remote_interface = db_fetch_array(db_query("SELECT id FROM {guifi_interfaces} WHERE device_id = %d AND interface_type = 'wds/p2p' AND radiodev_counter = %d", $to_device['id'], $to_radiodev_counter));
    
    $new_link['nid'] = $to_device['nid'];
    $new_link['device_id'] = $to_device['id'];
    $new_link['interface']['id'] = $remote_interface['id'];
    $new_link['interface']['device_id'] = $to_device['id'];
    $new_link['interface']['radiodev_counter'] = $to_radiodev_counter;
    $new_link['interface']['ipv4']['interface_id'] = $remote_interface['id'];
    
    foreach ($new_interface[$from_interface_id]['ipv4'] as $newInterface) {
      $from_device['radios'][$from_radiodev_counter]['interfaces'][$from_interface_id]['ipv4'][] = $newInterface;
    }
    guifi_device_save($from_device);
    $gapi->addResponseField('from_device', $from_device);
    return true;
  } else {
    $gapi->addError(404, "interface doesn't allow to create the link. from_interface_type = {$from_interface['interface_type']}");
    return false;
  }
  
  return false;
}

function guifi_api_link_remove(&$gapi, $parameters) {
  if (!guifi_api_check_fields(&$gapi, array('link_id' ), $parameters)) {
    return false;
  }
  
  $link_id = $parameters['link_id'];
  
  $link_query = db_query('SELECT * FROM {guifi_links} WHERE id = %d', $link_id);
  
  while ($link = db_fetch_object($link_query)) {
    if (!$link->device_id) {
      $gapi->addError(500, "link not found: $link_id");
      return false;
    }
    $device = guifi_device_load($link->device_id);
    if (!guifi_device_access('update', $device)) {
      $gapi->addError(501);
      return false;
    }
    
    $interface = db_fetch_object(db_query('SELECT * FROM {guifi_interfaces} WHERE id = %d LIMIT 1', $link->interface_id));
    
    $device['radios'][$interface->radiodev_counter]['interfaces'][$link->interface_id]['ipv4'][$link->ipv4_id]['deleted'] = true;
    $device['radios'][$interface->radiodev_counter]['interfaces'][$link->interface_id]['ipv4'][$link->ipv4_id]['links'][$link->id]['deleted'] = true;
    
    guifi_device_save($device);
  }
  
  return true;
}

function guifi_api_user_access($op, $id, &$rsp) {
  $ops = explode('.', $op);
  if (count($ops) < 3) {
    return false;
  }
  
  $area = $ops[1];
  $operation = $ops[2];
  switch ($area) {
    case 'zone':
      
      break;
    case 'device':
      break;
    case 'radio':
      break;
    case 'service':
      break;
  }
}

/**
 * Check if any fields are present in the parameters passed to the API or not
 *
 * @param GuifiAPI $gapi
 * @param string[] $required
 * @param mixed[] $parameters
 */
function guifi_api_check_fields(&$gapi, $required, $parameters) {
  $success = true;
  foreach ($required as $req) {
    if (!isset($parameters[$req])) {
      $gapi->addError(402, $req);
      $success = false;
    }
  }
  return $success;
}

?>