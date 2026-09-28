<?php
/**
 * Web Show Single Episode Template
 * Only for LIVE and upcoming shows
 *
 * @package GeneratePress_Child
 */

get_header();

$youtube_url = get_post_meta( get_the_ID(), '_itinpk_youtube_url', true );
$guest_name = get_post_meta( get_the_ID(), '_itinpk_guest_name', true );
$guest_bio = get_post_meta( get_the_ID(), '_itinpk_guest_bio', true );
$is_live = get_post_meta( get_the_ID(), '_itinpk_is_live', true );
$episode_number = get_post_meta( get_the_ID(), '_itinpk_episode_number', true );
$video_id = itinpk_extract_youtube_id( $youtube_url );
?>

<div class="single-episode-header">
    <div class="single-episode-container">
        <h1><?php the_title(); ?></h1>
        
        <?php if ( $is_live == '1' ) : ?>
            <div class="live-badge-large">
                <span class="live-dot"></span>
                LIVE NOW
            </div>
        <?php elseif ( $is_live == '2' ) : ?>
            <div class="coming-soon-badge-large">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                </svg>
                COMING SOON
            </div>
        <?php endif; ?>
        
        <?php if ( $episode_number ) : ?>
            <span class="episode-number-large">Episode <?php echo esc_html( $episode_number ); ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="single-episode-container" style="max-width:900px;margin:0 auto;padding:0 20px;">
    <?php if ( $video_id ) : ?>
        <div class="video-wrapper" style="position:relative;padding-top:56.25%;border-radius:8px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);margin-bottom:30px;">
            <iframe src="https://www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?><?php echo $is_live == '1' ? '?autoplay=1' : ''; ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
        </div>
    <?php endif; ?>
    
    <?php if ( $guest_name || $guest_bio ) : ?>
        <div class="guest-info" style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:30px;">
            <?php if ( $guest_name ) : ?>
                <h3 style="margin:0 0 10px;font-size:18px;color:#37215F;"><?php echo esc_html( $guest_name ); ?></h3>
            <?php endif; ?>
            <?php if ( $guest_bio ) : ?>
                <p style="margin:0;font-size:14px;color:#555;line-height:1.6;"><?php echo esc_html( $guest_bio ); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="episode-content">
        <?php the_content(); ?>
    </div>
</div>

<?php get_footer(); ?>
