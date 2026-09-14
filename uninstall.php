<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Inkbound
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'inkbound_options' );
delete_option( 'inkbound_mail_log' );
delete_option( 'inkbound_db_version' );

$inkbound_tables = array(
	$wpdb->prefix . 'inkbound_subs',
	$wpdb->prefix . 'inkbound_progress',
	$wpdb->prefix . 'inkbound_notices',
	$wpdb->prefix . 'inkbound_mailq',
);

foreach ( $inkbound_tables as $inkbound_table ) {
	// Table names cannot be parameterized historically; %i is supported since WP 6.2.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $inkbound_table ) );
}

$inkbound_meta_keys = array(
	'_inkbound_number',
	'_inkbound_label',
	'_inkbound_author_note',
	'_inkbound_word_count',
	'_inkbound_view_count',
	'_inkbound_subtitle',
	'_inkbound_age',
	'_inkbound_warnings',
	'_inkbound_schedule',
	'_inkbound_featured',
	'_inkbound_notify',
	'_inkbound_chapter_count',
	'_inkbound_last_chapter_id',
	'_inkbound_last_chapter_at',
	'_inkbound_follower_count',
);

foreach ( $inkbound_meta_keys as $inkbound_meta_key ) {
	delete_post_meta_by_key( $inkbound_meta_key );
}

delete_metadata( 'user', 0, '_inkbound_reader_id', '', true );
