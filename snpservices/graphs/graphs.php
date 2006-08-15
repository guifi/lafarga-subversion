<?php

include_once("config.php");

/**
 * guifi_get_availability
**/


function guifi_rrdfile($nick) {
       return str_replace(array (' ','.','-','?','&','%','$'),"",strtolower($nick));
}

/**
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

  
  
function guifi_get_pings($hostname, $start = NULL, $end = NULL) {
   
  global $rrddb_path;
  global $rrdtool_path;

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
  $fp = popen($rrdtool_path." fetch ".$rrddb_path.guifi_rrdfile($hostname).sprintf(".rrd AVERAGE --start=%d --end=%d",$start,$end), "r");
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

function guifi_get_traffic($hostname, $start = NULL, $end = NULL) {
  global $rrddb_path;
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
    $fp = popen($rrdtool_path." fetch ".$rrddb_path.$hostname.sprintf(".rrd AVERAGE --start=%d --end=%d",$start,$end), "r");
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
  if (isset($_GET['thumb']))
   {
     $thumb = "-j";  
   }
    else
    $thumb = "";



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
 
  if (isset($_GET['radio'])) 
     {
      //----------  XML Start Xpath Query-----------------------------------
      $radio_xml=$xml->xpath('//device[@id='.$_GET['radio'].']');
      $radio_attr=$radio_xml[0]->attributes();
      //----------  XML End Xpath Query -----------------------------------      
     }
  if (isset($_GET['direction']))
    {
	$direction = strtolower($_GET['direction']);
    }
  else 
    {
	$direction='in';
    }
  switch ($direction)
    {
    case 'in':  $ds = 'ds0'; $otherdir = 'out'; $otherds = 'ds1'; break;
    case 'out': $ds = 'ds1'; $otherdir = 'in';  $otherds = 'ds0'; break;
    }


  switch ($type)
   {
    case 'supernode': 
      if (isset($_GET['node']))
        $node = $_GET['node'];
      else return;      
      //----------  XML Start Xpath Query-----------------------------------
      $nodestr=array('nick' => '', 'title' => '');
      $nodestr['title']=$xml->xpath('//node[@id='.$node.']/@title');
      $nodestr['nick']=$xml->xpath('//node[@id='.$node.']/@title');
      //----------  XML End Xpath Query -----------------------------------      
      $title = sprintf('Supernode: %s - wLANs %s',$nodestr['nick'][0],$direction);
      $vscale = 'Bytes/s';

    case 'clients':
      if ($type == 'clients')
      {
        $radios_dev = $radio_xml[0]->xpath('radio');
        $rrdfiles = $radio_xml[0]->xpath('radio/@rrd_traffic');
        $traffic = array('in'=> 0,'out' => 0, 'max' => 0);
        foreach ($radios_dev as $radio_dev) {
          $radio_dev_attr = $radio_dev->attributes();
//          print $rrdfile ."\n<br>";

          $traffic_radio = guifi_get_traffic($radio_dev_attr['rrd_traffic'],$start,$end);
          $traffic['in'] =$traffic['in']  + $traffic_radio['in'];
          $traffic['out']=$traffic['out'] + $traffic_radio['out'];
          if ($traffic_radio['max'] > $traffic['max'])
            $traffic['max']=$traffic_radio['max'];
          $radios[] = array('nick' => $radio_dev_attr['ssid'], 'change_direction' => true, 'filename' => $rrddb_path.$radio_dev_attr['rrd_traffic'].'.rrd', 'max' => $traffic['max'] * 8);
        }
        $totals[] = $traffic[$otherdir] * 8;
//        $radios[] = array('nick' => $radio_attr['title'], 'change_direction' => true, 'filename' => $rrddb_path.'.rrd', 'max' => $traffic['max'] * 8);
	$title = sprintf('wLAN: %s (%s) - links (%s)',$radio_attr['title'],$otherdir,$direction);
        $vscale = 'Bytes/s'; 
      }

      $result = array();
      //----------  XML Start Xpath Query-----------------------------------
      if ($type == 'supernode') {
         $result=$xml->xpath('//node[@id='.$_GET['node'].']/device/radio');      
      } else {
//         print "Type: ".$type.' '.$_GET['radio']."\n<br>";
         $row = simplexml_load_string($radio_xml[0]->asXML());
         $linked_radios=$row->xpath('//radio/interface/link');
         foreach ($linked_radios as $linked_radio) {
           $linked_radio_attr=$linked_radio->attributes();
           $result_client = $xml->xpath('//node[@id='.$linked_radio_attr['linked_node_id'].']/device[@id='.$linked_radio_attr['linked_device_id'].']/radio');
           $result = array_merge($result,$result_client);
         }
//         print_r($result);
      }
      //----------  XML End Xpath Query -----------------------------------      

      if (!empty($result))
      foreach ($result as $radiodev)
      {
          $radio_attr = $radiodev->attributes();
	  $radiofetch['nick'] = $radio_attr['ssid'];
	  $radiofetch['rrdtraf'] = $radio_attr['rrd_traffic'];
	  $filename = $rrddb_path.guifi_rrdfile($radiofetch['rrdtraf']).'.rrd';
          if (file_exists($filename))
	  {
	      $traffic = guifi_get_traffic($radiofetch['rrdtraf'],$start,$end);
	      $totals[] = $traffic[$direction] * 8;
	      $radiofetch['change_direction'] = false;
	      $radiofetch['filename'] = $filename;
	      $radiofetch['max'] =  $traffic['max'] * 8;
	      $radios[] = $radiofetch;
	      $key ++;
	      
          }	  
      }
//      print_r($radios);
      arsort($totals);
      reset($totals);
      $col = 0;

      if (isset($_GET['numcli']))
       {	 
	if ($_GET['numcli']=='max')
	   {   
	   $numcli=count($totals);
	   }
       	else
	   {    
	   $numcli=$_GET['numcli']; 
	   }
       }
      else
       { 
        $numcli = 10;
       }
     
       
      foreach ($totals as $key => $total)
       {
        $item = $radios[$key];
        $totalstr = _guifi_tostrunits($total);	  
	if (($type == 'clients') && ($item['change_direction']))
	   {
	       $dir_str = $otherdir;
	       $datasource = $otherds;
           }
       else
	   {
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
        if (($type == 'clients') && ($col > $numcli)) break; 
       }
      break;
    case 'radio': 
      $vscale = 'Bytes/s';
      $row = simplexml_load_string($radio_xml[0]->asXML());
      $w = $row->xpath('//radio');
      $w_attr = $w[0];
      $traffic = guifi_get_traffic($w_attr['rrd_traffic'],$start,$end);
      $title = sprintf('radio: %s - wLAN In & Out',$radio_attr['title']);
      $filename = $rrddb_path.guifi_rrdfile($w_attr['rrd_traffic']).'.rrd';
      $cmd = $cmd.sprintf(' DEF:val0="%s":ds0:AVERAGE',$filename);
      $cmd = $cmd.        ' CDEF:val0a=val0,1,* ';
      $cmd = $cmd.sprintf(' AREA:val0a#0000FF:"%30s In "',$radio_attr['title']);
      $cmd = $cmd.        ' GPRINT:val0a:LAST:"Ara\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val0a:AVERAGE:"Mig\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val0a:MAX:"Max\:%8.2lf %s"';
      $cmd = $cmd.sprintf(' COMMENT:"Total\: %s\n"',_guifi_tostrunits($traffic['in']));
      $cmd = $cmd.sprintf(' DEF:val1="%s":ds1:AVERAGE',$filename);
      $cmd = $cmd.        ' CDEF:val1a=val1,1,* ';
      $cmd = $cmd.sprintf(' LINE2:val1a#00FF00:"%30s Out"',$radio_attr['title']);
      $cmd = $cmd.        ' GPRINT:val1a:LAST:"Ara\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val1a:AVERAGE:"Mig\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val1a:MAX:"Max\:%8.2lf %s"';
      $cmd = $cmd.sprintf(' COMMENT:"Total\: %s\n"',_guifi_tostrunits($traffic['out']));
      break;
    case 'pings': 
      $pings = guifi_get_pings($radio_attr['rrd_ping'],$start,$end);
      $vscale = 'Milisegons';
      $title = sprintf('device: %s -  pings & disponibilitat (%.2f %%)',$radio_attr['title'],$pings['succeed']);
      $filename = $rrddb_path.guifi_rrdfile($radio_attr['rrd_ping']).'.rrd';
      $cmd = $cmd.sprintf(' DEF:val0="%s":ds0:AVERAGE',$filename);
      $cmd = $cmd.sprintf(' AREA:val0#FFFF00:"%20s pings fallats "',$radio_attr['title']);
      $cmd = $cmd.        ' GPRINT:val0:LAST:"Ara\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val0:AVERAGE:"Mig\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val0:MAX:"Max\:%8.2lf %s\n"';
      $cmd = $cmd.sprintf(' DEF:val1="%s":ds1:AVERAGE',$filename);
      $cmd = $cmd.sprintf(' LINE2:val1#00FF00:"%20s temps del ping"',$radio_attr['title']);
      $cmd = $cmd.        ' GPRINT:val1:LAST:"Ara\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val1:AVERAGE:"Mig\:%8.2lf %s"';
      $cmd = $cmd.        ' GPRINT:val1:MAX:"Max\:%8.2lf %s\n"';
      break;
   } //end switch $type:

  $cmd = sprintf("%s graph - --font DEFAULT:7 --title=\"%s\" --imgformat=PNG --width=%d  --height=%d %s --vertical-label=\"%s\"  --start=%d  --end=%d --base=1000 -E %s ",
          $rrdtool_path,$title,$width,$height,$thumb,$vscale,$start,$end,$cmd);

if (isset($_GET['debug'])) {
  echo $cmd;
}

$fp = popen($cmd, "rb");
if (isset($fp)) {
  if (!isset($_GET['debug']))  {
    header("Content-Type: image/png");
    print fpassthru($fp);
  }
}
pclose($fp);

?>
