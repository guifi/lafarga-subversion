<?php

function budgets_edit_item($node,$op = null) {

  // hidden all values with no field at this forms to continue with the freamework
  foreach ($node as $key => $value) {
    if (is_array($value)) {
      if ($key != 'items') 
        foreach ($value as $k_id => $k_value)
          $output .= form_hidden($key.']['.$k_id,$k_value); 
    } else 
      $output .= form_hidden($key,$value);
  }

  // the budget items fields, setting the hidden values (not edited items)
  $next_item_id = 0;
  if (!empty($node->items)) foreach ($node->items as $item_id => $item) {
    if ($item_id >= $next_item_id) 
      $next_item_id = $item_id + 1;
    if ((isset($node->item_checked)) and ($node->item_checked == $item_id)) {
      $description = $item['description']; 
      $comments = $item['comments']; 
      $quantity = $item['quantity']; 
      $cost = $item['cost']; 
      $weight = $item['weight']; 
      $edit_item_id = $item_id;
    } else {
      foreach ($item as $field=>$value)
        $output .= form_hidden('items]['.$item_id.']['.$field,$value);
    } 
  }
  // going to edit the selected item
  if ($op == 'add')
    $edit_item_id = $next_item_id;

  $output .= form_textarea(t('Description'),'items]['.$edit_item_id.'][description',$description,60,10,t('Description of this item'));
  $output .= form_textarea(t('Comments'),'items]['.$edit_item_id.'][comments',$comments,60,10,t('Additional comments about this item'));
  $output .= form_textfield(t('Quantity'),'items]['.$edit_item_id.'][quantity',$quantity,11,11,t('Number of items units'));
  $output .= form_textfield(t('Cost'),'items]['.$edit_item_id.'][cost',$cost,11,11,t('Due cost per each unit').'('.variable_get('budget_currency_long','Euros').')');
  $output .= form_weight(t('Weight'),'items]['.$edit_item_id.'][weight',$weight,20,t('Item weight: Lower values are displayed first, larger last'));
  $output .= form_hidden('items]['.$edit_item_id.'][item_id',$edit_item_id);
  $output .= form_hidden('items]['.$edit_item_id.'][nid',$edit["nid"]);

  $output .= form_button(t('Back to budget form'),'back_to_form');
  $output .= t("<br />Warning: This button will accept your changes as sub-part of the edit.  You will still have to select <b>'submit'</b> on the next screen to make your changes permanent.");

  print theme('page', form($output));
}

function budgets_contribute(&$node) {

  drupal_set_title(t('Contribute to').': '.$node->title);
  if ($node->budget_status != 'Open') {
    drupal_set_message(t('Budget must be open to accept contributions'));
    return;
  }

  if (isset($_POST['submit_fund'])) {
    $fund = $_POST['edit'];
    budgets_validate_fund($fund);
    if (form_get_errors() == null) {
      db_query("INSERT INTO {budget_funds} (nid, fund_id, contributor_name, contributor_email, contributor_telf, amount, fund_status, comments, timestamp_created) VALUES (%d, %d, '%s', '%s', '%s', %.10f, 'Submitted', '%s', %d)",$node->nid, $node->next_fund, $fund['contributor_name'], $fund['contributor_email'], $fund['contributor_telf'], $fund['amount'], $fund['comments'], time());
      drupal_set_message(t('<b>Your contribution has been succesfully submitted.</b> Thanks for contributing to this project. Here you have a list of the current contributions'));
      cache_clear_all();
      drupal_goto('node/'.$node->nid.'/view/funds');
    } else {
      form_set_error(null,t('<b>WARNING:</b> Your contribution has <b><u>NOT</b></u> been sent. There was errors while validating, please read the error messages'));
    }
  }

  $form  = form_textfield(t('Name'),'contributor_name',$fund['contributor_name'], 60,60,t('Your full name, or if your contribution wants to be anonyomous, something that will identify that the money comes from this donation once is verified.'));
  $form .= form_textfield(t('Email'),'contributor_email',$fund['contributor_email'], 60,60,t('Your email-id. Will be available just to promoters and not published in the reports.'));
  $form .= form_textfield(t('Telf'),'contributor_telf',$fund['contributor_telf'], 60,60,t('How promoters can contact you by phone. Will be available just to them and not published at the reports'));
  $form .= form_textfield(t('Amount'),'amount',$fund['amount'], 15,15,t('Amount of the contribution').'&nbsp;('.$node->currency_txt.').'.
                          '<br />'.$node->payment_instructions);
  $form .= form_textarea(t('Comments'),'comments',$fund['comments'], 60,3,t('Write here any additional comments about your contribution. Note that in some cases you may donate other things, like reused material, availability to work...'));
  $form .= form_checkbox(t('I do agree with the <i>Terms & Conditions</i> expressed below').':','agree',true,false,$node->terms);

  $form .= form_button(t('Submit'),'submit_fund');
  $form .= form_hidden('min_contribution',$node->min_contribution);
  $form .= form_hidden('max_contribution',$node->max_contribution);

  $output .= form($form);

  return $output;
}

?>
