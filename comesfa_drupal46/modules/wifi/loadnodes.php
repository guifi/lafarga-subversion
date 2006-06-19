<?php

function load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_file) {
  global $mapid;


  $mapid = $mapid + 1;

  //   Dades calculades, no tocar

  $difY = $refY - $refY2;	 // Diferència entre Y dels dos punts
  $difLAT = $posY2 - $posY; // Diferència de latitud entre els dos punts
  $difX = $refX2 - $refX;	     // Diferència entre Y dels dos punts
  $difLON = $posX2 - $posX; // Diferència de longitud entre els dos punts
  $convY = $difLAT / $difY; // Conversió Latitud->pixels
  $convX = $difLON / $difX; // Conversió Latitud->pixels
  $absY = $posY + ($convY * $refY); // Conversió Latitud->pixels
  $absX = $posX - ($convX * $refX); // Conversió Latitud->pixels

  $output = "<h2>" .$map_name ."</h2>Conversio phpwirelessmap: <br>Constants de calibració: <br><ul>";
  $output .= "<li>posY: Latitud del punt més inferior, " .$posY ."</li>";
  $output .= "<li>difY: Diferència entre Y dels dos punts, " .$difY ."</li>";
  $output .= "<li>refY: Coordenada Y del punt posY, " .$refY ."</li>";
  $output .= "<li>difLAT: Diferència de latitud entre els dos punts, " .$difLAT ."</li>";
  $output .= "<li>posX: Longitud del punt més inferior, " .$posX ."</li>";
  $output .= "<li>difX: Diferència entre X dels dos punts, " .$difX ."</li>";
  $output .= "<li>refX: Coordenada X del punt posX, " .$refX ."</li>";
  $output .= "<li>difLON: Diferència de longitud entre els dos punts, " .$difLON ."</li>";
  $output .= "</ul>Dades del Mapa:<ul>";
  $output .= "<li>Conversió Longitud->pixels (X): " .$convX ."</li>";
  $output .= "<li>Conversió Latitud->pixels (Y): " .$convY ."</li>";
  $output .= "<li>Coordenada 0,0 (Lat,Lon): (" .$absY ."," .$absX .")</li>";
  $output .= "</ul><br>";

  // Inserta el mapa  
  $sqlinsert = "insert into " .$phpwm_db .".map(id, img_map_name, img_map_src_file, gps_orig_lat, gps_orig_long, gps_echelle_lat, gps_echelle_long) values (\"" 
  	.$mapid ."\",\"" .$map_name ."\",\"./map/"
  	.$img_src_file ."\"," .$absY ."," .$absX .","
        .sprintf("%d,%d",$convY,$convX) .");";
  $output .= "Inserta: " .$sqlinsert ."<br>";
          
  db_query($sqlinsert);

  // Inserta els nodes
  $result = db_query( "SELECT     l.nick, "
  			."        l.zone, l.state, l.sponsor, "
  			."        l.nid, l.lat, l.lon "
  			."FROM {wifi_location} l "
    			."ORDER BY l.nid");
  while ($row = db_fetch_array($result)) {
       sscanf($row["lon"],"%f",$long1);
       sscanf($row["lat"],"%f",$lat1);

//       $output .= "Processant: " .$row["nick"] ."<br>";  
       if ($lat1 or $long1) { // Només s'inserta si el lloc té coordenades
          $Y = $refY - round((($lat1 - $posY) * $difY) / $difLAT);
          $X = $refX + round((($long1 - $posX) * $difX) / $difLON);
          switch ($row["state"]) {
  		case "Working": $state=1; break;
  		case "Planned": $state=0; break;
  		case "Building": $state=2; break;
          }
          $res = 2400; // Default map resolution
          switch ($img_src_file) { // Other map resolutions
		case "Catalunya2001.png": $res = 4800; break;
		case "Catalunya.png": $res = 3600; break;
          }
//          if ($X >= 0 and $Y >= 0 and $X <= 2400 and $Y <= 2400)  { // Només s'inserta si és dins del mapa
          if ($X >= 0 and $Y >= 0 and $X <= $res and $Y <= $res)  { // Només s'inserta si és dins del mapa
            $sqlinsert = "insert into " .$phpwm_db .".node(id, map, nick, email, status, x, y) values (\"" 
  		.(($mapid * 1000000) +$row["nid"]) ."\",\"" .$mapid ."\",\"" .$row["nick"] ."\",\""
  		.$row["sponsor"] ."\"," .$state .","
                  .sprintf("%d,%d",$X,$Y) .");";
            $output .= "Inserta: " .$sqlinsert ."<br>";
          
  	    db_query($sqlinsert,$conn);
         } // Era dins del mapa
       } // if $lat$lon
  }
  
  // Vaig a insertar els enllaços
  $result =db_query(	 "SELECT  c.cid, l1.nid nid1, l2.nid nid2 "
  			."FROM {wifi_link} c, "
  			."     {wifi_radio} l1, "
  			."     {wifi_radio} l2, "
  			."     " .$phpwm_db .".node n "
  			."WHERE c.rid1 = l1.rid AND c.rid2 = l2.rid "
  			."  AND l1.nid + (1000000 * $mapid) = n.id "
  			."ORDER BY c.cid");
  while ($row = db_fetch_array($result)) {
  
            $sqlinsert = "insert into " .$phpwm_db .".link(id, node1_id, node2_id, type) values (" 
  		.($row["cid"] + (1000000 * $mapid)) ."," .($row["nid1"] + (1000000 * $mapid)) ."," .($row["nid2"] + (1000000 * $mapid)) .",1);";
            $output .= $sqlinsert ."<br>";
          
  	    db_query($sqlinsert,$conn);
  }
  return $output;
}

function load_pwpwm_nodes ($debug = 0) {
// Inicialitza taules i variables
$mapid = 0;
$phpwm_db="phpwmtest";
db_query("delete from " .$phpwm_db .".node;");
db_query("delete from " .$phpwm_db .".map;");
db_query("delete from " .$phpwm_db .".link;");


// Constants de calibració (varia de mapa a mapa)
// Per calibrar un mapa:
//  Carregar un mapa del phpwirelessmap
//  Marcar 2 punts en el mapa amb coordenades conegudes relativament distanciats entre si
//  Consultar els valors X i Y que ha posat en la taula de la base de dades i...
//  Dades constants a modificar segons el mapa:

// Osona-Centre
$posY = 41.936750;   // Latitud del punt més inferior
$posX = 2.257277;    // Longitud del mateix punt que hem agafat per posY 
$refX = 1311;	     // Coordenada X 
$refY = 1404;	     // Coordenada Y 

$posY2 = 41.975861;  // Latitud del punt més superior
$posX2 = 2.236805;   // Longitud
$refX2 = 1098;	     // Coordenada X 
$refY2 = 847;	     // Coordenada Y 

$img_src_map = "Osona-Centre.png"; // Imatge del mapa
$map_name = "Osona - Centre";


$output = load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Osona-Sud
$posY = 41.83724;   // Latitud del punt més inferior
$posX = 2.28112;    // Longitud del mateix punt que hem agafat per posY 
$refX = 1546;	     // Coordenada X 
$refY = 1331;	     // Coordenada Y 

$posY2 = 41.91169;  // Latitud del punt més superior
$posX2 = 2.18540;   // Longitud
$refX2 = 522;	     // Coordenada X 
$refY2 = 278;	     // Coordenada Y 

$img_src_map = "Osona-Sud.png"; // Imatge del mapa
$map_name = "Osona - Sud";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Osona-Nord
$posY = 41.96818;   // Latitud del punt més inferior
$posX = 2.31634;    // Longitud del mateix punt que hem agafat per posY 
$refX = 1930;	     // Coordenada X 
$refY = 1925;	     // Coordenada Y 

$posY2 = 42.10089;  // Latitud del punt més superior
$posX2 = 2.21674;   // Longitud
$refX2 = 904;	     // Coordenada X 
$refY2 = 65;	     // Coordenada Y 

$img_src_map = "Osona-Nord.png"; // Imatge del mapa
$map_name = "Osona - Nord";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Osona
$posY = 41.936750;   // Latitud del punt més inferior
$posX = 2.257277;    // Longitud del mateix punt que hem agafat per posY 
$refX = 1255;	     // Coordenada X 
$refY = 1305;	     // Coordenada Y 

$posY2 = 41.975861;  // Latitud del punt més superior
$posX2 = 2.236805;   // Longitud
$refX2 = 1150;	     // Coordenada X 
$refY2 = 1028;	     // Coordenada Y 

$img_src_map = "Osona.png"; // Imatge del mapa
$map_name = "Osona - Global";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Anoia
$posY = 41.47095;   // Latitud del punt més inferior (St. Jaume Sesoliveres/Ctra Canaletes)
$posX = 1.74993;    // Longitud del mateix punt que hem agafat per posY 
$refX = 2011;	     // Coordenada X 
$refY = 2322;	     // Coordenada Y 

$posY2 = 41.59174;  // Latitud del punt més superior
$posX2 = 1.59597;   // Longitud
$refX2 = 444;	     // Coordenada X 
$refY2 = 616;	     // Coordenada Y 

$img_src_map = "Anoia-Centre-SudEst.png"; // Imatge del mapa
$map_name = "Anoia - Sud Est";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Alt Penedes Centre-Est
$posY = 41.33600333;// Latitud del punt més inferior (Ctra a Begues Parc)
$posX = 1.88673667;    // Longitud del mateix punt que hem agafat per posY 
$refX = 2248;	     // Coordenada X 
$refY = 1871;	     // Coordenada Y 

$posY2 = 41.45088667;  // Latitud del punt més superior (St. Pere de Riudebitlles)
$posX2 = 1.69679667;   // Longitud
$refX2 = 153;	     // Coordenada X 
$refY2 = 136;	     // Coordenada Y 

$img_src_map = "APenedesCE.png"; // Imatge del mapa
$map_name = "Alt Penedes - Centre Est";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Maresme - Est
$posY = 41.64248;   // Latitud del punt més inferior (Malgrat, la pineda)
$posX = 2.72640;    // Longitud del mateix punt que hem agafat per posY 
$refX = 2053;	     // Coordenada X 
$refY = 1339;	     // Coordenada Y 

$posY2 = 41.729;  // Latitud del punt més superior (Batlloria-Viabreana)
$posX2 = 2.56792;   // Longitud
$refX2 = 388;	     // Coordenada X 
$refY2 = 131;	     // Coordenada Y 

$img_src_map = "Maresme-Est.png"; // Imatge del mapa
$map_name = "Maresme - Est";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Maresme - Sant Pol
$posY = 41.601621;   // Latitud del punt més inferior (Estacio)
$posX = 2.624525;    // Longitud del mateix punt que hem agafat per posY 
$refX = 1177;	     // Coordenada X 
$refY = 970;	     // Coordenada Y 

$posY2 = 41.605526;  // Latitud del punt més superior (S.Autopista)
$posX2 = 2.609433;   // Longitud
$refX2 = 153;	     // Coordenada X 
$refY2 = 623;	     // Coordenada Y 

$img_src_map = "Maresme-SantPol.png"; // Imatge del mapa
$map_name = "Maresme - Sant Pol de Mar";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Arenys 
$posY = 41.577983;   // Latitud del punt més inferior (Port)
$posX = 2.559367;    // Longitud del mateix punt que hem agafat per posY 
//$refX = 2191;	     // Coordenada X 
$refX = 2161;	     // Coordenada X 
//$refY = 2177;	     // Coordenada Y 
$refY = 2110;	     // Coordenada Y 

$posY2 = 41.596898;  // Latitud del punt més superior (C.Vinyes)
$posX2 = 2.530538;   // Longitud
$refX2 = 147;	     // Coordenada X 
$refY2 = 362;	     // Coordenada Y 

$img_src_map = "ArenysM3.png"; // Imatge del mapa
$map_name = "Maresme - Arenys de Mar";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);



// Catalunya 
$posY = 41.57874667;   // Latitud del punt més inferior (Arenys)
$posX = 2.55179833;    // Longitud del mateix punt que hem agafat per posY 
//$refX = 3550;	     // Coordenada X 
//$refY = 2578;	     // Coordenada Y 
$refX = 2662;	     // Coordenada X 
$refY = 1931;	     // Coordenada Y 

$posY2 = 42.702655;  // Latitud del punt més superior (Vielha)
$posX2 = 0.79413833;   // Longitud
//$refX2 = 1065;	     // Coordenada X 
//$refY2 = 377;	     // Coordenada Y 
$refX2 = 797;	     // Coordenada X 
$refY2 = 282;	     // Coordenada Y 

$img_src_map = "Catalunya.png"; // Imatge del mapa
$map_name = "Catalunya";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);

// Catalunya - Central
$posY = 41.57874667;   // Latitud del punt més inferior (Arenys)
$posX = 2.55179833;    // Longitud del mateix punt que hem agafat per posY 
$refX = 1977;	     // Coordenada X 
$refY = 1499;	     // Coordenada Y 

$posY2 = 41.99304167;  // Latitud del punt més superior (Vielha)
$posX2 = 1.51390833;   // Longitud
$refX2 = 289;	     // Coordenada X 
$refY2 = 576;	     // Coordenada Y 

$img_src_map = "CatalunyaCentral.png"; // Imatge del mapa
$map_name = "Catalunya - Central";


$output .= load_phpwm_map($mapid, $phpwm_db, $posX,$posY,$refX,$refY,$posX2,$posY2,$refX2,$refY2,$map_name, $img_src_map);
if ($debug)
   print $output;
}
?>
