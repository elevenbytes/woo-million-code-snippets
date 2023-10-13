<?php
/**
 * By default ElasticPress plugin makes request to ElasticSearch with limit 10.000 entries in response.
 * Because of that "total products" on the shop page was not more than 10.000.
 */

/**
 * Disable total posts limit in ElasticSearch response.
 *
 * @param array $formatted_args ElasticPress request arguments.
 *
 * @return array
 */
function elbytes_elasticpress_disable_total_posts_limit( $formatted_args ) {
	$formatted_args['track_total_hits'] = true;
	return $formatted_args;
}
add_filter( 'ep_formatted_args', 'elbytes_elasticpress_disable_total_posts_limit' );
