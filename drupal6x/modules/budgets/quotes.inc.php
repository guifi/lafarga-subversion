<?php
/*
 * Created on 12/08/2008 by rroca
 *
 * function for manage supplier quotes
 */
 
 function budgets_quote_help($path, $arg) {
  if ($path == 'admin/help#budgets_quote') {
    $txt = 'A quote is whatever component of a service or material which can be' .
        ' selected by users as an item of a budget or proposal';
    $replace = array();
    return '<p>'.t($txt,$replace).'</p>';
  }
}
 
function budgets_quote_form(&$node) {
  $type = node_get_types('type',$node);
  
  if (($type->has_title)) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => check_plain($type->title_label),
      '#required' => true,
      '#default_value' => $node->title,
    );
  }

  if (!empty($node->supplier_id)) {
    $s = node_load(array('nid'=>$node->supplier_id));
    $node->supplier = $s->nid.'-'.$s->title;
  } 
  $form['supplier'] = array(
    '#type' => 'textfield',
    '#required' => true,
    '#title' => t('Supplier'),
    '#description' => t('Supplier for this quote.'),
    '#default_vale' => $node->supplier,
    '#element_validate' => array('budgets_quote_supplier_validate'),
    '#autocomplete_path'=> 'budgets/js/select-supplier',
  );  

  $form['partno'] = array(
    '#type' => 'textfield',
    '#required' => true,
    '#size' => 60,
    '#maxlength' => 60,
    '#title' => t('Part number'),
    '#description' => t('Part number/Code to identify this quote.'),
    '#default_vale' => $node->partno,
    '#element_validate' => array('budgets_quote_partno_validate'),
  );  

  $form['cost'] = array(
    '#type' => 'textfield',
    '#title' => t('Cost'),
    '#size' => 12,
    '#required' => true,
    '#maxlength' => 15,
    '#attributes' => array('' .
        'class'=>'number required',
        'min'=>1),
    '#default_value' => $node->cost,
    '#description' => t('Quoted value (cost) for this quoted item.'),
  );

  if (($type->has_body)) {
    $form['body_field'] = node_body_field(
      $node,
      $type->body_label,
      $type->min_word_count
    );
  } 
    
  return $form;
}

function budgets_quote_ahah_select_supplier($string){
  $matches = array();
  
  $string = strtoupper(arg(3));
  
  $qry = db_query(
    'SELECT ' .
    '  CONCAT(id, "-", title) str '.
    'FROM {supplier} ' .
    'WHERE ' .
    '  (CONCAT(id, "-", upper(title)) ' .
    '    LIKE "%'.$string.'%") ' .
    'ORDER BY title');
    
  $c = 0;
  while (($value = db_fetch_array($qry)) and ($c < 50)) {
    $c++;
    $matches[$value['str']] = $value['str'];
  }
  print drupal_to_js($matches);
  exit();
}

function budgets_quote_partno_validate($element, &$form_state) {

  if (empty($element['#value']))
    return $element;
    
  $count = db_fetch_object(db_query(
      'SELECT count(partno) partno ' .
      'FROM {supplier_quote} ' .
      'WHERE partno IS NOT NULL ' .
      '  AND partno ="%s"',
      $element['#value']));
   
  if ($count->partno){
    form_error($element, t('Partno %partno already exists.',
      array('%partno'=>$element['#value'])));  
  }
  return $element;
}

function budgets_quote_access($op, $node, $account) {
  global $user;
  
  $node = node_load(array('nid'=>$node->id));
  switch($op) {
    case 'create':
      return user_access('create suppliers',$account);
    case 'update':
      if ($node->type == 'budgets') {
        if ((user_access('administer suppliers',$account)) 
          || ($node->uid == $user->uid)) {
          return TRUE;
        }
        else {
          return FALSE;
        }
      }
      else {
        return user_access('create suppliers',$account);
      }
  }
}

function budgets_quote_save($node) {
  global $user;
  
  $to_mail = $user->mail;
  $log = '';
  
  $sid = _guifi_db_sql(
    'supplier_quote',
    array('id'=>$node->nid),
    (array)$node,
    $log,$to_mail);

  if ($node->deleted)
    $action = t('DELETED');
  else if ($node->new)
    $action = t('CREATED');
  else
    $action = t('UPDATED');

  $subject = t('The supplier quote %title has been %action by %user.',
    array('%title'=>$node->title,
      '%action'=>$action,
      '%user'=>$user->name));

  drupal_set_message($subject);
  
  guifi_notify(
    $to_mail,
    $subject,
    $log);
}

function budgets_quote_insert($node) {
  $node->new = true;
  $node->id = $node->nid;
  budgets_quote_save($node); 
}

function budgets_quote_delete($node) {
  $node->delete = true;
  budgets_quote_save($node); 
}

function budgets_quote_update($node) {
  budgets_quote_save($node); 
}

function budgets_quote_load($node) {
  if (is_object($node))
    $k = $node->nid;
  else
    $k = $node;
        
  $node = db_fetch_object(
    db_query("SELECT * FROM {supplier_quote} WHERE id = '%d'", $k));
    
  if (is_null($node->id))
    return false;
  
  return $node;
}
 
?>
