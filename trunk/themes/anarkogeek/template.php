<?php

function phptemplate_wrap_content($text) {
  $text = preg_replace('!<pre>!i', '<div class="pre"><pre>', $text);
  $text = preg_replace('!</pre>!i', '</pre></div>', $text);
  return $text;
}
function phptemplate_wrap_links($link, $n) {
  $classes = array("lw1", "lw2");
  $before = $after = "";
  foreach ($classes as $c) {
    $before .= '<span class="'. $c .'">';
    $after .= '</span>';
  }
  
  $link = preg_replace('!<a[^>]*>!i', '\0'. $before, l($link['title'], $link['href'],
               $link['attributes'], $link['query'],
            $link['fragment'], FALSE, $link['html']));
            
  $link = preg_replace('!</a[^>]*>!i', $after . '\0', $link);
  
    return $link;
}

function phptemplate_menu_item_link($link) {
   if (empty($link['options'])) {
    $link['options'] = array();
  }

  // If an item is a LOCAL TASK, render it as a tab
  if ($link['type'] & MENU_IS_LOCAL_TASK) {
    $link['title'] = '<span class="lw1">' . check_plain($link['title']) . '</span>';
    $link['options']['html'] = TRUE;
  }

  if (empty($link['type'])) {
    $true = TRUE;
  }

  return l($link['title'], $link['href'], $link['options']);
}



function phptemplate_comment_thread_collapsed($comment) {
  if ($comment->depth) {
    $output  = '<div style="padding-left:'. ($comment->depth * 25) ."px;\">\n";
    $output .= theme('comment_view', $comment, '', 0);
    $output .= "</div>\n";
  }
  else {
    $output .= theme('comment_view', $comment, '', 0);   
  }
  return $output;
}

function phptemplate_comment_thread_expanded($comment) {
  $output = '';
  if ($comment->depth) {
    $output .= '<div style="padding-left:'. ($comment->depth * 25) ."px;\">\n";
  }

  $output .= theme('comment_view', $comment, module_invoke_all('link', 'comment', $comment, 0));

  if ($comment->depth) {
    $output .= "</div>\n";
  }
  return $output;
}

function _phptemplate_variables($hook, $vars = array()) {
  switch ($hook) {
    case 'page':
      // Load region content assigned via drupal_set_content().
      if (module_exists('fontsize')) {
        foreach (array('fontsize_init', 'fontsize_links') as $region) {
          $vars[$region] = drupal_get_content($region);
        }
      }
      break;
  }

  return $vars;
}
?>
