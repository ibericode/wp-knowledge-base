<?php

namespace WPDocs;

if( ! defined( 'WPDOCS_VERSION' ) ) {
	exit;
}

class Template_Manager {

	/**
	 * @var object
	 */
	private $queried_object;

	/**
	 * @var array
	 */
	private $templates = array( 'page.php', 'single.php', 'index.php' );

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'choose_template' ) );
	}

	/**
	 * Decide which template file to load
	 */
	public function choose_template() {

		$this->queried_object = get_queried_object();

		// choose post type archive template to load
		// - custom page
		// - custom archive template from theme
		// - default wpdocs custom archive template
		if( is_post_type_archive( WPDocs::POST_TYPE_NAME ) ) {

			// load template for a single page
			$custom_archive_page_id = WPDocs::get_option( 'custom_archive_page_id' );

			if( $custom_archive_page_id > 0 ) {

				// if we're using a custom archive page, that one should be used
				$archive_link = get_permalink( $custom_archive_page_id );

				if( $archive_link !== get_post_type_archive_link( WPDocs::POST_TYPE_NAME ) ) {
					wp_redirect( $archive_link );
					exit;
				}
			} else {
				add_filter( 'archive_template', array( $this, 'set_archive_template' ) );
			}

		} elseif( is_tax( WPDocs::TAXONOMY_CATEGORY_NAME ) ) {

			// choose "category" archive template to load
			add_filter( 'taxonomy_template', array( $this, 'set_taxonomy_category_template' ) );

		} elseif( is_tax( WPDocs::TAXONOMY_KEYWORD_NAME ) ) {

			// choose "keyword" archive template to load
			add_filter( 'taxonomy_template', array( $this, 'set_taxonomy_keyword_template' ) );

		} elseif( is_singular( WPDocs::POST_TYPE_NAME ) ) {

			// choose template to load for singular docs
			add_filter( 'single_template', array( $this, 'set_single_template' ) );
		}

	}

	/**
	 * @param $template
	 *
	 * @return string
	 */
	public function set_archive_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'archive-wpdocs-doc.php' ) !== false ) {
			return $template;
		}

		add_filter( 'the_title', array( $this, 'default_archive_title' ) );
		add_filter( 'the_content', array( $this, 'default_archive_content' ) );

		return locate_template( $this->templates );
	}

	/**
	 * @param $title
	 *
	 * @return string|void
	 */
	public function default_archive_title( $title ) {
		remove_filter( 'the_title', array( $this, 'default_archive_title' ) );
		return __( 'Documentation', 'wpdocs' );
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function default_archive_content( $content ) {
		remove_filter( 'the_content', array( $this, 'default_archive_content' ) );
		return '[wpdocs_search][wpdocs_list]';
	}

	/**
	 * @param $template
	 *
	 * @return mixed
	 */
	public function set_taxonomy_category_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'taxonomy-wpdocs-category.php' ) !== false ) {
			return $template;
		}

		add_filter( 'the_title', array( $this, 'default_taxonomy_category_title' ) );
		add_filter( 'the_content', array( $this, 'default_taxonomy_category_content' ) );

		return locate_template( $this->templates );
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function default_taxonomy_category_title( $title ) {
		remove_filter( 'the_title', array( $this, 'default_taxonomy_category_title' ) );
		return $this->queried_object->name;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function default_taxonomy_category_content( $content ) {
		remove_filter( 'the_content', array( $this, 'default_taxonomy_category_content' ) );

		$content = '';

		if( WPDocs::extension( 'breadcrumb' ) ) {
			$content .= wpdocs_breadcrumb();
			$this->relocate_breadcrumb();
		}

		$content .= term_description( );
		$content .= '[wpdocs_list category="'. $this->queried_object->name .'"]';
		$content .= '<h4>'. __( 'Search all categories', 'wpdocs' ) .'</h4>';
		$content .= '[wpdocs_search]';
		return $content;
	}

	/**
	 * @param $template
	 *
	 * @return mixed
	 */
	public function set_taxonomy_keyword_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'taxonomy-wpdocs-keyword.php' ) !== false ) {
			return $template;
		}

		add_filter( 'the_title', array( $this, 'default_taxonomy_keyword_title' ) );
		add_filter( 'the_content', array( $this, 'default_taxonomy_keyword_content' ) );

		return locate_template( $this->templates );
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function default_taxonomy_keyword_title( $title ) {
		remove_filter( 'the_title', array( $this, 'default_taxonomy_keyword_title' ) );
		return $this->queried_object->name;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function default_taxonomy_keyword_content( $content ) {
		remove_filter( 'the_content', array( $this, 'default_taxonomy_keyword_content' ) );

		$content = '';

		if( WPDocs::extension( 'breadcrumb' ) ) {
			$content .= wpdocs_breadcrumb();
			$this->relocate_breadcrumb();
		}

		$content .= '[wpdocs_list keyword="'. $this->queried_object->name . '"]';
		$content .= '<h4>'. __( 'Search all keywords', 'wpdocs' ) .'</h4>';
		$content .= '[wpdocs_search]';
		return $content;
	}

	/**
	 * @param $template
	 */
	public function set_single_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'single-wpdocs-doc.php' ) !== false ) {
			return $template;
		}

		// use default post template but with some modifications
		add_filter( 'the_content', array( $this, 'set_single_content' ) );
		return $template;
	}

	/**
	 * Adds the WPDocs breadcrumb to singular Docs
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function set_single_content( $content ) {

		// add breadcrumb
		if( WPDocs::extension( 'breadcrumb' ) ) {
			$content = wpdocs_breadcrumb() . $content;
			$this->relocate_breadcrumb();
		}

		// add block with related doc articles to end of article
		$terms = wp_get_object_terms( $this->queried_object->ID, WPDocs::TAXONOMY_CATEGORY_NAME );
		if( $terms && isset( $terms[0]->name ) ) {
			$title = sprintf( __( 'Other articles in %s', 'wpdocs' ), $terms[0]->name );
			$content .= '[wpdocs_list title="'. $title .'" category="'. $terms[0]->name .'"]';
		}

		return $content;
	}

	/**
	 * Attach the action to relocate the breadcrumb
	 */
	private function relocate_breadcrumb() {
		add_action( 'wp_footer', array( $this, 'relocate_breadcrumb_js' ), 90 );
	}

	/**
	 * Prints the JS that relocated the breadcrumb element.
	 */
	public function relocate_breadcrumb_js() {
		?><script type="text/javascript">
			var breadcrumbElement = document.querySelector('.wpdocs-breadcrumb');
			var postContainerElement = document.querySelector('.wpdocs-doc');
			if( breadcrumbElement && postContainerElement && postContainerElement.firstChild ) {
				postContainerElement.insertBefore( breadcrumbElement, postContainerElement.firstChild );
			}
		</script><?php
	}
}