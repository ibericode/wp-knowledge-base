<?php

defined( 'ABSPATH' ) or exit;

/**
 * Remove articles with custom-field "hidden_from_archive" and value "1" form overview pages.
 */
add_action( 'pre_get_posts', function( WP_Query $query ) {

	// make sure we have something to act on.. static pages don't have the globals
	if( empty( $query->query ) ) {
		return;
	}

	// detect overview pages
	if(
		! $query->is_tax( array( 'wpkb-category', 'wpkb-keyword' ) )
	    && ! $query->is_post_type_archive( 'wpkb-article' )
	    && ! $query->is_page( wpkb('options')->get( 'custom_archive_page_id' ) )
	) {
		return;
	}

	$query->set( 'meta_query', array(
		'relation' => 'OR',
		array(
			'key' => 'hidden_from_archive',
			'value' => false,
			'type' => 'BOOLEAN'
		),
		array(
			'key' => 'hidden_from_archive',
			'compare' => 'NOT EXISTS'
		)
	) );

});