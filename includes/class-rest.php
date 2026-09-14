<?php
/**
 * REST API for progress, follows, and inbox.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbound_REST {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			'inkbound/v1',
			'/progress',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_progress' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_progress' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'inkbound/v1',
			'/follow',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'toggle_follow' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'inkbound/v1',
			'/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'subscribe_email' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'inkbound/v1',
			'/notifications',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_notices' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'read_notices' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
			)
		);

		register_rest_route(
			'inkbound/v1',
			'/continue',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'continue_reading' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function save_progress( WP_REST_Request $request ) {
		$story_id   = (int) $request->get_param( 'story_id' );
		$chapter_id = (int) $request->get_param( 'chapter_id' );
		$percent    = (int) $request->get_param( 'percent' );

		if ( 'inkbound_story' !== get_post_type( $story_id ) || 'inkbound_chapter' !== get_post_type( $chapter_id ) ) {
			return new WP_Error( 'invalid', __( 'Invalid story or chapter.', 'inkbound' ), array( 'status' => 400 ) );
		}

		Inkbound_Progress::save( $story_id, $chapter_id, $percent );
		return rest_ensure_response( array( 'ok' => true ) );
	}

	public function get_progress( WP_REST_Request $request ) {
		$story_id = (int) $request->get_param( 'story_id' );
		$row      = $story_id ? Inkbound_Progress::get_for_story( $story_id ) : null;
		return rest_ensure_response( $row );
	}

	public function toggle_follow( WP_REST_Request $request ) {
		$story_id = (int) $request->get_param( 'story_id' );
		if ( 'inkbound_story' !== get_post_type( $story_id ) ) {
			return new WP_Error( 'invalid', __( 'Invalid story.', 'inkbound' ), array( 'status' => 400 ) );
		}
		$following = Inkbound_Follow::is_following( $story_id );
		if ( $following ) {
			Inkbound_Follow::unfollow( $story_id, get_current_user_id() );
		} else {
			Inkbound_Follow::subscribe( $story_id, get_current_user_id(), '', true );
		}
		return rest_ensure_response(
			array(
				'following' => ! $following,
				'count'     => Inkbound_Follow::count_for_story( $story_id ),
			)
		);
	}

	public function subscribe_email( WP_REST_Request $request ) {
		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$rate_key = 'inkbound_sub_' . md5( $ip );
		$attempts = (int) get_transient( $rate_key );
		if ( $attempts >= 5 ) {
			return new WP_Error(
				'rate_limited',
				__( 'Too many subscription attempts. Please try again later.', 'inkbound' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

		$story_id = (int) $request->get_param( 'story_id' );
		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$result   = Inkbound_Follow::subscribe( $story_id, get_current_user_id(), $email, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response(
			array(
				'status'  => $result->status,
				'message' => 'pending' === $result->status
					? __( 'Check your inbox to confirm.', 'inkbound' )
					: __( 'Subscribed. New chapters will be emailed to you.', 'inkbound' ),
			)
		);
	}

	public function list_notices() {
		$items = Inkbound_Notify::for_user( get_current_user_id() );
		$data  = array();
		foreach ( $items as $item ) {
			$chapter = get_post( $item->chapter_id );
			$story   = get_post( $item->story_id );
			$data[]  = array(
				'id'      => (int) $item->id,
				'read'    => (bool) $item->read_at,
				'created' => $item->created_at,
				'story'   => $story ? $story->post_title : '',
				'chapter' => $chapter ? $chapter->post_title : '',
				'heading' => $chapter ? inkbound_chapter_heading( $chapter ) : '',
				'url'     => $chapter ? get_permalink( $chapter ) : '',
			);
		}
		return rest_ensure_response(
			array(
				'unread' => Inkbound_Notify::unread_count( get_current_user_id() ),
				'items'  => $data,
			)
		);
	}

	public function read_notices( WP_REST_Request $request ) {
		Inkbound_Notify::mark_read( get_current_user_id(), (int) $request->get_param( 'id' ) );
		return rest_ensure_response( array( 'ok' => true ) );
	}

	public function continue_reading() {
		$rows = Inkbound_Progress::continue_list( 8 );
		$out  = array();
		foreach ( $rows as $row ) {
			$story   = get_post( $row->story_id );
			$chapter = get_post( $row->chapter_id );
			if ( ! $story || ! $chapter ) {
				continue;
			}
			$out[] = array(
				'story_id'   => (int) $story->ID,
				'story'      => $story->post_title,
				'chapter'    => $chapter->post_title,
				'heading'    => inkbound_chapter_heading( $chapter ),
				'percent'    => (int) $row->percent,
				'url'        => get_permalink( $chapter ),
				'cover'      => get_the_post_thumbnail_url( $story, 'medium' ),
			);
		}
		return rest_ensure_response( $out );
	}
}
