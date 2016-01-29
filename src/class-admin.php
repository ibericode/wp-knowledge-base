<?php

namespace WPKB;

use WP_Post;

class Admin {

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * Construct
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_items' ) );
		add_action( 'save_post_wpkb-article', array( $this, 'save_kb_article' ), 10, 2 );
		add_action( 'add_meta_boxes_wpkb-article', array( $this, 'add_meta_box') );
	}

	/**
	 * Add metabox with Article Settings to wpkb-article edit screen.
	 *
	 * @param WP_Post $post
	 */
	public function add_meta_box( $post ) {
		add_meta_box(
			'wpkb-article-settings',
			__( 'Article Settings', 'wp-knowledge-base' ),
			array( $this, 'show_meta_box' ),
			'wpkb-article',
			'advanced',
			'high'
		);
	}

	/**
	 * @param $settings
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		$sanitized = $settings;

		if( $settings['custom_archive_page_id'] !== $this->options->get( 'custom_archive_page_id' ) ) {
			register_shutdown_function( 'flush_rewrite_rules' );
		}

		return $sanitized;
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'wpkb_options', 'wpkb', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Add menu items
	 */
	public function add_menu_items() {
		add_submenu_page( 'edit.php?post_type=wpkb-article', __( 'Settings', 'wp-knowledge-base' ), __( 'Settings', 'wp-knowledge-base' ), 'manage_options', 'wpkb-settings', array( $this, 'show_settings_page' ) );
	}

	/**
	 * Render the settings page
	 */
	public function show_settings_page() {
		$opts = $this->options;
		require dirname( WPKB_FILE ) . '/views/settings-page.php';
	}

	/**
	 * @param WP_Post $post
	 */
	public function show_meta_box( $post ) {
		$opts = $this->options;
		require dirname( WPKB_FILE ) . '/views/meta-box.php';
	}

	/**
	 * Runs when saving a KB article
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @return boolean
	 */
	public function save_kb_article( $post_id, $post ) {

		if( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return false;
		}

		// Save settings
		$hidden_from_archive = isset( $_POST['hidden_from_archive'] ) && $_POST['hidden_from_archive'];
		$hidden_from_archive ?  update_post_meta( $post_id, 'hidden_from_archive', 1 ) : delete_post_meta( $post_id, 'hidden_from_archive' );

		// Sets a global "last_modified" option that tracks changes to articles in Knowledge Base.
		update_option( 'wpkb_last_modified', time() );

		return true;
	}



}