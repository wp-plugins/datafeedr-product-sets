<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Dfrps_Admin_Help' ) ) :

/**
 * Dfrps_Admin_Help Class
 */
class Dfrps_Admin_Help {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( "current_screen", array( $this, 'add_tabs' ), 50 );
	}

	/**
	 * Add help tabs
	 */
	public function add_tabs() {
		
		$screen = get_current_screen();
				
		/*
		[id] => datafeedr-productset
		[id] => edit-datafeedr-productset
		[id] => product-sets_page_dfrps_configuration
		[id] => product-sets_page_dfrpswc_options
		*/
		$possible_screens = array(
			DFRPS_CPT,
			'edit-' . DFRPS_CPT,
			'product-sets_page_dfrps_configuration',
			'product-sets_page_dfrpswc_options',
		);

		if ( ! in_array( $screen->id, $possible_screens ) ) { return; }
		
		// This is an Add/Edit page.
		if ( $screen->id == DFRPS_CPT ) {
		
			$screen->add_help_tab( array(
				'id'	=> 'dfrps_docs_overview',
				'title'	=> __( 'Product Set Overview', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "What is a Product Set?", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( "A Product Set contains a collection of related products and is responsible for importing those products into your blog and keeping them up-to-date.", DFRPS_DOMAIN ) . '</p>' .
					'<ul>' . 
						'<li><strong>' . __( "Collection of Products", DFRPS_DOMAIN ) . '</strong> - ' . __( "You have the option of adding products to your Product Set in one of two ways: by saving a search, or by adding a single product individually.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Importing Products", DFRPS_DOMAIN ) . '</strong> - ' . __( "After you publish your Product Set, the products will be imported into your blog when they reach the front of the update queue.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Updating Products", DFRPS_DOMAIN ) . '</strong> - ' . __( "The Product Set is also responsible for keeping its imported products up-to-date. The update interval is configured on the Product Sets > Configuration page.", DFRPS_DOMAIN ) . '</li>' .
					'</ul>' . 
					'<p>' . __( "", DFRPS_DOMAIN ) . '</p>' 
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_dashboard',
				'title'		=> __( 'Dashboard', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Dashboard", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'The Dashboard guides you through the process of creating a new Product Set. It gives you an overview of the Product Set\'s status, informs you about the update status after you publish, and provides quick links to perform additional actions.', DFRPS_DOMAIN ) . '</p>' . 
					'<p>' . __( "By default it can be found at the top of the right column.", DFRPS_DOMAIN ) . '</p>' 
				) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_title',
				'title'		=> __( 'Title', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Product Set Title", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'The Product Set title is for your reference only. Adding a title is optional, but allows you to identify the Product Set in the future. A short, descriptive title is recommended.', DFRPS_DOMAIN ) . '</p>' 
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_search',
				'title'		=> __( 'Search tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Search Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'Start your product search on the Search tab. On the search form, click <strong>+ add filter</strong> to add additional search fields or (-) to remove fields. Fill your search parameters, then click [Search]. After your results are returned, you\'ll have the option to save your search or add individual products to your Product Set.', DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_saved_search',
				'title'		=> __( 'Saved Search tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Saved Search Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'When/After you save a search, the results will appear on the Saved Search tab. You can also delete your saved search on this tab.', DFRPS_DOMAIN ) . '</p>' . 
					'<p>' . __( 'The number on the Saved Search tab indicates the number of products in your saved search results, not the total number of products in your Product Set.', DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_single_products',
				'title'		=> __( 'Single Products tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Single Products Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'When you add individual products to your product set using the ', DFRPS_DOMAIN ) . '<img src="' . DFRPS_URL . 'images/icons/plus.png" class="dfrps_valign_middle" />' . __( ' button, those products will be listed on the Single Products tab. You can also remove these products from your Product Set by clicking the ', DFRPS_DOMAIN ) . '<img src="' . DFRPS_URL . 'images/icons/minus.png" class="dfrps_valign_middle" />' . __( ' button next to the item on the Single Products tab.', DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_blocked_products',
				'title'		=> __( 'Blocked Products tab', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Blocked Products Tab", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'Products that you no longer wish to see in your Product Set or search results can be blocked by clicking the ', DFRPS_DOMAIN ) . '<img src="' . DFRPS_URL . 'images/icons/block.png" class="dfrps_valign_middle" />' . __( ' button. All the products you\'ve blocked from a Product Set will be listed on the Blocked Products tab. You can unblock products on this list by clicking ', DFRPS_DOMAIN ) . '<img src="' . DFRPS_URL . 'images/icons/unblock.png" class="dfrps_valign_middle" />.</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_record',
				'title'		=> __( 'Product Record', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Product Record", DFRPS_DOMAIN ) . '</h2>' . 
					'<p>' . __( 'How to understand the hidden and displayed information in a product record:', DFRPS_DOMAIN ) . '</p>' . 
					'<p><img src="' . DFRPS_URL . 'images/icons/productrecord.png" /></p>' . 
					'<h2>' . __( "Action Links Legend", DFRPS_DOMAIN ) . '</h2>
					<p><img src="' . DFRPS_URL . 'images/icons/plus.png" class="dfrps_valign_middle" /> ' . __( 'Click to add product to Product Set individually (ie. to Single Products list).', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/block.png" class="dfrps_valign_middle" /> ' . __( 'Click to block product from Product Set and searches.', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/minus.png" class="dfrps_valign_middle" /> ' . __( 'Click to remove product from Single Products list.', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/unblock.png" class="dfrps_valign_middle" /> ' . __( 'Click to remove product from Blocked Products list', DFRPS_DOMAIN ) . '</p>
					<p><img src="' . DFRPS_URL . 'images/icons/checkmark.png" class="dfrps_valign_middle" /> ' . __( 'Indicates product was added to Product Set individually.', DFRPS_DOMAIN ) . '</p>'
			) );

		// This is the "List" of product sets page (All Product Sets)
		} elseif ( $screen->id == 'edit-' . DFRPS_CPT ) {
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_docs_column_headers',
				'title'		=> __( 'Column Headers', DFRPS_DOMAIN ),
				'content'	=> 
					
					'<h2>' . __( "Column Headers", DFRPS_DOMAIN ) . '</h2>' . 
					
					'<p>' . __( "Definitions of the column headers from the Product Sets table below:", DFRPS_DOMAIN ) . '</p>' . 
					'<ul>' . 
						'<li><strong>' . __( "Title", DFRPS_DOMAIN ) . '</strong> - ' 			. __( "The title of your Product Set is optional, for your reference only, and not publicly viewable.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Created", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "The date/time the Product Set was published.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Modified", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "The date/time the Product Set was last modified.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Status", DFRPS_DOMAIN ) . '</strong> - ' 			. __( "The Product Set's publication status. Note that only \"Published\" or \"Scheduled\" Product Sets will be imported or updated.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Next Update", DFRPS_DOMAIN ) . '</strong> - ' 	. __( "The date/time this Product Set is scheduled to be updated.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Started", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "The date/time this Product Set's last update started.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Completed", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "The date/time this Product Set's last update completed.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Added", DFRPS_DOMAIN ) . '</strong> - ' 			. __( "The number of products added/updated during this Product Set's last update.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "Deleted", DFRPS_DOMAIN ) . '</strong> - ' 		. __( "The number of products deleted during this Product Set's last update.", DFRPS_DOMAIN ) . '</li>' .
						'<li><strong>' . __( "API Requests", DFRPS_DOMAIN ) . '</strong> - ' 	. __( "The number of API requests required during this Product Set's last update.", DFRPS_DOMAIN ) . '</li>' .
					'</ul>' . 
					'<p>' . __( "To hide a column:", DFRPS_DOMAIN ) . '</p>' . 
					'<ol>' . 
						'<li>' . __( 'Close this Help box by clicking the "Help" tab label (lower right).', DFRPS_DOMAIN ) . '</li>' . 
						'<li>' . __( 'Open the "Screen Options" tab.', DFRPS_DOMAIN ) . '</li>' . 
						'<li>' . __( "Uncheck the headers you want to hide.", DFRPS_DOMAIN ) . '</li>' . 
					'</ol>'
			) );
		
		// This is the Configuration page.
		} elseif ( $screen->id == 'product-sets_page_dfrps_configuration' ) {

			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_search',
				'title'		=> __( 'Search Settings', DFRPS_DOMAIN ),
				'content'	=> 
					'<h2>' . __( "Search Settings", DFRPS_DOMAIN ) . '</h2>' . 
					'<p><strong>' . __( 'Products Per Search', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This sets the number of products per page of search results to display in the admin area of your site. This setting does not affect how many products display to visitors on the front end of your site.", DFRPS_DOMAIN ) . '</p>' .
					'<p><strong>' . __( 'Default Search Setting', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting configures how the product search form loads when creating a new Product Set. Changing the default settings will not affect already created Product Sets.", DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_general_update',
				'title'		=> __( 'General Update Settings', DFRPS_DOMAIN ),
				'content'	=> 
					
					'<h2>' . __( "General Update Settings", DFRPS_DOMAIN ) . '</h2>' . 
					
					'<p><strong>' . __( 'Default Custom Post Type', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This is the Custom Post Type that will be selected by default when creating a new Product Set.  In most cases there will only be one option.", DFRPS_DOMAIN ) . '</p>' .

					'<p><strong>' . __( 'Delete Missing Products', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting configures how products which are no longer available from the API are handled on your site. By default, those products will be moved to the Trash and deleted after ", DFRPS_DOMAIN ) . EMPTY_TRASH_DAYS  . __( " days.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Updates', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting allows you to disable updates. If you've used all of your monthly API requests, updates become disabled automatically.", DFRPS_DOMAIN ) . '</p>'
			) );
		
			$screen->add_help_tab( array(
				'id'		=> 'dfrps_config_advanced_update',
				'title'		=> __( 'Advanced Update Settings', DFRPS_DOMAIN ),
				'content'	=> 
					
					'<h2>' . __( "Advanced Update Settings", DFRPS_DOMAIN ) . '</h2>' . 
					
					'<p><strong class="dfrps_warning">' . __( 'WARNING', DFRPS_DOMAIN ) . '</strong> - ' . __( "Updates are <strong>SERVER INTENSIVE</strong>. Modifying these values could cause server or hosting issues. Change with caution!", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Update Interval', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting determines how often a Product Set is updated.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Cron Interval', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting controls how often WordPress Cron will run to check if a Product Set needs to be updated or to perform the next step in the update process if a Product Set is currently being updated.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Products Per Update', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This setting controls the number of products per batch to update.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Preprocess Maximum', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This sets the number of products per batch to prepare for updating. Preprocessing includes flagging all products in a Product Set as being ready for updating and modifying those products' categories.", DFRPS_DOMAIN ) . '</p>' .
					
					'<p><strong>' . __( 'Postprocess Maximum', DFRPS_DOMAIN ) . '</strong> - ' . 
					__( "This sets the number of products to process per batch upon completion of a Product Set update. Postprocessing includes deleting any old or missing products.", DFRPS_DOMAIN ) . '</p>'

			) );
		}	

		// The following tabs appear on ALL screens.		
		dfrapi_help_tab( $screen );


	}
}

endif;

return new Dfrps_Admin_Help();