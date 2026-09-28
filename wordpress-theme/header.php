<?php
/**
 * Two-tier header for iTIN Pakistan.
 * Tier 1: Utility bar (dark) — date, search, WhatsApp, auth
 * Tier 2: Main nav — logo, 6 visible items, "More" dropdown, hamburger
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_url = home_url('/');
$logo_url = '';
if ( has_custom_logo() ) {
	$logo_id = get_theme_mod( 'custom_logo' );
	$logo_attachment = wp_get_attachment_image_src( $logo_id, 'full' );
	if ( $logo_attachment ) {
		$logo_url = $logo_attachment[0];
	}
}
$is_logged_in = is_user_logged_in();
$user_display = $is_logged_in ? wp_get_current_user()->display_name : '';

// Menu items config: items 1-6 show directly, items 7-9 go in "More"
$main_items = array();
$more_items = array();
$locations = get_nav_menu_locations();
if ( ! empty( $locations['primary'] ) ) {
	$all_items = wp_get_nav_menu_items( $locations['primary'] );
	if ( $all_items ) {
		$children_map = array();
		foreach ( $all_items as $item ) {
			if ( $item->menu_item_parent ) {
				$children_map[ $item->menu_item_parent ][] = $item;
			}
		}
		$index = 0;
		foreach ( $all_items as $item ) {
			if ( $item->menu_item_parent ) continue;
			$index++;
			$entry = array(
				'id'       => $item->ID,
				'title'    => $item->title,
				'url'      => $item->url,
				'children' => isset( $children_map[ $item->ID ] ) ? $children_map[ $item->ID ] : array(),
				'is_active' => ( isset($item->object_id) && $item->object_id == get_the_ID() ) || ( is_home() && untrailingslashit($item->url) === untrailingslashit($site_url) ),
			);
			if ( $index <= 6 ) {
				$main_items[] = $entry;
			} else {
				$more_items[] = $entry;
			}
		}
	}
}
$has_more = ! empty( $more_items );

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?> <?php generate_do_microdata( 'body' ); ?>>
<?php do_action( 'wp_body_open' ); ?>

<a class="screen-reader-text skip-link" href="#content">Skip to content</a>

<!-- Mobile Drawer -->
<div class="mn-overlay" id="mnOverlay"></div>
<div class="mn-drawer" id="mnDrawer">
	<div class="mn-drawer-head">
		<?php if ( $logo_url ) : ?>
			<a href="<?php echo esc_url( $site_url ); ?>"><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="mn-drawer-logo" /></a>
		<?php endif; ?>
		<button class="mn-close" id="mnClose" aria-label="Close">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
		</button>
	</div>
	<div class="mn-drawer-auth">
		<?php if ( $is_logged_in ) : ?>
			<a href="<?php echo esc_url( admin_url() ); ?>" class="mn-dbtn mn-dbtn-acc">My Account</a>
			<a href="<?php echo esc_url( wp_logout_url( $site_url ) ); ?>" class="mn-dbtn mn-dbtn-out">Log Out</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $site_url . 'sign-in/' ); ?>" class="mn-dbtn mn-dbtn-in">Sign In</a>
			<a href="<?php echo esc_url( $site_url . 'register/' ); ?>" class="mn-dbtn mn-dbtn-reg">Register</a>
		<?php endif; ?>
	</div>
	<a href="https://api.whatsapp.com/send/?text=Hi%20iTIN%20Pakistan%2C%20I%27d%20like%20to%20get%20in%20touch." target="_blank" rel="noopener" class="mn-dwa">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
		Follow us on WhatsApp
	</a>
	<nav aria-label="Mobile menu">
		<ul class="mn-dmenu">
			<?php foreach ( $main_items as $item ) : ?>
				<li class="mn-ditem<?php echo !empty($item['children']) ? ' has-sub' : ''; ?>">
					<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
					<?php if ( ! empty( $item['children'] ) ) : ?>
						<button class="mn-dtoggle" aria-expanded="false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg></button>
						<ul class="mn-dsub">
							<?php foreach ( $item['children'] as $child ) : ?>
								<li><a href="<?php echo esc_url( $child->url ); ?>"><?php echo esc_html( $child->title ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			<?php if ( $has_more ) : ?>
				<li class="mn-ditem has-sub">
					<a href="#">More</a>
					<button class="mn-dtoggle" aria-expanded="false"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg></button>
					<ul class="mn-dsub">
						<?php foreach ( $more_items as $mi ) : ?>
							<li><a href="<?php echo esc_url( $mi['url'] ); ?>"><?php echo esc_html( $mi['title'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</li>
			<?php endif; ?>
		</ul>
	</nav>
</div>

<!-- Tier 1: Utility Bar -->
<div class="mn-ubar">
	<div class="mn-ubar-inner">
		<div class="mn-ubar-left">
			<span class="mn-ubar-date"><?php echo date('l, F j, Y'); ?></span>
		</div>
		<div class="mn-ubar-right">
			<a href="https://api.whatsapp.com/send/?text=Hi%20iTIN%20Pakistan%2C%20I%27d%20like%20to%20get%20in%20touch." target="_blank" rel="noopener" class="mn-ubar-link mn-ubar-wa" aria-label="WhatsApp Channel">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
			</a>
			<span class="mn-ubar-sep"></span>
			<button class="mn-ubar-search" id="mnSearchBtn" aria-label="Search">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			</button>
		</div>
	</div>
</div>

<!-- Tier 2: Main Header -->
<header class="mn" id="masthead">
	<div class="mn-inner">
		<!-- Hamburger (mobile) -->
		<button class="mn-burger" id="mnBurger" aria-label="Menu" aria-expanded="false">
			<span></span><span></span><span></span>
		</button>

		<!-- Logo -->
		<div class="mn-logo">
			<a href="<?php echo esc_url( $site_url ); ?>">
				<?php if ( $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="mn-logo-img" />
				<?php else : ?>
					<span class="mn-logo-text"><?php bloginfo( 'name' ); ?></span>
				<?php endif; ?>
			</a>
		</div>

		<!-- Navigation -->
		<nav class="mn-nav" aria-label="Primary navigation">
			<ul class="mn-menu">
				<?php foreach ( $main_items as $item ) : ?>
					<li class="mn-item<?php echo $item['is_active'] ? ' active' : ''; ?><?php echo !empty($item['children']) ? ' has-sub' : ''; ?>">
						<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo !empty($item['children']) ? ' aria-haspopup="true" aria-expanded="false"' : ''; ?>>
							<?php echo esc_html( $item['title'] ); ?>
							<?php if ( ! empty( $item['children'] ) ) : ?>
								<svg class="mn-arrow" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
							<?php endif; ?>
						</a>
						<?php if ( ! empty( $item['children'] ) ) : ?>
							<ul class="mn-dropdown">
								<?php foreach ( $item['children'] as $child ) : ?>
									<li><a href="<?php echo esc_url( $child->url ); ?>"><?php echo esc_html( $child->title ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>

				<!-- "More" dropdown for items 7-9 -->
				<?php if ( $has_more ) : ?>
					<li class="mn-item mn-more-item has-sub">
						<a href="#" aria-haspopup="true" aria-expanded="false">
							More
							<svg class="mn-arrow" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
						</a>
						<ul class="mn-dropdown">
							<?php foreach ( $more_items as $mi ) : ?>
								<li><a href="<?php echo esc_url( $mi['url'] ); ?>"><?php echo esc_html( $mi['title'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>
			</ul>
		</nav>

		<!-- Inline search bar (desktop) -->
		<form class="mn-inline-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="search" name="s" placeholder="Search..." class="mn-inline-search-input" autocomplete="off" />
			<button type="submit" class="mn-inline-search-btn" aria-label="Search">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			</button>
		</form>

		<!-- Auth buttons (main nav row) -->
		<div class="mn-auth">
			<?php if ( $is_logged_in ) : ?>
				<a href="<?php echo esc_url( admin_url() ); ?>" class="mn-abtn mn-abtn-ghost">My Account</a>
				<a href="<?php echo esc_url( wp_logout_url( $site_url ) ); ?>" class="mn-abtn mn-abtn-outline">Log Out</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $site_url . 'sign-in/' ); ?>" class="mn-abtn mn-abtn-ghost">Sign In</a>
				<a href="<?php echo esc_url( $site_url . 'register/' ); ?>" class="mn-abtn mn-abtn-primary">Register</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Search overlay -->
	<div class="mn-search-overlay" id="mnSearchOverlay">
		<div class="mn-search-box">
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" name="s" placeholder="Search iTIN Pakistan..." class="mn-search-input" autocomplete="off" />
				<button type="submit" class="mn-search-go"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
			</form>
			<button class="mn-search-x" id="mnSearchClose" aria-label="Close"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
		</div>
	</div>
</header>

<script>
(function(){
	var b=document.getElementById('mnBurger'),d=document.getElementById('mnDrawer'),o=document.getElementById('mnOverlay'),c=document.getElementById('mnClose');
	function open(){d.classList.add('open');o.classList.add('open');document.body.classList.add('drawer-open');b.setAttribute('aria-expanded','true');}
	function close(){d.classList.remove('open');o.classList.remove('open');document.body.classList.remove('drawer-open');b.setAttribute('aria-expanded','false');}
	if(b)b.addEventListener('click',open);
	if(c)c.addEventListener('click',close);
	if(o)o.addEventListener('click',close);
	document.querySelectorAll('.mn-dtoggle').forEach(function(t){
		t.addEventListener('click',function(){var p=this.closest('.mn-ditem'),e=this.getAttribute('aria-expanded')==='true';this.setAttribute('aria-expanded',!e);p.classList.toggle('open');});
	});
		var sb=document.getElementById('mnSearchBtn'),so=document.getElementById('mnSearchOverlay'),sx=document.getElementById('mnSearchClose'),si=document.querySelector('.mn-search-input');
		if(sb)sb.addEventListener('click',function(e){e.preventDefault();so.classList.add('open');setTimeout(function(){si&&si.focus();},100);});
		if(sx)sx.addEventListener('click',function(){so.classList.remove('open');});
		// Overlay close on backdrop click
		if(so)so.addEventListener('click',function(e){if(e.target===so)so.classList.remove('open');});
		// Escape key closes overlay
		document.addEventListener('keydown',function(e){if(e.key==='Escape'&&so&&so.classList.contains('open'))so.classList.remove('open');});
		// Inline search: prevent empty submit, open overlay instead
		var isb=document.querySelector('.mn-inline-search');
		if(isb)isb.addEventListener('submit',function(e){
			var q=this.querySelector('input[name="s"]');
			if(!q||!q.value.trim()){e.preventDefault();so.classList.add('open');setTimeout(function(){si&&si.focus();},100);}
		});
	// Click-to-toggle for "More" dropdown
	var moreItem=document.querySelector('.mn-more-item');
	if(moreItem){
		var moreLink=moreItem.querySelector('a');
		moreLink.addEventListener('click',function(e){
			e.preventDefault();
			var wasOpen=moreItem.classList.contains('mn-open');
			// Close all open dropdowns first
			document.querySelectorAll('.mn-more-item.mn-open').forEach(function(el){el.classList.remove('mn-open');});
			if(!wasOpen) moreItem.classList.add('mn-open');
		});
	}
	// Close More dropdown when clicking outside
	document.addEventListener('click',function(e){
		if(moreItem && !moreItem.contains(e.target)){
			moreItem.classList.remove('mn-open');
		}
	});
})();
</script>
