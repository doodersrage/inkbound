<?php
/**
 * Catalog / homepage.
 *
 * @package Inkbound
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Template-scoped locals.

$query   = inkbound_catalog_query();
$genres  = get_terms( array( 'taxonomy' => 'inkbound_genre', 'hide_empty' => true ) );
$genre   = sanitize_title( wp_unslash( $_GET['genre'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$status  = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sort    = sanitize_key( wp_unslash( $_GET['sort'] ?? 'updated' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$q       = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$continue = Inkbound_Progress::continue_list( 1 );

require INKB_DIR . 'templates/parts/header.php';
?>
<section class="ink-hero">
	<p class="ink-kicker"><?php bloginfo( 'name' ); ?></p>
	<h1><?php echo esc_html( inkbound_option( 'catalog_title' ) ); ?></h1>
	<p class="ink-lede"><?php echo esc_html( inkbound_option( 'catalog_tagline' ) ); ?></p>
	<?php if ( $continue ) : ?>
		<?php
		$row     = $continue[0];
		$c_story = get_post( $row->story_id );
		$c_ch    = get_post( $row->chapter_id );
		if ( $c_story && $c_ch ) :
			?>
			<a class="ink-continue" href="<?php echo esc_url( get_permalink( $c_ch ) ); ?>">
				<span><?php esc_html_e( 'Continue reading', 'inkbound' ); ?></span>
				<strong><?php echo esc_html( $c_story->post_title ); ?></strong>
				<em><?php echo esc_html( inkbound_chapter_heading( $c_ch ) . ' — ' . $c_ch->post_title ); ?></em>
				<b><?php echo esc_html( (string) (int) $row->percent ); ?>%</b>
</a>
		<?php endif; ?>
	<?php endif; ?>
</section>

<form class="ink-filters" method="get" action="<?php echo esc_url( inkbound_url() ); ?>">
	<label class="ink-search">
		<span class="screen-reader-text"><?php esc_html_e( 'Search stories', 'inkbound' ); ?></span>
		<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'Search titles and blurbs…', 'inkbound' ); ?>">
	</label>
	<select name="genre" aria-label="<?php esc_attr_e( 'Genre', 'inkbound' ); ?>">
		<option value=""><?php esc_html_e( 'All genres', 'inkbound' ); ?></option>
		<?php if ( $genres && ! is_wp_error( $genres ) ) : ?>
			<?php foreach ( $genres as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $genre, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
			<?php endforeach; ?>
		<?php endif; ?>
	</select>
	<select name="status" aria-label="<?php esc_attr_e( 'Status', 'inkbound' ); ?>">
		<option value=""><?php esc_html_e( 'Any status', 'inkbound' ); ?></option>
		<?php foreach ( inkbound_statuses() as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<select name="sort" aria-label="<?php esc_attr_e( 'Sort', 'inkbound' ); ?>">
		<option value="updated" <?php selected( $sort, 'updated' ); ?>><?php esc_html_e( 'Recently updated', 'inkbound' ); ?></option>
		<option value="popular" <?php selected( $sort, 'popular' ); ?>><?php esc_html_e( 'Most read', 'inkbound' ); ?></option>
		<option value="followers" <?php selected( $sort, 'followers' ); ?>><?php esc_html_e( 'Most followed', 'inkbound' ); ?></option>
		<option value="title" <?php selected( $sort, 'title' ); ?>><?php esc_html_e( 'Title', 'inkbound' ); ?></option>
	</select>
	<button class="ink-btn" type="submit"><?php esc_html_e( 'Filter', 'inkbound' ); ?></button>
</form>

<?php if ( ! $query->have_posts() ) : ?>
	<div class="ink-empty">
		<h2><?php esc_html_e( 'No stories match.', 'inkbound' ); ?></h2>
		<p><?php esc_html_e( 'Try another genre, or clear the filters. Authors add serials from the Writing desk.', 'inkbound' ); ?></p>
	</div>
<?php else : ?>
	<div class="ink-grid">
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();
			$story = get_post();
			require INKB_DIR . 'templates/parts/story-card.php';
		endwhile;
		wp_reset_postdata();
		?>
	</div>
	<nav class="ink-pages">
		<?php
		echo wp_kses_post(
			paginate_links(
				array(
					'total'     => (int) $query->max_num_pages,
					'current'   => max( 1, (int) $query->get( 'paged' ) ),
					'format'    => '?pg=%#%',
					'add_args'  => array_filter(
						array(
							'genre'  => $genre,
							'status' => $status,
							'sort'   => $sort,
							'q'      => $q,
						)
					),
				)
			) ?: ''
		);
		?>
	</nav>
<?php endif; ?>
<?php require INKB_DIR . 'templates/parts/footer.php'; ?>
