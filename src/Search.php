<?php

namespace WPKB;

class Search {

	const SHORTCODE = 'wpkb_search';

	/**
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * @var bool
	 */
	private $is_search = false;

	/**
	 * @var string
	 */
	private $term = '';

	/**
	 * @var array
	 */
	private $defaults = array(
		'style' => 'default'
	);

	/**
	 * @var array
	 */
	private $results = array();

	/**
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function add_hooks() {
		// register shortcode
		add_shortcode( self::SHORTCODE, array( $this, 'form' ) );

		// register scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );

		// listen for requests
		add_action( 'wp', array( $this, 'process_non_ajax_search' ) );
		add_action( 'wp_ajax_wpkb_search', array( $this, 'process_ajax_search') );
		add_action( 'wp_ajax_nopriv_wpkb_search', array( $this, 'process_ajax_search' ) );
	}

	/**
	 * Load script & styles required for WP Docs search
	 */
	public function load_assets() {

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_register_script( 'wpkb-search', $this->plugin->url( '/assets/js/search' . $min . '.js' ), array( 'jquery' ), $this->plugin->version(), true );

		$data = array(
			'ajaxurl' =>  admin_url( 'admin-ajax.php' )
		);
		wp_localize_script( 'wpkb-search', 'wpkb_search_vars', $data );
	}

	/**
	 * Renders the WP Docs search form
	 *
	 * @param        $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function form( $args = array(), $content = '' ) {

		$args = shortcode_atts( $this->defaults, $args, self::SHORTCODE );

		$is_quick = ( $args['style'] === 'quick' );

		wp_enqueue_script( 'wpkb-search' );

		ob_start();
		?>
		<div class="wpkb-search">
		<form action="" method="get" class="wpkb-search-form <?php echo ( $is_quick ) ? 'wpkb-quick-search-form' : ''; ?>">
			<p>
				<span class="wpkb-search-input">
					<input type="text" name="wpkb-search" class="wpkb-search-term" placeholder="<?php esc_attr_e( ( $is_quick ) ? 'Quick Search' : 'What are you looking for?', 'wp-knowledge-base' ); ?>" required />
				</span>
				<span class="wpkb-search-button" style="<?php echo ( $is_quick ) ? 'display: none;' : ''; ?>">
					<input type="submit" value="<?php esc_attr_e( 'Search', 'wp-knowledge-base' ); ?>" />
				</span>
			</p>
		</form>
		<div class="wpkb-search-results">
		<?php
		if( $this->is_search ) {
			echo $this->build_result_html( $this->term, $this->results );
		} elseif( ! $is_quick )  {
			echo '<em class="wpkb-search-placeholder">' . __( 'Type your search query in the field above.', 'wp-knowledge-base' ) . '</em>';
		}
		// close search results div
		?></div></div><?php

		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Builds the HTML for the search results
	 *
	 * @param string $term
	 * @param string $results
	 *
	 * @return string
	 */
	private function build_result_html( $term, array $results ) {

		$html = '<strong>' . sprintf( __( 'Results for "%s"', 'wp-knowledge-base' ), $term ) . '</strong>';
		$html .= '<p>';

		if( count( $results ) === 0 ) {
			$html .= __( 'No articles found.', 'wp-knowledge-base' );
		} else {
			foreach( $results as $post ) {
				$html .= sprintf( '<a href="%s">%s</a><br />', get_permalink( $post->ID ), get_the_title( $post->ID ) );
			}
		}

		$html .= '</p>';

		return $html;
	}

	/**
	 * Process AJAX search requests, returns a JSON response.
	 */
	public function process_ajax_search() {

		$last_modified = get_option( 'wpkb_last_modified', time() );

		header( "Last-Modified: " . gmdate( "D, d M Y H:i:s", $last_modified ) . " GMT" );
		header( 'Cache-Control: public' );

		if( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			if( strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) == $last_modified ) {
				header( "HTTP/1.1 304 Not Modified" );
				exit;
			}
		}

		$term = sanitize_text_field( $_GET['search'] );
		$results = $this->search( $term );
		$data = $this->build_result_html( $term, $results );

		wp_send_json_success( $data );
	}

	/**
	 * Process regular (non-AJAX) search requests.
	 *
	 * Stores the term and results as instance properties so the results HTML can be built later on.
	 */
	public function process_non_ajax_search() {

		if( ! isset( $_GET['wpkb-search'] ) ) {
			return;
		}

		$this->term = sanitize_text_field( $_GET['wpkb-search'] );
		$this->results = $this->search( $this->term );
		$this->is_search = true;
	}

	/**
	 * Search through the Docs for a given term.
	 *
	 * @param string $original_term term to search for.
	 *
	 * @return array Array with post ID's
	 */
	public function search( $original_term ) {

		do_action( 'wpkb_search', $original_term );

		// use SearchWP if possible
		if( class_exists( 'SearchWP' ) ) {

			$engine = \SearchWP::instance();
			$posts = $engine->search( 'wpkb_search', $original_term );

			if( is_array( $posts ) ) {
				return $posts;
			}

			return array();
		}

		global $wpdb;

		// go for an easy escape if the original term is very short
		if( strlen( $original_term ) < 3 ) {
			return array();
		}

		// start building SQL query string
		$params = array();
		$string = '';

		$string .= "SELECT wpp.id";
		$string .= " FROM {$wpdb->posts} wpp";
		$string .= " LEFT JOIN {$wpdb->term_relationships} wptr ON wpp.id = wptr.object_id";
		$string .= " LEFT JOIN {$wpdb->term_taxonomy} wptt ON wptr.term_taxonomy_id = wptt.term_taxonomy_id";
		$string .= " LEFT JOIN {$wpdb->terms} wpt ON wpt.term_id = wptt.term_id";

		// only query post type doc
		$string .= " WHERE wpp.post_type = 'wpkb-article'";

		// only query published docs
		$string .= " AND wpp.post_status = 'publish'";

		// query each search word in post title, post content, docs keyword and docs category
		$string .= " AND (";

		// query title & content
		$string .= " wpp.post_title LIKE %s OR wpp.post_content LIKE '%s'";
		$params[] = '%%' . $original_term . '%%';
		$params[] = '%%' . $original_term . '%%';

		// query keywords
		$string .= " OR ( wptt.taxonomy = 'wpkb-keyword' AND wpt.name LIKE '%s' )";
		$params[] = '%%' . $original_term . '%%';

		// query category
		$string .= " OR ( wptt.taxonomy = 'wpkb-category' AND wpt.name LIKE '%s' )";
		$params[] = '%%' . $original_term . '%%';

		// close opened AND parenthesis
		$string .= " )";

		// group by post id
		$string .= " GROUP BY wpp.id";

		// prepare sql query string
		$query = $wpdb->prepare( $string, $params );

		// execute query
		$results = $wpdb->get_results( $query );

		if( ! is_array( $results ) || count( $results ) === 0 ) {
			return array();
		}

		$ids = array_map( function( $p ) { return $p->id; }, $results );

		$posts = get_posts(
			array(
				'post_type' => Plugin::POST_TYPE_NAME,
				'post__in' => $ids
			)
		);

		return $posts;
	}



}