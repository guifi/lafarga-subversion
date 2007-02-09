<?php

/* **
 *  * _guifi_tostrunits convert a number to string format in B,KB,MB...
 * **/
function _guifi_tostrunits($num) {
      $base = array('B','KB','MB','GB','TB','PB');
      $str = sprintf("%3d B",$num);
      foreach ($base as $key => $unit) {
	      if ($num > pow(1024,$key))
	          $str = sprintf("%7.2f %s",$num/pow(1024,$key),$unit);
	      else
	          return $str;
      }
}

  
/**
 * guifi_get_availability
**/

  
function guifi_get_pings($did, $start = NULL, $end = NULL) {
   
  global $rrdtool_path;
  global $rrddb_path;

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
  $fp = popen(sprintf("%s fetch %s/%d_ping.rrd AVERAGE --start=%d --end=%d",$rrdtool_path,$rrddb_path,$did,$start,$end), "r");
  if (isset($fp)) {
    while (!feof($fp)) {
      $failed = 'nan';
      $n = sscanf(fgets($fp),"%d: %f %f",$interval,$failed,$latency);
      if (is_numeric($failed) && ($n == 3)) {
        $var['succeed'] += $failed;
        $last_suceed = $failed;
        if ($latency > 0) {
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
  if ($var['samples'] > 0) {
    $var['succeed'] = 100 - ($var['succeed'] / $var['samples']);
    $var['avg_latency'] = $var['avg_latency'] / $var['samples'];
    $var['last_sample'] = date('H:i',$var['last']);
    $var['last_succeed'] = 100 - $last_suceed;
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
    $fp = popen(sprintf("%s fetch %s AVERAGE --start=%d --end=%d",$rrdtool_path,$filename,$start,$end), "r");
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
