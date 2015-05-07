<?php

// prevent direct file access
defined( 'ABSPATH' ) or exit;

do_action( 'wpkb_before_category_archive' );

term_description( );
echo '[wpkb_list category="'. $this->queried_object->name .'"]';
echo '<h4>'. __( 'Search all categories', 'wp-knowledge-base' ) .'</h4>';
echo '[wpkb_search]';

do_action( 'wpkb_after_category_archive' );