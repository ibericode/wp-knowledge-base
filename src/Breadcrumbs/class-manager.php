<?php

namespace WPKB\Breadcrumbs;

class Manager {

	/**
	 * @var int
	 */
	protected $archive_page_id;

	/**
	 * @param int $archive_page_id
	 */
	public function __construct( $archive_page_id = 0 ) {
		$this->archive_page_id = $archive_page_id;
	}

	public function add_hooks() {
		add_filter( 'wpkb_extensions', array( $this, 'register_extension' ) );
		add_action( 'wpkb_before_article_content', array( $this, 'output' ) );
		add_action( 'wpkb_before_category_archive', array( $this, 'output' ) );
		add_action( 'wpkb_before_keyword_archive', array( $this, 'output' ) );
	}

	/**
	 * @param array $extensions
	 *
	 * @return array
	 */
	public function register_extension( array $extensions ) {
		$extensions[ 'breadcrumbs' ] = $this;
		return $extensions;
	}

	/**
	 * Get the breadcrumb string
	 *
	 * @return string
	 */
	public function get_output() {
		$crumbs = new Crumbs( $this->archive_page_id );
		$crumbs->build_crumbs();
		return $crumbs->build_html();
	}

	/**
	 * Output the breadcrumb
	 */
	public function output() {
		echo $this->get_output();
		$this->relocate_crumbs();
	}

	/**
	 * Relocate breadcrumbs so that they come first in the post container
	 */
	public function relocate_crumbs() {
		?>
		<script>
			(function(d) {
				var crumbs = d.getElementById('wpkb-breadcrumbs');
				var postContainer = d.querySelector('.wpkb-article');
				postContainer.insertBefore( crumbs, postContainer.firstChild );
			})(document);
		</script>
		<?php
	}

}