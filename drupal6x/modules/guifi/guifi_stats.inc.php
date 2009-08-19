<?php
/*
 * Created on 1/08/2009 by Eduard
 *
 * functions for statistics graphs
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
?>
