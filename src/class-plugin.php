<?php

namespace WPKB;

final class Plugin {

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