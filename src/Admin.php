<?php

namespace WPKB;

use WP_Post;

class Admin {

	/**
	 * Construct
	 */
	public function __construct() {

	}

	public function add_hooks() {
		add_action( 'restrict_manage_posts', array( $this, 'taxonomy_filter_restrict_manage_posts' ) );
		add_filter( 'parse_query', array( $this, 'taxonomy_filter_post_type_request' ) );
		add_action( 'save_post_' . Plugin::POST_TYPE_NAME, array( $this, 'save_kb_article' ), 10, 2 );
	}

	/**
	 * Sets a global "last_modified" option that tracks changes to articles in Knowledge Base.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @return boolean
	 */
	public function save_kb_article( $post_id, $post ) {

		if( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return false;
		}

		update_option( 'wpkb_last_modified', time() );

		return true;
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