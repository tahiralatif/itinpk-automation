<?php
/**
 * Template Name: About Us — Professional
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$stats = array(
    array( 'num' => '75+', 'label' => 'Articles Published' ),
    array( 'num' => '5',   'label' => 'News Categories' ),
    array( 'num' => '50+', 'label' => 'Sources Tracked' ),
    array( 'num' => '24h', 'label' => 'News Coverage' ),
);
?>

<!-- HERO -->
<section class="about-hero">
    <div class="about-hero-inner">
        <span class="about-hero-badge">EST. 2026</span>
        <h1 class="about-hero-title">iTIN Pakistan</h1>
        <p class="about-hero-sub">Pakistan's technology news portal — covering IT, startups, cybersecurity, AI, and digital innovation.</p>
    </div>
</section>

<!-- STATS BAR -->
<section class="about-stats">
    <div class="about-stats-inner">
        <?php foreach ( $stats as $s ) : ?>
            <div class="about-stat">
                <span class="about-stat-num"><?php echo esc_html( $s['num'] ); ?></span>
                <span class="about-stat-label"><?php echo esc_html( $s['label'] ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- MISSION -->
<section class="about-section">
    <div class="about-inner">
        <div class="about-mission-grid">
            <div class="about-mission-card">
                <div class="about-mission-icon" style="background:#0881BE;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                </div>
                <h3>Our Mission</h3>
                <p>To empower Pakistan's tech community with accurate, timely, and actionable information that drives informed decisions and inspires innovation.</p>
            </div>
            <div class="about-mission-card">
                <div class="about-mission-icon" style="background:#37215F;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <h3>Our Vision</h3>
                <p>To become the most trusted and comprehensive source of technology news for Pakistan's digital economy — the first stop for founders, investors, and tech professionals.</p>
            </div>
        </div>
    </div>
</section>

<!-- WHAT WE COVER -->
<section class="about-section about-section-alt">
    <div class="about-inner">
        <h2 class="about-section-title">What We Cover</h2>
        <p class="about-section-sub">Deep, focused coverage across five core verticals.</p>
        <div class="about-covers-grid">
            <div class="about-cover-card" style="border-top: 4px solid #0881BE;">
                <div class="about-cover-num" style="color:#0881BE;">01</div>
                <h4>IT Industry</h4>
                <p>Pakistan's $2.5B+ IT exports, policy changes, digital transformation, and industry trends shaping the tech landscape.</p>
            </div>
            <div class="about-cover-card" style="border-top: 4px solid #37215F;">
                <div class="about-cover-num" style="color:#37215F;">02</div>
                <h4>Startups & Funding</h4>
                <p>Tracking every investment round from pre-seed to Series C across Pakistan's growing startup ecosystem.</p>
            </div>
            <div class="about-cover-card" style="border-top: 4px solid #e74c3c;">
                <div class="about-cover-num" style="color:#e74c3c;">03</div>
                <h4>Cybersecurity</h4>
                <p>Threat intelligence, vulnerability alerts, data breaches, and security best practices for businesses and individuals.</p>
            </div>
            <div class="about-cover-card" style="border-top: 4px solid #8e44ad;">
                <div class="about-cover-num" style="color:#8e44ad;">04</div>
                <h4>AI & Cloud</h4>
                <p>Artificial intelligence, machine learning, cloud computing, and emerging technologies transforming Pakistan.</p>
            </div>
            <div class="about-cover-card" style="border-top: 4px solid #27ae60;">
                <div class="about-cover-num" style="color:#27ae60;">05</div>
                <h4>Reviews</h4>
                <p>Honest, detailed reviews of the latest gadgets, software, and digital services available in Pakistan.</p>
            </div>
        </div>
    </div>
</section>

<!-- VALUES -->
<section class="about-section">
    <div class="about-inner">
        <h2 class="about-section-title">Our Values</h2>
        <p class="about-section-sub">The principles that guide everything we publish.</p>
        <div class="about-values-grid">
            <div class="about-value">
                <div class="about-value-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0881BE" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h4>Fact-Checked Reporting</h4>
                <p>Every article is verified against primary sources before publication. We correct errors promptly and transparently.</p>
            </div>
            <div class="about-value">
                <div class="about-value-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#37215F" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h4>Independent Editorial</h4>
                <p>No paywalls on core news. No sponsored content disguised as journalism. Our editorial voice is our own.</p>
            </div>
            <div class="about-value">
                <div class="about-value-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <h4>Source Transparency</h4>
                <p>Every article cites its original source with a direct link. You can always verify where the information came from.</p>
            </div>
            <div class="about-value">
                <div class="about-value-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#8e44ad" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h4>Pakistan First</h4>
                <p>We prioritize Pakistani tech news — startups, policy, innovation — while covering global trends that directly impact the local ecosystem.</p>
            </div>
        </div>
    </div>
</section>

<!-- COVERAGE MAP (text) -->
<section class="about-section about-section-alt">
    <div class="about-inner">
        <div class="about-coverage-wrap">
            <div class="about-coverage-text">
                <h2>Pakistan's Tech Ecosystem, Covered</h2>
                <p>From Karachi's startup hubs to Islamabad's policy corridors, from Lahore's software houses to Peshawar's emerging tech talent — iTIN Pakistan covers the full spectrum of the country's digital transformation.</p>
                <p>We track IT exports, 5G rollouts, cybersecurity incidents, AI adoption, funding rounds, and product launches that shape Pakistan's $2.5 billion IT industry.</p>
            </div>
            <div class="about-coverage-cities">
                <span class="about-city">Karachi</span>
                <span class="about-city">Lahore</span>
                <span class="about-city">Islamabad</span>
                <span class="about-city">Peshawar</span>
                <span class="about-city">Faisalabad</span>
                <span class="about-city">Multan</span>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="about-cta">
    <div class="about-inner about-cta-inner">
        <h2>Stay Informed</h2>
        <p>Get the latest Pakistan tech news delivered to your inbox.</p>
        <form class="about-cta-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
            <input type="hidden" name="action" value="mailpoet_subscribe" />
            <input type="hidden" name="list_id" value="3" />
            <input type="email" name="email" placeholder="Enter your email" required class="about-cta-input" />
            <button type="submit" class="about-cta-btn">Subscribe</button>
        </form>
        <p class="about-cta-note">No spam. Unsubscribe anytime.</p>
    </div>
</section>

<?php get_footer(); ?>
