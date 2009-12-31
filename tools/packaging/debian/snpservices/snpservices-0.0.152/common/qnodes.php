<?php

if (file_exists('/etc/snpservices/config.php'))
  include_once("/etc/snpservices/config.php");
else
  include_once("/etc/snpservices/config.php.template");

include_once("../common/misc.php");

if (isset($_GET['nodes']))
  $nodes=$_GET['nodes'];
else
  // for testing purposes, filling the query with a fixed list
  // $nodes = '3682,2673,2653,4675,5887,6362,5531,2201,5452,3310,6446,5506,5836,5846,6032,4631,6093,3718,6530,3725,3833,4030,4120,3998,5519,5525,6038,6173,6228,6298,2784,6601,6703,1636,4796,5486,5765,5784,5816,5046,4720,6199,6291,6555,6549,6596,6616,6659,6732,2984';
  die ('Error: At least one node has to be given to the query as a parameter. Syntax: qnodes?nodes=1,2,3,4,5');

$an = explode(',',$nodes);

$cnml = simplexml_load_file($CNMLData);
$nxml = $cnml->xpath('//node[@id='.implode(' or @id=',$an).']');
$rxml = '<cnml>';
foreach ($nxml as $n)
  $rxml .= $n->asXML();
$rxml .= '</cnml>';
$CNML = simplexml_load_string($rxml);

$CNML->addAttribute('version','0.1');
$CNML->addAttribute('server_id','1');
$CNML->addAttribute('server_url','http://guifi.net');
$CNML->addAttribute('generated',date('Ymd hi',time()));
$classXML = $CNML->addChild('class');

$classXML->addAttribute('node_description',$nodes);
$classXML->addAttribute('mapping','y');

header('Content-type: application/xml; charset=utf-8');
echo $CNML->asXML();


?>
