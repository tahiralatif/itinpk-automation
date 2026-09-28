<?php
/**
 * Template: My Bookmarks
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<div class="contact-hero" style="background:linear-gradient(135deg,#1a1130,#37215F);">
    <div class="contact-hero-inner">
        <h1 class="contact-hero-title">My Bookmarks</h1>
        <p class="contact-hero-sub">Articles you've saved for later reading.</p>
    </div>
</div>

<div style="max-width:800px;margin:48px auto;padding:0 28px;">
    <?php echo do_shortcode('[itinpk_bookmarks]'); ?>
</div>
<?php get_footer(); ?>
