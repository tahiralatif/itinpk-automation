<?php
/**
 * Category Template — Startups & Entrepreneurs
 *
 * @package GeneratePress_Child
 */

get_header();

$category = get_queried_object();
$cat_name = $category->name;
$cat_slug = $category->slug;
$cat_id = $category->term_id;
?>

<!-- Breaking News Ticker -->
<?php
$ticker_query = new WP_Query(array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'cat'            => $cat_id,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
));
if ($ticker_query->have_posts()) :
?>
<div class="breaking-ticker">
    <div class="ticker-label" style="background:#37215F;">
        <span class="ticker-dot"></span>
        STARTUPS
    </div>
    <div class="ticker-track">
        <div class="ticker-content">
            <?php while ($ticker_query->have_posts()) : $ticker_query->the_post(); ?>
                <a href="<?php the_permalink(); ?>" class="ticker-item">
                    <?php the_title(); ?>
                    <span class="ticker-sep">&bull;</span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php wp_reset_postdata(); endif; ?>

<!-- Breadcrumbs -->
<nav class="yoast-breadcrumb" style="max-width:1200px;margin:0 auto;padding:10px 28px;font-size:13px;color:#999;">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#999;text-decoration:none;">Home</a>
    <span style="margin:0 6px;">›</span>
    <span style="color:#333;"><?php echo esc_html( $cat_name ); ?></span>
</nav>

<!-- Category Header -->
<div class="cat-header" style="border-left-color:#37215F;">
    <div class="cat-header-inner">
        <div class="cat-header-left">
            <h1 class="cat-title">
                <span class="cat-icon" style="background:#37215F;">&#128640;</span>
                <?php echo esc_html($cat_name); ?>
            </h1>
            <p class="cat-desc">Tracking Pakistan's startup ecosystem — funding rounds, venture capital, founders, and entrepreneurial innovation.</p>
        </div>
        <div class="cat-header-right">
            <div class="cat-stats">
                <?php
                $total_posts = wp_count_posts('post');
                $cat_count = $category->count;
                ?>
                <span class="stat-item"><?php echo esc_html($cat_count); ?> Articles</span>
                <span class="stat-sep">|</span>
                <span class="stat-item"><?php echo esc_html(date('M d, Y')); ?></span>
            </div>
            <div class="cat-nav">
                <?php
                $sibling_cats = get_categories(array(
                    'hide_empty' => true,
                    'number'     => 6,
                ));
                foreach ($sibling_cats as $sib_cat) :
                    $active = ($sib_cat->term_id === $cat_id) ? ' active' : '';
                ?>
                    <a href="<?php echo esc_url(get_category_link($sib_cat->term_id)); ?>" class="cat-nav-item<?php echo $active; ?>">
                        <?php echo esc_html($sib_cat->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="it-news-container">

<?php
// Hero + Sidebar Layout
$hero_query = new WP_Query(array(
    'post_type'      => 'post',
    'posts_per_page' => 1,
    'cat'            => $cat_id,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
));

if ($hero_query->have_posts()) :
    while ($hero_query->have_posts()) : $hero_query->the_post();
        $hero_id = get_the_ID();
        $hero_img = get_the_post_thumbnail_url($hero_id, 'full');
        $hero_source = get_post_meta($hero_id, '_itinpk_source_name', true);
        $hero_source_url = get_post_meta($hero_id, '_itinpk_source_url', true);
        $hero_cats = get_the_category($hero_id);
        $hero_cat_name = !empty($hero_cats) ? $hero_cats[0]->name : '';
        $hero_cat_slug = !empty($hero_cats) ? $hero_cats[0]->slug : '';
?>
<div class="hero-section">
    <a href="<?php the_permalink(); ?>" class="hero-article">
        <div class="hero-image-wrap">
            <?php if ($hero_img) : ?>
                <img src="<?php echo esc_url($hero_img); ?>" alt="<?php the_title_attribute(); ?>" class="hero-img" />
            <?php endif; ?>
            <div class="hero-overlay"></div>
            <div class="hero-badge" style="background:#37215F;">
                <span class="badge-dot"></span>
                LATEST
            </div>
            <div class="hero-content">
                <div class="hero-meta-top">
                    <?php if ($hero_cat_name) : ?>
                        <span class="hero-cat-badge"><?php echo esc_html($hero_cat_name); ?></span>
                    <?php endif; ?>
                    <span class="hero-time"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'); ?></span>
                </div>
                <h2 class="hero-title"><?php the_title(); ?></h2>
                <p class="hero-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?></p>
                <div class="hero-meta-bottom">
                    <?php if ($hero_source) : ?>
                        <span class="hero-source">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                            <?php if ($hero_source_url) : ?>
                                <a href="<?php echo esc_url($hero_source_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($hero_source); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($hero_source); ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <span class="hero-read-time">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php echo esc_html(ceil(str_word_count(wp_strip_all_tags(get_the_content())) / 200)); ?> min read
                    </span>
                </div>
            </div>
        </div>
    </a>
</div>
<?php
    endwhile;
    wp_reset_postdata();
endif;
?>

<!-- Just In Strip -->
<div class="just-in-strip" style="border-left-color:#37215F;">
    <div class="just-in-strip-label" style="background:#37215F;">
        <span class="just-in-strip-dot"></span>JUST IN
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

<!-- Two Column Layout: Main + Sidebar -->
<div class="news-two-col">

<!-- Main Content Column -->
<div class="news-main-col">

<!-- Latest News Grid -->
<div class="section-header">
    <h2 class="section-title">
        <span class="title-bar" style="background:#37215F;"></span>
        Latest Stories
    </h2>
</div>

<div class="news-grid" id="news-grid">
<?php
$grid_query = new WP_Query(array(
    'post_type'      => 'post',
    'posts_per_page' => 9,
    'cat'            => $cat_id,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'paged'          => get_query_var('paged') ?: 1,
));

$grid_count = 0;
if ($grid_query->have_posts()) :
    while ($grid_query->have_posts()) : $grid_query->the_post();
        $grid_count++;
        $post_id = get_the_ID();
        $post_img = get_the_post_thumbnail_url($post_id, 'medium_large');
        $source_name = get_post_meta($post_id, '_itinpk_source_name', true);
        $source_url = get_post_meta($post_id, '_itinpk_source_url', true);
        $post_cats = get_the_category($post_id);
        $post_cat_name = !empty($post_cats) ? $post_cats[0]->name : '';
        $post_cat_slug = !empty($post_cats) ? $post_cats[0]->slug : '';
        $read_time = ceil(str_word_count(wp_strip_all_tags(get_the_content())) / 200);
        
        $card_class = ($grid_count === 1) ? 'grid-card grid-card-featured' : 'grid-card';
?>
    <article class="<?php echo esc_attr($card_class); ?>">
        <a href="<?php the_permalink(); ?>" class="grid-card-link">
            <div class="grid-card-image">
                <?php if ($post_img) : ?>
                    <img src="<?php echo esc_url($post_img); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
                <?php endif; ?>
                <?php if ($post_cat_name) : ?>
                    <span class="grid-cat-badge"><?php echo esc_html($post_cat_name); ?></span>
                <?php endif; ?>
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
<?php
    endwhile;
    wp_reset_postdata();
endif;
?>
</div>

<!-- Pagination -->
<?php if ($grid_query->max_num_pages > 1) : ?>
<div class="news-pagination">
    <?php
    echo paginate_links(array(
        'total'     => $grid_query->max_num_pages,
        'current'   => get_query_var('paged') ?: 1,
        'prev_text' => '&laquo; Prev',
        'next_text' => 'Next &raquo;',
    ));
    ?>
</div>
<?php endif; ?>

</div>

<!-- Sidebar -->
<aside class="news-sidebar">

<!-- Trending Section -->
<div class="sidebar-widget">
    <h3 class="widget-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 6l-9.5 9.5-5-5L1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        Trending Now
    </h3>
    <div class="trending-list">
        <?php
        $trending_query = new WP_Query(array(
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'cat'            => $cat_id,
            'post_status'    => 'publish',
            'orderby'        => 'comment_count',
            'order'          => 'DESC',
        ));
        $t_num = 0;
        if ($trending_query->have_posts()) :
            while ($trending_query->have_posts()) : $trending_query->the_post();
                $t_num++;
        ?>
            <a href="<?php the_permalink(); ?>" class="trending-item">
                <span class="trending-num"><?php echo esc_html($t_num); ?></span>
                <div class="trending-info">
                    <span class="trending-title"><?php the_title(); ?></span>
                    <span class="trending-time"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'); ?></span>
                </div>
            </a>
        <?php
            endwhile;
            wp_reset_postdata();
        endif;
        ?>
    </div>
</div>

<!-- Most Read This Week -->
<div class="sidebar-widget">
    <h3 class="widget-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Most Read
    </h3>
    <div class="most-read-list">
        <?php
        $most_read_query = new WP_Query(array(
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'cat'            => $cat_id,
            'post_status'    => 'publish',
            'orderby'        => 'rand',
            'date_query'     => array(
                array('after' => '7 days ago'),
            ),
        ));
        $mr_num = 0;
        if ($most_read_query->have_posts()) :
            while ($most_read_query->have_posts()) : $most_read_query->the_post();
                $mr_num++;
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
        <?php
            endwhile;
            wp_reset_postdata();
        endif;
        ?>
    </div>
</div>

<!-- All Categories -->
<div class="sidebar-widget">
    <h3 class="widget-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
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

</div><!-- .news-two-col -->
</div><!-- .it-news-container -->

<?php get_footer(); ?>
