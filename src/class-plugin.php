<?php

namespace WPKB;

final class Plugin {

	/**
	 * @const string
	 */
	const POST_TYPE_SLUG = WPKB_POST_TYPE_SLUG;

	/**
	 * @const string Slug of the post type
	 */
	const POST_TYPE_NAME = 'wpkb-article';

	/**
	 * @const string Slug of category taxonomy
	 */
	const TAXONOMY_CATEGORY_NAME = 'wpkb-category';

	/**
	 * @const string Slug of keyword taxonomy
	 */
	const TAXONOMY_KEYWORD_NAME = 'wpkb-keyword';

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $file;

	/**
	 * @var string
	 */
	private $dir;

	/**
	 * Constructor
	 *
	 * @param string $version
	 * @param string $file
	 * @param string $dir
	 */
	public function __construct( $version, $file, $dir ) {
		$this->version = $version;
		$this->file = $file;
		$this->dir = $dir;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		// add actions
		add_action( 'init', array( $this, 'init' ), 1);

		// register (de)activation hooks
		register_activation_hook( $this->file, array( $this, 'on_plugin_activation' ) );
		register_deactivation_hook( $this->file, array( $this, 'on_plugin_deactivation' ) );
	}

	/**
	 * Registers all terms, taxonomy's and post types.
	 */
	public function init() {

		$this->register_taxonomies();
		$this->register_post_type();
	}

	/**
	 * Register KB taxonomies
	 */
	protected function register_taxonomies() {
		$labels = array(
			'name'              => __( 'KB Keywords', 'wp-knowledge-base' ),
			'singular_name'     => __( 'KB Keyword', 'wp-knowledge-base' ),
			'menu_name'         => __( 'KB Keywords' )
		);

		// register docs taxonomy: keyword
		register_taxonomy(
			self::TAXONOMY_KEYWORD_NAME,
			self::POST_TYPE_NAME,
			array(
				'labels' => $labels,
				'rewrite' => array(
					'with_front' => false,
					'slug' => self::POST_TYPE_SLUG . '/keyword'
				),
				'hierarchical' => false,
			)
		);

		register_taxonomy_for_object_type( self::TAXONOMY_KEYWORD_NAME, self::POST_TYPE_NAME );

		$labels = array(
			'name'              => __( 'KB Categories', 'wp-knowledge-base' ),
			'singular_name'     => __( 'KB Category', 'wp-knowledge-base' ),
			'menu_name'         => __( 'KB Categories' )
		);

		// register docs taxonomy: category
		register_taxonomy(
			self::TAXONOMY_CATEGORY_NAME,
			self::POST_TYPE_NAME,
			array(
				'labels' => $labels,
				'rewrite' => array(
					'with_front' => false,
					'slug' => self::POST_TYPE_SLUG . '/category'
				),
				'hierarchical' => true,
				'query_var' => true
			)
		);
		register_taxonomy_for_object_type( self::TAXONOMY_CATEGORY_NAME, self::POST_TYPE_NAME );

	}

	/**
	 * Register KB post type
	 */
	protected function register_post_type() {

		$labels = array(
			'name'               => _x( 'KB Articles', 'post type general name', 'wp-knowledge-base' ),
			'singular_name'      => _x( 'KB Article', 'post type singular name', 'wp-knowledge-base' ),
			'new_item'           => __( 'New KB Article', 'wp-knowledge-base' ),
			'update_item'        => __( 'Update KB Article', 'wp-knowledge-base' ),
			'edit_item'          => __( 'Edit KB Article', 'wp-knowledge-base' ),
			'add_new_item'       => __( 'Add new KB Article', 'wp-knowledge-base' )
		);

		// register docs post type
		register_post_type(
			self::POST_TYPE_NAME,
			array(
				'public' => true,
				'labels' => $labels,
				'hierarchical' => true,
				'rewrite' => array( 'with_front' => false, 'slug' => self::POST_TYPE_SLUG ),
				'taxonomies' => array( self::TAXONOMY_CATEGORY_NAME, self::TAXONOMY_KEYWORD_NAME ),
				'has_archive' => true,
				'menu_icon'   => 'dashicons-info',
				'supports' => array( 'title', 'editor', 'author', 'revisions', 'custom-fields' ) //todo: finish migration to comments API & use that interface
			)
		);
	}

	/**
	 * Make sure rewrite rules are flushed on plugin activation
	 */
	public function on_plugin_activation() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}

	/**
	 * Make sure rewrite rules are flushed again on plugin deactivation
	 */
	public function on_plugin_deactivation() {
		add_action( 'shutdown', 'flush_rewrite_rules' );
	}


	/**
	 * Return al WPKB extensions
	 *
	 * @return Plugin
	 */
	public function extensions() {
		$extensions = apply_filters( 'wpkb_extensions', array() );
		return (array) $extensions;
	}

	/**
	 * Return al WPKB extensions
	 *
	 * @return Plugin
	 */
	public function extension( $slug ) {
		$extensions = $this->extensions();

		if( isset( $extensions[ $slug ] ) ) {
			return $extensions[ $slug ];
		}

		return null;
	}


	/**
	 * @return string
	 */
	public function version() {
		return $this->version;
	}

	/**
	 * @return mixed
	 */
	public function file() {
		return $this->file();
	}

	/**
	 * @return mixed
	 */
	public function dir() {
		return $this->dir;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function url( $path = '' ) {
		return plugins_url( $path, $this->file );
	}
}