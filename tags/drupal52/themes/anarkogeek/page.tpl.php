<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php print $language; ?>" xml:lang="<?php print $language; ?>">
<head>
  <title><?php print $head_title ?></title>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <?php print $head ?>
  <?php print $styles ?>
  <!--[if IE]>
	    <style type="text/css">
	      @import "<?php print base_path(). path_to_theme(); ?>/ie-fixes.css";</style>
	  <![endif]-->
	<!--[if IE 7]>
	    <style type="text/css">
	      @import "<?php print base_path(). path_to_theme(); ?>/ie7-fixes.css";</style>
	  <![endif]-->
	
  <?php print $scripts ?>
  <script type="text/javascript"> </script>
</head>
<body>
<?php print $fontsize_init ?>
<div class="bw1"><div class="bw2"><div id="body-wrap">

<div id="header"><div class="hw1"><div class="hw2">
  <?php if ($logo): ?>
    <a href="<?php print $base_path ?>" title="<?php print t('Home') ?>"><img src="<?php print $logo ?>" alt="<?php print t('Home') ?>" id="site-logo" /></a>
  <?php endif; ?>
  <?php if ($site_name): ?>
    <h1 id="site-name" class="<?php print $site_slogan ? 'with-slogan' : 'without-slogan'; ?>"><a href="<?php print $base_path ?>" title="<?php print t('Home') ?>"><?php print $site_name ?></a></h1>
  <?php endif; ?>
  <?php if ($site_slogan): ?>
    <span id="site-slogan"><?php print $site_slogan; ?></span>
  <?php endif; ?>
  <?php print $search_box ?>

  <div id="top-nav">
    <?php if (is_array($primary_links) && !empty($primary_links)): ?>
      <ul id="primary">
      <?php foreach ($primary_links as $link): ?>
        <li><?php print phptemplate_wrap_links($link, 2); ?></li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (is_array($secondary_links) && !empty($secondary_links)): ?>
      <ul id="secondary">
     <li>
     <a href="ca"><img src="<?php print base_path(). drupal_get_path('module', 'i18n'); ?>/flags/ca.png"  class="i18n-flag" width="16" height="12" alt="Catalan" /> </a> 
     <a href="en"><img src="<?php print base_path(). drupal_get_path('module', 'i18n'); ?>/flags/en.png"  class="i18n-flag" width="16" height="12" alt="English" /> </a>
     <a href="en"><img src="<?php print base_path(). drupal_get_path('module', 'i18n'); ?>/flags/es.png"  class="i18n-flag" width="16" height="12" alt="Spanish" /> </a>
     </li>
     <li><?php print $fontsize_links ?></li>

    <?php foreach (array_reverse($secondary_links) as $link): ?>
      <li><?php print phptemplate_wrap_links($link, 2); ?></li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div></div></div>

<div id="content" class="content-<?php print $layout; ?>">
    <div class="cw1"><div class="cw2"><div class="cw3"><div class="cw4"><div class="cw5"><div class="cw7">
 <div id="content-wrap" class="content-wrap-<?php print $layout; ?>">
  <?php if ($sidebar_left != ""): ?>
    <div class="sidebar" id="sidebar-left">
      <?php 
        // Mark first block title
        list($a, $b) = explode('<h2>', $sidebar_left, 2);
        print $a . '<h2 class="first">' . $b;
      ?>
    </div>
  <?php endif; ?>    
  <div id="main" class="main-<?php print $layout; ?>"><div id="main-wrap" class="main-wrap-<?php print $layout; ?>"><div class="mw1">
    <?php print $header ?>
    
    <?php if ($mission != ""): ?>
      <div id="mission"><div class="sw1"><div class="sw2"><div class="sw3"><?php print $mission; ?></div></div></div></div>
    <?php endif; ?>

    <?php print $breadcrumb ?>

    <?php if ($title): ?>
      <h2 class="main-title"><?php print $title; ?></h2>
    <?php endif; ?>

    <?php if ($tabs): ?>
      <?php print $tabs; ?>
    <?php endif; ?>
       
    <?php if ($help): ?>
      <p id="help"><?php print $help; ?></p>
    <?php endif; ?>
        
    <?php if ($messages): ?>
      <div id="message"><?php print $messages; ?></div>
    <?php endif; ?>
        
    <?php print phptemplate_wrap_content($content) ?>
 
    <?php if ($footer_message): ?>
      <div id="footer" class="footer-<?php print $layout; ?>"><p><?php print $footer_message; ?></p></div>
    <?php endif; ?>
  </div></div>
 </div>
</div>
 <?php if ($sidebar_right): ?>
 <div class="sidebar" id="sidebar-right">
   <span id="sidebar-right-top"></span>
   <?php
     // Mark first block title
     list($a, $b) = explode('<h2>', $sidebar_right, 2);
     print $a . '<h2 class="first">' . $b;
   ?>
 </div>
 <?php endif; ?>    
 <span class="clear"></span>
</div></div></div></div></div></div></div>

<?php print $closure;?>
</div></div></div><div id="end" class="end-<?php print $layout; ?>"><div class="ew1"><div class="ew2"></div></div></div></body>
</html>
