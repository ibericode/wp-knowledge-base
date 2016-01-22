<?php

namespace WPKB;


/**
 * Class Options
 *
 * TODO: Implement ArrayAccess?
 *
 * @package WPKB
 * @ignore
 */
class Options {

	/**
	 * @var string
	 */
	private $option_key;

	/**
	 * @var array
	 */
	private $defaults = array();

	/**
	 * @var array
	 */
	private $options;

	/**
	 * Options constructor.
	 *
	 * @param string $option_key
	 */
	public function __construct( $option_key, $defaults = array() ) {
		$this->option_key = $option_key;
		$this->defaults = $defaults;
		$this->load();
	}

	/**
	 * Load options from database
	 *
	 * @return void
	 */
	private function load() {
		$options = get_option( $this->option_key, array() );

		if( ! is_array( $options ) ) {
			$options = array();
		}

		// merge options with defaults
		$this->options = array_merge( $this->defaults, $options );
	}

	/**
	 * @param string $index
	 * @param null $default
	 *
	 * @return null
	 */
	public function get( $index, $default = null ) {

		if( isset( $this->options[ $index ] ) ) {
			return $this->options[ $index ];
		}

		return $default;
	}

}