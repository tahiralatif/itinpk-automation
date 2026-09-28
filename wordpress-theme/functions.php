<?php
/**
 * iTIN Pakistan Child Theme Functions
 *
 * @package GeneratePress_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Relative time helper
function itinpk_relative_time($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $post_time = get_post_field('post_date', $post_id);
    $now = current_time('timestamp');
    $diff = $now - strtotime($post_time);
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return get_the_date('M j', $post_id);
}

// Check if article is "new" (published in last 6 hours)
function itinpk_is_new($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $post_time = get_post_field('post_date', $post_id);
    $diff = current_time('timestamp') - strtotime($post_time);
    return $diff < 21600; // 6 hours
}

// Enqueue parent and child styles
function itinpk_child_enqueue_styles() {
    // Google Fonts — Inter
    wp_enqueue_style(
        'itinpk-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
        array(),
        null
    );
    
    // Parent theme style
    wp_enqueue_style(
        'generatepress-parent',
        get_template_directory_uri() . '/style.css',
        array( 'itinpk-google-fonts' ),
        wp_get_theme( 'generatepress' )->get( 'Version' )
    );
    
    // Child theme style (custom-colors.css)
    wp_enqueue_style(
        'itinpk-child-custom',
        get_stylesheet_directory_uri() . '/custom-colors.css',
        array( 'generatepress-parent' ),
        '1.0.0'
    );
}
add_action( 'wp_enqueue_scripts', 'itinpk_child_enqueue_styles' );

// Enable Yoast breadcrumbs
add_theme_support( 'yoast-seo-breadcrumbs' );

// Add Yoast breadcrumbs support
function itinpk_breadcrumbs() {
    if ( function_exists( 'yoast_breadcrumb' ) ) {
        yoast_breadcrumb( '<nav class="yoast-breadcrumb" style="max-width:1200px;margin:0 auto;padding:10px 28px;font-size:13px;color:#999;">', '</nav>' );
    }
}

// Favicon
function itinpk_favicon() {
    $uri = get_stylesheet_directory_uri();
    echo '<link rel="icon" href="' . $uri . '/favicon.ico" sizes="32x32">';
    echo '<link rel="icon" href="' . $uri . '/favicon-32x32.png" sizes="32x32">';
    echo '<link rel="icon" href="' . $uri . '/favicon-16x16.png" sizes="16x16">';
    echo '<link rel="apple-touch-icon" href="' . $uri . '/apple-touch-icon.png">';
}
add_action( 'wp_head', 'itinpk_favicon', 1 );

// Add custom logo support
function itinpk_custom_logo_support() {
    add_theme_support( 'custom-logo', array(
        'height'      => 120,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
}
add_action( 'after_setup_theme', 'itinpk_custom_logo_support' );

// Logo-only header: hide site title and tagline text
function itinpk_logo_only_header() {
    ?>
    <style>
        .site-branding .main-title,
        .site-branding .site-description,
        .site-branding > .main-title + .site-description { display: none !important; }
        .site-branding { display: flex; align-items: center; }
        .site-logo img { height: 90px; width: auto; display: block; }
        .site-branding-container { display: flex; align-items: center; }
    </style>
    <?php
}
add_action( 'wp_head', 'itinpk_logo_only_header' );

// Custom Recent Posts widget with thumbnails
class ITINPK_Recent_Posts_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'itinpk_recent_posts',
            'iTIN Recent Posts',
            array( 'description' => 'Recent posts with thumbnails' )
        );
    }
    
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? 'Recent Posts' );
        $number = $instance['number'] ?? 5;
        
        echo $args['before_widget'];
        
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        
        $posts = wp_get_recent_posts( array(
            'numberposts' => $number,
            'post_status' => 'publish',
        ) );
        
        if ( ! empty( $posts ) ) {
            echo '<ul class="sidebar-recent-posts">';
            foreach ( $posts as $post ) {
                $thumb = get_the_post_thumbnail_url( $post['ID'], 'thumbnail' );
                $title = esc_html( $post['post_title'] );
                $url = get_permalink( $post['ID'] );
                $date = get_the_date( 'M j, Y', $post['ID'] );
                
                echo '<li class="sidebar-post-item">';
                echo '<a href="' . $url . '" class="sidebar-post-link">';
                if ( $thumb ) {
                    echo '<img src="' . $thumb . '" alt="' . $title . '" class="sidebar-post-thumb" loading="lazy" />';
                } else {
                    echo '<img src="' . esc_url( get_site_url() . '/wp-content/uploads/2026/08/itin-logo-pakistan-01-150x150.png' ) . '" alt="' . $title . '" class="sidebar-post-thumb" loading="lazy" />';
                }
                echo '<div class="sidebar-post-info">';
                echo '<span class="sidebar-post-title">' . $title . '</span>';
                echo '<span class="sidebar-post-date">' . $date . '</span>';
                echo '</div>';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form( $instance ) {
        $title = $instance['title'] ?? 'Recent Posts';
        $number = $instance['number'] ?? 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>">Number:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3" />
        </p>
        <?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['number'] = absint( $new_instance['number'] ?? 5 );
        return $instance;
    }
}

function itinpk_register_recent_posts_widget() {
    register_widget( 'ITINPK_Recent_Posts_Widget' );
}
add_action( 'widgets_init', 'itinpk_register_recent_posts_widget' );

// Hide GP's default header output since we render our own in header.php
function itinpk_modern_header_css() {
    if ( is_admin() ) return;
    ?>
    <style>
    .site-header, .inside-header, .mobile-menu-control-wrapper,
    .navigation, #site-navigation, .main-navigation.has-menu-bar-items {
        display: none !important;
    }
    #page { padding-top: 0; }
    </style>
    <?php
}
add_action( 'wp_head', 'itinpk_modern_header_css', 1 );

// Disable old auth buttons in GP nav (we have our own in the modern header)
function itinpk_disable_old_auth_buttons() {
    remove_action( 'generate_menu_bar_items', 'itinpk_auth_buttons' );
}
add_action( 'init', 'itinpk_disable_old_auth_buttons' );

// Shared byline: show "iTIN Pakistan Editorial Team" instead of individual author names
function itinpk_shared_byline( $author_name ) {
    return 'iTIN Pakistan Editorial Team';
}
add_filter( 'the_author', 'itinpk_shared_byline' );

// Also override the author link to point to the homepage instead of author archive
function itinpk_shared_author_link( $url ) {
    return home_url( '/' );
}
add_filter( 'author_link', 'itinpk_shared_author_link' );

// Fix Yoast SEO schema to show "iTIN Pakistan Editorial Team" instead of login username
function itinpk_yoast_author_name( $name ) {
    return 'iTIN Pakistan Editorial Team';
}
add_filter( 'wpseo_schema_article_author', 'itinpk_yoast_author_name' );
add_filter( 'wpseo_schema_person_name', 'itinpk_yoast_author_name' );

// ---------------------------------------------------------------------------
// Related Video Card — renders below article content on single posts
// ---------------------------------------------------------------------------
function itinpk_related_video() {
    if ( ! is_single() ) return;

    $video_id = get_post_meta( get_the_ID(), '_itinpk_video_id', true );
    $video_url = get_post_meta( get_the_ID(), '_itinpk_video_url', true );
    $video_title = get_post_meta( get_the_ID(), '_itinpk_video_title', true );
    $video_thumbnail = get_post_meta( get_the_ID(), '_itinpk_video_thumbnail', true );

    if ( ! $video_id || ! $video_url ) return;

    echo '<div class="related-video-card">';
    echo '<h3 class="related-video-heading">Related Video</h3>';
    echo '<div class="video-thumbnail-wrapper" data-video-id="' . esc_attr( $video_id ) . '" data-video-title="' . esc_attr( $video_title ) . '" role="button" tabindex="0" aria-label="Play video: ' . esc_attr( $video_title ) . '">';
    echo '<img src="' . esc_url( $video_thumbnail ) . '" alt="' . esc_attr( $video_title ) . '" class="video-thumbnail" loading="lazy">';
    echo '<div class="video-play-icon"><svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="22" fill="rgba(0,0,0,0.7)" stroke="none"/><polygon points="18,12 18,36 38,24" fill="#FF0000"/></svg></div>';
    echo '</div>';
    echo '<p class="video-caption">' . esc_html( $video_title ) . '</p>';
    echo '</div>';
}
add_action( 'generate_after_entry_content', 'itinpk_related_video' );

// ---------------------------------------------------------------------------
// Video Modal — renders in footer on single posts and homepage
// ---------------------------------------------------------------------------
function itinpk_video_modal() {
    $is_single = is_single();
    $is_home = is_home() || is_front_page();
    if ( ! $is_single && ! $is_home ) return;

    $video_id = '';
    $video_url = '';
    $video_title = '';

    if ( $is_single ) {
        $video_id = get_post_meta( get_the_ID(), '_itinpk_video_id', true );
        $video_url = get_post_meta( get_the_ID(), '_itinpk_video_url', true );
        $video_title = get_post_meta( get_the_ID(), '_itinpk_video_title', true );
        if ( ! $video_id ) return;
    }

    echo '<div id="video-modal" class="video-modal" role="dialog" aria-modal="true" aria-label="Video player" style="display:none;">';
    echo '<div class="video-modal-overlay"></div>';
    echo '<div class="video-modal-container">';
    echo '<div class="video-modal-header">';
    echo '<span class="video-modal-title">' . esc_html( $video_title ) . '</span>';
    echo '<a href="' . esc_url( $video_url ) . '" target="_blank" rel="noopener" class="video-modal-yt-link">Watch on YouTube &#x2197;</a>';
    echo '<button class="video-modal-close" aria-label="Close video">&times;</button>';
    echo '</div>';
    echo '<div class="video-modal-body">';
    if ( $video_id ) {
        echo '<iframe src="https://www.youtube.com/embed/' . esc_attr( $video_id ) . '?autoplay=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>';
    } else {
        echo '<iframe src="" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
add_action( 'wp_footer', 'itinpk_video_modal' );

// ---------------------------------------------------------------------------
// Video Modal JS
// ---------------------------------------------------------------------------
function itinpk_video_modal_js() {
    $is_single = is_single();
    $is_home = is_home() || is_front_page();
    if ( ! $is_single && ! $is_home ) return;
    if ( $is_single ) {
        $video_id = get_post_meta( get_the_ID(), '_itinpk_video_id', true );
        $video_url = get_post_meta( get_the_ID(), '_itinpk_video_url', true );
        $video_title = get_post_meta( get_the_ID(), '_itinpk_video_title', true );
        if ( ! $video_id ) return;
    }
    ?>
    <script>
    (function(){
        var modal = document.getElementById('video-modal');
        if (!modal) return;
        var overlay = modal.querySelector('.video-modal-overlay');
        var closeBtn = modal.querySelector('.video-modal-close');
        var ytLink = modal.querySelector('.video-modal-yt-link');
        var titleEl = modal.querySelector('.video-modal-title');
        var iframe = modal.querySelector('iframe');

        function openModal(videoId, videoTitle, videoUrl) {
            if (iframe && videoId) {
                iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
            }
            if (ytLink && videoUrl) ytLink.href = videoUrl;
            if (titleEl && videoTitle) titleEl.textContent = videoTitle;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            if (iframe) iframe.src = iframe.src;
        }

        // Homepage video cards: .video-card-thumb or .video-sidebar-thumb
        var cardThumbs = document.querySelectorAll('.video-card-thumb[data-video-id], .video-sidebar-thumb[data-video-id]');
        for (var i = 0; i < cardThumbs.length; i++) {
            (function(el){
                el.addEventListener('click', function(){
                    openModal(el.getAttribute('data-video-id'), el.getAttribute('data-video-title'), 'https://www.youtube.com/watch?v=' + el.getAttribute('data-video-id'));
                });
                el.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(el.getAttribute('data-video-id'), el.getAttribute('data-video-title'), 'https://www.youtube.com/watch?v=' + el.getAttribute('data-video-id')); }
                });
            })(cardThumbs[i]);
        }

        // Single post: .video-thumbnail-wrapper[data-video-id]
        var wrappers = document.querySelectorAll('.video-thumbnail-wrapper[data-video-id]');
        for (var j = 0; j < wrappers.length; j++) {
            (function(el){
                el.addEventListener('click', function(){
                    openModal(el.getAttribute('data-video-id'), el.getAttribute('data-video-title'), 'https://www.youtube.com/watch?v=' + el.getAttribute('data-video-id'));
                });
                el.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(el.getAttribute('data-video-id'), el.getAttribute('data-video-title'), 'https://www.youtube.com/watch?v=' + el.getAttribute('data-video-id')); }
                });
            })(wrappers[j]);
        }

        // Inline video links in post content
        var inlineLinks = document.querySelectorAll('.video-inline-link[data-video-id]');
        for (var k = 0; k < inlineLinks.length; k++) {
            (function(el){
                el.addEventListener('click', function(e){
                    e.preventDefault();
                    openModal(el.getAttribute('data-video-id'), el.getAttribute('data-video-title'), 'https://www.youtube.com/watch?v=' + el.getAttribute('data-video-id'));
                });
            })(inlineLinks[k]);
        }

        overlay.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'itinpk_video_modal_js' );

// Homepage featured video player: click list item → swap player
function itinpk_featured_player_js() {
    if ( ! is_home() && ! is_front_page() ) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var overlay = document.getElementById('featured-player-overlay');
        var wrap = document.getElementById('featured-player-wrap');
        var titleEl = document.getElementById('featured-player-title');
        var descEl = document.getElementById('featured-player-desc');
        var list = document.getElementById('featured-list-items');
        if (!overlay || !wrap || !list) return;

        function loadVideo(videoId) {
            var embed = document.getElementById('featured-player-embed');
            embed.innerHTML = '<iframe src="https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;"></iframe>';
        }

        overlay.addEventListener('click', function() {
            var firstItem = list.querySelector('.featured-list-item');
            if (firstItem) loadVideo(firstItem.getAttribute('data-video-id'));
        });

        list.addEventListener('click', function(e) {
            var item = e.target.closest('.featured-list-item');
            if (!item) return;
            var videoId = item.getAttribute('data-video-id');
            var videoTitle = item.getAttribute('data-video-title');

            // Update active state
            list.querySelectorAll('.featured-list-item').forEach(function(el) { el.classList.remove('active'); });
            item.classList.add('active');

            // Update player
            var embed = document.getElementById('featured-player-embed');
            embed.innerHTML = '<iframe src="https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;"></iframe>';

            // Update text panel
            if (titleEl) titleEl.textContent = videoTitle;
            if (descEl) descEl.textContent = item.getAttribute('data-post-desc') || '';

            // Update category badge
            var catBadge = document.querySelector('.featured-text-top .cat-badge');
            var catName = item.getAttribute('data-cat-name');
            var catSlug = item.getAttribute('data-cat-slug');
            var catColor = item.getAttribute('data-cat-color');
            if (catBadge && catName) {
                catBadge.textContent = catName;
                catBadge.className = 'cat-badge cat-badge-' + catSlug;
                if (catColor) catBadge.style.backgroundColor = catColor;
            }
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'itinpk_featured_player_js' );

// ---------------------------------------------------------------------------
// Search Fallback: Web Results + Request Article
// ---------------------------------------------------------------------------
function itinpk_search_fallback_scripts() {
    if ( ! is_search() ) return;
    ?>
    <style>
        .search-fallback { margin: 20px 0; }
        .search-fallback-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px; }
        .web-results { margin: 20px 0; }
        .web-results-loading { text-align: center; padding: 20px; color: #666; }
        .web-results-loading .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #ddd; border-top-color: #37215F; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .web-result-item { padding: 15px 0; border-bottom: 1px solid #eee; }
        .web-result-item h3 { font-size: 16px; margin: 0 0 5px; }
        .web-result-item h3 a { color: #0881BE; text-decoration: none; }
        .web-result-item h3 a:hover { text-decoration: underline; }
        .web-result-item p { font-size: 14px; color: #555; margin: 0 0 5px; }
        .web-result-item .source { font-size: 12px; color: #999; }
        .request-article-section { background: #f8f5ff; border: 1px solid #e0d5f0; border-radius: 8px; padding: 20px; margin-top: 20px; text-align: center; }
        .request-article-section h3 { font-size: 16px; margin: 0 0 8px; color: #37215F; }
        .request-article-section p { font-size: 14px; color: #555; margin: 0 0 15px; }
        .request-article-btn { background: #37215F; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .request-article-btn:hover { background: #2a1a4a; }
        .request-article-btn:disabled { background: #999; cursor: not-allowed; }
        .request-status { margin-top: 15px; font-size: 14px; }
        .request-status.success { color: #28a745; }
        .request-status.error { color: #dc3545; }
        .request-status.pending { color: #666; }
        .search-result-item { padding: 15px 0; border-bottom: 1px solid #eee; }
        .search-result-item h2 { font-size: 18px; margin: 0 0 5px; }
        .search-result-item h2 a { color: #333; }
        .search-result-item .entry-meta { font-size: 12px; color: #999; margin-bottom: 8px; }
        .search-result-item .entry-summary { font-size: 14px; color: #555; }
    </style>
    <script>
    (function(){
        // Fetch web results from Google News RSS
        var webResults = document.getElementById('webResults');
        if (webResults) {
            var query = '<?php echo esc_js(get_search_query()); ?>';
            // Use a CORS proxy to fetch Google News RSS
            var rssUrl = 'https://news.google.com/rss/search?q=' + encodeURIComponent(query) + '&hl=en-PK&gl=PK&ceid=PK:en';

            // Try fetching via allorigins proxy
            fetch('https://api.allorigins.win/raw?url=' + encodeURIComponent(rssUrl))
                .then(function(r) { return r.text(); })
                .then(function(xml) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xml, 'text/xml');
                    var items = doc.querySelectorAll('item');
                    var html = '';
                    var count = 0;

                    for (var i = 0; i < items.length && count < 5; i++) {
                        var title = items[i].querySelector('title');
                        var link = items[i].querySelector('link');
                        var source = items[i].querySelector('source');
                        var pubDate = items[i].querySelector('pubDate');

                        if (title && link) {
                            var titleText = title.textContent || '';
                            var linkText = link.textContent || '';
                            var sourceText = source ? source.textContent || '' : '';
                            var dateText = pubDate ? new Date(pubDate.textContent).toLocaleDateString() : '';

                            // Skip if title matches the search (likely the same empty result)
                            if (titleText.toLowerCase().indexOf(query.toLowerCase()) === -1) {
                                html += '<div class="web-result-item">';
                                html += '<h3><a href="' + linkText + '" target="_blank" rel="noopener">' + titleText + '</a></h3>';
                                if (sourceText) html += '<div class="source">' + sourceText + (dateText ? ' · ' + dateText : '') + '</div>';
                                html += '</div>';
                                count++;
                            }
                        }
                    }

                    if (count > 0) {
                        webResults.innerHTML = html;
                    } else {
                        webResults.innerHTML = '<p style="color:#999;">No web results found.</p>';
                    }
                })
                .catch(function() {
                    webResults.innerHTML = '<p style="color:#999;">Unable to fetch web results.</p>';
                });
        }

        // Request Article button
        var requestBtn = document.getElementById('requestArticleBtn');
        var requestStatus = document.getElementById('requestStatus');
        if (requestBtn) {
            requestBtn.addEventListener('click', function() {
                var query = this.getAttribute('data-query');
                this.disabled = true;
                this.textContent = 'Requesting...';
                requestStatus.className = 'request-status pending';
                requestStatus.textContent = 'Submitting your request...';

                // Submit request via WordPress AJAX
                var formData = new FormData();
                formData.append('action', 'itinpk_request_article');
                formData.append('query', query);
                formData.append('nonce', '<?php echo wp_create_nonce('itinpk_request_article_nonce'); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        requestStatus.className = 'request-status success';
                        requestStatus.innerHTML = '<strong>Article requested!</strong><br>' + data.data.message;
                        requestBtn.textContent = 'Request Submitted';
                    } else {
                        requestStatus.className = 'request-status error';
                        requestStatus.textContent = data.data || 'Error requesting article.';
                        requestBtn.disabled = false;
                        requestBtn.textContent = 'Request Article on "' + query + '"';
                    }
                })
                .catch(function() {
                    requestStatus.className = 'request-status error';
                    requestStatus.textContent = 'Network error. Please try again.';
                    requestBtn.disabled = false;
                    requestBtn.textContent = 'Request Article on "' + query + '"';
                });
            });
        }
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'itinpk_search_fallback_scripts' );

// AJAX handler for article requests
function itinpk_request_article_handler() {
    check_ajax_referer( 'itinpk_request_article_nonce', 'nonce' );

    $query = sanitize_text_field( $_POST['query'] ?? '' );
    if ( empty( $query ) ) {
        wp_send_json_error( 'Please provide a search query.' );
    }

    // Check if we already have a recent request for this query
    $recent = get_option( 'itinpk_pending_requests', [] );
    $query_lower = strtolower( $query );
    if ( isset( $recent[ $query_lower ] ) && ( time() - $recent[ $query_lower ] ) < 600 ) {
        wp_send_json_error( 'This topic was recently requested. Please check back in a few minutes.' );
    }

    // Log the request
    $recent[ $query_lower ] = time();
    update_option( 'itinpk_pending_requests', $recent );

    // Trigger pipeline in background
    $cmd = sprintf(
        'cd /opt/itinpk-news && /usr/bin/python3 news_pipeline.py --source google --search-query %s >> /opt/itinpk-news/logs/manual_requests.log 2>&1 &',
        escapeshellarg( $query )
    );
    exec( $cmd );

    wp_send_json_success( array(
        'message' => sprintf( 'We\'re searching for articles about "%s". New articles typically appear within 2-3 minutes.', esc_html( $query ) ),
    ) );
}
add_action( 'wp_ajax_itinpk_request_article', 'itinpk_request_article_handler' );
add_action( 'wp_ajax_nopriv_itinpk_request_article', 'itinpk_request_article_handler' );

// ===========================================================================
// WEB CHANNEL / LIVE SHOWS — Custom Post Type
// ===========================================================================

// Register Web Show custom post type
function itinpk_register_web_show_cpt() {
    $labels = array(
        'name'               => 'Web Shows',
        'singular_name'      => 'Web Show',
        'menu_name'          => 'Web Shows',
        'add_new'            => 'Add New Episode',
        'add_new_item'       => 'Add New Web Show Episode',
        'edit_item'          => 'Edit Episode',
        'new_item'           => 'New Episode',
        'view_item'          => 'View Episode',
        'search_items'       => 'Search Episodes',
        'not_found'          => 'No episodes found',
        'not_found_in_trash' => 'No episodes found in Trash',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'web-shows' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-video-alt3',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
    );

    register_post_type( 'web_show', $args );
}
add_action( 'init', 'itinpk_register_web_show_cpt' );

// Add custom meta boxes for Web Show episodes
function itinpk_web_show_meta_boxes() {
    add_meta_box(
        'itinpk_episode_details',
        'Episode Details',
        'itinpk_episode_details_callback',
        'web_show',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'itinpk_web_show_meta_boxes' );

// Episode details meta box callback
function itinpk_episode_details_callback( $post ) {
    wp_nonce_field( 'itinpk_episode_details_nonce', 'itinpk_episode_nonce' );

    $guest_name = get_post_meta( $post->ID, '_itinpk_guest_name', true );
    $guest_bio = get_post_meta( $post->ID, '_itinpk_guest_bio', true );
    $youtube_url = get_post_meta( $post->ID, '_itinpk_youtube_url', true );
    $is_live = get_post_meta( $post->ID, '_itinpk_is_live', true );
    $episode_number = get_post_meta( $post->ID, '_itinpk_episode_number', true );
    ?>
    <style>
        .itinpk-meta-row { margin-bottom: 15px; }
        .itinpk-meta-row label { display: block; font-weight: 600; margin-bottom: 5px; }
        .itinpk-meta-row input[type="text"],
        .itinpk-meta-row input[type="url"],
        .itinpk-meta-row textarea { width: 100%; max-width: 500px; }
        .itinpk-meta-row textarea { height: 100px; }
        .itinpk-live-toggle { display: flex; align-items: center; gap: 10px; }
        .itinpk-live-toggle input { width: 20px; height: 20px; }
        .itinpk-live-badge { background: #e74c3c; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 700; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    </style>
    <div class="itinpk-meta-row">
        <label for="itinpk_guest_name">Guest Name</label>
        <input type="text" id="itinpk_guest_name" name="itinpk_guest_name" value="<?php echo esc_attr( $guest_name ); ?>" placeholder="e.g. Ahmed Khan, CEO of TechPakistan" />
    </div>
    <div class="itinpk-meta-row">
        <label for="itinpk_guest_bio">Guest Bio (short)</label>
        <textarea id="itinpk_guest_bio" name="itinpk_guest_bio" placeholder="e.g. Serial entrepreneur, founded 3 startups, TEDx speaker"><?php echo esc_textarea( $guest_bio ); ?></textarea>
    </div>
    <div class="itinpk-meta-row">
        <label for="itinpk_youtube_url">YouTube Video URL</label>
        <input type="url" id="itinpk_youtube_url" name="itinpk_youtube_url" value="<?php echo esc_url( $youtube_url ); ?>" placeholder="https://www.youtube.com/watch?v=..." />
        <p class="description">Paste the full YouTube URL. For live streams, use the live stream URL.</p>
    </div>
    <div class="itinpk-meta-row">
        <label for="itinpk_episode_number">Episode Number</label>
        <input type="text" id="itinpk_episode_number" name="itinpk_episode_number" value="<?php echo esc_attr( $episode_number ); ?>" placeholder="e.g. 01" style="width: 100px;" />
    </div>
    <div class="itinpk-meta-row">
        <div class="itinpk-live-toggle">
            <input type="checkbox" id="itinpk_is_live" name="itinpk_is_live" value="1" <?php checked( $is_live, '1' ); ?> />
            <label for="itinpk_is_live">Mark as "LIVE NOW"</label>
            <?php if ( $is_live ) : ?>
                <span class="itinpk-live-badge">LIVE</span>
            <?php endif; ?>
        </div>
        <p class="description">Enable this when the show is actively streaming live on YouTube.</p>
    </div>
    <?php
}

// Save episode meta data
function itinpk_save_episode_meta( $post_id ) {
    if ( ! isset( $_POST['itinpk_episode_nonce'] ) || ! wp_verify_nonce( $_POST['itinpk_episode_nonce'], 'itinpk_episode_details_nonce' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'itinpk_guest_name'     => '_itinpk_guest_name',
        'itinpk_guest_bio'      => '_itinpk_guest_bio',
        'itinpk_youtube_url'    => '_itinpk_youtube_url',
        'itinpk_episode_number' => '_itinpk_episode_number',
    );

    foreach ( $fields as $field_name => $meta_key ) {
        if ( isset( $_POST[ $field_name ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field_name ] ) );
        }
    }

    // Handle live toggle
    $is_live = isset( $_POST['itinpk_is_live'] ) ? '1' : '0';
    update_post_meta( $post_id, '_itinpk_is_live', $is_live );
}
add_action( 'save_post', 'itinpk_save_episode_meta' );

// Extract YouTube video ID from URL
function itinpk_extract_youtube_id( $url ) {
    if ( empty( $url ) ) return '';
    $patterns = array(
        '#youtube\.com/watch\?v=([a-zA-Z0-9_-]+)#',
        '#youtu\.be/([a-zA-Z0-9_-]+)#',
        '#youtube\.com/embed/([a-zA-Z0-9_-]+)#',
        '#youtube\.com/v/([a-zA-Z0-9_-]+)#',
    );
    foreach ( $patterns as $pattern ) {
        if ( preg_match( $pattern, $url, $matches ) ) {
            return $matches[1];
        }
    }
    return '';
}

// Get YouTube thumbnail from URL
function itinpk_get_youtube_thumbnail( $url ) {
    $video_id = itinpk_extract_youtube_id( $url );
    if ( ! $video_id ) return '';
    return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
}

// ===========================================================================
// WEB SHOWS — Archive Template
// ===========================================================================

// Add body class for web show post type
function itinpk_web_show_body_class( $classes ) {
    if ( is_post_type_archive( 'web_show' ) || is_singular( 'web_show' ) ) {
        $classes[] = 'itinpk-web-shows';
    }
    return $classes;
}
add_filter( 'body_class', 'itinpk_web_show_body_class' );

// Enqueue web shows template CSS
function itinpk_web_show_enqueue() {
    if ( is_post_type_archive( 'web_show' ) || is_singular( 'web_show' ) ) {
        wp_enqueue_style( 'itinpk-web-shows', get_stylesheet_directory_uri() . '/web-shows.css', array(), '1.0.0' );
    }
}
add_action( 'wp_enqueue_scripts', 'itinpk_web_show_enqueue' );

// Plausible Analytics (privacy-friendly, no cookies)
function itinpk_plausible_analytics() {
    ?>
    <script defer data-domain="itinpk.14.jugaar.ai" src="https://plausible.io/js/script.js"></script>
    <?php
}
add_action('wp_head', 'itinpk_plausible_analytics', 1);

// Google Search Console verification (replace with actual code)
function itinpk_gsc_verification() {
    echo '<meta name="google-site-verification" content="yddpUPqHnV2PHUy_XrwxUv3bwx3MCHhAyDM61HwS_Wc" />';
}
add_action('wp_head', 'itinpk_gsc_verification', 2);

// Reading progress bar + back-to-top button JS
function itinpk_progress_and_totop() {
    if (is_single()) {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var bar = document.getElementById('reading-progress');
        if (!bar) return;
        window.addEventListener('scroll', function() {
            var h = document.documentElement.scrollHeight - window.innerHeight;
            var pct = h > 0 ? (window.scrollY / h * 100) : 0;
            bar.style.width = pct + '%';
        });
    });
    </script>
    <?php
    }
    ?>
    <button class="back-to-top" id="backToTop" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">&#8593;</button>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('backToTop');
        if (!btn) return;
        window.addEventListener('scroll', function() {
            if (window.scrollY > 400) { btn.classList.add('visible'); }
            else { btn.classList.remove('visible'); }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'itinpk_progress_and_totop');

// MailPoet newsletter subscription handler
function itinpk_mailpoet_subscribe() {
    if (!isset($_POST['email']) || !isset($_POST['list_id'])) {
        wp_redirect(home_url());
        exit;
    }
    
    $email = sanitize_email($_POST['email']);
    $list_id = intval($_POST['list_id']);
    
    if (!is_email($email)) {
        wp_redirect(home_url('/?error=invalid_email'));
        exit;
    }
    
    // Subscribe via MailPoet API
    if (class_exists('MailPoet\API\API')) {
        try {
            $subscriber_data = array(
                'email' => $email,
                'status' => 'subscribed',
                'list_id' => $list_id,
            );
            MailPoet\API\API::mp('v1')->addSubscriber($subscriber_data);
            wp_redirect(home_url('/?subscribed=1'));
        } catch (Exception $e) {
            wp_redirect(home_url('/?error=subscribe_failed'));
        }
    } else {
        wp_redirect(home_url('/?error=mailpoet_not_available'));
    }
    exit;
}
add_action('admin_post_mailpoet_subscribe', 'itinpk_mailpoet_subscribe');
add_action('admin_post_nopriv_mailpoet_subscribe', 'itinpk_mailpoet_subscribe');

// Contact Form Handler
function itinpk_contact_submit() {
    if (!isset($_POST['name'], $_POST['email'], $_POST['subject'], $_POST['message'])) {
        wp_redirect(home_url('/contact-us/?error=missing'));
        exit;
    }
    
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $subject = sanitize_text_field($_POST['subject']);
    $message = sanitize_textarea_field($_POST['message']);
    
    if (!is_email($email)) {
        wp_redirect(home_url('/contact-us/?error=invalid_email'));
        exit;
    }
    
    $to = get_option('admin_email');
    $email_subject = "[iTIN Contact] $subject — $name";
    $body = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";
    $headers = array('Content-Type: text/plain; charset=UTF-8', "Reply-To: $name <$email>");
    
    $sent = wp_mail($to, $email_subject, $body, $headers);
    
    if ($sent) {
        wp_redirect(home_url('/contact-us/?success=1'));
    } else {
        wp_redirect(home_url('/contact-us/?error=failed'));
    }
    exit;
}
add_action('admin_post_itinpk_contact_submit', 'itinpk_contact_submit');
add_action('admin_post_nopriv_itinpk_contact_submit', 'itinpk_contact_submit');

// Article Schema Markup
function itinpk_article_schema() {
    if (!is_single()) return;
    
    global $post;
    $title = get_the_title();
    $excerpt = get_the_excerpt();
    $url = get_permalink();
    $datePublished = get_the_date('c');
    $dateModified = get_the_modified_date('c');
    $image = get_the_post_thumbnail_url($post->ID, 'full') ?: get_site_url() . '/wp-content/themes/generatepress-child/favicon-32x32.png';
    $author = get_the_author();
    $siteName = get_bloginfo('name');
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $title,
        'description' => $excerpt,
        'image' => $image,
        'url' => $url,
        'datePublished' => $datePublished,
        'dateModified' => $dateModified,
        'author' => array(
            '@type' => 'Organization',
            'name' => $author,
            'url' => get_site_url(),
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => get_site_url(),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => get_site_url() . '/wp-content/uploads/2026/08/itin-logo-pakistan-01.png',
            ),
        ),
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => $url,
        ),
    );
    
    // Add source citation if available
    $source_url = get_post_meta($post->ID, '_itinpk_source_url', true);
    $source_name = get_post_meta($post->ID, '_itinpk_source_name', true);
    if ($source_url && $source_name) {
        $schema['isBasedOn'] = array(
            '@type' => 'WebPage',
            'url' => $source_url,
            'name' => $source_name,
        );
    }
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
add_action('wp_head', 'itinpk_article_schema');

// BreadcrumbList Schema
function itinpk_breadcrumb_schema() {
    if (!is_single() && !is_category() && !is_page()) return;
    
    $items = array();
    $items[] = array(
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => get_site_url(),
    );
    
    if (is_category()) {
        $items[] = array(
            '@type' => 'ListItem',
            'position' => 2,
            'name' => single_cat_title('', false),
        );
    } elseif (is_single()) {
        $categories = get_the_category();
        if (!empty($categories)) {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $categories[0]->name,
                'item' => get_category_link($categories[0]->term_id),
            );
        }
        $items[] = array(
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => get_the_title(),
        );
    } elseif (is_page()) {
        $items[] = array(
            '@type' => 'ListItem',
            'position' => 2,
            'name' => get_the_title(),
        );
    }
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    );
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
add_action('wp_head', 'itinpk_breadcrumb_schema');

// og:image fallback
function itinpk_og_image_fallback() {
    if (is_single()) {
        global $post;
        $image = get_the_post_thumbnail_url($post->ID, 'full');
        if (!$image) {
            $image = get_site_url() . '/wp-content/uploads/2026/08/itin-logo-pakistan-01.png';
        }
        echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
        echo '<meta property="og:image:width" content="1200" />' . "\n";
        echo '<meta property="og:image:height" content="630" />' . "\n";
    } elseif (is_home() || is_front_page()) {
        echo '<meta property="og:image" content="' . esc_url(get_site_url() . '/wp-content/uploads/2026/08/itin-logo-pakistan-01.png') . '" />' . "\n";
    }
}
add_action('wp_head', 'itinpk_og_image_fallback', 1);

// ===========================================================================
// AD/BANNER FRAMEWORK — Custom Post Type + Placement
// ===========================================================================

function itinpk_register_ad_cpt() {
    $labels = array(
        'name' => 'Ad Placements',
        'singular_name' => 'Ad Placement',
        'menu_name' => 'Ads',
        'add_new' => 'Add New Ad',
        'add_new_item' => 'Add New Ad Placement',
        'edit_item' => 'Edit Ad',
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-megaphone',
        'menu_position' => 6,
    );
    register_post_type('itinpk_ad', $args);
}
add_action('init', 'itinpk_register_ad_cpt');

function itinpk_ad_meta_boxes() {
    add_meta_box('itinpk_ad_settings', 'Ad Settings', 'itinpk_ad_settings_callback', 'itinpk_ad', 'normal', 'high');
}
add_action('add_meta_boxes', 'itinpk_ad_meta_boxes');

function itinpk_ad_settings_callback($post) {
    wp_nonce_field('itinpk_ad_nonce', 'itinpk_ad_nonce_field');
    $slot = get_post_meta($post->ID, '_itinpk_ad_slot', true);
    $url = get_post_meta($post->ID, '_itinpk_ad_url', true);
    $start = get_post_meta($post->ID, '_itinpk_ad_start_date', true);
    $end = get_post_meta($post->ID, '_itinpk_ad_end_date', true);
    $is_active = get_post_meta($post->ID, '_itinpk_ad_is_active', true);
    ?>
    <p><label><strong>Placement Slot:</strong></label>
    <select name="itinpk_ad_slot" style="width:200px;">
        <option value="sidebar" <?php selected($slot, 'sidebar'); ?>>Sidebar</option>
        <option value="hero-banner" <?php selected($slot, 'hero-banner'); ?>>Homepage Hero Banner</option>
        <option value="article-mid" <?php selected($slot, 'article-mid'); ?>>Mid-Article</option>
        <option value="footer-banner" <?php selected($slot, 'footer-banner'); ?>>Footer Banner</option>
    </select></p>
    <p><label><strong>Destination URL:</strong></label>
    <input type="url" name="itinpk_ad_url" value="<?php echo esc_url($url); ?>" style="width:400px;" placeholder="https://example.com" /></p>
    <p><label><strong>Start Date:</strong></label> <input type="date" name="itinpk_ad_start_date" value="<?php echo esc_attr($start); ?>" />
    <label style="margin-left:20px;"><strong>End Date:</strong></label> <input type="date" name="itinpk_ad_end_date" value="<?php echo esc_attr($end); ?>" /></p>
    <p><label><input type="checkbox" name="itinpk_ad_is_active" value="1" <?php checked($is_active, '1'); ?> /> Active (display on site)</label></p>
    <p class="description">Upload a banner image as the featured image. Recommended sizes: Sidebar 300×250, Hero 728×90, Mid-Article 728×90, Footer 728×90.</p>
    <?php
}

function itinpk_save_ad_meta($post_id) {
    if (!isset($_POST['itinpk_ad_nonce_field']) || !wp_verify_nonce($_POST['itinpk_ad_nonce_field'], 'itinpk_ad_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = array(
        'itinpk_ad_slot' => '_itinpk_ad_slot',
        'itinpk_ad_url' => '_itinpk_ad_url',
        'itinpk_ad_start_date' => '_itinpk_ad_start_date',
        'itinpk_ad_end_date' => '_itinpk_ad_end_date',
    );
    foreach ($fields as $field => $meta) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $meta, sanitize_text_field($_POST[$field]));
        }
    }
    update_post_meta($post_id, '_itinpk_ad_is_active', isset($_POST['itinpk_ad_is_active']) ? '1' : '0');
}
add_action('save_post', 'itinpk_save_ad_meta');

// Ad display shortcode: [itinpk_ad slot="sidebar"]
function itinpk_ad_shortcode($atts) {
    $atts = shortcode_atts(array('slot' => 'sidebar'), $atts);
    $now = current_time('mysql');
    
    $ads = get_posts(array(
        'post_type' => 'itinpk_ad',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'meta_query' => array(
            array('key' => '_itinpk_ad_slot', 'value' => $atts['slot']),
            array('key' => '_itinpk_ad_is_active', 'value' => '1'),
            array('key' => '_itinpk_ad_start_date', 'value' => $now, 'compare' => '<=', 'type' => 'DATE'),
            array('key' => '_itinpk_ad_end_date', 'value' => $now, 'compare' => '>=', 'type' => 'DATE', 'OR' => array(
                array('key' => '_itinpk_ad_end_date', 'value' => '', 'compare' => '='),
            )),
        ),
        'orderby' => 'rand',
    ));
    
    if (empty($ads)) return '';
    
    $ad = $ads[0];
    $img = get_the_post_thumbnail_url($ad->ID, 'full');
    $url = get_post_meta($ad->ID, '_itinpk_ad_url', true);
    
    if (!$img) return '';
    
    $output = '<div class="itinpk-ad-slot itinpk-ad-' . esc_attr($atts['slot']) . '">';
    if ($url) {
        $output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener sponsored">';
    }
    $output .= '<img src="' . esc_url($img) . '" alt="Advertisement" loading="lazy" style="width:100%;height:auto;display:block;border-radius:6px;" />';
    if ($url) {
        $output .= '</a>';
    }
    $output .= '<span class="itinpk-ad-label">Sponsored</span>';
    $output .= '</div>';
    return $output;
}
add_shortcode('itinpk_ad', 'itinpk_ad_shortcode');

// Auto-insert mid-article ad on single posts
function itinpk_auto_mid_article_ad($content) {
    if (!is_single() || !is_singular('post')) return $content;
    
    $ad = get_posts(array(
        'post_type' => 'itinpk_ad',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'meta_query' => array(
            array('key' => '_itinpk_ad_slot', 'value' => 'article-mid'),
            array('key' => '_itinpk_ad_is_active', 'value' => '1'),
        ),
        'orderby' => 'rand',
    ));
    
    if (empty($ad)) return $content;
    
    $img = get_the_post_thumbnail_url($ad[0]->ID, 'full');
    $url = get_post_meta($ad[0]->ID, '_itinpk_ad_url', true);
    if (!$img) return $content;
    
    $ad_html = '<div class="itinpk-ad-slot itinpk-ad-article-mid" style="margin:24px 0;text-align:center;">';
    if ($url) $ad_html .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener sponsored">';
    $ad_html .= '<img src="' . esc_url($img) . '" alt="Advertisement" loading="lazy" style="max-width:728px;width:100%;height:auto;border-radius:6px;" />';
    if ($url) $ad_html .= '</a>';
    $ad_html .= '<span class="itinpk-ad-label" style="font-size:11px;color:#999;">Sponsored</span>';
    $ad_html .= '</div>';
    
    $paragraphs = explode('</p>', $content);
    if (count($paragraphs) > 4) {
        $mid = intdiv(count($paragraphs), 2);
        array_splice($paragraphs, $mid, 0, $ad_html);
    }
    return implode('</p>', $paragraphs);
}
add_filter('the_content', 'itinpk_auto_mid_article_ad', 20);

// ===========================================================================
// PRESS RELEASE SUBMISSION — Custom Post Type + Frontend Form
// ===========================================================================

function itinpk_register_press_release_cpt() {
    $labels = array(
        'name' => 'Press Releases',
        'singular_name' => 'Press Release',
        'menu_name' => 'Press Releases',
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-megaphone',
        'menu_position' => 7,
        'map_meta_cap' => true,
    );
    register_post_type('press_release', $args);
}
add_action('init', 'itinpk_register_press_release_cpt');

function itinpk_press_release_meta_boxes() {
    add_meta_box('itinpk_pr_settings', 'Press Release Details', 'itinpk_pr_settings_callback', 'press_release', 'normal', 'high');
}
add_action('add_meta_boxes', 'itinpk_press_release_meta_boxes');

function itinpk_pr_settings_callback($post) {
    wp_nonce_field('itinpk_pr_nonce', 'itinpk_pr_nonce_field');
    $org = get_post_meta($post->ID, '_itinpk_pr_organization', true);
    $submitter = get_post_meta($post->ID, '_itinpk_pr_submitter_name', true);
    $email = get_post_meta($post->ID, '_itinpk_pr_submitter_email', true);
    $phone = get_post_meta($post->ID, '_itinpk_pr_submitter_phone', true);
    ?>
    <p><label><strong>Organization:</strong></label> <input type="text" name="itinpk_pr_organization" value="<?php echo esc_attr($org); ?>" style="width:400px;" /></p>
    <p><label><strong>Submitter Name:</strong></label> <input type="text" name="itinpk_pr_submitter_name" value="<?php echo esc_attr($submitter); ?>" style="width:300px;" /></p>
    <p><label><strong>Submitter Email:</strong></label> <input type="email" name="itinpk_pr_submitter_email" value="<?php echo esc_attr($email); ?>" style="width:300px;" /></p>
    <p><label><strong>Submitter Phone:</strong></label> <input type="text" name="itinpk_pr_submitter_phone" value="<?php echo esc_attr($phone); ?>" style="width:200px;" /></p>
    <?php
}

function itinpk_save_pr_meta($post_id) {
    if (!isset($_POST['itinpk_pr_nonce_field']) || !wp_verify_nonce($_POST['itinpk_pr_nonce_field'], 'itinpk_pr_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    $fields = array(
        'itinpk_pr_organization' => '_itinpk_pr_organization',
        'itinpk_pr_submitter_name' => '_itinpk_pr_submitter_name',
        'itinpk_pr_submitter_email' => '_itinpk_pr_submitter_email',
        'itinpk_pr_submitter_phone' => '_itinpk_pr_submitter_phone',
    );
    foreach ($fields as $field => $meta) {
        if (isset($_POST[$field])) update_post_meta($post_id, $meta, sanitize_text_field($_POST[$field]));
    }
}
add_action('save_post', 'itinpk_save_pr_meta');

// Frontend press release submission handler
function itinpk_press_release_submit() {
    if (!isset($_POST['pr_title'], $_POST['pr_org'], $_POST['pr_name'], $_POST['pr_email'], $_POST['pr_content'])) {
        wp_redirect(home_url('/press-release/?error=missing'));
        exit;
    }
    
    $title = sanitize_text_field($_POST['pr_title']);
    $org = sanitize_text_field($_POST['pr_org']);
    $name = sanitize_text_field($_POST['pr_name']);
    $email = sanitize_email($_POST['pr_email']);
    $phone = sanitize_text_field($_POST['pr_phone'] ?? '');
    $content = sanitize_textarea_field($_POST['pr_content']);
    $category = intval($_POST['pr_category'] ?? 0);
    
    if (!is_email($email)) {
        wp_redirect(home_url('/press-release/?error=invalid_email'));
        exit;
    }
    
    $post_id = wp_insert_post(array(
        'post_title' => $title,
        'post_content' => $content,
        'post_type' => 'press_release',
        'post_status' => 'pending', // Goes to moderation
    ));
    
    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, '_itinpk_pr_organization', $org);
        update_post_meta($post_id, '_itinpk_pr_submitter_name', $name);
        update_post_meta($post_id, '_itinpk_pr_submitter_email', $email);
        update_post_meta($post_id, '_itinpk_pr_submitter_phone', $phone);
        
        // Notify admin
        $to = get_option('admin_email');
        $subj = "[iTIN Press Release] New submission from $org";
        $body = "Organization: $org\nSubmitter: $name ($email)\nTitle: $title\n\nContent:\n$content\n\nReview: " . admin_url('post.php?post=' . $post_id . '&action=edit');
        wp_mail($to, $subj, $body);
        
        wp_redirect(home_url('/press-release/?success=1'));
    } else {
        wp_redirect(home_url('/press-release/?error=failed'));
    }
    exit;
}
add_action('admin_post_itinpk_press_release_submit', 'itinpk_press_release_submit');
add_action('admin_post_nopriv_itinpk_press_release_submit', 'itinpk_press_release_submit');

// ===========================================================================
// ENHANCED MEMBERSHIP — User Profiles, Bookmarks, Comments
// ===========================================================================

// Enable comments on all posts
function itinpk_enable_comments() {
    add_post_type_support('post', 'comments');
}
add_action('init', 'itinpk_enable_comments');

// User bookmarks/favorites system
function itinpk_toggle_bookmark_handler() {
    check_ajax_referer('itinpk_bookmark_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error('Please log in to bookmark articles.');
    
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id) wp_send_json_error('Invalid article.');
    
    $bookmarks = get_user_meta($user_id, '_itinpk_bookmarks', true);
    if (!is_array($bookmarks)) $bookmarks = array();
    
    if (in_array($post_id, $bookmarks)) {
        $bookmarks = array_diff($bookmarks, array($post_id));
        $action = 'removed';
    } else {
        $bookmarks[] = $post_id;
        $action = 'added';
    }
    
    update_user_meta($user_id, '_itinpk_bookmarks', array_values($bookmarks));
    wp_send_json_success(array('action' => $action, 'count' => count($bookmarks)));
}
add_action('wp_ajax_itinpk_toggle_bookmark', 'itinpk_toggle_bookmark_handler');
add_action('wp_ajax_nopriv_itinpk_toggle_bookmark', 'itinpk_toggle_bookmark_handler');

// Bookmark button on single posts
function itinpk_bookmark_button() {
    if (!is_single()) return;
    $user_id = get_current_user_id();
    $post_id = get_the_ID();
    $bookmarks = $user_id ? get_user_meta($user_id, '_itinpk_bookmarks', true) : array();
    $is_bookmarked = is_array($bookmarks) && in_array($post_id, $bookmarks);
    ?>
    <div class="itinpk-bookmark-wrap" style="margin:16px 0;">
        <button class="itinpk-bookmark-btn" data-post-id="<?php echo $post_id; ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#555;transition:all 0.2s;">
            <span class="bookmark-icon"><?php echo $is_bookmarked ? '&#9733;' : '&#9734;'; ?></span>
            <span class="bookmark-text"><?php echo $is_bookmarked ? 'Bookmarked' : 'Bookmark'; ?></span>
        </button>
        <span class="bookmark-count" style="font-size:12px;color:#999;margin-left:8px;"></span>
    </div>
    <script>
    document.querySelector('.itinpk-bookmark-btn')?.addEventListener('click', function() {
        var btn = this;
        var postId = btn.getAttribute('data-post-id');
        var fd = new FormData();
        fd.append('action', 'itinpk_toggle_bookmark');
        fd.append('post_id', postId);
        fd.append('nonce', '<?php echo wp_create_nonce('itinpk_bookmark_nonce'); ?>');
        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {method:'POST', body:fd})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.success) {
                btn.querySelector('.bookmark-icon').textContent = d.data.action === 'added' ? '\u2605' : '\u2606';
                btn.querySelector('.bookmark-text').textContent = d.data.action === 'added' ? 'Bookmarked' : 'Bookmark';
                btn.style.color = d.data.action === 'added' ? '#0881BE' : '#555';
                btn.style.borderColor = d.data.action === 'added' ? '#0881BE' : '#ddd';
            } else {
                alert(d.data);
            }
        });
    });
    </script>
    <?php
}
add_action('generate_after_entry_content', 'itinpk_bookmark_button');

// My Bookmarks page shortcode: [itinpk_bookmarks]
function itinpk_bookmarks_shortcode() {
    $user_id = get_current_user_id();
    if (!$user_id) return '<p>Please <a href="' . wp_login_url(home_url('/my-bookmarks/')) . '">log in</a> to view your bookmarks.</p>';
    
    $bookmarks = get_user_meta($user_id, '_itinpk_bookmarks', true);
    if (!is_array($bookmarks) || empty($bookmarks)) return '<p>No bookmarks yet. Browse articles and click the bookmark button to save them here.</p>';
    
    $output = '<div class="itinpk-bookmarks-list">';
    foreach (array_reverse($bookmarks) as $pid) {
        $post = get_post($pid);
        if (!$post || $post->post_status !== 'publish') continue;
        $img = get_the_post_thumbnail_url($pid, 'thumbnail');
        $cats = get_the_category($pid);
        $cat_name = !empty($cats) ? $cats[0]->name : '';
        $cat_color = '#0881BE';
        if (!empty($cats)) {
            switch ($cats[0]->slug) {
                case 'cybersecurity-tech': $cat_color = '#e74c3c'; break;
                case 'ai-cloud': $cat_color = '#8e44ad'; break;
                case 'startups-entrepreneurs': $cat_color = '#37215F'; break;
                case 'reviews': $cat_color = '#27ae60'; break;
            }
        }
        $output .= '<div style="display:flex;gap:16px;padding:16px 0;border-bottom:1px solid #eee;">';
        if ($img) $output .= '<img src="' . esc_url($img) . '" alt="" style="width:120px;height:80px;object-fit:cover;border-radius:8px;flex-shrink:0;" />';
        $output .= '<div style="flex:1;">';
        if ($cat_name) $output .= '<span style="font-size:11px;font-weight:700;color:' . $cat_color . ';text-transform:uppercase;">' . esc_html($cat_name) . '</span>';
        $output .= '<h3 style="margin:4px 0;font-size:16px;font-weight:700;"><a href="' . get_permalink($pid) . '" style="color:#111;text-decoration:none;">' . esc_html($post->post_title) . '</a></h3>';
        $output .= '<span style="font-size:12px;color:#999;">' . get_the_date('M j, Y', $pid) . '</span>';
        $output .= '</div></div>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('itinpk_bookmarks', 'itinpk_bookmarks_shortcode');

// ===========================================================================
// VIDEO ARCHIVE — Searchable/Filterable Page
// ===========================================================================

function itinpk_video_archive_shortcode() {
    // Get all web shows with YouTube URLs
    $shows = get_posts(array(
        'post_type' => 'web_show',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    
    // Collect categories for filter
    $cat_slugs = array('all', 'it-news', 'cybersecurity', 'ai-cloud', 'startups');
    $cat_labels = array('All', 'IT News', 'Cybersecurity', 'AI & Cloud', 'Startups');
    
    $output = '<div class="itinpk-video-archive">';
    $output .= '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">';
    foreach ($cat_slugs as $i => $slug) {
        $active = $i === 0 ? 'background:#37215F;color:#fff;' : 'background:#f0f0f0;color:#555;';
        $output .= '<button class="itinpk-va-filter" data-cat="' . $slug . '" style="padding:6px 16px;border:none;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s;' . $active . '">' . $cat_labels[$i] . '</button>';
    }
    $output .= '</div>';
    $output .= '<div class="itinpk-va-search" style="margin-bottom:20px;"><input type="text" placeholder="Search episodes..." class="itinpk-va-search-input" style="width:100%;max-width:400px;padding:10px 16px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;outline:none;" /></div>';
    $output .= '<div class="itinpk-va-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">';
    
    if (empty($shows)) {
        $output .= '<p style="color:#999;text-align:center;grid-column:1/-1;padding:40px;">No episodes found.</p>';
    } else {
        foreach ($shows as $show) {
            $yt_url = get_post_meta($show->ID, '_itinpk_youtube_url', true);
            $guest = get_post_meta($show->ID, '_itinpk_guest_name', true);
            $ep_num = get_post_meta($show->ID, '_itinpk_episode_number', true);
            $is_live = get_post_meta($show->ID, '_itinpk_is_live', true);
            $vid_id = itinpk_extract_youtube_id($yt_url);
            $thumb = $vid_id ? "https://img.youtube.com/vi/$vid_id/hqdefault.jpg" : '';
            $cats = get_the_category($show->ID);
            $cat_names = array();
            foreach ($cats as $c) $cat_names[] = $c->slug;
            $cat_str = implode(' ', $cat_names);
            
            $output .= '<div class="itinpk-va-card" data-cats="' . esc_attr($cat_str) . '" data-title="' . esc_attr(strtolower($show->post_title)) . '" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);transition:transform 0.2s,box-shadow 0.2s;">';
            $output .= '<a href="' . get_permalink($show->ID) . '" style="text-decoration:none;color:inherit;display:block;">';
            if ($thumb) $output .= '<div style="position:relative;padding-top:56.25%;overflow:hidden;"><img src="' . esc_url($thumb) . '" alt="" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;" />' . ($is_live ? '<span style="position:absolute;top:8px;left:8px;background:#e74c3c;color:#fff;font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;">LIVE</span>' : '') . '</div>';
            $output .= '<div style="padding:14px;">';
            if ($ep_num) $output .= '<span style="font-size:11px;font-weight:700;color:#0881BE;text-transform:uppercase;">EP ' . esc_html($ep_num) . '</span>';
            $output .= '<h4 style="margin:4px 0;font-size:14px;font-weight:700;line-height:1.4;">' . esc_html($show->post_title) . '</h4>';
            if ($guest) $output .= '<span style="font-size:12px;color:#999;">Guest: ' . esc_html($guest) . '</span>';
            $output .= '<div style="margin-top:8px;"><span style="font-size:11px;color:#999;">' . get_the_date('M j, Y', $show->ID) . '</span></div>';
            $output .= '</div></a></div>';
        }
    }
    
    $output .= '</div></div>';
    $output .= '<script>
    document.querySelectorAll(".itinpk-va-filter").forEach(function(btn){
        btn.addEventListener("click", function(){
            document.querySelectorAll(".itinpk-va-filter").forEach(function(b){b.style.background="#f0f0f0";b.style.color="#555";});
            btn.style.background="#37215F";btn.style.color="#fff";
            var cat=btn.getAttribute("data-cat");
            document.querySelectorAll(".itinpk-va-card").forEach(function(card){
                if(cat==="all"||card.getAttribute("data-cats").indexOf(cat)!==-1){card.style.display="";}
                else{card.style.display="none";}
            });
        });
    });
    document.querySelector(".itinpk-va-search-input")?.addEventListener("input",function(){
        var q=this.value.toLowerCase();
        document.querySelectorAll(".itinpk-va-card").forEach(function(card){
            card.style.display=card.getAttribute("data-title").indexOf(q)!==-1?"":"none";
        });
    });
    </script>';
    return $output;
}
add_shortcode('itinpk_video_archive', 'itinpk_video_archive_shortcode');

// ===========================================================================
// BACKUP AUTOMATION — Daily DB + Files Backup
// ===========================================================================

function itinpk_daily_backup() {
    $backup_dir = '/var/backups/itinpk';
    if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);
    
    $date = date('Y-m-d');
    
    // Database backup
    $db_name = 'itin_pakistan_db';
    $db_user = 'itin_pk_user';
    $db_pass = trim(shell_exec('cat /root/.itinpk_db_pass 2>/dev/null'));
    $db_file = "$backup_dir/db_$date.sql.gz";
    exec("mysqldump -u$db_user -p'" . addslashes($db_pass) . "' $db_name 2>/dev/null | gzip > $db_file");
    
    // Files backup (uploads + themes only — skip core WP files)
    $files_file = "$backup_dir/files_$date.tar.gz";
    exec("tar -czf $files_file -C /var/www/itinpk wp-content/uploads wp-content/themes 2>/dev/null");
    
    // Keep only last 7 days
    exec("find $backup_dir -name '*.gz' -mtime +7 -delete 2>/dev/null");
    
    // Log
    file_put_contents("$backup_dir/backup.log", date('Y-m-d H:i:s') . " — DB: " . (file_exists($db_file) ? 'OK' : 'FAIL') . " | Files: " . (file_exists($files_file) ? 'OK' : 'FAIL') . "\n", FILE_APPEND);
}

// Schedule daily backup at 3 AM UTC
if (!wp_next_scheduled('itinpk_daily_backup')) {
    wp_schedule_event(strtotime('today 3:00 UTC'), 'daily', 'itinpk_daily_backup');
}
add_action('itinpk_daily_backup', 'itinpk_daily_backup');

// ===========================================================================
// ROLE-BASED ACCESS — Create Editor & Contributor Roles
// ===========================================================================

function itinpk_create_roles() {
    // Editor role
    if (!get_role('itinpk_editor')) {
        $role = add_role('itinpk_editor', 'iTIN Editor', array(
            'read' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_others_posts' => true,
            'delete_published_posts' => true,
            'edit_web_show' => true,
            'edit_others_web_show' => true,
            'publish_web_show' => true,
            'delete_web_show' => true,
            'edit_press_release' => true,
            'edit_others_press_release' => true,
            'publish_press_release' => true,
            'manage_categories' => true,
            'moderate_comments' => true,
            'edit_theme_options' => false,
            'manage_options' => false,
            'install_plugins' => false,
            'edit_plugins' => false,
        ));
    }
    
    // Contributor role
    if (!get_role('itinpk_contributor')) {
        $role = add_role('itinpk_contributor', 'iTIN Contributor', array(
            'read' => true,
            'edit_posts' => true,
            'edit_published_posts' => false,
            'publish_posts' => false,
            'delete_posts' => false,
            'edit_web_show' => false,
            'publish_web_show' => false,
            'delete_web_show' => false,
        ));
    }
}
add_action('init', 'itinpk_create_roles');

// ===========================================================================
// PRESS RELEASE PAGE TEMPLATE
// ===========================================================================

function itinpk_press_release_page_template($template) {
    if (is_page('press-release')) {
        $custom = get_stylesheet_directory() . '/page-press-release.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}
add_filter('page_template', 'itinpk_press_release_page_template');

// ===========================================================================
// MY BOOKMARKS PAGE TEMPLATE
// ===========================================================================

function itinpk_bookmarks_page_template($template) {
    if (is_page('my-bookmarks')) {
        $custom = get_stylesheet_directory() . '/page-bookmarks.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}
add_filter('page_template', 'itinpk_bookmarks_page_template');

// ===========================================================================
// VIDEO ARCHIVE PAGE TEMPLATE
// ===========================================================================

function itinpk_video_archive_page_template($template) {
    if (is_page('video-archive')) {
        $custom = get_stylesheet_directory() . '/page-video-archive.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}
add_filter('page_template', 'itinpk_video_archive_page_template');

// ===========================================================================
// AD CSS
// ===========================================================================

function itinpk_ad_css() {
    ?>
    <style>
    .itinpk-ad-slot { margin: 16px 0; text-align: center; position: relative; }
    .itinpk-ad-label { font-size: 10px; color: #bbb; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; display: block; }
    .itinpk-ad-sidebar { max-width: 300px; margin: 0 auto 20px; }
    .itinpk-ad-hero-banner { max-width: 728px; margin: 0 auto 24px; }
    .itinpk-ad-article-mid { max-width: 728px; margin: 24px auto; }
    .itinpk-ad-footer-banner { max-width: 728px; margin: 0 auto; }
    </style>
    <?php
}
add_action('wp_head', 'itinpk_ad_css');

// ===========================================================================
// HOMEPAGE CACHE CLEAR ON POST PUBLISH
// ===========================================================================
// Clear the full-page super cache whenever a post is published or updated,
// so the homepage always reflects the latest content immediately.
function itinpk_clear_homepage_cache_on_publish( $post_id, $post, $update ) {
    // Skip autosaves, revisions, and non-publish transitions
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    if ( $post->post_status !== 'publish' ) {
        return;
    }

    // Clear WP Super Cache for the front page
    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        wp_cache_clear_cache( 'homepage' );
    }

    // Also delete the specific supercache HTML files
    $cache_dir = WP_CONTENT_DIR . '/cache/supercache/' . $_SERVER['HTTP_HOST'];
    if ( is_dir( $cache_dir ) ) {
        // Clear root cached files (homepage, feed, etc.)
        $files = glob( $cache_dir . '/*.html' );
        if ( $files ) {
            foreach ( $files as $file ) {
                @unlink( $file );
            }
        }
        // Clear category and tag caches too
        $dirs = glob( $cache_dir . '/*/' , GLOB_ONLYDIR );
        if ( $dirs ) {
            foreach ( $dirs as $dir ) {
                $subfiles = glob( $dir . '*.html' );
                if ( $subfiles ) {
                    foreach ( $subfiles as $subfile ) {
                        @unlink( $subfile );
                    }
                }
            }
        }
    }
}
add_action( 'transition_post_status', 'itinpk_clear_homepage_cache_on_publish', 10, 3 );

// ===========================================================================
// CATEGORY NEWS VIDEOS — Real-time YouTube videos per category
// ===========================================================================
function itinpk_category_videos_data() {
    $json_path = WP_CONTENT_DIR . '/uploads/category-videos/latest_videos.json';
    if ( ! file_exists( $json_path ) ) {
        return null;
    }
    $raw = file_get_contents( $json_path );
    if ( ! $raw ) {
        return null;
    }
    $data = json_decode( $raw, true );
    if ( ! $data || empty( $data['categories'] ) ) {
        return null;
    }
    return $data;
}

function itinpk_render_hero_video() {
    $data = itinpk_category_videos_data();
    if ( ! $data || empty( $data['hero'] ) ) {
        return false;
    }
    $hero = $data['hero'];
    $video_id = esc_attr( $hero['video_id'] );
    $title = esc_html( $hero['title'] );
    $thumb = esc_url( $hero['thumbnail'] );
    $url = esc_url( $hero['url'] );
    $channel = esc_html( $hero['channel'] );
    ?>
    <div class="hero-v2-player" id="hero-video-player">
        <img src="<?php echo $thumb; ?>" alt="<?php echo $title; ?>" class="hero-v2-poster" id="hero-video-poster" />
        <div class="hero-v2-overlay" id="hero-video-overlay" role="button" tabindex="0" aria-label="Play video">
            <div class="hero-v2-play-btn">
                <svg viewBox="0 0 68 48" xmlns="http://www.w3.org/2000/svg"><path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55C3.97 2.33 2.27 4.81 1.48 7.74.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26zM45.26 23.27L29.02 33.82V14.28l16.24 8.99z" fill="#fff"/></svg>
            </div>
        </div>
    </div>
    <div class="hero-v2-video-caption">
        <span class="hero-v2-channel"><?php echo $channel; ?></span>
        <a href="<?php echo $url; ?>" target="_blank" rel="noopener" class="hero-v2-yt-link">Watch on YouTube &#x2197;</a>
    </div>
    <script>
    (function(){
        var overlay = document.getElementById('hero-video-overlay');
        var player = document.getElementById('hero-video-player');
        if (!overlay || !player) return;
        function playVideo() {
            player.innerHTML = '<iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>?autoplay=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;"></iframe>';
        }
        overlay.addEventListener('click', playVideo);
        overlay.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); playVideo(); }
        });
    })();
    </script>
    <?php
    return true;
}

function itinpk_render_category_videos_section() {
    $data = itinpk_category_videos_data();
    if ( ! $data || empty( $data['categories'] ) ) {
        return;
    }

    $cat_colors = array(
        'it-news' => '#0881BE',
        'startups-entrepreneurs' => '#37215F',
        'cybersecurity-tech' => '#e74c3c',
        'ai-cloud' => '#8e44ad',
        'reviews' => '#27ae60',
    );

    // Collect one video per category for the strip
    $strip_videos = array();
    foreach ( $data['categories'] as $cat_slug => $cat_data ) {
        if ( ! empty( $cat_data['videos'] ) ) {
            $v = $cat_data['videos'][0]; // first/top video
            $v['cat_slug'] = $cat_slug;
            $v['cat_name'] = $cat_data['name'];
            $v['cat_color'] = isset( $cat_colors[ $cat_slug ] ) ? $cat_colors[ $cat_slug ] : '#0881BE';
            $strip_videos[] = $v;
        }
    }

    if ( empty( $strip_videos ) ) {
        return;
    }
    ?>
    <section class="cat-videos-strip">
        <div class="cat-videos-strip-header">
            <div class="cat-videos-strip-left">
                <span class="cat-videos-strip-play">
                    <svg viewBox="0 0 24 24" width="20" height="20"><polygon points="6,3 6,21 20,12" fill="#FF0000"/></svg>
                </span>
                <h2 class="cat-videos-strip-title">Latest Videos</h2>
            </div>
            <div class="cat-videos-strip-right">
                <span class="cat-videos-strip-updated">Updated <?php echo esc_html( human_time_diff( strtotime( $data['updated_at'] ), current_time( 'timestamp' ) ) ); ?> ago</span>
            </div>
        </div>

        <div class="cat-videos-strip-scroll">
            <?php foreach ( $strip_videos as $video ) : ?>
            <div class="cat-videos-strip-card" data-video-id="<?php echo esc_attr( $video['video_id'] ); ?>">
                <div class="cat-videos-strip-thumb" role="button" tabindex="0" aria-label="Play: <?php echo esc_attr( $video['title'] ); ?>">
                    <img src="<?php echo esc_url( $video['thumbnail'] ); ?>" alt="<?php echo esc_attr( $video['title'] ); ?>" loading="lazy" />
                    <div class="cat-videos-strip-play-btn">
                        <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><circle cx="24" cy="24" r="22" fill="rgba(0,0,0,0.6)" stroke="none"/><polygon points="18,12 18,36 38,24" fill="#FF0000"/></svg>
                    </div>
                    <span class="cat-videos-strip-badge" style="background:<?php echo esc_attr( $video['cat_color'] ); ?>;"><?php echo esc_html( $video['cat_name'] ); ?></span>
                </div>
                <div class="cat-videos-strip-info">
                    <h4 class="cat-videos-strip-card-title"><?php echo esc_html( $video['title'] ); ?></h4>
                    <span class="cat-videos-strip-channel"><?php echo esc_html( $video['channel'] ); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function itinpk_category_videos_modal() {
    ?>
    <div id="cat-video-modal" class="cat-video-modal" role="dialog" aria-modal="true" aria-label="Video player" style="display:none;">
        <div class="cat-video-modal-overlay"></div>
        <div class="cat-video-modal-container">
            <div class="cat-video-modal-header">
                <span class="cat-video-modal-title"></span>
                <a href="#" target="_blank" rel="noopener" class="cat-video-modal-yt-link">Watch on YouTube &#x2197;</a>
                <button class="cat-video-modal-close" aria-label="Close video">&times;</button>
            </div>
            <div class="cat-video-modal-body">
                <iframe src="" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>
            </div>
        </div>
    </div>
    <?php
}

function itinpk_category_videos_scripts() {
    if ( ! is_home() && ! is_front_page() ) return;
    ?>
    <script>
    (function(){
        // Video modal
        var modal = document.getElementById('cat-video-modal');
        if (!modal) return;
        var overlay = modal.querySelector('.cat-video-modal-overlay');
        var closeBtn = modal.querySelector('.cat-video-modal-close');
        var ytLink = modal.querySelector('.cat-video-modal-yt-link');
        var titleEl = modal.querySelector('.cat-video-modal-title');
        var iframe = modal.querySelector('iframe');

        function openModal(videoId, videoTitle) {
            if (iframe && videoId) {
                iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
            }
            if (ytLink) ytLink.href = 'https://www.youtube.com/watch?v=' + videoId;
            if (titleEl && videoTitle) titleEl.textContent = videoTitle;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            if (iframe) iframe.src = '';
        }

        var thumbs = document.querySelectorAll('.cat-videos-strip-thumb[data-video-id]');
        for (var i = 0; i < thumbs.length; i++) {
            (function(el) {
                var card = el.closest('.cat-videos-strip-card');
                var videoId = card.getAttribute('data-video-id');
                var title = card.querySelector('.cat-videos-strip-card-title');
                var videoTitle = title ? title.textContent : '';
                el.addEventListener('click', function() { openModal(videoId, videoTitle); });
                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(videoId, videoTitle); }
                });
            })(thumbs[i]);
        }

        overlay.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'itinpk_category_videos_modal' );
add_action( 'wp_footer', 'itinpk_category_videos_scripts' );
