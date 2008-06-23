<?php
/*
 * Created on 01/06/2008
 *
 * Functions for Asynchrnous HTTP and HTML (AHAH) at some forms
 */
 
function guifi_ahah_render_newfields($fields, $name) {
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  // Add the new element to the stored form. Without adding the element to the
  // form, Drupal is not aware of this new elements existence and will not
  // process it. We retreive the cached form, add the element, and resave.
  $form = form_get_cache($form_build_id, $form_state);
  $form[$name] = $fields;
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );
  // Rebuild the form.
  $form = form_builder($_POST['form_id'], $form, $form_state);

  // Render the new output.
  $new_form = $form[$name];
  return drupal_render($new_form); 
}

function guifi_ahah_render_field($field){
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  
  if ($cache) {
    $form = $cache->data;

    // Validate the firmware.
    $form['replacedField'] = $field;
    cache_set($cid, $form, 'cache_form', $cache->expire);

    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['replacedField']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  }
  else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

function guifi_ahah_select_node(){
  $matches = array();
  
  $string = arg(3);
  
  $qry = db_query('SELECT ' .
                  '  CONCAT(l.id,"-",z.nick,", ",l.nick) str '. 
                  'FROM {guifi_location} l, {guifi_zone} z ' .
                  'WHERE l.zone_id=z.id ' .
                  '  AND ((CONCAT(l.id,"-",z.nick,", ",l.nick) LIKE "%'.
                       $string.'%")'.
                  '  OR (l.id like "%'.$string.'%"'.
                  '  OR l.nick like "%'.$string.'%"'.
                  '  OR z.nick like "%'.$string.'%"))'
                 );
  $c = 0;
  while (($value = db_fetch_array($qry)) and ($c < 50)) {
    $c++;
    $matches[$value['str']] = $value['str'];
  }
  print drupal_to_js($matches);
  exit();
}

function guifi_ahah_select_zone() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  
  $fname = arg(3);
  
  $zid = $_POST[$fname];
  
  if ($cache) {
    $form = $cache->data;

    // zid field
    $form[$fname] = 
        guifi_zone_select_field($zid,$fname);
          
    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form[$fname]);
    
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

function guifi_ahah_select_device() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  
  $action = arg(3);  
    
  if ($cache) {
    $form = $cache->data;

    $form['list-devices'] = 
        guifi_devices_select($_POST['filters'],$action);
          
    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['list-devices']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

function guifi_ahah_move_device() {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  
  $radio_id = arg(3);
  $node = explode('-',$_POST['movenode']);
  $orig_device_id = $_POST['id'];
  
  $qry = db_query('SELECT id, nick ' .
                  'FROM {guifi_devices} ' .
                  'WHERE nid=%d' .
                  ' AND id<>%d',
                  $node[0],$orig_device_id);
  
  $list = array();
  while ($value = db_fetch_array($qry)) {
    $list[$value['id']] = $value['nick']; 
  }
    
  if ($cache) {
    $form = $cache->data;

    $form['r'][$radio_id]['moveradio'] = array (
      '#type' => 'fieldset',
      '#collapsible' => false
    );
    if (count($list)) {
      $form['r'][$radio_id]['moveradio']['to_did'] = array(
        '#type'=>'select',
        '#parents'=> array('radios',$radio_id,'to_did'),        
        '#title'=>t('Move radio to device'),
        '#description'=>t('Select the device which you want to assign this radio.<br>' .
            'Note that the change will not take effect until the device has been saved.'),
        '#options'=>$list
      );
    } else {
      $form['r'][$radio_id]['moveradio']['msg'] = array(
        '#type'=>'item',
        '#title'=>t('No devices available'),
        '#description'=>t('Can\'t move this radio to another device ' .
            'since there are no other devices defined on this node.<br>' .
            'To move the radio to a device defined at another node, ' .
            'you should reassign the node of this device before proceeding.')
      );
      $form['r'][$radio_id]['moveradio']['to_id'] = array(
        '#type'=>'hidden',
        '#parents'=> array('radios',$radio_id,'to_did'),
        '#value'=>$orig_device_id,
      );      
    } 
          
    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['r'][$radio_id]['moveradio']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}


function guifi_ahah_select_firmware_by_model(){
  
  $cid = 'form_'. $_POST['form_build_id'];
//  $bid = $_POST['book']['bid'];
  $cache = cache_get($cid, 'cache_form');
  $mid = $_POST['variable']['model_id'];
  
  if ($cache) {
    $form = $cache->data;

    // Validate the firmware.
    if (isset($form['radio_settings']['variable']['model_id'])) {
      $form['radio_settings']['variable']['firmware'] = 
        guifi_radio_firmware_field($_POST['variable']['firmware'],
          $mid);
      cache_set($cid, $form, 'cache_form', $cache->expire);

      // Build and render the new select element, then return it in JSON format.
      $form_state = array();
      $form['#post'] = array();
      $form = form_builder($form['form_id']['#value'] , $form, $form_state);
      $output = drupal_render($form['radio_settings']['variable']['firmware']);
      drupal_json(array('status' => TRUE, 'data' => $output));
    }
    else {
      drupal_json(array('status' => FALSE, 'data' => ''));
    }
  }
  else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}


?>
