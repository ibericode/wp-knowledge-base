<?php

// prevent direct file access
defined( 'ABSPATH' ) or exit;

do_action( 'wpkb_before_archive' );

echo '[wpkb_search][wpkb_list]';

do_action( 'wpkb_after_archive' );