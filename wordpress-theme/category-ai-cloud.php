<?php
/**
 * Category Template — AI & Cloud
 * Unique purple/indigo tech theme
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$category = get_queried_object();
$cat_id = $category->term_id;
$cat_color = '#8e44ad';
?>

<!-- Breaking News Ticker -->
<?php
$ticker_query = new WP_Query(array(
    'post_type' => 'post', 'posts_per_page' => 10, 'cat' => $cat_id,
    'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
));
if ($ticker_query->have_posts()) :
?>
<div class="breaking-ticker ai-ticker">
    <div class="ticker-label" style="background:#1a1130;">
        <span class="ticker-dot" style="background:#a855f7;"></span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/><path d="M8 14s0 4 4 4 4-4 4-4"/></svg>
        AI FEED
    </div>
    <div class="ticker-track">
        <div class="ticker-content">
            <?php while ($ticker_query->have_posts()) : $ticker_query->the_post(); ?>
                <a href="<?php the_permalink(); ?>" class="ticker-item"><?php the_title(); ?><span class="ticker-sep">&bull;</span></a>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php wp_reset_postdata(); endif; ?>

<!-- AI Hero Section -->
<div class="ai-hero-v2">
    <div class="ai-hero-v2-bg"></div>
    <div class="ai-hero-v2-inner">
        <div class="ai-hero-v2-left">
            <div class="ai-brain-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/><path d="M8 14s0 4 4 4 4-4 4-4"/><line x1="9" y1="18" x2="9" y2="22"/><line x1="15" y1="18" x2="15" y2="22"/><path d="M12 22v-2"/><circle cx="12" cy="6" r="1"/></svg>
            </div>
            <h1 class="ai-hero-v2-title">AI & Cloud</h1>
            <p class="ai-hero-v2-desc">Artificial intelligence breakthroughs, cloud infrastructure, and machine learning innovations transforming Pakistan</p>
            <div class="ai-hero-v2-stats">
                <span class="ai-v2-stat">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/></svg>
                    <?php echo esc_html($category->count); ?> Articles
                </span>
                <span class="ai-v2-stat-sep"></span>
                <span class="ai-v2-stat"><?php echo esc_html(date('M d, Y')); ?></span>
            </div>
        </div>
        <div class="ai-hero-v2-right">
            <div class="ai-model-card">
                <span class="ai-model-label">TRENDING MODELS</span>
                <div class="ai-model-list">
                    <span class="ai-model-tag">GPT-5</span>
                    <span class="ai-model-tag">Claude 4</span>
                    <span class="ai-model-tag">Gemini 2</span>
                    <span class="ai-model-tag">Llama 4</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="it-news-container ai-page-v2">

<?php
// Hero Article
$hero_query = new WP_Query(array(
    'post_type' => 'post', 'posts_per_page' => 1, 'cat' => $cat_id,
    'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
));
if ($hero_query->have_posts()) :
    while ($hero_query->have_posts()) : $hero_query->the_post();
        $hero_id = get_the_ID();
        $hero_img = get_the_post_thumbnail_url($hero_id, 'full');
        $hero_source = get_post_meta($hero_id, '_itinpk_source_name', true);
        $hero_source_url = get_post_meta($hero_id, '_itinpk_source_url', true);
?>
<div class="hero-section ai-hero-section">
    <a href="<?php the_permalink(); ?>" class="hero-article">
        <div class="hero-image-wrap">
            <?php if ($hero_img) : ?>
                <img src="<?php echo esc_url($hero_img); ?>" alt="<?php the_title_attribute(); ?>" class="hero-img" />
            <?php endif; ?>
            <div class="hero-overlay"></div>
            <div class="hero-badge ai-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/></svg>
                AI UPDATE
            </div>
            <div class="hero-content">
                <div class="hero-meta-top">
                    <span class="hero-cat-badge ai-cat-badge">AI & Cloud</span>
                    <span class="hero-time"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'); ?></span>
                </div>
                <h2 class="hero-title"><?php the_title(); ?></h2>
                <p class="hero-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?></p>
                <div class="hero-meta-bottom">
                    <?php if ($hero_source) : ?>
                        <span class="hero-source">
                            <?php if ($hero_source_url) : ?>
                                <a href="<?php echo esc_url($hero_source_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($hero_source); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($hero_source); ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <span class="hero-read-time"><?php echo esc_html(ceil(str_word_count(wp_strip_all_tags(get_the_content())) / 200)); ?> min read</span>
                </div>
            </div>
        </div>
    </a>
</div>
<?php endwhile; wp_reset_postdata(); endif; ?>

<!-- Just In Strip -->
<div class="just-in-strip ai-just-in">
    <div class="just-in-strip-label" style="background:#1a1130;">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="#a855f7" stroke="none"><circle cx="12" cy="12" r="10"/></svg>
        JUST IN
    </div>
    <div class="just-in-strip-list">
        <?php
        $ji_query = new WP_Query(array(
            'post_type' => 'post', 'posts_per_page' => 5, 'cat' => $cat_id,
            'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
        ));
        $ji_num = 0;
        if ($ji_query->have_posts()) : while ($ji_query->have_posts()) : $ji_query->the_post();
            $ji_num++;
        ?>
            <a href="<?php the_permalink(); ?>" class="just-in-strip-item">
                <span class="just-in-strip-num"><?php echo esc_html($ji_num); ?></span>
                <span class="just-in-strip-title"><?php the_title(); ?></span>
                <span class="just-in-strip-time"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?></span>
            </a>
        <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
</div>

<!-- Two Column Layout -->
<div class="news-two-col">
<div class="news-main-col">

<div class="section-header ai-section-header">
    <h2 class="section-title">
        <span class="title-bar" style="background:#8e44ad;"></span>Latest in AI
    </h2>
</div>

<div class="news-grid">
<?php
$grid_query = new WP_Query(array(
    'post_type' => 'post', 'posts_per_page' => 9, 'cat' => $cat_id,
    'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
    'paged' => get_query_var('paged') ?: 1,
));
$grid_count = 0;
if ($grid_query->have_posts()) : while ($grid_query->have_posts()) : $grid_query->the_post();
    $grid_count++;
    $post_id = get_the_ID();
    $post_img = get_the_post_thumbnail_url($post_id, 'medium_large');
    $source_name = get_post_meta($post_id, '_itinpk_source_name', true);
    $source_url = get_post_meta($post_id, '_itinpk_source_url', true);
    $read_time = ceil(str_word_count(wp_strip_all_tags(get_the_content())) / 200);
    $card_class = ($grid_count === 1) ? 'grid-card grid-card-featured' : 'grid-card';
?>
    <article class="<?php echo esc_attr($card_class); ?>">
        <a href="<?php the_permalink(); ?>" class="grid-card-link">
            <div class="grid-card-image">
                <?php if ($post_img) : ?>
                    <img src="<?php echo esc_url($post_img); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
                <?php endif; ?>
                <span class="grid-cat-badge ai-grid-badge">AI & Cloud</span>
                <?php if (function_exists('itinpk_is_new') && itinpk_is_new($post_id)) : ?>
                    <span class="new-badge">NEW</span>
                <?php endif; ?>
            </div>
            <div class="grid-card-body">
                <h3 class="grid-card-title"><?php the_title(); ?></h3>
                <p class="grid-card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 15)); ?></p>
                <div class="grid-card-meta">
                    <span class="grid-card-time"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'); ?></span>
                    <?php if ($source_name) : ?>
                        <span class="grid-card-source">
                            <?php if ($source_url) : ?>
                                <a href="<?php echo esc_url($source_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($source_name); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($source_name); ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <span class="grid-card-read"><?php echo esc_html($read_time); ?>m</span>
                </div>
            </div>
        </a>
    </article>
<?php endwhile; wp_reset_postdata(); endif; ?>
</div>

<?php if ($grid_query->max_num_pages > 1) : ?>
<div class="news-pagination">
    <?php echo paginate_links(array(
        'total' => $grid_query->max_num_pages,
        'current' => get_query_var('paged') ?: 1,
        'prev_text' => '&laquo; Prev', 'next_text' => 'Next &raquo;',
    )); ?>
</div>
<?php endif; ?>

</div>

<!-- Sidebar -->
<aside class="news-sidebar">
<div class="sidebar-widget ai-sidebar-widget">
    <h3 class="widget-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8e44ad" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/><path d="M8 14s0 4 4 4 4-4 4-4"/></svg>
        AI Trending
    </h3>
    <div class="trending-list">
        <?php
        $trending_query = new WP_Query(array(
            'post_type' => 'post', 'posts_per_page' => 5, 'cat' => $cat_id,
            'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC',
        ));
        $t_num = 0;
        if ($trending_query->have_posts()) : while ($trending_query->have_posts()) : $trending_query->the_post();
            $t_num++;
        ?>
            <a href="<?php the_permalink(); ?>" class="trending-item">
                <span class="trending-num" style="color:#8e44ad;"><?php echo esc_html($t_num); ?></span>
                <div class="trending-info">
                    <span class="trending-title"><?php the_title(); ?></span>
                    <span class="trending-time"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'); ?></span>
                </div>
            </a>
        <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
</div>

<div class="sidebar-widget ai-sidebar-widget">
    <h3 class="widget-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8e44ad" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Most Read
    </h3>
    <div class="most-read-list">
        <?php
        $mr_query = new WP_Query(array(
            'post_type' => 'post', 'posts_per_page' => 5, 'cat' => $cat_id,
            'post_status' => 'publish', 'orderby' => 'rand',
            'date_query' => array(array('after' => '7 days ago')),
        ));
        if ($mr_query->have_posts()) : while ($mr_query->have_posts()) : $mr_query->the_post();
            $mr_img = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            $mr_source = get_post_meta(get_the_ID(), '_itinpk_source_name', true);
        ?>
            <a href="<?php the_permalink(); ?>" class="most-read-item">
                <?php if ($mr_img) : ?>
                    <img src="<?php echo esc_url($mr_img); ?>" alt="" class="most-read-thumb" loading="lazy" />
                <?php endif; ?>
                <div class="most-read-info">
                    <span class="most-read-title"><?php the_title(); ?></span>
                    <div class="most-read-meta">
                        <?php if ($mr_source) : ?>
                            <span class="most-read-source"><?php echo esc_html($mr_source); ?></span>
                        <?php endif; ?>
                        <span class="most-read-time"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?></span>
                    </div>
                </div>
            </a>
        <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
</div>

<div class="sidebar-widget ai-sidebar-widget">
    <h3 class="widget-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8e44ad" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Categories
    </h3>
    <div class="category-list">
        <?php
        $all_cats = get_categories(array('hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC'));
        foreach ($all_cats as $a_cat) :
            $cat_icon = '';
            switch ($a_cat->slug) {
                case 'it-news': $cat_icon = '&#128187;'; break;
                case 'startups-entrepreneurs': $cat_icon = '&#128640;'; break;
                case 'cybersecurity-tech': $cat_icon = '&#128274;'; break;
                case 'ai-cloud': $cat_icon = '&#129302;'; break;
                case 'reviews': $cat_icon = '&#128247;'; break;
                default: $cat_icon = '&#128196;'; break;
            }
        ?>
            <a href="<?php echo esc_url(get_category_link($a_cat->term_id)); ?>" class="category-item<?php echo ($a_cat->term_id === $cat_id) ? ' active' : ''; ?>">
                <span class="cat-item-icon"><?php echo $cat_icon; ?></span>
                <span class="cat-item-name"><?php echo esc_html($a_cat->name); ?></span>
                <span class="cat-item-count"><?php echo esc_html($a_cat->count); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
</aside>
</div>
</div>

<?php get_footer(); ?>
