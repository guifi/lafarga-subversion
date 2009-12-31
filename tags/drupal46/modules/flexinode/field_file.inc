<?php
// $Id: field_file.inc,v 1.11.4.1 2005/04/16 12:56:10 JonBob Exp $

function flexinode_field_file_name($field) {
  return t('file');
}

function flexinode_field_file_form($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  $output .= form_hidden($fieldname .'_old', serialize($node->$fieldname));
  $output .= form_file(t($field->label), $fieldname, 40, ($node->$fieldname ? t('"%filename" has been uploaded. If you upload another file, the current file will be replaced.', array('%filename' => $node->$fieldname->filename)) : '') . t($field->description), $field->required);
  return $output;
}

function flexinode_field_file_db_select($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $fieldname .'.serialized_data AS '. $fieldname;
}

function flexinode_field_file_db_sort_column($field) {
  return 'flexinode_'. $field->field_id .'.textual_data';
}

function flexinode_field_file_insert($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  $node->$fieldname = file_save_upload($node->$fieldname, $node->$fieldname->filename);
  $serialized = is_object($node->$fieldname) ? serialize($node->$fieldname) : '';
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data, serialized_data) VALUES (%d, %d, '%s', '%s')", $node->nid, $field->field_id, $node->$fieldname->filename, $serialized);
}

function flexinode_field_file_delete($field, $node, $unconditional = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  $result = db_fetch_object(db_query('SELECT serialized_data FROM {flexinode_data} WHERE nid = %d AND field_id = %d', $node->nid, $field->field_id));
  $file = unserialize($result->serialized_data);
  if ($unconditional || $node->$fieldname != $file) {
    file_delete($file->filepath);
  }
}

function flexinode_field_file_validate($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  if ($file = file_save_upload($fieldname)) {
    return array('value' => $file);
  }
  else if (empty($node->$fieldname)) {
    return array('value' => unserialize($node->{$fieldname .'_old'}));
  }
}

function flexinode_field_file_format($field, $node, $brief = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  $file = is_object($node->$fieldname) ? $node->$fieldname : unserialize($node->$fieldname);

  if ($file) {
    return '<a href="'. file_create_url($file->filepath) .'">'. check_plain($file->filename) .'</a> ('. format_size($file->filesize) .')';
  }
}

function flexinode_field_file_load($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  return unserialize($node->$fieldname);
}


/**
 * @addtogroup themeable
 * @{
 */

/**
 * Format a file download for display in a node.
 *
 * @param field_id
 *   Which field is being displayed (useful when overriding this function
 *   if you want to style one particular field differently).
 * @param label
 *   The label for the field as displayed on the node form.
 * @param file
 *   The file that the user has uploaded. This is an object as provided
 *   by file.inc.
 * @param formatted_value
 *   A download link to the file.
 */
function theme_flexinode_file($field_id, $label, $file, $formatted_value) {
  $output = theme('form_element', $label, $formatted_value);
  $output = '<div class="flexinode-file-'. $field_id .'">'. $output .'</div>';
  return $output;
}

/** @} End of addtogroup themeable */

?>