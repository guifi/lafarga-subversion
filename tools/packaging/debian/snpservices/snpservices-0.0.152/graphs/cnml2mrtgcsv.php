<?php

function snmplist($did,$dev) {
  if (!isset($dev[$did])) 
    return;
  $att = $dev[$did]->attributes();

  $ipv4 = null;
  $snmp = array();

  $rnum = 0;

  foreach ($dev[$did] as $key=>$value) {
    $tatt = $value->attributes();
    // any other server
    if ($att->type!='radio') {
      if ($key == 'interface') {
        if ($tatt->type=='Lan' or $tatt->type=='wLan/Lan')
          $ipv4=$tatt->ipv4;
      }
      if (isset($att->snmp_index))
        $snmp[(int)$att->id] = (int)$att->snmp_index;
      continue;
    }

    // is a radio, now let's take the interface
    switch ($key) {
    case 'radio':
      if ($tatt->mode=='client') {
        $itype="Wan";
      } else {
        $itype="wLan/Lan";
      }

//      switch ($att->name) {
//        case 'WRT54Gv1-4':
//        case 'WHR-HP-G54, WHR-G54S':
//        case 'WRT54GL':
//        case 'WRT54GSv1-2':
//        case 'WRT54GSv4':
//          $snmp[] = 6;
//          break;
//        case 'Supertrasto RB532 guifi.net':
//          $rnum++;
//          $snmp[] = 'wlan'.$rnum.';'.$tatt->ssid;
//          $snmp[] = 'wlan'.((int)$tatt->id + 1).';'.$tatt->ssid;
//          break;
//        default: 
//          $snmp[] = 'wlam;'.$tatt->ssid;
//      } // model name switch

      if (isset($tatt->snmp_index)) {
        $snmp[(int)$tatt->id] = (int)$tatt->snmp_index;
      } else if (isset($tatt->snmp_name))
        $snmp[(int)$tatt->id] = 'wlan'.((int)$tatt->id + 1).';'.$tatt->ssid;

      break;
    case 'interface':
      if (!empty($itype) and $tatt->type==$itype)
        $ipv4=$tatt->ipv4;
      break;
    }
  }

  if ($ipv4) {
    if ((isset($_GET['cp'])) or (isset($_GET['list']))) {
      $devfn = str_replace(array (' ','.','-','?','&','%','$'),"",strtolower($att->title));
      if (isset($_GET['cp']))
        print "cp ".$devfn."_ping.rrd ".$did."_ping.rrd\n";
      if (isset($_GET['list']))
        print $did."_ping.rrd\n";
      foreach ($snmp as $k=>$idx) {
        if (is_numeric($idx)) {
          if (isset($_GET['cp']))
            print "cp ".$devfn."_".$idx.".rrd ".$did."-".$idx."_traf.rrd\n";
          if (isset($_GET['list']))
            print $did."-".$idx."_traf.rrd\n";
        }
        else {
          $wlan = explode(';',$idx);
          $sidfn = str_replace(array (' ','.','-','?','&','%','$'),"",strtolower($wlan[1]));
          if (isset($_GET['cp']))
            print "cp ".$sidfn.".rrd ".$did."-".$k."_traf.rrd\n";
          if (isset($_GET['list']))
            print $did."-".$k."_traf.rrd\n";
        }
      }
    } else
      print $did.','.$att->title.','.$ipv4.','.implode('|',$snmp)."\n";
  }
}

function cnmlwalk($cnml,$SNPServer,$arr = array(), $export = FALSE) {


  foreach($cnml as $tag=>$value) {
    $sons = $export;
    $att = $value->attributes();
    if (!empty($att['graph_server']))
    if ($att['graph_server'] == $SNPServer)
       $sons = TRUE;
    else
       if ($att['graph_server'] != 0)
         $sons = FALSE;
    switch ($tag) {
      case 'device':
        $arr['device'][(int)$att['id']] = $value;
        if ($att['type'] == 'radio') 
        foreach($value as $dtag=>$dvalue) {
          $datt = $dvalue->attributes();
          switch ($dtag) {
          case 'radio':
            if ($datt['mode'] == 'ap') {
              if ($sons) { 
                $arr['mrtg'][(int)$att['id']]=NULL;
                // radio interfaces
                foreach($dvalue as $rtag=>$rvalue) {
                  if ($rtag == 'interface') foreach($rvalue as $itag=>$ivalue) {
                    if ($itag == 'link') {
                      $latt = $ivalue->attributes();
                      $arr['mrtg'][(int)$latt['linked_device_id']]=NULL;
                    } // foreach link
                  }  
                }
              } 
            } // radio in mode ap
            break;
          case 'interface':
            // check if the radio has its own links
            if ($sons) {
              $arr['mrtg'][(int)$att['id']]=NULL;
              foreach($dvalue as $itag=>$ivalue) {
                if ($itag == 'link') {
                  $latt = $ivalue->attributes();
                  $arr['mrtg'][(int)$latt['linked_device_id']]=NULL;
                } // foreach link
              }
            }
            break;
          }
        } // is a radio
      case 'node':
      case 'network':
      case 'zone':
        $arr = cnmlwalk($value,$SNPServer,$arr,$sons);
        break;
    }
  }


  return $arr;
}

if (file_exists("/etc/snpservices/config.php")) {
   include_once("/etc/snpservices/config.php");
} else {
  include_once("/etc/snpservices/config.php.template");
}

// Controlinmg time execution (this routine should be very efficient)
$time_start = microtime(true);

if (!isset($_GET['server'])) {
//  echo "You should provide the ID of a valid SNP graph server\n";
//  exit();
  $SNPServer=$SNPGraphServerId;
} else
  $SNPServer = $_GET['server'];

$cnml = simplexml_load_file('/var/lib/snpservices/data/guifi.cnml');
$servers = $cnml->xpath("//service[@id=".$SNPServer." and @type='SNPgraphs']");

// print count($servers);
if (count($servers) == 0) {
  echo "You must provide a valid server id\n";
  exit();
}

$arr = cnmlwalk($cnml,$SNPServer);
//print "devices: ".count($arr['device'])."\n";
//print "snmp devices: ".count($arr['mrtg'])."\n";
//print_r($arr);
$time_1 = microtime(true);
//print "guifi.cnml loaded ".($time_start - $time_1)."\n";

//header('Content-type: application/csv');
foreach($arr['mrtg'] as $id=>$foo) {
  snmplist($id,$arr['device']);
}
?>
