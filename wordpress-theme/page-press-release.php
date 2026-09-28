<?php
/**
 * Template: Press Release / Submit News
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<div class="contact-hero" style="background:linear-gradient(135deg,#1a1130,#37215F 50%,#e74c3c);">
    <div class="contact-hero-inner">
        <h1 class="contact-hero-title">Press Release</h1>
        <p class="contact-hero-sub">Submit your technology news, product launches, and company announcements for editorial review.</p>
    </div>
</div>

<div style="max-width:800px;margin:48px auto;padding:0 28px;">
<?php if (isset($_GET['success'])): ?>
    <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:20px;margin-bottom:24px;text-align:center;">
        <h3 style="color:#155724;margin:0 0 8px;">Submission Received!</h3>
        <p style="color:#155724;margin:0;">Your press release has been submitted for editorial review. We will review it and publish if approved. You will receive an email notification.</p>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;padding:20px;margin-bottom:24px;">
        <p style="color:#721c24;margin:0;">Error: <?php echo esc_html($_GET['error']); ?>. Please try again.</p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div style="text-align:center;padding:40px 0;">
    <a href="<?php echo home_url('/'); ?>" style="background:#37215F;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;">Back to Homepage</a>
</div>
<?php else: ?>

<div style="background:#fff;border-radius:12px;padding:36px;box-shadow:0 2px 10px rgba(0,0,0,0.05);margin-bottom:32px;">
    <h2 style="font-size:24px;font-weight:800;margin:0 0 6px;color:#111;">Submit a Press Release</h2>
    <p style="color:#666;margin:0 0 28px;">Fill out the form below. All submissions are reviewed by our editorial team before publishing.</p>

    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="itinpk_press_release_submit" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:6px;">Organization *</label>
                <input type="text" name="pr_org" required style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;outline:none;" placeholder="Company name" />
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:6px;">Contact Name *</label>
                <input type="text" name="pr_name" required style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;outline:none;" placeholder="Your name" />
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:6px;">Email *</label>
                <input type="email" name="pr_email" required style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;outline:none;" placeholder="contact@company.com" />
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:6px;">Phone</label>
                <input type="text" name="pr_phone" style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;outline:none;" placeholder="+92 300 1234567" />
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:6px;">Press Release Title *</label>
            <input type="text" name="pr_title" required style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;outline:none;" placeholder="Headline for your press release" />
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:6px;">Category</label>
            <select name="pr_category" style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;outline:none;">
                <option value="17">IT News</option>
                <option value="16">Startups & Entrepreneurs</option>
                <option value="11">Cybersecurity & Tech</option>
                <option value="14">AI & Cloud</option>
                <option value="18">Reviews</option>
            </select>
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:6px;">Press Release Content *</label>
            <textarea name="pr_content" required rows="12" style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;outline:none;resize:vertical;" placeholder="Write your press release here. Include key details, quotes, and data."></textarea>
        </div>

        <button type="submit" style="background:#37215F;color:#fff;border:none;padding:14px 28px;border-radius:6px;font-size:15px;font-weight:700;cursor:pointer;transition:background 0.2s;">Submit for Review</button>
    </form>
</div>

<div style="background:#f8f9fa;border-radius:8px;padding:20px;">
    <h3 style="font-size:16px;font-weight:700;margin:0 0 8px;">Submission Guidelines</h3>
    <ul style="margin:0;padding-left:20px;color:#555;font-size:14px;line-height:1.8;">
        <li>Only technology-related press releases will be considered</li>
        <li>Submissions are reviewed within 24-48 hours</li>
        <li>We reserve the right to edit for clarity and style</li>
        <li>Include official logos and images when possible</li>
        <li>Contact information will be used for verification only</li>
    </ul>
</div>
<?php endif; ?>
</div>
<?php get_footer(); ?>
