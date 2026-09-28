<?php
/**
 * Custom Search Results — Card layout with featured images
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$search_query = get_search_query();
$has_results  = have_posts();

$cat_colors = array(
    'it-news' => '#0881BE', 'startups-entrepreneurs' => '#37215F',
    'cybersecurity-tech' => '#e74c3c', 'ai-cloud' => '#8e44ad',
    'reviews' => '#27ae60',
);
?>

<section class="search-hero">
    <div class="search-hero-inner">
        <h1 class="search-hero-title">
            <?php if ( $has_results ) : ?>
                Search Results for: <span class="search-query">"<?php echo esc_html( $search_query ); ?>"</span>
            <?php else : ?>
                No Results for: <span class="search-query">"<?php echo esc_html( $search_query ); ?>"</span>
            <?php endif; ?>
        </h1>
        <?php if ( $has_results ) : ?>
            <p class="search-hero-count"><?php echo esc_html( $wp_query->found_posts ); ?> article<?php echo $wp_query->found_posts !== 1 ? 's' : ''; ?> found</p>
        <?php endif; ?>
    </div>
</section>

<div class="search-results-wrap">
    <?php if ( $has_results ) : ?>
        <div class="search-grid">
            <?php while ( have_posts() ) : the_post();
                $pid     = get_the_ID();
                $title   = get_the_title();
                $url     = get_permalink();
                $excerpt = get_the_excerpt();
                $date    = get_the_date();
                $img     = get_the_post_thumbnail_url( $pid, 'medium_large' );
                $cats    = get_the_category();
                $cat_name  = ! empty( $cats ) ? $cats[0]->name : '';
                $cat_slug  = ! empty( $cats ) ? $cats[0]->slug : '';
                $cat_color = isset( $cat_colors[ $cat_slug ] ) ? $cat_colors[ $cat_slug ] : '#0881BE';
                $source_url   = get_post_meta( $pid, '_itinpk_source_url', true );
                $source_name  = get_post_meta( $pid, '_itinpk_source_name', true );
                $read_time    = max( 1, ceil( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) );
            ?>
                <article class="search-card">
                    <a href="<?php echo esc_url( $url ); ?>" class="search-card-link">
                        <div class="search-card-img">
                            <?php if ( $img ) : ?>
                                <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
                            <?php else : ?>
                                <div class="search-card-placeholder" style="background: <?php echo esc_attr( $cat_color ); ?>;"></div>
                            <?php endif; ?>
                            <?php if ( $cat_name ) : ?>
                                <span class="search-card-cat" style="background: <?php echo esc_attr( $cat_color ); ?>;"><?php echo esc_html( $cat_name ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="search-card-body">
                            <h2 class="search-card-title"><?php echo esc_html( $title ); ?></h2>
                            <div class="search-card-meta">
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" title="<?php echo esc_attr( get_the_date( 'F j, Y g:i A' ) ); ?>"><?php echo function_exists( 'itinpk_relative_time' ) ? esc_html( itinpk_relative_time( $pid ) ) : esc_html( $date ); ?></time>
                                <span class="search-card-sep">&middot;</span>
                                <span class="search-card-read"><?php echo esc_html( $read_time ); ?>m read</span>
                                <?php if ( $source_name ) : ?>
                                    <span class="search-card-sep">&middot;</span>
                                    <span class="search-card-source">
                                        <?php if ( $source_url ) : ?>
                                            <a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $source_name ); ?></a>
                                        <?php else : ?>
                                            <?php echo esc_html( $source_name ); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="search-card-excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 20 ) ); ?></p>
                        </div>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <div class="search-pagination">
            <?php
            echo paginate_links( array(
                'prev_text' => '&laquo; Prev',
                'next_text' => 'Next &raquo;',
            ) );
            ?>
        </div>

    <?php else : ?>
        <!-- No results — web fallback -->
        <div class="search-no-results">
            <div class="search-no-results-icon">&#128269;</div>
            <h2>No articles found</h2>
            <p>We couldn't find any articles matching "<strong><?php echo esc_html( $search_query ); ?></strong>" on iTIN Pakistan.</p>

            <div class="web-results" id="webResults">
                <div class="web-results-loading">
                    <span class="spinner"></span> Searching the web for results...
                </div>
            </div>

            <div class="request-article-section">
                <h3>Can't find what you're looking for?</h3>
                <p>Request an article on this topic and we'll publish it within minutes.</p>
                <button class="request-article-btn" id="requestArticleBtn" data-query="<?php echo esc_attr( $search_query ); ?>">
                    Request Article on "<?php echo esc_html( $search_query ); ?>"
                </button>
                <div class="request-status" id="requestStatus"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
