<?php
/**
 * WP-CLI: wp inkbound seed
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbound_CLI {
	/**
	 * Load the bundled demo serials.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Delete existing demo stories first.
	 *
	 * @when after_wp_load
	 */
	public function seed( $args, $assoc_args ): void {
		unset( $args );
		Inkbound_Seed::run( ! empty( $assoc_args['force'] ) );
		WP_CLI::success( 'Demo serials loaded.' );
	}

	/**
	 * Recalculate story word counts and follower totals.
	 *
	 * @when after_wp_load
	 */
	public function recount(): void {
		$stories = get_posts(
			array(
				'post_type'      => 'inkbound_story',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);
		foreach ( $stories as $story ) {
			inkbound_recalculate_story( (int) $story->ID );
		}
		WP_CLI::success( sprintf( 'Recounted %d stories.', count( $stories ) ) );
	}

	/**
	 * Send queued chapter emails now.
	 *
	 * @when after_wp_load
	 */
	public function mail(): void {
		Inkbound_Mail::process_queue( 100 );
		WP_CLI::success( 'Mail queue processed.' );
	}
}
