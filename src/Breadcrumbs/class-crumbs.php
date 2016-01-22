<?php

namespace WPKB\Breadcrumbs;

use WPKB\Plugin;

class Crumbs {

	/**
	 * @var array
	 */
	private $crumbs = array();

	/**
	 * @var int
	 */
	protected $base_page_id = 0;

	/**
	 * Constructor
	 * @param int $base_page_id
	 */
	public function __construct( $base_page_id = 0 ) {
		$this->base_page_id = $base_page_id;
	}

	/**
	 * Adds a crumb to the breadcrumbs
	 *
	 * @param string $link
	 * @param string $title
	 */
	private function add_crumb( $link, $title ) {
		$this->crumbs[] = array(
			'link' => $link,
			'title' => $title
		);
	}

	/**
	 * @param        $term
	 * @param string $term_type
	 *
	 * @return bool
	 */
	private function add_term_crumb( $term, $term_type = Plugin::TAXONOMY_CATEGORY_NAME ) {

		// if term is not an object, query it.
		if( ! is_object( $term ) && is_int( $term ) ) {
			$term = get_term( $term, $term_type );
		}

		// was term found?
		if( ! $term ) {
			return false;
		}

		// check if term has parents
		if( isset( $term->parent ) && $term->parent > 0 ) {
			$this->add_term_crumb( $term->parent, $term_type );
		}

		// add term to crumbs array
		$this->add_crumb( get_term_link( $term ) , $term->name );
		return true;
	}

	/**
	 * Build the array of crumbs
	 */
	public function build_crumbs() {

		$object = get_queried_object();

		// add base to crumb
		if( $this->base_page_id > 0 ) {
			$this->add_crumb( get_permalink( $this->base_page_id ), get_the_title( $this->base_page_id ) );
		} else {
			$this->add_crumb ( get_post_type_archive_link( Plugin::POST_TYPE_NAME ), __( 'Knowledge Base', 'wpdocs' ) );
		}


		if( is_singular( Plugin::POST_TYPE_NAME ) ) {

			// add category
			$categories = wp_get_object_terms( $object->ID, Plugin::TAXONOMY_CATEGORY_NAME );

			if( is_array( $categories ) && isset( $categories[0] ) && is_object( $categories[0] ) ) {
				$this->add_term_crumb( $categories[0], Plugin::TAXONOMY_CATEGORY_NAME );
			}

			// add doc title
			$this->add_crumb( get_permalink( $object ), get_the_title( $object ) );

		} elseif( is_tax( Plugin::TAXONOMY_CATEGORY_NAME ) ) {

			$this->add_term_crumb( $object, Plugin::TAXONOMY_CATEGORY_NAME );

		} elseif( is_tax( Plugin::TAXONOMY_KEYWORD_NAME ) ) {

			$this->add_term_crumb( $object, Plugin::TAXONOMY_KEYWORD_NAME );

		}
	}

	/**
	 * Build the HTML string for the breadcrumbs
	 *
	 * @param string $opening_element
	 * @param string $closing_element
	 * @param string $separator
	 * @param string $prefix
	 *
	 * @return string
	 */
	public function build_html( $opening_element = '<p class="breadcrumb wpkb-breadcrumb">', $closing_element = '</p>', $separator = '&raquo;', $prefix = 'You are here:' ) {

		$output = '<div id="wpkb-breadcrumbs">';
		$output .= $opening_element;

		if( '' !==  $prefix ) {
			$output .= '<span class="wpkb-breadcrumb-prefix">' . rtrim( $prefix, ' ' ) . '</span> ';
		}

		$output .= '<span prefix="v: http://rdf.data-vocabulary.org/#">';

		// add individual crumbs
		$number_of_crumbs = count( $this->crumbs );
		$current_crumb = 1;

		foreach( $this->crumbs as $crumb ) {

			// is this the last crumb?
			$is_last_crumb = ( $current_crumb === $number_of_crumbs );

			$output .= '<span typeof="v:Breadcrumb">';

			// last crumb shouldn't be a link
			if( $is_last_crumb ) {
				$output .= '<strong property="v:title">';
				$output .= esc_html( $crumb['title'] );
				$output .= '</strong>';
			} else {
				$output .= '<a href="' . esc_url( $crumb['link'] ) . '" rel="v:url" property="v:title">';
				$output .= esc_html( $crumb['title'] );
				$output .= '</a>';
			}


			$output .= '</span>';

			// add crumb separator, we'll strip this off later for the last crumb
			$output .= ' ' . $separator . ' ';

			$current_crumb++;
		}

		// strip separator after last crumb
		$output = rtrim( $output, $separator . ' ' );


		$output .= '</span>';
		$output .= $closing_element;
		$output .= '</div>';

		return $output;
	}


}