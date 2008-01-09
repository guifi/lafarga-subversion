<?php

/**
 * guifi_graph_detail
 * outputs a page with node detailed graphs
**/
function guifi_graph_detail() {
  $type = $_GET['type'];
  if (isset($_GET['radio'])) {
      $query = db_query("SELECT r.id, r.nick, n.title, r.nid, l.zone_id FROM {guifi_devices} r, {node} n, {guifi_location} l WHERE r.id=%d AND n.nid=r.nid AND n.nid = l.id",$_GET['radio']);
      $radio = db_fetch_object($query);
      $zid = $radio->zone_id;
  }
  
  if ($type=='supernode') {
    $node = node_load(array('nid' => $_GET['node']));
    if ($node->graph_server == -1) {
      $rows[] = array(t('This node has the graphs disabled.'));
      return array_merge($rows);
    }
    if (empty($node->graph_server))
      $server_mrtg = guifi_node_get_url_mrtg($node->id);
    else
      $server_mrtg = guifi_get_graph_url($node->graph_server);
  } else {   
    if ($radio->graph_server == -1) {
      $rows[] = array(t('This device has the graphs disabled.'));
      return array_merge($rows);
    }
    if (empty($radio->graph_server))
      $server_mrtg = guifi_radio_get_url_mrtg($radio->id);
    else
      $server_mrtg = guifi_get_graph_url($radio->graph_server);
  }

  $help = t('Here you have a detailed view of the available information for several periods of time (daily, weekly, monthly and yearly). You can obtain a detailed graph for a given period of time by entering the period in the boxes below.');
  switch ($type) {
    case 'clients':
      $title = '<a href="/guifi/device/'.$radio->id.'">'.$radio->nick.'</a> '.t('at').' '.'<a href=node/'.$radio->nid.'>'.$radio->title.'</a>';
      $args = sprintf('<img src="%s?type=clients&node=%d&radio=%d&direction=%s',$server_mrtg,$radio->nid,$_GET['radio'],$_GET['direction']);
      $help .= '<br />'.t('The clients graph show the top clients by transit.');
      break;
    case 'supernode':
      $zid = $node->zone_id;
      $title = '<a href=node/'.$_GET['node'].'>'.$node->title.'</a>';
      $args = sprintf('<img src="%s?type=supernode&node=%d&direction=%s',$server_mrtg,$_GET['node'],$_GET['direction']);
      $help .= '<br />'.t('Supernode graph show the transif of each radio.');
      break;
    case 'radio':
      $help= '<br />'.t('The radio graph show in &#038; out transit.');
    case 'pings':      
      if ($type != 'radio')
        $help= '<br />'.t('The ping graph show the latency and availability. High latency usually means bad connection. Yellow means % of failed pings, could be some yellow on the graphs, but must not reach value of 100, if the value reaches 100, that means that the radio is offline.');
      $args = sprintf('<img src="%s?type=%s&node=%d&radio=%d',$server_mrtg,$_GET['type'],$radio->nid,$_GET['radio']);
      $title = $radio->nick.' '.t('at').' '.'<a href=node/'.$radio->nid.'>'.$radio->title.'</a>';
      break;
  }

  $secs_day = 60*60*24;
  drupal_set_breadcrumb(guifi_zone_ariadna($zid)); 
  $output = '<div id="guifi">';

//  $rows[] = array(t('enter a timeframe to graph a customized period')); 
  $output .= '<h3>'.$type.'</h3>'.$help;
  switch ($type) {
  }
  if (isset($_POST['date1'])) 
    $date1 = $_POST['date1'];
  else
    $date1 = date('d-m-Y H:i',time()-60*60*2);
  if (isset($_POST['date2'])) 
    $date2 = $_POST['date2'];
  else
    $date2 = date('d-m-Y H:i',time()-300);
  $str = '<form name="form_timespan_selector" method="post"><strong>&nbsp;'.t('From:');
  $str .= '&nbsp;</strong><input type="text" name="date1" id=\'date1\' size=\'14\' value="'.$date1.'">&nbsp;<input type="image" 
src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar.gif" alt="Start date selector" onclick="return showCalendar(\'date1\');">&nbsp;';
  $str .= '<strong>'.t('To:').'&nbsp;</strong> <input type="text" name="date2" id=\'date2\' size="14" value="'.$date2.'"> &nbsp;';
  $str .= '<input type="image" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar.gif" alt="End date selector" align="absmiddle" onclick="return showCalendar(\'date2\');"> &nbsp;&nbsp;';
  $str .= '<input type="submit" name="button_refresh" action="submit" value="refresh">';
  $rows[] = array($str);
  if (isset($_POST['date1'])) {
    list($day,$month,$year,$hour,$min) = sscanf($_POST['date1'],'%d-%d-%d %d:%d');
    $start = mktime($hour, $min, 0, $month, $day, $year);
    list($day,$month,$year,$hour,$min) = sscanf($_POST['date2'],'%d-%d-%d %d:%d');
    $end = mktime($hour, $min, 0, $month, $day, $year);
    $rows[] = array(t('customized graph'));
    $rows[] = array(sprintf($args.'&start=%d&end=%d">',$start,$end));
  }
  $rows[] = array(t('day'));
  $rows[] = array(sprintf($args.'&start=-%d&end=%d">',$secs_day,-300));
  $rows[] = array(t('week'));
  $rows[] = array(sprintf($args.'&start=-%d&end=%d">',$secs_day * 7,-300));
  $rows[] = array(t('month'));
  $rows[] = array(sprintf($args.'&start=-%d&end=%d">',$secs_day * 31,-300));
  $rows[] = array(t('year'));
  $rows[] = array(sprintf($args.'&start=-%d&end=%d">',$secs_day * 365,-300));
  $output .= theme('table', NULL, array_merge($rows));
  $output .= "</div>"._guifi_script_calendar();
 
  drupal_set_html_head('<script type="text/javascript" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar.js"></script> <script type="text/javascript" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/lang/calendar-ca.js"></script></script> <script type="text/javascript" src="'.base_path(). drupal_get_path('module', 'guifi').'/contrib/calendar-setup.js"></script>'); 
  drupal_set_title(t('graph details for').' '.$title);
  return print theme('page', $output, t('graph details for').' '.$title);
}

/**
 * guifi_get_parent_mrtg
 * recursively iterates trough parent zones until a non-empty mrtg-server field is found
 **/

function guifi_get_parent_mrtg($zone,$old_mrtg = TRUE) {
    $query_zone= db_query("SELECT r.mrtg_servers,r.master,r.graph_server FROM {guifi_zone} r WHERE r.id=%d",$zone);
    $z=db_fetch_object($query_zone);

    if (!empty($z->graph_server))
      return guifi_get_graph_url($z->graph_server);
    if (!empty($z->mrtg_servers) and $old_mrtg)
      return $z->mrtg_servers;

    if ($z->master != 0)
       return guifi_get_parent_mrtg($z->master,$old_mrtg);

    return NULL;
}

function guifi_get_graph_url($sid) {
  $queryService = db_query("SELECT extra FROM {guifi_services} WHERE id=%d AND service_type='SNPgraphs'",$sid);
  $Service = db_fetch_object($queryService); 
  if (!empty($Service)) {
    $server_attr = unserialize($Service->extra);
    if (!empty($server_attr['url']))
      return $server_attr['url'];
  }
  return NULL;
}


function guifi_node_get_url_mrtg($node,$old_mrtg = TRUE) {

  $queryZone = db_query("SELECT r.graph_server, r.zone_id FROM {guifi_location} r WHERE r.id=%d",$node);
  $Zone = db_fetch_object($queryZone);
  if ($Zone->graph_server > 0)
    return guifi_get_graph_url($Zone->graph_server);

  // if node has ap, inherits from zone, if not, inherits from his ap
  $queryRadios = db_query("SELECT 'x' FROM {guifi_radios} WHERE nid=%d AND mode='ap'",$node);
  if (db_num_rows($queryRadios)>0)
    return guifi_get_parent_mrtg($Zone->zone_id,$old_mrtg);

  $queryRadios = db_query("SELECT id FROM {guifi_radios} WHERE nid=%d AND mode='client'",$node);
  if (db_num_rows($queryRadios)>0) {
    $Radio = db_fetch_object($queryRadios);
    return guifi_radio_get_url_mrtg($Radio->id, $old_mrtg);
  }

  return guifi_get_parent_mrtg($Zone->zone_id,$old_mrtg);	
}

function get_SSID_radio($radio) {
  $querySSID = db_query("SELECT r.ssid FROM {guifi_radios} r WHERE r.id=%d",$radio);
  $SSID = db_fetch_object($querySSID);
   return $SSID->ssid; 	
}


function guifi_radio_get_url_mrtg($radio, $old_mrtg = TRUE ) {

  $queryNode = db_query("SELECT d.nid FROM {guifi_devices} d WHERE d.id=%d",$radio);
  $Node = db_fetch_object($queryNode);
  $queryRadios = db_query("SELECT 'x' FROM {guifi_radios} WHERE nid=%d AND mode='ap'",$Node->nid);
  if (db_num_rows($queryRadios)>0) {
    // node has APs, inherits node graph server
    return guifi_node_get_url_mrtg($Node->nid,$old_mrtg);
  }

  // finding an ap/client link for this node, inherits from remote node
  $queryLinks = db_query("SELECT d.graph_server dg, n.graph_server ng, n.zone_id FROM {guifi_links} l1, {guifi_links} l2, {guifi_devices} d, {guifi_location} n WHERE l1.id=l2.id AND l1.nid != l2.nid AND l1.link_type='ap/client' AND l1.nid=%d AND d.id=l2.device_id AND n.id=l2.nid",$Node->nid);
  if (db_num_rows($queryLinks)>0) {
    $Link = db_fetch_object($queryLinks);
    if ($Link->dg == -1)
      return NULL;
    if ($Link->dg > 0)
      return guifi_get_graph_url($Link->dg);	
    if ($Link->ng == -1)
      return NULL;
    if ($Link->ng > 0)
      return guifi_get_graph_url($Link->ng);	
    return guifi_get_parent_mrtg($Link->zone_id,$old_mrtg);
  }
  return NULL;
}	


function guifi_get_graph_server($nid) {
}


/**
 * guifi_node_graph_overview
 * outputs an overiew graph of the node
**/
function guifi_node_graph_overview($node) {
  
/**
*	Get the zone 
**/

 if (empty($node->graph_server))
   $server_mrtg = guifi_node_get_url_mrtg($node->id);
 else 
   $server_mrtg = guifi_get_graph_url($node->graph_server);
 
 $query = db_query("SELECT * FROM {guifi_radios} WHERE nid=%d",$node->id);
  if (db_num_rows($query) > 1) { // Supernode, Totals In & Out
    if (substr($server_mrtg,0,3)=="fot"){	
	//  graph all devices.about a node. Ferran Ot
      while ($radio = db_fetch_object($query)){
        $ssid=get_SSID_radio($radio->id);
        $ssid=strtolower($ssid);
	$mrtg_url=substr($server_mrtg,3);
        $rows[] = array('<a href="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_6&cfg=mrtg.cfg" target="_blank"> <img src="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_6&cfg=mrtg.cfg&png=weekly"></a>');
        $rows[] = array('<a href="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_ping&cfg=mrtg.cfg" target="_blank"> <img src="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_ping&cfg=mrtg.cfg&png=weekly"></a>');
      }
	 return array_merge($rows);
    }else{
      $args = sprintf('type=supernode&node=%d&direction=',$node->id);
      $rows[] = array(sprintf('<a href=guifi/graph_detail?'.$args.'in><img src="'.$server_mrtg.'?'.$args.'in"></a>',$node->id));
      $rows[] = array(sprintf('<a href=guifi/graph_detail?'.$args.'out><img src="'.$server_mrtg.'?'.$args.'out"></a>',$node->id));
      return array_merge($rows);
    }
  } else {
    $radio = db_fetch_array($query);
    return guifi_device_graph_overview($radio);
  }
}

/**
 * guifi_device_graph_overview
 * outputs an overiew graph of the device
**/
function guifi_device_graph_overview($radio) {

 if ($radio['graph_server'] == -1) {
   $rows[] = array(t('This device has the graphs disabled.'));
   return array_merge($rows);
 }
 if (empty($radio['graph_server']))
   $server_mrtg = guifi_radio_get_url_mrtg($radio['id']);
 else 
   $server_mrtg = guifi_get_graph_url($radio['graph_server']);

 if (substr($server_mrtg,0,3) == "fot")
    { 
    $ssid=get_SSID_radio($radio['id']);
    $ssid=strtolower($ssid);	
    $mrtg_url=substr($server_mrtg,3);
    $rows[] = array('<a href="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_6&cfg=mrtg.cfg" target="_blank" > <img src="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_6&cfg=mrtg.cfg&png=weekly"></a>');
    $rows[] = array('<a href="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_ping&cfg=mrtg.cfg" target="_blank" > <img src="'.$mrtg_url.'/14all.cgi?log='.$ssid.'_ping&cfg=mrtg.cfg&png=weekly"></a>');

    return array_merge($rows);
    }
 else    
    {
      $filename = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile(guifi_get_hostname($radio['id'])).'_ping.rrd'; 
      if (!((file_exists($filename)) | (substr($server_mrtg,0,7) == "http://")))    // Checks whether to display graph on not, checking if is an internal or external graphing
      {
        $rows[] = array(t('This radio is not being graphed yet.'));
        return array_merge($rows);
      }
      $query = db_query("SELECT c.id FROM {guifi_links} c WHERE c.device_id=%d AND c.link_type in ('wds','ap/client','bridge')",$radio['id']);
      if (db_num_rows($query) > 1)  // several clients, Totals In & Out	
      { 
        $args = sprintf('type=clients&node=%d&radio=%d&direction=',$radio['nid'],$radio['id']);
        $rows[] = array(sprintf('<a href=guifi/graph_detail?'.$args.'in><img src="'.$server_mrtg.'?'.$args.'in"></a>',$radio['id']));
        $rows[] = array(sprintf('<a href=guifi/graph_detail?'.$args.'out><img src="'.$server_mrtg.'?'.$args.'out"></a>',$radio['id']));
      } 
      else 
      {
        $args = sprintf('type=radio&node=%d&radio=%d',$radio['nid'],$radio['id']);
        $rows[] = array('<a href=guifi/graph_detail?'.$args.'><img src="'.$server_mrtg.'?'.$args.'">');
      }
      $args = sprintf('type=pings&node=%d&radio=%d',$radio['nid'],$radio['id']);
      $rows[] = array('<a href=guifi/graph_detail?'.$args.'><img src="'.$server_mrtg.'?'.$args.'">');
      return array_merge($rows);
    }

}


/**
 * guifi_mrtg
**/

function guifi_mrtg() {
  
  if (is_numeric(arg(1)))
    $zoneid = arg(1);
  else
    $zoneid = arg(2);

  function wlan_traffic($rrdfile,$snmpIndex,$max,$txt,$row,$nl) {
     $output = "Target[".$rrdfile."_".$snmpIndex."]: ".$snmpIndex.":public@".$row['ip'].':';
//     $output .= $nl."SetEnv[".$rrdfile.'_'.$snmpIndex.']: MRTG_INT_IP="'.$row['ip'].'" MRTG_INT_DESCR="eth0"';
     $output .= $nl."SetEnv[".$rrdfile.'_'.$snmpIndex.']: MRTG_INT_IP="'.$row['ip'].'"';
     $output .= $nl.'MaxBytes['.$rrdfile.'_'.$snmpIndex.']: '.$max;
     $output .= $nl."Title[".$rrdfile."_".$snmpIndex."]: ".$txt." de ".$row['title'];
     $html = "PageTop[".$rrdfile."_".$snmpIndex."]: <H1>".$txt." de ".$row['title']."</H1>
     <TABLE>
     <TR><TD>System:</TD>     <TD>".$row['title']."</TD></TR>
     <TR><TD>Maintainer:</TD> <TD>guifi@guifi.net</TD></TR>
     <TR><TD>Description:</TD><TD>".$txt."</TD></TR>
     <TR><TD>IP:</TD>         <TD>".$row['ip']."</TD></TR>
     <TR><TD>Max Speed:</TD>  <TD>".$max." bits/sec</TD></TR>
     </TABLE>";
     if ($nl == "\n")
       $output .= $nl.$html;
     else
       $output .= $nl.htmlentities($html);
  
     return $nl.$output;
  }
  function wlan_routeros_traffic($rrdfile,$row,$ifid,$nl) {
     $ifDescr = 'wlan'.$ifid;
     $output = "Target[".$rrdfile."]: \\".$ifDescr.":public@".$row['ip'].':';
     $output .= $nl."SetEnv[".$rrdfile.']: MRTG_INT_IP="'.$row['ip'].'" MRTG_INT_DESCR="'.$ifDescr.'"';
     $output .= $nl.'MaxBytes['.$rrdfile.']: 3000000';
     $output .= $nl."Title[".$rrdfile."]: Trafic a ".$ifDescr." de ".$row['title'];
     $html = "PageTop[".$rrdfile."]: <H1>Tr&agrave;fic a ".$ifDescr." de ".$row['title']."</H1>
     <TABLE>
     <TR><TD>System:</TD>     <TD>".$row['title']."</TD></TR>
     <TR><TD>Maintainer:</TD> <TD>guifi@guifi.net</TD></TR>
     <TR><TD>Description:</TD><TD>".$ifDescr."</TD></TR>
     <TR><TD>Max Speed:</TD>  <TD>30.0 Mbits/s</TD></TR>
     </TABLE>";
     if ($nl == "\n")
       $output .= $nl.$html;
     else
       $output .= $nl.htmlentities($html);
  
     return $nl.$output;
  }
  function ping($rrdfile,$row,$nl) {
     $output .= 'Title['.$rrdfile.'_ping]: Temps del ping de '.$row['title'];
     $html = 'PageTop['.$rrdfile.'_ping]: <H1>Lat&egrave;ncia '.$row['title']."</H1>
     <TABLE
     <TR><TD>System:</TD>     <TD>".$row['title']."</TD></TR>
     <TR><TD>Maintainer:</TD> <TD>guifi@guifi.net</TD></TR>
     <TR><TD>Description:</TD><TD>ping  </TD></TR>
     <TR><TD>IP:</TD>         <TD>".$row['ip']."</TD></TR>
     </TABLE>";
     if ($nl == "\n")
       $output .= $nl.$html;
     else
       $output .= $nl.htmlentities($html);
     $output .= $nl.'Target['.$rrdfile.'_ping]: `/etc/mrtg/ping.sh '.$row['ip'].'`';
     $output .= $nl.'MaxBytes['.$rrdfile.'_ping]: 2000';
     $output .= $nl.'Options['.$rrdfile.'_ping]: growright,unknaszero,nopercent,gauge';
     $output .= $nl.'LegendI['.$rrdfile.'_ping]: Perduts %';
     $output .= $nl.'LegendO['.$rrdfile.'_ping]: Temps mig';
     $output .= $nl.'Legend1['.$rrdfile.'_ping]: Temps max. en ms';
     $output .= $nl.'Legend2['.$rrdfile.'_ping]: Temps min. en ms';
     $output .= $nl.'YLegend['.$rrdfile.'_ping]: RTT (ms)';

     return $nl.$output;
  }
  if (isset($_GET['ascii']))
    $nl = "\n";
  else
    $nl = "<BR>\n";

  $query = db_query("SELECT z.title FROM {guifi_zone} z WHERE z.id=%d",$zoneid);
  $zone = db_fetch_object($query);

  print "# MRTG configuration for zone: ".$zoneid." - ".$zone->title;
  print $nl."HtmlDir: ".variable_get('rrdimg_path','/home/comesfa/mrtg/images');
  print $nl."ImageDir: ".variable_get('rrdimg_path','/home/comesfa/mrtg/images');
  print $nl."LogDir: ".variable_get('rrddb_path','/home/comesfa/mrtg/logs');
  print $nl."LogFormat: rrdtool";
  print $nl."ThreshDir: ".variable_get('rrddb_path','/home/comesfa/mrtg/logs');
  print $nl."Forks: 24";
  print $nl."SnmpOptions: retries => 2, only_ip_address_matching => 0";
  print $nl."SnmpOptions: timeout => 1";
  
  $listed = array();
  $query = db_query("SELECT d.nick title, d.type, a.ipv4 ip, d.id, i.interface_type, d.extra FROM {guifi_location} l, {guifi_devices} d, {guifi_interfaces} i, {guifi_ipv4} a WHERE l.zone_id IN (".implode(',',guifi_get_zone_child_tree($zoneid)).") AND l.id = d.nid AND d.id=i.device_id AND i.id=a.interface_id AND i.interface_type IN ('Wan','wLan/Lan','Lan','Client','wlan','wlan1','wlan2','wlan3','wlan4','wlan5','wlan6','wds/p2p') AND a.ipv4 != '' GROUP BY 1,2,3");
  while ($row = db_fetch_array($query)) {

   // if not a radio in client mode and if is Wan, next
   if ($row['type'] == 'radio') {
     $radio = db_fetch_object(db_query("SELECT * FROM {guifi_radios} WHERE id=%d",$row['id']));
     if (($radio->mode!='client') and ($row['interface_type'] == 'Wan')) {
       continue;
     }
   }

   // if device already listed, next
   if ($listed[$row['id']]) {
     continue;
   } else {
     $listed[$row['id']] = true;
   }

   print $nl.'# '.$row['title'].' - '.$row['ip'];
   $rrdfile = guifi_rrdfile($row['title']);
   $query_linksys_buffalo = db_query("SELECT r.id FROM {guifi_radios} r, {guifi_model} m WHERE r.id=%d AND r.model_id=m.mid AND m.model in ('WRT54Gv1-4','WRT54GSv1-2','WRT54GSv4','WRT54GL','WHR-HP-G54, WHR-G54S')",$row['id']);
   if (db_num_rows($query_linksys_buffalo) > 0)  
      print wlan_traffic($rrdfile,6,10000000,t('wLan traffic'),$row,$nl);
   $query_routeros = db_query("SELECT r.id FROM {guifi_radios} r, {guifi_model} m WHERE r.id=%d AND r.model_id=m.mid AND m.model in ('Supertrasto RB532 guifi.net')",$row['id']);
   if (db_num_rows($query_routeros) > 0)   {
     $dev = guifi_get_device($row[id]);
     if (isset($dev[radios])) foreach ($dev[radios] as $radio_id=>$radio) {
       print wlan_routeros_traffic(guifi_rrdfile($radio[ssid]),$row,$radio_id+1,$nl);
     }
   }
   
   // ADSL
   if (($row['type'] == 'ADSL'))  {
     $adsl = unserialize($row['extra']);
     if (isset($adsl['mrtg_index']))
       print wlan_traffic($rrdfile,$adsl['mrtg_index'],$adsl['download'],t('ADSL traffic'),$row,$nl);
   }

   print ping($rrdfile,$row,$nl);
  }

}

/**
 * guifi_get_availability
**/

function guifi_get_pings($hostname, $start = NULL, $end = NULL) {
  $var = array();
  $var['max_latency'] = 0;
  $var['min_latency'] = NULL;
  $var['last'] = NULL;
  $var['avg_latency'] = 0;
  $var['succeed'] = 0;
  $var['samples'] = 0;

  if ($start == NULL)
    $start = time() - 60*60*24*7;
  if ($end == NULL)
    $end = time() - 300;
//  print 'Start/end: '.$start.' '.$end."\n<br />";
  $fn = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($hostname)."_ping.rrd";
//  print $fn."\n<br />";
  if (file_exists($fn)) {
    $cmd = sprintf("%s fetch %s AVERAGE --start=%d --end=%d",variable_get('rrdtool_path','/usr/bin/rrdtool'),$fn,$start,$end);
//    print $cmd."\n<br />";
    $fp = popen($cmd, "r");
    if (isset($fp)) {
      while (!feof($fp)) {
        $failed = 'nan';
        $n = sscanf(fgets($fp),"%d: %f %f",$interval,$failed,$latency);
        if (is_numeric($failed) && ($n == 3)) {
          $var['succeed'] += $failed;
          $last_suceed = $failed;
          if ($latency > 0) {
//            print $interval.' '.$failed.' '.$latency."\n<br />";
            $var['avg_latency'] += $latency;
            if ($var['max_latency'] < $latency)
              $var['max_latency']    = $latency;
            if (($var['min_latency'] > $latency) || ($var['min_latency'] == NULL))
              $var['min_latency']    = $latency;
          }
          $var['last'] = $interval;
          $var['samples']++;
        }
      }
    }
    pclose($fp);
  }
  if ($var['samples'] > 0) {
    $var['succeed'] = 100 - ($var['succeed'] / $var['samples']);
    $var['avg_latency'] = $var['avg_latency'] / $var['samples'];
    $var['last_sample'] = date('H:i',$var['last']);
    $var['last_succeed'] = 100 - $last_suceed;
  }
  return $var;
}

function guifi_get_traffic($hostname, $start = NULL, $end = NULL) {
  $var['in'] = 0;
  $var['out'] = 0;
  $var['max'] = 0;
  $data = array();
  $secs = NULL;
  
  if ($start == NULL)
    $start = -86400;
  if ($end == NULL)
    $end = -300;
  $fp = popen(variable_get('rrdtool_path','/usr/bin/rrdtool')." fetch ".variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($hostname).sprintf("_6.rrd AVERAGE --start=%d --end=%d",$start,$end), "r");
  if (isset($fp)) {
    while (!feof($fp)) {
      $n = sscanf(fgets($fp),"%d: %f %f",$interval,$in,$out);
      if (is_numeric($in) && ($n == 3)) {
        if ($var['max'] < $in)
          $var['max'] = $in;
        if ($var['max'] < $out)
          $var['max'] = $out;
        $data[] = array('interval' => $interval, 'in' => $in, 'out' => $out);
      }
    }
    foreach ($data as $key => $sample) {
      if ($key == 0)
        $secs = $data[1]['interval'] - $sample['interval'];
      else
        $secs = $sample['interval'] - $data[$key - 1]['interval'];
      $var['in'] += $sample['in'] * $secs;
      $var['out'] += $sample['out'] * $secs;
    }
  }
  pclose($fp);
  return $var;
}

function guifi_graph_node($node, $start = NULL, $end = NULL) {

  $query_radios = db_query("SELECT d.id, d.nick FROM {guifi_devices} d WHERE d.nid=%d",$node);
  while ($radio = db_fetch_object($query_radios)) {
    print $radio->id."-".$radio->nick."<br />\n";
    print_r(guifi_get_traffic($radio->nick,null,null));
  }
  return db_num_rows($query_radios);
}

function guifi_graph() {
  $type    = $_GET['type'];
  if (isset($_GET['start']))
    $start   = $_GET['start'];
  else
    $start = -86400;
  if (isset($_GET['end']))
    $end     = $_GET['end'];
  else
    $end = -300;
  if (isset($_GET['width']))
    $width   = $_GET['width'];
  else
    $width = 600;
  if (isset($_GET['height']))
    $height  = $_GET['height'];
  else
    $height = 120;
  $radios = array();
  $totals = array();
  $key = 0;


  if ($start == 0) 
    $start = -86400;
  if ($end == 0) 
    $start = -300;

  $color = array(
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0'
                );
  $cmd = '';

  if (isset($_GET['radio'])) {
    $device = guifi_get_device($_GET['radio']);
    $queryradio = db_query("SELECT d.nick FROM {guifi_devices} d WHERE d.id=%d",$_GET['radio']);
    $radio = db_fetch_object($queryradio);
  }
  if (isset($_GET['direction'])) {
    $direction = strtolower($_GET['direction']);
  } else {
    $direction='in';
  }
  switch ($direction) {
    case 'in':  $ds = 'ds0'; $otherdir = 'out'; $otherds = 'ds1'; break;
    case 'out': $ds = 'ds1'; $otherdir = 'in';  $otherds = 'ds0'; break;
  }


  switch ($type) {
    case 'supernode': 
      if (isset($_GET['node']))
        $node = $_GET['node'];
      else return; 
      $querynode = db_query("SELECT n.title,l.nick FROM {node} n, {guifi_location} l WHERE n.nid=%d AND l.id=n.nid",$node, $node);
      $nodestr = db_fetch_object($querynode);
      $title = sprintf('Supernode: %s - wLANs %s',$nodestr->nick,$direction);
      $vscale = 'Bytes/s';
    case 'clients':
      $queryradios = db_query("SELECT d.nick,r.id FROM {guifi_radios} r, {guifi_devices} d WHERE r.nid=%d AND r.id=d.id",$node, $node);
      if ($type == 'clients') {
        $traffic = guifi_get_traffic($radio->nick,$start,$end);
        $totals[] = $traffic[$otherdir] * 8;
        $radios[] = array('nick' => $radio->nick, 'id' => $radio->id, 'filename' => variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($radio->nick).'_6.rrd', 'max' => $traffic['max'] * 8);
        $title = sprintf('wLAN: %s (%s) - links (%s)',$radio->nick,$otherdir,$direction);
        $vscale = 'Bytes/s'; 
       $queryradios = db_query("SELECT d.nick,r.id FROM {guifi_links} c1, {guifi_links} c2, {guifi_radios} r, {guifi_devices} d WHERE c1.device_id=%d AND c1.link_type in ('wds','ap/client','bridge') AND c2.device_id != %d AND c1.id = c2.id AND c2.device_id = r.id AND r.id=d.id",$_GET['radio'], $_GET['radio']);
      }

      while ($radiofetch = db_fetch_array($queryradios)) {
        $filename = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($radiofetch['nick']).'_6.rrd';
        if (file_exists($filename)) {
          $traffic = guifi_get_traffic($radiofetch['nick'],$start,$end);
          $totals[] = $traffic[$direction] * 8;
          $radiofetch['filename'] = $filename;
          $radiofetch['max'] =  $traffic['max'] * 8;
          $radios[] = $radiofetch;
          $key ++;
        }
      }
       
      arsort($totals);
      reset($totals);
      $col = 0;
      foreach ($totals as $key => $total) {
        $item = $radios[$key];
        $totalstr = _guifi_tostrunits($total);
        if (($type == 'clients') && ($item['id'] == $radio->id)) {
          $dir_str = $otherdir;
          $datasource = $otherds;
        } else {
          $datasource = $ds;
          $dir_str = $direction;
        }
        $cmd = $cmd.sprintf(' DEF:val%d="%s":%s:AVERAGE',$key,$item['filename'],$datasource);
        $cmd = $cmd.sprintf(' CDEF:val%da=val%d,1,* ',$key,$key);
        $cmd = $cmd.sprintf(' LINE1:val%da%s:"%30s %3s"',$key,$color[$col],$item['nick'],$dir_str);
        $cmd = $cmd.sprintf(' GPRINT:val%da:LAST:"Ara\:%%8.2lf %%s"',$key);
        $cmd = $cmd.sprintf(' GPRINT:val%da:AVERAGE:"Mig\:%%8.2lf %%s"',$key);
        $cmd = $cmd.sprintf(' GPRINT:val%da:MAX:"Max\:%%8.2lf %%s"',$key);
        $cmd = $cmd.sprintf(' COMMENT:"Total\: %s\n"',$totalstr);
        $col++;
        if (($type == 'clients') && ($col > 5)) break; 
      }
      break;
    case 'radio': 
      $cmd = $cmd.guifi_graph_device($device,$type,$start,$end, $width, $height, $direction);
      break;
    case 'pings': 
      $pings = guifi_get_pings($radio->nick,$start,$end);
       $vscale = 'Milisegons';
      $title = sprintf('device: %s -  pings &#038; disponibilitat (%.2f %%)',$radio->nick,$pings['succeed']);
      $filename = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($radio->nick).'_ping.rrd';
      $cmd = $cmd.sprintf(' DEF:val0="%s":ds0:AVERAGE',$filename);
      $cmd = $cmd.sprintf(' AREA:val0#FFFF00:"%20s pings fallats "',$radio->nick);
      $cmd = $cmd.        ' GPRINT:val0:LAST:"Ara\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val0:AVERAGE:"Mig\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val0:MAX:"Max\:%8.2lf %s\n"';
      $cmd = $cmd.sprintf(' DEF:val1="%s":ds1:AVERAGE',$filename);
      $cmd = $cmd.sprintf(' LINE2:val1#00FF00:"%20s temps del ping"',$radio->nick);
      $cmd = $cmd.        ' GPRINT:val1:LAST:"Ara\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val1:AVERAGE:"Mig\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val1:MAX:"Max\:%8.2lf %s\n"';
      break;
  }

  if ($type != 'radio')
    $cmd = sprintf("%s graph - --font DEFAULT:7 --title=\"%s\" --imgformat=PNG --width=%d  --height=%d  --vertical-label=\"%s\"  --start=%d  --end=%d --base=1000 -E %s ",
          variable_get(rrd_path,'/usr/bin/rrdtool'),$title,$width,$height,$vscale,$start,$end,$cmd);
//  print $cmd."\n<br />"; return;
  $fp = popen($cmd, "rb");
  if (isset($fp))
    header("Content-Type: image/png");
    print fpassthru($fp);
  pclose($fp);
}

function guifi_graph_device($device, $type='radio',$start=-86400, $end=-300, $width=600, $height=120, $direction='in') {

  if ($start == 0) 
    $start = -86400;
  if ($end == 0) 
    $start = -300;

  $color = array(
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0',
         '#0000FF','#FF0000','#FFCC00','#66CCFF','#000000','#00CC00','#990000','#FFFF00','#800000','#C0FFC0','#FFDCA8','#008000','#A0A0A0'
                );
  $cmd = '';

  switch ($direction) {
    case 'in':  $ds = 'ds0'; $otherdir = 'out'; $otherds = 'ds1'; break;
    case 'out': $ds = 'ds1'; $otherdir = 'in';  $otherds = 'ds0'; break;
  }
  if ($device['type'] == 'ADSL')
    $mrtg_index='_'.$device['variable']['mrtg_index'].'.rrd';
  else
    $mrtg_index='_6.rrd';
  $vscale = 'Bytes/s';
  $traffic = guifi_get_traffic($device['nick'],$start,$end);
  $title = sprintf('radio: %s - wLAN In & Out',$device['nick']);
  $filename = variable_get('rrddb_path','/home/comesfa/mrtg/logs/').guifi_rrdfile($device['nick']).$mrtg_index;
  $cmd = $cmd.sprintf(' DEF:val0="%s":ds0:AVERAGE',$filename);
  $cmd = $cmd.        ' CDEF:val0a=val0,1,* ';
  $cmd = $cmd.sprintf(' AREA:val0a#0000FF:"%30s In "',$device['nick']);
  $cmd = $cmd.        ' GPRINT:val0a:LAST:"Ara\:%8.2lf %s"';
  $cmd = $cmd.        ' GPRINT:val0a:AVERAGE:"Mig\:%8.2lf %s"';
  $cmd = $cmd.        ' GPRINT:val0a:MAX:"Max\:%8.2lf %s"';
  $cmd = $cmd.sprintf(' COMMENT:"Total\: %s\n"',_guifi_tostrunits($traffic['in']));
  $cmd = $cmd.sprintf(' DEF:val1="%s":ds1:AVERAGE',$filename);
  $cmd = $cmd.        ' CDEF:val1a=val1,1,* ';
  $cmd = $cmd.sprintf(' LINE2:val1a#00FF00:"%30s Out"',$device['nick']);
  $cmd = $cmd.        ' GPRINT:val1a:LAST:"Ara\:%8.2lf %s"';
  $cmd = $cmd.        ' GPRINT:val1a:AVERAGE:"Mig\:%8.2lf %s"';
  $cmd = $cmd.        ' GPRINT:val1a:MAX:"Max\:%8.2lf %s"';
  $cmd = $cmd.sprintf(' COMMENT:"Total\: %s\n"',_guifi_tostrunits($traffic['out']));

  $cmd = sprintf("%s graph - --font DEFAULT:7 --title=\"%s\" --imgformat=PNG --width=%d  --height=%d  --vertical-label=\"%s\"  --start=%d  --end=%d --base=1000 -E %s ",
           variable_get(rrd_path,'/usr/bin/rrdtool'),$title,$width,$height,$vscale,$start,$end,$cmd);
//  print $cmd."\n<br />"; return;
  return $cmd;
}

function _guifi_script_calendar() {
  return "<script type='text/javascript'>
  // Initialize the calendar
  calendar=null;

  // This function displays the calendar associated to the input field 'id'
  function showCalendar(id) {
    var el = document.getElementById(id);
    if (calendar != null) {
      // we already have some calendar created
      calendar.hide();  // so we hide it first.
    } else {
      // first-time call, create the calendar.
      var cal = new Calendar(true, null, selected, closeHandler);
      cal.weekNumbers = false;  // Do not display the week number
      cal.showsTime = true;     // Display the time
      cal.time24 = true;        // Hours have a 24 hours format
      cal.showsOtherMonths = false;    // Just the current month is displayed
      calendar = cal;                  // remember it in the global var
      cal.setRange(1900, 2070);        // min/max year allowed.
      cal.create();
    }

    calendar.setDateFormat('%d-%m-%Y %H:%M');    // set the specified date format
    calendar.parseDate(el.value);                // try to parse the text in field
    calendar.sel = el;                           // inform it what input field we use

    // Display the calendar below the input field
    calendar.showAtElement(el, \"Br\");        // show the calendar

    return false;
  }

  // This function update the date in the input field when selected
  function selected(cal, date) {
    cal.sel.value = date;      // just update the date in the input field.
  }

  // This function gets called when the end-user clicks on the 'Close' button.
  // It just hides the calendar without destroying it.
  function closeHandler(cal) {
    cal.hide();                        // hide the calendar
    calendar = null;
  }
        </script>";
}

?>
