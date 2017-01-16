<?php

namespace WPKB;

use WP_Screen;
use WPKB\Plugin;

class CodeHighlighting {

	/**
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * @param $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Add necessary hooks
	 */
	public function add_hooks() {

		// register shortcode
		add_shortcode( 'wpkb_code', array( $this, 'shortcode' ) );

		// lazy add actions
		add_action( 'template_redirect', array( $this, 'lazy_add' ) );

		// add more buttons to the html editor
		add_action( 'admin_print_footer_scripts', array( $this, 'add_quicktags' ) );
	}

	/**
	 * Performs a set of action, but only for `wpkb-article` posts.
	 *
	 * - Registers scripts and styles
	 * - Registers filters and action hooks to properly format code snippets
	 * - Prints inline JS in footer to initialize the Highlighter
	 *
	 * @return bool
	 */
	public function lazy_add() {

		if( ! is_singular( 'wpkb-article' ) ) {
			return false;
		}

		// get post that's being viewed
		$post = get_post();

		if( ! has_shortcode( $post->post_content, 'wpkb_code' ) ) {
			return false;
		}

		// add filters
		remove_filter( 'the_content', 'wpautop' );
		remove_filter('the_content', 'wptexturize');
		add_filter( 'the_content', 'wpautop' , 99);
		add_filter( 'the_content', 'shortcode_unautop', 100 );
		add_filter( 'the_content', array( $this, 'encode_php_tags' ) );

		// register scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'wp_footer', array( $this, 'print_inline_js' ), 99 );
		return true;
	}

	/**
	 * @param $content
	 * @todo: This encodes all ?> and <? occurences in the entire post content. Ideally, we only want to apply this on our shortcode content...
	 * @return mixed
	 */
	public function encode_php_tags( $content ) {
		$content = str_ireplace( array( '<?', '?>' ), array( '&lt;?', '?&gt;' ), $content );
		return $content;
	}

	/**
	 * Load script & styles required for WP Docs search
	 */
	public function load_assets() {

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( 'wpkb-code-highlighting', $this->plugin->url( '/assets/css/code-highlighting' . $min . '.css' ) );
		wp_register_script( 'wpkb-code-highlighting', $this->plugin->url( '/assets/js/code-highlighting.min.js' ), array( ), false, true );

		wp_enqueue_style( 'wpkb-code-highlighting' );
		wp_enqueue_script( 'wpkb-code-highlighting' );
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
		$args = shortcode_atts( $defaults, $args, 'wpkb_code' );

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
				QTags.addButton( 'wpkb_code', 'KB: Code', '[wpkb_code]\n', '\n[/wpkb_code]', 'kbco', 'Code', 101 );
			</script>
		<?php
		}

		return true;
	}


}