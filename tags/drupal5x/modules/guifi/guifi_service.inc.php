<?php
// $Id: guifi.module x$

/**
 * @file
 * Manage guifi_service 
 */

/**
 * Implementation of hook_access().
 */
function guifi_service_access($op, $node) {
  global $user; 
  if ($op == 'create') {
    return user_access('create guifi nodes');
  }

  if ($op == 'update') {
    if ((user_access('administer guifi zones')) || ($node->uid == $user->uid)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}


/**
 * zone editing functions
**/

/**
 * Present the guifi zone editing form.
 */
function guifi_service_form(&$node, &$param) {
  
  global $user;

  if ( (empty($node->nid)) and (is_numeric($node->title)) ) {
    $zone = guifi_zone_load($node->title);
    $node->zone_id = $node->title;
    $node->contact = $user->mail;
    $default = t('<service>');
    $node->title = null;
    $node->nick = $zone->nick.$default;
    $node->status_flag = 'Planned';
  }

  $type = db_fetch_object(db_query("SELECT description FROM {guifi_types} WHERE type='service' AND text='%s'",$node->service_type));
  if ($node->nid > 0)
    $output = form_item(t('Service type'),$node->service_type,t($type->description));
  $output .= form_textfield(t("Nick"), "nick", $node->nick, 20, 20, t("Unique identifier for this service. Avoid generic names such 'Disk Server', use something that really describes what is doing and how can be distinguished from the other similar services.<br />Short name, single word with no spaces, 7-bit chars only.") . ($error['nick'] ? $error["nick"] : ''), null, true);
  $output .= form_textfield(t("Contact"), "contact", $node->contact, 60, 128, t("Who did possible this service or who to contact with regarding this service if it is distinct of the owner of this page.") . ($error['contact'] ? $error["contact"] : ''));
//  $output .= form_select(t('Zone'), 'zone_id', $node->zone_id, guifi_zones_listbox(), t('The zone where this node where this node belongs to.'));


  $params .= guifi_form_column(form_select(t('Device'), "device_id", $node->device_id, guifi_servers_select(),t('Where it runs.')));
  if (!$node->nid) {
    $types = guifi_types('service');
    array_shift($types);
    $params.= guifi_form_column(form_select(t('Service'), "service_type", $node->service_type, $types,t('Type of service')));
  } else
    $output .= form_hidden("service_type",$node->service_type);
  $params .= guifi_form_column(form_select(t('Status'), 'status_flag', $node->status_flag, guifi_types('status'), t('Current status')));
  $output .= guifi_form_column_group(t('General parameters'),$params,null);

  unset($specs);
  if ($node->nid > 0)
  switch ($node->service_type) {
    case 'mail':
      $specs = form_textfield(t("Inbound mail server"), "var][in", $node->var['in'], 60, 60, t('Where email clients have to be configured for getting email messages'));
      $specs .= form_textfield(t("Outbound mail server"), "var][out", $node->var['out'], 60, 60, t('Where email clients have to be configured for sending email messages'));
      $specs .= form_textfield(t("Webmail url"), "var][webmail", $node->var['webmail'], 60, 60, t('URL for accessing to this mail server, if there is'));
      $specs .= form_textfield(t("Admin web interface"), "var][admin", $node->var['admin'], 60, 60, t('Where to create/edit/delete users, change passwords, etc...'));
      break;
    case 'asterisk':
      $specs .= form_textfield(t("Dial prefix"), "var][prefix", $node->var['prefix'], 60, 60, t('Dial prefix for calling this server extensions'));
      $specs .= form_textfield(t("Incoming calls"), "var][incoming", $node->var['incoming'], 60, 60, t('Server or IP address where the calls have to be sent'));
      $specs .= form_checkboxes(t("Protocols"), "var][protocols", $node->var['protocols'], array('IAX'=>'IAX','SIP'=>'SIP'));
      break;
    case 'NTP':
      $specs .= form_textfield(t("IP address or hostname"), "var][ntp", $node->var['ntp'], 60, 60, null);
      break;
    case 'ftp':
      $specs .= form_textfield(t("IP address or hostname"), "var][ftphost", $node->var['ftphost'], 60, 60, null);
      $specs .= form_checkboxes(t("Protocols"), "var][protocols", $node->var['protocols'], array('SMB'=>'SMB (Samba)','ftp'=>'FTP','nfs'=>'NFS'));
      break;
    case 'Proxy': case 'ADSL':
      $specs .= form_textfield(t("Download"), "var][down", $node->var['down'], 60, 60, t('Download bandwidth'));
      $specs .= form_textfield(t("Upload"), "var][up", $node->var['up'], 60, 60, t('Upload bandwidth'));
      if ($node->service_type == 'ADSL')
        break;
      $specs .= form_checkboxes(t("Proxy federation"), "var][fed", $node->var['fed'], array('IN'=>t('Allow login of users from OUT federated proxys'),'OUT'=>t('Allow proxy users to use other IN federated proxys')));  
      $specs .= form_textfield(t("Name"), "var][proxy", $node->var['proxy'], 60, 60, null);
      $specs .= form_textfield(t("Port"), "var][port", $node->var['port'], 60, 60, null);
      $specs .= form_select(t("Type"), "var][type", $node->var['type'], array('HTTP'=>'HTTP','Socks4'=>'SOCKS4','Socks5'=>'SOCKS5','arp'=>'ARP','ftp'=>'FTP'), null);
       break;
    default:
      $specs .= form_textfield(t("url"), "var][url", $node->var['url'], 60, 250, null);
      break;
  }
  if (isset($specs))
    $output .= form_group(t('Specific %type parameters',array('%type' => theme('placeholder',$type->description))),$specs,null);

  unset($domains);
  if ($node->nid > 0)
  switch ($node->service_type) {
  case 'mail': case 'DNS': 
    $key = 0;
    if (isset($node->var['domains']))  
    if (count($node->var['domains']) > 0)  
    foreach ($node->var['domains'] as $key => $domain)
      if ($node->var['domains'][$key] != '')
        $domains .= form_textfield(null, "var][domains][".$key, $node->var['domains'][$key], 60, 60, null);
    for ($i = 0; $i < 2; $i++)
      $domains .= form_textfield(null, "var][domains][".($i + $key + 1), null, 60, 60, null);
  }
  if (isset($domains))
    $output .= form_group(t('Managed domains'),$domains,t('Press "Preview" to get more rows'));

  unset($homepages);
  if ($node->nid > 0)
  switch ($node->service_type) {
  case 'web':
    $key = 0;
    if (isset($node->var['homepages']))  
    if (count($node->var['homepages']) > 0)  
    foreach ($node->var['homepages'] as $key => $homepage)
      if ($node->var['homepages'][$key] != '')
        $homepages .= form_textfield(null, "var][homepages][".$key, $node->var['homepages'][$key], 60, 60, null);
    for ($i = 0; $i < 2; $i++)
      $homepages .= form_textfield(null, "var][homepages][".($i + $key + 1), null, 60, 60, null);
  }
  if (isset($homepages))
    $output .= form_group(t('Homepages'),$homepages,t('Press "Preview" to get more rows'));

  unset($ircservers);
  if ($node->nid > 0)
  switch ($node->service_type) {
  case 'irc': 
    $key = 0;
    if (isset($node->var['irc']))  
    if (count($node->var['irc']) > 0)  
    foreach ($node->var['irc'] as $key => $irc)
      if ($node->var['irc'][$key] != '')
        $ircservers .= form_textfield(null, "var][irc][".$key, $node->var['irc'][$key], 60, 60, null);
    for ($i = 0; $i < 2; $i++)
      $ircservers .= form_textfield(null, "var][irc][".($i + $key + 1), null, 60, 60, null);
  }
  if (isset($ircservers))
    $output .= form_group(t('IRC servers'),$ircservers,t('Press "Preview" to get more rows'));

  $output .= form_textarea(t("Body"), "body", $node->body, 60, 20, t("Textual description of the wifi") . ($error['body'] ? $error['body'] : ''));

  return $output;
}

/**
 * Confirm that an edited guifi item has fields properly filled in.
 */
function guifi_edit_service_validate(&$node) {
  guifi_validate_nick($node->nick);

  if (!empty($node->nick)) { 
    $query = db_query("SELECT nick FROM {guifi_services} WHERE lcase(nick)='%s' AND id <> %d",strtolower($node->nick),$node->nid);
    if (db_num_rows($query))
      form_set_error('nick', t('Nick already in use.'));
  }

  if ($node->device_id > 0) {
    $zone = db_fetch_object(db_query("SELECT zone_id FROM {guifi_location} l LEFT JOIN {guifi_devices} d ON d.nid=l.id WHERE d.id=%d",$node->device_id));
    $node->zone_id = $zone->zone_id;
  }

}

/**
 * Save changes to a guifi item into the database.
 */

function guifi_service_insert($node) {
  global $user;

  db_query("INSERT INTO {guifi_services} ( id, zone_id, nick, service_type, device_id, contact, status_flag, extra, timestamp_created, user_created) VALUES (%d, %d, '%s', '%s', %d, '%s', '%s', '%s', %d, %d)", $node->nid, $node->zone_id, $node->nick, $node->service_type, $node->device_id, $node->contact, $node->status_flag, serialize($node->var), time(), $user->uid);

// Refresh maps?
}

function guifi_service_update($node) {
  global $user;

  db_query("UPDATE {guifi_services} SET zone_id = %d, nick = '%s', device_id = %d, contact = '%s', status_flag = '%s', extra = '%s', timestamp_changed = %d, user_changed = %d WHERE id = %d", $node->zone_id, $node->nick, $node->device_id, $node->contact, $node->status_flag, serialize($node->var), time(), $user->uid, $node->nid);

}

/**
 * outputs the zone information data
**/
function guifi_service_print_data($node) {
  
  $name_created = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $node->user_created));
  $name_changed = db_fetch_object(db_query('SELECT u.name FROM {users} u WHERE u.uid = %d', $node->user_changed));
  $zone         = db_fetch_object(db_query('SELECT title FROM {guifi_zone} WHERE id = %d', $node->zone_id));
  $type         = db_fetch_object(db_query('SELECT description FROM {guifi_types} WHERE type="service" AND text = "%s"', $node->service_type));

  $rows[] = array(t('service'),$node->nid .'-' .$node->nick,'<b>' .$node->title .'</b>'); 
  $rows[] = array(t('type'),$node->service_type,t($type->description)); 
  if ($node->device_id > 0) {
    $device       = db_fetch_object(db_query('SELECT nick FROM {guifi_devices} WHERE id = %d', $node->device_id));
    $url = url('guifi/device/'.$node->device_id,null, null, false);
    $rows[] = array(t('device &#038; status'),'<a href='.$url.'>'.$device->nick.'</a>',array('data' => t($node->status_flag),'class' => $node->status_flag)); 
  }

  switch ($node->service_type) {
    case 'mail':
      $rows[] = array(t('inbound and outbound servers'),$node->var['in'],$node->var['out']);
      $rows[] = array(t('webmail and admin url'),guifi_url($node->var['webmail']),guifi_url($node->var['admin']));
      break;
    case 'Proxy': case 'ADSL':
      $rows[] = array(t('bandwidth (Up/Down)'),$node->var['down'],$node->var['up']);
      $rows[] = array(t('proxy name &#038; port'),$node->var['proxy'],$node->var['port']);
      $rows[] = array(t('type'),$node->var['type'],null);
      if (is_array($node->var['fed'])) $rows[] = array(t('federation'),implode(", ",$node->var['fed']),null);
      else $rows[] = array(t('federation'),t('This proxy is not federated yet'),null);
      break;
    case 'ftp': 
      $rows[] = array(t('ftphost'),$node->var['ftphost'],null);
      $rows[] = array(t('supported protocols'),implode(", ",$node->var['protocols']),null);
      break;
    case 'ntp': 
      $rows[] = array(t('IP address or hostname'),$node->var['ntp'],null);
      break;
    case 'asterisk': 
      $rows[] = array(t('dial prefix and incoming calls'),$node->var['prefix'],$node->var['incoming']);
      if (isset($node->var['protocols']))
        $rows[] = array(t('supported protocols'),implode(", ",$node->var['protocols']),null);
      break;
    default: 
      if (!empty($node->var['url'])) {
        if (preg_match('/^http:\/\//',$node->var[url])) 
          $url = $node->var[url];
        else
          $url = 'http://'.$node->var[url];
        $rows[] = array(t('url'),'<a href="'.$url.'">'.$node->var['url'].'</a>',null);
      }
      break;
  }

  if (isset($node->var['homepages'])) 
  if (count($node->var['homepages'] > 0)) {
    $rows[] = array(t('homepages'),null,null);
    foreach ($node->var['homepages'] as $homepage) {
      if (preg_match('/^http:\/\//',$homepage))
        $url = $homepage;
      else
        $url = 'http://'.$homepage;
      $rows[] = array(null,'<a href='.$url.'>'.$homepage.'</a>',null);
    }
  }
  
  if (isset($node->var['ircs'])) 
  if (count($node->var['ircs'] > 0)) {
    $rows[] = array(t('ircs'),null,null);
    foreach ($node->var['ircs'] as $irc) 
      $rows[] = array(null,$irc,null);
  }
  
  if (isset($node->var['domains'])) 
  if (count($node->var['domains'] > 0)) {
    $rows[] = array(t('domains'),null,null);
    foreach ($node->var['domains'] as $domain) 
      $rows[] = array(null,$domain,null);
  }
  
  $rows[] = array(null,null,null);
  $rows[] = array('<b>' .t('user and log information') .'<b>',null,null);
  if ($node->timestamp_created > 0) 
    $rows[] = array(t('created by'),$name_created->name,format_date($node->timestamp_created)); 
  else
    $rows[] = array(t('created by'),$name_created->name,null); 
  if ($node->timestamp_changed > 0) 
    $rows[] = array(t('last update'),$name_changed->name,format_date($node->timestamp_changed)); 
  return array_merge($rows);
}

function guifi_list_services_query($param, $typestr = 'by zone', $service = '%') {

  $rows = array();
  $sqlprefix = "SELECT s.*,z.title zonename FROM {guifi_services} s LEFT JOIN {guifi_devices} d ON s.device_id=d.id LEFT JOIN {guifi_zone} z ON s.zone_id=z.id LEFT JOIN {guifi_location} l ON d.nid=l.id WHERE ";
  switch ($typestr) {
    case t('by zone'): 
      $childs = guifi_get_zone_child_tree($param->id);
      $sqlwhere = sprintf('s.zone_id IN (%s) ',implode(',',$childs));
      break;
    case t('by node'): 
      $sqlwhere = sprintf('d.nid = %d ',$param->nid);
      break;
    case t('by device'): 
      $sqlwhere = sprintf('d.id = %d ',$param);
      break;
  }
  $query = db_query($sqlprefix.$sqlwhere.' ORDER BY s.service_type, s.zone_id, s.nick');

  $current_service = '';  
  while ($service = db_fetch_object($query)) {
    $node = node_load(array('nid'=>$service->id));
    if ($current_service != $service->service_type) {
      $typedescr = db_fetch_object(db_query("SELECT * FROM {guifi_types} WHERE type='service' AND text = '%s'",$service->service_type));
      $rows[] = array('<strong>'.t($typedescr->description).'</strong>',null,null,null);
      $current_service = $service->service_type;
    } 
  
    $rows[] = array('<a href="node/'.$service->id.'">'.$node->title.'</a>', 
                    '<a href="node/'.$service->zone_id.'">'.$service->zonename.'</a>',
                    '<a href="guifi/device/'.$service->device_id.'">'.guifi_get_hostname($service->device_id).'</a>',
                    array('data' => t($node->status_flag),'class' => $node->status_flag));
  }

  return array_merge($rows);
}

/*
 * guifi_list_services
 */
function guifi_list_services($node,$service = '%') {

//  print "Enter list services by zone ".$node->nid."\n<br />";

  if (is_numeric($node)) {
    $typestr = t('by device');
  } else {
    if ($node->type == 'guifi-node')
      $typestr = t('by node');
    else
      $typestr = t('by zone');
  }
  $output = '<h2>' .t('Services of ') .' ' .$node->title .' ('.$typestr.')</h2>';
  $rows = guifi_list_services_query($node,$typestr);
  $output .= theme('table', array(t('service'),t('zone'),t('device'),t('status')), array_merge($rows),array('width'=>'100%'));
  return $output;
}
/**
 * outputs the node information
**/
function guifi_service_view(&$node) {
  
  $output = '<div id="guifi">';
  if ($node->zone_id > 0)
    $output .= guifi_zone_ariadna($node->zone_id);

  switch (arg(3)) {
    case 'data':
    case 'view':
    case 'users': 
    case 'passwd': 
    case 'federated':
    case 'ldif':
      $op = arg(3);
      break;
    default: 
      $op = "default";
      break;
  }
  switch ($op) {
    case 'all': case 'data': case 'default':
      // node details
      $output .= theme('table', NULL, guifi_service_print_data($node));
      if ($op == 'data') break;
      break;
    case 'users': 
      $output .= theme('box', NULL, guifi_list_users($node));
      break;
    case 'passwd': 
      $output .= theme('box', NULL, guifi_dump_passwd($node));
      break;
    case 'federated': 
      $output .= theme('box', NULL, guifi_dump_federated($node));
      break;
    case 'ldif': 
      $output .= theme('box', NULL, guifi_dump_ldif($node));
      break;
    case 'view':
      $output .= $node->body;
      break;
  }
  $output .= "</div>";

  $node->body .= theme('box', t('service information'), $output);

  if ($op != 'default')
    print theme('page',$output,t('node').': '.$node->title.' ('.t($op).')');
}

?>
