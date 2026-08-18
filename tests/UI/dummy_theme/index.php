<?php $this->get_header(['title' => $title ?? 'Default']); ?>
<main>Content: <?php echo $content ?? ''; ?></main>
<?php $this->get_footer(); ?>
