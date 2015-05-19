<?php

namespace WPKB;

class Categories {

	public $taxonomy_name = 'wpkb-category';
	public $post_type;
	protected $slug_base;

	public function __construct( $post_type, $slug_base ) {
		$this->post_type = $post_type;
		$this->slug_base = $slug_base;
	}

	/**
	 * @return array|\WP_Error
	 */
	public function index() {
		global $post;

		$args = array(
			'taxonomy' => $this->taxonomy_name,
			'title_li' => '',
			'echo' => false,
			'orderby' => 'slug'
		);

		if( ! is_tax( $this->taxonomy_name ) ) {
			$current_categories = get_the_terms( $post, $this->taxonomy_name );

			if( is_array( $current_categories ) ) {
				$current_category = array_pop( $current_categories);
				$args['current_category'] = $current_category->term_id;
			}
		}

		$list = wp_list_categories( $args );

		return $list;
	}

	public function add_hooks() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	public function register_taxonomy() {
		// register docs taxonomy: category
		register_taxonomy(
			$this->taxonomy_name,
			$this->post_type,
			array(
				'label' => __( 'Category' ),
				'rewrite' => array( 'slug' => $this->slug_base . '/category' ),
				'hierarchical' => true,
				'query_var' => true
			)
		);
	}

}