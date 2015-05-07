<?php
/*
Plugin Name: WP Knowledge Base
Version: 1.0.2
Plugin URI: https://mc4wp.com/kb/
Description: WordPress powered documentation for your products. Beautiful.
Author: Danny van Kooten
Author URI: https://dannyvankooten.com/
Text Domain: wp-knowledge-base
Domain Path: /languages/
License: GPL v3

WP Knowledge Base plugin
Copyright (C) 2014-2015, Danny van Kooten - support@dannyvankooten.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WPKB;

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

// define version
define( 'WPKB_VERSION', '1.0.2' );

// load composer autoloader
require __DIR__ . '/vendor/autoload.php';

// instantiate main plugin file
$GLOBALS['wpkb'] = $wpkb = new Plugin( WPKB_VERSION, __FILE__, __DIR__ );
$wpkb->add_hooks();

// load breadcrumbs
$breadcrumbs = new Breadcrumbs\Manager( $wpkb->get_option('custom_archive_page_id') );
$breadcrumbs->add_hooks();

// load search
$search = new Search( $wpkb );
$search->add_hooks();

// load code highlighter
$highlighting = new CodeHighlighting( $wpkb );
$highlighting->add_hooks();

// load template manager
add_action( 'template_redirect', function() use ( $wpkb ) {
	$template = new TemplateManager( $wpkb );
	$template->override_templates();
});

// Register [wpkb_list] shortcode
ArticleList::register_shortcode();