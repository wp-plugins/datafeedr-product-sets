<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'the_post', 'dfrps_upload_images' );
function dfrps_upload_images( $post ) {

	// The postmeta field to find the image URL to use as the product's main featured image.
	$field = '_dfrps_featured_image_url';
	
	// Don't process if this post_type is not even a registered CPT.
	$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
	if ( !array_key_exists( $post->post_type, $registered_cpts ) ) {
		return $post;
	}
	
	// We need some postmeta so get that now.
	$postmeta = get_post_custom( $post->ID );
		
	// Apply filter to postmeta.
	$postmeta = apply_filters( 'dfrps_upload_images_postmeta', $postmeta, $post );
	
	// Check that this post was created by the Datafeedr Products Sets plugin.
	if ( !isset( $postmeta['_dfrps_product_set_id'][0] ) ) {
		return $post;
	}
	
	// Don't do if we've already checked the image status for this product
	if ( isset( $postmeta['_dfrps_product_check_image'][0] ) && ( $postmeta['_dfrps_product_check_image'][0] == 0 ) ) {
		return $post;
	}	
		
	// Don't do if this post already has a thumbnail
	$thumbnail_id = get_post_thumbnail_id( $post->ID );
	if ( !empty( $thumbnail_id ) ) { 
		update_post_meta( $post->ID, '_dfrps_product_check_image', 0 );	
		return $post;
	}
		
	$image = false;
	
	if ( @getimagesize( $postmeta[$field][0] ) ) {
		$image = $postmeta[$field][0];
	}
		
	if ( $image ) {
	
		$product_image = array(
			'post_title'   => preg_replace( '/\.[^.]+$/', '', $post->post_title ),
			'post_content' => '',
			'post_status'  => 'inherit',
			'post_parent'  => $post->ID
		);
		
		$attachment = dfrps_process_attachment( $product_image, $image );
		
		if ( is_integer( $attachment ) ) {
			set_post_thumbnail( $post->ID, $attachment );	
		}	
		
	} else {
	
		// Do action if image is not a valid image.
		do_action( 'dfrps_invalid_image', $post );
	}
	
   	update_post_meta( $post->ID, '_dfrps_product_check_image', 0 );	
	return $post;
}

/*
 * CODE FROM WP IMPORTER
 * ~/wp-content/plugins/wordpress-importer/wordpress-importer.php
 */
function dfrps_process_attachment( $post, $url ) {

	$upload = dfrps_fetch_remote_file( $url, $post );
	
	if ( is_wp_error( $upload ) ) {
		return $upload;
	}

	if ( $info = wp_check_filetype( $upload['file'] ) ) {
		$post['post_mime_type'] = $info['type'];
	} else {
		return new WP_Error( 'attachment_processing_error', __('Invalid file type', 'wordpress-importer') );
	}

	$post['guid'] = $upload['url'];

	// as per wp-admin/includes/upload.php
	require_once ABSPATH . 'wp-admin/includes/image.php';
	
	$post_id = wp_insert_attachment( $post, $upload['file'] );
	
	wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );
	
	// remap resized image URLs, works by stripping the extension and remapping the URL stub.
	if ( preg_match( '!^image/!', $info['type'] ) ) {
		$parts = pathinfo( $url );
		$name = basename( $parts['basename'], ".{$parts['extension']}" ); // PATHINFO_FILENAME in PHP 5.2

		$parts_new = pathinfo( $upload['url'] );
		$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );
	}

	return $post_id;
}

/**
 * Attempt to download a remote file attachment
 *
 * @param string $url URL of item to fetch
 * @param array $post Attachment details
 * @return array|WP_Error Local file location details on success, WP_Error otherwise
 */
function dfrps_fetch_remote_file( $url, $post ) {

	$size = getimagesize($url);
	$ext = image_type_to_extension($size[2]);

	// extract the file name and extension from the url
	$file_name = sanitize_title( $post['post_title'] ) . '-' . $post['post_parent'] . $ext;

	// get placeholder file in the upload dir with a unique, sanitized filename
	$upload = wp_upload_bits( $file_name, null, file_get_contents($url) );
	if ( $upload['error'] )
		return new WP_Error( 'upload_dir_error', $upload['error'] );

	// fetch the remote url and write it to the placeholder file
	$headers = wp_get_http( $url, $upload['file'] );

	// request failed
	if ( ! $headers ) {
		@unlink( $upload['file'] );
		return new WP_Error( 'import_file_error', __('Remote server did not respond', 'wordpress-importer') );
	}

	// make sure the fetch was successful
	if ( $headers['response'] != '200' ) {
		@unlink( $upload['file'] );
		return new WP_Error( 'import_file_error', sprintf( __('Remote server returned error response %1$d %2$s', 'wordpress-importer'), esc_html($headers['response']), get_status_header_desc($headers['response']) ) );
	}

	$filesize = filesize( $upload['file'] );

	if ( 0 == $filesize ) {
		@unlink( $upload['file'] );
		return new WP_Error( 'import_file_error', __('Zero size file downloaded', 'wordpress-importer') );
	}

	$max_size = (int) dfrps_max_attachment_size();
	if ( ! empty( $max_size ) && $filesize > $max_size ) {
		@unlink( $upload['file'] );
		return new WP_Error( 'import_file_error', sprintf(__('Remote file is too large, limit is %s', 'wordpress-importer'), size_format($max_size) ) );
	}

	return $upload;
}

function dfrps_max_attachment_size() {
	return apply_filters( 'import_attachment_size_limit', 0 );
}