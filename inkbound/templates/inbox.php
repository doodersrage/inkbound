<?php
/**
 * On-site update inbox.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( inkbound_url( 'inbox' ) ) );
	exit;
}

$user_id = get_current_user_id();
if ( isset( $_GET['inkbound_read_all'] ) && check_admin_referer( 'inkbound_read_all' ) ) {
	Inkbound_Notify::mark_read( $user_id );
	wp_safe_redirect( inkbound_url( 'inbox' ) );
	exit;
}

$notices = Inkbound_Notify::for_user( $user_id );
$unread  = Inkbound_Notify::unread_count( $user_id );

require INKB_DIR . 'templates/parts/header.php';
?>
<section class="ink-hero ink-hero--compact">
	<p class="ink-kicker"><?php esc_html_e( 'Notifications', 'inkbound' ); ?></p>
	<h1><?php esc_html_e( 'Updates', 'inkbound' ); ?></h1>
	<p class="ink-lede"><?php esc_html_e( 'When a followed serial publishes a chapter, it lands here. Email goes out in parallel if you opted in.', 'inkbound' ); ?></p>
	<?php if ( $unread ) : ?>
		<p><a class="ink-btn ink-btn--ghost" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'inkbound_read_all', '1' ), 'inkbound_read_all' ) ); ?>"><?php esc_html_e( 'Mark all read', 'inkbound' ); ?></a></p>
	<?php endif; ?>
</section>

<?php if ( ! $notices ) : ?>
	<div class="ink-empty">
		<h2><?php esc_html_e( 'No chapter alerts yet.', 'inkbound' ); ?></h2>
		<p><?php esc_html_e( 'Follow a story, then wait for the next publish — or load the demo serials from the Writing desk to see a sample update.', 'inkbound' ); ?></p>
	</div>
<?php else : ?>
	<ul class="ink-inbox">
		<?php foreach ( $notices as $item ) : ?>
			<?php
			$story   = get_post( $item->story_id );
			$chapter = get_post( $item->chapter_id );
			if ( ! $chapter ) {
				continue;
			}
			?>
			<li class="<?php echo $item->read_at ? 'is-read' : 'is-unread'; ?>">
				<a href="<?php echo esc_url( get_permalink( $chapter ) ); ?>">
					<span class="ink-kicker"><?php echo $story ? esc_html( $story->post_title ) : ''; ?></span>
					<strong><?php echo esc_html( inkbound_chapter_heading( $chapter ) . ' — ' . $chapter->post_title ); ?></strong>
					<em><?php echo esc_html( $item->created_at ); ?></em>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<?php require INKB_DIR . 'templates/parts/footer.php'; ?>
