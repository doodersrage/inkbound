<?php
/**
 * Demo serials so the catalog is readable on a fresh install.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbound_Seed {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {}

	public static function run( bool $force = false ): void {
		Inkbound_CPT::insert_default_terms();
		$GLOBALS['inkbound_seeding'] = true;

		if ( $force ) {
			$existing = get_posts(
				array(
					'post_type'      => 'inkbound_story',
					'meta_key'       => '_inkbound_demo',
					'meta_value'     => '1',
					'posts_per_page' => -1,
					'post_status'    => 'any',
				)
			);
			foreach ( $existing as $story ) {
				wp_delete_post( $story->ID, true );
			}
		}

		$author_id = get_current_user_id() ?: 1;
		$data      = include INKB_DIR . 'demo/stories.php';

		$created = array();
		foreach ( $data as $item ) {
			$found = get_page_by_path( $item['slug'], OBJECT, 'inkbound_story' );
			if ( $found && ! $force ) {
				$created[ $item['slug'] ] = (int) $found->ID;
				continue;
			}

			$story_id = wp_insert_post(
				array(
					'post_type'    => 'inkbound_story',
					'post_status'  => 'publish',
					'post_title'   => $item['title'],
					'post_name'    => $item['slug'],
					'post_content' => $item['synopsis'],
					'post_excerpt' => $item['excerpt'],
					'post_author'  => $author_id,
					'post_date'    => $item['date'],
				),
				true
			);
			if ( is_wp_error( $story_id ) ) {
				continue;
			}

			update_post_meta( $story_id, '_inkbound_demo', '1' );
			update_post_meta( $story_id, '_inkbound_subtitle', $item['subtitle'] );
			update_post_meta( $story_id, '_inkbound_age', $item['age'] );
			update_post_meta( $story_id, '_inkbound_warnings', $item['warnings'] );
			update_post_meta( $story_id, '_inkbound_schedule', $item['schedule'] );
			update_post_meta( $story_id, '_inkbound_featured', ! empty( $item['featured'] ) ? '1' : '0' );
			wp_set_object_terms( $story_id, $item['genres'], 'inkbound_genre' );
			wp_set_object_terms( $story_id, $item['tropes'], 'inkbound_trope' );
			wp_set_object_terms( $story_id, $item['status'], 'inkbound_status' );

			self::sideload_cover( $story_id, $item['cover'] );

			$n = 1;
			foreach ( $item['chapters'] as $chapter ) {
				$date       = gmdate( 'Y-m-d H:i:s', strtotime( $item['date'] . ' +' . ( ( $n - 1 ) * 7 ) . ' days' ) );
				$chapter_id = wp_insert_post(
					array(
						'post_type'    => 'inkbound_chapter',
						'post_status'  => 'publish',
						'post_parent'  => $story_id,
						'post_title'   => $chapter['title'],
						'post_name'    => $chapter['slug'],
						'post_content' => $chapter['content'],
						'post_author'  => $author_id,
						'post_date'    => $date,
						'comment_status' => 'open',
						'menu_order'   => $n * 100,
					),
					true
				);
				if ( is_wp_error( $chapter_id ) ) {
					continue;
				}
				update_post_meta( $chapter_id, '_inkbound_number', (string) ( $chapter['number'] ?? $n ) );
				update_post_meta( $chapter_id, '_inkbound_label', $chapter['label'] ?? '' );
				update_post_meta( $chapter_id, '_inkbound_author_note', $chapter['note'] ?? '' );
				update_post_meta( $chapter_id, '_inkbound_notify', '1' );
				update_post_meta( $chapter_id, '_inkbound_word_count', inkbound_count_words( $chapter['content'] ) );
				++$n;
			}

			inkbound_recalculate_story( $story_id );
			$created[ $item['slug'] ] = $story_id;
		}

		self::seed_reader( $created );
		$GLOBALS['inkbound_seeding'] = false;
		flush_rewrite_rules( false );
	}

	private static function sideload_cover( int $story_id, string $filename ): void {
		$path = INKB_DIR . 'assets/covers/' . $filename;
		if ( ! file_exists( $path ) ) {
			return;
		}
		if ( get_post_thumbnail_id( $story_id ) ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = wp_tempnam( $filename );
		copy( $path, $tmp );
		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		);
		$id = media_handle_sideload( $file_array, $story_id );
		if ( ! is_wp_error( $id ) ) {
			set_post_thumbnail( $story_id, $id );
		} elseif ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}
	}

	private static function seed_reader( array $created ): void {
		if ( empty( $created['the-gilded-deep'] ) ) {
			return;
		}

		$user = get_user_by( 'login', 'reader' );
		if ( ! $user ) {
			$id = wp_create_user( 'reader', 'reader-demo', 'reader@example.test' );
			if ( ! is_wp_error( $id ) ) {
				$user = get_user_by( 'id', $id );
				$user->set_role( 'subscriber' );
				wp_update_user(
					array(
						'ID'           => $id,
						'display_name' => 'Rowan Ellis',
					)
				);
			}
		}
		if ( ! $user ) {
			return;
		}

		$story_id = (int) $created['the-gilded-deep'];
		Inkbound_Follow::subscribe( $story_id, (int) $user->ID, $user->user_email, true );

		if ( ! empty( $created['signal-hollow'] ) ) {
			Inkbound_Follow::subscribe( (int) $created['signal-hollow'], (int) $user->ID, $user->user_email, true );
		}

		$chapters = inkbound_story_chapters( $story_id );
		if ( isset( $chapters[1] ) ) {
			Inkbound_Progress::save( $story_id, (int) $chapters[1]->ID, 62, (int) $user->ID, '' );
		}
		if ( isset( $chapters[3] ) ) {
			global $wpdb;
			$wpdb->insert(
				Inkbound_Notify::table(),
				array(
					'user_id'     => (int) $user->ID,
					'story_id'    => $story_id,
					'chapter_id'  => (int) $chapters[3]->ID,
					'notice_type' => 'new_chapter',
					'read_at'     => null,
					'created_at'  => current_time( 'mysql' ),
				)
			);
			$sub = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE user_id = %d AND story_id = %d LIMIT 1',
					Inkbound_Follow::table(),
					$user->ID,
					$story_id
				)
			);
			if ( $sub ) {
				Inkbound_Mail::queue( (int) $sub->id, (int) $chapters[3]->ID );
				Inkbound_Mail::process_queue( 5 );
			}
		}
	}
}
