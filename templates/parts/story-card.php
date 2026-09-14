<?php
/**
 * Story card for catalog / library.
 *
 * @package Inkbound
 *
 * @var WP_Post $story
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$story      = $story ?? get_post();
$status     = get_the_terms( $story, 'inkbound_status' );
$genres     = get_the_terms( $story, 'inkbound_genre' );
$chapters   = (int) get_post_meta( $story->ID, '_inkbound_chapter_count', true );
$words      = (int) get_post_meta( $story->ID, '_inkbound_word_count', true );
$followers  = (int) get_post_meta( $story->ID, '_inkbound_follower_count', true );
$subtitle   = (string) get_post_meta( $story->ID, '_inkbound_subtitle', true );
$progress   = Inkbound_Progress::get_for_story( (int) $story->ID );
$status_n   = ( $status && ! is_wp_error( $status ) ) ? $status[0]->name : '';
$genre_n    = ( $genres && ! is_wp_error( $genres ) ) ? $genres[0]->name : '';
?>
<article class="ink-card">
	<a class="ink-card__cover" href="<?php echo esc_url( get_permalink( $story ) ); ?>">
		<?php if ( has_post_thumbnail( $story ) ) : ?>
			<?php echo get_the_post_thumbnail( $story, 'medium_large' ); ?>
		<?php else : ?>
			<span class="ink-card__fallback"><?php echo esc_html( mb_substr( $story->post_title, 0, 1 ) ); ?></span>
		<?php endif; ?>
		<?php if ( $status_n ) : ?><span class="ink-pill"><?php echo esc_html( $status_n ); ?></span><?php endif; ?>
	</a>
	<div class="ink-card__body">
		<?php if ( $genre_n ) : ?><p class="ink-kicker"><?php echo esc_html( $genre_n ); ?></p><?php endif; ?>
		<h2 class="ink-card__title"><a href="<?php echo esc_url( get_permalink( $story ) ); ?>"><?php echo esc_html( $story->post_title ); ?></a></h2>
		<?php if ( $subtitle ) : ?><p class="ink-card__sub"><?php echo esc_html( $subtitle ); ?></p><?php endif; ?>
		<p class="ink-card__meta">
			<?php echo esc_html( sprintf(
				/* translators: 1: chapter count, 2: word count, 3: follower count */
				__( '%1$s ch · %2$s words · %3$s following', 'inkbound' ),
				$chapters,
				inkbound_format_count( $words ),
				inkbound_format_count( $followers )
			) ); ?>
		</p>
		<?php if ( $progress ) : ?>
			<p class="ink-card__progress">
				<a href="<?php echo esc_url( Inkbound_Progress::continue_url( (int) $story->ID ) ); ?>">
					<?php echo esc_html( sprintf( __( 'Continue · %d%%', 'inkbound' ), (int) $progress->percent ) ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</article>
