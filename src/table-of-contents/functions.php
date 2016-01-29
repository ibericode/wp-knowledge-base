<?php

/**
 * @param $args
 *
 * @return string
 */
function wpkb_table_of_contents( $args ) {
	global $post;

	$content = '';

	// parse args
	$args = is_array( $args ) ? $args : array();
	$default_args = array( 'title' => __( 'Table of Contents', 'wp-knowledge-base' ) );
	$args = array_merge( $default_args, $args );

	// parse content for <h2> elements with an ID
	preg_match_all( '/<h2 id="([a-z\-]+)">([a-zA-Z0-9\s]+)<\/h2>/i', $post->post_content, $headings );

	if( ! empty( $headings[1] ) ) {
		$content .= '<div class="wpkb-table-of-contents">';
		$content .= '<h4>' . $args['title'] . '</h4>';

		$content .= '<ul>';
		foreach( $headings[1] as $i => $heading_id ) {
			$heading_text = $headings[2][$i];
			$content .= sprintf( '<li><a href="#%s">%s</a></li>', $heading_id, $heading_text );

		}
		$content .= '</ul>';
		$content .= '</div>';
	}

	return $content;
}