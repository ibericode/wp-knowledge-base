<?php

namespace WPDocs\Breadcrumbs;

class Manager {

	public function __construct() {
		add_filter( 'wpdocs_extensions', array( $this, 'register_extension' ) );
	}

	/**
	 * @param array $extensions
	 *
	 * @return array
	 */
	public function register_extension( array $extensions ) {
		$extensions[] = 'breadcrumb';
		return $extensions;
	}

}