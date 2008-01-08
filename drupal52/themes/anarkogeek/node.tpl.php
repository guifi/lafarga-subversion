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

<!-- Not themed links but they do work -->
<?php
print '<p></p>';
print '<p class="small">';
$all_links = array();
foreach ($node->links as $link) {
  $all_links[] = l($link['title'], $link['href'],
    $link['attributes'], $link['query'],
    $link['fragment'], FALSE, $link['html']) ;
}
if ( count($all_links) ) {
  print implode(' | ', $all_links);
}
print '</p>';
?>
    <div class="links"><?php print $links ?></div>
</div>
