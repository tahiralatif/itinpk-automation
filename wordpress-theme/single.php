<?php
/**
 * Single post template - Two-column layout with trending sidebar.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<!-- Reading Progress Bar -->
<div class="reading-progress" id="reading-progress"></div>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

        <div class="article-layout">
            <div class="article-main">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="inside-article">
                        <header class="entry-header">
                            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                            <div class="entry-meta">
                                <span class="posted-on"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
                                <span class="byline"> &middot; iTIN Pakistan Editorial Team</span>
                                <span class="read-time"> &middot; <?php echo ceil(str_word_count(strip_tags(get_the_content())) / 200); ?> min read</span>
                            </div>
                        </header>

                        <?php itinpk_breadcrumbs(); ?>

                        <?php if ( has_post_thumbnail() ) : ?>
                        <div class="entry-featured-image">
                            <?php the_post_thumbnail( 'full', array('loading' => 'lazy') ); ?>
                            <?php
                            $image_credit = get_post_meta(get_the_ID(), '_itinpk_image_credit', true);
                            if ($image_credit) :
                            ?>
                            <div class="image-credit" style="font-size:11px;color:#999;text-align:right;padding:4px 0;font-style:italic;">
                                Image: <a href="<?php echo esc_url(get_post_meta(get_the_ID(), '_itinpk_source_url', true)); ?>" target="_blank" rel="noopener" style="color:#999;"><?php echo esc_html($image_credit); ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Social Share Bar -->
                        <?php $share_url = urlencode(get_permalink()); $share_title = urlencode(get_the_title()); ?>
                        <div class="share-bar">
                            <span class="share-label">Share:</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener" class="share-btn share-facebook" aria-label="Share on Facebook">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener" class="share-btn share-twitter" aria-label="Share on X">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $share_url; ?>&title=<?php echo $share_title; ?>" target="_blank" rel="noopener" class="share-btn share-linkedin" aria-label="Share on LinkedIn">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo $share_title . '%20' . $share_url; ?>" target="_blank" rel="noopener" class="share-btn share-whatsapp" aria-label="Share on WhatsApp">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                            <button class="share-btn share-copy" onclick="navigator.clipboard.writeText(window.location.href).then(function(){this.innerHTML='✓ Copied!';setTimeout(function(){document.querySelector('.share-copy').innerHTML='<svg viewBox=\'0 0 24 24\' fill=\'currentColor\' width=\'18\' height=\'18\'><path d=\'M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z\'/></svg>';},2000)}.bind(this))" aria-label="Copy link">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                            </button>
                        </div>

                        <div class="entry-content" itemprop="text">
                            <?php the_content(); ?>
                        </div>

                        <?php
                        // Source citation block for pipeline-generated articles
                        $source_url = get_post_meta(get_the_ID(), '_itinpk_source_url', true);
                        $source_name = get_post_meta(get_the_ID(), '_itinpk_source_name', true);
                        if ($source_url && $source_name) :
                        ?>
                        <div class="source-citation">
                            <span class="source-label">Originally reported by</span>
                            <a href="<?php echo esc_url($source_url); ?>" target="_blank" rel="noopener" class="source-link"><?php echo esc_html($source_name); ?></a>
                        </div>
                        <?php endif; ?>

                        <?php do_action('generate_after_entry_content'); ?>
                    </div>
                </article>

                <?php
                // Related Posts — same category, exclude current
                $current_id = get_the_ID();
                $current_cats = wp_get_post_categories($current_id);
                if (!empty($current_cats)) :
                    $related = new WP_Query(array(
                        'posts_per_page' => 4,
                        'category__in'   => $current_cats,
                        'post__not_in'   => array($current_id),
                        'post_status'    => 'publish',
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ));
                    if ($related->have_posts()) :
                ?>
                <div class="related-posts">
                    <h3 class="related-heading">Related Articles</h3>
                    <div class="related-grid">
                        <?php while ($related->have_posts()) : $related->the_post();
                            $r_thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                            $r_cats  = wp_get_post_categories(get_the_ID(), array('fields' => 'names'));
                        ?>
                        <article class="related-card">
                            <a href="<?php the_permalink(); ?>" class="related-card-link">
                                <div class="related-thumb">
                                    <?php if ($r_thumb) : ?>
                                        <img src="<?php echo esc_url($r_thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
                                    <?php endif; ?>
                                </div>
                                <div class="related-info">
                                    <?php if (!empty($r_cats)) : ?>
                                        <span class="related-cat"><?php echo esc_html($r_cats[0]); ?></span>
                                    <?php endif; ?>
                                    <h4 class="related-title"><?php the_title(); ?></h4>
                                    <time class="related-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
                                </div>
                            </a>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                <?php
                    endif;
                endif;
                ?>

                <?php if ( comments_open() || get_comments_number() ) : ?>
                    <?php comments_template(); ?>
                <?php endif; ?>
            </div>

            <aside class="article-sidebar">
                <h3 class="sidebar-heading">Trending Now</h3>
                <?php
                $trending = new WP_Query(array(
                    'posts_per_page' => 6,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'post_status'    => 'publish',
                    'post__not_in'   => array(get_the_ID()),
                ));
                $trending_num = 1;
                if ($trending->have_posts()) :
                    while ($trending->have_posts()) : $trending->the_post();
                        $t_title = get_the_title();
                        $t_url   = get_permalink();
                        $t_date  = get_the_date();
                        $t_thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                ?>
                <article class="trending-item">
                    <span class="trending-num"><?php printf('%02d', $trending_num); ?></span>
                    <?php if ($t_thumb) : ?>
                    <a href="<?php echo esc_url($t_url); ?>" class="trending-thumb-link">
                        <img src="<?php echo esc_url($t_thumb); ?>" alt="<?php echo esc_attr($t_title); ?>" class="trending-thumb" loading="lazy">
                    </a>
                    <?php endif; ?>
                    <div class="trending-info">
                        <h4 class="trending-title"><a href="<?php echo esc_url($t_url); ?>"><?php echo esc_html($t_title); ?></a></h4>
                        <time class="trending-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html($t_date); ?></time>
                    </div>
                </article>
                <?php
                    $trending_num++;
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </aside>
        </div>

    <?php endwhile; endif; ?>
    </main>
</div>

<?php
get_footer();
