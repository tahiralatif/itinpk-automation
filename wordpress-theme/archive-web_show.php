<?php
/**
 * Web Shows Archive Template
 * Shows only REAL live or genuinely upcoming tech streams.
 * Topic filters: IT, AI, Cybersecurity, Startups
 *
 * @package GeneratePress_Child
 */

get_header();

// Load streams data
$streams_file = wp_upload_dir()['basedir'] . '/web-shows/upcoming_streams.json';
$streams_data = array();
$streams_raw = array();
if ( file_exists( $streams_file ) ) {
    $streams_data = json_decode( file_get_contents( $streams_file ), true ) ?: array();
    $streams_raw = $streams_data['streams'] ?? array();
}

// PHP-side tech filter
$blocked_channels = array('otv','ndtv','times now','republic','aaj tak','zee news','india today','news18','bloomberg television','cnbc','bbc news','cnn','fox news','sky news','al jazeera','wion','dawn news','geo news','ary news','samaa','hum news','bol news','express news','92 news','ap','reuters','daily news','defected records','defected','musical','music','gaming','esports','streamer','nextwork','devtown','self-host hub','major seifer','devs fm','synthesis pulse','chili ai music');
$blocked_words = array('flood','earthquake','cricket','football','soccer','bollywood','hollywood','movie','film','music video','cooking','recipe','horoscope','prank','funny','comedy','election','parliament','minister','protest','murder','crime','trump','modi','war','conflict','deep house','soulful','house music','giveaway','custom room','free fire','gameplay','tutorial','course','learn','lesson','how to','walkthrough','crash course','bootcamp','coding','code with','live coding','vibecoding','build with','full stack','frontend','backend','simulator','autonomous','music','lofi','synthwave','focus music','never closed','asmr','bitcoin','btc','usdt','bombing','missile');

$streams = array_filter( $streams_raw, function( $s ) use ( $blocked_channels, $blocked_words ) {
    $title = strtolower( $s['title'] ?? '' );
    $channel = strtolower( $s['channel'] ?? '' );
    $desc = strtolower( $s['description'] ?? '' );
    $text = $title . ' ' . $desc;
    foreach ( $blocked_channels as $bc ) {
        if ( strpos( $channel, $bc ) !== false ) return false;
    }
    foreach ( $blocked_words as $bw ) {
        if ( strpos( $text, $bw ) !== false ) return false;
    }
    return true;
});
$streams = array_values( $streams );

$live_streams = array_filter( $streams, function( $s ) { return ( $s['event_type'] ?? '' ) === 'live'; });
$upcoming_streams = array_filter( $streams, function( $s ) { return ( $s['event_type'] ?? '' ) === 'upcoming'; });
$has_live = count( $live_streams ) > 0;
$has_upcoming = count( $upcoming_streams ) > 0;
$has_any = $has_live || $has_upcoming;

// Category filter data
$filter_cats = array(
    '' => 'All',
    'IT News' => 'IT News',
    'AI & Cloud' => 'AI & Cloud',
    'Cybersecurity' => 'Cybersecurity',
    'Startups' => 'Startups',
);
?>

<!-- HERO HEADER -->
<div class="ws-hero">
    <div class="ws-hero-inner">
        <div class="ws-hero-badge">LIVE & UPCOMING</div>
        <h1 class="ws-hero-title">Web Shows &amp; Live Streams</h1>
        <p class="ws-hero-desc">Watch live tech streams, podcasts, and upcoming shows from the global tech community. Updated every 6 hours.</p>
        <?php if ( $has_any ) : ?>
        <div class="ws-hero-stats">
            <?php if ( $has_live ) : ?>
                <span class="ws-stat ws-stat-live"><span class="ws-stat-dot"></span> <?php echo count( $live_streams ); ?> Live Now</span>
            <?php endif; ?>
            <?php if ( $has_upcoming ) : ?>
                <span class="ws-stat"><?php echo count( $upcoming_streams ); ?> Upcoming</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- TOPIC FILTERS -->
<?php if ( $has_any ) : ?>
<div class="ws-filters">
    <div class="ws-filters-inner">
        <span class="ws-filters-label">Filter by topic:</span>
        <div class="ws-filters-btns">
            <?php foreach ( $filter_cats as $key => $label ) : ?>
                <button class="ws-filter-btn<?php echo ( $key === '' ) ? ' active' : ''; ?>" data-cat="<?php echo esc_attr( $key ); ?>" onclick="wsFilter('<?php echo esc_attr( $key ); ?>', this)"><?php echo esc_html( $label ); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ( $has_live ) : ?>
<!-- LIVE NOW SECTION -->
<div class="ws-section ws-live-section">
    <div class="ws-section-inner">
        <div class="ws-section-header">
            <span class="ws-live-badge"><span class="ws-live-dot-pulse"></span> LIVE NOW</span>
            <span class="ws-section-count"><?php echo count( $live_streams ); ?> stream<?php echo count( $live_streams ) > 1 ? 's' : ''; ?></span>
        </div>
        <div class="ws-streams-grid">
            <?php foreach ( $live_streams as $stream ) :
                $vid       = $stream['video_id'] ?? '';
                $title     = $stream['title'] ?? '';
                $channel   = $stream['channel'] ?? '';
                $thumb     = $stream['thumbnail'] ?? '';
                $url       = $stream['url'] ?? "https://www.youtube.com/watch?v=$vid";
                $cat       = $stream['discovered_category'] ?? '';
                $type      = $stream['type_label'] ?? '';
            ?>
            <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="ws-stream-card" data-cat="<?php echo esc_attr( $cat ); ?>">
                <div class="ws-stream-thumb">
                    <?php if ( $thumb ) : ?>
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
                    <?php endif; ?>
                    <span class="ws-stream-live"><span class="ws-live-dot-sm"></span> LIVE</span>
                    <div class="ws-stream-play"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                </div>
                <div class="ws-stream-body">
                    <h3 class="ws-stream-title"><?php echo esc_html( $title ); ?></h3>
                    <div class="ws-stream-channel"><?php echo esc_html( $channel ); ?></div>
                    <div class="ws-stream-meta">
                        <?php if ( $cat ) : ?><span class="ws-stream-cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
                        <?php if ( $type ) : ?><span class="ws-stream-type"><?php echo esc_html( $type ); ?></span><?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ( $has_upcoming ) : ?>
<!-- UPCOMING SHOWS SECTION -->
<div class="ws-section ws-upcoming-section">
    <div class="ws-section-inner">
        <div class="ws-section-header">
            <h2 class="ws-section-title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="vertical-align:middle;margin-right:8px;">
                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                </svg>
                Upcoming Shows
            </h2>
            <span class="ws-section-count"><?php echo count( $upcoming_streams ); ?> scheduled</span>
        </div>
        <div class="ws-streams-grid">
            <?php foreach ( $upcoming_streams as $stream ) :
                $vid       = $stream['video_id'] ?? '';
                $title     = $stream['title'] ?? '';
                $channel   = $stream['channel'] ?? '';
                $thumb     = $stream['thumbnail'] ?? '';
                $url       = $stream['url'] ?? "https://www.youtube.com/watch?v=$vid";
                $scheduled = $stream['scheduled_display'] ?? '';
                $cat       = $stream['discovered_category'] ?? '';
                $type      = $stream['type_label'] ?? '';
            ?>
            <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="ws-stream-card ws-stream-upcoming" data-cat="<?php echo esc_attr( $cat ); ?>">
                <div class="ws-stream-thumb">
                    <?php if ( $thumb ) : ?>
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
                    <?php endif; ?>
                    <?php if ( $scheduled ) : ?>
                        <span class="ws-stream-scheduled"><?php echo esc_html( $scheduled ); ?></span>
                    <?php endif; ?>
                    <div class="ws-stream-play"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                </div>
                <div class="ws-stream-body">
                    <h3 class="ws-stream-title"><?php echo esc_html( $title ); ?></h3>
                    <div class="ws-stream-channel"><?php echo esc_html( $channel ); ?></div>
                    <div class="ws-stream-meta">
                        <?php if ( $cat ) : ?><span class="ws-stream-cat"><?php echo esc_html( $cat ); ?></span><?php endif; ?>
                        <?php if ( $type ) : ?><span class="ws-stream-type"><?php echo esc_html( $type ); ?></span><?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ( ! $has_any ) : ?>
<!-- EMPTY STATE -->
<div class="ws-empty">
    <div class="ws-empty-inner">
        <div class="ws-empty-icon">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#37215F" stroke-width="1.5">
                <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2>No Live Shows Right Now</h2>
        <p>We scan YouTube every 6 hours for live and upcoming tech streams, podcasts, and events. Check back later — new shows appear throughout the day.</p>
        <div class="ws-empty-topics">
            <span class="ws-empty-topic">IT News</span>
            <span class="ws-empty-topic">AI & Cloud</span>
            <span class="ws-empty-topic">Cybersecurity</span>
            <span class="ws-empty-topic">Startups</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- NOTIFY ME -->
<div class="ws-notify">
    <div class="ws-notify-inner">
        <div class="ws-notify-icon">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        </div>
        <h3>Never Miss a Live Show</h3>
        <p>Get notified when we find live tech streams and upcoming shows.</p>
        <div class="ws-notify-form">
            <input type="email" class="ws-notify-input" placeholder="Your email address" />
            <button class="ws-notify-btn" onclick="wsNotify(this)">Notify Me</button>
        </div>
    </div>
</div>

<!-- DISCLAIMER -->
<div class="ws-disclaimer">
    <div class="ws-disclaimer-inner">
        <p>Streams are auto-discovered from YouTube and filtered for tech relevance. iTIN Pakistan is not affiliated with these channels. Data refreshed every 6 hours.</p>
        <?php if ( isset( $streams_data['updated_at'] ) ) : ?>
            <p class="ws-disclaimer-time">Last updated: <?php echo esc_html( date( 'M d, Y g:i A', strtotime( $streams_data['updated_at'] ) ) ); ?> UTC</p>
        <?php endif; ?>
    </div>
</div>

<script>
function wsFilter(cat, btn) {
    document.querySelectorAll('.ws-filter-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('.ws-stream-card').forEach(function(card){
        if (!cat || card.getAttribute('data-cat') === cat) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
function wsNotify(btn){
    var input = btn.parentElement.querySelector('.ws-notify-input');
    var email = input.value.trim();
    if(!email || !email.includes('@')){
        input.style.borderColor='#e74c3c';
        return;
    }
    input.style.borderColor='#27ae60';
    btn.textContent='Subscribed!';
    btn.style.background='#27ae60';
    input.disabled=true;
    btn.disabled=true;
}
</script>

<?php get_footer(); ?>
