<?php
/*
 * Created on 1/08/2009 by Eduard
 *
 * functions for statistics graphs
 */
function guifi_stats($action = '',$statsid) {
  if (!is_numeric($statsid))
    return;
  
  switch ($action) {
  case 'growthchart':
    guifi_stats_growth_chart();
    return;
    break;
  case 'annualincrement':
    guifi_stats_annualincrement_chart();
    return;
    break;
  case 'mensualaverage':
    guifi_stats_mensualaverage_chart();
    return;
    break;
  case 'lastyear':
    guifi_stats_lastyear_chart();
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
    $output = "";
    drupal_add_js(drupal_get_path('module', 'guifi').'/js/guifi_stats_nodes.js','module');
    $output .= '<div id="plot" style="width: 500px; border-style:none; float:right; margin:5px;"></div>';
    $output .= '<div id="menu" style="width: 200px; margin:5px;">';
    $output .= '<a href="javascript:growth_chart()">'.t("Growth chart").'</a>';
    $output .= '<br><a href="javascript:annualincrement_chart()">'.t("Annual increment").'</a>';
    $output .= '<br><a href="javascript:mensualaverage_chart()">'.t("Mensual average").'</a>';
    $output .= '<br><a href="javascript:lastyear_chart()">'.t("Last year").'</a>';
    $output .= '</div>';

  guifi_log(GUIFILOG_TRACE,'stats_nodes',1);

  return $output;
}
//create gif working nodes
function guifi_stats_growth_chart(){
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
    $plot->SetXLabelType('custom', 'guifi_stats_growth_chart_LabelFormat');
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
function guifi_stats_growth_chart_LabelFormat($value){
   return($value);
}
//create gif annual increment
function guifi_stats_annualincrement_chart(){
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

//create gif mensual average
function guifi_stats_mensualaverage_chart(){
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
    $plot->SetTitle(t('Mensual average'));
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
function guifi_stats_lastyear_chart(){
    include drupal_get_path('module','guifi').'/contrib/phplot/phplot.php';
    $today=getdate();
    $year=$today[year];
    $month=$today[mon];
    $month=$month-11;
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
?>
