<?php

/**
 * @file
 * Administrative page for handling updates from one Drupal version to another.
 *
 * Copy this file to the root directory of your drupal installation.
 * Login as user 1, or disable the access checking below
 * Point your browser to "http://www.site.com/event_upgrade.php" and follow the
 * instructions.
 *
 * If you are not logged in as administrator, you will need to modify the access
 * check statement below. Change the TRUE into a FALSE to disable the access
 * check. After finishing the upgrade, be sure to open this file and change the
 * FALSE back into a TRUE!
 */

// Enable access checking?
$access_check = TRUE;

if (!ini_get("safe_mode")) {
  set_time_limit(180);
}

function event_upgrade_page_header($title) {
  $output = "<html><head><title>$title</title>";
  $output .= <<<EOF
      <link rel="stylesheet" type="text/css" media="print" href="misc/print.css" />
      <style type="text/css" title="layout" media="Screen">
        @import url("misc/drupal.css");
      </style>
EOF;
  $output .= "</head><body>";
  $output .= "<div id=\"logo\"><a href=\"http://drupal.org/\"><img src=\"misc/druplicon-small.png\" alt=\"Druplicon - Drupal logo\" title=\"Druplicon - Drupal logo\" /></a></div>";
  $output .= "<div id=\"update\"><h1>$title</h1>";
  return $output;
}

function event_upgrade_page_footer() {
  return "</div></body></html>";
}

function event_upgrade_page() {
  global $user;

  if (isset($_POST['edit'])) {
    $edit = $_POST['edit'];
  }
  if (isset($_POST['op'])) {
    $op = $_POST['op'];
  }

  switch ($op) {
    case "Update":
      // make sure we have updates to run.
      print event_upgrade_page_header("event.module database update");
      $links[] = "<a href=\"index.php\">main page</a>";
      $links[] = "<a href=\"index.php?q=admin\">administration pages</a>";
      print theme("item_list", $links);

      $ctype_id = db_next_id('{flexinode_ctype}');
      $vals[] = $ctype_id;
      $vals[] = 'event';
      $vals[] = 'This is the event type setup by event_upgrade.php';
      $vals[] = 'You can change this text by going to the <a href="admin/node/types">flexinode content configuration page</a>';
      if(!db_query("INSERT INTO {flexinode_type} (ctype_id, name, description, help) VALUES (%d, '%s', '%s', '%s')", $vals)) {
        die('unable to create new flexinode type');
      }

      switch ($edit['type']) {
        case 'drupal' :
          event_upgrade_drupal($ctype_id);
          break;

        case 'civicspace' :
          event_upgrade_civicspace($ctype_id);
          break;
      }

      print event_upgrade_page_footer();
      break;
    default:
      // make update form and output it.
      $types = array('drupal' => 'Drupal v4.5 event.module', 'civicspace' => 'Civicspace v0.8.0.3 event.module');
      $form = form_select('Perform updates for', 'type', '', $types, 'This defaults to the first available update since the last update you performed.');
      $form .= form_submit('Update');
      print event_upgrade_page_header('Drupal database update');
      print form($form);
      print event_upgrade_page_footer();
      break;
  }
}

function event_upgrade_drupal($ctype_id) {
  include_once 'modules/event/fields.inc';

  $fields = event_extra_fields();
  $flex_fields = array('password' => 'textfield',
                        'textfield' => 'textfield',
                        'textarea' => 'textarea',
                        'select' => 'select',
                        'radios' => 'select',
                        'checkbox' => 'checkbox',
                        'radio' => 'checkbox' );

  foreach ($fields as $key => $def) {
    $field_id = db_next_id('{flexinode_field}');

    $map[$key] = $field_id;

    // leaving this here to make it easier to see the field mapping later
    $edit['field_type'] = $flex_fields[$def[0]];
    $edit['label'] = $def[1];
    $edit['required'] = $def[2];
    $edit['show_teaser'] = $def[4];
    $edit['default_value'] = $def[6];
    $options = $def[7];
    $edit['rows'] = $def[8];
    $edit['description'] = $def[9];

    db_query("INSERT INTO {flexinode_field} (label, default_value, rows, required, show_teaser, show_table, weight, ctype_id, field_type, options, description, field_id) VALUES ('%s', '%s', %d, %d, %d, %d, %d, %d, '%s', '%s', '%s', %d)", $edit['label'], $edit['default_value'], $edit['rows'], $edit['required'], $edit['show_teaser'], TRUE, 0, $ctype_id, $edit['field_type'], serialize($options), $edit['description'], $field_id);
  }

  $events = db_query("SELECT * FROM {event} e LEFT JOIN {node} n ON n.nid = e.nid");
  while($event = db_fetch_object($events)) {
    // first the fields with thier own db table
    foreach ($fields as $key => $def) {
      $value = NULL;
      if ($def[3]) { // Stored in separate database field
        $drops[$key] = $key;
        if ($def[0] == "select" && $def[10]) { // multi-select
            $value = unserialize($event->$key);
        }
        else {
          $value = $event->$key;
        }
        $function = 'event_upgrade_'. $flex_fields[$def[0]] .'_insert';
        $field->field_id = $map[$key];
        $field->nid = $event->nid;
        $function($field, $value);
      }
    }

    // now get the serialized info out of the data field
    $event->data = unserialize($event->data);
    if (is_array($event->data)) {
      foreach ($event->data as $key => $value) {
        $type = $fields[$key][0];
        $function = 'event_upgrade_'. $flex_fields[$type] .'_insert';
        $field->field_id = $map[$key];
        $field->nid = $event->nid;
        $function($field, $value);
      }
    }
    print 'Transferred event: '. $event->title ."<br />\n";
  }

  db_query("UPDATE {node} set type='flexinode-$ctype_id' where type='event'");

  print '<p>Your event fields should be transferred now. If you want to confirm this is so now is a good time.<br />Please execute the following sql statements directly on the database to finish the upgrade:</p>';
  print '<code>';
  print 'ALTER TABLE event ADD KEY start (start);<br />'."\n";
  print 'ALTER TABLE event ADD end INT(10) UNSIGNED NOT NULL default \'0\';<br />'."\n";
  print 'ALTER TABLE event ADD tz INT(11) default NULL;<br />'."\n";
  print 'ALTER TABLE event DROP data;<br />'."\n";
  foreach($drops as $drop) {
    print 'ALTER TABLE event DROP '. $drop .';<br />'."\n";
  }
  print '</code>';
}

function event_upgrade_civicspace($ctype_id) {
  $flex_fields = array('password' => 'textfield',
                      'textfield' => 'textfield',
                      'textarea' => 'textarea',
                      'select' => 'select',
                      'radios' => 'select',
                      'checkbox' => 'checkbox',
                      'file' => 'file',
                      'email_address' => 'email',
                      'url' => 'url');

/**

  [ffid] => 1
  [fid] => 1
  [title] => A textfield
  [name] => text1
  [explanation] => the textfield
  [page] => 
  [type] => textfield
  [weight] => -1
  [required] => 1
  [flags] => a:1:{s:7:"publish";s:1:"0";}
  [validation] => --
  [options] => 
  [multiple] => 0

**/

  $results = db_query("SELECT fi.* FROM {forms} f LEFT JOIN {form_fields} fi ON f.fid = fi.fid WHERE f.type = 'event'");
  while($field = db_fetch_object($results)) {
    $field_id = db_next_id('{flexinode_field}');

    $map[$field->name] = $field_id;

    // leaving this here to make it easier to see the field mapping later
    $flags = unserialize($field->flags);
    $edit['show_teaser'] = ($flags['publish'] ? TRUE : FALSE);

    $type = ($field->validation != '--' ? $field->validation : $field->type);
    $edit['field_type'] = $flex_fields[$type];
    $types[$field->name] = $flex_fields[$type];

    $edit['label'] = $field->title;
    $edit['required'] = $field->required;
    $options = serialize(event_upgrade_forms_options($field->options));
    $edit['description'] = $field->explanation;
    $edit['weight'] = $field->weight;

    db_query("INSERT INTO {flexinode_field} (label, default_value, rows, required, show_teaser, show_table, weight, ctype_id, field_type, options, description, field_id) VALUES ('%s', '%s', %d, %d, %d, %d, %d, %d, '%s', '%s', '%s', %d)", $edit['label'], '', 5, $edit['required'], $edit['show_teaser'], TRUE, $edit['weight'], $ctype_id, $edit['field_type'], serialize($options), $edit['description'], $field_id);
  }

  $events = db_query("SELECT * FROM {event} e LEFT JOIN {node} n ON n.nid = e.nid");
  while($event = db_fetch_object($events)) {
    $fields = db_query("SELECT f.*, ff.* FROM {event_field_data} f LEFT JOIN {event} e ON e.nid = f.nid LEFT JOIN {form_fields} ff ON ff.name = f.name WHERE e.nid = %d", $event->nid);
    while ($field = db_fetch_object($fields)) {
      $function = 'event_upgrade_'. $types[$field->name] .'_insert';
      $field->field_id = $map[$field->name];
      $field->nid = $event->nid;
      $function($field, $field->data);
    }
    print 'Transferred event: '. $event->title ."<br />\n";
  }

  db_query("UPDATE {node} set type='flexinode-$ctype_id' where type='event'");

  print '<p>Your event fields should be transferred now. If you want to confirm this is so now is a good time.<br />Please execute the following sql statements directly on the database to finish the upgrade:</p>';
  print '<code>';
  print 'ALTER TABLE event ADD KEY start (start);<br />'."\n";
  print 'ALTER TABLE event ADD end INT(10) UNSIGNED NOT NULL default \'0\';<br />'."\n";
  print 'ALTER TABLE event ADD tz INT(11) default NULL;<br />'."\n";
  print 'ALTER TABLE event DROP galleries;<br />'."\n";
  print 'DROP TABLE event_field_data;<br />'."\n";
  print 'DROP TABLE event_item;<br />'."\n";
  print '</code>';
}

function event_upgrade_select_insert($field, $value) {
  $options = array_flip(event_upgrade_forms_options($field->options));
  db_query('INSERT INTO {flexinode_data} (nid, field_id, numeric_data) VALUES (%d, %d, %d)', $field->nid, $field->field_id, $options[$value]);
}

function event_upgrade_textarea_insert($field, $value) {
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data, numeric_data) VALUES (%d, %d, '%s', %d)", $field->nid, $field->field_id, $value, 0);
}

function event_upgrade_textfield_insert($field, $value) {
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data) VALUES (%d, %d, '%s')", $field->nid, $field->field_id, $value);
}

function event_upgrade_checkbox_insert($field, $value) {
  db_query('INSERT INTO {flexinode_data} (nid, field_id, numeric_data) VALUES (%d, %d, %d)', $field->nid, $field->field_id, $value);
}

function event_upgrade_file_insert($field, $value) {
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data, serialized_data) VALUES (%d, %d, '%s', '%s')", $field->nid, $field->field_id, $value, '');
}

function event_upgrade_email_insert($field, $value) {
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data) VALUES (%d, %d, '%s')", $field->nid, $field->field_id, $value);
}

function event_upgrade_url_insert($field, $value) {
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data) VALUES (%d, %d, '%s')", $field->nid, $field->field_id, $value);
}

function event_upgrade_forms_options($options) {
  $array = explode(';', $options);
  $options = array();
  foreach ($array as $opt) {
    list($key, $value) = explode(':', $opt);
    $key = trim($key);
    if ($value) {
      $options[] = trim($value);
    }
    else {
      $options[] = $key;
    }
  }
  return $options;
}

function event_upgrade_info() {
  print event_upgrade_page_header("event.module database update");
  print "<ol>\n";
  print "<li>Use this script to <strong>upgrade an existing event.module installation</strong>.  You don't need this script when installing the event.module from scratch.</li>";
  print "<li>Before doing anything, backup your database. This process will change your database and its values, and some things might get lost.</li>\n";
  print "<li>This script has only been tested on mysql databases. It may work with postgres, however it may not. If you have problems please submit a bug report on drupal.org, or better yet, a patch.</li>\n";
  print "<li>You must have Drupal v4.6 OR Civicspace v0.8.0.x and the equivalent flexinode (http://drupal.org/node/5737) and event modules for this script to work.";
  print "Check the notes below and <a href=\"event_upgrade.php?op=update\">run the database upgrade script</a>.  Don't upgrade your database twice as it may cause problems.</li>\n";
  print "<li>This script will create a flexinode called 'event' with the appropriate fields to migrate your data to. If you already have a flexinode named this, you will have two when it is done.</li>\n";
  print "<li>Go through the various administration pages to change the existing and new settings to your liking.</li>\n";
  print "</ol>";
  print "<p>Drupal event.module Notes:";
  print "<ol>";
  print "<li><strong>If you are upgrading the from the 4.5 Drupal version of event.module, you must leave the fields.inc file in the modules/event directory for the upgrade to work. If you removed it during the upgrade process, put it back.</strong> Once the upgrade is finished you may remove it.</li>\n";
  print "<li>Field-level permissions setup in fields.inc will not be carried over to the new events module. Field-level permissions are not implemented in the current version of flexinode.</li>\n";
  print "<li>Password fields setup in fields.inc will be translated to textfields, there is currently no password field type for flexinode.</li>\n";
  print "<li>Radios will be translated to selects, there is currently no radios field type for flexinode.</li>\n";
  print "</ol>";
  
  print event_upgrade_page_footer();
}

if (isset($_GET["op"])) {
  include_once "includes/bootstrap.inc";
  include_once "includes/common.inc";

  // Access check:
  if (($access_check == 0) || ($user->uid == 1)) {
    event_upgrade_page();
  }
  else {
    print event_upgrade_page_header("Access denied");
    print "Access denied.  You are not authorized to access to this page.  Please log in as the user with user ID #1. If you cannot log-in, you will have to edit <code>modules/event/event_upgrade.php</code> to by-pass this access check; in that case, open <code>modules/event/event_upgrade.php</code> in a text editor and follow the instructions at the top.";
    print event_upgrade_page_footer();
  }
}
else {
  event_upgrade_info();
}
?>
