<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * BEGIN
 * 
 * Get previous version stored in database.
 */
$previous_version = get_option( 'dfrps_version', FALSE );




/**
 * Upgrading to version 1.10.0
 * 
 * We just check for 'false' here because before v1.10.0
 * we were not storing the 'dfrps_version' in the database.
 */
if ( !$previous_version ) {
    
    global $wpdb;
        
    // Get currently updating product set ID.
    $set_id = $wpdb->get_var( "
		SELECT post_id 
		FROM $wpdb->postmeta
		WHERE meta_key = '_dfrps_cpt_update_phase'
		AND meta_value > '0'
	" );
		
	if ( !is_null( $set_id ) ) {
		/**
		 * Reset update for this Product Set because
		 * we are changing the update class to add products first
		 * to temporary product table and then inserting from 
		 * that table into the custom post type (ie. product).
		 */
    	dfrps_reset_product_set_update( $set_id );
    }
    
}







/**
 * END
 * 
 * Now that any upgrade functions are performed, update version in database.
 */
add_option( 'dfrps_version', DFRPS_VERSION );
