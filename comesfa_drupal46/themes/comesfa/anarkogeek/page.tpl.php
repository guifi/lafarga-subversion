<?php global $filter_ip; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php print $language; ?>" xml:lang="<?php print $language; ?>">
<head>
  <title><?php print $head_title ?></title>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <?php print $head ?>
  <?php print $styles ?>
  <script type="text/javascript"> </script>
</head>
<body>
<br<?php print theme("onload_attribute"); ?>><div class="bw1"><div class="bw2"><div id="body-wrap">

<div id="header"><div class="hw1"><div class="hw2">
  <?php if ($logo): ?>
    <a href="<?php print url(); ?>" title="Index Page"><img src="<?php print $logo; ?>" alt="Logo" id="site-logo" /></a>
  <?php endif; ?>
  <?php if ($site_name): ?>
    <h1 id="site-name" class="<?php print $site_slogan ? 'with-slogan' : 'without-slogan'; ?>"><a href="<?php print url(); ?>" title="Index Page"><?php print $site_name; ?></a></h1>
  <?php endif;?>
  <?php if ($site_slogan): ?>
    <span id="site-slogan"><?php print $site_slogan; ?></span>
  <?php endif;?>
  <?php if ($search_box): ?>
  <form action="<?php print $search_url; ?>" method="post">
    <div id="search">
      <input class="form-text" type="text" size="15" value="" name="edit[keys]" /><input class="form-submit" type="submit" value="<?php print ucfirst($search_button_text); ?>" />
    </div>
  </form>
  <?php endif; ?>

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
      <li><?php  print theme("i18n_flags"); ?></li>
      <li><a href="javascript:;" onclick="setActiveStyleSheet('larger'); return false;" style="font-size: 120%; vertical-align: text-top;" class="larger"><span class="lw1"><span class="lw2">A</span></span></a></li>
      <li><a href="javascript:;" onclick="setActiveStyleSheet('normal'); return false;" style="font-size: 100%; vertical-align: text-bottom;" class="normal"><span class="lw1"><span class="lw2">A</span></span></a></li>
      <li><a href="javascript:;" onclick="setActiveStyleSheet('smaller'); return false;" style="font-size: 80%; vertical-align: text-bottom;" class="smaller"><span class="lw1"><span class="lw2">A</span></span></a></li>
      <?php foreach (array_reverse($secondary_links) as $link): ?>
        <li><?php print phptemplate_wrap_links($link, 2); ?></li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

</div></div></div>

<div id="content" class="content-<?php print $layout; ?>"><div class="cw1"><div class="cw2"><div class="cw3"><div class="cw4"><div class="cw5"><div class="cw6"><div class="cw7"><div class="cw8">
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
    <?php if ($mission != ""): ?>
      <div id="mission"><div class="sw1"><div class="sw2"><div class="sw3"><?php print $mission; ?></div></div></div></div>
    <?php endif; ?>

<?php if ( !$filter_ip ): ?>
<center>
<script type="text/javascript"><!--
google_ad_client = "pub-3241715727040799";
google_ad_width = 468;
google_ad_height = 60;
google_ad_format = "468x60_as";
google_ad_type = "text";
//2007-05-29: Superior
google_ad_channel = "0189167230";
//-->
</script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
</center><br>
<?php endif; ?>
    
    <?php print $breadcrumb ?>

    <?php if ($title != ""): ?>
      <h2 class="main-title"><?php print $title; ?></h2>
    <?php endif; ?>

    <?php if ($tabs != ""): ?>
      <?php print $tabs; ?>
    <?php endif; ?>
       
    <?php if ($help != ""): ?>
      <p id="help"><?php print $help; ?></p>
    <?php endif; ?>
        
    <?php if ($messages != ""): ?>
      <div id="message"><?php print $messages; ?></div>
    <?php endif; ?>
        
    <?php print phptemplate_wrap_content($content) ?>

<?php if ( !$filter_ip ): ?>
<br><center>
<script type="text/javascript"><!--
google_ad_client = "pub-3241715727040799";
google_ad_width = 468;
google_ad_height = 60;
google_ad_format = "468x60_as";
google_ad_type = "text";
//2007-05-29: Inferior
google_ad_channel = "1806657103";
//-->
</script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
</center>
<?php endif; ?>    
 
    <?php if ($footer_message): ?><div id="footer" class="footer-<?php print $layout; ?>"><p><?php print $footer_message; ?></p></div><?php endif; ?>
  </div></div>
 </div>
</div>
 <?php if ($sidebar_right != ""): ?>
 <div class="sidebar" id="sidebar-right">
   <?php
     // Mark first block title
     list($a, $b) = explode('<h2>', $sidebar_right, 2);
     print $a . '<h2 class="first">' . $b;
   ?>
 </div>
 <?php endif; ?>    
 <span class="clear"></span>
</div></div></div></div></div></div></div></div></div>

      <?php print $closure;?></div>
  </div></div><div id="end" class="end-<?php print $layout; ?>"><div class="ew1"><div class="ew2"></div></div></div>
<br>

<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-2026473-1";
urchinTracker();
</script>

</body>
</html>
