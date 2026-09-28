<?php
/**
 * Home template - Magazine-style homepage with ticker, hero+videos, and grid.
 *
 * @package GeneratePress
 */

get_header();
?>

<!-- BREAKING NEWS TICKER -->
<?php
$breaking_query = new WP_Query(array(
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish',
));
if ($breaking_query->have_posts()) :
?>
<section class="breaking-ticker">
    <div class="breaking-label"><span class="breaking-dot"></span>BREAKING</div>
    <div class="breaking-wrap">
        <div class="breaking-scroll">
            <?php while ($breaking_query->have_posts()) : $breaking_query->the_post(); ?>
                <a href="<?php the_permalink(); ?>" class="breaking-link"><?php the_title(); ?></a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

    <?php if ( have_posts() ) : ?>

        <?php
        the_post();
        $hero_id       = get_the_ID();
        $hero_title    = get_the_title();
        $hero_url      = get_permalink();
        $hero_cat      = get_the_category();
        $hero_cat_name = ! empty( $hero_cat ) ? $hero_cat[0]->name : '';
        $hero_catSlug  = ! empty( $hero_cat ) ? $hero_cat[0]->slug : '';
        $hero_excerpt  = get_the_excerpt();
        $hero_date     = get_the_date();
        $hero_img      = get_the_post_thumbnail_url( $hero_id, 'full' );

        $cat_colors_fallback = array(
            'it-news' => '#0881BE', 'startups-entrepreneurs' => '#37215F',
            'cybersecurity-tech' => '#e74c3c', 'ai-cloud' => '#8e44ad',
            'reviews' => '#27ae60', 'web-channel-live-shows' => '#f39c12',
        );
        $hero_fb_color = isset($cat_colors_fallback[$hero_catSlug]) ? $cat_colors_fallback[$hero_catSlug] : '#37215F';

        // Track shown post IDs to prevent duplicates across sections
        $shown_ids = array($hero_id);
        ?>

        <!-- HERO: Video Left + Top 3 Headlines Right -->
        <section class="hero-v2">
            <div class="hero-v2-wrap">
                <!-- LEFT: YouTube Video Player -->
                <div class="hero-v2-video">
                    <?php if ( function_exists( 'itinpk_render_hero_video' ) ) : ?>
                        <?php itinpk_render_hero_video(); ?>
                    <?php endif; ?>
                </div>

                <!-- RIGHT: Top 3 Headlines -->
                <div class="hero-v2-headlines">
                    <div class="hero-v2-headlines-header">
                        <h2 class="hero-v2-headlines-title">Top Stories</h2>
                        <span class="hero-v2-headlines-badge">TRENDING</span>
                    </div>
                    <?php
                    $hero_headlines_query = new WP_Query(array(
                        'posts_per_page' => 3,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'post_status'    => 'publish',
                        'post__not_in'   => $shown_ids,
                    ));
                    $hl_num = 0;
                    if ($hero_headlines_query->have_posts()) :
                        while ($hero_headlines_query->have_posts()) : $hero_headlines_query->the_post();
                            $hl_id    = get_the_ID();
                            $shown_ids[] = $hl_id;
                            $hl_title = get_the_title();
                            $hl_url   = get_permalink();
                            $hl_cat   = get_the_category();
                            $hl_cat_name = !empty($hl_cat) ? $hl_cat[0]->name : '';
                            $hl_catSlug  = !empty($hl_cat) ? $hl_cat[0]->slug : '';
                            $hl_color = isset($cat_colors_fallback[$hl_catSlug]) ? $cat_colors_fallback[$hl_catSlug] : '#0881BE';
                            $hl_excerpt = get_the_excerpt();
                            $hl_img = get_the_post_thumbnail_url($hl_id, 'medium_large');
                            $hl_num++;
                    ?>
                    <a href="<?php echo esc_url($hl_url); ?>" class="hero-v2-headline-item">
                        <span class="hero-v2-headline-num"><?php echo esc_html($hl_num); ?></span>
                        <div class="hero-v2-headline-content">
                            <span class="hero-v2-headline-cat" style="color:<?php echo esc_attr($hl_color); ?>;"><?php echo esc_html($hl_cat_name); ?></span>
                            <h3 class="hero-v2-headline-title"><?php echo esc_html($hl_title); ?></h3>
                            <p class="hero-v2-headline-excerpt"><?php echo esc_html(wp_trim_words($hl_excerpt, 12)); ?></p>
                            <span class="hero-v2-headline-time"><?php echo function_exists('itinpk_relative_time') ? esc_html(itinpk_relative_time($hl_id)) : ''; ?></span>
                        </div>
                        <?php if ($hl_img) : ?>
                            <img src="<?php echo esc_url($hl_img); ?>" alt="<?php echo esc_attr($hl_title); ?>" class="hero-v2-headline-thumb" loading="lazy" />
                        <?php endif; ?>
                    </a>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        </section>

        <!-- JUST IN: Horizontal Scroll Strip -->
        <section class="just-in-strip">
            <div class="just-in-strip-label">
                <span class="just-in-strip-dot"></span>
                JUST IN
            </div>
            <div class="just-in-strip-list">
                <?php
                $just_in_query = new WP_Query(array(
                    'posts_per_page' => 8,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'post_status'    => 'publish',
                    'post__not_in'   => $shown_ids,
                ));
                if ($just_in_query->have_posts()) :
                    $ji_num = 0;
                    while ($just_in_query->have_posts()) : $just_in_query->the_post();
                        $ji_id    = get_the_ID();
                        $shown_ids[] = $ji_id;
                        $ji_title = get_the_title();
                        $ji_url   = get_permalink();
                        $ji_cat   = get_the_category();
                        $ji_catSlug  = !empty($ji_cat) ? $ji_cat[0]->slug : '';
                        $ji_color = isset($cat_colors_fallback[$ji_catSlug]) ? $cat_colors_fallback[$ji_catSlug] : '#0881BE';
                        $ji_num++;
                ?>
                <a href="<?php echo esc_url($ji_url); ?>" class="just-in-strip-item">
                    <span class="just-in-strip-num" style="color:<?php echo esc_attr($ji_color); ?>"><?php echo esc_html($ji_num); ?></span>
                    <span class="just-in-strip-title"><?php the_title(); ?></span>
                    <span class="just-in-strip-time"><?php echo function_exists('itinpk_relative_time') ? esc_html(itinpk_relative_time($ji_id)) : ''; ?></span>
                </a>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </section>

        <!-- CATEGORY VIDEOS: Horizontal Scroll Strip -->
        <?php if ( function_exists( 'itinpk_render_category_videos_section' ) ) : ?>
            <?php itinpk_render_category_videos_section(); ?>
        <?php endif; ?>

        <?php
        // Category-wise grid sections — paired 2-column layout
        $cat_sections = array(
            array('name' => 'IT News',                'slug' => 'it-news',                'color' => '#0881BE', 'icon' => 'IT'),
            array('name' => 'Startups & Entrepreneurs', 'slug' => 'startups-entrepreneurs', 'color' => '#37215F', 'icon' => 'ST'),
            array('name' => 'Cybersecurity & Tech',   'slug' => 'cybersecurity-tech',     'color' => '#e74c3c', 'icon' => 'CS'),
            array('name' => 'AI & Cloud',             'slug' => 'ai-cloud',               'color' => '#8e44ad', 'icon' => 'AI'),
            array('name' => 'Reviews',                'slug' => 'reviews',                'color' => '#27ae60', 'icon' => 'RV'),
        );

        // Group into pairs: [0,1], [2,3], [4]
        $cat_pairs = array();
        for ($i = 0; $i < count($cat_sections); $i += 2) {
            $pair = array($cat_sections[$i]);
            if ($i + 1 < count($cat_sections)) {
                $pair[] = $cat_sections[$i + 1];
            }
            $cat_pairs[] = $pair;
        }
        ?>

        <?php foreach ($cat_pairs as $pair) : ?>
        <div class="cat-pair-row">
        <?php foreach ($pair as $section) :
            $cat_q = new WP_Query(array(
                'category_name'  => $section['slug'],
                'posts_per_page' => 3,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post_status'    => 'publish',
                'post__not_in'   => $shown_ids,
            ));
            if (!$cat_q->have_posts()) continue;
            $cat_term = get_term_by('slug', $section['slug'], 'category');
            $cat_total = $cat_term ? $cat_term->count : 0;
        ?>
        <section class="cat-section cat-section-half">
            <div class="cat-section-header">
                <h2 class="cat-section-heading" style="border-color: <?php echo esc_attr($section['color']); ?>">
                    <span class="cat-section-icon" style="background-color: <?php echo esc_attr($section['color']); ?>"><?php echo esc_html($section['icon']); ?></span>
                    <?php echo esc_html($section['name']); ?>
                    <span class="cat-article-count">(<?php echo intval($cat_total); ?>)</span>
                </h2>
                <a href="<?php echo esc_url(get_category_link(get_cat_ID($section['name']))); ?>" class="cat-section-viewall">View All <span class="viewall-arrow">&rarr;</span></a>
            </div>
            <div class="article-grid cat-grid cat-grid-half">
            <?php $card_num = 0; while ($cat_q->have_posts()) : $cat_q->the_post();
                $post_id       = get_the_ID();
                $shown_ids[]   = $post_id;
                $post_title    = get_the_title();
                $post_url      = get_permalink();
                $post_cat      = get_the_category();
                $post_cat_name = !empty($post_cat) ? $post_cat[0]->name : '';
                $post_catSlug  = !empty($post_cat) ? $post_cat[0]->slug : '';
                $post_excerpt  = get_the_excerpt();
                $post_date     = get_the_date();
                $post_img      = get_the_post_thumbnail_url($post_id, 'medium_large');
                $badge_color   = isset($cat_colors_fallback[$post_catSlug]) ? $cat_colors_fallback[$post_catSlug] : '#0881BE';
                $card_class    = ($card_num === 0) ? 'article-card card-featured' : 'article-card';
                $card_num++;
            ?>
                <article class="<?php echo esc_attr($card_class); ?>">
                    <a href="<?php echo esc_url($post_url); ?>" class="card-link">
                        <div class="card-image">
                            <?php if ($post_img) : ?>
                                <img src="<?php echo esc_url($post_img); ?>" alt="<?php echo esc_attr($post_title); ?>" loading="lazy" />
                            <?php else : ?>
                                <div class="card-placeholder"></div>
                            <?php endif; ?>
                            <?php if ($post_cat_name) : ?>
                                <span class="cat-badge cat-badge-<?php echo esc_attr($post_catSlug); ?>" style="background-color: <?php echo esc_attr($badge_color); ?>;"><?php echo esc_html($post_cat_name); ?></span>
                            <?php endif; ?>
                            <?php if (function_exists('itinpk_is_new') && itinpk_is_new($post_id)) : ?>
                                <span class="new-badge">NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title"><?php echo esc_html($post_title); ?></h3>
                            <time class="card-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>" title="<?php echo esc_attr(get_the_date('F j, Y g:i A')); ?>"><?php echo function_exists('itinpk_relative_time') ? esc_html(itinpk_relative_time($post_id)) : esc_html($post_date); ?></time>
                            <p class="card-excerpt"><?php echo esc_html(wp_trim_words($post_excerpt, 18)); ?></p>
                        </div>
                    </a>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

    <?php else : ?>
        <?php get_template_part( 'no-results' ); ?>
    <?php endif; ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();
