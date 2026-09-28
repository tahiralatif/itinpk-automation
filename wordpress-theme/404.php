<?php
/**
 * Custom 404 Page Template
 */
get_header(); ?>

<div class="custom-404" style="max-width: 800px; margin: 0 auto; padding: 60px 28px; text-align: center;">

    <div style="font-size: 120px; font-weight: 700; color: #37215F; line-height: 1; margin-bottom: 10px;">404</div>

    <h1 style="font-size: 28px; font-weight: 700; color: #1e1e1e; margin-bottom: 15px;">Page Not Found</h1>

    <p style="font-size: 17px; color: #666; margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto;">
        The page you're looking for doesn't exist or has been moved.
    </p>

    <!-- Search Box -->
    <div style="max-width: 500px; margin: 0 auto 40px;">
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:flex; border-radius: 8px; overflow: hidden; border: 2px solid #37215F;">
            <input type="search" name="s" placeholder="Search iTIN Pakistan..."
                   value="<?php echo get_search_query(); ?>"
                   style="flex:1; padding: 14px 18px; border: none; font-size: 16px; outline: none; background: #fff;">
            <button type="submit" style="background: #37215F; color: #fff; border: none; padding: 14px 28px; font-weight: 700; font-size: 16px; cursor: pointer;">Search</button>
        </form>
    </div>

    <!-- Quick Links -->
    <div style="margin-bottom: 40px;">
        <p style="font-size: 14px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Popular Sections</p>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo esc_url( home_url( '/it-news/' ) ); ?>" style="display:inline-block; padding: 10px 20px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; font-weight: 600; font-size: 14px; transition: background 0.2s;">IT News</a>
            <a href="<?php echo esc_url( home_url( '/startups/' ) ); ?>" style="display:inline-block; padding: 10px 20px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; font-weight: 600; font-size: 14px; transition: background 0.2s;">Startups</a>
            <a href="<?php echo esc_url( home_url( '/cybersecurity/' ) ); ?>" style="display:inline-block; padding: 10px 20px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; font-weight: 600; font-size: 14px; transition: background 0.2s;">Cybersecurity</a>
            <a href="<?php echo esc_url( home_url( '/ai-cloud/' ) ); ?>" style="display:inline-block; padding: 10px 20px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; font-weight: 600; font-size: 14px; transition: background 0.2s;">AI &amp; Cloud</a>
            <a href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>" style="display:inline-block; padding: 10px 20px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; font-weight: 600; font-size: 14px; transition: background 0.2s;">Reviews</a>
            <a href="<?php echo esc_url( home_url( '/web-shows/' ) ); ?>" style="display:inline-block; padding: 10px 20px; background: #f0f0f0; border-radius: 6px; text-decoration: none; color: #333; font-weight: 600; font-size: 14px; transition: background 0.2s;">Web Shows</a>
        </div>
    </div>

    <!-- Recent Posts -->
    <?php
    $recent = new WP_Query(array(
        'posts_per_page' => 3,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    if($recent->have_posts()) : ?>
    <div style="text-align: left; max-width: 700px; margin: 0 auto;">
        <p style="font-size: 14px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; text-align: center;">Or Read These</p>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <?php while($recent->have_posts()) : $recent->the_post(); ?>
                <a href="<?php the_permalink(); ?>" style="text-decoration: none; color: #1e1e1e;">
                    <?php if(has_post_thumbnail()) : ?>
                        <div style="border-radius: 8px; overflow: hidden; margin-bottom: 10px; aspect-ratio: 16/9;">
                            <?php the_post_thumbnail('medium', array('style' => 'width:100%; height:100%; object-fit:cover;')); ?>
                        </div>
                    <?php endif; ?>
                    <h3 style="font-size: 14px; font-weight: 600; line-height: 1.4; margin: 0 0 5px 0;"><?php the_title(); ?></h3>
                    <p style="font-size: 12px; color: #999; margin: 0;"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')); ?> ago</p>
                </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Back to Home -->
    <div style="margin-top: 40px;">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display: inline-block; padding: 12px 30px; background: #37215F; color: #fff; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; transition: background 0.2s;">Back to Homepage</a>
    </div>

</div>

<?php get_footer(); ?>
