<div class="node<?php print ($sticky) ? " sticky" : ""; ?>">
  <?php if ($page == 0): ?>
    <h2 class="title"><a href="<?php print $node_url ?>" title="<?php print $title ?>"><?php print $title ?></a></h2>
  <?php endif; ?>
  <?php print $picture ?>
  
  <div class="content">
    <?php print $content ?>
  </div>
  <?php if ($picture): ?>
    <span class="clear"></span>
  <?php endif; ?>
  <?php if ($submitted): ?>
    <div class="info"><?php print theme('username', $node) . ' – ' . str_replace('-', ' – ', format_date($node->created)) ?></div>
  <?php endif; ?>
  
</div>
