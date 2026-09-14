<?php
/**
 * Followed stories + in-progress reading.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require INKB_DIR . 'templates/parts/header.php';

$progress = Inkbound_Progress::continue_list( 20 );
$followed = is_user_logged_in() ? Inkbound_Follow::library_story_ids( get_current_user_id() ) : array();
?>
<section class="ink-hero ink-hero--compact">
	<p class="ink-kicker"><?php esc_html_e( 'Your shelf', 'inkbound' ); ?></p>
	<h1><?php esc_html_e( 'Library', 'inkbound' ); ?></h1>
	<p class="ink-lede"><?php esc_html_e( 'Continue anything you started, plus the serials you follow. Progress is saved for guests on this browser and for accounts everywhere.', 'inkbound' ); ?></p>
</section>

<section class="ink-shelf">
	<h2><?php esc_html_e( 'Continue reading', 'inkbound' ); ?></h2>
	<?php if ( ! $progress ) : ?>
		<p class="ink-empty-line"><?php esc_html_e( 'Open a chapter and your place will show up here.', 'inkbound' ); ?></p>
	<?php else : ?>
		<ul class="ink-continue-list">
			<?php foreach ( $progress as $row ) : ?>
				<?php
				$story   = get_post( $row->story_id );
				$chapter = get_post( $row->chapter_id );
				if ( ! $story || ! $chapter ) {
					continue;
				}
				?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $chapter ) ); ?>">
						<strong><?php echo esc_html( $story->post_title ); ?></strong>
						<span><?php echo esc_html( inkbound_chapter_heading( $chapter ) . ' — ' . $chapter->post_title ); ?></span>
						<em><?php echo esc_html( (string) (int) $row->percent ); ?>%</em>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>

<section class="ink-shelf">
	<h2><?php esc_html_e( 'Following', 'inkbound' ); ?></h2>
	<?php if ( ! is_user_logged_in() ) : ?>
		<p class="ink-empty-line"><?php echo wp_kses_post( sprintf( __( '<a href="%s">Sign in</a> to keep a follow list and get on-site chapter alerts.', 'inkbound' ), esc_url( wp_login_url( inkbound_url( 'library' ) ) ) ) ); ?></p>
	<?php elseif ( ! $followed ) : ?>
		<p class="ink-empty-line"><?php esc_html_e( 'You are not following anything yet. Follow from a story page to build your library.', 'inkbound' ); ?></p>
	<?php else : ?>
		<div class="ink-grid">
			<?php
			foreach ( $followed as $sid ) {
				$story = get_post( $sid );
				if ( $story ) {
					require INKB_DIR . 'templates/parts/story-card.php';
				}
			}
			?>
		</div>
	<?php endif; ?>
</section>
<?php require INKB_DIR . 'templates/parts/footer.php'; ?>
