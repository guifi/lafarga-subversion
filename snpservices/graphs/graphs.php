<?php

if (file_exists('../common/config.php'))
  include_once("../common/config.php");
else
  include_once("../common/config.php.template");

include_once("../common/misc.php");

  $type    = $_GET['type'];

  $format='long';
  if (isset($_GET['format']))
    $format=$_GET['format'];
  
  if ($type == 'availability') {  
    // Just creating the availability PNG, not a graph
    $pings = guifi_get_pings($_GET['device']);
  //  print_r($pings);
    if ($pings['samples'] > 0) {
      $available = sprintf("%.2f%%",$pings['succeed']);
      if ($pings['last_succeed'] == 0)
        $last = 'Down';
      else
        $last = 'Up';
    } else {
       $last = 'number';
    }
    $var['available'] = $available;
    $var['last'] = $last;
  
    // create a image
    if ($format=='short')
      $pixlen=77;
    else
      $pixlen=117;
    $im = imagecreate($pixlen, 15);

    // white background and blue text
    //$bg = imagecolorallocate($im,0x33, 0xff, 0);

    if ($last == "Up")
      $bg = imagecolorallocate($im,0x33, 0xff, 0);
    else if ($last == "Down")
      $bg = imagecolorallocate($im,0xff, 0x33, 0);
    else
      return;

    $textcolor = imagecolorallocate($im, 0, 0, 100);

    // write the string at the top left
    if ($format=="short")
      imagestring($im, 2, 3, 1, sprintf("%s (%s)",$last,$var['available']), $textcolor);
    else 
      imagestring($im, 2, 3, 1, sprintf("%s %s (%s)",$last,$pings['last_sample'],$var['available']), $textcolor);

    // output the image
    header("Content-type: image/png");
    imagepng($im);
    exit;
  }

  // going to create a graph

 
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

//  print_r($_GET);

  if (isset($_GET['node'])) {
    $gxml = simplexml_node_file($_GET['node']);
//    print $gxml->asXML();
  }
 
  if (isset($_GET['radio'])) 
     {
      //----------  XML Start Xpath Query-----------------------------------
      $radio_xml=$gxml->xpath('//device[@id='.$_GET['radio'].']');
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
      $nodestr['title']=$gxml->xpath('//node[@id='.$node.']/@title');
      $nodestr['nick']=$gxml->xpath('//node[@id='.$node.']/@title');
      //----------  XML End Xpath Query -----------------------------------      
      $title = sprintf('Supernode: %s - wLANs %s',$nodestr['nick'][0],$direction);
      $vscale = 'Bytes/s';

    case 'clients':
      if ($type == 'clients')
      {
        $radios_dev = $radio_xml[0]->xpath('radio');
        $traffic = array('in'=> 0,'out' => 0, 'max' => 0);
        foreach ($radios_dev as $radio_dev) {
          $radio_dev_attr = $radio_dev->attributes();
//          print_r($radio_dev_attr);
//          print "\n<br>";

          $filename = guifi_get_traf_filename($radio_dev_attr['device_id'],$radio_dev_attr['snmp_index'],$radio_dev_attr['snmp_name'],$radio_dev_attr['id']);

          $traffic_radio = guifi_get_traffic($filename,$start,$end);
          $traffic['in'] =$traffic['in']  + $traffic_radio['in'];
          $traffic['out']=$traffic['out'] + $traffic_radio['out'];
          if ($traffic_radio['max'] > $traffic['max'])
            $traffic['max']=$traffic_radio['max'];
          $radios[] = array('title' => $radio_dev_attr['ssid'], 'change_direction' => true, 'filename' => $filename, 'max' => $traffic['max'] * 8);
        }
        $totals[] = $traffic[$otherdir] * 8;
//        $radios[] = array('nick' => $radio_attr['title'], 'change_direction' => true, 'filename' => $rrddb_path.'.rrd', 'max' => $traffic['max'] * 8);
	$title = sprintf('wLAN: %s (%s) - links (%s)',$radio_attr['title'],$otherdir,$direction);
        $vscale = 'Bytes/s'; 
      }

      $result = array();
      //----------  XML Start Xpath Query-----------------------------------
      if ($type == 'supernode') {
         $result=$gxml->xpath('//node/device/radio');      
      } else {
//         print "Type: ".$type.' '.$_GET['radio']."\n<br>";
         $row = simplexml_load_string($radio_xml[0]->asXML());
         $linked_radios=$row->xpath('//radio/interface/link');
         $remote_clients = array();
         foreach ($linked_radios as $linked_radio) {
           $linked_radio_attr=$linked_radio->attributes();
           $remote_clients[] = (int)$linked_radio_attr['linked_node_id']; 
         }
         $rxml = simplexml_node_file(implode(',',$remote_clients));
         reset($linked_radios);
         foreach ($linked_radios as $linked_radio) {
           $linked_radio_attr=$linked_radio->attributes();
           $result_client = $rxml->xpath('//device[@id='.$linked_radio_attr['linked_device_id'].']/radio');
           $result = array_merge($result,$result_client);
         }
//         print_r($result);
      }
      //----------  XML End Xpath Query -----------------------------------      

      if (!empty($result))
      foreach ($result as $radiodev)
      {
          $radio_attr = $radiodev->attributes();
//          print_r($radio_attr);
	  $radiofetch['title'] = $radio_attr['ssid'];

          $filename = guifi_get_traf_filename($radio_attr['device_id'],$radio_attr['snmp_index'],$radio_attr['snmp_name'],$radio_attr['id']);

          if (file_exists($filename))
	  {
	      $traffic = guifi_get_traffic($filename,$start,$end);
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
        $cmd = $cmd.sprintf(' LINE1:val%da%s:"%30s %3s"',$key,$color[$col],$item['title'],$dir_str);
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
      $title = sprintf('radio: %s - wLAN In & Out',$radio_attr['title']);
      $filename = guifi_get_traf_filename($w_attr['device_id'],$w_attr['snmp_index'],$w_attr['snmp_name'],$w_attr['id']);

      $traffic = guifi_get_traffic($filename,$start,$end);


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
      $pings = guifi_get_pings($radio_attr['id'],$start,$end);
      $vscale = 'Milisegons';
      $title = sprintf('device: %s -  pings & disponibilitat (%.2f %%)',$radio_attr['title'],$pings['succeed']);
      $filename = $rrddb_path.$radio_attr['id'].'_ping.rrd';
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
