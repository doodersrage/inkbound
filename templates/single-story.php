<?php
/**
 * Single story: blurb, follow, TOC, continue.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template-scoped locals.

$story     = get_queried_object();
$chapters  = inkbound_story_chapters( (int) $story->ID );
$first     = $chapters[0] ?? null;
$progress  = Inkbound_Progress::get_for_story( (int) $story->ID );
$following = Inkbound_Follow::is_following( (int) $story->ID );
$subtitle  = (string) get_post_meta( $story->ID, '_inkbound_subtitle', true );
$schedule  = (string) get_post_meta( $story->ID, '_inkbound_schedule', true );
$warnings  = inkbound_parse_warnings( (string) get_post_meta( $story->ID, '_inkbound_warnings', true ) );
$age       = (string) get_post_meta( $story->ID, '_inkbound_age', true );
$genres    = get_the_terms( $story, 'inkbound_genre' );
$tropes    = get_the_terms( $story, 'inkbound_trope' );
$status    = get_the_terms( $story, 'inkbound_status' );
$words     = (int) get_post_meta( $story->ID, '_inkbound_word_count', true );
$views     = (int) get_post_meta( $story->ID, '_inkbound_view_count', true );
$follows   = (int) get_post_meta( $story->ID, '_inkbound_follower_count', true );
$continue  = $first ? get_permalink( $first ) : '';
if ( $progress ) {
	$continue = Inkbound_Progress::continue_url( (int) $story->ID );
}

require INKB_DIR . 'templates/parts/header.php';
?>
<article class="ink-story">
	<div class="ink-story__hero">
		<div class="ink-story__cover">
			<?php if ( has_post_thumbnail( $story ) ) : ?>
				<?php echo get_the_post_thumbnail( $story, 'large' ); ?>
			<?php endif; ?>
		</div>
		<div class="ink-story__intro">
			<?php if ( $status && ! is_wp_error( $status ) ) : ?>
				<p class="ink-kicker"><?php echo esc_html( $status[0]->name ); ?><?php echo $schedule ? ' · ' . esc_html( $schedule ) : ''; ?></p>
			<?php endif; ?>
			<h1><?php echo esc_html( $story->post_title ); ?></h1>
			<?php if ( $subtitle ) : ?><p class="ink-lede"><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
			<p class="ink-by"><?php
			echo esc_html(
				sprintf(
					/* translators: %s: author display name */
					__( 'By %s', 'inkbound' ),
					get_the_author_meta( 'display_name', $story->post_author )
				)
			);
			?></p>
			<ul class="ink-stats">
				<li><strong><?php echo esc_html( (string) count( $chapters ) ); ?></strong> <?php esc_html_e( 'chapters', 'inkbound' ); ?></li>
				<li><strong><?php echo esc_html( inkbound_format_count( $words ) ); ?></strong> <?php esc_html_e( 'words', 'inkbound' ); ?></li>
				<li><strong class="js-follow-count"><?php echo esc_html( inkbound_format_count( $follows ) ); ?></strong> <?php esc_html_e( 'following', 'inkbound' ); ?></li>
				<li><strong><?php echo esc_html( inkbound_format_count( $views ) ); ?></strong> <?php esc_html_e( 'reads', 'inkbound' ); ?></li>
			</ul>
			<?php if ( $genres && ! is_wp_error( $genres ) ) : ?>
				<p class="ink-tags">
					<?php foreach ( $genres as $term ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'genre', $term->slug, inkbound_url() ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
					<?php endforeach; ?>
					<?php if ( $tropes && ! is_wp_error( $tropes ) ) : ?>
						<?php foreach ( $tropes as $term ) : ?>
							<span><?php echo esc_html( $term->name ); ?></span>
						<?php endforeach; ?>
					<?php endif; ?>
					<?php if ( $age && isset( inkbound_ages()[ $age ] ) ) : ?>
						<span><?php echo esc_html( inkbound_ages()[ $age ] ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( $warnings ) : ?>
				<p class="ink-warnings"><strong><?php esc_html_e( 'Content notes', 'inkbound' ); ?></strong> <?php echo esc_html( implode( ' · ', $warnings ) ); ?></p>
			<?php endif; ?>
			<div class="ink-actions">
				<?php if ( $continue ) : ?>
					<a class="ink-btn" href="<?php echo esc_url( $continue ); ?>">
						<?php echo $progress ? esc_html__( 'Continue reading', 'inkbound' ) : esc_html__( 'Start reading', 'inkbound' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( is_user_logged_in() ) : ?>
					<button class="ink-btn ink-btn--ghost js-follow" data-story="<?php echo (int) $story->ID; ?>" aria-pressed="<?php echo $following ? 'true' : 'false'; ?>">
						<?php echo $following ? esc_html__( 'Following', 'inkbound' ) : esc_html__( 'Follow', 'inkbound' ); ?>
					</button>
				<?php else : ?>
					<a class="ink-btn ink-btn--ghost" href="<?php echo esc_url( wp_login_url( get_permalink( $story ) ) ); ?>"><?php esc_html_e( 'Sign in to follow', 'inkbound' ); ?></a>
				<?php endif; ?>
			</div>
			<form class="ink-subscribe" method="post">
				<?php wp_nonce_field( 'inkbound_front' ); ?>
				<input type="hidden" name="inkbound_action" value="subscribe_email">
				<input type="hidden" name="story_id" value="<?php echo (int) $story->ID; ?>">
				<label>
					<span><?php esc_html_e( 'Email new chapters', 'inkbound' ); ?></span>
					<input type="email" name="email" required placeholder="you@example.com" value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->user_email ) : ''; ?>">
				</label>
				<button class="ink-btn ink-btn--ghost" type="submit"><?php esc_html_e( 'Subscribe', 'inkbound' ); ?></button>
				<p><?php esc_html_e( 'Email delivery: the chapter lands in your inbox when it publishes. Unsubscribe any time.', 'inkbound' ); ?></p>
			</form>
		</div>
	</div>

	<section class="ink-blurb">
		<h2><?php esc_html_e( 'Synopsis', 'inkbound' ); ?></h2>
		<?php echo wp_kses_post( apply_filters( 'the_content', $story->post_content ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core content filter. ?>
	</section>

	<section class="ink-toc" id="chapters">
		<h2><?php esc_html_e( 'Table of contents', 'inkbound' ); ?></h2>
		<?php if ( ! $chapters ) : ?>
			<p><?php esc_html_e( 'No chapters yet. Follow the story to be notified when the first one goes up.', 'inkbound' ); ?></p>
		<?php else : ?>
			<ol>
				<?php foreach ( $chapters as $chapter ) : ?>
					<?php
					$here = $progress && (int) $progress->chapter_id === (int) $chapter->ID;
					$wc   = (int) get_post_meta( $chapter->ID, '_inkbound_word_count', true );
					?>
					<li class="<?php echo $here ? 'is-current' : ''; ?>">
						<a href="<?php echo esc_url( get_permalink( $chapter ) ); ?>">
							<span class="ink-toc__num"><?php echo esc_html( inkbound_chapter_heading( $chapter ) ); ?></span>
							<span class="ink-toc__title"><?php echo esc_html( $chapter->post_title ); ?></span>
							<span class="ink-toc__meta"><?php echo esc_html( number_format_i18n( $wc ) . ' words · ' . get_the_date( '', $chapter ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</section>
</article>
<?php require INKB_DIR . 'templates/parts/footer.php'; ?>
