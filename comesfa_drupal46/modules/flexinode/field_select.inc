<?php
// $Id: field_select.inc,v 1.9 2004/07/08 21:01:38 JonBob Exp $

function flexinode_field_select_name($field) {
  return t('dropdown menu');
}

function flexinode_field_select_form($field, &$node) {
  $fieldname = 'flexinode_'. $field->field_id;
  $options = array(0 => '<'. t('none') .'>');
  foreach ($field->options as $option) {
    $options[] = $option;
  }
  if ($field->required) {
    unset($options[0]);
  }
  return form_select(t($field->label), $fieldname, $node->$fieldname, $options, t($field->description), 0, 0, $field->required);
}

function flexinode_field_select_db_select($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $fieldname .'.numeric_data AS '. $fieldname;
}

function flexinode_field_select_db_sort_column($field) {
  return 'flexinode_'. $field->field_id .'.numeric_data';
}

function flexinode_field_select_insert($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  db_query('INSERT INTO {flexinode_data} (nid, field_id, numeric_data) VALUES (%d, %d, %d)', $node->nid, $field->field_id, $node->$fieldname);
}

function flexinode_field_select_format($field, $node, $brief = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $node->$fieldname ? $field->options[$node->$fieldname] : FALSE;
}

function flexinode_field_select_search_form($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  $options = array(-1 => '<'. t('Any') .'>', 0 => '<'. t('none') .'>');
  foreach ($field->options as $option) {
    $options[] = $option;
  }
  if ($field->required) {
    unset($options[0]);
  }
  return form_select(t($field->label), $fieldname, -1, $options);
}

function flexinode_field_select_search_clause($field, $edit) {
  $fieldname = 'flexinode_'. $field->field_id;
  if (isset($edit[$fieldname]) && $edit[$fieldname] != -1) {
    return $fieldname .'.numeric_data = '. $edit[$fieldname];
  }
}

function flexinode_field_select_config($field, $edit) {
  if ($edit['options']) {
    foreach ($edit['options'] as $option) {
      $option_fields .= form_textfield('', 'options][', $option, 60, 128);
    }
  }
  $option_fields .= form_textfield('', 'options][', '', 60, 128);
  $option_fields .= form_textfield('', 'options][', '', 60, 128);
  $option_fields .= form_textfield('', 'options][', '', 60, 128);
  $option_fields .= form_textfield('', 'options][', '', 60, 128);
  $option_fields .= form_submit(t('More'));

  return form_group(t('Options'), $option_fields, t('Options for the dropdown menu. For more fields, click "More".'));
}


/**
 * @addtogroup themeable
 * @{
 */

/**
 * Format a dropdown selection for display in a node.
 *
 * @param field_id
 *   Which field is being displayed (useful when overriding this function
 *   if you want to style one particular field differently).
 * @param label
 *   The label for the field as displayed on the node form.
 * @param value
 *   The value that the user entered for the field.
 * @param formatted_value
 *   The displayed label for the selected value
 */
function theme_flexinode_select($field_id, $label, $value, $formatted_value) {
  $output = theme('form_element', $label, $formatted_value);
  $output = '<div class="flexinode-select-'. $field_id .'">'. $output .'</div>';
  return $output;
}

/** @} End of addtogroup themeable */

?>