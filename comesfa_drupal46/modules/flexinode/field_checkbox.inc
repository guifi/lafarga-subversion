<?php
// $Id: field_checkbox.inc,v 1.7 2004/07/08 21:01:38 JonBob Exp $

function flexinode_field_checkbox_name($field) {
  return t('checkbox');
}

function flexinode_field_checkbox_form($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  return form_checkbox(t($field->label), $fieldname, 1, isset($node->$fieldname) ? $node->$fieldname : $field->default_value, t($field->description), NULL, $field->required);
}

function flexinode_field_checkbox_db_select($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $fieldname .'.numeric_data AS '. $fieldname;
}

function flexinode_field_checkbox_db_sort_column($field) {
  return 'flexinode_'. $field->field_id .'.numeric_data';
}

function flexinode_field_checkbox_insert($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  db_query('INSERT INTO {flexinode_data} (nid, field_id, numeric_data) VALUES (%d, %d, %d)', $node->nid, $field->field_id, $node->$fieldname);
}

function flexinode_field_checkbox_format($field, $node, $brief = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $node->$fieldname ? t('Yes') : t('No');
}

function flexinode_field_checkbox_search_form($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  $options = array(-1 => '<'. t('Any') .'>', 0 => t('No'), 1 => t('Yes'));
  return form_select(t($field->label), $fieldname, -1, $options);
}

function flexinode_field_checkbox_search_clause($field, $edit) {
  $fieldname = 'flexinode_'. $field->field_id;
  if (isset($edit[$fieldname]) && $edit[$fieldname] != -1) {
    return $fieldname .'.numeric_data = '. $edit[$fieldname];
  }
}

function flexinode_field_checkbox_config($field, $edit) {
  return form_checkbox(t('Checked by default'), 'default_value');
}


/**
 * @addtogroup themeable
 * @{
 */

/**
 * Format a checkbox value for display in a node.
 *
 * @param field_id
 *   Which field is being displayed (useful when overriding this function
 *   if you want to style one particular field differently).
 * @param label
 *   The label for the field as displayed on the node form.
 * @param value
 *   The value that the user entered for the field.
 * @param formatted_value
 *   The value that the user entered for the field as pre-formatted by the module.
 */
function theme_flexinode_checkbox($field_id, $label, $value, $formatted_value) {
  $output = theme('form_element', $label, $formatted_value);
  $output = '<div class="flexinode-checkbox-'. $field_id .'">'. $output .'</div>';
  return $output;
}

/** @} End of addtogroup themeable */

?>