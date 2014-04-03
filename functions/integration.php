<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Functions for integration DFRPS with importing plugins.
 */

function dfrps_register_cpt( $post_type, $args ) {
	
	$cpts = get_option( 'dfrps_registered_cpts', array() );
	
	// If $cpts is empty, set this CPT as the default CPT
	if ( !$cpts || empty( $cpts ) ) {
		$configuration = get_option( 'dfrps_configuration', array() );
		$configuration['default_cpt'] = $post_type;
		update_option( 'dfrps_configuration', $configuration );
	}
	
	$args['post_type'] = $post_type;
	$cpts[$post_type] = $args;
	update_option( 'dfrps_registered_cpts', $cpts );
}

function dfrps_unregister_cpt( $post_type ) {

	$cpts = get_option( 'dfrps_registered_cpts', array() );
	unset( $cpts[$post_type] );
	
	// Update default_cpt if there are other CPTs available.
	// Otherwise the user will see the "install importer plugin" nag.
	if ( !empty( $cpts ) ) {
		$configuration = get_option( 'dfrps_configuration', array() );
		$default_cpt = $configuration['default_cpt'];
		
		// The CPT being unregistered is the default CPT.  So update the default CPT with another arbitrary CPT.
		if ( $default_cpt == $post_type ) {
			foreach ( $cpts as $type => $values ) {
				$configuration['default_cpt'] = $type;
				update_option( 'dfrps_configuration', $configuration );
				break;
			}
		}
	}
	update_option( 'dfrps_registered_cpts', $cpts );
}

/**
 * Adds new terms and 
 * Gets existing term IDs.
 * 
 * ### EXAMPLE USAGE ###
 * 
 * 
 	$taxonomy = 'product_cat';
	$paths = array (
		'Fruit/Apple',
		'Pets/Large Pets/Cats',
		'Farm Animals/Pigs',
		'Farm Animals/Cows',
		'Vegetable/Asparagus',
		'Vegetable/Broccoli',
		'Vegetable/Lettuce',
		'Food/Vegetable',
		'Food/Vegetable/Asparagus',
		'Food/Meat/Pigs',
		'Food/Meat/Cows',
	);
	$ids = dfrps_add_term( $taxonomy, $paths );
 * 
 * 
 */
function dfrps_add_term( $taxonomy, $paths ) {

	$ids = array();
	$all_ids = array();
	
	foreach( $paths as $path ) {
		           
		$names = explode('/', $path);
		$num_names = count( $names );

		for( $depth=0; $depth<$num_names; $depth++ ) {
		                                
			$parent = ( $depth > 0 ) ? $ids[( $depth - 1 )] : '';
			$term = term_exists( $names[$depth], $taxonomy, $parent );
			
			// Insert term.
			if ( $term === 0 || $term === null || $term === false ) {
				
				$args = ( $depth > 0 ) ? array( 'parent' => $ids[( $depth - 1 )]) : array();
				$term = wp_insert_term( $names[$depth], $taxonomy, $args );
			}

			if ( is_array( $term ) ) {
				$ids[$depth] = intval( $term['term_id'] );
			}
			
		}
		
		$all_ids = array_unique( array_merge( $all_ids, $ids ) );
	}
	
	return $all_ids;
}

/**
 * This gets the category IDs for the current post (ie. the product
 * that was already imported into the database) and removes the
 * IDs from the post.
 * 
 * Why?
 * 
 * We need to delete this set's current category IDs from this product
 * so that at the end of the update, if this product isn't re-imported
 * during the update, the post/product's category information (for this
 * set) will no longer be available so that if this post/product was 
 * added via another Product Set, only that Product Set's category IDs
 * will be attributed to this post/product.
 */
function dfrps_remove_category_ids_from_post( $post_id, $set, $cpt, $taxonomy ) {
	
	// Get the category IDs associated with this Set.
	$this_sets_categories = unserialize( $set['postmeta']['_dfrps_cpt_categories_history'][0] );
	$this_sets_categories =  $this_sets_categories[$cpt];
	
	if ( is_array( $this_sets_categories ) && !empty( $this_sets_categories ) ) {
		$terms = array();
		foreach ( $this_sets_categories as $term ) {
			$terms[] = intval( $term );
		}
		wp_remove_object_terms( $post_id, $terms, $taxonomy );
	}
}

function dfrps_get_all_post_ids_by_set_id( $set_id ) {
	
	global $wpdb;
	
	$set_id = intval( $set_id );
	
	if ( $set_id < 1 ) {
		return array();
	}
	
	$posts = $wpdb->get_results( "
		SELECT post_id AS ID
		FROM $wpdb->postmeta
		WHERE meta_key = '_dfrps_product_set_id'
		AND meta_value = " . $set_id . "
	", ARRAY_A );
	
	if ( $posts == NULL ) {	
		return array();
	}
	
	$ids = array();
	foreach ( $posts as $post ) {
		$ids[] = $post['ID'];
	}
	
	return $ids;
}

/**
 * Returns post ARRAY if post already exists.
 * Returns FALSE if post does not exist.
 * 
 * $product - array of product information.
 * $set - array of Product Set.
 */
function dfrps_get_existing_post( $product, $set ) { 
	
	global $wpdb;
	
	$import_into = get_post_meta( $set['ID'], '_dfrps_cpt_import_into', true );
	$import_into = implode( "','", $import_into );
	
	$post = $wpdb->get_row( $wpdb->prepare( "
		SELECT * 
		FROM $wpdb->posts p
		JOIN $wpdb->postmeta pm1
			ON pm1.post_id = p.ID
		WHERE pm1.meta_key = '_dfrps_product_id' 
		AND pm1.meta_value = %s
		AND p.post_type IN ( '" . $import_into . "' )
	", $product['_id'] ), ARRAY_A );
	
	if ( $post != NULL ) {
		return $post;
	}
	
	return false;
}

function dfrps_int_to_price( $price ) {
	$price = intval( $price );
	return ( $price/100 );
}



