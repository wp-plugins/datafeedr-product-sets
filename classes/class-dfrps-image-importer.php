<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Imports product images.
 *
 * This class imports images from merchant's site, attaches them to the
 * $post (ie. the product) and sets them as the Featured Image.
 *
 * @since 1.1.10
 */
class Dfrps_Image_Importer {

	/**
	 * Set up class.
	 *
	 * This sets the class's properties and fires the import() method.
	 *
	 * @since 1.1.10
	 *
	 * @param object $post Expects a full $post object of the product we're importing an image for.
	 */	
	public function __construct ( $post ) {

		wp_suspend_cache_addition( true );
		wp_suspend_cache_invalidation( true );
		$use_cache = wp_using_ext_object_cache( false );

		$this->post = $post;
		$this->postmeta = get_post_custom( $post->ID );
		$this->image_url = $this->set_image_url();
		$this->import();

		wp_suspend_cache_addition( false );
		wp_suspend_cache_invalidation( false );
		wp_using_ext_object_cache( $use_cache );
	}

	/**
	 * Check if there is an image URL.
	 *
	 * Check the field respsonsible for storing the URL of the image
	 * we intentd to import.
	 *
	 * @since 1.1.10
	 *
	 * @return string|bool Return image URL if exists. Otherwise, return false.
	 */	
	public function set_image_url() {
		if ( isset( $this->postmeta['_dfrps_featured_image_url'][0] ) && !empty( $this->postmeta['_dfrps_featured_image_url'][0] ) ) {
			$image_url = $this->postmeta['_dfrps_featured_image_url'][0];
			$image_url = ( substr( $image_url, 0, 2 ) === "//" ) ? 'http:' . $image_url : $image_url; // Handle Protocol-relative URLs
			$image_url = str_replace( " ", "%20", $image_url ); // Replace spaces in URL with "%20".
			return $image_url;
		}
		return false;
	}
	
	/**
	 * Performs the image import.
	 *
	 * This method first checks that if we should even try. Then we require() the 
	 * proper files to perform this function. Next, we modify the HTTP request
	 * to change the user-agent. Then we let media_sideload_image() do most of the
	 * work. If it's successful, it returns the ID of the attachment/thumbnail. 
	 * Lastly, if a thumbnail ID was returned, we set it as the product's 
	 * Featured Image and set the product's "check_image" option to 0 meaning don't
	 * check this product for an image again until it's gone though another Product
	 * Set update.
	 *
	 * @since 1.1.10
	 *
	 * @see media_sideload_image()
	 */
	public function import() {
			
		// Skip import if it's not necessary to import the image.
		if ( ! $this->do_import_image() ) {
			return;
		}
				
		// Require necessary files to handle image import.
		$this->require_files();
		
		// Modify the WP_Http::request's user agent.
		add_filter( 'http_request_args', array ( $this, 'http_request_args' ), 20, 2 );
		
		// http://theme.fm/2011/10/how-to-upload-media-via-url-programmatically-in-wordpress-2657/
		$thumbnail_id = $this->media_sideload_image();
		
		// Set as featured image or fire 'dfrps_invalid_image' action.
		if ( $thumbnail_id ) {
			set_post_thumbnail( $this->post->ID, $thumbnail_id );
		} else {
			do_action( 'dfrps_invalid_image', $this->post );
		}

		// Set this so we don't check this post again until after its updated again.
		update_post_meta( $this->post->ID, '_dfrps_product_check_image', 0 );	
	}
	
	/**
	 * Detemine if we should download and import the image for this post.
	 *
	 * This will check whether this post was added by a Product Set, 
	 * if its type is a registered CPT, already has a thumbnail and if
	 * this post has already been checked since the last Product Set update.
	 *
	 * @since 1.1.10
	 *
	 * @param Object $post Post object of the post we will add an image to.
	 * 
	 * @return bool Returns true if post has an image URL, is of a type which is a registered CPT, 
	 * was added by a Product Set (ie. has a set ID), we have not checked the 
	 * image status of this post since its last Product Set update and it 
	 * does not already have a thumbnail. Otherwise return false.
	 */
	public function do_import_image() {
	
		// There is no image URL for this post. 
		if ( ! $this->image_url ) {
			return false;
		}
	
		// Don't process if this post_type is not even a registered CPT.
		$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
		if ( !array_key_exists( $this->post->post_type, $registered_cpts ) ) {
			return false;
		}
	
		// Apply filter to postmeta.
		$this->postmeta = apply_filters( 'dfrps_upload_images_postmeta', $this->postmeta, $this->post );
	
		// Check that this post was created by the Datafeedr Products Sets plugin.
		if ( !isset( $this->postmeta['_dfrps_product_set_id'][0] ) ) {
			return false;
		}
	
		// Don't do if we've already checked the image status for this product
		if ( isset( $this->postmeta['_dfrps_product_check_image'][0] ) && ( (int) $this->postmeta['_dfrps_product_check_image'][0] == 0 ) ) {
			return false;
		}	
	
		// Don't do if this post already has a thumbnail. Filter added for bypassing thumbnail check.
		$thumbnail_id = apply_filters( 'dfrps_check_for_thumbnail_id', get_post_thumbnail_id( $this->post->ID ), $this->post, $this->postmeta );
		if ( ! empty( $thumbnail_id ) ) { 
			update_post_meta( $this->post->ID, '_dfrps_product_check_image', 0 );	
			return false;
		}
	
		// Do import the image for this post.
		return true;
	}

	/**
	 * Require the necessary files.
	 *
	 * Require the necessary wp-admin files to perform an file upload. We
	 * need to require these as they are not available from the site'
	 * front end.
	 *
	 * @since 1.1.10
	 *
	 * @see media_sideload_image()
	 * @link http://codex.wordpress.org/Function_Reference/media_sideload_image
	 */
	function require_files() {
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
	}
	
	/**
	 * Modifies the WP_Http::request.
	 *
	 * This adds a different 'user-agent' to our request to help prevent
	 * image access controls blocking the download of images.
	 *
	 * @since 1.1.10
	 *
	 * @param array $r The arguements of the WP_Http request.
	 * @param string $url The URL of the image.
	 * @return array Returns back to the filter.
	 */
	function http_request_args( $r, $url ) {
		$r['user-agent'] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36';
		return apply_filters( 'dfrps_image_import_http_request_args', $r, $url, $this->post );
	}
	
	/**
	 * Sideload the image.
	 *
	 * This function handles sideloading the image. First it checks if the image
	 * has an extension. If it does not, then it uses wp_remote_get() to get the 
	 * image type. Next, it downloads the image file and stores it in the temporary
	 * directory. Next, the temp image gets passed to media_handle_sideload() along with
	 * the post_title and ID for more validation, renaming and moving to /uploads
	 * directory.
	 * 
	 * This function is a modification of media_sideload_image() found here: ~/wp-admin/includes/media.php
	 * WP's core media_sideload_image() function expects 3 parameters: $file, $post_id, $desc = null
	 * In our function, those values correspond to:
	 * 
	 * 		$file 		= $this->image_url
	 * 		$post_id	= $this->post->ID
	 * 		$desc		= $this->post->post_title
	 * 
	 * @since 1.1.10
	 *
	 * @see wp_remote_get(), wp_remote_retrieve_header(), media_handle_sideload()
	 * @link http://theme.fm/2011/10/how-to-upload-media-via-url-programmatically-in-wordpress-2657/
	 * 
	 * @return bool|int Returns false if image processing fails. Retunrs ID of attachment if image processing succeeds.
	 */
	function media_sideload_image() {
	
		// Initialize $file_array.
		$file_array = array();
	
		// Set variables for storage, fix file filename for query strings.
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|tif|bmp)\b/i', $this->image_url, $matches );
	
		/*
		 * Here we check if the image has an extension. If it does, we create
		 * a name like "Nike Jordans.jpg".
		 * 
		 * If the image URL does not have an extension, then we make an 
		 * HTTP request to the image using wp_remote_get() to get its 'content-type'.
		 * Then we can create the name.
		 */
		if ( isset( $matches[1] ) ) {	
			$file_array['name'] = $this->post->post_title . '.' . $matches[1];
		} else {
			$mime = wp_remote_retrieve_header( wp_remote_get( $this->image_url ), 'content-type' );
			$file_array['name'] = $this->post->post_title . '.' . $this->convert_mime_to_ext( $mime );
		}
	
		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $this->image_url );

		// If error storing temporarily, return false.
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return false;
		}
		
		// Allow for filtering of the $file_array.
		$file_array = apply_filters( 'dfrps_pre_media_handle_sideload', $file_array, $this->post, $this->postmeta );
		
		// Do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, $this->post->ID, $this->post->post_title );

		// If error storing permanently, unlink and return false.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return false;
		}
	
		// Return attachment ID (ie. thumbnail ID).
		return $id;
	}
	
	/**
	 * Converts an image mime type to the appropriate extension.
	 *
	 * @since 1.1.10
	 *
	 * @param string $mime A mime type for an image (format image/png).
	 * @return bool|string Extension without leading period "." 
	 * Returns false if mime type is not in array.
	 */
	function convert_mime_to_ext( $mime ) {

		// Handle mimes of values like this: "image/jpeg;charset=UTF-8"
		$mime_array = explode( ";", $mime );
		$mime       = $mime_array[0];

		$mime_to_ext = apply_filters(
			'dfrps_mimes_to_exts',
			array(
				'image/jpeg' => 'jpg',
				'image/png'  => 'png',
				'image/gif'  => 'gif',
				'image/bmp'  => 'bmp',
				'image/tiff' => 'tif',
			)
		);

		if ( isset( $mime_to_ext[ $mime ] ) ) {
			return $mime_to_ext[ $mime ];
		}

		return false;
	}

} // end Dfrps_Image_Importer
