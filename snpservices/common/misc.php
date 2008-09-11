<?php

/* **
 *  * _guifi_tostrunits convert a number to string format in B,KB,MB...
 * **/
function _guifi_tostrunits($num) {
      $base = array(
        0=>'b',              // 
        1024=>'Kb',          // Kilobits
        pow(1024,2)=>'Mb',   // Megabits
        pow(1024,3)=>'Gb',   // Gigabits
        pow(1024,4)=>'Tb',   // Terabits
        pow(1024,5)=>'Pb');  // Pedabits
//      $str = sprintf("%3d B",$num);
      foreach ($base as $key => $unit) {
        if ($num > $key)
          if ($num < ($key + ($key*1024)))
            return sprintf("%s %s",number_format(($num/$key),2),$unit);  	
      }
      return sprintf("%s %s",number_format(($num/$key),2),$unit);
}

  
/**
 * guifi_get_availability
**/

  
function guifi_get_pings($did, $start = NULL, $end = NULL) {
   
  global $rrdtool_path;
  global $rrddb_path;
  
  $now = time();
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
    $end = time();
    
  $opts = array(
    'AVERAGE',
    '--start',$start,
    '--end',$end
    );
  $fname = sprintf("%s/%d_ping.rrd",$rrddb_path,$did);
  $result = rrd_fetch($fname,$opts,count($opts));
  $result['data'] = array_chunk($result['data'],$result['ds_cnt']);
  foreach ($result['data'] as $k=>$data) 
    $fetched_data[$result['start'] + ($k * $result['step'])] = $data;  	
  ksort($fetched_data);
  $var['last_online'] = 0;
  
  foreach ($fetched_data as $interval=>$data) {
  	if ($interval > $now)
  	  break;
  	  
  	list($failed,$latency) = $data;
  	
  	if (strtoupper($failed)=='NAN')
  	  continue;
  	  
    $var['succeed'] += $failed;
    $last_succeed = $failed;
    if ($failed < 100) {
      $var['last_online'] = $interval;
      $var['avg_latency'] += $latency;
      if ($var['max_latency'] < $latency)
        $var['max_latency']    = $latency;
      if (($var['min_latency'] > $latency) || ($var['min_latency'] == NULL))
        $var['min_latency']    = $latency;
    }
    $var['last'] = $interval;
    $var['samples']++;  	
  }

  if ($var['samples'] > 0) {
    $var['succeed'] = 100 - ($var['succeed'] / $var['samples']);
    $var['avg_latency'] = $var['avg_latency'] / $var['samples'];
    $var['last_sample'] = date('H:i',$var['last']);
    $var['last_online'] = date('Ymd',$var['last_online']);
    $var['last_sample_date'] = date('Ymd',$var['last']);
    $var['last_succeed'] = 100 - $last_succeed;
  }
  return $var;
}

function guifi_get_traffic($filename, $start = NULL, $end = NULL) {
  global $rrdtool_path;
  $var['in'] = 0;
  $var['out'] = 0;
  $var['max'] = 0;
  $data = array();
  $secs = NULL;
  
  if ($start == NULL)
    $start = -86400;
  if ($end == NULL)
    $end = -300;

  $opts = array(
    'AVERAGE',
    '--start',$start,
    '--end',$end
    );
  $result = rrd_fetch($filename,$opts,count($opts));
  $result['data'] = array_chunk($result['data'],$result['ds_cnt']);
  foreach ($result['data'] as $k=>$data) 
    $fetched_data[$result['start'] + ($k * $result['step'])] = $data;  	
  ksort($fetched_data);
  
  foreach ($fetched_data as $interval=>$data) {
  	list($in,$out) = $data;
  	if (strtoupper($in)=='NAN')
  	  continue;
    if ($var['max'] < $in)
      $var['max'] = $in;
    if ($var['max'] < $out)
      $var['max'] = $out;
    $var['in'] += $result['step'] * $in;
    $var['out'] += $result['step'] * $out;
  }
  return $var;
}

function simplexml_node_file($n) {
  global $CNMLSource;

   $fn = '../tmp/'.$n.'.cnml';
   if (file_exists($fn))
   if (time () < (filectime($fn) + (60 * 60))) {
     $xml = simplexml_load_file($fn);
     if ($xml)
       return $xml;
   }
  // new file, loading into a variable
  $cnmlS = sprintf($CNMLSource,$n);
  $xml = simplexml_load_file($cnmlS);
  if ($xml) {
    $wcnml = @fopen($fn, "w+") or die("Error caching XML, can't write $fn\n");
    fwrite($wcnml,$xml->asXML());
    fclose($wcnml);
    return $xml;
  }
  return FALSE;
}

function guifi_get_traf_filename($did, $snmp_index, $snmp_name, $rid) {
  global $rrddb_path;

  if (isset($snmp_index))
    $rrdtraf = (string)$did."-".(string)$snmp_index;
  else if (isset($snmp_name))
    $rrdtraf = (string)$did."-".(string)$rid;
  else 
    return NULL;

  return  $rrddb_path.$rrdtraf.'_traf.rrd';
}


?>
