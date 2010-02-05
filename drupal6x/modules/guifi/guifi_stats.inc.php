<?php
/*
 * Created on 1/08/2009 by Eduard
 *
 * functions for statistics graphs
 */
function guifi_stats($action,$statsid = 0) {
 if (!is_numeric($statsid))
    return;
  
  switch ($action) {
  case 'chart':
    guifi_stats_chart();
    return;
    break;
  case 'chart01': //growth_chart
    guifi_stats_chart01();
    return;
    break;
  case 'chart02':  //annualincrement
    guifi_stats_chart02();
    return;
    break;
  case 'chart03':  //monthlyaverage':
    guifi_stats_chart03();
    return;
    break;
  case 'chart04': //lastyear':
    guifi_stats_chart04();
    return;
    break;
  case 'chart05': //':
    guifi_stats_chart05($statsid);
    return;
    break;
  case 'feeds': //total working nodes http://guifi.net/guifi/stats/nodes/0
    $ret=guifi_stats_feeds($statsid);
    echo $ret;
    return;
    break;
  }
}

/*
 * growthmap
 */
function guifi_stats_growthmap() {
  $output = "";
  if (guifi_gmap_key()) {
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_gmap_growthmap.js','module');
    $output .=  '<form>' .
        '<input type=hidden value='.base_path().drupal_get_path('module','guifi').'/js/'.' id=edit-jspath />' .
        '<input type=hidden value='.variable_get('guifi_wms_service','').' id=guifi-wms />' .
        '</form>';
    $output .= drupal_get_form('guifi_growthmap_map_form');
    $output .= '<div id="map" style="width: 800px; height: 600px; margin:5px;"></div>';
    $output .= '<div id="footmap" style="margin:5px;">'.t('Mode:').'</div>';
    $output .= '<canvas id="testcanvas" width="1px" height="1px"></canvas>';
    if(isset($_GET['id'])){
      $output .='<form><input type=hidden value=1 id=maprun /></form>';
    }else{
      $output .='<form><input type=hidden value=0 id=maprun /></form>';
    }
  }

  guifi_log(GUIFILOG_TRACE,'growthmap',1);

  return $output;
}
 
function guifi_growthmap_map_form($form_state) { 
  $form['#action'] = '';
  $form['formmap2'] = array(
    '#type' => 'textfield',
    '#name' => 'formmap2',
    '#default_value' => '',
    '#size' => 63,
    '#attributes' => array('style'=>'margin:5px;text-align:center;font-size:24px'),
    '#prefix' => '<div style="align:center">',
    '#suffix' => '</div>'
  );
  return $form;
}

/*
 * nodes statistics
 */
function guifi_stats_nodes() {
  drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_stats_nodes.js','module');
  $output = "";
  if(isset($_GET['id'])){
    $vid=$_GET['id'];
    switch($vid){
    case ($vid=='5'):
      if(isset($_GET['sid'])){
        $v=$_GET['sid'];
      }else{
        $v='12';
      }
      $output .= '<div id="plot" style="width: 500px; border-style:none; margin:5px;"><img src="/guifi/stats/chart0'.$vid.'/'.$v.'"></div>';
      break;
    case ($vid>='1' && $vid<=9):
      $output .= '<div id="plot" style="width: 500px; border-style:none; margin:5px;"><img src="/guifi/stats/chart0'.$vid.'/0"></div>';
      break;
    default:
      $vid='0';
      break;
    }
  }else{
    $vid='0';
  }
  
  if($vid=='0'){
    $output .= '<div id="plot" style="width: 500px; border-style:none; float:right; margin:5px;"></div>';
    $output .= '<div id="menu" style="width: 230px; margin:5px;">';
    $output .= '<a href="javascript:guifi_stats_chart01()">'.t("1 Growth chart").'</a>';
    $output .= '<br><a href="javascript:guifi_stats_chart02()">'.t("2 Annual increment").'</a>';
    $output .= '<br><a href="javascript:guifi_stats_chart03()">'.t("3 Monthly average").'</a>';
    $output .= '<br><a href="javascript:guifi_stats_chart04()">'.t("4 Last year").'</a>';
    $output .= '<br><a href="javascript:guifi_stats_chart05(3)">'.t("5.3 Nodes per month, avr. 3m.").'</a>';
    $output .= '<br><a href="javascript:guifi_stats_chart05(6)">'.t("5.6 Nodes per month, avr. 6m.").'</a>';
    $output .= '<br><a href="javascript:guifi_stats_chart05(12)">'.t("5.12 Nodes per month, avr. 12m.").'</a>';
    $output .= '</div>';
    $output .= '<div style="height:300px">&nbsp;</div>';
    $output .= '<div style="width:700px;">';
    $output .= t('link:').' http://guifi.net/guifi/menu/stats/nodes?id=N';
    $output .= '<br />'.t('link:').' http://guifi.net/guifi/menu/stats/nodes?id=N&sid=M';
    $output .= '<br />'.t('link:').' &lt;img src="http://guifi.net/guifi/stats/chart?id=N"&gt;';
    $output .= '<br />'.t('link:').' &lt;img src="http://guifi.net/guifi/stats/chart?id=N&sid=M"&gt;';
    $output .= '</div>';
  }    
  guifi_log(GUIFILOG_TRACE,'stats_nodes',1);

  return $output;
}
function guifi_stats_chart() {
  if(isset($_GET['id'])){
    $vid=$_GET['id'];
    switch($vid){
    case '1':
      guifi_stats_chart01();
      break;
    case '2':
      guifi_stats_chart02();
      break;
    case '3':
      guifi_stats_chart03();
      break;
    case '4':
      guifi_stats_chart04();
      break;
    case '5':
      if(isset($_GET['sid'])){
        $v=$_GET['sid'];
      }else{
        $v='12';
      }
      guifi_stats_chart05($v);
      break;
    default:
      guifi_stats_chart01();
      break;
    }
  }
}
//create gif working nodes
function guifi_stats_chart01(){ //growth_chart
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $result=db_query("select COUNT(*) as num, MONTH(FROM_UNIXTIME(timestamp_created)) as mes, YEAR(FROM_UNIXTIME(timestamp_created)) as ano from {guifi_location} where status_flag='Working' GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)),MONTH(FROM_UNIXTIME(timestamp_created)) ");
    $inicial=5;
    $nreg=$inicial;
    $tot=0;
    $ano=2004;
    $mes=5;
    $items=2004;
    $label="a";
    $today=getdate();
	while ($record=db_fetch_object($result)){
      if($record->ano>=2004){
        if($mes==12){
          $mes=1;
          $ano++;
        }else{
          $mes++;
        }
        if($ano==$today[year] && $mes>=$today[mon]){
          if($mes==1){
            $mes=12;
            $ano--;
          }else{
            $mes--;
          }
          break;
        }
        while ($ano<$record->ano || $mes<$record->mes){
          $nreg++;
          if($mes==6){
            $label=$ano;
          }else{
            $label='';
          }
          $data[]=array("$label",$nreg,$tot);
          if($mes==12){
            $mes=1;
            $ano++;
          }else{
            $mes++;
          }
        }
        $tot+=$record->num;
        $nreg++;
        if($mes==6){
          $label=$ano;
        }else{
          $label='';
        }
        $data[]=array("$label",$nreg,$tot);
      }else{
         $tot+=$record->num;
      };
	};
    while($mes<12){
      $nreg++;
      $mes++;
      if($mes==6){
        $label=$ano;
      }else{
        $label='';
      }
      $data[]=array("$label",$nreg,"");
    }
    $items=($ano-$items+1)*12;
    $shapes = array( 'none');
    $plot = new PHPlot(500,400);
    $plot->SetPlotAreaWorld(0, 0,$items,NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("data-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("linepoints"); 
    $plot->SetYTickIncrement(1000);
    $plot->SetXTickIncrement(12);
    $plot->SetSkipBottomTick(true);
    $plot->SetSkipLeftTick(true);
    $plot->SetXAxisPosition(0);
    $plot->SetPointShapes($shapes); 
    $plot->SetPointSizes(10);
    $plot->SetTickLength(3);
    $plot->SetDrawXGrid(true);
    $plot->SetTickColor('grey');
    $plot->SetTitle(t('Growth chart'));
    $plot->SetXTitle(t('Years'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetDrawXDataLabelLines(false);
    $plot->SetXLabelAngle(0);
    $plot->SetXLabelType('custom', 'guifi_stats_chart01_LabelFormat');
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetXTickLabelPos('none');
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetIsInline(true);
    $plot->DrawGraph();
}
function guifi_stats_chart01_LabelFormat($value){
   return($value);
}
//create gif annual increment
function guifi_stats_chart02(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $result=db_query("select COUNT(*) as num, YEAR(FROM_UNIXTIME(timestamp_created)) as ano from {guifi_location} where status_flag='Working' GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)) ");
    $tot=0;
	while ($record=db_fetch_object($result)){
      if($record->ano>=2004){
         //$nreg++;
         $tot+=$record->num;
         $data[]=array("$record->ano",$tot);
         $tot=0;
      }else{
         $tot+=$record->num;
      };
	};
    $shapes = array( 'none');
    $plot = new PHPlot(500,400);
    $plot->SetPlotAreaWorld(0, 0,NULL,NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("text-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("bars"); 
    $plot->SetYTickIncrement(500);
    $plot->SetSkipBottomTick(true);
    $plot->SetSkipLeftTick(true);
    $plot->SetTickLength(0);
    $plot->SetXTickPos('none');
    $plot->SetYDataLabelPos('plotin');
    $plot->SetTickColor('grey');
    $plot->SetTitle(t('Annual increment'));
    $plot->SetXTitle(t('Years'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetXLabelAngle(0);
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(true);
    $plot->DrawGraph();
}

//create gif monthly average
function guifi_stats_chart03(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $result=db_query("select COUNT(*) as num, month(FROM_UNIXTIME(timestamp_created)) as mes from {guifi_location} where status_flag='Working' GROUP BY MONTH(FROM_UNIXTIME(timestamp_created)) ");
    $tot=0;
    $valor=0;
	while ($record=db_fetch_object($result)){
        $tot+=$record->num;
        $data[]=array("$record->mes",$record->num);
    };
	foreach ($data as &$dat){
        $dat[1]=$dat[1]*100/$tot;
	};
    $shapes = array( 'none');
    $plot = new PHPlot(500,400);
    $plot->SetPlotAreaWorld(0, 0,NULL,NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("text-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("bars"); 
    //$plot->SetYTickIncrement(10);
    $plot->SetSkipBottomTick(true);
    $plot->SetSkipLeftTick(true);
    $plot->SetTickLength(0);
    $plot->SetXTickPos('none');
    $plot->SetYTickPos('none');
    $plot->SetDrawYGrid(false);
    $plot->SetYTickLabelPos('none');
    $plot->SetYDataLabelPos('plotin');
    $plot->SetTickColor('grey');
    $plot->SetTitle(t('Monthly average'));
    $plot->SetXTitle(t('Months'));
    $plot->SetYTitle(t('% Working nodes'));
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetXLabelAngle(0);
    $plot->SetYLabelType('data', 2);
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(true);
    $plot->DrawGraph();
}

//create gif last year
function guifi_stats_chart04(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $today=getdate();
    $year=$today[year];
    $month=$today[mon];
    $month=$month-12;
    $n=0;
    $tot=0;
    if($month<1){
      $year=$year-1;
      $month=12+$month;
    }
    $datemin=mktime(0,0,0,$month,1,$year);
    $result=db_query("select COUNT(*) as num, max(timestamp_created) as fecha, max(month(FROM_UNIXTIME(timestamp_created))) as mes,max(year(FROM_UNIXTIME(timestamp_created))) as year from {guifi_location}
      where (timestamp_created >= ".$datemin." and status_flag='Working')  
      GROUP BY Year(FROM_UNIXTIME(timestamp_created)), month(FROM_UNIXTIME(timestamp_created))");
    while ($record=db_fetch_object($result)){
      $data[]=array("$record->mes".'/'.substr("$record->year",2,2),$record->num);
      if($record->mes!=$today[mon] || $record->year!=$today[year]){
        $n++;
        $tot=$tot+$record->num;
      }
    };
    if($n>0){
      $tot=$tot/$n;
    }
    $shapes = array( 'none');
    $plot = new PHPlot(500,400);
    $plot->SetPlotAreaWorld(0, 0,NULL,NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("text-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("bars"); 
    $plot->SetYTickIncrement($tot);
    $plot->SetSkipBottomTick(true);
    $plot->SetSkipLeftTick(true);
    $plot->SetTickLength(0);
    //$plot->SetXTickPos('none');
    $plot->SetYDataLabelPos('plotin');
    $plot->SetYLabelType('data', 0);
    $plot->SetTickColor('grey');
    $plot->SetTitle(t('Last year'));
    $plot->SetXTitle(t('Months'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetXLabelAngle(0);
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetIsInline(true);
    $plot->DrawGraph();
}
//Nodes per month, average of 6 months
function guifi_stats_chart05($nmonths){ 
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $result=db_query("select COUNT(*) as num, MONTH(FROM_UNIXTIME(timestamp_created)) as mes, YEAR(FROM_UNIXTIME(timestamp_created)) as ano from {guifi_location} where status_flag='Working' GROUP BY YEAR(FROM_UNIXTIME(timestamp_created)),MONTH(FROM_UNIXTIME(timestamp_created)) ");
    $inicial=5;
    $nreg=$inicial;
    $tot=0;
    $ano=2004;
    $mes=5;
    $items=2004;
    $label="a";
    $n=0;
    $med=0;
    $datos=array(0,0,0,0,0,0,0,0,0,0,0,0,0);
    $today=getdate();
    if($nmonths==0) $nmonths=12;
	while ($record=db_fetch_object($result)){
      if($record->ano>=2004){
        if($mes==12){
          $mes=1;
          $ano++;
        }else{
          $mes++;
        }
        if($ano==$today[year] && $mes>=$today[mon]){
          if($mes==1){
            $mes=12;
            $ano--;
          }else{
            $mes--;
          }
          break;
        }
        while ($ano<$record->ano || $mes<$record->mes){
          $nreg++;
          if($mes==6){
            $label=$ano;
          }else{
            $label='';
          }
          if($n==0){
            $tot+=$record->num;
          }else{
            $tot=$record->num;
          }
          $tot=fmediacalc($tot,$datos,$n,$nmonths);
          $data[]=array("$label",$nreg,$tot);
          if($mes==12){
            $mes=1;
            $ano++;
          }else{
            $mes++;
          }
        }
        $tot+=$record->num;
        $nreg++;
        if($mes==6){
          $label=$ano;
        }else{
          $label='';
        }
        if($n==0){
          $tot+=$record->num;
        }else{
          $tot=$record->num;
        }
        $tot=fmediacalc($tot,$datos,$n,$nmonths);
        $data[]=array("$label",$nreg,$tot);
      }else{
         $tot+=$record->num;
      };
	};
    while($mes<12){
      $nreg++;
      $mes++;
      if($mes==6){
        $label=$ano;
      }else{
        $label='';
      }
      $data[]=array("$label",$nreg,"");
    }
    $items=($ano-$items+1)*12;
    $shapes = array( 'none');
    $plot = new PHPlot(500,400);
    $plot->SetPlotAreaWorld(0, 0,$items,NULL);
    $plot->SetFileFormat('png');
    $plot->SetDataType("data-data");
    $plot->SetDataValues($data);
    $plot->SetPlotType("linepoints"); 
    $plot->SetYTickIncrement(50);
    $plot->SetXTickIncrement(12);
    $plot->SetSkipBottomTick(true);
    $plot->SetSkipLeftTick(true);
    $plot->SetXAxisPosition(0);
    $plot->SetPointShapes($shapes); 
    $plot->SetPointSizes(10);
    $plot->SetTickLength(3);
    $plot->SetDrawXGrid(true);
    $plot->SetTickColor('grey');
    $plot->SetTitle(t('Nodes per month, '."$nmonths".' months average'));
    $plot->SetXTitle(t('Years'));
    $plot->SetYTitle(t('Working nodes'));
    $plot->SetDrawXDataLabelLines(false);
    $plot->SetXLabelAngle(0);
    $plot->SetXLabelType('custom', 'guifi_stats_chart05_LabelFormat');
    $plot->SetGridColor('red');
    $plot->SetPlotBorderType('left');
    $plot->SetDataColors(array('orange'));
    $plot->SetTextColor('DimGrey');
    $plot->SetTitleColor('DimGrey');
    $plot->SetLightGridColor('grey');
    $plot->SetBackgroundColor('white');
    $plot->SetTransparentColor('white');
    $plot->SetXTickLabelPos('none');
    $plot->SetXDataLabelPos('plotdown');
    $plot->SetIsInline(true);
    $plot->DrawGraph();
}
function guifi_stats_chart05_LabelFormat($value){
   return($value);
}
function fmediacalc($tot,&$datos,&$n,$nmonths){
  $v=0;
  $i=0;
  if($n>=$nmonths){
    $n=1;
  }else{
    $n++;
  }
  $datos[$n]=$tot;
  for($i=1;$i<=$nmonths;$i++){
    $v=$v+$datos[$i];
  }
  //return($datos[$n]);
  return($v/$nmonths);
}

//stats Nodes
function guifi_stats_feeds($pnum){
  $output="";
  switch ($pnum) {
  case 0: //total nodes
    if(isset($_GET['tex'])){
      $vt=$_GET['tex'];
    }else{
      $vt="%d% %n% - ".t("working nodes");
    }
    $output ='<?xml version="1.0" encoding="utf-8"?>';
    //$output .= '<rss version="2.0" xml:base="http://guifi.net"  xmlns:dc="http://purl.org/dc/elements/1.1/">';
    $output .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
    $output .= '<channel>';
    $output .= '<title>'.utf8_encode('guifi.net - estad�sticas').'</title>';
    $output .= '<link>http://guifi.net/guifi/stats/feeds/0</link>';
    $output .= '<atom:link href="http://guifi.net/guifi/stats/feeds/0" rel="self" type="application/rss+xml" />';
    $output .= '<description>'.utf8_encode('estad�sticas guifi.net').'</description>';
    $result=db_query("select COUNT(*) as num from {guifi_location} where status_flag='Working'");
    if ($record=db_fetch_object($result)){
      $output .= '<item>';
      $output .= '<guid isPermaLink="false">http://guifi.net/guifi/menu/stats/nodes?dat='.date("d/m/Y",time()).'</guid>';
      $output .= '<description>';
      $vt = str_replace("%d%", date("d/m/Y",time()), $vt);
      $vt = str_replace("%n%", $record->num, $vt);
      $output .= $vt;
      //$output .= date("d/m/Y",time()).' = nodos activos = '.$record->num.' = fully working nodes!';
      $output .= '</description>';
      $output .= '</item>';
    };
    $output .= '</channel>';
    $output .= '</rss>';
    break;
  }
  return($output);
}

?>