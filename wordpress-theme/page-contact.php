<?php
/**
 * Template Name: Contact Us — Professional
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<!-- Hero -->
<section class="contact-hero">
    <div class="contact-hero-inner">
        <h1 class="contact-hero-title">Contact Us</h1>
        <p class="contact-hero-sub">Have a tip, question, or partnership inquiry? We'd love to hear from you.</p>
    </div>
</section>

<!-- Quick Contact Cards -->
<section class="contact-cards">
    <div class="contact-cards-inner">
        <a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" class="contact-card">
            <div class="contact-card-icon" style="background: #0881BE;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <h3>Email Us</h3>
            <p><?php echo esc_html( get_option( 'admin_email' ) ); ?></p>
            <span class="contact-card-action">Send Email &rarr;</span>
        </a>
        <a href="https://api.whatsapp.com/send/?text=Hi%20iTIN%20Pakistan%2C%20I%27d%20like%20to%20get%20in%20touch." target="_blank" rel="noopener" class="contact-card">
            <div class="contact-card-icon" style="background: #25d366;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </div>
            <h3>WhatsApp</h3>
            <p>Chat with us directly</p>
            <span class="contact-card-action">Open WhatsApp &rarr;</span>
        </a>
        <a href="<?php echo esc_url( home_url( '/press-release-submit-news/' ) ); ?>" class="contact-card">
            <div class="contact-card-icon" style="background: #37215F;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <h3>Submit News</h3>
            <p>Send us a press release or tip</p>
            <span class="contact-card-action">Submit &rarr;</span>
        </a>
    </div>
</section>

<!-- Main Content -->
<section class="contact-main">
    <div class="contact-main-inner">

        <!-- Contact Form -->
        <div class="contact-form-wrap">
            <h2 class="contact-section-title">Send Us a Message</h2>
            <p class="contact-section-desc">Fill out the form below and we'll get back to you within 24 hours.</p>

            <form class="contact-form" id="contactForm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="itinpk_contact_submit" />

                <div class="contact-form-row">
                    <div class="contact-form-group">
                        <label for="contact-name">Name</label>
                        <input type="text" id="contact-name" name="name" placeholder="Your full name" required />
                    </div>
                    <div class="contact-form-group">
                        <label for="contact-email">Email</label>
                        <input type="email" id="contact-email" name="email" placeholder="you@example.com" required />
                    </div>
                </div>

                <div class="contact-form-group">
                    <label for="contact-subject">Subject</label>
                    <select id="contact-subject" name="subject" required>
                        <option value="">Select a topic...</option>
                        <option value="editorial">Editorial Inquiry</option>
                        <option value="tip">News Tip</option>
                        <option value="advertising">Advertising & Sponsorship</option>
                        <option value="partnership">Partnership</option>
                        <option value="technical">Technical Issue</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="contact-form-group">
                    <label for="contact-message">Message</label>
                    <textarea id="contact-message" name="message" rows="6" placeholder="Tell us what's on your mind..." required></textarea>
                </div>

                <button type="submit" class="contact-form-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Send Message
                </button>
            </form>

            <div class="contact-form-success" id="contactSuccess" style="display:none;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <h3>Message Sent!</h3>
                <p>Thank you for reaching out. We'll get back to you within 24 hours.</p>
            </div>
        </div>

        <!-- Contact Info Sidebar -->
        <div class="contact-info-sidebar">
            <div class="contact-info-card">
                <h3 class="contact-info-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0881BE" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Response Time
                </h3>
                <p>We typically respond within <strong>24 hours</strong> during business days. For urgent matters, reach us on WhatsApp.</p>
            </div>

            <div class="contact-info-card">
                <h3 class="contact-info-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#37215F" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Office
                </h3>
                <p>iTIN Pakistan<br>Karachi, Pakistan</p>
            </div>

            <div class="contact-info-card">
                <h3 class="contact-info-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Business Hours
                </h3>
                <p>Monday - Friday: 9 AM - 6 PM<br>Saturday: 10 AM - 2 PM<br>Sunday: Closed</p>
            </div>

            <div class="contact-info-card">
                <h3 class="contact-info-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8e44ad" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Departments
                </h3>
                <ul class="contact-dept-list">
                    <li><strong>Editorial:</strong> editorial@itinpk.14.jugaar.ai</li>
                    <li><strong>Advertising:</strong> ads@itinpk.14.jugaar.ai</li>
                    <li><strong>Technical:</strong> support@itinpk.14.jugaar.ai</li>
                </ul>
            </div>
        </div>

    </div>
</section>

<!-- FAQ Section -->
<section class="contact-faq">
    <div class="contact-faq-inner">
        <h2 class="contact-faq-title">Frequently Asked Questions</h2>
        <div class="contact-faq-grid">
            <div class="contact-faq-item">
                <h4>How do I submit a news tip?</h4>
                <p>You can submit news tips via email at <a href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"><?php echo esc_html( get_option( 'admin_email' ) ); ?></a> or use our <a href="<?php echo esc_url( home_url( '/press-release-submit-news/' ) ); ?>">Submit News</a> page. Include as much detail as possible.</p>
            </div>
            <div class="contact-faq-item">
                <h4>How can I advertise on iTIN Pakistan?</h4>
                <p>Visit our <a href="<?php echo esc_url( home_url( '/advertise-sponsor/' ) ); ?>">Advertise page</a> for sponsorship packages, display ads, and content partnerships. We offer flexible options for all budgets.</p>
            </div>
            <div class="contact-faq-item">
                <h4>Do you accept guest posts?</h4>
                <p>Yes! We welcome expert contributions from industry professionals. Send your pitch to editorial@itinpk.14.jugaar.ai with your topic idea and credentials.</p>
            </div>
            <div class="contact-faq-item">
                <h4>How do I report an error in an article?</h4>
                <p>We take accuracy seriously. Email us at editorial@itinpk.14.jugaar.ai with the article URL and the correction needed. We'll update the article promptly.</p>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
