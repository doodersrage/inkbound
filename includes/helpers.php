<?php
/**
 * Shared helpers.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default plugin options.
 *
 * @return array<string, mixed>
 */
function inkbound_default_options(): array {
	return array(
		'replace_home'     => true,
		'catalog_title'    => 'The Catalog',
		'catalog_tagline'  => 'Serial fiction with chapter tools that WordPress does not ship — follows, progress, and update mail.',
		'from_name'        => '',
		'from_email'       => '',
		'email_mode'       => 'full',
		'confirm_subs'     => false,
		'notify_on_publish'=> true,
		'stories_per_page' => 12,
	);
}

/**
 * Get a plugin option.
 *
 * @param string $key     Option key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function inkbound_option( string $key, $default = null ) {
	$options = wp_parse_args( get_option( 'inkbound_options', array() ), inkbound_default_options() );
	if ( null === $default ) {
		$default = inkbound_default_options()[ $key ] ?? null;
	}
	return $options[ $key ] ?? $default;
}

/**
 * Story status labels.
 *
 * @return array<string, string>
 */
function inkbound_statuses(): array {
	return array(
		'ongoing'   => __( 'Ongoing', 'inkbound' ),
		'completed' => __( 'Completed', 'inkbound' ),
		'hiatus'    => __( 'Hiatus', 'inkbound' ),
		'dropped'   => __( 'Dropped', 'inkbound' ),
	);
}

/**
 * Age-rating labels.
 *
 * @return array<string, string>
 */
function inkbound_ages(): array {
	return array(
		'everyone' => __( 'Everyone', 'inkbound' ),
		'teen'     => __( 'Teen', 'inkbound' ),
		'mature'   => __( 'Mature', 'inkbound' ),
	);
}

/**
 * Published chapters for a story, ordered by chapter number.
 *
 * @param int    $story_id Story post ID.
 * @param string $status   Post status or 'any'.
 * @return WP_Post[]
 */
function inkbound_story_chapters( int $story_id, string $status = 'publish' ): array {
	$args = array(
		'post_type'      => 'inkbound_chapter',
		'post_parent'    => $story_id,
		'post_status'    => $status,
		'posts_per_page' => -1,
		'orderby'        => 'meta_value_num',
		'meta_key'       => '_inkbound_number',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);

	if ( 'any' === $status ) {
		$args['post_status'] = array( 'publish', 'draft', 'future', 'pending', 'private' );
	}

	$posts = get_posts( $args );
	if ( $posts ) {
		return $posts;
	}

	// Fallback if numbers are missing.
	unset( $args['orderby'], $args['meta_key'] );
	$args['orderby'] = 'menu_order date';
	$args['order']   = 'ASC';
	return get_posts( $args );
}

/**
 * Parent story for a chapter.
 *
 * @param int|WP_Post $chapter Chapter.
 * @return WP_Post|null
 */
function inkbound_chapter_story( $chapter ): ?WP_Post {
	$chapter = get_post( $chapter );
	if ( ! $chapter || 'inkbound_chapter' !== $chapter->post_type ) {
		return null;
	}
	$story = get_post( $chapter->post_parent );
	if ( ! $story || 'inkbound_story' !== $story->post_type ) {
		return null;
	}
	return $story;
}

/**
 * Chapter number as displayed string.
 *
 * @param int|WP_Post $chapter Chapter.
 * @return string
 */
function inkbound_chapter_number( $chapter ): string {
	$raw = get_post_meta( get_post( $chapter )->ID ?? 0, '_inkbound_number', true );
	return '' === (string) $raw ? '' : (string) $raw;
}

/**
 * Human chapter heading: "Prologue" or "Chapter 4".
 *
 * @param int|WP_Post $chapter Chapter.
 * @return string
 */
function inkbound_chapter_heading( $chapter ): string {
	$chapter = get_post( $chapter );
	if ( ! $chapter ) {
		return '';
	}
	$label = trim( (string) get_post_meta( $chapter->ID, '_inkbound_label', true ) );
	if ( $label ) {
		return $label;
	}
	$number = inkbound_chapter_number( $chapter );
	if ( '' === $number ) {
		return $chapter->post_title;
	}
	return sprintf(
		/* translators: %s: chapter number */
		__( 'Chapter %s', 'inkbound' ),
		$number
	);
}

/**
 * Adjacent chapter.
 *
 * @param int|WP_Post $chapter Chapter.
 * @param string      $dir     next|prev.
 * @return WP_Post|null
 */
function inkbound_adjacent_chapter( $chapter, string $dir = 'next' ): ?WP_Post {
	$chapter = get_post( $chapter );
	$story   = inkbound_chapter_story( $chapter );
	if ( ! $chapter || ! $story ) {
		return null;
	}
	$chapters = inkbound_story_chapters( (int) $story->ID );
	$index    = null;
	foreach ( $chapters as $i => $item ) {
		if ( (int) $item->ID === (int) $chapter->ID ) {
			$index = $i;
			break;
		}
	}
	if ( null === $index ) {
		return null;
	}
	$target = 'next' === $dir ? $index + 1 : $index - 1;
	return $chapters[ $target ] ?? null;
}

/**
 * First published chapter.
 *
 * @param int $story_id Story ID.
 * @return WP_Post|null
 */
function inkbound_first_chapter( int $story_id ): ?WP_Post {
	$chapters = inkbound_story_chapters( $story_id );
	return $chapters[0] ?? null;
}

/**
 * Count words in HTML/text.
 *
 * @param string $content Content.
 * @return int
 */
function inkbound_count_words( string $content ): int {
	$text = trim( wp_strip_all_tags( $content ) );
	if ( '' === $text ) {
		return 0;
	}
	if ( preg_match_all( '/[\p{L}\p{N}\']+/u', $text, $matches ) ) {
		return count( $matches[0] );
	}
	$parts = preg_split( '/\s+/', $text );
	return is_array( $parts ) ? count( $parts ) : 0;
}

/**
 * Compact number formatting.
 *
 * @param int $n Number.
 * @return string
 */
function inkbound_format_count( int $n ): string {
	if ( $n >= 1000000 ) {
		return rtrim( rtrim( number_format( $n / 1000000, 1 ), '0' ), '.' ) . 'm';
	}
	if ( $n >= 1000 ) {
		return rtrim( rtrim( number_format( $n / 1000, 1 ), '0' ), '.' ) . 'k';
	}
	return (string) $n;
}

/**
 * Recalculate rolled-up story stats from chapters.
 *
 * @param int $story_id Story ID.
 */
function inkbound_recalculate_story( int $story_id ): void {
	$chapters = inkbound_story_chapters( $story_id, 'publish' );
	$words    = 0;
	$last     = null;
	foreach ( $chapters as $chapter ) {
		$words += (int) get_post_meta( $chapter->ID, '_inkbound_word_count', true );
		$last   = $chapter;
	}
	update_post_meta( $story_id, '_inkbound_chapter_count', count( $chapters ) );
	update_post_meta( $story_id, '_inkbound_word_count', $words );
	update_post_meta( $story_id, '_inkbound_last_chapter_id', $last ? (int) $last->ID : 0 );
	update_post_meta( $story_id, '_inkbound_last_chapter_at', $last ? $last->post_date_gmt : '' );

	$followers = Inkbound_Follow::count_for_story( $story_id );
	update_post_meta( $story_id, '_inkbound_follower_count', $followers );

	if ( $last ) {
		wp_update_post(
			array(
				'ID'                => $story_id,
				'post_modified'     => $last->post_date,
				'post_modified_gmt' => $last->post_date_gmt,
			)
		);
	}
}

/**
 * Suggested next chapter number for a story.
 *
 * @param int $story_id Story ID.
 * @return string
 */
function inkbound_next_number( int $story_id ): string {
	$chapters = inkbound_story_chapters( $story_id, 'any' );
	$max      = 0;
	foreach ( $chapters as $chapter ) {
		$max = max( $max, (float) inkbound_chapter_number( $chapter ) );
	}
	return (string) ( (int) $max + 1 );
}

/**
 * Catalog query from request.
 *
 * @return WP_Query
 */
function inkbound_catalog_query(): WP_Query {
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	if ( isset( $_GET['pg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = max( $paged, absint( wp_unslash( $_GET['pg'] ) ) );
	}
	$genre  = sanitize_title( wp_unslash( $_GET['genre'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$status = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$sort   = sanitize_key( wp_unslash( $_GET['sort'] ?? 'updated' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$q      = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$args = array(
		'post_type'      => 'inkbound_story',
		'post_status'    => 'publish',
		'posts_per_page' => (int) inkbound_option( 'stories_per_page', 12 ),
		'paged'          => $paged,
	);

	if ( $q ) {
		$args['s'] = $q;
	}

	if ( $genre ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'inkbound_genre',
			'field'    => 'slug',
			'terms'    => $genre,
		);
	}

	if ( $status && isset( inkbound_statuses()[ $status ] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'inkbound_status',
			'field'    => 'slug',
			'terms'    => $status,
		);
	}

	switch ( $sort ) {
		case 'title':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		case 'popular':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_inkbound_view_count';
			$args['order']    = 'DESC';
			break;
		case 'followers':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_inkbound_follower_count';
			$args['order']    = 'DESC';
			break;
		case 'updated':
		default:
			$args['orderby'] = 'modified';
			$args['order']   = 'DESC';
			break;
	}

	return new WP_Query( $args );
}

/**
 * Escape and format warnings list.
 *
 * @param string $raw Raw comma-separated warnings.
 * @return string[]
 */
function inkbound_parse_warnings( string $raw ): array {
	$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
	return array_values( array_unique( $parts ) );
}

/**
 * Current reader cookie id (guests).
 *
 * @return string
 */
function inkbound_reader_cookie(): string {
	$id = isset( $_COOKIE['inkbound_rid'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['inkbound_rid'] ) ) : '';
	if ( $id && preg_match( '/^[a-f0-9]{16,64}$/', $id ) ) {
		return $id;
	}
	$id = wp_generate_password( 32, false, false );
	if ( ! headers_sent() ) {
		setcookie(
			'inkbound_rid',
			$id,
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		$_COOKIE['inkbound_rid'] = $id;
	}
	return $id;
}

/**
 * Catalog / library / inbox URLs.
 *
 * @param string $which catalog|library|inbox.
 * @return string
 */
function inkbound_url( string $which = 'catalog' ): string {
	switch ( $which ) {
		case 'library':
			return home_url( '/library/' );
		case 'inbox':
			return home_url( '/library/inbox/' );
		default:
			return get_post_type_archive_link( 'inkbound_story' ) ?: home_url( '/stories/' );
	}
}

/**
 * Whether the current request should use plugin templates.
 */
function inkbound_is_app_request(): bool {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}
	if ( get_query_var( 'inkbound_library' ) || get_query_var( 'inkbound_inbox' ) ) {
		return true;
	}
	if ( is_post_type_archive( 'inkbound_story' ) || is_tax( array( 'inkbound_genre', 'inkbound_status', 'inkbound_trope' ) ) ) {
		return true;
	}
	if ( is_singular( array( 'inkbound_story', 'inkbound_chapter' ) ) ) {
		return true;
	}
	if ( inkbound_option( 'replace_home' ) && is_front_page() ) {
		return true;
	}
	return false;
}
