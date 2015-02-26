<?php

namespace WPDocs\Breadcrumbs;

use WPDocs\WPDocs;

class Crumbs {

	/**
	 * @var array
	 */
	private $crumbs = array();

	/**
	 * @var Crumbs
	 */
	private static $instance;

	/**
	 * @return Crumbs
	 */
	public static function instance() {

		if( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {

		if( ! did_action( 'init' ) ) {
			add_action( 'init', array( $this, 'build_crumbs' ) );
		} else {
			$this->build_crumbs();
		}
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
	private function add_term_crumb( $term, $term_type = WPDocs::TAXONOMY_CATEGORY_NAME ) {

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
		$custom_archive_page_id = WPDocs::get_option( 'custom_archive_page_id' );
		if( $custom_archive_page_id > 0 ) {
			$this->add_crumb( get_permalink( $custom_archive_page_id ), get_the_title( $custom_archive_page_id ) );
		} else {
			$this->add_crumb ( get_post_type_archive_link( WPDocs::POST_TYPE_NAME ), __( 'Docs', 'wpdocs' ) );
		}


		if( is_singular( WPDocs::POST_TYPE_NAME ) ) {

			// add category
			$categories = wp_get_object_terms( $object->ID, WPDocs::TAXONOMY_CATEGORY_NAME );

			if( is_array( $categories ) && isset( $categories[0] ) && is_object( $categories[0] ) ) {
				$this->add_term_crumb( $categories[0], WPDocs::TAXONOMY_CATEGORY_NAME );
			}

			// add doc title
			$this->add_crumb( get_permalink( $object ), get_the_title( $object ) );

		} elseif( is_tax( WPDocs::TAXONOMY_CATEGORY_NAME ) ) {

			$this->add_term_crumb( $object, WPDOcs::TAXONOMY_CATEGORY_NAME );

		} elseif( is_tax( WPDOcs::TAXONOMY_KEYWORD_NAME ) ) {

			$this->add_term_crumb( $object, WPDOcs::TAXONOMY_KEYWORD_NAME );

		}
	}

	/**
	 * Build the HTML string for the breadcrumbs
	 */
	public function build_html( $opening_element = '<p class="breadcrumb wpdocs-breadcrumb">', $closing_element = '</p>', $separator = '&raquo;', $prefix = 'You are here:' ) {

		$output = $opening_element;

		if( '' !==  $prefix ) {
			$output .= '<span class="wpdocs-breadcrumb-prefix">' . rtrim( $prefix, ' ' ) . '</span> ';
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

		return $output;
	}


}