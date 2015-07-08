<?php

namespace WPKB;

class TemplateManager {

	/**
	 * @var object
	 */
	private $queried_object;

	/**
	 * @var array
	 */
	private $templates = array( 'page.php', 'single.php', 'index.php' );

	/**
	 * @var Plugin
	 */
	protected $wpkb;

	/**
	 * @param Plugin $wpkb
	 */
	public function __construct( Plugin $wpkb ) {
		$this->wpkb = $wpkb;
	}

	/**
	 * @param        $template
	 * @param string $content
	 *
	 * @return string
	 */
	protected function load_template( $template, $content = '' ) {
		ob_start();
		require __DIR__ . '/templates/' . $template . '.php';
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function load_article_template( $content ) {
		return $this->load_template( 'article', $content );
	}

	/**
	 * Decide which template file to load
	 */
	public function override_templates() {

		$this->queried_object = get_queried_object();

		if( is_post_type_archive( Plugin::POST_TYPE_NAME ) ) {

			// load template for a single page
			$custom_archive_page_id = $this->wpkb->get_option( 'custom_archive_page_id' );

			if( $custom_archive_page_id > 0 ) {

				// if we're using a custom archive page, that one should be used
				$archive_link = get_permalink( $custom_archive_page_id );

				if( $archive_link !== get_post_type_archive_link( Plugin::POST_TYPE_NAME ) ) {
					wp_redirect( $archive_link );
					exit;
				}
			} else {
				add_filter( 'archive_template', array( $this, 'set_archive_template' ) );
			}

		} elseif( is_tax( Plugin::TAXONOMY_CATEGORY_NAME ) ) {

			// choose "category" archive template to load
			add_filter( 'taxonomy_template', array( $this, 'set_taxonomy_category_template' ) );

		} elseif( is_tax( Plugin::TAXONOMY_KEYWORD_NAME ) ) {

			// choose "keyword" archive template to load
			add_filter( 'taxonomy_template', array( $this, 'set_taxonomy_keyword_template' ) );

		} elseif( is_singular( Plugin::POST_TYPE_NAME ) ) {

			// choose template to load for singular docs
			add_filter( 'single_template', array( $this, 'set_single_template' ) );
			add_filter( 'wp_footer', array( $this, 'print_js_helpers' ) );
			add_filter( 'wp_head', array( $this, 'print_css_helpers' ) );
		}

	}

	/**
	 * @param $template
	 *
	 * @return string
	 */
	public function set_archive_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'archive-wpkb-article.php' ) !== false ) {
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
		return __( 'Knowledge Base', 'wp-knowledge-base' );
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function default_archive_content( $content ) {
		remove_filter( 'the_content', array( $this, 'default_archive_content' ) );
		return $this->load_template( 'archive', $content );
	}

	/**
	 * @param $template
	 *
	 * @return mixed
	 */
	public function set_taxonomy_category_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'taxonomy-wpkb-category.php' ) !== false ) {
			return $template;
		}

		add_filter( 'the_title', array( $this, 'default_taxonomy_category_title' ) );
		add_filter( 'the_content', array( $this, 'default_taxonomy_category_content' ) );

		return locate_template( $this->templates );
	}

	/**
	 * @param $title
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
		return $this->load_template( 'category', $content );
	}

	/**
	 * @param $template
	 *
	 * @return mixed
	 */
	public function set_taxonomy_keyword_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'taxonomy-wpkb-keyword.php' ) !== false ) {
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

		return $this->load_template( 'keyword', $content );
	}

	/**
	 * @param $template
	 */
	public function set_single_template( $template ) {

		// if custom archive was set, use that one.
		if( stristr( $template, 'single-wpkb-article.php' ) !== false ) {
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
		return $this->load_template( 'single', $content );
	}

	/**
	 * Print CSS helpers
	 */
	public function print_css_helpers() {
		?>
		<style type="text/css">
			.wpkb-alert {
				padding: 14px;
				margin-bottom: 20px;
				border: 1px solid transparent;
			}
			.wpkb-alert.success {
				background-color: #dff0d8;
				border-color: #d6e9c6;
			}
			.wpkb-alert.info {
				background-color: #ecf7ff;
				border-color: #80bfe2;
			}
			.wpkb-alert.warning {
				background-color: #f2dede;
				border-color: #ebccd1;
			}
		</style>
		<?php
	}

	/**
	 * Print JavaScript helpers
	 */
	public function print_js_helpers() {
		?>
		<script type="text/javascript">
			(function() {
				var alert = document.querySelector('.wpkb-alert');
				if( alert ) {
					var parent = document.querySelector('.wpkb-article');
					parent.insertBefore( alert, parent.firstChild );
				}
			})();
		</script>
		<?php
	}

}