<?php
/**
 * Plugin bootstrap.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin controller.
 */
class Inkbound_Plugin {
	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate(): void {
		$plugin = self::instance();
		$plugin->create_tables();
		Inkbound_CPT::register();
		Inkbound_CPT::insert_default_terms();
		flush_rewrite_rules();

		$existing = get_option( 'inkbound_options', array() );
		if ( ! is_array( $existing ) || ! $existing ) {
			update_option( 'inkbound_options', inkbound_default_options() );
		}

		if ( ! wp_next_scheduled( 'inkbound_process_mail_queue' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'inkbound_process_mail_queue' );
		}
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
		$timestamp = wp_next_scheduled( 'inkbound_process_mail_queue' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'inkbound_process_mail_queue' );
		}
	}

	public function boot(): void {
		$this->maybe_upgrade();
		$this->load_textdomain();

		Inkbound_CPT::instance()->boot();
		Inkbound_Follow::instance()->boot();
		Inkbound_Progress::instance()->boot();
		Inkbound_Notify::instance()->boot();
		Inkbound_Mail::instance()->boot();
		Inkbound_REST::instance()->boot();
		Inkbound_Admin::instance()->boot();
		Inkbound_Frontend::instance()->boot();
		Inkbound_Seed::instance()->boot();

		add_action( 'init', array( $this, 'register_rewrites' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'inkbound_process_mail_queue', array( Inkbound_Mail::instance(), 'process_queue' ) );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( 'admin_init', array( $this, 'privacy_policy_content' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once INKB_DIR . 'includes/class-cli.php';
			WP_CLI::add_command( 'inkbound', 'Inkbound_CLI' );
		}
	}

	/**
	 * Load translations for local / non-.org installs.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'inkbound',
			false,
			dirname( plugin_basename( INKB_FILE ) ) . '/languages'
		);
	}

	/**
	 * Suggested privacy policy text for Tools → Privacy.
	 */
	public function privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			'<p>%1$s</p><ul><li>%2$s</li><li>%3$s</li><li>%4$s</li><li>%5$s</li></ul>',
			esc_html__( 'Inkbound helps readers follow serial stories and optionally receive chapter update emails. When using this plugin you should disclose:', 'inkbound' ),
			esc_html__( 'Email addresses collected for chapter subscriptions, confirmation tokens, and unsubscribe links.', 'inkbound' ),
			esc_html__( 'Account-linked follows and on-site update notices for logged-in readers.', 'inkbound' ),
			esc_html__( 'A guest reader cookie (inkbound_rid) used only to restore reading progress across visits.', 'inkbound' ),
			esc_html__( 'Optional mail delivery logs retained in the WordPress options table for troubleshooting.', 'inkbound' )
		);

		wp_add_privacy_policy_content( 'Inkbound', wp_kses_post( $content ) );
	}

	public function cron_schedules( array $schedules ): array {
		if ( ! isset( $schedules['inkbound_five_minutes'] ) ) {
			$schedules['inkbound_five_minutes'] = array(
				'interval' => 300,
				'display'  => __( 'Every five minutes (Inkbound)', 'inkbound' ),
			);
		}
		return $schedules;
	}

	public function query_vars( array $vars ): array {
		$vars[] = 'inkbound_library';
		$vars[] = 'inkbound_inbox';
		$vars[] = 'inkbound_confirm';
		$vars[] = 'inkbound_unsub';
		return $vars;
	}

	public function register_rewrites(): void {
		add_rewrite_rule( '^library/inbox/?$', 'index.php?inkbound_inbox=1', 'top' );
		add_rewrite_rule( '^library/?$', 'index.php?inkbound_library=1', 'top' );
		add_rewrite_rule( '^inkbound/confirm/([a-zA-Z0-9]+)/?$', 'index.php?inkbound_confirm=$matches[1]', 'top' );
		add_rewrite_rule( '^inkbound/unsubscribe/([a-zA-Z0-9]+)/?$', 'index.php?inkbound_unsub=$matches[1]', 'top' );

		add_rewrite_tag( '%inkbound_story%', '([^/]+)' );
		add_rewrite_rule(
			'^stories/([^/]+)/([^/]+)/?$',
			'index.php?inkbound_chapter=$matches[2]',
			'top'
		);
	}

	public function maybe_upgrade(): void {
		$version = get_option( 'inkbound_db_version' );
		if ( INKB_VERSION !== $version ) {
			$this->create_tables();
			update_option( 'inkbound_db_version', INKB_VERSION );
		}
	}

	public function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$subs = $wpdb->prefix . 'inkbound_subs';
		dbDelta(
			"CREATE TABLE {$subs} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				story_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				email varchar(190) NOT NULL DEFAULT '',
				status varchar(20) NOT NULL DEFAULT 'active',
				notify_email tinyint(1) NOT NULL DEFAULT 1,
				confirm_token varchar(64) NOT NULL DEFAULT '',
				unsub_token varchar(64) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				confirmed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY story_status (story_id, status),
				KEY user_story (user_id, story_id),
				KEY email_story (email, story_id)
			) {$charset};"
		);

		$progress = $wpdb->prefix . 'inkbound_progress';
		dbDelta(
			"CREATE TABLE {$progress} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				reader_id varchar(64) NOT NULL DEFAULT '',
				story_id bigint(20) unsigned NOT NULL,
				chapter_id bigint(20) unsigned NOT NULL,
				percent tinyint(3) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_story (user_id, story_id),
				KEY reader_story (reader_id, story_id)
			) {$charset};"
		);

		$notices = $wpdb->prefix . 'inkbound_notices';
		dbDelta(
			"CREATE TABLE {$notices} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				story_id bigint(20) unsigned NOT NULL,
				chapter_id bigint(20) unsigned NOT NULL,
				notice_type varchar(40) NOT NULL DEFAULT 'new_chapter',
				read_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_read (user_id, read_at),
				KEY chapter (chapter_id)
			) {$charset};"
		);

		$mailq = $wpdb->prefix . 'inkbound_mailq';
		dbDelta(
			"CREATE TABLE {$mailq} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				sub_id bigint(20) unsigned NOT NULL,
				chapter_id bigint(20) unsigned NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'queued',
				attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
				scheduled_at datetime NOT NULL,
				sent_at datetime DEFAULT NULL,
				error_text text,
				PRIMARY KEY  (id),
				KEY status_sched (status, scheduled_at),
				KEY chapter (chapter_id)
			) {$charset};"
		);
	}
}
