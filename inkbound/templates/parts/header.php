<?php
/**
 * Shared document chrome for Inkbound front-end pages.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$unread = is_user_logged_in() ? Inkbound_Notify::unread_count( get_current_user_id() ) : 0;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="ink-skip" href="#ink-main"><?php esc_html_e( 'Skip to content', 'inkbound' ); ?></a>
<header class="ink-top">
	<div class="ink-top__inner">
		<a class="ink-wordmark" href="<?php echo esc_url( inkbound_url() ); ?>">Inkbound</a>
		<nav class="ink-nav" aria-label="<?php esc_attr_e( 'Serial', 'inkbound' ); ?>">
			<a href="<?php echo esc_url( inkbound_url() ); ?>"><?php esc_html_e( 'Catalog', 'inkbound' ); ?></a>
			<a href="<?php echo esc_url( inkbound_url( 'library' ) ); ?>"><?php esc_html_e( 'Library', 'inkbound' ); ?></a>
			<?php if ( is_user_logged_in() ) : ?>
				<a class="ink-inbox-link" href="<?php echo esc_url( inkbound_url( 'inbox' ) ); ?>">
					<?php esc_html_e( 'Updates', 'inkbound' ); ?>
					<?php if ( $unread ) : ?><span class="ink-badge"><?php echo esc_html( (string) $unread ); ?></span><?php endif; ?>
				</a>
			<?php endif; ?>
		</nav>
		<div class="ink-top__account">
			<?php if ( is_user_logged_in() ) : ?>
				<?php if ( current_user_can( 'edit_posts' ) ) : ?>
					<a class="ink-quiet" href="<?php echo esc_url( admin_url( 'admin.php?page=inkbound' ) ); ?>"><?php esc_html_e( 'Writing desk', 'inkbound' ); ?></a>
				<?php endif; ?>
				<a class="ink-quiet" href="<?php echo esc_url( wp_logout_url( inkbound_url() ) ); ?>"><?php esc_html_e( 'Sign out', 'inkbound' ); ?></a>
			<?php else : ?>
				<a class="ink-btn ink-btn--ghost" href="<?php echo esc_url( wp_login_url( inkbound_url() ) ); ?>"><?php esc_html_e( 'Sign in', 'inkbound' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</header>
<main id="ink-main" class="ink-main">
<?php Inkbound_Frontend::notice(); ?>
