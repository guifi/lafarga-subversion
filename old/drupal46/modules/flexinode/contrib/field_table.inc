<?php
// $Id: field_table.inc,v 1.3 2004/07/23 07:51:27 chx Exp $
function flexinode_field_table_name($field) {
  return t('table');
}

function flexinode_field_table_form($field, $node) {

  $fieldname = 'flexinode_'. $field->field_id;
  if ($node->$fieldname) {
    $table=$node->$fieldname;
    $rows=count($table)+1;
    $cols=count($table[0])+1;
  }
  else {
    $table=array();
    $rows=8;
    $cols=5;
  }
  $output = '';
  $output .= form_hidden($fieldname.'_rows',$rows);
  $output .= '<div class="form-item"><label for="flexinode_'.$field->field_id.'_0][">'.t($field->label).':</label></div>';
  $output .= '<table>';
  $title = t($field->label);
  for ($i = 0; $i < $rows; $i++) {
    $output .= '<tr>';
    for ($j = 0; $j < $cols; $j++) {
      $output .= '<td>';
      $value = (isset($table[$i]) && isset($table[$i][$j])) ? $table[$i][$j] : '';
      $output .= form_textarea('', $fieldname.'_'.$i.'][', $value, 10, 3);
      $output .= '</td>';
    }
    $output .= '</tr>';
  }
  $output .= '</table>';
  return $output;
}

function flexinode_field_table_validate($field, $node) {

  $fieldname = 'flexinode_'. $field->field_id;
  if (!isset($_POST['edit'])) {
    return array('value' => $node->$fieldname);
  }
  $rows = $_POST['edit'][$fieldname.'_rows'];
  $value = array();
  $deleted_cols = array();
  foreach ($_POST['edit'][$fieldname.'_0'] as $key=>$val) {
    if (empty($val) && $key) {
      $deleted_cols[] = $key;
    }    
  }
  for ($i = 0; $i <= $rows; $i++) {
    if ($i && empty($_POST['edit'][$fieldname.'_'.$i][0])) {
      continue;
    }
    $newrow = $_POST['edit'][$fieldname.'_'.$i];
    foreach ($deleted_cols as $key)
      unset($newrow[$key]);
    $value[] = array_values($newrow); // reindex from 0
  }
  return (array('value' => $value));
}

function flexinode_field_table_insert($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  db_query("INSERT INTO {flexinode_data} (nid, field_id, serialized_data) VALUES (%d, %d, '%s')", $node->nid, $field->field_id, serialize($node->$fieldname));
}

function flexinode_field_table_db_select($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $fieldname .'.serialized_data AS '. $fieldname;
}

function flexinode_field_table_load($field, $node) {

  $fieldname = 'flexinode_'. $field->field_id;
  if (isset($table_phase)) $table_phase++; else $table_phase=0;
  return unserialize($node->$fieldname);
}


function flexinode_field_table_format($field, $node, $brief = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  $table = $node->$fieldname;
  $output = "<table id=$fieldname>";
  $i=0;
  foreach ($table as $row) {
    $output .= '<tr class="'.$fieldname.'_'.$i.'"><td>'.implode('</td><td>',$row).'</td></tr>';
    $i=1-$i;
  }
  $output .= "</table>";
  return $output;
}

function theme_flexinode_table($field_id, $label, $value, $formatted_value) {
  $output = theme('form_element', $label, $formatted_value);
  $output = '<div class="flexinode-table-'. $field_id .'">'. $output .'</div>';
  return $output;
}

?>
