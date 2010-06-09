<?php
/*
Plugin Name: WP-Guifi
Plugin URI: http://blog.albertsarle.com/wp-guifi
Description: Visualitza una llista de nodes operatius i la seva disponibilitat
en una zona de guifi (http://guifi.net, la xarxa lliure oberta, alternativa i neutra)
Author: Albert Sarle 
Version: 0.4
Author URI: http://blog.albertsarle.com
*/
/*  Copyright 2009  ALBERT SARLÉ  (email : albertsarle@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*
 *
 * Changelist :
 *
 * v 0.4 - 9/6/2010
 * Corretgit identificador de zona per defecte (88022->8802 Terrassa)
 * Eliminar cestrencades del codi $enllaços -> $enllacos
 *
 * v 0.3 - 27/8/2009
 * Afegida opcio per ordenar descendent els nodes (per id, alfabetic, per # de aps , per # de devices)
 * Afegit logo i enllaç a guifi.net encapçalant el formulari de gestió del widget
 * Passat html de li a table per ajustar amplades de celes (al cap i a la fi es un llistat)
 * Igualades l'amplada de les celes del resum amb les de la llista de nodes.
 * Per als supernodes , quan connectivitat es mostren tots els seus devices
 * Remaquetacio HTML de la llista de nodes, status sempre alineat a la dreta
 * Afegit parametre call=availability a les imatges de connectivitat per evitar  problemes amb versions diefrents de servidors de grafiques.
 * Corretgida deteccio de supernodes afegint restriccio al xpath (access_points>1) && (devices>1)
 * Afegit enllaç a la gestió del widget des del llistat de plugins
 *
 * v 0.2
 * Afegit Temps de catxe configurable
 * Afegida opció per anar a buscar la disponibilitat  dels servidors de gràfiques o no. Estalviant consultes HTTP
 * Afegida taula de Resum de Zona
 * Inicialitzacio de variables per defecte
 * Afegit Limit de nodes al llistat
 * Afegit filtre de supernodes
 * Neteja de cache al actualitzar opcions.
 *
 * v 0.1
 * show me the code!!
 *
 */

  // valors de la llista de estats dels nodes
  $nodeStatusList = array();
  $nodeStatusList[0] = "All";
  $nodeStatusList[1] = "Working";
  $nodeStatusList[2] = "Testing";
  $nodeStatusList[3] = "Building";
  $nodeStatusList[4] = "Planned";

  // valors de la llista de ordenacions dels nodes
  $orderByList = array();
  $orderByList[0] = "Id Node";
  $orderByList[1] = "Nom node";
  $orderByList[2] = "Access Points";
  $orderByList[3] = "Devices ";
  $orderBySelect = 0;

// Encapsulament general del widget (sintaxi de wordpress)
function widget_wp_guifi_init () {
  // Check for the required plugin functions. This will prevent fatal
  // errors occurring when you deactivate the dynamic-sidebar plugin.
  if ( !function_exists('register_sidebar_widget') )
    return;


  // Rep dos elements tipus DOMNodeList i els compara segons el criteri de la
  // variable global $orderBySelect.
  function compareNodes($a, $b) {
    global $orderBySelect;

    switch ($orderBySelect) {
      case 0:
          $attribute = "id";
          break;
      case 1:
          $attribute = "title";
          break;
      case 2:
          $attribute = "devices";
          break;
      case 3:
          $attribute = "access_points";
          break;
    }
    if ($a->getAttribute($attribute) < $b->getAttribute($attribute)) return 1;
    else if($a->getAttribute($attribute) == $b->getAttribute($attribute)) return 0;
    else return -1;
  }

  // Donat l'estat d'un node retorna el codi hexadecimal del color asssociat
  // segons els disseny de guifi.net
  function getStatusColor($status){
    $nodeStatusColor = array();
    $nodeStatusColor[0] = "#FFF";
    $nodeStatusColor[1] = "#33FF00";
    $nodeStatusColor[2] = "#FF9900";
    $nodeStatusColor[3] = "#FFFF99";
    $nodeStatusColor[4] = "#66FFFF";

    switch ($status){
      case "Working":
        $color = '#33FF00';
        break;
      case "Planned":
        $color = '#66FFFF';
        break;
      case "Building":
        $color = '#FFFF99';
        break;
      case "Testing":
        $color ='#FF9900';
        break;
    }
    return $color;
  }

  // Presentació del formulari de gestió del widget des del backoffice del WP
  function widget_wp_guifi_control() {
    global $nodeStatusList, $orderByList;

    // Get our options and see if we're handling a form submission.
    $options = get_option('widget_wp_guifi');
    if ( !is_array($options) )

      // definicio de valors per defecte
      $options = array(
       'nodeid' => '8802', // identificador de la zona de Terrassa
       'title' => 'Guifi.net ',
       'status' => 0,
       'orderby' =>1,
       'typeinfo' => 1,
       'resumzona' => 1,
       'nodelimit' => 5,
       'supernode' => 0,
       'typeinfo' => 1,
       'cachetime' => 900
      );

    if ( $_POST['wp_guifisubmit'] ) {
      // Remember to sanitize and format use input appropriately.
      $options['nodeid'] = strip_tags(stripslashes($_POST['wp_guifi_nodeid']));
      $options['title'] = $_POST['wp_guifi_title'];
      $options['cachetime'] = $_POST['wp_guifi_cachetime'];
      $options['status'] = $_POST['wp_guifi_filterstatus'];
      $options['orderby'] = $_POST['wp_guifi_orderby'];
      $options['typeinfo'] = $_POST['wp_guifi_typeinfo'];
      $options['resumzona'] = ($_POST['wp_guifi_resumzona']=='on')?1:0;
      $options['nodelimit'] = $_POST['wp_guifi_nodelimit'];
      $options['supernode'] = ($_POST['wp_guifi_supernode']=='on')?1:0;;
      $options['cache'] = "";
      update_option('widget_wp_guifi',$options);
    }

    // Be sure you format your options to be valid HTML attributes.
    $nodeid = htmlspecialchars($options['nodeid'], ENT_QUOTES);
    $title = urldecode(htmlspecialchars($options['title'], ENT_QUOTES));
    $filterStatus = $options['status'];
    $orderBy = $options['orderby'];
    $cacheTime  = $options['cachetime'];
    $typeInfo = $options['typeinfo'];
    $resumzona = $options['resumzona'];
    $nodeLimit = $options['nodelimit'];
    $mostrarSuperNodes = $options['supernode'];


    // Here is our little form segment. Notice that we don't need a
    // complete form. This will be embedded into the existing form.
    $logoGuifi = get_option('siteurl') . '/wp-content'.'/plugins/'.plugin_basename(dirname(__FILE__)) ."/guifi.net_logo.gif";
    echo '<p style="height:76px;font-size:large;font-weight:bold;"><a href="http://guifi.net"><img src="'. $logoGuifi .'" style="float:left;margin-right:15px;" /></a>La xarxa Oberta<br />Lliure i Neutral</p>';
    echo '<p style="text-align:right;clear:left;"><label for="wp_guifi_title">' . __('Titol Widget:') . ' <input style="width: 200px;" id="wp_guifi_title" name="wp_guifi_title" type="text" value="'.$title.'" /></label></p>';
    echo '<p style="text-align:right;"><label for="wp_guifi_nodeid">' . __('Id de Zona:') . ' <input style="width: 200px;" id="wp_guifi_nodeid" name="wp_guifi_nodeid" type="text" value="'.$nodeid.'" /></label></p>';
    echo '<p style="text-align:right;"><label for="wp_guifi_cachetime">' . __('Actualitzacio en segons:').'<input style="width: 200px;" id="wp_guifi_cachetime" name="wp_guifi_cachetime" type="text" value="'.$cacheTime.'" /></label></p>';
    echo '<p style="text-align:right;"><label for="wp_guifi_nodelimit">' . __('Limitar llistat:').'<input style="width: 200px;" id="wp_guifi_cachetime" name="wp_guifi_nodelimit" type="text" value="'.$nodeLimit.'" /></label></p>';
    echo '<p style="text-align:right;"><label for="wp_guifi_filterstatus">' . __('Filtrar Status:') . '<select style="width: 200px;" id="wp_guifi_filterstatus" name="wp_guifi_filterstatus">';
    foreach ($nodeStatusList as $key=>$status) {
      if ($filterStatus==$key) {
        $selected = " selected='selected'";
      } else {
        $selected = '';
      }
      echo "<option value='$key'$selected>$status</option>";
    }
    echo '</select></label></p>';

    echo '<p style="text-align:right;"><label for="wp_guifi_orderby">' . __('Ordenar per:') . '<select style="width: 200px;" id="wp_guifi_orderby" name="wp_guifi_orderby">';
    foreach ($orderByList as $key=>$status) {
      if ($orderBy==$key) {
        $selected = " selected='selected'";
      } else {
        $selected = '';
      }
      echo "<option value='$key'$selected>$status</option>";
    }
    echo '</select></label></p>';


    $radio0 = '';$radio1 = '';
    if ($options['typeinfo']==0){
      $radio0 = " checked='checked'";
    } else {
      $radio1 = " checked='checked'";
    }

    $resum = '';
    if ($resumzona==1){
      $resum =  " checked='checked'";
    }
    $sNode = '';
    if ($mostrarSuperNodes==1){
      $sNode =  " checked='checked'";
    }

    echo '<p style="text-align:right;"><label for="wp_guifi_typeinfo1">' . __('Mostrar Status:');
    echo "<input type='radio' name='wp_guifi_typeinfo' id='wp_guifi_typeinfo1' value='0' $radio0/>&nbsp;";
    echo '<label for="wp_guifi_typeinfo2">' . __('Mostrar Connectivitat:');
    echo "<input type='radio' name='wp_guifi_typeinfo' id='wp_guifi_typeinfo2' value='1' $radio1/></p>";

    echo '<p style="text-align:right;"><label for="wp_guifi_resumzona">' . __('Mostrar taula resum de zona:');
    echo "&nbsp;<input type='checkbox' name='wp_guifi_resumzona' id='wp_guifi_resumzona' $resum/></p>";

    echo '<p style="text-align:right;"><label for="wp_guifi_supernode">' . __('Mostrar només Supernodes:');
    echo "&nbsp;<input type='checkbox' name='wp_guifi_supernode' id='wp_guifi_supernode' $sNode/></p>";

    echo '<input type="hidden" id="wp_guifisubmit" name="wp_guifisubmit" value="1" />';
  }
  // This registers our optional widget control form. Because of this
  // our widget will have a button that reveals a 300x100 pixel form.
  register_widget_control(array('WP-Guifi', 'widgets'), 'widget_wp_guifi_control', 350, 325);


  // Generació del codi HTML amb lallista de nodes segons les opcions escollides
  function widget_wp_guifi($args) {
    global $nodeStatusList, $orderByList, $orderBySelect;

    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);

    // Each widget can store its own options. We keep strings here.
    $options = get_option('widget_wp_guifi');
    $nodeid = urlencode($options['nodeid']);
    $title = urlencode($options['title']);
    $cachetime = $options['cachetime'];
    $lastcheck = $options['lastcheck'];
    $filterStatus = $options['status'];
    $orderBySelect = $options['orderby'];
    $resumzona = $options['resumzona'];
    $nodelimit = $options['nodelimit'];
    $$mostrarSuperNodes = $options['supernode'];
    $unit = $options['unit'];
    $unit = ($unit == "s" || $unit == "m") ? $unit : "s";

    if(time() - $cachetime < $lastcheck AND $options['cache'] != "") {
      $widgettitle = ($options['title'] != "") ? $before_title.$options['title'].$options['nodetitle'].$after_title : "";
      echo $before_widget.$widgettitle.$options['cache'].$after_widget;
    }
    else {
      $uri = 'http://guifi.net/guifi/cnml/'. $nodeid .'/nodes';
      $data = file_get_contents($uri);


      if($data != "") {
        $dom = new DomDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($data);
        $xpath = new DOMXPath($dom);
        $zona = $xpath->query("/cnml/network/zone");
        if (!is_null($zona)) {
          $parentZoneId = $zona->item(0)->getAttribute('parent_id');
          $graphServerId = $zona->item(0)->getAttribute('graph_server');
          $nodeCount =  $zona->item(0)->getAttribute('zone_nodes');
          $nodeTitle =  $zona->item(0)->getAttribute('title');
          $widgettitle = ($options['title'] != "") ? $before_title.$options['title'].$nodeTitle.$after_title : "";

          $graphServerURI = 'http://guifi.net/node/'. $graphServerId ;
          $graphServerPage = file_get_contents($graphServerURI);
          $dom2 = new DomDocument;
          $dom2->preserveWhiteSpace = false;
          $dom2->loadHTML($graphServerPage);
          $xpath2 = new DOMXPath($dom2);
          $xpath2->registerNamespace('html','http://www.w3.org/1999/xhtml');
          $graphServerPageCells = $xpath2->query('//div[@id="node-'.$graphServerId.'"]//table//tr[4]/td[2]/a');

          if (!is_null($graphServerPageCells)) {
              $graphServerURL  =  $graphServerPageCells->item(0)->nodeValue;
              // comprovem la versio del servidor de grafiques
              if (strpos($graphServerURL,"graphs.php")==0){
                $graphServerURL .= "/index.php?call=availability";
              }  else  {
                $graphServerURL .= "?";
              }
           }
        }

        $xpathQuery = "/cnml/network/zone/node";
        //if ($superNode) $xpathQuery .= "[@devices>1][@access_points>1]";
        //if ($nodelimit) $xpathQuery .= "[position()<=$nodelimit]";

        $elements = $xpath->query($xpathQuery);
        $totalItems = $elements->length;
        // com qeu no puc ordenar un DOMNodeList ho poso en un array per ordenar-ho despres
        foreach ($elements as $element) {
          $llista[] = $element;
        }
        // endreça l'array segons el criteri de la variabla global $orderBySelect
        $elementsSorted = usort($llista, 'compareNodes' );
        $totalItems = sizeof($llista);

        if (!is_null($elements)) {

          // creem un array per acumular els contadors dels tipus de nodes
          if ($resumzona) {
            $infoResum = array();
              $infoResum['All'] = $totalItems;
          }
          $count = 0;

          foreach ($llista as $element) {

            // fora del bucle de comprovacio de estat per acumular els totals
            if ($resumzona){
              $infoResum[$status] += 1;
            }

            // inicialitzacio de variables del trasto
            $imgPercent = null;
            $enllacos = null;
            $nodeId = $element->getAttribute('id');
            $nodeName = $element->getAttribute('title');
            $status = $element->getAttribute('status');
            $devices = $element->getAttribute('devices');
            $accesPoints = $element->getAttribute('access_points');
            $esSupernode = (($accesPoints<=1)&&($devices<2))?false:true;
            // recollida de coordenades
            $lat = $element->getAttribute('lat');
            $lon = $element->getAttribute('lon');

            // si nomes volem veure supernodes i nomes te un AP o menys
            if ($mostrarSuperNodes && !$esSupernode) {
              // surt del foreach sense pintar res i segueix amb el seguent
              continue;
            }

            // comprovacio de filtres
            if (
                (($nodelimit)>0 && ($count<=$nodelimit-1))&&
                (
                ($filterStatus==0)     // si no hi ha filtre, passen tots
                ||($status==$nodeStatusList[$filterStatus]))
                ) {
              $deviceServerURI = $uri = 'http://guifi.net/guifi/cnml/'. $nodeId . '/node/';
              $deviceServerPage = file_get_contents($deviceServerURI);
              $dom3 = new DomDocument;
              $dom3->preserveWhiteSpace = false;
              $dom3->loadXML($deviceServerPage);
              $xpath2 = new DOMXPath($dom3);
              $deviceServerNode = $xpath2->query('//device[@type="radio"]');

              if (!is_null($deviceServerNode)) {
                //$deviceId = $deviceServerNode->item(0)->nodeValue;
                foreach ($deviceServerNode as $element) {
                  if ($element->getAttribute('id')) $imgPercent[] = $graphServerURL . "device=". $element->getAttribute('id') ."&format=short&type=availability";
                  if ($element->getAttribute('title')) $enllacos[] = $element->getAttribute('title');
                }
              }
              $urlNodeGuifi .= "<tr>";
              $urlNodeGuifi .= "<td><a href='http://guifi.net/node/$nodeId'>$nodeName</a></td>\n";
              if (($options['typeinfo']==0)||($status!="Working")) {
                  $urlNodeGuifi .= "<td style='width:80px;background-image:none;background-color:". getStatusColor($status) .";text-align:center;margin:0;padding:0;'>&nbsp;";
                  $urlNodeGuifi .= $status;
                  $urlNodeGuifi .= "&nbsp;</td>\n";

              }
              else {
                $urlNodeGuifi .= "<td style='width:80px;background-image:none;background-color:". getStatusColor($status) .";text-align:center;margin:0;padding:0'>&nbsp;";
                $urlNodeGuifi .= $status;
                $urlNodeGuifi .= "&nbsp;</td>\n";
                if ($enllacos) {
                  foreach ($enllacos as $key=>$status) {
                    $urlNodeGuifi .= "</tr><tr>\n";
                    $urlNodeGuifi .= "<td style='font-size:x-small;background:none;margin:0;padding:0;text-align:left;'>";
                    $urlNodeGuifi .= $status;
                    $urlNodeGuifi .= "</td>\n";
                    $urlNodeGuifi .= "<td style='width:80px;background-image:none;text-align:center;margin:0;padding:0;text-align:center;vertical-align:top;'>\n";
                    $urlNodeGuifi .="<img src='$imgPercent[$key]' width='77' alt=''/>";
                    $urlNodeGuifi .= "</td>\n";
                  }
                }
                $urlNodeGuifi .= "</tr>\n";
              }
              $count++;
            }
          }
        }
      }

      //$content = '<div class="textwidget wpguifi"><ul style="list-style-type:none;">'. $urlNodeGuifi . "</ul></div>\n";
      $content = '<div class="textwidget wpguifi"><table style="table-layout: fixed;width:100%;margin:0;padding:0;">'. $urlNodeGuifi . "</table></div>\n";
      if ($resumzona){
        $infoResum[$status] += 1;
        //$resumStr = '<div class="textwidget wpguifi" style="padding:10px 0 0 0;clear:both;"><ul style="list-style-type:none;">';
        $resumStr = '<div class="textwidget wpguifi" style="padding:10px 0 0 0;clear:both;"><table style="table-layout: fixed;width:100%;margin:0;padding:0;">';
          foreach ($nodeStatusList as $key=>$status) {
            $resumStr .= "<tr>";
            $resumStr .= "<td style='background:none;padding-left:0;'>$status</td>\n";
            $resumStr .= "<td style='width:80px;text-align:center;background:none;background-color:". getStatusColor($status)."'>". ($infoResum[$status]?$infoResum[$status]:"0") . "</td>\n";
            $resumStr .= "</tr>";
          }
        $resumStr .= '</table></div><br style="clear:left" />';
        $content = $content  . $resumStr;
      }
      $options['cache'] = $content;
      $options['lastcheck'] = time();
      $options['nodetitle'] = $nodeTitle;
      update_option('widget_wp_guifi',$options);

      echo $before_widget;
      echo $widgettitle;
      echo $content;
      echo $after_widget;

    }
  }
  register_sidebar_widget(array('WP-Guifi', 'widgets'), 'widget_wp_guifi');

}

function wp_guifi_menu() {
//  add_options_page('wp-Guifi Options', 'wp-Guifi', 10, __FILE__, 'wp_guifi_menu_options');
}


// wp_guifi_menu_options() displays the page content for the Test Options submenu
function wp_guifi_menu_options() {

    // variables for the field and option names
    $opt_name = 'wp_guifi_nodeid';
    $hidden_field_name = 'wp_guifi_hidden';
    $data_field_name = 'wp_guifi_nodeid';

    // Read in existing option value from database
    $opt_val = get_option( $opt_name );

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read their posted value
        $opt_val = $_POST[ $data_field_name ];

        // Save the posted value in the database
        update_option( $opt_name, $opt_val );

        // Put an options updated message on the screen

?>
<div class="updated"><p><strong><?php _e('Options saved.', 'wp_guifi_trans_domain' ); ?></strong></p></div>
<?php

    }

    // Now display the options editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2>" . __( 'WP-Guifi Plugin Options', 'wp_guifi_trans_domain' ) . "</h2>";

    // options form

    ?>

<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">

<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<p>
<a href="http://guifi.net" alt="Guifi.net, xarxa lliure - free network"><img src="<?php echo get_option('siteurl') . '/wp-content'.'/plugins/'.plugin_basename(dirname(__FILE__)) ?>/guifi.net_logo.gif" align="right" /></a>
WP-guifi és un plugin que ens mostra una llista dels nodes operatius de una zona de guifi.net, una de les xarxes lliures més grans del món. WP-guifi ha estat programat per <a href="http://albertsarle.com/plugins/wp-guifi">Albert Sarlé</a>
<?php _e("Node :", 'wp_guifi_trans_domain' ); ?>
<input type="text" name="<?php echo $data_field_name; ?>" value="<?php echo $opt_val; ?>" size="20">
</p><hr />

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Update Options', 'wp_guifi_trans_domain' ) ?>" />
</p>

</form>
</div>

<?php

}

function set_plugin_meta($links, $file) {


  $plugin = plugin_basename(__FILE__);
  // create link
  if ($file==$plugin) {
    return array_merge(
      $links,
      array( sprintf( '<a href="widgets.php">%s</a>', __('Widget Settings') ) )
    );
  }

  return $links;
}

add_filter( 'plugin_row_meta', 'set_plugin_meta', 10, 2 );

add_action('admin_menu', 'wp_guifi_menu');
add_action('widgets_init', 'widget_wp_guifi_init');
?>