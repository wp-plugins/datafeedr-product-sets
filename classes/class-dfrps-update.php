<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'Dfrps_Update' ) ) {

/*******************************************************************************
                    OVERVIEW OF THE UPDATE PROCESS WORKS

When a Product Set is published, the following postmeta is set:
_dfrps_cpt_next_update_time = NOW (in Unix Timestamp format)

The dfrps_cron_schedules runs at the configured "cron_interval".  Upon running, 
the cron performs an SQL query which looks like this:

	SELECT 1 post AND update_phase AND next_update
	FROM posts table LEFT JOINed twice on the postmeta table
	WHERE the post_type = "datafeedr-productset"
	AND status is either "published" or "trash"
	
	These results are ordered by update_phase DESC and then next_update ASC

The cron SQL query basically gives us first any product set that is currently
being updated (indicated by the update_phase being greater than 0) and if there
are no product sets currently being updated, returns the product set that is
has the lowest (soonest) next_update time.

We grab "published" product sets to process sets that need to be imported or 
updated.

We also grab "trashed" product sets to remove products from their respective
CPT if the product set has been moved to the Trash. Product sets moved to the
Trash are given a a next_update time of 5 minutes in the future so the user can
reverse their decision before products are removed from their site.

Now that 1 result is returned from our query, we check the post type.

First, if a product set's post_type is "trash" and the next_update time is less
than NOW, we call the Dfrps_Delete( $post ) class.

------------ Add info on how the Delete class works

If there are no products sets in the trash that need to be processed, then we 
check if there are published product sets that need to be processed.  We do this
by checking if the status is "publish" and if update_phase > 0 OR next_update 
time is less than NOW OR next_update equals 0 (which means it should be updated
as soon as possible).

If those conditions apply, then we call the Dfrps_Update( $post ) class, passing 
the $post array to the class.

Begin Dfrps_Update( $post )

When the Dfrps_Update() is initialized we check the update_phase.  When a 
Product Set is published or has completed its update process the update_phase 
is set to 0.  

If the update phase is 0 OR 1, we call the phase1() method. 

PHASE 1

The first thing phase1 checks for is if this is the first time phase1 has been
called during the update of this product set (is_first_pass()).

We need to check this because there are things we need to do on the first pass
that shouldn't be done on subsequent passes such as:

	- delete _dfrps_cpt_errors
	- unset _dfrps_cpt_previous_update_info
	- set _dfrps_cpt_previous_update_info to all OLD postmeta info 
	- set _dfrps_preprocess_complete_{$cpt} to false
	- set _dfrps_cpt_update_phase to 1
	- set _dfrps_cpt_update_iteration to 1
	- set _dfrps_cpt_offset to 1
	- set _dfrps_cpt_last_update_time_started to NOW
	- set last_update completed, added, reqests and deleted to 0

If this is not the first pass for this phase, we only need to call the 
count_iteration() method to update _dfrps_cpt_update_iteration. 

After the is_first_pass() check, we do_action( 'dfrps_preprocess', $this ).

This triggers any importer plugin to run its preprocess stage.

Then we make a call to the preprocess_complete_check() method.  This method 
loops through the array of CPTs to import into (ie. _dfrps_cpt_import_into).  
This array is a simple array of post types.  It might look like this:

	array( 'product', 'post' )
	
If _dfrps_cpt_import_into returns array( 'product', 'post' ), then the 
preprocess_complete_check() would do the following look ups:

	get_post_meta( $set_id, '_dfrps_preprocess_complete_product', true )
	get_post_meta( $set_id, '_dfrps_preprocess_complete_post', true )
	
The value '_dfrps_preprocess_complete_{$cpt}' is set by the importer plugin, not
by the Product Sets plugin.  The importer plugin is responsible for setting 
'_dfrps_preprocess_complete_{$cpt}' to TRUE when the importer plugin is finished
with its preprocess functions.

During the preprocess_complete_check() loop where it checks if the preprocess
stage of each CPT is complete, if false is returned at anytime, the 
preprocess_complete_check() method stops running and returns FALSE.

Here's where it gets tricky...

The current importer plugins do a LOT of pre and post process work for each 
update.

For example, when the WooCommerce Importer plugin does its dfrps_preprocess
action, it performs the following tasks:

	- First, it gets all post IDs created by this set. Why?
	
		We need to remove all products which were imported via a product set
 		from the categories they were added to when they were imported 
		so that at the end of the update, if these products weren't re-imported
 		during the update, the post/product's category information (for this
 		set) will no longer be available so that if this post/product was 
 		added via another Product Set, only that Product Set's category IDs
 		will be attributed to this post/product.
 		
 		Even the process of getting all post IDs created by this set is 
 		intensive so those post IDs are stored in the options table under the 
 		name:
 		
 			unset_post_categories_{$CPT}_for_set_XXX
 			
 		Therefore, if the CPT were "product" and those products were added by
 		the product set with ID of 253, then the option name would be:
 		
 			unset_post_categories_product_for_set_253
 			
 		Because the process of unsettings categories from their posts is an 
 		intensive process, we have to do it in batches configured by the 
 		"preprocess_maximum" option.
 		
 		So now each time phase1() runs and we do_action( 'dfrps_preprocess' )
 		we either query for all post IDs to unset or look them up via the 
 		unset_post_categories_{$CPT}_for_set_XXX option and start working our
 		way through a list of post IDs unsetting their category information.
 		
 	- Second, we loop through those post IDs in batches and call the following 
 	function for each product:
 	
 			dfrps_remove_category_ids_from_post( $id, $obj->set, CPT, TAXONOMY )
 		
		Why?
		
			This gets the category IDs for the current post (ie. the product
			that was already imported into the database) and removes the
			IDs from the post. Why?
		
			We need to delete this set's current category IDs from this product
			so that at the end of the update, if this product isn't re-imported
			during the update, the post/product's category information (for this
			set) will no longer be available so that if this post/product was 
			added via another Product Set, only that Product Set's category IDs
			will be attributed to this post/product.
		
		Basically we're only removing category information set by this Product Set.
		So if a post was added by multiple product sets, we only unset category IDs
		which were set by this product set.
		
		Another tricky part about this step is that we're looking at the 
		_dfrps_cpt_categories_history postmeta field.  This field is responsible
		for keeping track of all past categories a set was ever attributed to. 
		Without	keeping track of historical categories, there's no way to know 
		if a product set was "unlinked" from a category.

	- Third, after a post's categories are unset, then we delete the 
	   _dfrps_product_set_id field for this post and set. Why?
		
		We have to do this so that if the product doesn't get re-imported or 
		updated during the update, that means it's no longer available from the 
		API or from the search query and we need to move it to the Trash. The 
		actual moving to the Trash is handled in the postprocess stage.
		
	- Lastly, after we have processed the "preprocess_maximum" batch, we check
	if there are any more post IDs to process.
		
		If there are, then we update unset_post_categories_{$CPT}_for_set_XXX 
		with the remaining post IDs so that they are quickly available to us 
		during the next iteration of phase1().
		
		If there are not, then we set _dfrps_preprocess_complete_{$cpt} to true
		and delete the option unset_post_categories_{$CPT}_for_set_XXX.
 		
 The last step of phase1() is to check if preprocess_complete is true.  This 
 means all importer scripts are done with their preprocess tasks.
 
 If preprocess_complete is true, we set the phase to 2.
 
 If preprocess_complete is false, we do nothing and phase1 will run again.
 
 PHASE 2
 
 phase2()'s task is to import any products saved via a saved search.
 
 phase2() begins with updating the iteration counter.
 
 If there is no saved query (_dfrps_cpt_query is empty), then we update the phase
 to 3, and return.
 
If there is a saved query (_dfrps_cpt_query).  If there is, that means we have 
a saved query and should continue.

Next we get any "blocked" products.  These are products that should be excluded
from our API search query. (_dfrps_cpt_manually_blocked_ids)

Next we run our query: dfrapi_api_get_products_by_query(). This doesn't return 
all of the products, only a subset of products determined either by API limitations
or by the "num_products_per_update" config setting.

We also update _dfrps_cpt_last_update_num_api_requests by 1. This gives the user
the number of API requests this product set requires during import/update.

If there is an error returned by the API, then we set the error in the 
_dfrps_cpt_errors postmeta field.  We also call the handle_error() method which
performs additional actions based on the error code.

If there are no errors, we set _dfrps_cpt_errors to false. This helps to 
remove any temporary errors that may have been set but are no longer applicable.

Now we send it over to the importers for processing the product data...

To do that we loop through and array of CPTs to import into 
(ie. _dfrps_cpt_import_into).  For each CPT we do the following action:

	do_action( "dfrps_action_do_products_{$cpt}", $data, $this->set );
	
So an example of an action name would be:

	"dfrps_action_do_products_product"

$data contains the product data returned by the API request.
$this->set contains all of the information about this product set.

Now the importer scripts that have this action will do their importing duty. For 
the WooCommerce Importer plugin, this includes:

	- Looping through each product
	- Calling dfrps_get_existing_post() to determine if this is an existing or 
	  new post/product.
	- Handling the insertion or updating of a post.
	- Handling any meta data (terms, postmeta, attributes) related to each product.

_dfrps_cpt_last_update_num_products_added is updated with the number of products
added during this iteration to keep track of the total number of products 
imported or updated during this update.	

If $data is empty OR if count(products) < 'num_products_per_update'
THEN _dfrps_cpt_offset is set to 1 and the update phase is set to 3.

PHASE 3

 phase3()'s task is to import any products added individually.
 
 phase3() begins with updating the iteration counter.
 
This phase is essentially the same as phase2() except we're querying the API
for specific product IDs, not searching for products.

PHASE 4

phase4()'s task is to do "clean up" of the product set update.

phase4() begins with updating the iteration counter.

Next we check if this is the first pass for this phase. If it is, we set
_dfrps_postprocess_complete_{$cpt} for all CPTs in this set to FALSE.

Next we do_action( 'dfrps_postprocess', $this )

Now it's the importer's turn to do any/all of its postprocessing.  In this 
example we will look at the WooCommerce Importer plugin.

The WC Importer plugin's main task is to delete "stranded" products.  That is
products that were added by this set but which are no longer associated with 
this set because their "_dfrps_product_set_id" field was unset in phase1().

This only deletes stranded products if the delete_missing_products is TRUE. If
that's the case, the WC Importer plugin sets _dfrps_postprocess_complete_{$cpt} 
to TRUE.

Next we either get a list of trashable posts from the option 
'trashable_posts_for_set_123' where 123 is the ID of this Product set.  If that 
does not exist, we run a query for trashable posts.

Basically the SQL SELECTs all post IDs where the post's postmeta contains the 
meta_key '_dfrps_is_dfrps_product' and that meta_key's meta_value = 1 AND 
the post does not have a meta_key of '_dfrps_product_set_id'.

If no post IDs are returned in the query, _dfrps_postprocess_complete_{$cpt}
is set to TRUE and we return.

If there are post IDs, then we create a record in the options table with the name
'trashable_posts_for_set_{$set_id}' and add the array of post IDs to it.

Then we loop through that array of post IDs, processing them in batches. Batches
are configured by postprocess_maximum.

Post IDs are continually processed in batches each iteration of phase4 until
there are no more to process.

When there are no more post IDs to process, we delete 'trashable_posts_for_set_{$set_id}'
and set _dfrps_postprocess_complete_{$cpt} to TRUE.

After all importers are returning TRUE for postprocess_complete, then we finish
up phase4() by doing the following:

- delete all first pass data stored during this update.
- set the next update time and apply_filter to this value.
- update time completed with NOW.
- update phase to 0.

And that's it...  My fingers are tired.
 
*******************************************************************************/

/**
 * Product Set Updater
 */
class Dfrps_Update {

	/**
	 * PHASES
	 * 
	 * 0 - Not currently being updated.
	 * 
	 * 1 - Initialize update. Set necessary variables.
	 * 			- _dfrps_cpt_offset = 0
	 * 			- _dfrps_cpt_last_update_num_products_deleted = 0
	 * 			- _dfrps_cpt_last_update_time_started = TIMESTAMP
	 * 			- _dfrps_cpt_update_phase = 1
	 * 			- _dfrps_cpt_last_update_num_products_added = 0
	 * 			- _dfrps_cpt_last_update_num_api_requests = 0
	 * 			- store "last_update" info to use for stats.
	 *
	 * 2 - Process Saved Search
	 * 			- count API requests
	 * 			- _dfrps_cpt_last_update_num_products_added ++
	 * 			- _dfrps_cpt_offset ++
	 * 			- _dfrps_cpt_update_phase = 2
	 *
	 * 3 - Process Single Products
	 * 			- count API requests
	 * 			- _dfrps_cpt_last_update_num_products_added ++
	 * 			- _dfrps_cpt_offset ++
	 * 			- _dfrps_cpt_update_phase = 3
	 * 
	 * 4 - Finalize update.
	 * 			- delete missing products (if "delete_missing_products" is true)
	 * 			- _dfrps_cpt_update_phase = 0
	 * 			- _dfrps_cpt_offset = 0
	 * 			- _dfrps_cpt_last_update_time_completed = TIMESTAMP
	 * 			- _dfrps_cpt_next_update_time = TIMESTAMP
	 *
	 * 			
	 */

	public function __construct( $post ) {
		
		$this->action = 'update';
		$this->set = $post;
		$this->config = $this->get_configuration();
		$this->meta = $this->get_postmeta();
		$this->phase = $this->get_phase();
		$this->set['postmeta'] = $this->meta;
		$this->update();
	}
	
	// Get user's configuration settings.
	function get_configuration() {
		return get_option( 'dfrps_configuration' );
	}
	
	// Load post meta.
	function get_postmeta() {
		return get_post_custom( $this->set['ID'] );
	}
	
	// Get the current phase of the update.
	function get_phase() {
		if ( isset( $this->meta['_dfrps_cpt_update_phase'][0] ) ) {
			return intval( $this->meta['_dfrps_cpt_update_phase'][0] );
		}
		return 0;
	}
	
	// Get CPTs that products can potentially be imported in to.
	function get_cpts_to_import_into() {
		$cpts = unserialize( @$this->meta['_dfrps_cpt_import_into'][0] );
		if ( !empty( $cpts ) ) {
			asort( $cpts );
			return $cpts;
		}
		return array();
	}

	// Run update.
	function update() {
		
		if ( $this->phase == 0 || $this->phase == 1 ) {
			$this->phase1();
		} elseif ( $this->phase == 2 ) {
			$this->phase2();
		} elseif ( $this->phase == 3 ) {
			$this->phase3();
		} elseif ( $this->phase == 4 ) {
			$this->phase4();
		}
	}
	
	// Count each iteraction of the update process.
	function count_iteration() {
		$iteration = get_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', true );		
		if( !empty( $iteration ) ) {
			$iteration = intval( $iteration );
			$iteration = ( $iteration + 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', $iteration );
		} else {
			$iteration = 1;
			add_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', $iteration, true );
		}
	}
	
	function preprocess_complete_check() {
		foreach ( $this->get_cpts_to_import_into() as $cpt ) {
			$complete = get_post_meta( $this->set['ID'], '_dfrps_preprocess_complete_' . $cpt, true );
			if ( $complete == '' ) {
				return false;
			}
		}
		return true;
	}
	
	function postprocess_complete_check() {
		foreach ( $this->get_cpts_to_import_into() as $cpt ) {
			$complete = get_post_meta( $this->set['ID'], '_dfrps_postprocess_complete_' . $cpt, true );
			if ( $complete == '' ) {
				return false;
			}
		}
		return true;
	}
	
	function is_first_pass() {
		$first_pass = get_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase' . $this->phase . '_first_pass', true );
		if ( empty( $first_pass ) ) {
			// This is the first pass for this phase.
			add_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase' . $this->phase . '_first_pass', true, true );	
			return TRUE;
		}
		return FALSE;
	}
	
	function delete_first_passes() {
		for( $i=1; $i<=4; $i++ ) {
			delete_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase' . $i . '_first_pass' );
		}
	}
	
	function handle_error( $data ) {
		//$code = intval( $data['dfrapi_api_error']['code'] );
		//if ( $code < 500 ) {
		
		// Regarding Ticket #8262
		$class = $data['dfrapi_api_error']['class'];
		
		// These are the ERROR classes that trigger updates to be disabled.
		$error_classes = array(
			'DatafeedrBadRequestError',
			'DatafeedrAuthenticationError',
			'DatafeedrLimitExceededError',
			'DatafeedrQueryError',
		);
		
		$error_classes = apply_filters( 'dfrps_disable_updates_error_classes', $error_classes );
		
		if ( in_array( $class, $error_classes ) ) {
			$this->config['updates_enabled'] = 'disabled';
			update_option( 'dfrps_configuration', $this->config );
			$this->updates_disabled_email_user( $data );
		}
	}
	
	function updates_disabled_email_user( $obj ) {

		$params 			= array();
		$params['to'] 		= get_bloginfo( 'admin_email' );
		$params['subject']  = get_bloginfo( 'name' ) . __( ': Datafeedr API Message (Product Set Update Failed)', DFRPS_DOMAIN );
		
		$params['message']  = "<p>" . __( "This is an automated message generated by: ", DFRPS_DOMAIN ) . get_bloginfo( 'wpurl' ) . "</p>";
		$params['message'] .= "<p>" . __( "An error occurred during the update of the ", DFRPS_DOMAIN );
		$params['message'] .= "<a href=\"" . admin_url( 'post.php?post=' . $this->set['ID'] . '&action=edit' ) . "\">" . $this->set['post_title'] . "</a>";
		$params['message'] .= __( " product set.", DFRPS_DOMAIN ) . "</p>";
		
		if ( isset( $obj['dfrapi_api_error']['class'] ) ) {
			
			// Have we exceeded the API request limit?
			if ( $obj['dfrapi_api_error']['class'] == 'DatafeedrLimitExceededError' ) {
				
				$params['message'] .= "<p>" . __( "You have used <strong>100%</strong> of your allocated Datafeedr API requests for this period. <u>You are no longer able to query the Datafeedr API to get product information.</u>", DFRPS_DOMAIN ) . "</p>";
				$params['message'] .= "<p><strong>" . __( "What to do next?", DFRPS_DOMAIN ) . "</strong></p>";
				$params['message'] .= "<p>" . __( "We strongly recommend that you upgrade to prevent your product information from becoming outdated.", DFRPS_DOMAIN ) . "</p>";
				$params['message'] .= "<p><a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=updatesdisablednotice\"><strong>" . __( "UPGRADE NOW", DFRPS_DOMAIN ) . "</strong></a></p>";
				$params['message'] .= "<p>" . __( "Upgrading only takes a minute. You will have <strong>instant access</strong> to more API requests. Any remaining credit for your current plan will be applied to your new plan.", DFRPS_DOMAIN ) . "</p>";
				$params['message'] .= "<p>" . __( "You are under no obligation to upgrade. You may continue using your current plan for as long as you would like.", DFRPS_DOMAIN ) . "</p>";
								
			} else {
				
				$params['message'] .= "<p>" . __( "The details of the error are below.", DFRPS_DOMAIN ) . "</p>";
				$params['message'] .= "<tt>";
				$params['message'] .= "#################################################<br />";
				$params['message'] .= __( "CLASS: ", DFRPS_DOMAIN ) . $obj['dfrapi_api_error']['class'] . "<br />";
				$params['message'] .= __( "CODE: ", DFRPS_DOMAIN ) . $obj['dfrapi_api_error']['code'] . "<br />";
				$params['message'] .= __( "MESSAGE: ", DFRPS_DOMAIN ) . $obj['dfrapi_api_error']['msg'] . "<br />";
				if ( !empty( $obj['dfrapi_api_error']['params'] ) ) {
					$query = dfrapi_display_api_request( $obj['dfrapi_api_error']['params'] );
					$params['message'] .= __( "<br />QUERY:<br />", DFRPS_DOMAIN ) . $query . "<br />";
				}
				$params['message'] .= "#################################################";
				$params['message'] .= "</tt>";
			}		
		}
		
		$params['message'] .= "<p>" . __( "In the meantime, all product updates have been disabled on your site. After you fix this problem you will need to ", DFRPS_DOMAIN );
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrps_configuration' ) . "\">" . __( "enable updates again", DFRPS_DOMAIN ) . ".</p>";
		$params['message'] .= "<p>" . __( "If you have any questions about your account, please ", DFRPS_DOMAIN );
		$params['message'] .= "<a href=\"" . DFRAPI_EMAIL_US_URL . "?utm_source=email&utm_medium=link&utm_campaign=updatesdisablednotice\">" . __( "contact us", DFRPS_DOMAIN ) . "</a>.</p>";
		$params['message'] .= "<p>" . __( "Thanks,<br />Eric &amp; Stefan<br />The Datafeedr Team", DFRPS_DOMAIN ) . "</p>";

		$params['message'] .= "<p>";
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrapi_account' ) . "\">" . __( "Account Information", DFRPS_DOMAIN ) . "</a> | ";
		$params['message'] .= "<a href=\"" . dfrapi_user_pages( 'change' ) . "?utm_source=email&utm_medium=link&utm_campaign=updatesdisablednotice\">" . __( "Upgrade Account", DFRPS_DOMAIN ) . "</a> | ";
		$params['message'] .= "<a href=\"" . admin_url( 'admin.php?page=dfrps_configuration' ) . "\">" . __( "Enable Updates", DFRPS_DOMAIN ) . "</a>";
		$params['message'] .= "</p>";
		
		add_filter( 'wp_mail_content_type', 'dfrps_set_html_content_type' );
		wp_mail( $params['to'], $params['subject'], $params['message'] );
		remove_filter( 'wp_mail_content_type', 'dfrps_set_html_content_type' );
		
	}
	
	// Phase 1, initialize update, set variables and update phase.
	function phase1() {
	
		$this->phase = 1;
		
		do_action( 'dfrps_begin_phase', $this );
		do_action( 'dfrps_begin_phase_1', $this );
						
		if( $this->is_first_pass() ) {
			
			// Set preprocess incomplete for each CPT that this set imports into.
			foreach ( $this->get_cpts_to_import_into() as $cpt ) {
				update_post_meta( $this->set['ID'], '_dfrps_preprocess_complete_' . $cpt, false );
			}
			
			delete_post_meta( $this->set['ID'], '_dfrps_cpt_errors' );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 1 );
				
			unset( $this->meta['_dfrps_cpt_previous_update_info'] ); // Unset so array item is not duplicated
			update_post_meta( $this->set['ID'], '_dfrps_cpt_previous_update_info', $this->meta );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_iteration', 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_started', date_i18n( 'U' ) );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_completed', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_added', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_api_requests', 0 );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_deleted', 0 );
		} else {
			$this->count_iteration();
		}
		
		do_action( 'dfrps_preprocess', $this );
		
		// Check if preprocess is complete (detemined by importer scripts)
		$preprocess_complete = $this->preprocess_complete_check();
				
		// Move to phase 2 ONLY if all posts have been unset from their categories.
		if ( $preprocess_complete ) {
			$this->phase = 2;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 2 );
		}
		
		do_action( 'dfrps_end_phase', $this );
		do_action( 'dfrps_end_phase_1', $this );
		return;	
	}
	
	// Phase 2, process saved search if it exists.
	function phase2() {
		
		$this->phase = 2;

		do_action( 'dfrps_begin_phase', $this );
		do_action( 'dfrps_begin_phase_2', $this );
		$this->count_iteration();
		
		$query = unserialize( @$this->meta['_dfrps_cpt_query'][0] );
	
		// Check that a saved search exists and move on if it doesn't.
		if ( empty( $query ) ) {
			$this->phase = 3;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 3 );
			$this->phase3();
			do_action( 'dfrps_end_phase_2', $this );
			return;
		}
		
		// Get manually blocked product IDs.
		$blocked = get_post_meta( $this->set['ID'], '_dfrps_cpt_manually_blocked_ids', true );
		if ( is_array( $blocked ) && !empty( $blocked ) ) {
			$manually_blocked = $blocked;
		} else {
			$manually_blocked = array();
		}
				
		// Run query.
		$data = dfrapi_api_get_products_by_query( $query, $this->config['num_products_per_update'], $this->meta['_dfrps_cpt_offset'][0], $manually_blocked ); 
		
		// Update number of API requests.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_api_requests', ( $this->meta['_dfrps_cpt_last_update_num_api_requests'][0] + 1 ) );
	
		// Handle errors & return.
		if ( is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', $data );
			$this->handle_error( $data );
			do_action( 'dfrps_end_phase', $this );
			do_action( 'dfrps_end_phase_2', $this );
			return;
		}

		// Delete any errors that are currently being stored.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', false );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		
		// Let the integration plugin(s) handle the group of products for this set.
		$data['_update_offset'] = $this->meta['_dfrps_cpt_offset'][0];
		foreach ( $this->get_cpts_to_import_into() as $cpt ) {
			do_action( "dfrps_action_do_products_{$cpt}", $data, $this->set );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
				
		// Check for 0 products. If no products, update "Phase".
		if ( !isset( $data['products'] ) || empty( $data['products'] ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			$this->phase = 3;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 3 );
			do_action( 'dfrps_end_phase_2', $this );
			return;
		}
		
		// All products in this batch have been imported.  Now update some meta stuff.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', ( $this->meta['_dfrps_cpt_offset'][0] + 1 ) );
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_added', ( $this->meta['_dfrps_cpt_last_update_num_products_added'][0] + count( $data['products'] ) ) );
		
		// If the number of products is less than the number of products per update 
		// (that means subsequent queries wont return any more products).
		// Move to next phase so as not to incur 1 additional API request.
		if ( ( count( $data['products'] ) < $this->config['num_products_per_update'] ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			$this->phase = 3;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 3 );
			do_action( 'dfrps_end_phase_2', $this );
			return;		
		}
		
		do_action( 'dfrps_end_phase', $this );
		do_action( 'dfrps_end_phase_2', $this );
		return;
	}
	
	// Phase 3, process single products.
	function phase3() {
	
		$this->phase = 3;

		do_action( 'dfrps_begin_phase', $this );
		do_action( 'dfrps_begin_phase_3', $this );
		$this->count_iteration();
			
		// Get included IDs and remove any duplicates or empty values.
		$ids = get_post_meta( $this->set['ID'], '_dfrps_cpt_manually_added_ids', true );
		$ids = array_filter( (array) $ids );
				
		// If no IDs, update phase and go to Phase 3.
		if ( empty( $ids ) ) {
			$this->phase = 4;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 4 );
			$this->phase4();
			do_action( 'dfrps_end_phase_3', $this );
			return;
		}		
				
		// Query API
		$data = dfrapi_api_get_products_by_id( $ids, $this->config['num_products_per_update'], $this->meta['_dfrps_cpt_offset'][0] );
				
		// Update number of API requests.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_api_requests', ( $this->meta['_dfrps_cpt_last_update_num_api_requests'][0] + 1 ) );
	
		// Handle errors & return.
		if ( is_array( $data ) && array_key_exists( 'dfrapi_api_error', $data ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', $data );
			$this->handle_error( $data );
			do_action( 'dfrps_end_phase', $this );
			do_action( 'dfrps_end_phase_3', $this );
			return;
		}
		
		// Delete any errors that are currently being stored.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_errors', false );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		
		// Let the integration plugin(s) handle the group of products for this set.
		$data['_update_offset'] = $this->meta['_dfrps_cpt_offset'][0];
		foreach ( $this->get_cpts_to_import_into() as $cpt ) {
			do_action( "dfrps_action_do_products_{$cpt}", $data, $this->set );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
				
		// Check for 0 products. If no products, update "Phase".
		if ( !isset( $data['products'] ) || empty( $data['products'] ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			$this->phase = 4;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 4 );
			do_action( 'dfrps_end_phase_3', $this );
			return;
		}

		// All products in this batch have been imported.  Now update some meta stuff.
		update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', ( $this->meta['_dfrps_cpt_offset'][0] + 1 ) );
		update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_num_products_added', ( $this->meta['_dfrps_cpt_last_update_num_products_added'][0] + count( $data['products'] ) ) );	
		
		// If the number of products is less than the number of products per update 
		// (that means subsequent queries wont return any more products).
		// Move to next phase so as not to incur 1 additional API request.
		if ( ( count( $data['products'] ) < $this->config['num_products_per_update'] ) ) {
			update_post_meta( $this->set['ID'], '_dfrps_cpt_offset', 1 );
			$this->phase = 4;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 4 );
			do_action( 'dfrps_end_phase_3', $this );
			return;
		}
		
		do_action( 'dfrps_end_phase', $this );
		do_action( 'dfrps_end_phase_3', $this );
		return;
	}
	
	// Phase 4, clean up and finalize.
	function phase4() {
	
		$this->phase = 4;
		
		do_action( 'dfrps_begin_phase', $this );
		do_action( 'dfrps_begin_phase_4', $this );
		$this->count_iteration();
				
		if( $this->is_first_pass() ) {	
			// Set postprocess incomplete for each CPT that this set imports into.
			foreach ( $this->get_cpts_to_import_into() as $cpt ) {
				update_post_meta( $this->set['ID'], '_dfrps_postprocess_complete_' . $cpt, false );
			}
		}
				
		do_action( 'dfrps_postprocess', $this );
		
		// Check if preprocess is complete (detemined by importer scripts)
		$postprocess_complete = $this->postprocess_complete_check();
		
		if ( $postprocess_complete ) {
			$this->delete_first_passes();
			
			$next_update_time = dfrps_get_next_update_time();
			$next_update_time = apply_filters( 'dfrps_cpt_next_update_time', $next_update_time, $this->set );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_next_update_time', $next_update_time );
			
			update_post_meta( $this->set['ID'], '_dfrps_cpt_last_update_time_completed', date_i18n( 'U' ) );
			$this->phase = 0;
			do_action( 'dfrps_end_phase', $this );
			update_post_meta( $this->set['ID'], '_dfrps_cpt_update_phase', 0 );
		}
		
		do_action( 'dfrps_end_phase', $this );
		do_action( 'dfrps_end_phase_4', $this );
		return;
	}

		
} // class Dfrps_Update

} // class_exists check