<?php
/**
 * In-site update notices when chapters publish.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom Inkbound tables require direct $wpdb access.

class Inkbound_Notify {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'transition_post_status', array( $this, 'on_status' ), 10, 3 );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'inkbound_notices';
	}

	public function on_status( string $new, string $old, WP_Post $post ): void {
		if ( 'inkbound_chapter' !== $post->post_type ) {
			return;
		}
		if ( 'publish' !== $new || 'publish' === $old ) {
			return;
		}
		if ( ! empty( $GLOBALS['inkbound_seeding'] ) ) {
			return;
		}
		if ( ! inkbound_option( 'notify_on_publish' ) ) {
			return;
		}

		$notify = get_post_meta( $post->ID, '_inkbound_notify', true );
		if ( '0' === (string) $notify ) {
			return;
		}

		self::fanout( $post );
	}

	public static function fanout( WP_Post $chapter ): void {
		$story = inkbound_chapter_story( $chapter );
		if ( ! $story ) {
			return;
		}

		$subs = Inkbound_Follow::active_for_story( (int) $story->ID );
		$now  = current_time( 'mysql' );

		global $wpdb;
		foreach ( $subs as $sub ) {
			if ( (int) $sub->user_id ) {
				$wpdb->insert(
					self::table(),
					array(
						'user_id'     => (int) $sub->user_id,
						'story_id'    => (int) $story->ID,
						'chapter_id'  => (int) $chapter->ID,
						'notice_type' => 'new_chapter',
						'read_at'     => null,
						'created_at'  => $now,
					)
				);
			}
			if ( (int) $sub->notify_email && is_email( $sub->email ) ) {
				Inkbound_Mail::queue( (int) $sub->id, (int) $chapter->ID );
			}
		}

		Inkbound_Mail::process_queue( 40 );
	}

	public static function unread_count( int $user_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE user_id = %d AND read_at IS NULL',
				self::table(),
				$user_id
			)
		);
	}

	public static function for_user( int $user_id, int $limit = 40 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE user_id = %d ORDER BY created_at DESC LIMIT %d',
				self::table(),
				$user_id,
				$limit
			)
		) ?: array();
	}

	public static function mark_read( int $user_id, int $notice_id = 0 ): void {
		global $wpdb;
		$now = current_time( 'mysql' );
		if ( $notice_id ) {
			$wpdb->update(
				self::table(),
				array( 'read_at' => $now ),
				array(
					'id'      => $notice_id,
					'user_id' => $user_id,
				)
			);
			return;
		}
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE %i SET read_at = %s WHERE user_id = %d AND read_at IS NULL',
				self::table(),
				$now,
				$user_id
			)
		);
	}
}
