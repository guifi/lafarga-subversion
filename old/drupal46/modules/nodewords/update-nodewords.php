<?php
/**
 * Copy this file to your drupal installation root and run from a web browser
 *
 * BACK UP YOUR DATABASE FIRST!
 */

include_once 'includes/bootstrap.inc';
if (function_exists('drupal_bootstrap')) { //CVS-HEAD
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL); 
}
else { //4.6
  include_once 'includes/common.inc';
}

if (module_exist('nodewords')) {
  print "<h1>Updating <em>nodewords</em>...</h1>";
  print "<ol>";
  print "<li>Copying settings...";
  $settings = _nodewords_get_settings();

  $global_keywords = variable_get('nodewords_global_keywords', "");
  variable_del('nodewords_global_keywords');

  $nodewords = variable_get('nodewords', array());
  variable_del('nodewords');

  if ($nodewords) {
    $settings = $nodewords;
    $settings['head'] = $nodewords['output'];
    unset($settings['output']);
  }
  elseif (!empty($global_keywords)) {
    $settings['global']['keywords'] = $global_keywords;
  }
  variable_set('nodewords', $settings);
  print "done.</li>";

  print "<li>Renaming permissions from <em>administer node words</em> to <em>administer meta tags</em>...";
  $old = 'administer node keywords';
  $new = 'administer meta tags';

  $result = db_query("SELECT p.rid, p.perm, r.name FROM {permission} p INNER JOIN {role} r ON p.rid = r.rid WHERE p.perm LIKE '%$old%'");
  while ($role = db_fetch_object($result)) {
    $rid = $role->rid;
    $name = $role->name;
    $perm = str_replace($old, $new, $role->perm);
    db_query("UPDATE {permission} SET perm = '%s' WHERE rid = %d", $perm, $rid);
  }
  print "done.</li>";

  print "<li>Copying data...";
  $result = db_query("SELECT * FROM {nodewords_old}");
  while ($row = db_fetch_array($result)) {
    if (array_key_exists('nodewords', $row)) {
      // Convert from 4.6 to 4.7 format
      db_query("INSERT INTO {nodewords} (type, id, name, content) VALUES ('%s', '%s', '%s', '%s')", 'node', $row['nid'], 'keywords', $row['nodewords']);
    }
    elseif (array_key_exists('keywords', $row)) {
      // Convert from intermediate CVS-HEAD to 4.7 format
      $nid = $row['nid'];
      foreach ($row as $name => $content) {
        if ($name != 'nid') {
          db_query("INSERT INTO {nodewords} (type, id, name, content) VALUES ('%s', '%s', '%s', '%s')", 'node', $nid, $name, $content);
        }
      }
    }
  }
  print "done.</li>";
  print "</ol>";

  print "<h1>Update complete!</h1>";
  print "<ol>";
  print "<li>If your data was updated correctly, you can delete the <em>nodewords_old</em> table:";
  print "<pre>DROP TABLE nodewords_old;</pre></li>";
  print "<li>Remove <em>update-nodewords.php</em> from the root directory.</li>";
  print "</ol>";
}

?>
