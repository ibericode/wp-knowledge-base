<?php

// Add id's to h2 tags in content of documentation post type
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	// only act on our own post type
	if( $data['post_type'] !== 'wpkb-article' ) {
		return $data;
	}

	// add `id` attributes to <h2> in post content
	$data['post_content'] = preg_replace_callback( '/<h2>([a-zA-Z0-9\s]+)<\/h2>/', function ( $matches ) {
		return '<h2 id="' . sanitize_title_with_dashes( $matches[1] ) . '">' . $matches[1] . '</h2>';
	}, $data['post_content'] );

	return $data;
}, 99, 2 );

add_shortcode( 'wpkb_table_of_contents', 'wpkb_table_of_contents' );