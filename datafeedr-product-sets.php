<?php
/*
Plugin Name: Datafeedr Product Sets
Plugin URI: https://v4.datafeedr.com
Description: Build sets of products to import into your website. <strong>REQUIRES: </strong><a href="http://wordpress.org/plugins/datafeedr-api/">Datafeedr API plugin</a> and an <a href="http://wordpress.org/plugins/tags/dfrpsimporter">importer plugin</a>.
Author: datafeedr.com
Author URI: https://v4.datafeedr.com
License: GPL v3
Requires at least: 3.8
Tested up to: 4.4-beta1
Version: 1.2.7

Datafeedr Product Sets Plugin
Copyright (C) 2014, Datafeedr - help@datafeedr.com

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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define constants.
 */
define( 'DFRPS_VERSION', 	'1.2.7' );
define( 'DFRPS_DB_VERSION', '1.2.0' );
define( 'DFRPS_SET_VERSION','1.2.0' );
define( 'DFRPS_URL', 		plugin_dir_url( __FILE__ ) );
define( 'DFRPS_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'DFRPS_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'DFRPS_DOMAIN', 	'datafeedr-product-sets' );
define( 'DFRPS_CPT', 		'datafeedr-productset' );
define( 'DFRPS_PREFIX', 	'dfrps' );

/**
 * Loads required function files.
 */
require_once( DFRPS_PATH . 'functions/functions.php' );

/**
 * Import the post's image.
 *
 * Instantiate the Dfrps_Image_Importer class and pass a $post object
 * for image processing. This loads on all page loads so that images 
 * will be uploaded even when on the frontend of the site.
 *
 * @since 1.1.10
 *
 * @param object $post A $post object for the post we want to import an image for.
 */	
add_action( 'the_post', 'dfrps_import_image' );
function dfrps_import_image( $post ) {
	if ( ! class_exists( 'Dfrps_Image_Importer' ) ) {
		require_once( DFRPS_PATH . 'classes/class-dfrps-image-importer.php' );
	}
	new Dfrps_Image_Importer( $post );
}

/**
 * Notify user that the Datafeedr API plugin is missing and is required.
 */
add_action( 'admin_notices', 'dfrps_dfrapi_missing' );
function dfrps_dfrapi_missing() {
	if ( !defined( 'DFRAPI_BASENAME' ) ) {
		echo '<div class="update-nag">' . __( 'The <strong>Datafeedr Product Sets</strong> plugin requires that the <strong>Datafeedr API</strong> plugin be installed and activated.', DFRPS_DOMAIN );
		echo ' <a href="http://wordpress.org/plugins/datafeedr-api/">';
		echo  __( 'Download the Datafeedr API Plugin', DFRPS_DOMAIN );
		echo '</a></div>';	
	}
}

/**
 * Notify user that an Importer plugin is missing and is required.
 */
add_action( 'admin_notices', 'dfrps_missing_importer' );
function dfrps_missing_importer() {
	if ( !dfrps_registered_cpt_exists() ) {
		echo '<div class="update-nag" style="border-color: red;">' . __( 'The <strong>Datafeedr Product Sets</strong> plugin requires an importer plugin.', DFRPS_DOMAIN );
		echo ' <a href="http://wordpress.org/plugins/tags/dfrpsimporter">';
		echo  __( 'Download an Importer Plugin', DFRPS_DOMAIN );
		echo '</a></div>';		
	}
}

/**
 * Notify user if a default CPT hasn't been selected.
 */
add_action( 'admin_notices', 'dfrps_default_cpt_not_selected' );
function dfrps_default_cpt_not_selected() {
	if ( !dfrps_default_cpt_is_selected() ) {
		echo '<div class="update-nag" style="border-color: red;">' . __( 'The <strong>Datafeedr Product Sets</strong> plugin requires you to', DFRPS_DOMAIN );
		echo ' <a href="' . admin_url( 'admin.php?page=dfrps_configuration' ) . '">';
		echo  __( 'select a Default Custom Post Type', DFRPS_DOMAIN );
		echo '</a>.</div>';		
	}
}

/**
 * Notify user that updates are disabled.
 */
add_action( 'admin_notices', 'dfrps_updates_disabled' );
function dfrps_updates_disabled() {
	$options = get_option( 'dfrps_configuration', array() );
	if ( isset( $options['updates_enabled'] ) && $options['updates_enabled'] == 'disabled' ) {
		echo '<div class="update-nag" style="border-color: red;">' . __( 'The <strong>Datafeedr Product Sets</strong> plugin has disabled Product Set updates. Enable Product Set updates ', DFRPS_DOMAIN );
		echo ' <a href="' . admin_url( 'admin.php?page=dfrps_configuration' ) . '">';
		echo  __( 'here', DFRPS_DOMAIN );
		echo '</a>.</div>';		
	}
}

/**
 * Notify user that their version of DFRPS is not compatible with their version of DFRPSWC
 */
add_action( 'admin_notices', 'dfrps_not_compatible_with_dfrpscwc' );
function dfrps_not_compatible_with_dfrpscwc() {
	if ( defined( 'DFRPSWC_VERSION' ) )  {
		if ( version_compare( DFRPSWC_VERSION, '1.2.0', '<' ) ) {

			$file = 'datafeedr-woocommerce-importer/datafeedr-woocommerce-importer.php';
			$url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file );

			?>
			
			<div class="error">
				<p>
					<strong style="color:#E44532;"><?php _e( 'URGENT - ACTION REQUIRED!', DFRPS_DOMAIN ); ?></strong>
					<br /><?php _e( 'Your version of the <strong><em>Datafeedr WooCommerce Importer</em></strong> plugin is not compatible with your version of the <strong><em>Datafeedr Product Sets</em></strong> plugin.', DFRPS_DOMAIN ); ?>
					<br /><?php _e( 'Failure to upgrade will result in data loss. Please update your version of the <strong><em>Datafeedr WooCommerce Importer</em></strong> plugin now.', DFRPS_DOMAIN ); ?>
					<br /><a class="button button-primary button-large" style="margin-top: 6px" href="<?php echo $url; ?>"><?php _e( 'Update Now', DFRPS_DOMAIN ); ?></a>
				</p>
			</div>
		
			<?php
		}
	}
}

/**
 * Upon plugin activation.
 */
register_activation_hook( __FILE__, 'dfrps_activate' );
function dfrps_activate() {
	dfrps_add_capabilities();
	
	// Add default options if they do not already exist. @since 1.2.1
	$dfrps_configuration = get_option( 'dfrps_configuration', false );
	if ( ! $dfrps_configuration ) {
		require_once( DFRPS_PATH . 'classes/class-dfrps-configuration.php' );
		$default_options = Dfrps_Configuration::default_options();
		add_option( 'dfrps_configuration', $default_options );
	}
}

/**
 * Add new capabilities to "administrator" role.
 */
function dfrps_add_capabilities() {
	$role = get_role( 'administrator' );
	$role->add_cap( 'edit_product_set' );
	$role->add_cap( 'read_product_set' );
	$role->add_cap( 'delete_product_set' );
	$role->add_cap( 'edit_product_sets' );
	$role->add_cap( 'edit_others_product_sets' );
	$role->add_cap( 'publish_product_sets' );
	$role->add_cap( 'read_private_product_sets' );
	$role->add_cap( 'delete_product_sets' );
	$role->add_cap( 'delete_private_product_sets' );
	$role->add_cap( 'delete_published_product_sets' );
	$role->add_cap( 'delete_others_product_sets' );
	$role->add_cap( 'edit_private_product_sets' );
	$role->add_cap( 'edit_published_product_sets' );
	$role->add_cap( 'edit_product_sets' );
}

/**
 * Build CPT
 */
add_action( 'init', 'dfrps_create_post_type' );
function dfrps_create_post_type() {
	
	$labels = array(
		'name' 					=> _x( 'Product Sets', DFRPS_DOMAIN ),
		'singular_name' 		=> _x( 'Product Set', DFRPS_DOMAIN ),
		'add_new' 				=> _x( 'Add New Product Set', DFRPS_DOMAIN ),
		'all_items' 			=> _x( 'All Product Sets', DFRPS_DOMAIN ),
		'add_new_item' 			=> _x( 'Add New Product Set', DFRPS_DOMAIN ),
		'edit_item' 			=> _x( 'Edit Product Set', DFRPS_DOMAIN ),
		'new_item' 				=> _x( 'New Product Set', DFRPS_DOMAIN ),
		'view_item' 			=> _x( 'View Product Set', DFRPS_DOMAIN ),
		'search_items' 			=> _x( 'Search Product Sets', DFRPS_DOMAIN ),
		'not_found' 			=> _x( 'No Product Sets found', DFRPS_DOMAIN ),
		'not_found_in_trash' 	=> _x( 'No Product Sets found in trash', DFRPS_DOMAIN ),
		'parent_item_colon' 	=> _x( 'Parent Product Set:', DFRPS_DOMAIN ),
		'menu_name' 			=> _x( 'Product Sets', DFRPS_DOMAIN )
	);
	
	$args = array(
		'labels' 				=> $labels,
		'description' 			=> "These store saved searches and individual products as product sets.",
		'public' 				=> true,
		'exclude_from_search'	=> true,
		'publicly_queryable' 	=> false,
		'show_ui' 				=> true, 
		'show_in_nav_menus' 	=> true, 
		'show_in_menu' 			=> 'dfrps',
		'show_in_admin_bar' 	=> true,
		'menu_position' 		=> 20,
		'menu_icon' 			=> null,
		'capability_type' 		=> 'product_set',
		'map_meta_cap'			=> true,
		'hierarchical' 			=> true,
		'supports' 				=> array( 'title' ),
		'has_archive' 			=> false,
		'rewrite' 				=> false,
		'query_var' 			=> false,
		'can_export' 			=> true
	);

	register_post_type( DFRPS_CPT, $args );
}

/**
 * Load files only if we're in the admin section of the site.
 */
if ( is_admin() ) {
	if ( defined( 'DFRAPI_BASENAME' ) ) {
		require_once ( DFRPS_PATH . 'classes/class-dfrps-initialize.php' );
	}
}