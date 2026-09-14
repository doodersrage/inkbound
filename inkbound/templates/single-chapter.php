<?php
/**
 * Chapter reader.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$chapter = get_queried_object();
$story   = inkbound_chapter_story( $chapter );
if ( ! $story ) {
	wp_safe_redirect( inkbound_url() );
	exit;
}

$next    = inkbound_adjacent_chapter( $chapter, 'next' );
$prev    = inkbound_adjacent_chapter( $chapter, 'prev' );
$note    = (string) get_post_meta( $chapter->ID, '_inkbound_author_note', true );
$words   = (int) get_post_meta( $chapter->ID, '_inkbound_word_count', true );
$all     = inkbound_story_chapters( (int) $story->ID );
$index   = 0;
foreach ( $all as $i => $item ) {
	if ( (int) $item->ID === (int) $chapter->ID ) {
		$index = $i + 1;
		break;
	}
}
$following = Inkbound_Follow::is_following( (int) $story->ID );

require INKB_DIR . 'templates/parts/header.php';
?>
<div class="ink-reader" data-theme="paper">
	<div class="ink-progress" aria-hidden="true"><span class="js-read-bar"></span></div>
	<div class="ink-reader__bar">
		<a class="ink-quiet" href="<?php echo esc_url( get_permalink( $story ) ); ?>"><?php echo esc_html( $story->post_title ); ?></a>
		<span><?php echo esc_html( $index . ' / ' . count( $all ) ); ?></span>
		<div class="ink-reader__tools">
			<button type="button" class="ink-icon js-settings" aria-expanded="false"><?php esc_html_e( 'Reader', 'inkbound' ); ?></button>
			<?php if ( is_user_logged_in() ) : ?>
				<button type="button" class="ink-icon js-follow" data-story="<?php echo (int) $story->ID; ?>" aria-pressed="<?php echo $following ? 'true' : 'false'; ?>">
					<?php echo $following ? esc_html__( 'Following', 'inkbound' ) : esc_html__( 'Follow', 'inkbound' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<div class="ink-settings" hidden>
		<p><?php esc_html_e( 'Reading surface', 'inkbound' ); ?></p>
		<div class="ink-seg js-theme">
			<button type="button" data-theme="paper"><?php esc_html_e( 'Paper', 'inkbound' ); ?></button>
			<button type="button" data-theme="sepia"><?php esc_html_e( 'Sepia', 'inkbound' ); ?></button>
			<button type="button" data-theme="night"><?php esc_html_e( 'Night', 'inkbound' ); ?></button>
		</div>
		<p><?php esc_html_e( 'Type size', 'inkbound' ); ?></p>
		<div class="ink-seg js-size">
			<button type="button" data-size="sm">A</button>
			<button type="button" data-size="md">A</button>
			<button type="button" data-size="lg">A</button>
		</div>
		<p><?php esc_html_e( 'Measure', 'inkbound' ); ?></p>
		<div class="ink-seg js-width">
			<button type="button" data-width="narrow"><?php esc_html_e( 'Narrow', 'inkbound' ); ?></button>
			<button type="button" data-width="wide"><?php esc_html_e( 'Wide', 'inkbound' ); ?></button>
		</div>
	</div>

	<article class="ink-prose js-prose">
		<p class="ink-kicker"><?php echo esc_html( inkbound_chapter_heading( $chapter ) ); ?></p>
		<h1><?php echo esc_html( $chapter->post_title ); ?></h1>
		<p class="ink-by"><?php echo esc_html( sprintf( __( '%s words · %s', 'inkbound' ), number_format_i18n( $words ), get_the_date( '', $chapter ) ) ); ?></p>
		<div class="ink-body">
			<?php echo wp_kses_post( apply_filters( 'the_content', $chapter->post_content ) ); ?>
		</div>
		<?php if ( $note ) : ?>
			<aside class="ink-note">
				<h2><?php esc_html_e( 'Author\'s note', 'inkbound' ); ?></h2>
				<?php echo wp_kses_post( wpautop( $note ) ); ?>
			</aside>
		<?php endif; ?>
	</article>

	<nav class="ink-chapter-nav">
		<?php if ( $prev ) : ?>
			<a class="ink-btn ink-btn--ghost" rel="prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
				<?php echo esc_html( sprintf( __( 'Previous · %s', 'inkbound' ), $prev->post_title ) ); ?>
			</a>
		<?php else : ?>
			<a class="ink-btn ink-btn--ghost" href="<?php echo esc_url( get_permalink( $story ) ); ?>"><?php esc_html_e( 'Story home', 'inkbound' ); ?></a>
		<?php endif; ?>
		<?php if ( $next ) : ?>
			<a class="ink-btn" rel="next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
				<?php echo esc_html( sprintf( __( 'Next · %s', 'inkbound' ), $next->post_title ) ); ?>
			</a>
		<?php endif; ?>
	</nav>

	<?php if ( ! $following ) : ?>
		<section class="ink-cta">
			<h2><?php esc_html_e( 'Get the next chapter', 'inkbound' ); ?></h2>
			<p><?php esc_html_e( 'Follow to keep progress across devices, and subscribe if you want new chapters delivered to your inbox.', 'inkbound' ); ?></p>
			<form class="ink-subscribe" method="post">
				<?php wp_nonce_field( 'inkbound_front' ); ?>
				<input type="hidden" name="inkbound_action" value="subscribe_email">
				<input type="hidden" name="story_id" value="<?php echo (int) $story->ID; ?>">
				<label>
					<span class="screen-reader-text"><?php esc_html_e( 'Email', 'inkbound' ); ?></span>
					<input type="email" name="email" required placeholder="you@example.com" value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->user_email ) : ''; ?>">
				</label>
				<button class="ink-btn" type="submit"><?php esc_html_e( 'Email me new chapters', 'inkbound' ); ?></button>
			</form>
		</section>
	<?php endif; ?>

	<?php if ( comments_open( $chapter ) || get_comments_number( $chapter ) ) : ?>
		<section class="ink-comments">
			<?php comments_template(); ?>
		</section>
	<?php endif; ?>
</div>
<?php require INKB_DIR . 'templates/parts/footer.php'; ?>
