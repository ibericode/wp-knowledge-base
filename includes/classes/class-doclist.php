<?php

namespace WPDocs;

use Smalot\PdfParser\Page;

if( ! defined( 'WPDOCS_VERSION' ) ) {
	exit;
}

class DocList {

	const SHORTCODE = 'wpdocs_list';

	private $defaults = array(
		'category' => '',
		'keyword' => '',
		'title' => '',
		'css_classes' => ''
	);

	private $html = '';

	/**
	 * Initialize the shortcode
	 */
	public static function init() {
		// register shortcode
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * @param array  $args
	 * @param string $content
	 *
	 * @return DocBlock
	 */
	public static function shortcode( $args = array(), $content = '' ) {
		return new DocList( $args );
	}

	/**
	 * Constructor
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		$args = shortcode_atts( $this->defaults, $args, self::SHORTCODE );

		$query_args = array(
			'post_type' => WPDocs::POST_TYPE_NAME,
			'postsperpage' => -1,
			'post_status' => 'publish'
		);


		// start by assuming a title has been set
		$title = $args['title'];

		// grab given css classes
		$css_classes = $args['css_classes'];

		// query by category?
		if( '' !== $args['category'] ) {

			// add to query arguments
			$query_args[ WPDocs::TAXONOMY_CATEGORY_NAME ] = $args['category'];

			// if no title has been set, use the term name
			if( '' === $title ) {
				$term = get_term_by( 'name', $args['category'], WPDocs::TAXONOMY_CATEGORY_NAME );
				if( is_object( $term ) ) {
					$title = $term->name;
				}
			}

			// add useful css class
			$css_classes .= ' wpdocs-list-category-' . $args['category'];
		}

		// query by keyword?
		if( '' !== $args['keyword'] ) {

			// add to query arguments
			$query_args[ WPDocs::TAXONOMY_KEYWORD_NAME ] = $args['keyword'];

			// if no title has been set, use the term name
			if( '' === $title ) {
				$term = get_term_by( 'name', $args['keyword'], WPDocs::TAXONOMY_KEYWORD_NAME );

				// if no title has been set, use the term name
				if( is_object( $term ) ) {
					$title = $term->name;
				}
			}

			// add useful css class
			$css_classes .= ' wpdocs-list-keyword-' . $args['keyword'];
		}

		// start building output string
		$output = '<div class="wpdocs-list ' . esc_attr( ltrim( $css_classes ) ) . '">';
		$output .= '<h3 class="wpdocs-list-title">' . esc_html( $title ) . '</h3>';

		// query docs
		$docs = new \WP_Query( $query_args );

		$output .= '<div class="wpdocs-list-content">';

		if( $docs->have_posts() ) {

			$output .= '<ul>';

			while( $docs->have_posts() ) {
				$docs->the_post();

				// build string of css classes for this list element
				$css_classes = 'wpdocs-doc-' . get_the_ID();

				$css_classes .= ( $docs->current_post % 2 ) ? ' wpdocs-odd' : ' wpdocs-even';

				if( $docs->current_post === 1 ) {
					$css_classes .= ' wpdocs-doc-first';
				} elseif( $docs->current_post + 1 === $docs->post_count ) {
					$css_classes .= ' wpdocs-doc-last';
				}

				$output .= '<li class="' . $css_classes . '"><a href="'. get_permalink() .'">' . get_the_title() . '</a></li>';
			}

			$output .= '</ul>';
		} else {
			$output .= '<p>' . __( 'No documentation articles.', 'wpdocs' ) . '</p>';
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