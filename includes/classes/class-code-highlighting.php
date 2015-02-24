<?php

namespace WPDocs;

if( ! defined( 'WPDOCS_VERSION' ) ) {
	exit;
}

class Code_Highlighting {

	public function __construct() {

		// register shortcode
		add_shortcode( 'wpdocs_code', array( $this, 'shortcode' ) );

		// lazy add actions
		add_action( 'template_redirect', array( $this, 'lazy_add' ) );
	}

	/**
	 * Performs a set of action, but only for `wpdocs-doc` posts.
	 *
	 * - Registers scripts and styles
	 * - Registers filters and action hooks to properly format code snippets
	 * - Prints inline JS in footer to initialize the Highlighter
	 *
	 * @return bool
	 */
	public function lazy_add() {

		if( ! is_singular( 'wpdocs-doc' ) ) {
			return false;
		}

		// get post that's being viewed
		$post = get_post();

		if( ! has_shortcode( $post->post_content, 'wpdocs_code' ) ) {
			return false;
		}

		// add filters
		remove_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'wpautop' , 99);
		add_filter( 'the_content', 'shortcode_unautop',100 );
		remove_filter('the_content', 'wptexturize');

		// register scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'wp_footer', array( $this, 'print_inline_js' ), 99 );
		return true;
	}

	/**
	 * Load script & styles required for WP Docs search
	 */
	public function load_assets() {

		$plugin_url = plugins_url( '/assets/', WPDOCS_FILE );
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'wpdocs-code-highlighting', $plugin_url . 'css/code-highlighting' . $min . '.css' );
		wp_register_script( 'wpdocs-code-highlighting', '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.3/highlight.min.js', array( ), false, true );

		wp_enqueue_style( 'wpdocs-code-highlighting' );
		wp_enqueue_script( 'wpdocs-code-highlighting' );
	}

	/**
	 * @param        $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function shortcode( $args, $content = '' ) {

		$defaults = array(
			'lang' => 'html'
		);
		$args = shortcode_atts( $defaults, $args, 'wpdocs_code' );

		$content = trim( $content );
		$content = ltrim( $content, '\n' );
		$content = rtrim( $content, '\n' );

		$output = '<pre><code class="'. esc_attr( $args['lang'] ) .'">';
		$output .= esc_html( $content );
		$output .= '</code></pre>';

		return $output;
	}

	/**
	 * Print inline JS to initialize Highlight.js
	 */
	public function print_inline_js() {
		?>
		<script type="text/javascript">
			hljs.initHighlightingOnLoad();
		</script>
		<?php
	}


}