<?php
/*
Plugin Name: WP Knowledge Base
Version: 1.2
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
define( 'WPKB_VERSION', '1.2' );

// load composer autoloader
require __DIR__ . '/vendor/autoload.php';


// load constants, filters, actions & shortcodes
require __DIR__ . '/src/constants.php';
require __DIR__ . '/src/default-actions.php';
require __DIR__ . '/src/default-filters.php';
require __DIR__ . '/src/shortcodes.php';

// instantiate object tree
global $wpkb;

$wpkb = wpkb();

$wpkb['plugin'] = $plugin = new Plugin( WPKB_VERSION, __FILE__, __DIR__ );
$wpkb['options'] = $options = new Options( 'wpkb', array(
	'custom_archive_page_id' => 42540
	)
);
$wpkb['breadcrumbs'] = $breadcrumbs = new Breadcrumbs\Manager( $options->get( 'custom_archive_page_id' ) );
$wpkb['search'] = $search = new Search( $plugin );
$wpkb['categories'] = new Term_List( Plugin::TAXONOMY_CATEGORY_NAME );
$wpkb['keywords'] = new Term_List( Plugin::TAXONOMY_KEYWORD_NAME );

$highlighting = new CodeHighlighting( $plugin );
$callouts = new Callouts();
$rating = new Rating\Rater();

// hook!
$plugin->add_hooks();
$breadcrumbs->add_hooks();
$search->add_hooks();
$highlighting->add_hooks();
$callouts->add_hooks();
$rating->add_hooks();

// load admin specific code
if( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	$admin = new Admin();
	$admin->add_hooks();

	$rating_admin = new Rating\Admin( $rating );
	$rating_admin->add_hooks();
}

// load template specific stuff
add_action( 'template_redirect', function() use ( $options ) {
	$template = new TemplateManager( $options->get( 'custom_archive_page_id' ) );
	$template->override_templates();
});