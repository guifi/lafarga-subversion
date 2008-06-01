<?php
/*
 * Created on 01/06/2008
 *
 * Functions for Asynchrnous HTTP and HTML (AHAH) at some forms
 */
 
 function guifi_ahah_render($fields, $name) {
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

function guifi_ahah_add_wds(){
  $form = array(
    '#type' => 'select',
    '#title' => 'You selected that because...',
    '#options' => array(
      '1' => 'drugs',
      '2' => 'I do what I want.',
      '3' => "I'm feeling lucky..."
    ),
    '#description'=>$_POST,
  );
  // ahah_render is where the magic happens. 
  // 'the value of this field will show up as $form_value['user_problem'] 
  $output = guifi_ahah_render($form, 'user_problem');
  print drupal_to_js(array('data' => $output, 'status' => true));
  exit();
}


?>
