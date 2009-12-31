<?php
// $Id: field_timestamp.inc,v 1.9 2004/09/16 14:34:00 JonBob Exp $

function flexinode_field_timestamp_name($field) {
  return t('date/time');
}

function flexinode_field_timestamp_form($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  return form_item(t($field->label), flexinode_form_date($node->$fieldname, $fieldname), t($field->description), NULL, $field->required);
}

function flexinode_field_timestamp_db_select($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $fieldname .'.numeric_data AS '. $fieldname;
}

function flexinode_field_timestamp_db_sort_column($field) {
  return 'flexinode_'. $field->field_id .'.numeric_data';
}

function flexinode_field_timestamp_insert($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  db_query('INSERT INTO {flexinode_data} (nid, field_id, numeric_data) VALUES (%d, %d, %d)', $node->nid, $field->field_id, $node->$fieldname);
}

function flexinode_field_timestamp_validate($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  return array('value' => flexinode_validate_date($node, $fieldname));
}

function flexinode_field_timestamp_format($field, $node, $brief = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  return format_date($node->$fieldname);
}


/**
 * A copy of event_form_date().
 */
function flexinode_form_date($timestamp, $prefix = '') {
  // Construct the start time select boxes.
  // TODO: Make the years automatically populated instead of static.

  //determine settings for form's hour selector
  if (variable_get('event_ampm', '0')) {
    $hour_format = 'g';
    $first_hour = 1;
    $last_hour = 12;
  }
  else {
    $first_hour = 0;
    $last_hour =23;
    $hour_format = 'G';
  }

  $years = array(2000 => 2000, 2001 => 2001, 2002 => 2002, 2003 => 2003, 2004 => 2004, 2005 => 2005, 2006 => 2006, 2007 => 2007, 2008 => 2008, 2009 => 2009);
  $months = array(1 => t('January'), t('February'), t('March'), t('April'), t('May'), t('June'), t('July'), t('August'), t('September'), t('October'), t('November'), t('December'));
  for ($i = 1; $i <= 31; $i++) $days[$i] = $i;
  for ($i = $first_hour; $i <= $last_hour; $i++) $hours[$i] = $i;
  for ($i = 0; $i <= 59; $i++) $minutes[$i] = $i < 10 ? "0$i" : $i;
  $am_pms = array('am' => t('am'), 'pm' => t('pm'));

  if (!isset($timestamp)) {
    $timestamp = time();
    // Round to nearest hour:
    $timestamp -= $timestamp % (60 * 60);
  }

  // Use format_date(), it handles user timezone and locale.
  $year = format_date($timestamp, 'custom', 'Y');
  $month = format_date($timestamp, 'custom', 'm');
  $day = format_date($timestamp, 'custom', 'd');
  $hour = format_date($timestamp, 'custom', $hour_format);
  $minute = format_date($timestamp, 'custom', 'i');
  $am_pm = format_date($timestamp, 'custom', 'a');

  $when = '<div class="container-inline">';
  $when .= '<div class="day">';
  $when .= form_select('', $prefix .'month', $month, $months);
  $when .= form_select('', $prefix .'day', $day, $days);
  $when .= form_select('', $prefix .'year', $year, $years);
  $when .= '</div><div class="time">';
  $when .= form_select('', $prefix .'hour', $hour, $hours);
  $when .= ':';
  $when .= form_select('', $prefix .'minute', $minute, $minutes);
  if (variable_get('event_ampm', '0')) {
    $when .= form_select('', $prefix .'ampm', $am_pm, $am_pms);
  }
  $when .= '</div></div>';

  return $when;
}


function flexinode_validate_date(&$node, $prefix = '') {
  if (isset($node->{$prefix . 'year'}) && isset($node->{$prefix . 'month'}) && isset($node->{$prefix . 'day'}) && isset($node->{$prefix . 'hour'}) && isset($node->{$prefix . 'minute'})) {
    $hour = $node->{$prefix . 'hour'};
    if (variable_get('event_ampm', '0')) {
      if ($node->{$prefix . 'ampm'} == 'pm') {
        $hour += 12;
      }
      elseif ($hour == 12) {
        $hour -= 12;
      }
    }
    $result = gmmktime($hour, $node->{$prefix . 'minute'}, 0, $node->{$prefix . 'month'}, $node->{$prefix . 'day'}, $node->{$prefix . 'year'}) - $GLOBALS['user']->timezone;
  }
  else if (isset($node->$prefix)) {
    $result = $node->$prefix;
  }

  return $result;
}


/**
 * @addtogroup themeable
 * @{
 */

/**
 * Format a timestamp for display in a node.
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
function theme_flexinode_timestamp($field_id, $label, $value, $formatted_value) {
  $output = theme('form_element', $label, $formatted_value);
  $output = '<div class="flexinode-timestamp-'. $field_id .'">'. $output .'</div>';
  return $output;
}

/** @} End of addtogroup themeable */

?>