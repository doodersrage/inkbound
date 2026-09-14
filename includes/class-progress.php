<?php
/**
 * Reading progress for guests and accounts.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom Inkbound tables require direct $wpdb access.

class Inkbound_Progress {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'template_redirect', array( $this, 'bump_views' ) );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'inkbound_progress';
	}

	public static function save( int $story_id, int $chapter_id, int $percent, int $user_id = 0, string $reader_id = '' ): void {
		global $wpdb;
		$percent   = max( 0, min( 100, $percent ) );
		$user_id   = $user_id ?: get_current_user_id();
		$reader_id = $reader_id ?: ( $user_id ? '' : inkbound_reader_cookie() );
		$now       = current_time( 'mysql' );

		if ( $user_id ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id FROM %i WHERE user_id = %d AND story_id = %d LIMIT 1',
					self::table(),
					$user_id,
					$story_id
				)
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id FROM %i WHERE reader_id = %s AND story_id = %d LIMIT 1',
					self::table(),
					$reader_id,
					$story_id
				)
			);
		}

		$data = array(
			'user_id'    => $user_id,
			'reader_id'  => $reader_id,
			'story_id'   => $story_id,
			'chapter_id' => $chapter_id,
			'percent'    => $percent,
			'updated_at' => $now,
		);

		if ( $row ) {
			$wpdb->update( self::table(), $data, array( 'id' => (int) $row->id ) );
		} else {
			$wpdb->insert( self::table(), $data );
		}
	}

	public static function get_for_story( int $story_id, int $user_id = 0, string $reader_id = '' ): ?object {
		global $wpdb;
		$user_id   = $user_id ?: get_current_user_id();
		if ( ! $reader_id && ! $user_id && isset( $_COOKIE['inkbound_rid'] ) ) {
			$reader_id = sanitize_text_field( wp_unslash( $_COOKIE['inkbound_rid'] ) );
		}

		if ( $user_id ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE user_id = %d AND story_id = %d LIMIT 1',
					self::table(),
					$user_id,
					$story_id
				)
			);
			return $row ?: null;
		}
		if ( ! $reader_id ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE reader_id = %s AND story_id = %d LIMIT 1',
				self::table(),
				$reader_id,
				$story_id
			)
		);
		return $row ?: null;
	}

	/**
	 * Continue-reading rows for the current visitor.
	 *
	 * @return object[]
	 */
	public static function continue_list( int $limit = 6 ): array {
		global $wpdb;
		$user_id   = get_current_user_id();
		$reader_id = isset( $_COOKIE['inkbound_rid'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['inkbound_rid'] ) ) : '';

		if ( $user_id ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE user_id = %d ORDER BY updated_at DESC LIMIT %d',
					self::table(),
					$user_id,
					$limit
				)
			) ?: array();
		}
		if ( ! $reader_id ) {
			return array();
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE reader_id = %s ORDER BY updated_at DESC LIMIT %d',
				self::table(),
				$reader_id,
				$limit
			)
		) ?: array();
	}

	public static function continue_url( int $story_id ): string {
		$row = self::get_for_story( $story_id );
		if ( $row && $row->chapter_id && get_post_status( $row->chapter_id ) === 'publish' ) {
			return get_permalink( (int) $row->chapter_id );
		}
		$first = inkbound_first_chapter( $story_id );
		return $first ? get_permalink( $first ) : get_permalink( $story_id );
	}

	public function bump_views(): void {
		if ( ! is_singular( array( 'inkbound_story', 'inkbound_chapter' ) ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		$key     = 'inkbound_viewed_' . $post_id;
		if ( ! empty( $_COOKIE[ $key ] ) ) {
			return;
		}
		$views = (int) get_post_meta( $post_id, '_inkbound_view_count', true );
		update_post_meta( $post_id, '_inkbound_view_count', $views + 1 );

		$chapter = get_post( $post_id );
		if ( $chapter && 'inkbound_chapter' === $chapter->post_type && $chapter->post_parent ) {
			$story_views = (int) get_post_meta( $chapter->post_parent, '_inkbound_view_count', true );
			update_post_meta( $chapter->post_parent, '_inkbound_view_count', $story_views + 1 );
			Inkbound_Progress::save( (int) $chapter->post_parent, (int) $chapter->ID, 5 );
		}

		if ( ! headers_sent() ) {
			setcookie( $key, '1', time() + 6 * HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', '', is_ssl(), true );
		}
	}
}
