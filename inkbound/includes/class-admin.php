<?php
/**
 * wp-admin: dashboard, metaboxes, settings, mail log, subscribers.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Inkbound_Admin {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'menus' ) );
		add_action( 'add_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'manage_inkbound_story_posts_columns', array( $this, 'story_columns' ) );
		add_action( 'manage_inkbound_story_posts_custom_column', array( $this, 'story_column' ), 10, 2 );
		add_filter( 'manage_inkbound_chapter_posts_columns', array( $this, 'chapter_columns' ) );
		add_action( 'manage_inkbound_chapter_posts_custom_column', array( $this, 'chapter_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'chapter_story_filter' ) );
		add_filter( 'parse_query', array( $this, 'filter_chapters_by_story' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ) );
		add_action( 'admin_notices', array( $this, 'seed_notice' ) );
		add_filter( 'parent_file', array( $this, 'parent_file' ) );
	}

	public function parent_file( $parent ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && in_array( $screen->taxonomy, array( 'inkbound_genre', 'inkbound_trope', 'inkbound_status' ), true ) ) {
			return 'inkbound';
		}
		return $parent;
	}

	public function menus(): void {
		add_menu_page(
			__( 'Inkbound', 'inkbound' ),
			__( 'Inkbound', 'inkbound' ),
			'edit_posts',
			'inkbound',
			array( $this, 'render_dashboard' ),
			'dashicons-book-alt',
			25
		);
		add_submenu_page( 'inkbound', __( 'Overview', 'inkbound' ), __( 'Overview', 'inkbound' ), 'edit_posts', 'inkbound', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'inkbound', __( 'Stories', 'inkbound' ), __( 'Stories', 'inkbound' ), 'edit_posts', 'edit.php?post_type=inkbound_story' );
		add_submenu_page( 'inkbound', __( 'Chapters', 'inkbound' ), __( 'Chapters', 'inkbound' ), 'edit_posts', 'edit.php?post_type=inkbound_chapter' );
		add_submenu_page( 'inkbound', __( 'Genres', 'inkbound' ), __( 'Genres', 'inkbound' ), 'manage_categories', 'edit-tags.php?taxonomy=inkbound_genre&post_type=inkbound_story' );
		add_submenu_page( 'inkbound', __( 'Tropes', 'inkbound' ), __( 'Tropes', 'inkbound' ), 'manage_categories', 'edit-tags.php?taxonomy=inkbound_trope&post_type=inkbound_story' );
		add_submenu_page( 'inkbound', __( 'Subscribers', 'inkbound' ), __( 'Subscribers', 'inkbound' ), 'edit_posts', 'inkbound-subs', array( $this, 'render_subs' ) );
		add_submenu_page( 'inkbound', __( 'Update mail', 'inkbound' ), __( 'Update mail', 'inkbound' ), 'manage_options', 'inkbound-mail', array( $this, 'render_mail' ) );
		add_submenu_page( 'inkbound', __( 'Settings', 'inkbound' ), __( 'Settings', 'inkbound' ), 'manage_options', 'inkbound-settings', array( $this, 'render_settings' ) );
	}

	public function assets( string $hook ): void {
		$screen = get_current_screen();
		$use    = $screen && ( 0 === strpos( (string) $screen->id, 'inkbound' ) || in_array( $screen->post_type, array( 'inkbound_story', 'inkbound_chapter' ), true ) );
		if ( ! $use && false === strpos( $hook, 'inkbound' ) ) {
			return;
		}
		wp_enqueue_style( 'inkbound-admin', INKB_URL . 'admin/css/admin.css', array(), INKB_VERSION );
		wp_enqueue_script( 'inkbound-admin', INKB_URL . 'admin/js/admin.js', array( 'jquery' ), INKB_VERSION, true );
	}

	public function metaboxes(): void {
		add_meta_box( 'inkbound-story-meta', __( 'Serial details', 'inkbound' ), array( $this, 'story_metabox' ), 'inkbound_story', 'side', 'high' );
		add_meta_box( 'inkbound-story-chapters', __( 'Chapters', 'inkbound' ), array( $this, 'story_chapters_metabox' ), 'inkbound_story', 'normal', 'high' );
		add_meta_box( 'inkbound-chapter-meta', __( 'Chapter details', 'inkbound' ), array( $this, 'chapter_metabox' ), 'inkbound_chapter', 'side', 'high' );
	}

	public function story_metabox( WP_Post $post ): void {
		wp_nonce_field( 'inkbound_save_story', 'inkbound_story_nonce' );
		$subtitle = (string) get_post_meta( $post->ID, '_inkbound_subtitle', true );
		$age      = (string) get_post_meta( $post->ID, '_inkbound_age', true ) ?: 'teen';
		$warnings = (string) get_post_meta( $post->ID, '_inkbound_warnings', true );
		$schedule = (string) get_post_meta( $post->ID, '_inkbound_schedule', true );
		$featured = (string) get_post_meta( $post->ID, '_inkbound_featured', true );
		$terms    = wp_get_object_terms( $post->ID, 'inkbound_status', array( 'fields' => 'slugs' ) );
		$status   = $terms ? $terms[0] : 'ongoing';
		?>
		<p>
			<label for="inkbound_subtitle"><strong><?php esc_html_e( 'Subtitle', 'inkbound' ); ?></strong></label>
			<input type="text" class="widefat" id="inkbound_subtitle" name="inkbound_subtitle" value="<?php echo esc_attr( $subtitle ); ?>">
		</p>
		<p>
			<label for="inkbound_status"><strong><?php esc_html_e( 'Status', 'inkbound' ); ?></strong></label>
			<select class="widefat" id="inkbound_status" name="inkbound_status">
				<?php foreach ( inkbound_statuses() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="inkbound_age"><strong><?php esc_html_e( 'Age rating', 'inkbound' ); ?></strong></label>
			<select class="widefat" id="inkbound_age" name="inkbound_age">
				<?php foreach ( inkbound_ages() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $age, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="inkbound_schedule"><strong><?php esc_html_e( 'Update schedule', 'inkbound' ); ?></strong></label>
			<input type="text" class="widefat" id="inkbound_schedule" name="inkbound_schedule" value="<?php echo esc_attr( $schedule ); ?>" placeholder="<?php esc_attr_e( 'Tuesdays', 'inkbound' ); ?>">
		</p>
		<p>
			<label for="inkbound_warnings"><strong><?php esc_html_e( 'Content warnings', 'inkbound' ); ?></strong></label>
			<input type="text" class="widefat" id="inkbound_warnings" name="inkbound_warnings" value="<?php echo esc_attr( $warnings ); ?>" placeholder="<?php esc_attr_e( 'grief, drowning', 'inkbound' ); ?>">
			<span class="description"><?php esc_html_e( 'Comma-separated, shown on the story page.', 'inkbound' ); ?></span>
		</p>
		<p>
			<label><input type="checkbox" name="inkbound_featured" value="1" <?php checked( $featured, '1' ); ?>> <?php esc_html_e( 'Feature on the catalog', 'inkbound' ); ?></label>
		</p>
		<?php
	}

	public function story_chapters_metabox( WP_Post $post ): void {
		$chapters = inkbound_story_chapters( (int) $post->ID, 'any' );
		$add_url  = admin_url( 'post-new.php?post_type=inkbound_chapter&story_id=' . (int) $post->ID );
		$subs     = Inkbound_Follow::count_for_story( (int) $post->ID );
		echo '<p class="inkbound-admin-lede">';
		echo esc_html( sprintf(
			/* translators: 1: chapter count, 2: follower count */
			_n( '%1$d chapter · %2$d follower', '%1$d chapters · %2$d followers', count( $chapters ), 'inkbound' ),
			count( $chapters ),
			$subs
		) );
		echo ' · <a class="button button-small" href="' . esc_url( $add_url ) . '">' . esc_html__( 'Add next chapter', 'inkbound' ) . '</a></p>';

		if ( ! $chapters ) {
			echo '<p>' . esc_html__( 'No chapters yet. Add one to start the serial.', 'inkbound' ) . '</p>';
			return;
		}
		echo '<table class="widefat striped inkbound-chapter-table"><thead><tr>';
		echo '<th>' . esc_html__( '#', 'inkbound' ) . '</th>';
		echo '<th>' . esc_html__( 'Title', 'inkbound' ) . '</th>';
		echo '<th>' . esc_html__( 'Words', 'inkbound' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'inkbound' ) . '</th>';
		echo '<th>' . esc_html__( 'Published', 'inkbound' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $chapters as $chapter ) {
			$edit = get_edit_post_link( $chapter->ID );
			echo '<tr>';
			echo '<td>' . esc_html( inkbound_chapter_heading( $chapter ) ) . '</td>';
			echo '<td><a href="' . esc_url( $edit ) . '">' . esc_html( $chapter->post_title ) . '</a></td>';
			echo '<td>' . esc_html( number_format_i18n( (int) get_post_meta( $chapter->ID, '_inkbound_word_count', true ) ) ) . '</td>';
			echo '<td>' . esc_html( get_post_status_object( $chapter->post_status )->label ?? $chapter->post_status ) . '</td>';
			echo '<td>' . esc_html( get_the_date( '', $chapter ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	public function chapter_metabox( WP_Post $post ): void {
		wp_nonce_field( 'inkbound_save_chapter', 'inkbound_chapter_nonce' );
		$story_id = (int) $post->post_parent;
		if ( ! $story_id && isset( $_GET['story_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$story_id = (int) $_GET['story_id'];
		}
		$number = (string) get_post_meta( $post->ID, '_inkbound_number', true );
		$label  = (string) get_post_meta( $post->ID, '_inkbound_label', true );
		$note   = (string) get_post_meta( $post->ID, '_inkbound_author_note', true );
		$notify = get_post_meta( $post->ID, '_inkbound_notify', true );
		if ( '' === $notify ) {
			$notify = '1';
		}
		if ( '' === $number && $story_id ) {
			$number = inkbound_next_number( $story_id );
		}
		$stories = get_posts(
			array(
				'post_type'      => 'inkbound_story',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$followers = $story_id ? Inkbound_Follow::count_for_story( $story_id ) : 0;
		?>
		<p>
			<label for="inkbound_story_id"><strong><?php esc_html_e( 'Story', 'inkbound' ); ?></strong></label>
			<select class="widefat" id="inkbound_story_id" name="inkbound_story_id" required>
				<option value=""><?php esc_html_e( 'Select a story…', 'inkbound' ); ?></option>
				<?php foreach ( $stories as $story ) : ?>
					<option value="<?php echo (int) $story->ID; ?>" <?php selected( $story_id, $story->ID ); ?>><?php echo esc_html( $story->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="inkbound_number"><strong><?php esc_html_e( 'Chapter number', 'inkbound' ); ?></strong></label>
			<input type="text" class="widefat" id="inkbound_number" name="inkbound_number" value="<?php echo esc_attr( $number ); ?>" placeholder="1">
			<span class="description"><?php esc_html_e( 'Use 0 for a prologue, or 12.5 for an interlude.', 'inkbound' ); ?></span>
		</p>
		<p>
			<label for="inkbound_label"><strong><?php esc_html_e( 'Label override', 'inkbound' ); ?></strong></label>
			<input type="text" class="widefat" id="inkbound_label" name="inkbound_label" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php esc_attr_e( 'Prologue, Interlude…', 'inkbound' ); ?>">
		</p>
		<p>
			<label for="inkbound_author_note"><strong><?php esc_html_e( 'Author\'s note', 'inkbound' ); ?></strong></label>
			<textarea class="widefat" rows="4" id="inkbound_author_note" name="inkbound_author_note"><?php echo esc_textarea( $note ); ?></textarea>
		</p>
		<p>
			<label><input type="checkbox" name="inkbound_notify" value="1" <?php checked( $notify, '1' ); ?>>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: follower count */
						__( 'Email and notify %d subscribers when this chapter is published', 'inkbound' ),
						$followers
					)
				);
				?>
			</label>
		</p>
		<?php
	}

	public function story_columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['inkbound_status']    = __( 'Status', 'inkbound' );
				$out['inkbound_chapters']  = __( 'Chapters', 'inkbound' );
				$out['inkbound_words']     = __( 'Words', 'inkbound' );
				$out['inkbound_followers'] = __( 'Followers', 'inkbound' );
			}
		}
		return $out;
	}

	public function story_column( string $column, int $post_id ): void {
		if ( 'inkbound_status' === $column ) {
			$terms = get_the_terms( $post_id, 'inkbound_status' );
			echo $terms && ! is_wp_error( $terms ) ? esc_html( $terms[0]->name ) : '—';
		}
		if ( 'inkbound_chapters' === $column ) {
			echo esc_html( (string) (int) get_post_meta( $post_id, '_inkbound_chapter_count', true ) );
		}
		if ( 'inkbound_words' === $column ) {
			echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_inkbound_word_count', true ) ) );
		}
		if ( 'inkbound_followers' === $column ) {
			echo esc_html( (string) (int) get_post_meta( $post_id, '_inkbound_follower_count', true ) );
		}
	}

	public function chapter_columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['inkbound_story']  = __( 'Story', 'inkbound' );
				$out['inkbound_number'] = __( 'No.', 'inkbound' );
				$out['inkbound_words']  = __( 'Words', 'inkbound' );
			}
		}
		return $out;
	}

	public function chapter_column( string $column, int $post_id ): void {
		if ( 'inkbound_story' === $column ) {
			$story = inkbound_chapter_story( $post_id );
			echo $story ? '<a href="' . esc_url( get_edit_post_link( $story ) ) . '">' . esc_html( $story->post_title ) . '</a>' : '—';
		}
		if ( 'inkbound_number' === $column ) {
			echo esc_html( inkbound_chapter_heading( $post_id ) );
		}
		if ( 'inkbound_words' === $column ) {
			echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, '_inkbound_word_count', true ) ) );
		}
	}

	public function chapter_story_filter( string $post_type ): void {
		if ( 'inkbound_chapter' !== $post_type ) {
			return;
		}
		$selected = isset( $_GET['inkbound_story_filter'] ) ? (int) $_GET['inkbound_story_filter'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$stories  = get_posts(
			array(
				'post_type'      => 'inkbound_story',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'any',
			)
		);
		echo '<select name="inkbound_story_filter">';
		echo '<option value="0">' . esc_html__( 'All stories', 'inkbound' ) . '</option>';
		foreach ( $stories as $story ) {
			printf(
				'<option value="%d" %s>%s</option>',
				(int) $story->ID,
				selected( $selected, $story->ID, false ),
				esc_html( $story->post_title )
			);
		}
		echo '</select>';
	}

	public function filter_chapters_by_story( WP_Query $query ): void {
		global $pagenow;
		if ( ! is_admin() || 'edit.php' !== $pagenow || 'inkbound_chapter' !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( ! empty( $_GET['inkbound_story_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query->set( 'post_parent', (int) $_GET['inkbound_story_filter'] );
		}
	}

	public function render_dashboard(): void {
		$stories  = wp_count_posts( 'inkbound_story' );
		$chapters = wp_count_posts( 'inkbound_chapter' );
		global $wpdb;
		$subs    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Inkbound_Follow::table() . " WHERE status = 'active'" );
		$queued  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Inkbound_Mail::queue_table() . " WHERE status = 'queued'" );
		$recent  = get_posts(
			array(
				'post_type'      => 'inkbound_chapter',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="wrap inkbound-wrap">
			<h1><?php esc_html_e( 'Inkbound', 'inkbound' ); ?></h1>
			<p class="inkbound-admin-lede"><?php esc_html_e( 'Stories, chapters, follows, and chapter-update mail — the serial toolkit WordPress does not ship.', 'inkbound' ); ?></p>
			<div class="inkbound-stats">
				<div><strong><?php echo esc_html( (string) ( (int) $stories->publish ) ); ?></strong><span><?php esc_html_e( 'Published stories', 'inkbound' ); ?></span></div>
				<div><strong><?php echo esc_html( (string) ( (int) $chapters->publish ) ); ?></strong><span><?php esc_html_e( 'Published chapters', 'inkbound' ); ?></span></div>
				<div><strong><?php echo esc_html( (string) $subs ); ?></strong><span><?php esc_html_e( 'Active subscriptions', 'inkbound' ); ?></span></div>
				<div><strong><?php echo esc_html( (string) $queued ); ?></strong><span><?php esc_html_e( 'Queued emails', 'inkbound' ); ?></span></div>
			</div>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=inkbound_story' ) ); ?>"><?php esc_html_e( 'New story', 'inkbound' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=inkbound_chapter' ) ); ?>"><?php esc_html_e( 'New chapter', 'inkbound' ); ?></a>
				<a class="button" href="<?php echo esc_url( inkbound_url() ); ?>"><?php esc_html_e( 'View catalog', 'inkbound' ); ?></a>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=inkbound&inkbound_seed=1' ), 'inkbound_seed' ) ); ?>"><?php esc_html_e( 'Load demo serials', 'inkbound' ); ?></a>
				<?php endif; ?>
			</p>
			<h2><?php esc_html_e( 'Latest chapters', 'inkbound' ); ?></h2>
			<?php if ( ! $recent ) : ?>
				<p><?php esc_html_e( 'Nothing published yet. Create a story, then add numbered chapters. Publishing a chapter emails everyone who follows it.', 'inkbound' ); ?></p>
			<?php else : ?>
				<ul class="inkbound-recent">
					<?php foreach ( $recent as $chapter ) : ?>
						<?php $story = inkbound_chapter_story( $chapter ); ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $chapter ) ); ?>"><?php echo esc_html( inkbound_chapter_heading( $chapter ) . ' — ' . $chapter->post_title ); ?></a>
							<?php if ( $story ) : ?>
								<span><?php echo esc_html( $story->post_title ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_subs(): void {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . Inkbound_Follow::table() . ' ORDER BY created_at DESC LIMIT 200' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscribers', 'inkbound' ); ?></h1>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Story', 'inkbound' ); ?></th>
						<th><?php esc_html_e( 'Reader', 'inkbound' ); ?></th>
						<th><?php esc_html_e( 'Email', 'inkbound' ); ?></th>
						<th><?php esc_html_e( 'Status', 'inkbound' ); ?></th>
						<th><?php esc_html_e( 'Mail', 'inkbound' ); ?></th>
						<th><?php esc_html_e( 'Since', 'inkbound' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No subscriptions yet. Readers can follow from a story page or subscribe by email without an account.', 'inkbound' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$story = get_post( $row->story_id );
					$user  = $row->user_id ? get_user_by( 'id', $row->user_id ) : null;
					?>
					<tr>
						<td><?php echo $story ? esc_html( $story->post_title ) : '—'; ?></td>
						<td><?php echo $user ? esc_html( $user->display_name ) : esc_html__( 'Email only', 'inkbound' ); ?></td>
						<td><?php echo esc_html( $row->email ); ?></td>
						<td><?php echo esc_html( $row->status ); ?></td>
						<td><?php echo $row->notify_email ? esc_html__( 'On', 'inkbound' ) : esc_html__( 'Off', 'inkbound' ); ?></td>
						<td><?php echo esc_html( $row->created_at ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_mail(): void {
		if ( isset( $_GET['inkbound_flush_mail'] ) && check_admin_referer( 'inkbound_flush_mail' ) ) {
			Inkbound_Mail::process_queue( 100 );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Mail queue processed.', 'inkbound' ) . '</p></div>';
		}
		global $wpdb;
		$queue = $wpdb->get_results( 'SELECT * FROM ' . Inkbound_Mail::queue_table() . ' ORDER BY id DESC LIMIT 50' );
		$log   = Inkbound_Mail::log();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Update mail', 'inkbound' ); ?></h1>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=inkbound-mail&inkbound_flush_mail=1' ), 'inkbound_flush_mail' ) ); ?>"><?php esc_html_e( 'Send queued chapter emails now', 'inkbound' ); ?></a></p>
			<h2><?php esc_html_e( 'Queue', 'inkbound' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th>ID</th><th><?php esc_html_e( 'Chapter', 'inkbound' ); ?></th><th><?php esc_html_e( 'Status', 'inkbound' ); ?></th><th><?php esc_html_e( 'Scheduled', 'inkbound' ); ?></th><th><?php esc_html_e( 'Error', 'inkbound' ); ?></th></tr></thead>
				<tbody>
				<?php if ( ! $queue ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'Queue is empty. Publishing a chapter with notifications enabled will add a row per subscriber.', 'inkbound' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $queue as $row ) : ?>
					<?php $ch = get_post( $row->chapter_id ); ?>
					<tr>
						<td><?php echo (int) $row->id; ?></td>
						<td><?php echo $ch ? esc_html( $ch->post_title ) : (int) $row->chapter_id; ?></td>
						<td><?php echo esc_html( $row->status ); ?></td>
						<td><?php echo esc_html( $row->scheduled_at ); ?></td>
						<td><?php echo esc_html( (string) $row->error_text ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'Delivery log', 'inkbound' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Every chapter email is recorded here, even when the server has no SMTP. Use this to verify update notifications locally.', 'inkbound' ); ?></p>
			<?php foreach ( $log as $item ) : ?>
				<div class="inkbound-mail-item">
					<p><strong><?php echo esc_html( $item['subject'] ?? '' ); ?></strong><br>
					<?php echo esc_html( ( $item['at'] ?? '' ) . ' · ' . ( $item['to'] ?? '' ) . ' · ' . ( ! empty( $item['sent'] ) ? 'sent' : 'logged' ) ); ?></p>
					<pre><?php echo esc_html( $item['text'] ?? '' ); ?></pre>
				</div>
			<?php endforeach; ?>
			<?php if ( ! $log ) : ?>
				<p><?php esc_html_e( 'No mail has been generated yet.', 'inkbound' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_settings(): void {
		$opts = wp_parse_args( get_option( 'inkbound_options', array() ), inkbound_default_options() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Inkbound settings', 'inkbound' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'inkbound_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><?php esc_html_e( 'Homepage', 'inkbound' ); ?></th>
						<td><label><input type="checkbox" name="inkbound_options[replace_home]" value="1" <?php checked( ! empty( $opts['replace_home'] ) ); ?>> <?php esc_html_e( 'Use the story catalog as the site home', 'inkbound' ); ?></label></td>
					</tr>
					<tr>
						<th><label for="catalog_title"><?php esc_html_e( 'Catalog title', 'inkbound' ); ?></label></th>
						<td><input class="regular-text" id="catalog_title" name="inkbound_options[catalog_title]" value="<?php echo esc_attr( $opts['catalog_title'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="catalog_tagline"><?php esc_html_e( 'Catalog tagline', 'inkbound' ); ?></label></th>
						<td><input class="large-text" id="catalog_tagline" name="inkbound_options[catalog_tagline]" value="<?php echo esc_attr( $opts['catalog_tagline'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="from_name"><?php esc_html_e( 'From name', 'inkbound' ); ?></label></th>
						<td><input class="regular-text" id="from_name" name="inkbound_options[from_name]" value="<?php echo esc_attr( $opts['from_name'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="from_email"><?php esc_html_e( 'From email', 'inkbound' ); ?></label></th>
						<td><input class="regular-text" type="email" id="from_email" name="inkbound_options[from_email]" value="<?php echo esc_attr( $opts['from_email'] ); ?>"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Chapter emails', 'inkbound' ); ?></th>
						<td>
							<label><input type="radio" name="inkbound_options[email_mode]" value="full" <?php checked( $opts['email_mode'], 'full' ); ?>> <?php esc_html_e( 'Send the full chapter (Substack-style)', 'inkbound' ); ?></label><br>
							<label><input type="radio" name="inkbound_options[email_mode]" value="excerpt" <?php checked( $opts['email_mode'], 'excerpt' ); ?>> <?php esc_html_e( 'Send an excerpt and a link to read on-site', 'inkbound' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Email confirmation', 'inkbound' ); ?></th>
						<td><label><input type="checkbox" name="inkbound_options[confirm_subs]" value="1" <?php checked( ! empty( $opts['confirm_subs'] ) ); ?>> <?php esc_html_e( 'Require guests to confirm before mail starts (recommended in production)', 'inkbound' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Publish hook', 'inkbound' ); ?></th>
						<td><label><input type="checkbox" name="inkbound_options[notify_on_publish]" value="1" <?php checked( ! empty( $opts['notify_on_publish'] ) ); ?>> <?php esc_html_e( 'Notify followers when a chapter is published', 'inkbound' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function save_settings(): void {
		if ( empty( $_POST['inkbound_options'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'inkbound_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$raw  = wp_unslash( $_POST['inkbound_options'] );
		$save = inkbound_default_options();
		$save['replace_home']      = ! empty( $raw['replace_home'] );
		$save['catalog_title']     = sanitize_text_field( $raw['catalog_title'] ?? '' );
		$save['catalog_tagline']   = sanitize_text_field( $raw['catalog_tagline'] ?? '' );
		$save['from_name']         = sanitize_text_field( $raw['from_name'] ?? '' );
		$save['from_email']        = sanitize_email( $raw['from_email'] ?? '' );
		$save['email_mode']        = 'excerpt' === ( $raw['email_mode'] ?? '' ) ? 'excerpt' : 'full';
		$save['confirm_subs']      = ! empty( $raw['confirm_subs'] );
		$save['notify_on_publish'] = ! empty( $raw['notify_on_publish'] );
		update_option( 'inkbound_options', $save );
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'inkbound' ) . '</p></div>';
			}
		);
	}

	public function seed_notice(): void {
		if ( empty( $_GET['inkbound_seed'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'inkbound_seed' );
		Inkbound_Seed::run();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Demo serials loaded.', 'inkbound' ) . '</p></div>';
	}
}
