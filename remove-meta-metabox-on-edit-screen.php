<?php
/**
 * The problem that in "Woo Million" we have 45M rows in the wp_postmeta table and query to show related custom meta
 * on the single post edit page takes time. We decided to remove default WordPress custom fields metabox from all post types.
 */

/**
 * Remove post custom fields metabox from all post types.
 *
 * @return void
 */
function elbytes_remove_post_custom_fields_metabox() {
	$types = get_post_types( [], 'names' );
	foreach ( $types as $type ) {
		remove_meta_box( 'postcustom', $type, 'normal' );
	}
}
add_action( 'add_meta_boxes', 'elbytes_remove_post_custom_fields_metabox', 999 );
