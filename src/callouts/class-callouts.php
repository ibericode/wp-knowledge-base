<?php

namespace WPKB;

use WP_Screen;

class Callouts {

	const SHORTCODE = 'wpkb_callout';

	protected $default_atts = array(
		'type' => 'info'
	);

	public function __construct() {

	}

	public function add_hooks() {
		// register shortcode
		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );

		// print css for this in head
		add_action( 'wp_head', array( $this, 'css' ) );

		// add more buttons to the html editor
		add_action( 'admin_print_footer_scripts', array( $this, 'add_quicktags' ) );
	}

	/**
	 * @return bool
	 */
	public function css() {

		if( ! is_singular( 'wpkb-article' ) ) {
			return false;
		}

		if( ! has_shortcode( get_the_content(), self::SHORTCODE ) ) {
			return false;
		}

		// add filters
		remove_filter( 'the_content', 'wpautop' );
		remove_filter('the_content', 'wptexturize');
		add_filter( 'the_content', 'wpautop' , 99);
		add_filter( 'the_content', 'shortcode_unautop', 100 );

		?>
		<style type="text/css">
			.wpkb-callout {
				border-left: 5px solid #80bfe2;
				padding: 14px;
				margin: 20px 0;
			}
			.wpkb-callout.success {
				background: #e1f3c5;
				border-color: #a8dd57;
			}
			.wpkb-callout.info {
				background: #ecf7ff;
				border-color: #80bfe2;
			}
			.wpkb-callout.warning {
				background: #fde8e8;
				border-color: #e7aaaa;
			}
			.wpkb-callout a{
				color: inherit;
				text-decoration: underline;
			}
			.wpkb-callout > *:first-child {
				margin-top: 0;
			}
			.wpkb-callout > *:last-child {
				margin-bottom: 0;
			}
		</style>
		<?php
	}

	/**
	 * @param array  $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public function shortcode( $atts = array(), $content = '' ) {
		$atts = shortcode_atts( $this->default_atts, $atts, self::SHORTCODE );
		$content = '<div class="wpkb-callout ' . $atts['type'] . '">' . trim( $content ) . '</div>';
		return $content;
	}

	/**
	 * @return bool
	 */
	public function add_quicktags() {
		$screen = get_current_screen();

		if( ! $screen instanceof WP_Screen || $screen->parent_base !== 'edit' || $screen->post_type !== 'wpkb-article' ) {
			return false;
		}

		// only print if quicktags is loaded
		if( wp_script_is( 'quicktags' ) ) {
			?>
			<script type="text/javascript">
				QTags.addButton( 'wpkb_callout', 'KB: Callout', '[wpkb_callout type="info"]', '[/wpkb_callout]', 'kbca', 'Callout', 100 );
			</script>
		<?php
		}

		return true;
	}


}