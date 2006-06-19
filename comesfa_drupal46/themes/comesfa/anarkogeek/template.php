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
  $link = preg_replace('!<a[^>]*>!i', '\0'. $before, $link);
  $link = preg_replace('!</a[^>]*>!i', $after . '\0', $link);
  return $link;
}

function phptemplate_links($links, $delimiter = " – ") {
  return implode($delimiter, $links);
}

function phptemplate_menu_item_link($item, $link_item) {
  /* Wrapper span */
  return l('<span class="lw1">'. check_plain($item['title']) .'</span>', $link_item['path'], array_key_exists('description', $item) ? array('title' => $items['description']) : array(), NULL, NULL, FALSE, TRUE);
}

function phptemplate_comment_thread_min($comment, $threshold, $pid = 0) {
  if (comment_visible($comment, $threshold)) {
    $output  = '<div style="padding-left:'. ($comment->depth * 25) ."px;\">\n";
    $output .= theme('comment_view', $comment, '', 0);
    $output .= "</div>\n";
  }
  return $output;
}

function phptemplate_comment_thread_max($comment, $threshold, $level = 0) {
  $output = '';
  if ($comment->depth) {
    $output .= '<div style="padding-left:'. ($comment->depth * 25) ."px;\">\n";
  }

  $output .= theme('comment_view', $comment, theme('links', module_invoke_all('link', 'comment', $comment, 0)), comment_visible($comment, $threshold));

  if ($comment->depth) {
    $output .= "</div>\n";
  }
  return $output;
}

