<?php
/**
 * Shared footer.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="ink-foot">
	<p><?php esc_html_e( 'Inkbound keeps serials on your own WordPress — chapters, follows, progress, and update mail.', 'inkbound' ); ?></p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
