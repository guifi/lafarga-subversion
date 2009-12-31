<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//CA"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php print $language ?>" xml:lang="<?php print $language ?>">
<head>
  <title><?php print $head_title ?></title>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <?php print $head ?>
  <?php print $styles ?>
</head>
<body <?php print theme("onload_attribute"); ?>>
<table>
<tr>
		<?php if ($sidebar_left != ""): ?>
		<td class="sidebar" id="sidebar-left">
				<?php print $sidebar_left ?>
			</td>
<td class="main-content"><table width="100%"><tr><td>
<div id="header">
  <?php if ($logo) : ?>
  <a href="<?php print url() ?>" title="Index Page"><img src="<?php print($logo) ?>" alt="Logo" /></a>
  </td><td>
  <?php endif; ?>
  <img src="themes/comesfa/ipvox/same.gif">
  </td><td width="100%">
  <?php if ($site_name) : ?>
    <h1 id="site-name"><a href="<?php print url() ?>" title="Index Page"><?php print($site_name) ?></a></h1>
  <?php endif;?>
  <?php if ($site_slogan) : ?>
    <span id="site-slogan"><?php print($site_slogan) ?></span>
  <?php endif;?>

  <?php if (count($secondary_links)) : ?>
    <ul id="secondary">
    <?php foreach ($secondary_links as $link): ?>
      <li><?php print $link?></li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (count($primary_links)) : ?>
    <ul id="primary">
    <?php foreach ($primary_links as $link): ?>
      <li><?php print $link?></li>
    <?php endforeach; ?>
    <li>
      <a href="javascript:;" onclick="setActiveStyleSheet('larger'); return false;" style="font-size: 120%; vertical-align: text-bottom;" class="larger">A</a><a href="javascript:;" onclick="setActiveStyleSheet('normal'); return false;" style="font-size: 100%; vertical-align: text-bottom;" class="normal">A</a><a href="javascript:;" onclick="setActiveStyleSheet('smaller'); return false;" style="font-size: 80%; vertical-align: text-bottom;" class="smaller">A</a>
    </li>
    </ul>
  <?php endif; ?>
  <ul id="primary">
  <?php if ($search_box): ?>
	<li><form action="<?php print $search_url ?>" method="post">
		<div id="search">
			<input class="form-text" type="text" size="15" value="" name="edit[keys]" /><input class="form-submit" type="submit" value="<?php print $search_button_text ?>" />
		</div>
	</form></li>
  <?php endif; ?>
  </ul>
</div>
  <div align="right">
    <?php print theme("i18n_flags"); ?>
    <?php print theme("i18n_links",$flags,$names,$delim1,$delim2) ?>
  </div>
</td></tr>
</table>
<?php print $breadcrumb ?>
<table id="content">
	<tr>
		<?php endif; ?>		
				<td class="main-content" id="content-<?php print $layout ?>">
				<?php if ($title != ""): ?>
					<h2 class="content-title"><?php print $title ?></h2>
				<?php endif; ?>
				<?php if ($tabs != ""): ?>
					<?php print $tabs ?>
				<?php endif; ?>
				
				<?php if ($mission != ""): ?>
					<div id="mission"><?php print $mission ?></div>
				<?php endif; ?>
				
				<?php if ($help != ""): ?>
					<p id="help"><?php print $help ?></p>
				<?php endif; ?>
				
				<?php if ($messages != ""): ?>
					<div id="message"><?php print $messages ?></div>
				<?php endif; ?>
				
				<!-- start main content -->
				<?php print($content) ?>
				<!-- end main content -->
				</td><!-- mainContent -->
		<?php if ($sidebar_right != ""): ?>
		<td class="sidebar" id="sidebar-right">
				<?php print $sidebar_right ?>
		</td>
		<?php endif; ?>
	</tr>
</table>
<?php print $breadcrumb ?>
</td></tr></table>
<div id="footer">
  <?php if ($footer_message) : ?>
    <p><?php print $footer_message;?></p>
  <?php endif; ?>
Validate <a href="http://validator.w3.org/check/referer">XHTML</a> or <a href="http://jigsaw.w3.org/css-validator/check/referer">CSS</a>.
</div><!-- footer -->	
 <?php print $closure;?>
  </body>
</html>

