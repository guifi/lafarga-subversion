<?php

//==========================================================
//
// 04/05/2007 
//
// Codi: exportxml-budgets.php (v1.00)
// Ubicacio: a ubicar en guifi.net
//
// Descripcio:
// Requereix ser incluit en un altre Php que obri la bdd per
// poder realitzar les consules pertinets. Genera un xml
// a fi d'exportar informacio relatia als apadrinaments.
// Si hi a cap problema per conectar amb la bdd retorna
// el tag xml "budgets" amb valor -1 amb el que despres
// a l´hora d'importar es pot esta al corrent i mostrar
// un misatge adecuat i descriptiu del problema, per no
// confondra amb quan no hi han apadrinaments actius.
//
// Control seleccio de dades (url):
//
//   - Cap parametre, extreu tots el oberts
//	 - "nid" > 0 seleccio per apadrinamet
//	 - "gzn" > 0 seleccio per zona (guifi-zona)
//
//----------------------------------------------------------
//
// Configuracio:
//
// + Access a Base de dades:
//
//		- $setting_mysql_server = ip_servidor
//		- $setting_mysql_user 	= usuari
//		- $setting_mysql_pass 	= password
//		- $setting_mysql_db			= nomb_bdd
//
//==========================================================
//
// Fortmat XML simple:
// Nota: Se incluye espacio despues de < y antes
// de > puesto que de otra forma el codigo php se
// ejecuta mal (en esta descripcion de formato)
//
// < ?xml version="1.0" encoding="iso-8859-1"? >
// < xmlbudgets >
//		< budgets >NumeroTotaldeRegistres< /budgets >
//		< budget9999 >
//			< id>Codi-nid< /id >
//			< title >
//				Titulo descriptivo del budget
//			< /title >
//			< imports >
//				< amount >ImporteTotal< /amount >
//				< funds >ImportesApadrinamientos< /funds >
//			< /imports >
//		< /budget9999 >
//		...
// < /xmlbudgets >
//
//----------------------------------------------------


	// Completar parametres bdd, per conexio 
	$setting_mysql_server="ip";
	$setting_mysql_user="usuari";
	$setting_mysql_pass="pwd";
	$setting_mysql_db="bd";

//-----------------------------------------------------

	// Recullir parametres
	$nid=intval($_GET["id"]);
	$guifi_zones=intval($_GET["gzn"]);

	// ubicacio relativa al directori d'aquest script ;)
	$root="../";		
	
	// ---------------------------------------------------------
	// Preparar conexio a la bdd
	//

	//Desactivar temporalmente control de errores
	$error_reporting=error_reporting(0);

	$conn_db_ok=1;
			
	if (!$conn_db=mysql_connect($setting_mysql_server,$setting_mysql_user,$setting_mysql_pass)) $conn_db_ok=0; 
	if (!mysql_select_db($setting_mysql_db,$conn_db)) $conn_db_ok=0;
	
	// Activar nuevamente control de errores
	error_reporting($error_reporting);
	unset($error_reporting);
	
	//
	//
	// ---------------------------------------------------------
	
	// Si conexio aconseguida
	if ($conn_db_ok) {

header("Content-type: text/xml"); 

		// Opcions: Obtenir un budget, tots els relacionats amb una zona
		// o be tots els existents, sempre que estiguin en estat "Obert" 
		
		// AVIS, he tingut de retirar el "ORDER BY change DESC" del final de les
		// sentecies SELECT degut a que en la descripcio de l'estructura no hi
		// es i per tant no se que fer amb aquest camp. Aixo putser es pot 
		// corretgir si finalment a la estructura real si que hi es.
		
		if ($nid!=0) {
			// Seleccio per numero de budget (Obert)
			$qbudgets_cond = " WHERE b.id=$nid and n.nid=b.id AND budget_status = 'Open' ";
			$qbudgets = mysql_query("SELECT n.nid, n.title, currency_symbol FROM budgets b, node n".$qbudgets_cond,$conn_db);
		} else if ($guifi_zones!=0) {
			// Seleccio de bugets  Oberts	 d'una zona		
			$qbudgets_cond = " WHERE n.nid=b.id AND budget_status = 'Open' AND b.zone_id = $guifi_zones ";		
			$qbudgets = mysql_query("SELECT n.nid, n.title, currency_symbol FROM budgets b, node n".$qbudgets_cond,$conn_db);
		} else {
			// Seleccio de bugets Oberts		
			$qbudgets_cond = " WHERE n.nid=b.id AND budget_status = 'Open' ";
			$qbudgets = mysql_query("SELECT n.nid, n.title, currency_symbol FROM budgets b, node n".$qbudgets_cond,$conn_db);
		}
	
		echo mysql_error();
		
		// Contar numero budgets que s'incluiran en el XML
		$qbudgets_cnt = mysql_num_rows($qbudgets);
		
echo '<?xml version="1.0" encoding="iso-8859-1"?>';
echo '<xmlbudgets>'; 
	
		if ($qbudgets_cnt > 0) {
			
			$qbudgets_pos = 0; 
			echo '<budgets>'.$qbudgets_cnt.'</budgets>';	
			
			// Incluir al XML els budgets obtinguts a la consulta a la BDD		
			while ($budget = mysql_fetch_array($qbudgets)) {
		    $temporaly_budge_nid=intval($budget['id']);
		    
				$t = mysql_fetch_array(mysql_query("SELECT sum(quantity * cost) amount FROM budget_items WHERE id = $temporaly_budge_nid",$conn_db));
				$s = mysql_fetch_array(mysql_query("SELECT sum(amount) amount FROM budget_funds WHERE fund_status != 'Declined' AND id = $temporaly_budge_nid",$conn_db));
				
				$qbudgets_pos++;
				
		    extract($t,EXTR_PREFIX_ALL,"budget_total");
			  extract($s,EXTR_PREFIX_ALL,"budget_funds");	  
			  
				$bud_id = $budget["id"];
				$bud_titulo = $budget["title"];
				$bud_imp_total = $budget_total_amount;
				$bud_imp_aport = $budget_funds_amount;
				//
				echo '<budget'.$qbudgets_pos.'>'; 
					echo '<id>'.$bud_id.'</id>';
					echo '<title>'; 
						echo xml_budgets_espchar($bud_titulo); 
					echo '</title>'; 
					echo '<imports>';
						echo '<amount>'.$bud_imp_total.'</amount>'; 
						echo '<funds>'.$bud_imp_aport.'</funds>'; 
					echo '</imports>';
				echo'</budget'.$qbudgets_pos.'>';
			}
	
		} else {
			echo '<budgets>0</budgets>';				
		}
		
echo '</xmlbudgets>'; 
	
	} else {
		// Tornar un xml amb un numero de budgets = -1 
		// indicant un error d'access a la bdd a fi 
		// que qui extreu pugi detectar que s'ha 
		// produit aquest problema ;)
		echo '<?xml version="1.0" encoding="iso-8859-1"?>';
		echo '<xmlbudgets><budgets>-1</budgets></xmlbudgets>';
	}

	
// ------------------------------------------------------------------------	
	
//
// Substituir caracters accentuats i d'altres per 
// evitar problemes derivats de la taula de caracters
// del receptor de l'arxiu XML
//
Function xml_budgets_espchar ($contenido) {
	$contenido=ereg_replace("á","a",$contenido); 
	$contenido=ereg_replace("é","e",$contenido); 
	$contenido=ereg_replace("í","i",$contenido); 
	$contenido=ereg_replace("ó","o",$contenido); 
	$contenido=ereg_replace("ú","u",$contenido); 
	$contenido=ereg_replace("Á","A",$contenido); 
	$contenido=ereg_replace("É","E",$contenido); 
	$contenido=ereg_replace("Í","I",$contenido); 
	$contenido=ereg_replace("Ó","O",$contenido); 
	$contenido=ereg_replace("Ú","U",$contenido); 
	$contenido=ereg_replace("Ñ","NI",$contenido); 
	$contenido=ereg_replace("ñ","ni",$contenido);
	
	return $contenido;
}

?>
