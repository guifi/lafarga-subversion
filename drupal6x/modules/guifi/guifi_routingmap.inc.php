<?php
/*
 * Created on 01/12/2009 by Eduard
 *
 * functions for routing map tools
 */

function guifi_routingmap($action = 'init',$actionid) {
  if (!is_numeric($actionid)) return;
		
	switch ($action) {
	case 'init':
		guifi_routingmap_init();
	case 'search':
		$json=guifi_routingmap_search($_GET["lat1"],$_GET["lon1"],$_GET["lat2"],$_GET["lon2"]);
		//echo $json;
		echo $json;
		return;
		break;
	}
}
 
function guifi_routingmap_init(){
  $output = "";
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_routingmap.js','module');
    $output .=  '<form>' .
        '<input type=hidden value='.base_path().drupal_get_path('module','guifi').'/js/'.' id=edit-jspath />' .
        '<input type=hidden value='.variable_get('guifi_wms_service','').' id=guifi-wms />' .
        '</form>';
    $output .= '<div id="map" style="width: 100%; height: 600px; margin:5px;"></div>';
  }

  guifi_log(GUIFILOG_TRACE,'routingmap',1);

  return $output;
}
function guifi_routingmap_search($plat1,$plon1,$plat2,$plon2){

   $result=db_query(sprintf("SELECT t1.id as nid,t1.lat,t1.lon
            FROM guifi_location as t1
            where t1.status_flag='Working' and t1.lat between (%s) and (%s) and
						t1.lon between (%s) and (%s);",$plat1,$plat2,$plon1,$plon2));

	$cnmlid=0;
  while ($record=db_fetch_object($result)){
    $cnmlid=$record->nid;
	  break;
	}
	//return($cnmlid);
	
  $nmax=200;   //num maxim de nodes a procesar
  $networks = Array(); //array de subxarxes de la zona
  $nodes = Array(); //array de nodes id de la zona
  $nodesid=Array(); //array de dades del node + control de repeticions
  $subnets=Array(); //array de subxarxes agrupades
  $azones=Array(); //array de zones implicades
  $aznets=Array(); //array de xarxes de les zones implicades
  $alinks=Array(); //Array links
  $nreg = 0;
  $n=0;

  $nodesid["$cnmlid"]="";
  $nodes[]=$cnmlid;
   //busqueda de nodes de la zona ospf
   $tnodes=0;
   while (isset($nodes[$n])){
      $tnodes=$tnodes+guifi_routingmap_search_links($nodes,$nodesid,$alinks,$nodes[$n]);
      if ($tnodes<$nmax){
         $n++;
      } else {
         break;
      }
   }
   ksort($nodesid);
   //busqueda de dades node, subxarxes de cada node, llista de zones
   $nreg=count($nodes);
   for($n=0;$n<$nreg;$n++){
      $result=db_query(sprintf("SELECT t1.nick as nnick, t1.zone_id as zid, t1.lat, t1.lon, t2.nick as znick
               FROM guifi_location as t1
               join guifi_zone as t2 on t1.zone_id = t2.id 
               where t1.id = (%s)",$nodes[$n]));
      if ($record=db_fetch_object($result)){
         $nodesid["$nodes[$n]"]=Array("nnick"=>$record->nnick,"zid"=>$record->zid,"lat"=>$record->lat,"lon"=>$record->lon);
         if (!isset($azones[$record->zid])){
            $azones[$record->zid]=$record->znick;
         }
      };
      guifi_routingmap_add_node_networks($networks,$nodes[$n]);
   }
	 
   
   ksort($networks);
   //busqueda de xarxes de la zona
   if (count($azones)) foreach ($azones as $key=>$azone){
      $result=db_query(sprintf("SELECT t1.base as netid, t1.mask as mask
               FROM guifi_networks as t1
               where t1.zone = (%s)",$key));
      while ($record=db_fetch_object($result)){
         $a = _ipcalc($record->netid,$record->mask);
         $splitip=explode(".",$a["netid"]);
         $c=$splitip[0]*pow(256,3)+$splitip[1]*pow(256,2)+$splitip[2]*256+$splitip[3];
         if (!isset($aznets[$c])){
            $aznets[$c]=Array("netid"=>$a["netid"],"maskbits"=>$a["maskbits"],"broadcast"=>$a["broadcast"],"zid"=>$key,"swagr"=>0);
         }elseif ($aznets[$c]["maskbits"]>$a["maskbits"]){
            $aznets[$c]=Array("netid"=>$a["netid"],"maskbits"=>$a["maskbits"],"broadcast"=>$a["broadcast"],"zid"=>$key,"swagr"=>0);
         }
      }
   }
   ksort($aznets);
   //verifica que les xarxes de zona estiguin als nodes de la zona ospf
   $result=db_query("SELECT ipv4, netmask FROM {guifi_ipv4} where ipv4_type = 1");
   while ($ip=db_fetch_array($result)){
      if ( ($ip['ipv4'] != 'dhcp') and (!empty($ip['ipv4'])) )  {
        $ip_dec = ip2long($ip['ipv4']);
        $min = false; $max = false;
        if (!isset($ips[$ip_dec]))
          // save memory by storing just the maskbits
          // by now, 1MB array contains 7,750 ips
          $ips[$ip_dec] = guifi_ipcalc_get_maskbits($ip['netmask']);
      }
   }
   //agrupació de subxarxes
   $subnets=array_values($networks);
   
   for($nmaskbits=30;$nmaskbits>16;$nmaskbits--){
      $net1="";
      $knet1=0;
      $nreg=0;
      if (count($subnets)) foreach ($subnets as $key=>$subnet){
         if ($subnet["maskbits"]==$nmaskbits){
            $nreg++;
            $a = _ipcalc_by_netbits($subnet["netid"],$nmaskbits-1);
            if ($a["netid"]!=$net1){
               $net1=$a["netid"];
               $knet1=$key;
            }else{
               $subnets[$knet1]["maskbits"]=$nmaskbits-1;
               unset($subnets[$key]);
               $net1="";
               $knet1=0;
            }
         }else{
            $net1="";
            $knet1=0;
         }
      }
      //if($nreg==0){
      //   break;
      //}
   }
  return json_encode(array($cnmlid,$nodesid,$alinks));
   //   $networks[$c]=Array("ipv4"=>$record->ipv4,"netmask"=>$record->netmask,"netid"=>$a["netid"],"maskbits"=>$a["maskbits"],"nid"=>$nid);
}

function guifi_routingmap_search_links(&$nodes,&$nodesid,&$alinks,$nid){
   $n=0;
   $resultlinks=db_query(sprintf('SELECT id FROM guifi_links where nid = (%s) and routing="OSPF" and (link_type="cable" or link_type="wds")',$nid));
   while ($recordlink=db_fetch_object($resultlinks)){
      $result=db_query(sprintf("SELECT nid, routing, link_type FROM guifi_links where id = (%s) and nid != (%s)",$recordlink->id,$nid));
      if ($record=db_fetch_object($result)){
         if (!isset($nodesid["$record->nid"]) && $record->routing="OSPF" && ($record->link_type="cable" || $record->link_type="wds")){
            $nodesid["$record->nid"]="";
            $nodes[]=$record->nid;
            $n++;
         };
         if (!isset($alinks["$recordlink->id"])){
          $alinks["$recordlink->id"]=Array("nid1"=>$nid,"nid2"=>$record->nid);
         }
      };
   };
   return $n;
}

function guifi_routingmap_add_node_networks(&$networks,$nid){
   $v="";
   $result=db_query(sprintf("SELECT t3.ipv4, t3.netmask
               FROM guifi_devices as t1
               join guifi_interfaces as t2 on t1.id = t2.device_id
               join guifi_ipv4 as t3 on t2.id = t3.interface_id
               where t1.nid = (%s) and t3.ipv4_type=1",$nid));
   while ($record=db_fetch_object($result)){
      $a = _ipcalc($record->ipv4,$record->netmask);
      $c=ip2long($a["netid"]);
      if (!isset($networks[$c])){
         $networks[$c]=Array("ipv4"=>$record->ipv4,"netmask"=>$record->netmask,"netid"=>$a["netid"],"maskbits"=>$a["maskbits"],"nid"=>$nid);
      }elseif ($networks[$c]["maskbits"]>$a["maskbits"]){
         $networks[$c]=Array("ipv4"=>$record->ipv4,"netmask"=>$record->netmask,"netid"=>$a["netid"],"maskbits"=>$a["maskbits"],"nid"=>$nid);
      }
   };
   return 0;
}
  //$vjson=json_encode(array($objects,$nodes,$links));
  //return $vjson;

?>
