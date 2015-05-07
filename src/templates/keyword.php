<?php

// prevent direct file access
defined( 'ABSPATH' ) or exit;

do_action( 'wpkb_before_keyword_archive' );

echo '[wpkb_list keyword="'. $this->queried_object->name . '"]';
echo '<h4>'. __( 'Search all keywords', 'wp-knowledge-base' ) .'</h4>';
echo '[wpkb_search]';

do_action( 'wpkb_after_keyword_archive' );
