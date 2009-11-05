<?php
// $Id: template.php,v 1.16 2007/10/11 09:51:29 goba Exp $
/**
 * Sets the body-tag class attribute.
 *
 * Adds 'sidebar-left', 'sidebar-right' or 'sidebars' classes as needed.
 */
function phptemplate_body_class($left, $right, $is_front) {
  if ($is_front){
    $class = 'sidebars-home';
  } else {
    if ($left != '' && $right != '') {
      $class = 'sidebars';
    }
    else {
      if ($left != '') {
        $class = 'sidebar-left';
      }
      if ($right != '') {
        $class = 'sidebar-right';
      }
    }
  }
  
  if (isset($class)) {
    print ' class="'. $class .'"';
  }
}
function phptemplate_sidebar_right_class($is_front) {
  if ($is_front) {
    $class = 'sidebar-right';
  }else {
    $class = 'sidebar';
  }

  if (isset($class)) {
    print ' class="'. $class .'"';
  }
}
/**
 * Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return a string containing the breadcrumb output.
 */
function phptemplate_breadcrumb($breadcrumb) {
  if (!empty($breadcrumb)) {
    return '<div class="breadcrumb">'. implode(' › ', $breadcrumb) .'</div>';
  }
}

/**
 * Allow themable wrapping of all comments.
 */
function phptemplate_comment_wrapper($content, $node) {
  if (!$content || $node->type == 'forum') {
    return '<div id="comments">'. $content .'</div>';
  }
  else {
    return '<div id="comments"><h2 class="comments">'. t('Comments') .'</h2>'. $content .'</div>';
  }
}

/**
 * Override or insert PHPTemplate variables into the templates.
 */
function phptemplate_preprocess_page(&$vars) {
//  print_r($vars);
  $vars['primary_links']   = _opensourcery_primary_links($vars['primary_links']);
  $vars['secondary_links'] = _opensourcery_secondary_links($vars['secondary_links']);

  $vars['tabs2'] = menu_secondary_local_tasks();

  // Hook into color.module
  if (module_exists('color')) {
    _color_page_alter($vars);
  }
}

function _opensourcery_primary_links($primary) {
  global $language;

  foreach ($primary as $lid => $link) {
    $link = os_translate_translate_path($link);
    $primary[$lid] = $link;
  }
  return $primary;
}

function _opensourcery_secondary_links($secondary) {
  global $language;

  // This function call will rebuild the secondary menu as if the page were in
  // English, thus solving the second issue.
  if ($language->language == 'ca') {
    $secondary = _opensourcery_rebuild_secondary_links();
  }

  foreach ($secondary as $lid => $link) {
    $link = os_translate_translate_path($link);
    $secondary[$lid] = $link;
  }
  return $secondary;
}

/**
 * Translate a link array.
 */
function os_translate_translate_path($link) {
  global $language;
  // get a list of all available paths
  $new_paths = translation_path_get_translations($link['href']);
  if ($new_paths[$language->language]) {
    // if a translated path exists, set it here
    $link['href'] = $new_paths[$language->language];
  }

  // translate the title (this adds every menu title to the locale_source
  // table, for later translation
  $link['title'] = t($link['title']);
  if ($link['attributes']['title']) {
    $link['attributes']['title'] = t($link['attributes']['title']);
  }

  return $link;
}

function _opensourcery_rebuild_secondary_links() {
  // menus are built in English, so set active trail there
  $new_paths = translation_path_get_translations($_GET['q']);

  // save current path
  $current = $_GET['q'];

  if ($new_paths['en']) {
    menu_set_active_item($new_paths['en']);
  }

  $secondary_links = menu_secondary_links();

  // reset active item
  menu_set_active_item($current);

  return $secondary_links;
}
/**
 * Returns the rendered local tasks. The default implementation renders
 * them as tabs. Overridden to split the secondary tasks.
 *
 * @ingroup themeable
 */
function phptemplate_menu_local_tasks() {
  return menu_primary_local_tasks();
}

function phptemplate_comment_submitted($comment) {
  return t('!datetime — !username',
    array(
      '!username' => theme('username', $comment),
      '!datetime' => format_date($comment->timestamp)
    ));
}

function phptemplate_node_submitted($node) {
  return t('!datetime — !username',
    array(
      '!username' => theme('username', $node),
      '!datetime' => format_date($node->created),
    ));
}

/**
 * Generates IE CSS links for LTR and RTL languages.
 */
function phptemplate_get_ie_styles() {
  global $language;

  $iecss = '<link type="text/css" rel="stylesheet" media="all" href="'. base_path() . path_to_theme() .'/fix-ie.css" />';
  if (defined('LANGUAGE_RTL') && $language->direction == LANGUAGE_RTL) {
    $iecss .= '<style type="text/css" media="all">@import "'. base_path() . path_to_theme() .'/fix-ie-rtl.css";</style>';
  }

  return $iecss;
}
