<?php
/**
 * Front-end templates, assets, query routing.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbound_Frontend {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_theme' ), 100 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'comments_template', array( $this, 'comments_template' ) );
		add_action( 'pre_get_posts', array( $this, 'home_query' ) );
		add_filter( 'document_title_parts', array( $this, 'titles' ) );
		add_action( 'after_setup_theme', array( $this, 'theme_support' ) );
	}

	public function theme_support(): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
	}

	public function home_query( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( inkbound_option( 'replace_home' ) && $query->is_home() && $query->is_front_page() ) {
			$query->set( 'post_type', 'inkbound_story' );
			$query->set( 'posts_per_page', (int) inkbound_option( 'stories_per_page', 12 ) );
		}
	}

	public function template_include( string $template ): string {
		if ( get_query_var( 'inkbound_inbox' ) ) {
			return INKB_DIR . 'templates/inbox.php';
		}
		if ( get_query_var( 'inkbound_library' ) ) {
			return INKB_DIR . 'templates/library.php';
		}
		if ( is_singular( 'inkbound_chapter' ) ) {
			return INKB_DIR . 'templates/single-chapter.php';
		}
		if ( is_singular( 'inkbound_story' ) ) {
			return INKB_DIR . 'templates/single-story.php';
		}
		if ( is_post_type_archive( 'inkbound_story' ) || is_tax( array( 'inkbound_genre', 'inkbound_status', 'inkbound_trope' ) ) ) {
			return INKB_DIR . 'templates/catalog.php';
		}
		if ( inkbound_option( 'replace_home' ) && is_front_page() ) {
			return INKB_DIR . 'templates/catalog.php';
		}
		return $template;
	}

	public function comments_template( string $file ): string {
		if ( is_singular( 'inkbound_chapter' ) ) {
			return INKB_DIR . 'templates/comments.php';
		}
		return $file;
	}

	public function dequeue_theme(): void {
		if ( ! inkbound_is_app_request() ) {
			return;
		}
		$theme = get_stylesheet();
		wp_dequeue_style( $theme . '-style' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
	}

	public function assets(): void {
		if ( ! inkbound_is_app_request() ) {
			return;
		}
		// System stacks only — no remote webfonts (WordPress.org guideline).
		wp_enqueue_style( 'inkbound', INKB_URL . 'public/css/inkbound.css', array(), INKB_VERSION );
		wp_enqueue_script( 'inkbound', INKB_URL . 'public/js/inkbound.js', array(), INKB_VERSION, true );

		$story_id   = 0;
		$chapter_id = 0;
		if ( is_singular( 'inkbound_story' ) ) {
			$story_id = get_queried_object_id();
		}
		if ( is_singular( 'inkbound_chapter' ) ) {
			$chapter_id = get_queried_object_id();
			$story      = inkbound_chapter_story( $chapter_id );
			$story_id   = $story ? (int) $story->ID : 0;
		}

		$next = $chapter_id ? inkbound_adjacent_chapter( $chapter_id, 'next' ) : null;
		$prev = $chapter_id ? inkbound_adjacent_chapter( $chapter_id, 'prev' ) : null;

		wp_localize_script(
			'inkbound',
			'inkboundApp',
			array(
				'rest'      => esc_url_raw( rest_url( 'inkbound/v1/' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'loggedIn'  => is_user_logged_in(),
				'storyId'   => $story_id,
				'chapterId' => $chapter_id,
				'nextUrl'   => $next ? get_permalink( $next ) : '',
				'prevUrl'   => $prev ? get_permalink( $prev ) : '',
				'following' => $story_id ? Inkbound_Follow::is_following( $story_id ) : false,
				'i18n'      => array(
					'followed'    => __( 'Following', 'inkbound' ),
					'follow'      => __( 'Follow', 'inkbound' ),
					'subscribed'  => __( 'Subscribed', 'inkbound' ),
					'subscribe'   => __( 'Email new chapters', 'inkbound' ),
				),
			)
		);
	}

	public function body_class( array $classes ): array {
		if ( inkbound_is_app_request() ) {
			$classes[] = 'inkbound-app';
		}
		if ( is_singular( 'inkbound_chapter' ) ) {
			$classes[] = 'inkbound-reader';
		}
		return $classes;
	}

	public function titles( array $parts ): array {
		if ( get_query_var( 'inkbound_library' ) ) {
			$parts['title'] = __( 'Library', 'inkbound' );
		}
		if ( get_query_var( 'inkbound_inbox' ) ) {
			$parts['title'] = __( 'Updates', 'inkbound' );
		}
		if ( is_post_type_archive( 'inkbound_story' ) || ( inkbound_option( 'replace_home' ) && is_front_page() ) ) {
			$parts['title'] = inkbound_option( 'catalog_title' );
		}
		return $parts;
	}

	public static function notice(): void {
		if ( empty( $_GET['inkbound_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$msg = sanitize_text_field( wp_unslash( $_GET['inkbound_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="ink-notice" role="status">' . esc_html( $msg ) . '</div>';
	}
}
