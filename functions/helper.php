<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Links to other related or required plugins.
 */
function dfrps_plugin_links( $plugin ) {
	$map = array(
		'dfrapi' => 'http://wordpress.org/plugins/datafeedr-api/',
		'importers' => admin_url( 'plugins.php' ),
	);
	return $map[$plugin];
} 

/**
 * Gets the next update time for a PS. 
 */
function dfrps_get_next_update_time() {
	$configuration = (array) get_option( DFRPS_PREFIX.'_configuration' );	
	if ( $configuration['update_interval'] == -1 ) {
		$time = date_i18n( 'U' );
	} else {
		$time = ( ( $configuration['update_interval'] * DAY_IN_SECONDS ) + date_i18n( 'U' ) );
	}
	return $time;
}

function dfrps_pagination( $data, $context ) {
		
	// Initialize $html variable.
	$html = '';
	
	// Return nothing if there are no products.
	if ( empty( $data['products'] ) ) {
		return $html;
	}
	
	// Begin pagination class.
	$html .= '<div class="dfrps_pagination">';
	
	$current_page 	= $data['page'];
	$limit 			= $data['limit'];
	$offset 		= $data['offset'];
	$found_count 	= $data['found_count'];
	$query 			= ( isset( $data['query'] ) ) ? $data['query'] : array();
	$hard_limit 	= dfrapi_get_query_param( $query, 'limit' );

	// Limit Found Count to hard limit if hard limit exists.
	if ( $hard_limit ) {
		if ( $found_count > $hard_limit['value'] ) {
			$found_count = $hard_limit['value'];
		}
	}
	
	// Maximum number of products.
	$max_num_products = ( ( $offset + count( $data['products'] ) ) > $found_count ) ? $found_count : ( $offset + count( $data['products'] ) );
			
	// Set total possible page.
	$total_possible_pages = ceil( $found_count / $limit );
	
	// Maximum number of pages.
	$max_total = 10000;
	$max_possible_pages = ceil ( $max_total / $limit );
	
	// Set total pages (if more pages that max_total value allows, adjust total).
	$total_pages = ( $max_possible_pages < $total_possible_pages ) ? $max_possible_pages : $total_possible_pages;
	
	// Number of relevant products.
	$relevant_results = ( $found_count > 10000 ) ? 10000 : $found_count;
	
	// "Showing 1 - 100 of 10,000 total relevant products found."
	$html .= '<div class="dfrps_pager_info">';
	$html .= __( 'Showing ', DFRPS_DOMAIN );
	$html .= '<span class="dfrps_pager_start">';
	$html .= number_format( ( 1 + $offset ) );
	$html .= '</span>';
	$html .= ' - ';
	$html .= '<span class="dfrps_pager_end">';
	$html .= number_format( $max_num_products );
	$html .= '</span>';
	$html .= __( ' of ', DFRPS_DOMAIN );
	$html .= '<span class="dfrps_relevant_results">';
	$html .= number_format( $relevant_results );
	$html .= '</span>';
	$html .= __( ' total products.', DFRPS_DOMAIN );
	$html .= '<span style="float:right"><a class="dfrps_delete_saved_search" href="#">' . __( 'Delete Saved Search', DFRPS_DOMAIN ) . '</a></span>';
	$html .= '</div>';
	
	// Return nothing if there are less than 2 pages.
	if ( $total_pages < 2 )  {
		$html .= '<div class="clearfix"></div>';
		$html .= '</div>'; // .dfrps_pagination
		return $html;
	}
	
	// There is more than 1 page. Start pager classes.
	$html .= '<div class="dfrps_pager_label_wrapper">';
	$html .= '<div class="dfrps_pager_label">' . __( 'Page', DFRPS_DOMAIN ) . '</div>';
	$html .= '</div>'; // .dfrps_pager_label_wrapper
	
	$html .= '<div class="dfrps_pager_links">';
	for ( $i=1; $i<=$total_pages; $i++ ) {
		if ( $i == $current_page ) {
			$html .= '<span><strong>' . $i . '</strong></span>';
		} else {
			$html .= '<span> <a href="#" class="dfrps_pager" page="' . $i . '" context="' . $context . '">' . $i . '</a> </span>';
		}
	}
	$html .= '<div class="clearfix"></div></div>'; // .dfrps_pager_links

	$html .= '</div>'; // .dfrps_pagination
	
	return $html;
}

function dfrps_format_product_list( $data, $context ) {

	$msg = '';
	
	// Get manually included product IDs.
	$manually_included_ids = get_post_meta( $data['postid'], '_dfrps_cpt_manually_added_ids', true );
	if ( !is_array( $manually_included_ids ) ) {
		$manually_included_ids = array();
	}
	$manually_included_ids = array_filter( $manually_included_ids );
	
	// Get manually included product IDs.
	$manually_blocked_ids = get_post_meta( $data['postid'], '_dfrps_cpt_manually_blocked_ids', true );
	if ( !is_array( $manually_blocked_ids ) ) {
		$manually_blocked_ids = array();
	}
	$manually_blocked_ids = array_filter( $manually_blocked_ids );
	
	//Get pagination.
	$pagination = dfrps_pagination( $data, $context );
	
	// Message on "Search" tab.
	if ( empty( $data ) ) {
		
		if ( $context == 'div_dfrps_tab_search' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'Click the [Search] button to view products that match your search.', DFRPS_DOMAIN );
			$msg .= '</div>';
		}
			
	} elseif ( empty( $data['products'] ) ) {
			
		if ( $context == 'div_dfrps_tab_search' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'No products matched your search.', DFRPS_DOMAIN );
			$msg .= '</div>';		
		}
	}
	
	
	if ( empty( $data ) || empty( $data['products'] ) ) {
	
		if ( $context == 'div_dfrps_tab_saved_search' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'You have not saved a search.', DFRPS_DOMAIN );
			$msg .= '</div>';		
		} elseif ( $context == 'div_dfrps_tab_included' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'You have not added any individual products to this Product Set.', DFRPS_DOMAIN );
			$msg .= '</div>';		
		} elseif ( $context == 'div_dfrps_tab_blocked' ) {
			$msg .= '<div class="dfrps_alert dfrps_alert-info">';
			$msg .= __( 'You have not blocked any products from this Product Set.', DFRPS_DOMAIN );
			$msg .= '</div>';		
		}
		
	} else {
	
		$args = array(
			'manually_included_ids' => $manually_included_ids,
			'manually_blocked_ids' => $manually_blocked_ids,
			'context' => $context,
		);
		
		if ( $context == 'div_dfrps_tab_search' ) {
			$msg .= '';
		} elseif ( $context == 'div_dfrps_tab_saved_search' ) {
			$msg .= '';			
		} elseif ( $context == 'div_dfrps_tab_included' ) {
			$msg .= '';		
		} elseif ( $context == 'div_dfrps_tab_blocked' ) {
			$msg .= '';			
		}
	}
		
	// Loop through products and display them.
	echo $msg;
	
	// Query info
	if ( isset( $data['params'] ) && !empty( $data['params'] ) ) { ?>
		<div class="dfrps_api_info" id="dfrps_raw_api_query">
			<div class="dfrps_head"><?php _e( 'API Reqest', DFRPS_DOMAIN ); ?></div>
			<div class="dfrps_query"><span><?php echo dfrapi_display_api_request( $data['params'] ); ?></span></div>
		</div>
	<?php }
	
	echo $pagination;	
	echo '<div class="product_list">';
	if ( isset( $data['products'] ) && !empty( $data['products'] ) ) {
		foreach ( $data['products'] as $product ) {
			dfrps_html_product_list( $product, $args );
		}
	}
	echo '</div>';
	echo $pagination;
	
}

function dfrps_more_info_rows( $product ) {

	$dfr_fields = array(
		'_id',
		'onsale', 
		'merchant_id',
		'time_updated',
		'time_created',
		'source_id',
		'feed_id',
		'ref_url',
	);

	ksort($product);
	$f=1;
	foreach ($product as $k => $v) {
		$class1 = ( $f % 2 ) ? 'even' : 'odd';
		$class2 = ( in_array( $k, $dfr_fields ) ) ? ' dfrps_data' : '';
		echo '<tr class="'.$class1.$class2.'">';
		echo '<td class="count">'.$f.'</td>';
		echo '<td class="field">'.str_replace( array("<",">"), array("&lt;","&gt;"), $k).'</td>';
		if ( $k == 'image' || $k == 'thumbnail' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="'.$v.'" target="_blank" title="'.__('Open image in new window.', DFRPS_DOMAIN).'">'.esc_attr( $v ).'</a>
				<br />
				<img src="'.$v.'" />
			</td>';
		} elseif ( $k == 'url' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="'.dfrapi_url( $product ).'" target="_blank" title="'.__('Open affiliate link in new window.', DFRPS_DOMAIN).'">'.esc_attr( dfrapi_url( $product ) ).'</a>
			</td>';	
		} elseif ( $k == 'ref_url' ) {
			echo '
			<td class="value dfrps_force_wrap">
				<a href="'.dfrapi_url( $product ).'" target="_blank" title="'.__('Open affiliate link in new window.', DFRPS_DOMAIN).'">'.esc_attr( dfrapi_url( $product ) ).'</a>
			</td>';	
		} else {
			echo '<td class="value dfrps_force_wrap">'.esc_attr( $v ).'</td>';		
		}
		echo '</tr>';
		$f++;
	}
}
 
/**
 * This *estimates* the percentage of completion
 * of a product set.  It's just an estimate.
 */
function dfrps_percent_complete( $set_id ) {

	$meta = get_post_custom( $set_id );
	
	$update_phase 	= intval( $meta['_dfrps_cpt_update_phase'][0] );
	$last_update 	= unserialize( $meta['_dfrps_cpt_previous_update_info'][0] );
	
	if ( $update_phase < 1 ) {
		return FALSE;
	}
	
	if ( $last_update['_dfrps_cpt_last_update_time_completed'][0] == 0 ) {
		// There is no last update info (no iterations). Return percentage based on update phase.
		$percent = round( ( $update_phase / 5 ) * 100 );
		return $percent;
	}
	
	$current_iteration 	= intval( $meta['_dfrps_cpt_update_iteration'][0] );
	$total_iterations 	= intval( $last_update['_dfrps_cpt_update_iteration'][0] );
	
	if ( $total_iterations > 0 ) {
		if ( $current_iteration <= $total_iterations ) {
			$percent = round( ( $current_iteration / $total_iterations ) * 100 );
			return $percent;
		} else {
			return 101;
		}
	}
	
	return FALSE;
}

function dfrps_progress_bar( $percent ) {	
	
	if ( !$percent ) {
		return '';
	}
		
	if ( $percent <= 100 ) {
	
		return '
		<div id="dfrps_dynamic_progress_bar">
			<div><small>' . $percent . '% ' . __( 'complete', DFRPS_DOMAIN ) . '</small></div>
			<div class="dfrps_progress">
				<div class="dfrps_progress-bar dfrps_progress-bar-success" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $percent . '%">
					<span class="dfrps_sr-only">' . $percent . '% ' . __( 'complete', DFRPS_DOMAIN ) . '</span>
				</div>
			</div>
		</div>
		';
		
	} else {
	
		return '
		<div id="dfrps_dynamic_progress_bar">
			<div><small><em>' . __( 'Unknown % complete', DFRPS_DOMAIN ) . '</em></small></div>
		</div>
		';
	
	}
}

/**
 * Adds a Product ID to an existing or new postmeta value.
 */
function dfrps_helper_add_id_to_postmeta( $product_id, $post_id, $meta_key ) {
	
	// Get all Product IDs already stored for this $meta_key.
	$product_ids = get_post_meta( $post_id, $meta_key, true );
	
	// Add new $product_id to array of Product IDs.
	if ( !empty( $product_ids ) ) {
		array_unshift( $product_ids, $product_id );
	} else {
		$product_ids = array( $product_id ); 
	}
	
	// Remove any empty array values.
	$product_ids = array_filter( $product_ids );
	
	// Update post meta.
	update_post_meta( $post_id, $meta_key, $product_ids );
}

/**
 * Removes a Product ID from an existing postmeta value.
 */
function dfrps_helper_remove_id_from_postmeta( $product_id, $post_id, $meta_key ) {
	
	// Get all Product IDs already stored for this $meta_key.
	$product_ids = get_post_meta( $post_id, $meta_key, true );
	
	if ( !is_array( $product_ids ) ) { return; }
	
	// Remove Product ID from $product_ids array.
   	$product_ids = array_diff( $product_ids, array( $product_id ) );
	
	// Remove any empty array values.
	$product_ids = array_filter( $product_ids );
	
	// Update post meta.
	update_post_meta( $post_id, $meta_key, $product_ids );
}

/**
 * This returns the text "Saving..." to JS.
 */
function dfrps_helper_js_text( $str ) {
	if ( $str == 'saving' ) {
		return __("Saving...", DFRPS_DOMAIN);
	} elseif ( $str == 'searching' ) {
		return __("Searching...", DFRPS_DOMAIN);
	} elseif ( $str == 'search' ) {
		return __("Search", DFRPS_DOMAIN);
	} elseif ( $str == 'deleting' ) {
		return __("Deleting...", DFRPS_DOMAIN);
	}
}

function dfrps_helper_include_product( $pid, $args ) {
	
	// Product has already been included?
	if ( in_array( $pid, $args['manually_included_ids'] ) ) {
		
		// What's the context of this page?
		if ( $args['context'] == 'div_dfrps_tab_search' ) {
			dfrps_html_included_product_icon();	// Search page, display "checkmark" icon.
		} elseif ( $args['context'] == 'div_dfrps_tab_included' ) {
			dfrps_html_remove_included_product_link( $pid ); // Included page, display "minus" icon/link.		
		}
		
	// Product has NOT already been included?
	} else {
		if ( $args['context'] != 'blocked' && $args['context'] != 'saved_search' ) {
			dfrps_html_include_product_link( $pid ); // Not already included and we're not in the "blocked" context, display "add" icon/link.
		}
	}
	
}

function dfrps_helper_block_product( $pid, $args ) {
	
	// Product has already been blocked?
	if ( in_array( $pid, $args['manually_blocked_ids'] ) ) {
	
		// What's the context of this page?
		if ( $args['context'] == 'div_dfrps_tab_blocked' ) {
			dfrps_html_unblock_product_link( $pid ); // Product is blocked, display "unblock" icon/link.
		}
		
	// Product has NOT already been blocked?
	} else {
		if ( $args['context'] != 'div_dfrps_tab_included' ) {
			dfrps_html_block_product_link( $pid ); // Not already blocked, display "block" icon/link.
		}
	}
	
}

function dfrps_date_in_two_rows( $date ) {
	if ( is_numeric( $date ) ) {
		//$html  = date('M d, G:i', $date );
		$html  = '<div>' . date('M j', $date ) . ' ' . date('g:ia', $date ) . '</div>';
	} else {
		//$html  = date('M d, G:i', strtotime( $date ) );
		$html  = '<div>' . date('M j', strtotime( $date ) ) . ' ' . date('g:ia', strtotime( $date ) ) . '</div>';
	}
	return $html;
}

function dfrps_registered_cpt_exists() {
	$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
	$num_registered_cpts = count( $registered_cpts );
	if ( $num_registered_cpts > 0 ) {
		return TRUE;
	}
	return FALSE;
}

function dfrps_default_cpt_is_selected() {
	$config = get_option( 'dfrps_configuration', array() );
	$default_cpt = $config['default_cpt'];
	if ( !is_array( $default_cpt ) ) {
		$default_cpt = array( $default_cpt );
	}
	$default_cpt = array_filter( $default_cpt );
	
	if ( !empty( $default_cpt ) ) {
		return TRUE;
	}
	return FALSE;
}		

function dfrps_set_html_content_type() {
	return 'text/html';
}

function dfrps_reset_product_set_update( $set_id ) {
	
	// Update phase/added/deleted.
	update_post_meta( $set_id, '_dfrps_cpt_update_phase', 0 );
	
	// Delete first passes.
	for( $i=1; $i<=10; $i++ ) {
		delete_post_meta( $set_id, '_dfrps_cpt_update_phase' . $i . '_first_pass' );
	}
}
