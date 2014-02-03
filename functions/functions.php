<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
		
/**
 * Load generic helper functions.
 */
require_once( DFRPS_PATH . 'functions/helper.php' );

/**
 * Load cron functions. This loads on all page loads.
 */
require_once( DFRPS_PATH . 'functions/cron.php' );

/**
 * Load integration functions. This loads on all page loads.
 */
require_once( DFRPS_PATH . 'functions/integration.php' );

/**
 * Load image functions. 
 * This loads on all page loads so that images will be uploaded
 * even when on the frontend of the site.
 */
require_once( DFRPS_PATH . 'functions/image.php' );

