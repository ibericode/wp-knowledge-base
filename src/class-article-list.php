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
			'post_type' => Plugin::POST_TYPE_NAME,
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
			$query_args[ Plugin::TAXONOMY_CATEGORY_NAME ] = $args['category'];

			// if no title has been set, use the term name
			if( '' === $title ) {
				$term = get_term_by( 'name', $args['category'], Plugin::TAXONOMY_CATEGORY_NAME );

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
			$query_args[ Plugin::TAXONOMY_KEYWORD_NAME ] = $args['keyword'];

			// if no title has been set, use the term name
			if( '' === $title ) {
				$term = get_term_by( 'name', $args['keyword'], Plugin::TAXONOMY_KEYWORD_NAME );

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
		$docs = new \WP_Query( $query_args );

		$output .= '<div class="wpkb-list-content">';

		if( $docs->have_posts() ) {

			$output .= '<ul>';

			while( $docs->have_posts() ) {
				$docs->the_post();

				// build string of css classes for this list element
				$css_classes = 'wpkb-article-' . get_the_ID();

				$css_classes .= ( $docs->current_post % 2 ) ? ' wpkb-odd' : ' wpkb-even';

				if( $docs->current_post === 1 ) {
					$css_classes .= ' wpkb-first';
				} elseif( $docs->current_post + 1 === $docs->post_count ) {
					$css_classes .= ' wpkb-last';
				}

				$output .= '<li class="' . $css_classes . '"><a href="'. get_permalink() .'">' . get_the_title() . '</a></li>';
			}

			$output .= '</ul>';
		} else {
			$output .= '<p>' . __( 'No documentation articles.', 'wp-knowledge-base' ) . '</p>';
		}

		wp_reset_postdata();

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