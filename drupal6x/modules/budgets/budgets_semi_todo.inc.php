<?php

function budgets_edit_form($node) {
  budgets_node_loads($node);

 
$form_weight = -50;

    $form['title'] = array(
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#required' => TRUE,
        '#default_value' => !empty($node->title) ? $node->title : NULL,
        '#weight' => $form_weight++
    );

    $form['budgets_general'] = array(
        '#type' => 'fieldset',
        '#title' => t('General'),
        '#weight' => $form_weight++
    );

    $form['budgets_general']['budget_status'] = array(
        '#type' => 'select',
        '#title' => t('Status'),
        '#default_value' => $node->budget_status,
        '#options' => array(
            'Preparation' => t('Preparation'),
            'Open' => t('Open'), 
            'Closed' => t('Closed')),
        '#weight' => $form_weight++
    );

    $form['budgets_general']['txtexpire'] = array(
        '#type' => 'textfield',
        '#title' => t('Expiration'),
        '#default_value' => $node->expires,
        '#size' => 10,
        '#maxlength' => 10,
        '#description' => t("Date when this budget will expire (format: YYYY-MM-YY)")
    );

    $form['budgets_currency'] = array(
        '#type' => 'fieldset',
        '#title' => t('Currency'),
        '#weight' => $form_weight++
    );
  
    $form['budgets_currency']['currency_txt'] = array(
        '#type' => 'textfield',
        '#title' => t('Text'),
        '#default_value' => $node->currency_txt,
        '#size' => 10,
        '#maxlength' => 10, 
        '#description' => t("abbreviation"),
        '#weight' => $form_weight++
    );
  
    $form['budgets_currency']['currency_symbol'] = array(
        '#type' => 'textfield',
        '#title' => t('Symbol'),
        '#default_value' => $node->currency_symbol,
        '#size' => 5,
        '#maxlength' => 5,
        '#weight' => $form_weight++
    );

    $form['budgets_contribution'] = array(
        '#type' => 'fieldset',
        '#title' => t('Contributions'),
        '#weight' => $form_weight++
    );

    $form['budgets_contribution']['min_contribution'] = array(
        '#type' => 'textfield',
        '#default_value' =>  $node->min_contribution,
        '#size' => 10,
        '#maxlength' => 10,
        '#description' => t("Under this amount, contributions will not be accepted"),
        '#weight' => $form_weight++
    );

    $form['budgets_contribution']['max_contribution'] = array(
        '#type' => 'textfield',
        '#default_value' =>  $node->max_contribution,
        '#size' => 10,
        '#maxlength' => 10,
        '#description' => t("Below this amount, contributions will not be accepted"),
        '#weight' => $form_weight++
    );
  // List all budget items.
  if( is_array($node->items) && !empty($node->items)) {
    $nocomponents = count($node->items);

    // sorting array by weight
    foreach ($node->items as $item_id => $item)
      $items_sorted[$item_id] = $item['weight'];
    asort($items_sorted);

    $total = 0;
    foreach($items_sorted as $item_id => $weight) {
      $subtotal = $node->items[$item_id]['quantity']* $node->items[$item_id]['cost'];
      $total = $total + $subtotal;

                    //form_radio('', 'item_checked', $item_id),
                     $form['items]['.$item_id.'][item_checked'] = array(
                         '#type' => 'radio',
                         '#id' => $item_id,
                         '#prefix' => '<table style="width: 40em"><tr><td style="wiiidth: 200px">',
                         '#suffix' => '</td>',
                     );
                    //form_hidden("items][".$item_id.'][description', $node->items[$item_id]['description']).$node->items[$item_id]['description'], 
                     $form['items]['.$item_id.'][description'] = array(
                         '#type' => 'hidden', 
                         '#value' => $node->items[$item_id]['description'],
    '#prefix' => '<td>',
    '#suffix' => '</td>',
                     );
                    //form_hidden("items][".$item_id.'][comments', $node->items[$item_id]['comments']).$node->items[$item_id]['comments'], 
                     $form['items]['.$item_id.'][comments'] = array(
                         '#type' => 'hidden', 
                         '#value' => $node->items[$item_id]['comments'],
    '#prefix' => '<td>',
    '#suffix' => '</td>',
                     );
                    //form_textfield('', "items][".$item_id.'][quantity', $node->items[$item_id]['quantity'], 6, 12),
                     $form['items]['.$item_id.'][quantity'] = array(
                         '#type' => 'textfield',
                         '#default_value' =>  $node->items[$item_id]['quantity'],
                         '#size' => 6,
                         '#maxlength' => 12,
    '#prefix' => '<td>',
    '#suffix' => '</td>',
                     );
                    //form_textfield('', "items][".$item_id.'][cost', $node->items[$item_id]['cost'], 10, 11),
                     $form['items]['.$item_id.'][cost'] = array(
                         '#type' => 'textfield',
                         '#default_value' =>  $node->items[$item_id]['cost'],
                         '#size' => 10,
                         '#maxlength' => 11,
    '#prefix' => '<td>',
    '#suffix' => '</td>',
                     );
                    array('data'=>number_format($subtotal,2,',','.'),'align'=>'right');
                    //form_weight('',"items][".$item_id.'][weight', $node->items[$item_id]['weight'],20));
                     $form['items]['.$item_id.'][weight'] = array(
                         '#type' => 'weight',
                         '#default_value' => $node->items[$item_id]['weight'],
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></table>',
                     );
                
    }
    $rows[] = array(null,'<strong>'.t('Total').'</strong>',null,null,null,
                    array('data'=>'<strong>'.number_format($total,2,',','.').'</strong>','align'=>'right'),null);

    $help_text = theme_item_list(array(
                                       t('To edit an item description or comment, check its box and press "Edit selected".'),
                                       t('To delete an item, check its box and press "Delete selected".'),
                                       t('You can edit units, cost and weight values directly in the form.'),
                                       t('Remember to set weight on the components or they will be added to the budget in a random order.'),
                                       t('The components are sorted first by weight and then by name.')
                                       )
                                 );
    $form['items']['items_help'] = array(
        '#type' => 'item',
        '#title' => t('Budget items'),
        '#value' => $help_text,
        '#weight' => $form_weight++
    );
    $headers = array(
//                     '<span>&nbsp;&nbsp;'.t('Select').'</span>',
                     '&nbsp;',
                     '<span>'.t('Description').'</span>',
                     t('Comments'),
                     '<span>'.t('Units').'</span>',
                     '<span>'.t('Cost').'</span>',
                     '<span>'.t('Total').'</span>',
                     '<span>'.t('Weight').'</span>'
                     );
    $output .= theme('table', $headers, $rows);
//    $output .= form_button(t('Edit selected'), 'form_item[edit]');
      $output .= 'form_button';
//    $output .= form_button(t('Delete selected'), 'form_item[delete]');
      $output .= 'form_button';

  } else {

 //   $output .= form_item(t('Budget items'),t('There is no budget items yet for this project'),t('Add values by pressing the "Add budget item" button'));
      $output .=     $form['menu']['_path'] = array(
      '#type' => 'item',
      '#title' =>t('Budget items'),
      '#description' => t('There is no budget items yet for this project'),t('Add values by pressing the "Add budget item" button'),
    );

  }

 // $output .= form_button(t('Add budget item'), 'form_item[add]');
    $form['form_item[add]'] = array(
        '#type' => 'button',
        '#value' => t('Add budget item'),
        '#weight' => $form_weight++
    );

  if (!empty($node->budget_items)) {
  }
    $form['body'] = array(
        '#title' => t("Body"),
        '#type' => 'textarea',
        '#default_value' => $node->body,
        '#description' => t('Description of the project. Describe here it\'s purpose, expected benefits and beneficiaries, justifications, etc. Use de default drupal node syntax for constructing a teaser with the first lines of text or by inserting the <!-- header --> tag'),
        '#rows' => 10,
        '#weight' => $form_weight++
    );
    $form['zones'] = array(
        '#type' => 'select',
        '#title' => t('Zones'),
        '#default_value' => $node->zones,
        '#options' => guifi_zones_listbox(),
        '#description' => t("If there are, zones where this budget apply specifically."),
        '#multiple' => TRUE,
        '#weight' => $form_weight++
    );
    $form['promoter'] = array(
        '#title' => t("Promoter(s)"),
        '#type' => 'textarea',
        '#default_value' => $node->promoter,
        '#description' => t('Explain here who are you, how to contact you, why people have to trust you, what you have already done...'),
        '#rows' => 3,
        '#weight' => $form_weight++
    );

    $form['payment_instructions'] = array(
        '#title' => t("Payment instructions"),
        '#type' => 'textarea',
        '#default_value' => $node->payment_instructions,
        '#description' => t("Explain here how to send money funds for this project, it can be an account for money transfers, ask for contact information, links to paypal or similar online applications..."),
        '#rows' => 7,
        '#weight' => $form_weight++
    );

    $form['terms'] = array(
        '#title' => t("Terms & Conditions"),
        '#type' => 'textarea',
        '#default_value' => $node->terms,
        '#description' => t("Explain here the terms and conditions of this project or use the default text provided"),
        '#rows' => 7,
        '#weight' => $form_weight++
    );

  return $form;
}

?>
