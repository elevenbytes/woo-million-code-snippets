<?php
/**
 * Taxonomies optimization for WooCommerce.
 */

/**
 * The problem we had that woocommerce re-count products number for terms every time when get_terms() function is called.
 * We have thousands of terns and Because of that pages with filters loaded 30-60 seconds.
 *
 * Our idea what to disable terms count calculation on frontend and use CRON task to do it time to time.
 */
if ( ! is_admin() || wp_doing_ajax() ) {
	remove_filter( 'get_terms', 'wc_change_term_counts', 10, 2 );
}

/**
 * The same problem as above. Wooconnerce re-calculate terms every time when product updated.
 *
 * Our idea what to disable terms count calculation during product update and use CRON task to do it time to time.
 */
if ( ! wp_doing_cron() ) {
	remove_action( 'transition_post_status', '_update_term_count_on_transition_post_status' );

	add_filter( 'woocommerce_product_recount_terms', '__return_false' );
	add_action( 'transition_post_status', 'elbytes_maybe_update_term_count_on_transition_post_status', 10, 3 );
}

/**
 * Update terms count on transition post status if post type is not product.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function elbytes_maybe_update_term_count_on_transition_post_status( string $new_status, string $old_status, WP_Post $post ) {
	if ( $post->post_type === 'product' ) {
		return false;
	}

	// Update counts for the post's terms if not woocommerce product.
	foreach ( (array) get_object_taxonomies( $post->post_type ) as $taxonomy ) {
		$tt_ids = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'tt_ids' ] );
		wp_update_term_count( $tt_ids, $taxonomy );
	}
}


/**
 * Schedule CRON task to update terms count every hour.
 *
 * @return void
 */
if ( ! wp_next_scheduled( 'elbytes_update_terms_count' ) ) {
	wp_schedule_event( time(), 'hourly', 'elbytes_update_terms_count' );
}
add_action( 'elbytes_update_terms_count', 'elbytes_update_terms_count_callback' );

/**
 * Update term count meta.
 *
 * @return void
 */
function elbytes_update_terms_count_callback() {
	$taxonomies        = [ 'product_cat', 'product_tag' ];
	$wc_product_terms  = [];
	$product_terms_ids = [];

	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$wc_product_terms[ $term->term_id ] = $term->parent;

				$product_terms_ids[] = $term->term_id;
			}

			// Updated terms count and save to database.
			_wc_term_recount( $wc_product_terms, get_taxonomy( $taxonomy ), false, false );

			wp_update_term_count( $product_terms_ids, $taxonomy );
		}
	}
}
