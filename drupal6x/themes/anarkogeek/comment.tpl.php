<div class="comment <?php print ($comment->new) ? 'comment-new' : '' ?>">
<?php if ($comment->new) : ?>
  <a id="new"></a>
  <span class="new"><?php print $new ?></span>
<?php endif; ?>

<h3 class="title"><?php print $title ?></h3>
  <?php print $picture ?>
  <div class="content"><?php print $content ?></div>
  <?php if ($picture) : ?>
    <span class="clear"></span>
  <?php endif; ?>
  <div class="info"><?php print theme('username', $comment) . ' – ' . str_replace('-', ' – ', format_date($comment->timestamp)) ?></div>
  <div class="links"><?php print $links ?></div>
</div>
