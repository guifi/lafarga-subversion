<?php
// $Id: field_textarea.inc,v 1.10 2005/01/13 16:32:24 JonBob Exp $

function flexinode_field_textarea_name($field) {
  return t('text area');
}

function flexinode_field_textarea_form($field, &$node) {
  $fieldname = 'flexinode_'. $field->field_id;
  $formatname = 'flexinode_'. $field->field_id .'_format';
  $output .= form_textarea(t($field->label), $fieldname, isset($node->$fieldname) ? $node->$fieldname : $field->default_value, 60, $field->rows, t($field->description), NULL, $field->required);
  $output .= filter_form($formatname, $node->$formatname);

  return $output;
}

function flexinode_field_textarea_db_select($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  $formatname = 'flexinode_'. $field->field_id .'_format';
  return $fieldname .'.textual_data AS '. $fieldname .', '. $fieldname .'.numeric_data AS '. $formatname;
}

function flexinode_field_textarea_insert($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  $formatname = 'flexinode_'. $field->field_id .'_format';
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data, numeric_data) VALUES (%d, %d, '%s', %d)", $node->nid, $field->field_id, $node->$fieldname, $node->$formatname);
}

function flexinode_field_textarea_format($field, $node, $brief = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  $formatname = 'flexinode_'. $field->field_id .'_format';
  if ($node->$fieldname) {
    if ($brief && strlen($node->$fieldname) > 100) {
      return check_output(substr($node->$fieldname, 0, 100), $node->$formatname) . '...';
    }
    else {
      return check_output($node->$fieldname, $node->$formatname);
    }
  }
}

function flexinode_field_textarea_config($field, $edit) {
  $form .= form_textarea(t('Default value'), 'default_value', $edit['default_value'], 60, 10);
  $form .= form_textfield(t('Lines'), 'rows', $edit['rows'] ? $edit['rows'] : 5, 10, 10, t('How large the text area will be.'));
  return $form;
}


/**
 * @addtogroup themeable
 * @{
 */

/**
 * Format a text area for display in a node.
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
function theme_flexinode_textarea($field_id, $label, $value, $formatted_value) {
  $output = theme('form_element', $label, $formatted_value);
  $output = '<div class="flexinode-textarea-'. $field_id .'">'. $output .'</div>';
  return $output;
}

/** @} End of addtogroup themeable */

?>