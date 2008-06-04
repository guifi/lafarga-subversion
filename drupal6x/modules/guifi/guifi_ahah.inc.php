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

function guifi_ahah_add_wds(){
  ob_start();
  print_r($_POST);
  $descr = ob_get_clean();
  ob_end_flush();
  $form = array(
    '#type' => 'select',
    '#title' => 'You selected that because...',
    '#options' => array(
      '1' => 'drugs',
      '2' => 'I do what I want.',
      '3' => "I'm feeling lucky..."
    ),
    '#description'=>$descr,
  );
  // ahah_render is where the magic happens. 
  // 'the value of this field will show up as $form_value['user_problem'] 
  $output = guifi_ahah_render_newfields($form, 'user_problem');
  print drupal_to_js(array('data' => $output, 'status' => true));
  exit();
}

function guifi_ahah_select_channel($rid){
 
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  $protocol = $_POST['radios'][$rid]['protocol'];
  $curr_channel = $_POST['radios'][$rid]['channel'];
  
  if ($cache) {
    $form = $cache->data;

    $form['r']['radios'][$rid]['channel'] = 
        guifi_radio_channel_field(
          $rid,
          $curr_channel,
          $protocol);
          
    cache_set($cid, $form, 'cache_form', $cache->expire);
    // Build and render the new select element, then return it in JSON format.
    $form_state = array();
    $form['#post'] = array();
    $form = form_builder($form['form_id']['#value'] , $form, $form_state);
    $output = drupal_render($form['r']['radios'][$rid]['channel']);
    drupal_json(array('status' => TRUE, 'data' => $output));
  } else {
    drupal_json(array('status' => FALSE, 'data' => ''));
  }
  exit;
}

function guifi_ahah_select_zone($fname) {
  $cid = 'form_'. $_POST['form_build_id'];
  $cache = cache_get($cid, 'cache_form');
  
  $zid = $_POST[$fname];
  
  if ($cache) {
    $form = $cache->data;

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
