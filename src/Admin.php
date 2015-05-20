<?php

namespace WPKB;

class Admin {
	public function __construct() {

	}

	public function add_hooks() {
		add_action( 'restrict_manage_posts', array( $this, 'taxonomy_filter_restrict_manage_posts' ) );
		add_filter( 'parse_query', array( $this, 'taxonomy_filter_post_type_request' ) );
	}

	// Filter the request to just give posts for the given taxonomy, if applicable.
	function taxonomy_filter_restrict_manage_posts() {
		global $typenow;

		// If you only want this to work for your specific post type,
		// check for that $type here and then return.
		// This function, if unmodified, will add the dropdown for each
		// post type / taxonomy combination.

		$post_types = array( 'wpkb-article' );

		if ( in_array( $typenow, $post_types ) ) {
			$filters = array( 'wpkb-category', 'wpkb-keyword' );

			foreach ( $filters as $tax_slug ) {
				$taxonomy = get_taxonomy( $tax_slug );
				wp_dropdown_categories( array(
					'show_option_all' => 'All ' . $taxonomy->label,
					'taxonomy' 	  => $tax_slug,
					'name' 		  => $taxonomy->name,
					'orderby' 	  => 'name',
					'selected' 	  => isset( $_GET[$tax_slug] ) ? $_GET[$tax_slug] : '',
					'hierarchical' 	  => $taxonomy->hierarchical,
					'show_count' 	  => false,
					'hide_empty' 	  => true
				) );
			}
		}
	}

	function taxonomy_filter_post_type_request( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' == $pagenow ) {
			$filters = get_object_taxonomies( $typenow );
			foreach ( $filters as $tax_slug ) {
				$var = &$query->query_vars[$tax_slug];
				if ( isset( $var ) ) {
					$term = get_term_by( 'id', $var, $tax_slug );

					if( is_object( $term ) ) {
						$var = $term->slug;
					}

				}
			}
		}
	}


}