<?php
/**
 * guifi_cnml
**/

function guifi_cnml($cnmlid,$action = 'help') {

  if ($action == "help") {
     $zone = db_fetch_object(db_query('SELECT title, nick FROM {guifi_zone} WHERE id = %d',$cnmlid));
     $output = '<div id="guifi">';
     $output .= guifi_zone_ariadna($cnmlid);
     $output .= '<h2>'.t('Zone %zname%',array('%zname%'=>$zone->title)).'</h2>';
     $output .= '<p>'.t('You must specify which data do you want to export, the following options are available:').'</p>';
     $output .= '<ol><li>'. l(t('Zones'), "guifi/cnml/".$cnmlid."/zones", array('title'=>t('export zone and zone childs in CNML format')) ).'</li>';
     $output .= '<li>'. l(t('Zones and nodes'), "guifi/cnml/".$cnmlid."/nodes", array('title'=>t('export zones and nodes in CNML format (short)')) ).'</li>';
     $output .= '<li>'. l(t('Detailed'), "guifi/cnml/".$cnmlid."/detail", array('title'=>t('export zones, nodes  and devices in CNML format (long)')) ).'</li></ol>';
     $output .= '<p>'.t('The <b>C</b>ommunity <b>N</b>etwork <b>M</b>arkup <b>L</b>anguage (<a href="/node/3521>">CNML</a>) is a XML format to interchange network information between services or servers.').'</p>';
     $output .= '<p>'.t('<b>IMPORTANT LEGAL NOTE:</b> This network information is under the <a href="http://guifi.net/ComunsSensefils/">Comuns Sensefils</a> license, and therefore, available for any other network under the same licensing. If is not your case, you should ask for permission before using it.</a>').'</p>';
     $output .= "</div>";
     print theme('page',$output,t('export %zname% in CNML format',array('%zname%' => $z->title)));
     return;
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
     $sql_services = 'SELECT s.*,n.body FROM {guifi_services} s, {node} n WHERE n.nid=s.id';
     break;
   case 'node':
     $qnode = db_query(sprintf('SELECT l.*,n.body FROM {guifi_location} l, {node} n WHERE l.id=n.nid AND l.id in (%s)',$cnmlid));
     while ($node = db_fetch_object($qnode)) {
       $tree[] = $node;
     }
     $sql_devices = sprintf('SELECT * FROM {guifi_devices} d WHERE nid in (%s)',$cnmlid);
     $sql_radios = sprintf('SELECT * FROM {guifi_radios} r',$cnmlid);
     $sql_interfaces = sprintf('SELECT i.*,a.ipv4,a.id ipv4_id, a.netmask FROM {guifi_devices} d, {guifi_interfaces} i, {guifi_ipv4} a WHERE d.nid in (%s) AND d.id=i.device_id AND i.id=a.interface_id',$cnmlid);
     $sql_links = sprintf('SELECT l1.id, l1.device_id, l1.interface_id, l1.ipv4_id, l2.device_id linked_device_id, l2.nid linked_node_id, l2.interface_id linked_interface_id, l2.ipv4_id linked_radiodev_counter, l1.link_type, l1.flag status FROM {guifi_links} l1, {guifi_links} l2 WHERE l1.nid in (%s) AND l1.id=l2.id AND l1.device_id != l2.device_id',$cnmlid);
     $sql_services = sprintf('SELECT s.*,n.body FROM {guifi_devices} d, {guifi_services} s, {node} n WHERE d.nid=%d AND d.id=s.device_id AND n.nid=s.id',$cnmlid);
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
  $qmodel = db_query("SELECT mid, model FROM guifi_model ORDER BY mid");
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
               case 'kamikaze': 
                 $radioXML->addAttribute('snmp_index',3);
                 break;
               default:
                 $radioXML->addAttribute('snmp_index',6);
               }
              } else if  (in_array($model_name,
                     array('Supertrasto RB532 guifi.net' , 'Supertrasto RB133C guifi.net' , 'Supertrasto RB133 guifi.net' , 'Supertrasto RB112 guifi.net' , 'Supertrasto RB153 guifi.net'))) {
                $radioXML->addAttribute('snmp_name','wlan'.(string)$id);
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

?>
