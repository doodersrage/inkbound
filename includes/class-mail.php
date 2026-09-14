<?php
/**
 * Chapter update mail and a local delivery log (works without SMTP).
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom Inkbound tables require direct $wpdb access.

class Inkbound_Mail {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );
		add_filter( 'wp_mail_from', array( $this, 'from_email' ) );
	}

	public static function queue_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'inkbound_mailq';
	}

	public function from_name( string $name ): string {
		$custom = trim( (string) inkbound_option( 'from_name' ) );
		return $custom ?: $name;
	}

	public function from_email( string $email ): string {
		$custom = sanitize_email( (string) inkbound_option( 'from_email' ) );
		return $custom ?: $email;
	}

	public static function queue( int $sub_id, int $chapter_id ): void {
		global $wpdb;
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE sub_id = %d AND chapter_id = %d LIMIT 1',
				self::queue_table(),
				$sub_id,
				$chapter_id
			)
		);
		if ( $exists ) {
			return;
		}
		$wpdb->insert(
			self::queue_table(),
			array(
				'sub_id'       => $sub_id,
				'chapter_id'   => $chapter_id,
				'status'       => 'queued',
				'attempts'     => 0,
				'scheduled_at' => current_time( 'mysql' ),
			)
		);
	}

	public static function process_queue( int $limit = 25 ): void {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE status = %s AND scheduled_at <= %s ORDER BY id ASC LIMIT %d',
				self::queue_table(),
				'queued',
				current_time( 'mysql' ),
				$limit
			)
		);
		if ( ! $rows ) {
			return;
		}
		foreach ( $rows as $row ) {
			self::send_chapter_row( $row );
		}
	}

	public static function send_chapter_row( object $row ): bool {
		$sub     = Inkbound_Follow::get( (int) $row->sub_id );
		$chapter = get_post( (int) $row->chapter_id );
		if ( ! $sub || ! $chapter || ! is_email( $sub->email ) ) {
			self::mark( (int) $row->id, 'skipped', 'Missing subscriber or chapter.' );
			return false;
		}
		$story = inkbound_chapter_story( $chapter );
		if ( ! $story ) {
			self::mark( (int) $row->id, 'skipped', 'Chapter has no story.' );
			return false;
		}

		$heading = inkbound_chapter_heading( $chapter );
		$subject = sprintf( '[%1$s] %2$s — %3$s', $story->post_title, $heading, $chapter->post_title );
		$body    = self::chapter_html( $story, $chapter, $sub );
		$text    = self::chapter_text( $story, $chapter, $sub );

		$sent = self::deliver( $sub->email, $subject, $body, $text, array(
			'story'   => $story->post_title,
			'chapter' => $chapter->post_title,
			'type'    => 'chapter',
		) );

		self::mark( (int) $row->id, $sent ? 'sent' : 'failed', $sent ? '' : 'wp_mail returned false' );
		return $sent;
	}

	public static function send_confirm( object $sub ): void {
		$story = get_post( (int) $sub->story_id );
		if ( ! $story || ! is_email( $sub->email ) ) {
			return;
		}
		$url     = home_url( user_trailingslashit( 'inkbound/confirm/' . $sub->confirm_token ) );
		$subject = sprintf(
			/* translators: %s: story title */
			__( 'Confirm your subscription to %s', 'inkbound' ),
			$story->post_title
		);
		$html    = '<p>' . esc_html(
			sprintf(
				/* translators: %s: story title */
				__( 'Confirm you want chapter updates for “%s”.', 'inkbound' ),
				$story->post_title
			)
		) . '</p>';
		$html   .= '<p><a href="' . esc_url( $url ) . '">' . esc_html__( 'Confirm subscription', 'inkbound' ) . '</a></p>';
		self::deliver( $sub->email, $subject, self::wrap( $subject, $html ), wp_strip_all_tags( $html ), array( 'type' => 'confirm' ) );
	}

	public static function deliver( string $to, string $subject, string $html, string $text, array $meta = array() ): bool {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $to, $subject, $html, $headers );

		$log   = get_option( 'inkbound_mail_log', array() );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'at'      => current_time( 'mysql' ),
			'to'      => $to,
			'subject' => $subject,
			'text'    => $text,
			'html'    => $html,
			'sent'    => $sent,
			'meta'    => $meta,
		);
		$log = array_slice( $log, -80 );
		update_option( 'inkbound_mail_log', $log, false );

		$dir = WP_CONTENT_DIR . '/uploads';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$line = sprintf( "[%s] to=%s subject=%s sent=%s\n", current_time( 'mysql' ), $to, $subject, $sent ? 'yes' : 'no' );
		file_put_contents( $dir . '/inkbound-mail.log', $line, FILE_APPEND );

		return (bool) $sent;
	}

	public static function chapter_html( WP_Post $story, WP_Post $chapter, object $sub ): string {
		$mode     = inkbound_option( 'email_mode', 'full' );
		$heading  = inkbound_chapter_heading( $chapter );
		$read_url = get_permalink( $chapter );
		$unsub    = home_url( user_trailingslashit( 'inkbound/unsubscribe/' . $sub->unsub_token ) );
		$note     = get_post_meta( $chapter->ID, '_inkbound_author_note', true );

		$inner  = '<p style="color:#7a6a58;font-size:13px;letter-spacing:.12em;text-transform:uppercase;margin:0 0 8px">' . esc_html( $story->post_title ) . '</p>';
		$inner .= '<h1 style="font-family:Georgia,serif;font-size:26px;line-height:1.25;margin:0 0 6px">' . esc_html( $heading ) . '</h1>';
		$inner .= '<p style="font-size:18px;margin:0 0 24px">' . esc_html( $chapter->post_title ) . '</p>';

		if ( 'excerpt' === $mode ) {
			$inner .= '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $chapter->post_content ), 80 ) ) . '</p>';
			$inner .= '<p><a href="' . esc_url( $read_url ) . '" style="background:#9a3b2f;color:#fff;text-decoration:none;padding:10px 16px;border-radius:999px;display:inline-block">' . esc_html__( 'Continue on the site', 'inkbound' ) . '</a></p>';
		} else {
			$inner .= wp_kses_post( apply_filters( 'the_content', $chapter->post_content ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core content filter.
			if ( $note ) {
				$inner .= '<div style="margin-top:28px;padding:16px;border-top:1px solid #e6dcc8"><p style="font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#7a6a58">Author\'s note</p>' . wp_kses_post( wpautop( $note ) ) . '</div>';
			}
			$inner .= '<p style="margin-top:28px"><a href="' . esc_url( $read_url ) . '">' . esc_html__( 'Read in the browser', 'inkbound' ) . '</a></p>';
		}

		$inner .= '<p style="margin-top:32px;font-size:12px;color:#7a6a58">' . sprintf(
			/* translators: %s: unsubscribe URL */
			esc_html__( 'You received this because you follow this story. %s', 'inkbound' ),
			'<a href="' . esc_url( $unsub ) . '">' . esc_html__( 'Unsubscribe', 'inkbound' ) . '</a>'
		) . '</p>';

		return self::wrap( $story->post_title . ' — ' . $heading, $inner );
	}

	public static function chapter_text( WP_Post $story, WP_Post $chapter, object $sub ): string {
		$unsub = home_url( user_trailingslashit( 'inkbound/unsubscribe/' . $sub->unsub_token ) );
		$parts = array(
			$story->post_title,
			inkbound_chapter_heading( $chapter ) . ' — ' . $chapter->post_title,
			wp_trim_words( wp_strip_all_tags( $chapter->post_content ), 80 ),
			get_permalink( $chapter ),
			'Unsubscribe: ' . $unsub,
		);
		return implode( "\n\n", $parts );
	}

	public static function wrap( string $title, string $inner ): string {
		return '<!DOCTYPE html><html><body style="margin:0;background:#f4efe6;padding:24px;font-family:Georgia,serif;color:#1a1612">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">
<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fffaf3;padding:32px;border:1px solid #e6dcc8">
<tr><td>
<p style="margin:0 0 24px;letter-spacing:.22em;text-transform:uppercase;font-size:11px;color:#9a3b2f">Inkbound</p>
' . $inner . '
</td></tr></table></td></tr></table></body></html>';
	}

	public static function mark( int $id, string $status, string $error = '' ): void {
		global $wpdb;
		$wpdb->update(
			self::queue_table(),
			array(
				'status'     => $status,
				'sent_at'    => 'sent' === $status ? current_time( 'mysql' ) : null,
				'error_text' => $error,
				'attempts'   => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT attempts FROM %i WHERE id = %d', self::queue_table(), $id ) ) + 1,
			),
			array( 'id' => $id )
		);
	}

	public static function log(): array {
		$log = get_option( 'inkbound_mail_log', array() );
		return is_array( $log ) ? array_reverse( $log ) : array();
	}
}
