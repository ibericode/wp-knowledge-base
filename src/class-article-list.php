<?php

namespace WPKB;

class Article_List {

	/**
	 * @var array
	 */
	private $defaults = array(
		'category' => '',
		'keyword' => '',
		'title' => '',
		'css_classes' => '',
		'exclude' => ''
	);

	/**
	 * @var string
	 */
	private $html = '';

	/**
	 * Constructor
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		$args = shortcode_atts( $this->defaults, $args );

		$query_args = array(
			'post_type' => 'wpkb-article',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		);

		if( '' !== $args['exclude'] ) {
			$exclude = explode( ',', $args['exclude'] );
			$query_args['post__not_in'] = $exclude;
		}

		// start by assuming a title has been set
		$title = $args['title'];

		// grab given css classes
		$css_classes = $args['css_classes'];

		// query by category?
		if( '' !== $args['category'] ) {

			// add to query arguments
			$query_args[ 'wpkb-category' ] = $args['category'];

			// if no title has been set, use the term name
			if( '' === $title ) {
				$term = get_term_by( 'name', $args['category'], 'wpkb-category' );

				if( is_object( $term ) ) {
					$title = $term->name;
				}
			}

			// add useful css class
			$css_classes .= ' wpkb-list-category-' . sanitize_title( $args['category'] );
		}

		// query by keyword?
		if( '' !== $args['keyword'] ) {

			// add to query arguments
			$query_args[ 'wpkb-keyword' ] = $args['keyword'];

			// if no title has been set, use the term name
			if( '' === $title ) {
				$term = get_term_by( 'name', $args['keyword'], 'wpkb-keyword' );

				// if no title has been set, use the term name
				if( is_object( $term ) ) {
					$title = $term->name;
				}
			}

			// add useful css class
			$css_classes .= ' wpkb-list-keyword-' . sanitize_title( $args['keyword'] );
		}

		// start building output string
		$output = '<div class="wpkb-list ' . esc_attr( ltrim( $css_classes ) ) . '">';
		$output .= '<h3 class="wpkb-list-title">' . esc_html( $title ) . '</h3>';

		// query docs
		$query = new \WP_Query( $query_args );
		$posts = $query->get_posts();

		$output .= '<div class="wpkb-list-content">';

		if( $posts ) {

			$output .= '<ul>';
			$odd = false;

			foreach( $posts as $post ) {
				$odd = ! $odd;

				// build string of css classes for this list element
				$css_classes = 'wpkb-article-' . $post->ID;
				$css_classes .= $odd ? ' wpkb-odd' : ' wpkb-even';

				// build html for list item
				$output .= '<li class="' . $css_classes . '"><a href="'. get_permalink( $post ) .'">' . get_the_title( $post ) . '</a></li>';
			}

			$output .= '</ul>';
		} else {
			$output .= '<p>' . __( 'No documentation articles.', 'wp-knowledge-base' ) . '</p>';
		}

		$output .= '</div>';
		$output .= '</div>';

		$this->html = $output;
	}



	/**
	 * @return string
	 */
	public function __toString() {
		return $this->html;
	}


}