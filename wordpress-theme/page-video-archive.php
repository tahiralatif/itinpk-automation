<?php
/**
 * Template: Video Archive
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<div class="ws-hero">
    <div class="ws-hero-inner">
        <span class="ws-hero-badge">VIDEO ARCHIVE</span>
        <h1 class="ws-hero-title">All Episodes</h1>
        <p class="ws-hero-desc">Browse our complete library of tech interviews, startup stories, and live show episodes.</p>
    </div>
</div>

<div style="max-width:1200px;margin:40px auto;padding:0 28px;">
    <?php echo do_shortcode('[itinpk_video_archive]'); ?>
</div>
<?php get_footer(); ?>
