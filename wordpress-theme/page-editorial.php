<?php
/**
 * Template Name: Editorial Team
 * Shows all articles by iTIN Pakistan Editorial Team
 */

get_header();
?>

<div class="ws-hero" style="background: linear-gradient(135deg, #37215F 0%, #0881BE 100%);">
    <div class="ws-hero-inner">
        <div class="ws-hero-badge">OUR TEAM</div>
        <h1 class="ws-hero-title">iTIN Pakistan Editorial Team</h1>
        <p class="ws-hero-desc">Dedicated to delivering the latest technology news, startup insights, and cybersecurity updates from Pakistan and beyond.</p>
    </div>
</div>

<div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px;">
        <div>
            <h2 style="font-size: 22px; font-weight: 800; color: #1a1a2e; margin: 0 0 24px;">All Articles</h2>
            <div style="display: grid; gap: 20px;">
                <?php
                $posts = new WP_Query(array(
                    'posts_per_page' => 20,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));
                
                if ($posts->have_posts()) :
                    while ($posts->have_posts()) : $posts->the_post();
                        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                        $cats = wp_get_post_categories(get_the_ID(), array('fields' => 'names'));
                        $source_url = get_post_meta(get_the_ID(), '_itinpk_source_url', true);
                        $source_name = get_post_meta(get_the_ID(), '_itinpk_source_name', true);
                ?>
                <article style="display: flex; gap: 16px; background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                    <?php if ($thumb) : ?>
                    <a href="<?php the_permalink(); ?>" style="flex-shrink: 0;">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" style="width: 120px; height: 80px; object-fit: cover; border-radius: 6px;" loading="lazy" />
                    </a>
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <?php if (!empty($cats)) : ?>
                            <span style="font-size: 11px; font-weight: 700; color: #0881BE; text-transform: uppercase;"><?php echo esc_html($cats[0]); ?></span>
                        <?php endif; ?>
                        <h3 style="font-size: 16px; font-weight: 700; margin: 4px 0; line-height: 1.4;">
                            <a href="<?php the_permalink(); ?>" style="color: #1a1a2e; text-decoration: none;"><?php the_title(); ?></a>
                        </h3>
                        <div style="font-size: 12px; color: #999; display: flex; gap: 12px;">
                            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
                            <?php if ($source_name) : ?>
                                <span>Via <?php echo esc_html($source_name); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
        
        <aside>
            <div style="background: #f8f9fa; border-radius: 10px; padding: 24px; position: sticky; top: 20px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #1a1a2e; margin: 0 0 16px;">About Us</h3>
                <p style="font-size: 14px; color: #555; line-height: 1.6; margin: 0 0 16px;">
                    iTIN Pakistan is dedicated to covering Pakistan's technology ecosystem — from IT infrastructure and AI developments to startup funding and cybersecurity.
                </p>
                <p style="font-size: 14px; color: #555; line-height: 1.6; margin: 0 0 16px;">
                    Our editorial team curates and reports on the latest developments to keep Pakistan's tech community informed.
                </p>
                <a href="<?php echo esc_url(home_url('/about-us/')); ?>" style="display: inline-block; padding: 10px 20px; background: #37215F; color: #fff; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600;">Learn More</a>
            </div>
            
            <div style="background: #f8f9fa; border-radius: 10px; padding: 24px; margin-top: 20px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #1a1a2e; margin: 0 0 16px;">Categories</h3>
                <?php
                $categories = get_categories(array(
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'number' => 6,
                ));
                foreach ($categories as $cat) :
                ?>
                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; text-decoration: none; color: #333; font-size: 14px;">
                    <span><?php echo esc_html($cat->name); ?></span>
                    <span style="color: #999; font-size: 12px;"><?php echo $cat->count; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
</div>

<?php get_footer(); ?>
