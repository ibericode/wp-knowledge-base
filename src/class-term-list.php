<?php

namespace WPKB;

class Term_List {

	/**
	 * @var string
	 */
	public $taxonomy_name;


	/**
	 * Categories constructor.
	 *
	 * @param string $taxonomy_name
	 */
	public function __construct( $taxonomy_name ) {
		$this->taxonomy_name = $taxonomy_name;
	}

	/**
	 * @return string
	 */
	public function __toString( ) {
		global $post;

		$args = array(
			'taxonomy' => $this->taxonomy_name,
			'title_li' => '',
			'echo' => false,
			'orderby' => 'slug'
		);

		if( ! is_tax( $this->taxonomy_name ) ) {
			$terms = get_the_terms( $post, $this->taxonomy_name );

			if( is_array( $terms ) ) {
				$current_term = array_pop( $terms );
				$args['current_category'] = $current_term->term_id;
			}
		}

		$list = wp_list_categories( $args );
		return $list;
	}

}