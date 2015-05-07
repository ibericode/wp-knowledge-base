<?php

// prevent direct file access
defined( 'ABSPATH' ) or exit;

do_action( 'wpkb_before_article_content' );

echo $content;

do_action( 'wpkb_after_article_content' );

// add block with related doc articles to end of article
$terms = wp_get_object_terms( $this->queried_object->ID, WPKB\Plugin::TAXONOMY_CATEGORY_NAME );
if( $terms && isset( $terms[0]->name ) ) {
	$title = sprintf( __( 'Other articles in %s', 'wp-knowledge-base' ), $terms[0]->name );
	echo '[wpkb_list title="'. $title .'" category="'. $terms[0]->slug .'" exclude="'. $this->queried_object->ID .'"]';
}



