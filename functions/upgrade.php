<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * BEGIN UPGRADE.
 */

/**
 * Get current version of plugin.
 */
$current_version = get_option( 'dfrps_version', DFRPS_VERSION );

/**
 * If previous version does not match current version.
 */
if ( version_compare( $current_version, DFRPS_VERSION, '!=' ) ) {
	dfrps_reset_update();
}

/**
 * Stop & reset any Product Set updates.
 */
function dfrps_reset_update() {

	global $wpdb;

	// Get currently updating product set ID.
	$set_id = $wpdb->get_var( "
		SELECT post_id 
		FROM $wpdb->postmeta
		WHERE meta_key = '_dfrps_cpt_update_phase'
		AND meta_value > '0'
	" );

	if ( ! is_null( $set_id ) ) {
		dfrps_reset_product_set_update( $set_id );
	}
}

/**
 * Now that any upgrade functions are performed, update version in database.
 * 
 * This should be the last action on this page. 
 * 
 * DO NOT PLACE ANY CODE AFTER THIS LINE!
 */
update_option( 'dfrps_version', DFRPS_VERSION );

/**
 * END UPGRADE.
 */