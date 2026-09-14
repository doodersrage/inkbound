<?php
/**
 * Reader subscriptions: logged-in follows and email subscribers.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbound_Follow {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'template_redirect', array( $this, 'handle_tokens' ) );
		add_action( 'init', array( $this, 'handle_forms' ) );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'inkbound_subs';
	}

	public static function count_for_story( int $story_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . ' WHERE story_id = %d AND status = %s',
				$story_id,
				'active'
			)
		);
	}

	public static function is_following( int $story_id, int $user_id = 0, string $email = '' ): bool {
		global $wpdb;
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id ) {
			$found = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM ' . self::table() . ' WHERE story_id = %d AND user_id = %d AND status = %s LIMIT 1',
					$story_id,
					$user_id,
					'active'
				)
			);
			return (bool) $found;
		}
		if ( $email ) {
			$found = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM ' . self::table() . ' WHERE story_id = %d AND email = %s AND status = %s LIMIT 1',
					$story_id,
					$email,
					'active'
				)
			);
			return (bool) $found;
		}
		return false;
	}

	public static function get( int $id ): ?object {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
		return $row ?: null;
	}

	/**
	 * Follow a story for a user and/or email address.
	 *
	 * @return object|WP_Error Subscription row.
	 */
	public static function subscribe( int $story_id, int $user_id = 0, string $email = '', bool $notify_email = true ) {
		global $wpdb;

		if ( 'inkbound_story' !== get_post_type( $story_id ) ) {
			return new WP_Error( 'invalid_story', __( 'That story does not exist.', 'inkbound' ) );
		}

		$email = strtolower( sanitize_email( $email ) );
		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user && ! $email ) {
				$email = strtolower( $user->user_email );
			}
		}

		if ( ! $user_id && ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Enter a valid email address.', 'inkbound' ) );
		}

		$existing = null;
		if ( $user_id ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . self::table() . ' WHERE story_id = %d AND user_id = %d LIMIT 1',
					$story_id,
					$user_id
				)
			);
		}
		if ( ! $existing && $email ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . self::table() . ' WHERE story_id = %d AND email = %s LIMIT 1',
					$story_id,
					$email
				)
			);
		}

		$need_confirm = (bool) inkbound_option( 'confirm_subs' ) && ! $user_id;
		$now          = current_time( 'mysql' );

		if ( $existing ) {
			$wpdb->update(
				self::table(),
				array(
					'status'        => $need_confirm && 'active' !== $existing->status ? 'pending' : 'active',
					'notify_email'  => $notify_email ? 1 : 0,
					'email'         => $email ?: $existing->email,
					'user_id'       => $user_id ?: (int) $existing->user_id,
					'confirmed_at'  => $need_confirm ? $existing->confirmed_at : $now,
				),
				array( 'id' => (int) $existing->id ),
				array( '%s', '%d', '%s', '%d', '%s' ),
				array( '%d' )
			);
			$row = self::get( (int) $existing->id );
			if ( $need_confirm && $row && 'pending' === $row->status ) {
				Inkbound_Mail::send_confirm( $row );
			}
			inkbound_recalculate_story( $story_id );
			return $row;
		}

		$token = wp_generate_password( 32, false, false );
		$unsub = wp_generate_password( 32, false, false );
		$wpdb->insert(
			self::table(),
			array(
				'story_id'      => $story_id,
				'user_id'       => $user_id,
				'email'         => $email,
				'status'        => $need_confirm ? 'pending' : 'active',
				'notify_email'  => $notify_email ? 1 : 0,
				'confirm_token' => $token,
				'unsub_token'   => $unsub,
				'created_at'    => $now,
				'confirmed_at'  => $need_confirm ? null : $now,
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		$row = self::get( (int) $wpdb->insert_id );
		if ( $need_confirm && $row ) {
			Inkbound_Mail::send_confirm( $row );
		}
		inkbound_recalculate_story( $story_id );
		return $row;
	}

	public static function unfollow( int $story_id, int $user_id = 0, string $email = '' ): bool {
		global $wpdb;
		if ( $user_id ) {
			$wpdb->update(
				self::table(),
				array( 'status' => 'unsubscribed' ),
				array(
					'story_id' => $story_id,
					'user_id'  => $user_id,
				)
			);
		}
		if ( $email ) {
			$wpdb->update(
				self::table(),
				array( 'status' => 'unsubscribed' ),
				array(
					'story_id' => $story_id,
					'email'    => strtolower( sanitize_email( $email ) ),
				)
			);
		}
		inkbound_recalculate_story( $story_id );
		return true;
	}

	public static function unfollow_token( string $token ): ?object {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE unsub_token = %s LIMIT 1', $token )
		);
		if ( ! $row ) {
			return null;
		}
		$wpdb->update( self::table(), array( 'status' => 'unsubscribed' ), array( 'id' => (int) $row->id ) );
		inkbound_recalculate_story( (int) $row->story_id );
		return $row;
	}

	public static function confirm_token( string $token ): ?object {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE confirm_token = %s LIMIT 1', $token )
		);
		if ( ! $row ) {
			return null;
		}
		$wpdb->update(
			self::table(),
			array(
				'status'       => 'active',
				'confirmed_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $row->id )
		);
		inkbound_recalculate_story( (int) $row->story_id );
		return self::get( (int) $row->id );
	}

	public static function active_for_story( int $story_id ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE story_id = %d AND status = %s',
				$story_id,
				'active'
			)
		) ?: array();
	}

	public static function library_story_ids( int $user_id ): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT story_id FROM ' . self::table() . ' WHERE user_id = %d AND status = %s',
				$user_id,
				'active'
			)
		);
		return array_map( 'intval', $ids ?: array() );
	}

	public function handle_tokens(): void {
		$confirm = get_query_var( 'inkbound_confirm' );
		$unsub   = get_query_var( 'inkbound_unsub' );

		if ( $confirm ) {
			$row = self::confirm_token( sanitize_text_field( (string) $confirm ) );
			$msg = $row
				? __( 'Subscription confirmed. New chapters will arrive by email.', 'inkbound' )
				: __( 'That confirmation link is invalid or expired.', 'inkbound' );
			wp_safe_redirect( add_query_arg( 'inkbound_notice', rawurlencode( $msg ), inkbound_url( 'catalog' ) ) );
			exit;
		}

		if ( $unsub ) {
			$row = self::unfollow_token( sanitize_text_field( (string) $unsub ) );
			$msg = $row
				? __( 'You are unsubscribed from that story.', 'inkbound' )
				: __( 'That unsubscribe link is invalid.', 'inkbound' );
			wp_safe_redirect( add_query_arg( 'inkbound_notice', rawurlencode( $msg ), inkbound_url( 'catalog' ) ) );
			exit;
		}
	}

	public function handle_forms(): void {
		if ( empty( $_POST['inkbound_action'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['inkbound_action'] ) );
		if ( ! in_array( $action, array( 'subscribe_email', 'toggle_follow' ), true ) ) {
			return;
		}
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'inkbound_front' ) ) {
			return;
		}

		$story_id = (int) ( $_POST['story_id'] ?? 0 );
		$redirect = wp_get_referer() ?: inkbound_url();

		if ( 'subscribe_email' === $action ) {
			$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
			$result = self::subscribe( $story_id, get_current_user_id(), $email, true );
			if ( is_wp_error( $result ) ) {
				$redirect = add_query_arg( 'inkbound_notice', rawurlencode( $result->get_error_message() ), $redirect );
			} elseif ( 'pending' === $result->status ) {
				$redirect = add_query_arg( 'inkbound_notice', rawurlencode( __( 'Check your inbox to confirm the subscription.', 'inkbound' ) ), $redirect );
			} else {
				$redirect = add_query_arg( 'inkbound_notice', rawurlencode( __( 'You are subscribed. New chapters will be emailed to you.', 'inkbound' ) ), $redirect );
			}
		}

		if ( 'toggle_follow' === $action && is_user_logged_in() ) {
			if ( self::is_following( $story_id ) ) {
				self::unfollow( $story_id, get_current_user_id() );
				$redirect = add_query_arg( 'inkbound_notice', rawurlencode( __( 'Removed from your library.', 'inkbound' ) ), $redirect );
			} else {
				self::subscribe( $story_id, get_current_user_id(), '', true );
				$redirect = add_query_arg( 'inkbound_notice', rawurlencode( __( 'Followed. We will notify you when a chapter goes up.', 'inkbound' ) ), $redirect );
			}
		}

		wp_safe_redirect( $redirect );
		exit;
	}
}
