<?php
/**
 * Footer template — Clean professional layout.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<footer class="site-footer">
    <!-- Main Footer -->
    <div class="footer-main">
        <div class="footer-main-inner">

            <!-- Brand Column -->
            <div class="footer-brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer-logo-link">
                    <?php if ( has_custom_logo() ) : ?>
                        <?php
                        $logo_id = get_theme_mod( 'custom_logo' );
                        if ( $logo_id ) {
                            $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
                            echo '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" class="custom-logo" />';
                        }
                        ?>
                    <?php else : ?>
                        <span class="footer-site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                    <?php endif; ?>
                </a>
                <p class="footer-tagline">Pakistan's technology news portal — IT, startups, cybersecurity, AI, and digital innovation.</p>
                <div class="footer-contact-row">
                    <a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="footer-contact-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?php echo esc_html( get_option( 'admin_email' ) ); ?>
                    </a>
                    <a href="https://api.whatsapp.com/send/?text=Hi%20iTIN%20Pakistan%2C%20I%27d%20like%20to%20get%20in%20touch." target="_blank" rel="noopener" class="footer-contact-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp
                    </a>
                </div>
                <div class="footer-social">
                    <a href="https://api.whatsapp.com/send/?text=Hi%20iTIN%20Pakistan%2C%20I%27d%20like%20to%20get%20in%20touch." class="footer-social-link" aria-label="WhatsApp" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Links Columns -->
            <div class="footer-links-group">
                <div class="footer-col">
                    <h4 class="footer-col-title">Categories</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo esc_url( home_url( '/category/it-news/' ) ); ?>">IT News</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/category/startups-entrepreneurs/' ) ); ?>">Startups & Entrepreneurs</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/category/cybersecurity-tech/' ) ); ?>">Cybersecurity & Tech</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/category/ai-cloud/' ) ); ?>">AI & Cloud</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/category/reviews/' ) ); ?>">Reviews</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-col-title">Company</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">About Us</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/editorial-team/' ) ); ?>">Editorial Team</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/advertise-sponsor/' ) ); ?>">Advertise</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/press-release-submit-news/' ) ); ?>">Submit News</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4 class="footer-col-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>">Terms of Service</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/cookie-data-policy/' ) ); ?>">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsletter Bar -->
    <div class="footer-newsletter">
        <div class="footer-newsletter-inner">
            <div class="footer-newsletter-text">
                <h4>Stay Ahead in Tech</h4>
                <p>Get the latest IT news, startup updates, and cybersecurity alerts delivered to your inbox.</p>
            </div>
            <form class="footer-newsletter-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="mailpoet_subscribe" />
                <input type="hidden" name="list_id" value="3" />
                <input type="email" name="email" placeholder="Enter your email address" class="footer-newsletter-input" required />
                <button type="submit" class="footer-newsletter-btn">Subscribe</button>
            </form>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom">
        <div class="footer-bottom-inner">
            <p>&copy; <?php echo date( 'Y' ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. All Rights Reserved.</p>
            <p class="footer-credit">Part of iTIN Pakistan Technology Network.</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
