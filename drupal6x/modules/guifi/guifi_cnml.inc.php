<?php
/**
 * guifi_cnml
**/

function guifi_cnml($cnmlid,$action = 'help') {

  guifi_log(GUIFILOG_TRACE,'function guifi_cnml()',$cnmlid);

  if (!is_numeric($cnmlid))
    return;

  if ($action == "help") {
     $zone = db_fetch_object(db_query(
       'SELECT title, nick ' .
       'FROM {guifi_zone} ' .
       'WHERE id = %d',$cnmlid));
     drupal_set_breadcrumb(guifi_zone_ariadna($cnmlid));

     $output = '<div id="guifi">';
     $output .= '<h2>'.t('Zone %zname%',array('%zname%'=>$zone->title)).'</h2>';
     $output .= '<p>'.t('You must specify which data do you want to export, the following options are available:').'</p>';
     $output .= '<ol><li>'. l(t('Zones'), "guifi/cnml/".$cnmlid."/zones", array('title'=>t('export zone and zone childs in CNML format')) ).'</li>';
     $output .= '<li>'. l(t('Zones and nodes'), "guifi/cnml/".$cnmlid."/nodes", array('title'=>t('export zones and nodes in CNML format (short)')) ).'</li>';
     $output .= '<li>'. l(t('Detailed'), "guifi/cnml/".$cnmlid."/detail", array('title'=>t('export zones, nodes  and devices in CNML format (long)')) ).'</li></ol>';
     $output .= '<p>'.t('The <b>C</b>ommunity <b>N</b>etwork <b>M</b>arkup <b>L</b>anguage (<a href="'.base_path().'node/3521">CNML</a>) is a XML format to interchange network information between services or servers.').'</p>';
     $output .= '<p>'.t('<b>IMPORTANT LEGAL NOTE:</b> This network information is under the <a href="http://guifi.net/ComunsSensefils/">Comuns Sensefils</a> license, and therefore, available for any other network under the same licensing. If is not your case, you should ask for permission before using it.</a>').'</p>';
     $output .= "</div>";
     print theme('page',$output,t('export %zname% in CNML format',array('%zname%' => $z->title)));
     exit;
  }

  function links($iid,$iipv4_id,$ident,$nl) {

    $links->count = 0;
    $links->xml = "";
    $qlinks = db_query("SELECT l2.* FROM {guifi_links} l1 LEFT JOIN {guifi_links} l2 ON l1.id=l2.id WHERE l1.device_id<>l2.device_id AND l1.interface_id=%d AND l1.ipv4_id=%d",$iid,$iipv4_id);
     while ($l = db_fetch_object($qlinks)) {
      $links->count++;
      $links->xml .= xmlopentag($ident,'link',array('id'=>$l->id,
						    'linked_device_id'=>$l->device_id,
                                                    'linked_node_id'=>$l->nid,
						    'linked_interface_id'=>$l->interface_id,
                                                    'link_type'=>$l->link_type,
                                                    'link_status'=>$l->flag));
      $links->xml .= xmlclosetag($ident,'link',$nl);

    }

    return $links->xml;
  }

  global $base_url;

  // load nodes and zones in memory for faster execution
  switch ($action) {
  case 'zones':
  case 'nodes':
  case 'detail':
     $tree = guifi_cnml_tree($cnmlid);
     $sql_devices = 'SELECT * FROM {guifi_devices} d';
     $sql_radios = 'SELECT * FROM {guifi_radios} r';
     $sql_interfaces = 'SELECT i.*,a.ipv4,a.id ipv4_id, a.netmask FROM {guifi_interfaces} i, {guifi_ipv4} a WHERE i.id=a.interface_id';
     $sql_links = 'SELECT l1.id, l1.device_id, l1.interface_id, l1.ipv4_id, l2.device_id linked_device_id, l2.nid linked_node_id, l2.interface_id linked_interface_id, l2.ipv4_id linked_radiodev_counter, l1.link_type, l1.flag status FROM {guifi_links} l1, {guifi_links} l2 WHERE l1.id=l2.id AND l1.device_id != l2.device_id';
     $sql_services = 'SELECT s.* FROM {guifi_services} s';
     break;
   case 'node':
     $qnode = db_query(sprintf(
       'SELECT l.*,r.body body ' .
       'FROM {guifi_location} l, {node} n, {node_revisions} r ' .
       'WHERE l.id=n.nid AND n.vid=r.vid ' .
       '  AND l.id in (%s)',
       $cnmlid));
     while ($node = db_fetch_object($qnode)) {
       $tree[] = $node;
     }
     $sql_devices = sprintf('SELECT * FROM {guifi_devices} d WHERE nid in (%s)',$cnmlid);
     $sql_radios = sprintf('SELECT r.* FROM {guifi_radios} r, {guifi_devices} d WHERE d.nid in (%s) AND d.id=r.id',$cnmlid);
     $sql_interfaces = sprintf('SELECT i.*,a.ipv4,a.id ipv4_id, a.netmask FROM {guifi_devices} d, {guifi_interfaces} i, {guifi_ipv4} a WHERE d.nid in (%s) AND d.id=i.device_id AND i.id=a.interface_id',$cnmlid);
     $sql_links = sprintf('SELECT l1.id, l1.device_id, l1.interface_id, l1.ipv4_id, l2.device_id linked_device_id, l2.nid linked_node_id, l2.interface_id linked_interface_id, l2.ipv4_id linked_radiodev_counter, l1.link_type, l1.flag status FROM {guifi_links} l1, {guifi_links} l2 WHERE l1.nid in (%s) AND l1.id=l2.id AND l1.device_id != l2.device_id',$cnmlid);
     $sql_services = sprintf('SELECT s.*, r.body FROM {guifi_devices} d, {guifi_services} s, {node} n, {node_revisions} r WHERE d.nid in (%s) AND d.id=s.device_id AND n.nid=s.id AND n.vid=r.vid',$cnmlid);
     break;
   case 'nodecount':
   	 $CNML=fnodecount($cnmlid);
     drupal_set_header('Content-Type: application/xml; charset=utf-8');
     echo $CNML->asXML();
     return;
     break;
   case 'ips':
   	 $CNML=dump_guifi_ips($cnmlid);
     drupal_set_header('Content-Type: application/xml; charset=utf-8');
     echo $CNML->asXML();
     return;
     break;
   case 'plot':
     plot_guifi($cnmlid);
     return;
     break;
  }


  // load devices in memory for faster execution
  global $devices;

  $qdevices = db_query($sql_devices);
  while ($device = db_fetch_object($qdevices)) {
      $devices[$device->nid][$device->id] = $device;
  }

  // load radios in memory for faster execution
  global $radios;

  $qradios = db_query($sql_radios);
  while ($radio = db_fetch_object($qradios)) {
      $radios[$radio->nid][$radio->id][$radio->radiodev_counter] = $radio;
  }

  // load interfaces in memory for faster execution
  global $interfaces;

  $qinterfaces = db_query($sql_interfaces);
  while ($interface = db_fetch_object($qinterfaces)) {
      $interfaces[$interface->device_id][$interface->radiodev_counter][$interface->interface_id][] = $interface;
  }

  // load links in memory for faster execution
  global $links;

  $qlinks = db_query($sql_links);
  while ($link = db_fetch_object($qlinks)) {
      $links[$link->device_id][$link->interface_id][$link->id] = $link;
  }

  // load services in memory for faster execution
  global $services;

  $qservices = db_query($sql_services);
  while ($service = db_fetch_object($qservices)) {
      $services[$service->device_id][$service->id] = $service;
  }

  // load radio models in memory for faster execution
  global $models;
  $qmodel = db_query("SELECT mid, fid, model FROM guifi_model ORDER BY mid");
  while ($model = db_fetch_object($qmodel)) {
     $models[$model->mid] = $model->model;
  }

// print_r($models);


//  print_r($tree);


  function _add_cnml_node(&$CNML,$node,&$summary,$action) {

    global $devices;
    global $radios;
    global $interfaces;
    global $links;
    global $services;
    global $models;

    $nodesummary->ap = 0;
    $nodesummary->client = 0;
    $nodesummary->devices = 0;
    $nodesummary->services = 0;
    $nodesummary->links = 0;

    if ($action != 'zones') {
      $nodeXML = $CNML->addChild('node',htmlspecialchars($node->body,ENT_QUOTES));
      foreach ($node as $key=>$value) {
       if ($value) switch ($key) {
         case 'body': break;
         case 'id': $nodeXML->addAttribute('id',$value); break;
         case 'nick': $nodeXML->addAttribute('title',$value); break;
         case 'lat': $nodeXML->addAttribute('lat',$value); break;
         case 'lon': $nodeXML->addAttribute('lon',$value); break;
         case 'elevation': if ($value) $nodeXML->addAttribute('antenna_elevation',$value); break;
         case 'status_flag': $nodeXML->addAttribute('status',$value); break;
         case 'graph_server': $nodeXML->addAttribute('graph_server',$value); break;
         case 'timestamp_created': $nodeXML->addAttribute('created',date('Ymd hi',$value)); break;
         case 'timestamp_changed': $nodeXML->addAttribute('updated',date('Ymd hi',$value)); break;
       }
      }
    }
    $summary->nodes++;
    if ($node->lon < $summary->minx) $summary->minx = $node->lon;
    if ($node->lat < $summary->miny) $summary->miny = $node->lat;
    if ($node->lon > $summary->maxx) $summary->maxx = $node->lon;
    if ($node->lat > $summary->maxy) $summary->maxy = $node->lat;

    // if report type = 'detail', going to list all node content
    // devices
    if (is_array($devices[$node->id])) if (count($devices[$node->id])) {
      foreach ($devices[$node->id] as $id=>$device) {
        if ($action == 'detail') {
          $deviceXML = $nodeXML->addChild('device',htmlspecialchars($device->comment,ENT_QUOTES));
         foreach ($device as $key=>$value) {
          if ($value) switch ($key) {
            case 'body': comment;
            case 'id': $deviceXML->addAttribute('id',$value); break;
            case 'nick': $deviceXML->addAttribute('title',$value); break;
            case 'type': $deviceXML->addAttribute('type',$value); break;
            case 'flag': $deviceXML->addAttribute('status',$value); break;
            case 'graph_server': $deviceXML->addAttribute('graph_server',$value); break;
            case 'timestamp_created': $deviceXML->addAttribute('created',date('Ymd hi',$value)); break;
            case 'timestamp_changed': $deviceXML->addAttribute('updated',date('Ymd hi',$value)); break;
          }
         }
         if (!empty($device->extra)) {
           $device->variable = unserialize($device->extra);
           if ($device->type == 'radio')
           if (isset($device->variable['firmware']))
             $deviceXML->addAttribute('firmware',($device->variable['firmware']));
           if (isset($device->variable['model_id'])) {
             $model_name = $models[(int)$device->variable['model_id']];
             $deviceXML->addAttribute('name',$model_name);
           }
           if (!empty($device->variable['mrtg_index']))
             $deviceXML->addAttribute('snmp_index',($device->variable['mrtg_index']));
         }
        }
        $nodesummary->devices++;

        // device radios
        if (is_array($radios[$node->id][$device->id])) if (count($radios[$node->id][$device->id])) {
          foreach ($radios[$node->id][$device->id] as $id=>$radio) {
            if ($action == 'detail') {
              $radioXML = $deviceXML->addChild('radio',htmlspecialchars($radio->comment,ENT_QUOTES));
              $radioXML->addAttribute('id',$radio->radiodev_counter);
              $radioXML->addAttribute('device_id',$device->id);
              foreach ($radio as $key=>$value) {
               if ($value) switch ($key) {
                 case 'radiodev_counter':
                 case 'comment': break;
                 case 'ssid': $radioXML->addAttribute('ssid',$value); break;
                 case 'mode': $radioXML->addAttribute('mode',$value); break;
                 case 'protocol': $radioXML->addAttribute('protocol',$value); break;
                 case 'channel': $radioXML->addAttribute('channel',$value); break;
                 case 'antenna_angle': $radioXML->addAttribute('antenna_angle',$value); break;
                 case 'antenna_gain': $radioXML->addAttribute('antenna_gain',$value); break;
                 case 'antenna_azimuth': $radioXML->addAttribute('antenna_azimuth',$value); break;
               }
              }
              if (isset($device->variable['model_id']))
              if (in_array($model_name,
                     array('WRT54Gv1-4','WHR-HP-G54, WHR-G54S','WRT54GL','WRT54GSv1-2','WRT54GSv4'))) {
               switch ($device->variable['firmware']) {
               case 'whiterussian':
                if ($radio->mode == 'client') {
                  $radioXML->addAttribute('snmp_name','eth1');
                 } else {
                  $radioXML->addAttribute('snmp_name','br0');
                 }
                 break;
               case 'kamikaze':
                if ($radio->mode == 'client') {
                  $radioXML->addAttribute('snmp_name','eth0.1');
                 } else {
                  $radioXML->addAttribute('snmp_name','br-lan');
                 }
                 break;
               default:
                 $radioXML->addAttribute('snmp_index',6);
               }
              } else if  (in_array($model_name,
                // TODO, for mikrotiks would be better to use fid instead of model name?
                     array(
                       'Supertrasto RB532 guifi.net' ,
                       'Supertrasto RB133C guifi.net' ,
                       'Supertrasto RB133 guifi.net' ,
                       'Supertrasto RB112 guifi.net' ,
                       'Supertrasto RB153 guifi.net' ,
                       'Supertrasto RB600 guifi.net' ,
                       'Supertrasto RB333 guifi.net' ,
                       'Supertrasto RB411 guifi.net',
                       'Supertrasto RB412 guifi.net',
                       'Supertrasto RB433 guifi.net'))) {
                $radioXML->addAttribute('snmp_name','wlan'.(string) ($id + 1));
              }
                else if  (in_array($model_name,
                     array('NanoStation2' , 'NanoStation5'))) {
                 switch ($device->variable['firmware']) {
                 case 'kamikaze':
                  $radioXML->addAttribute('snmp_name','ath0');
                 case 'AirOsv30':
                 case 'AirOsv221':
                  $radioXML->addAttribute('snmp_name','wifi0');
                   break;
                   }
              }
            }
            switch ($radio->mode) {
              case 'ap': $nodesummary->ap++; break;
              case 'client': $nodesummary->client++; break;
            }

            // device radio interfaces
            if (is_array($interfaces[$device->id][$radio->radiodev_counter])) if (count($interfaces[$device->id][$radio->radiodev_counter])) {
              foreach ($interfaces[$device->id][$radio->radiodev_counter] as $radio_interfaces)
              foreach ($radio_interfaces as $interface) {
                if (!array_search($interface->interface_type,array('a'=>'wds/p2p','b'=>'wLan','c'=>'wLan/Lan','d'=>'Wan')))
                  continue;
                if ($interface->interface_type == 'Wan' and $radio->mode != 'client') continue;
                if ($action == 'detail') {
                  $interfaceXML = $radioXML->addChild('interface');
                  foreach ($interface as $key=>$value) {
                    if ($value) switch ($key) {
                      case 'id': $interfaceXML->addAttribute('id',$interface->id); break;
                      case 'mac': $interfaceXML->addAttribute('mac',$interface->mac); break;
                      case 'ipv4': $interfaceXML->addAttribute('ipv4',$interface->ipv4); break;
                      case 'netmask': $interfaceXML->addAttribute('mask',$interface->netmask); break;
                      case 'interface_type': $interfaceXML->addAttribute('type',$interface->interface_type); break;
                    }
                  }
                }

                // linked interfaces
                if (is_array($links[$device->id][$interface->id])) if (count($links[$device->id][$interface->id])) {
                  foreach ($links[$device->id][$interface->id] as $id=>$link) {
                    if (!array_search($link->link_type,array('a'=>'ap/client','b'=>'wds')))
                      continue;
                    if ($link->ipv4_id != $interface->ipv4_id) continue;
                    $nodesummary->links++;
                    if ($action == 'detail') {
                      $linkXML = $interfaceXML->addChild('link');
                      foreach ($link as $key=>$value) {
                        if ($value) switch ($key) {
                          case 'id': $linkXML->addAttribute('id',$link->id); break;
                          case 'linked_node_id': $linkXML->addAttribute('linked_node_id',$link->linked_node_id); break;
                          case 'linked_device_id': $linkXML->addAttribute('linked_device_id',$link->linked_device_id); break;
                          case 'linked_interface_id': $linkXML->addAttribute('linked_interface_id',$link->linked_device_id); break;
                          case 'link_type': $linkXML->addAttribute('link_type',$link->link_type); break;
                          case 'status': $linkXML->addAttribute('link_status',$link->status); break;
                        }
                      }
                    }
                  } // foreach link
                } //interface links


              } // foreach radio interface
            } // radio interfaces

          } // foreach radios
        } // device radios

        // device interfaces
        if (is_array($interfaces[$device->id])) if (count($interfaces[$device->id])) {
          foreach ($interfaces[$device->id] as $device_interfaces)
          foreach ($device_interfaces as $counter_interfaces)
          foreach ($counter_interfaces as $interface) {
            if (array_search($interface->interface_type,array('a'=>'wds/p2p','b'=>'wLan','c'=>'wlan/Lan')))
              continue;
            if ($action == 'detail') {
              $interfaceXML = $deviceXML->addChild('interface');
              foreach ($interface as $key=>$value) {
                if ($value) switch ($key) {
                  case 'id': $interfaceXML->addAttribute('id',$interface->id); break;
                  case 'mac': $interfaceXML->addAttribute('mac',$interface->mac); break;
                  case 'ipv4': $interfaceXML->addAttribute('ipv4',$interface->ipv4); break;
                  case 'netmask': $interfaceXML->addAttribute('mask',$interface->netmask); break;
                  case 'interface_type': $interfaceXML->addAttribute('type',$interface->interface_type); break;
                }
              }
            }

            // linked interfaces
            if (is_array($links[$device->id][$interface->id])) if (count($links[$device->id][$interface->id])) {
              foreach ($links[$device->id][$interface->id] as $id=>$link) {
                if (array_search($link->link_type,array('a'=>'ap/client','b'=>'wds')))
                  continue;
                if ($link->ipv4_id != $interface->ipv4_id) continue;
                if ($action == 'detail') {
                  $linkXML = $interfaceXML->addChild('link');
                  foreach ($link as $key=>$value) {
                    if ($value) switch ($key) {
                      case 'id': $linkXML->addAttribute('id',$link->id); break;
                      case 'linked_node_id': $linkXML->addAttribute('linked_node_id',$link->linked_node_id); break;
                      case 'linked_device_id': $linkXML->addAttribute('linked_device_id',$link->linked_device_id); break;
                      case 'linked_interface_id': $linkXML->addAttribute('linked_interface_id',$link->linked_device_id); break;
                      case 'link_type': $linkXML->addAttribute('link_type',$link->link_type); break;
                      case 'status': $linkXML->addAttribute('link_status',$link->status); break;
                    }
                  }
                }
              } // foreach link
            } //interface links
          } // foreach interface
        } //interface

        // services
        if (is_array($services[$device->id])) if (count($services[$device->id])) {
          foreach ($services[$device->id] as $id=>$service) {
            if ($action == 'detail') {
              $serviceXML = $deviceXML->addChild('service',htmlspecialchars($service->body,ENT_QUOTES));
              foreach ($service as $key=>$value) {
                if ($value) switch ($key) {
                  case 'body':              break;
                  case 'id':                $serviceXML->addAttribute('id',$value); break;
                  case 'nick':              $serviceXML->addAttribute('title',$value); break;
                  case 'service_type':      $serviceXML->addAttribute('type',$value); break;
                  case 'status_flag':       $serviceXML->addAttribute('status',$value); break;
                  case 'timestamp_created': $serviceXML->addAttribute('created',date('Ymd hi',$value)); break;
                  case 'timestamp_changed': $serviceXML->addAttribute('updated',date('Ymd hi',$value)); break;
                }
              }
            }
            $nodesummary->services++;
          } // foreach service
        } // service

      } // foreach device
    } // devices
    $summary->ap      += $nodesummary->ap;
    $summary->client  += $nodesummary->client;
    $summary->devices += $nodesummary->devices;
    $summary->links   += $nodesummary->links;
    $summary->services+= $nodesummary->services;

    if ($action != 'zones') {
      if ($nodesummary->ap) $nodeXML->addAttribute('access_points',$nodesummary->ap);
      if ($nodesummary->client) $nodeXML->addAttribute('clients',$nodesummary->client);
      if ($nodesummary->devices) $nodeXML->addAttribute('devices',$nodesummary->devices);
      if ($nodesummary->links) $nodeXML->addAttribute('links',$nodesummary->links);
      if ($nodesummary->services) $nodeXML->addAttribute('services',$nodesummary->services);
    }

    return;
  } // _add_cnml_node

  function _add_cnml_zone(&$CNML,$zone,$action) {
    $summary->nodes = 0;
    $summary->minx = 179.9;
    $summary->miny = 89.9;
    $summary->maxx = -179.9;
    $summary->maxy = -89.9;
    $summary->devices = 0;
    $summary->ap = 0;
    $summary->client = 0;
    $summary->services = 0;
    $summary->links = 0;

    $zoneXML = $CNML->addChild('zone',htmlspecialchars($zone->body,ENT_QUOTES));
    reset($zone);
    foreach ($zone as $key=>$value) {
     if ($value) switch ($key) {
       case 'body': break;
       case 'childs':
        foreach ($value as $child) {
            $summary2 = _add_cnml_zone($zoneXML,$child,$action);
            $summary->nodes   += $summary2->nodes;
            $summary->ap      += $summary2->ap;
            $summary->client  += $summary2->client;
            $summary->servers += $summary2->servers;
            $summary->links   += $summary2->links;
            $summary->services+= $summary2->services;
            if ($summary2->minx < $summary->minx) $summary->minx = $summary2->minx;
            if ($summary2->miny < $summary->miny) $summary->miny = $summary2->miny;
            if ($summary2->maxx > $summary->maxx) $summary->maxx = $summary2->maxy;
            if ($summary2->maxy > $summary->maxy) $summary->maxy = $summary2->maxy;
          }
          break;
       case 'nodes':
          $summary = (object)array();
          foreach ($value as $child)
            _add_cnml_node($zoneXML,$child,$summary,$action);
          break;
       case 'id': $zoneXML->addAttribute('id',$value); break;
       case 'parent_id': $zoneXML->addAttribute('parent_id',$value); break;
       case 'title': $zoneXML->addAttribute('title',$value); break;
       case 'time_zone': $zoneXML->addAttribute('time_zone',$value); break;
       case 'ntp_servers': $zoneXML->addAttribute('ntp_servers',$value); break;
       case 'dns_servers': $zoneXML->addAttribute('dns_servers',$value); break;
       case 'graph_server': $zoneXML->addAttribute('graph_server',$value); break;
       case 'timestamp_created': $zoneXML->addAttribute('created',date('Ymd hi',$value)); break;
       case 'timestamp_changed': $zoneXML->addAttribute('updated',date('Ymd hi',$value)); break;
     }
    }
    $zoneXML->addAttribute('zone_nodes',$summary->nodes);

    if (($zone->minx != 0) and ($zone->miny != 0) and ($zone->maxx != 0) and ($zone->maxy != 0))
      $zoneXML->addAttribute('box',$zone->minx.','.$zone->miny.','.$zone->maxx.','.$zone->maxy);
    else
      $zoneXML->addAttribute('box',$summary->minx.','.$summary->miny.','.$summary->maxx.','.$summary->maxy);

    if ($summary->ap)       $zoneXML->addAttribute('access_points',$summary->ap);
    if ($summary->client)   $zoneXML->addAttribute('clients',      $summary->client);
    if ($summary->devices)  $zoneXML->addAttribute('devices',      $summary->devices);
    if ($summary->services) $zoneXML->addAttribute('services',     $summary->services);
    if ($summary->links)    $zoneXML->addAttribute('links',        $summary->links);

    return $summary;
  }

  $summary->nodes = 0;
  $summary->minx = 179.9;
  $summary->miny = 89.9;
  $summary->maxx = -179.9;
  $summary->maxy = -89.9;
  $summary->devices = 0;
  $summary->ap = 0;
  $summary->client = 0;
  $summary->services = 0;
  $summary->links = 0;

  $CNML = new SimpleXMLElement('<cnml></cnml>');
  $CNML->addAttribute('version','0.1');
  $CNML->addAttribute('server_id','1');
  $CNML->addAttribute('server_url','http://guifi.net');
  $CNML->addAttribute('generated',date('Ymd hi',time()));
  $classXML = $CNML->addChild('class');

  if ($action != 'node') {
    $classXML->addAttribute('network_description',$action);
    $classXML->addAttribute('mapping','y');
    $networkXML = $CNML->addChild('network');

    if (count($tree))
    foreach ($tree as $zone_id=>$zone) {
      $summary2 = _add_cnml_zone($networkXML,$zone,$action);
      $summary->nodes   += $summary2->nodes;
      $summary->ap      += $summary2->ap;
      $summary->client  += $summary2->client;
      $summary->servers += $summary2->servers;
      $summary->links   += $summary2->links;
      $summary->services+= $summary2->services;

    }

    $networkXML->addAttribute('nodes',$summary->nodes);
    $networkXML->addAttribute('devices',$summary->devices);
    $networkXML->addAttribute('ap',$summary->ap);
    $networkXML->addAttribute('client',$summary->client);
    $networkXML->addAttribute('services',$summary->services);
    $networkXML->addAttribute('links',$summary->links);
  } else {
    $classXML->addAttribute('node_description',$cnmlid);
    $classXML->addAttribute('mapping','y');

    $summary->devices = 0;
    $summary->ap = 0;
    $summary->client = 0;
    $summary->services = 0;
    $summary->links = 0;

    // print_r($tree);
    foreach ($tree as $nodeid=>$node) {
      $summary = _add_cnml_node($CNML,$node,$summary,'detail');
    }
  }

  drupal_set_header('Content-Type: application/xml; charset=utf-8');
  echo $CNML->asXML();

  return;

}

function fnodecount($cnmlid){
  if($cnmlid<0 or $cnmlid>9){
    $vid=0;
  }else{
    $vid=$cnmlid;
  }
  $CNML = new SimpleXMLElement('<cnml></cnml>');
  $CNML->addAttribute('version','0.1');
  $CNML->addAttribute('server_id','1');
  $CNML->addAttribute('server_url','http://guifi.net');
  $CNML->addAttribute('generated',date('Ymd hi',time()));
  switch ($vid){
  case 5:
  case 6:
  case 7:
  case 8:
  case 0: //compte els nodes per any
  	$result=db_query("select COUNT(*) as num, YEAR(FROM_UNIXTIME(timestamp_created)) as ano from {guifi_location} GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)) ");
	$classXML = $CNML->addChild('nodesxyear');
	$nreg=0;
	while ($record=db_fetch_object($result)){
	  $nreg++;
	  $reg = $classXML->addChild('rec');
	  $reg->addAttribute('year',$record->ano);
	  $reg->addAttribute('nodes',$record->num);
	};
	$classXML->addAttribute('numyears',$nreg);
	break;
  case 1:  //compte els nodes per any i estat
  	$result=db_query("select COUNT(*) as num,status_flag, YEAR(FROM_UNIXTIME(timestamp_created)) as ano from {guifi_location} GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)),status_flag ");
	$classXML = $CNML->addChild('nodesxyearxstatus');
	$nreg=0;
	$nyear=0;
	$vyear='';
	while ($record=db_fetch_object($result)){
	  $nreg++;
	  if($record->ano!=$vyear){
	    $vyear=$record->ano;
	    $nyear++;
	  }
	  $reg = $classXML->addChild('rec');
	  $reg->addAttribute('year',$record->ano);
	  $reg->addAttribute('nodes',$record->num);
	  $reg->addAttribute('status',$record->status_flag);
	};
	$classXML->addAttribute('numrecs',$nreg);
	$classXML->addAttribute('numyears',$nyear);
	break;
  case 2:  //compta els nodes per estat
  	$result=db_query("select COUNT(*) as num, status_flag from {guifi_location} GROUP BY status_flag ");
	$classXML = $CNML->addChild('nodesxstatus');
	$nreg=0;
	while ($record=db_fetch_object($result)){
	  $nreg++;
	  $reg = $classXML->addChild('rec');
	  $reg->addAttribute('nodes',$record->num);
	  $reg->addAttribute('status',$record->status_flag);
	};
	$classXML->addAttribute('numstatus',$nreg);
	break;
  case 3:  //torna els nodes actius totals
  	$result=db_query("select COUNT(*) as num from {guifi_location} where status_flag='Working'");
	$classXML = $CNML->addChild('totalactivenodes');
	$nreg=0;
	if ($record=db_fetch_object($result)){
		$nreg++;
		$classXML->addAttribute('nodes',$record->num);
	};
	$classXML->addAttribute('result',$nreg);
	break;
  case 4:  //torna els nombre i distancies dels enllaços per tipo denllaç'
    $oGC = new GeoCalc();
    $dTotals = array();
    $qlinks = db_query('
      SELECT
        l1.id, n1.id nid1, n2.id nid2, l1.link_type, n1.lat lat1,
        n1.lon lon1, n2.lat lat2, n2.lon lon2
      FROM guifi_links l1
        LEFT JOIN guifi_links l2 ON l1.id=l2.id
        LEFT JOIN guifi_location n1 ON l1.nid=n1.id
        LEFT JOIN guifi_location n2 ON l2.nid=n2.id
      WHERE l1.nid != l2.nid AND l1.device_id != l2.device_id');
    unset($listed);
    while ($link = db_fetch_object($qlinks)) {
      if (!isset($listed[$link->id]) ){
		$listed[$link->id] = $link;
	    $d = round($oGC->EllipsoidDistance($link->lat1,$link->lon1,$link->lat2,$link->lon2),1);
	    switch ($link->link_type) {
	      case 'wds': $type=t('PtP link'); break;
	      case 'ap/client': $type=t('ap/client'); break;
	      default: $type=t('unknown');
	    }
	    if ($d < 100) {
	      $dTotals[$type]['dTotal'] += $d;
	      $dTotals[$type]['count'] ++;
	    }else{
	      guifi_log(GUIFILOG_BASIC,sprintf('Probable DISTANCE error between nodes (%d and %d) %d kms.',
	        $link->nid1, $link->nid2, $d));
	    }
      }
    }
	$classXML = $CNML->addChild('linksxtype');
	$nreg=0;
    if (count($dTotals)) foreach ($dTotals as $key=>$dTotal){
	    if ($dTotal['dTotal']) {
	  	  $nreg++;
	  	  $reg = $classXML->addChild('rec');
		  $reg->addAttribute('type',$key);
		  $reg->addAttribute('links',$dTotal['count']);
		  $reg->addAttribute('km',$dTotal['dTotal']);
	    }
    }
	$classXML->addAttribute('numtypes',$nreg);
	break;
  case 9:  //torna els nodes actius totals i els del ultim minut
	$afecha=getdate();
	$tiempomin=mktime($afecha[hours],$afecha[minutes]-1,$afecha[seconds],$afecha[mday],$afecha[mon],$afecha[year]);
	$tiempomax=$tiempomin+60;
  	$result=db_query("select COUNT(*) as num from {guifi_location} where status_flag='Working'");
  	$result2=db_query("select COUNT(*) as num from {guifi_location} where timestamp_created>".$tiempomin." and timestamp_created<=".$tiempomax."");
	$classXML = $CNML->addChild('totalnodes');
	$nreg=0;
	if ($record=db_fetch_object($result)){
		if ($record2=db_fetch_object($result2)){
	  		$nreg++;
	  		$classXML->addAttribute('nodes',$record->num);
	  		$classXML->addAttribute('nodeslastmin',$record2->num);
		}
	};
	$classXML->addAttribute('result',$nreg);
	break;
  }
  return $CNML;
}


// Creates CNML with all guifi's IPs, used to generate DNS Reverse Resolution zones (RRZ)
function dump_guifi_ips($cnmlid){
  $CNML = new SimpleXMLElement('<cnml></cnml>');
  $CNML->addAttribute('version','0.1');
  $CNML->addAttribute('server_id','1');
  $CNML->addAttribute('server_url','http://guifi.net');
  $CNML->addAttribute('generated',date('Ymd hi',time()));

  // 172.x.x.x
  $result=db_query("SELECT t1.ipv4, t2.device_id, t3.nick FROM guifi_ipv4 AS t1 JOIN guifi_interfaces AS t2 ON t1.interface_id = t2.id JOIN guifi_devices AS t3 ON t2.device_id = t3.id WHERE t1.ipv4 LIKE '172%' ");
  $classXML = $CNML->addChild('subnet');
	while ($record=db_fetch_object($result)){
	  $reg = $classXML->addChild('IP');
	  $reg->addAttribute('address',$record->ipv4);
	  $reg->addAttribute('device_id',$record->device_id);
	  $reg->addAttribute('nick',$record->nick);
	};
	$classXML->addAttribute('range','172');

  // 10.x.x.x
  $result=db_query("SELECT t1.ipv4, t2.device_id, t3.nick FROM guifi_ipv4 AS t1 JOIN guifi_interfaces AS t2 ON t1.interface_id = t2.id JOIN guifi_devices AS t3 ON t2.device_id = t3.id WHERE t1.ipv4 LIKE '10%' ");
  $classXML = $CNML->addChild('subnet');
	while ($record=db_fetch_object($result)){
	  $reg = $classXML->addChild('IP');
	  $reg->addAttribute('address',$record->ipv4);
	  $reg->addAttribute('device_id',$record->device_id);
	  $reg->addAttribute('nick',$record->nick);
	};
	$classXML->addAttribute('range','10');

  return $CNML;
}
function plot_guifi($cnmlid){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $result=db_query("select COUNT(*) as num, MONTH(FROM_UNIXTIME(timestamp_created)) as mes, YEAR(FROM_UNIXTIME(timestamp_created)) as ano from {guifi_location} where status_flag='Working' GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)),MONTH(FROM_UNIXTIME(timestamp_created)) ");
	$nreg=5;
    $tot=0;
    $ano=2004;
    $mes=5;
    $items=2004;
    $label="";
	while ($record=db_fetch_object($result)){
      if($record->ano>=2004){
         if($mes==12){
            $mes=1;
            $ano++;
         }else{
            $mes++;
         }
         while ($ano<$record->ano || $mes<$record->mes){
            $nreg++;
            $data[]=array("$label",$nreg,$tot);
            if($mes==12){
               $mes=1;
               $ano++;
            }else{
               $mes++;
            }
         }
         $tot+=$record->num;
         $nreg++;
         $data[]=array("$label",$nreg,$tot);
      }else{
         $tot+=$record->num;
      };
	};
    $items=($ano-$items+1)*12;

    $plot = new PHPlot(200,150);
    $plot->SetPlotAreaWorld(0, 0,$items,NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("data-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("lines");
    $plot->SetYTickIncrement(1000);
    $plot->SetXTickIncrement(12);
    $plot->SetSkipBottomTick(true);
    $plot->SetSkipLeftTick(true);
    $plot->SetXAxisPosition(0);
    $plot->SetPointShapes('none');
    $plot->SetTickLength(3);
    $plot->SetDrawXGrid(true);
    $plot->SetTickColor('grey');
    $plot->SetTitle('Nodes operatius');
    $plot->SetXDataLabelPos('none');
    $plot->SetDrawXDataLabelLines(false);
    $plot->SetXLabelAngle(0);
    $plot->SetXLabelType('custom', 'Plot1_LabelFormat');
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(true);
    $plot->DrawGraph();
}
function Plot1_LabelFormat($value){
   $v=$value/12+2003;
   return ("{$v}   .");
}
?>
