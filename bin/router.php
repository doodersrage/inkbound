<?php
/**
 * Router for PHP's built-in server so WordPress permalinks work.
 */
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
$file = $root . $path;
if ( $path !== '/' && $path !== '' && file_exists( $file ) && ! is_dir( $file ) ) {
	return false;
}
require $root . '/index.php';
