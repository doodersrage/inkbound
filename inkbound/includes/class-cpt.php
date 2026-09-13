<?php
/**
 * Stories, chapters, taxonomies, permalinks, stats.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbound_CPT {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'save_post_inkbound_chapter', array( $this, 'save_chapter_meta' ), 10, 2 );
		add_action( 'save_post_inkbound_story', array( $this, 'save_story_meta' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'before_delete' ) );
		add_filter( 'post_type_link', array( $this, 'chapter_permalink' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'story_permalink' ), 10, 2 );
	}

	public static function register(): void {
		register_post_type(
			'inkbound_story',
			array(
				'labels'          => array(
					'name'          => __( 'Stories', 'inkbound' ),
					'singular_name' => __( 'Story', 'inkbound' ),
					'add_new'       => __( 'Add Story', 'inkbound' ),
					'add_new_item'  => __( 'Add New Story', 'inkbound' ),
					'edit_item'     => __( 'Edit Story', 'inkbound' ),
					'new_item'      => __( 'New Story', 'inkbound' ),
					'view_item'     => __( 'View Story', 'inkbound' ),
					'search_items'  => __( 'Search Stories', 'inkbound' ),
					'not_found'     => __( 'No stories found.', 'inkbound' ),
					'menu_name'     => __( 'Stories', 'inkbound' ),
					'all_items'     => __( 'All Stories', 'inkbound' ),
				),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => 'inkbound',
				'show_in_rest'    => true,
				'has_archive'     => 'stories',
				'rewrite'         => array(
					'slug'       => 'stories',
					'with_front' => false,
				),
				'menu_icon'       => 'dashicons-book-alt',
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'inkbound_chapter',
			array(
				'labels'          => array(
					'name'          => __( 'Chapters', 'inkbound' ),
					'singular_name' => __( 'Chapter', 'inkbound' ),
					'add_new'       => __( 'Add Chapter', 'inkbound' ),
					'add_new_item'  => __( 'Add New Chapter', 'inkbound' ),
					'edit_item'     => __( 'Edit Chapter', 'inkbound' ),
					'all_items'     => __( 'All Chapters', 'inkbound' ),
				),
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => 'inkbound',
				'show_in_rest'    => true,
				'has_archive'     => false,
				'rewrite'         => false,
				'supports'        => array( 'title', 'editor', 'author', 'revisions', 'comments' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

		register_taxonomy(
			'inkbound_genre',
			array( 'inkbound_story' ),
			array(
				'labels'            => array(
					'name'          => __( 'Genres', 'inkbound' ),
					'singular_name' => __( 'Genre', 'inkbound' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'genre',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'inkbound_trope',
			array( 'inkbound_story' ),
			array(
				'labels'            => array(
					'name'          => __( 'Tropes', 'inkbound' ),
					'singular_name' => __( 'Trope', 'inkbound' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'trope',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'inkbound_status',
			array( 'inkbound_story' ),
			array(
				'labels'            => array(
					'name'          => __( 'Publication status', 'inkbound' ),
					'singular_name' => __( 'Status', 'inkbound' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'status',
					'with_front' => false,
				),
			)
		);
	}

	public static function insert_default_terms(): void {
		$genres = array(
			'Fantasy',
			'Science Fiction',
			'Romance',
			'Mystery',
			'Horror',
			'Historical',
			'LitRPG',
			'Progression',
			'Adventure',
			'Contemporary',
		);
		foreach ( $genres as $genre ) {
			if ( ! term_exists( $genre, 'inkbound_genre' ) ) {
				wp_insert_term( $genre, 'inkbound_genre' );
			}
		}
		foreach ( array_keys( inkbound_statuses() ) as $status ) {
			if ( ! term_exists( $status, 'inkbound_status' ) ) {
				wp_insert_term( inkbound_statuses()[ $status ], 'inkbound_status', array( 'slug' => $status ) );
			}
		}
	}

	public function save_story_meta( int $post_id, WP_Post $post ): void {
		unset( $post );
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['inkbound_story_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['inkbound_story_nonce'] ) ), 'inkbound_save_story' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$subtitle = sanitize_text_field( wp_unslash( $_POST['inkbound_subtitle'] ?? '' ) );
		$age      = sanitize_key( wp_unslash( $_POST['inkbound_age'] ?? 'teen' ) );
		$warnings = sanitize_text_field( wp_unslash( $_POST['inkbound_warnings'] ?? '' ) );
		$schedule = sanitize_text_field( wp_unslash( $_POST['inkbound_schedule'] ?? '' ) );
		$featured = empty( $_POST['inkbound_featured'] ) ? '0' : '1';

		update_post_meta( $post_id, '_inkbound_subtitle', $subtitle );
		update_post_meta( $post_id, '_inkbound_age', isset( inkbound_ages()[ $age ] ) ? $age : 'teen' );
		update_post_meta( $post_id, '_inkbound_warnings', $warnings );
		update_post_meta( $post_id, '_inkbound_schedule', $schedule );
		update_post_meta( $post_id, '_inkbound_featured', $featured );

		if ( isset( $_POST['inkbound_status'] ) ) {
			$status = sanitize_key( wp_unslash( $_POST['inkbound_status'] ) );
			if ( isset( inkbound_statuses()[ $status ] ) ) {
				wp_set_object_terms( $post_id, $status, 'inkbound_status', false );
			}
		}

		inkbound_recalculate_story( $post_id );
	}

	public function save_chapter_meta( int $post_id, WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$from_form = isset( $_POST['inkbound_chapter_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['inkbound_chapter_nonce'] ) ), 'inkbound_save_chapter' );

		if ( $from_form ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			$story_id = (int) ( $_POST['inkbound_story_id'] ?? 0 );
			$number   = sanitize_text_field( wp_unslash( $_POST['inkbound_number'] ?? '' ) );
			$label    = sanitize_text_field( wp_unslash( $_POST['inkbound_label'] ?? '' ) );
			$note     = wp_kses_post( wp_unslash( $_POST['inkbound_author_note'] ?? '' ) );
			$notify   = empty( $_POST['inkbound_notify'] ) ? '0' : '1';

			if ( $story_id && 'inkbound_story' === get_post_type( $story_id ) && (int) $post->post_parent !== $story_id ) {
				remove_action( 'save_post_inkbound_chapter', array( $this, 'save_chapter_meta' ), 10 );
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_parent' => $story_id,
					)
				);
				add_action( 'save_post_inkbound_chapter', array( $this, 'save_chapter_meta' ), 10, 2 );
			}

			if ( '' === $number && $story_id ) {
				$number = inkbound_next_number( $story_id );
			}

			update_post_meta( $post_id, '_inkbound_number', $number );
			update_post_meta( $post_id, '_inkbound_label', $label );
			update_post_meta( $post_id, '_inkbound_author_note', $note );
			update_post_meta( $post_id, '_inkbound_notify', $notify );

			remove_action( 'save_post_inkbound_chapter', array( $this, 'save_chapter_meta' ), 10 );
			wp_update_post(
				array(
					'ID'         => $post_id,
					'menu_order' => (int) round( ( (float) $number ) * 100 ),
				)
			);
			add_action( 'save_post_inkbound_chapter', array( $this, 'save_chapter_meta' ), 10, 2 );
		}

		$fresh = get_post( $post_id );
		$words = inkbound_count_words( (string) ( $fresh->post_content ?? $post->post_content ) );
		update_post_meta( $post_id, '_inkbound_word_count', $words );

		$parent = (int) ( $fresh->post_parent ?? $post->post_parent );
		if ( $parent ) {
			inkbound_recalculate_story( $parent );
		}
	}

	public function before_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}
		if ( 'inkbound_chapter' === $post->post_type && $post->post_parent ) {
			$parent = (int) $post->post_parent;
			add_action(
				'deleted_post',
				static function ( $deleted ) use ( $parent, $post_id ) {
					if ( (int) $deleted === (int) $post_id ) {
						inkbound_recalculate_story( $parent );
					}
				}
			);
		}
		if ( 'inkbound_story' === $post->post_type ) {
			$chapters = inkbound_story_chapters( $post_id, 'any' );
			foreach ( $chapters as $chapter ) {
				wp_delete_post( $chapter->ID, true );
			}
		}
	}

	public function story_permalink( string $permalink, WP_Post $post ): string {
		if ( 'inkbound_story' !== $post->post_type || empty( $post->post_name ) ) {
			return $permalink;
		}
		return home_url( user_trailingslashit( 'stories/' . $post->post_name ) );
	}

	public function chapter_permalink( string $permalink, WP_Post $post ): string {
		if ( 'inkbound_chapter' !== $post->post_type ) {
			return $permalink;
		}
		$story = inkbound_chapter_story( $post );
		if ( ! $story ) {
			return $permalink;
		}
		return home_url( user_trailingslashit( 'stories/' . $story->post_name . '/' . $post->post_name ) );
	}
}
