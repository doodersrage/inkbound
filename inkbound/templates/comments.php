<?php
/**
 * Minimal comments on chapters.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<div class="ink-comments-inner">
	<h2><?php esc_html_e( 'Chapter discussion', 'inkbound' ); ?></h2>
	<?php if ( have_comments() ) : ?>
		<ol class="ink-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size'=> 40,
				)
			);
			?>
		</ol>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'title_reply'          => __( 'Leave a note', 'inkbound' ),
			'label_submit'         => __( 'Post comment', 'inkbound' ),
			'comment_notes_before' => '',
		)
	);
	?>
</div>
