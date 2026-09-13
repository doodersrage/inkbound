<?php
/**
 * Plugin Name:       Inkbound
 * Plugin URI:        https://github.com/doodersrage/inkbound
 * Description:       Serialized fiction and web-novel tools for WordPress — stories, chapter management, reader subscriptions, update mail, and reading progress.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Robert McDowell
 * Author URI:        https://github.com/doodersrage/inkbound
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       inkbound
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INKB_VERSION', '1.0.0' );
define( 'INKB_FILE', __FILE__ );
define( 'INKB_DIR', plugin_dir_path( __FILE__ ) );
define( 'INKB_URL', plugin_dir_url( __FILE__ ) );

require_once INKB_DIR . 'includes/helpers.php';
require_once INKB_DIR . 'includes/class-cpt.php';
require_once INKB_DIR . 'includes/class-follow.php';
require_once INKB_DIR . 'includes/class-progress.php';
require_once INKB_DIR . 'includes/class-notify.php';
require_once INKB_DIR . 'includes/class-mail.php';
require_once INKB_DIR . 'includes/class-rest.php';
require_once INKB_DIR . 'includes/class-admin.php';
require_once INKB_DIR . 'includes/class-frontend.php';
require_once INKB_DIR . 'includes/class-seed.php';
require_once INKB_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Inkbound_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Inkbound_Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		Inkbound_Plugin::instance()->boot();
	}
);
