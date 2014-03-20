<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Dfrps_Configuration' ) ) {

	/**
	 * Configuration page.
	 */
	class Dfrps_Configuration {

		private $page = 'dfrps_configuration';
		private $key;
		private $account;

		public function __construct() {
			$this->key = 'dfrps_configuration';
			$this->account = (array) get_option( 'dfrapi_account', array( 'max_length' => 50 ) );
			add_action( 'admin_init', array( &$this, 'load_settings' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		}
	
		function admin_notice() {
			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true && isset( $_GET['page'] ) && $this->page == $_GET['page'] ) {
				echo '<div class="updated"><p>';
				_e( 'Configuration successfully updated!', DFRPS_DOMAIN );
				echo '</p></div>';
			}
		}
	
		function admin_menu() {
			
			// @http://wordpress.org/support/topic/add_menu_page-always-add-an-extra-subpage?replies=6
			$configuration = get_option( 'dfrps_configuration', array() );
			
			if ( !Dfrapi_Env::api_keys_exist() || empty( $configuration['default_cpt'] ) ) {
				
				add_submenu_page(
					'dfrps_configuration',
					__( 'Configuration &#8212; Datafeedr Product Sets', DFRPS_DOMAIN ), 
					__( 'Configuration', DFRPS_DOMAIN ), 
					'manage_options', 
					'dfrps_configuration',
					array( $this, 'output' ) 
				);
			
			
			} else {
		
				add_submenu_page(
					'dfrps',
					__( 'Configuration &#8212; Datafeedr Product Sets', DFRPS_DOMAIN ), 
					__( 'Configuration', DFRPS_DOMAIN ), 
					'manage_options', 
					'dfrps_configuration',
					array( $this, 'output' ) 
				);
			
			}
		}

		function output() {
			echo '<div class="wrap" id="' . $this->key . '">';
			echo '<h2>' . __( 'Configuration &#8212; Datafeedr Product Sets', DFRPS_DOMAIN ) . '</h2>';
			echo '<form method="post" action="options.php">';
			wp_nonce_field( 'update-options' );
			settings_fields( $this->page );
			do_settings_sections( $this->page);
			submit_button();
			echo '</form>';		
			echo '</div>';
		}
		
		function default_options() {
			return array(
				'update_interval' => 7,
				'num_products_per_update' => 100,
				'delete_missing_products' => 'yes',
				'preprocess_maximum' => 100,
				'postprocess_maximum' => 100,
				'num_products_per_search' => 100,
				'default_filters' => array(),
				'cron_interval' => 60,
				'updates_enabled' => 'enabled',
			);
		}
		
		function load_settings() {
			$this->options = array_merge( 
				$this->default_options(), 
				get_option( $this->key, array() ) 
			);
			update_option( $this->key, $this->options );
		}
	
		function register_settings() {
			
			register_setting( $this->page, $this->key, array( $this, 'validate' ) );
			
			add_settings_section( 'default_search_filters', __( 'Search Settings', DFRPS_DOMAIN ), array( &$this, 'section_default_search_filters_desc' ), $this->page );
			add_settings_field( 'num_products_per_search', __( 'Products per Search', DFRPS_DOMAIN ), array( &$this, 'field_num_products_per_search' ), $this->page, 'default_search_filters' );
			add_settings_field( 'default_filters', __( 'Default Search Setting', DFRPS_DOMAIN ), array( &$this, 'field_default_filters' ), $this->page, 'default_search_filters' );

			add_settings_section( 'general-update', __( 'General Update Settings', DFRPS_DOMAIN ), array( &$this, 'section_general_update_desc' ), $this->page );
			add_settings_field( 'default_cpt', __( 'Default Custom Post Type', DFRPS_DOMAIN ), array( &$this, 'field_default_cpt' ), $this->page, 'general-update' );
			add_settings_field( 'delete_missing_products', __( 'Delete Missing Products', DFRPS_DOMAIN ), array( &$this, 'field_delete_missing_products' ), $this->page, 'general-update' );
			add_settings_field( 'updates_enabled', __( 'Updates', DFRPS_DOMAIN ), array( &$this, 'field_updates_enabled' ), $this->page, 'general-update' );
			
			add_settings_section( 'advanced-update', __( 'Advanced Update Settings', DFRPS_DOMAIN ), array( &$this, 'section_advanced_update_desc' ), $this->page );
			add_settings_field( 'update_interval', __( 'Update Interval', DFRPS_DOMAIN ), array( &$this, 'field_update_interval' ), $this->page, 'advanced-update' );
			add_settings_field( 'cron_interval', __( 'Cron Interval', DFRPS_DOMAIN ), array( &$this, 'field_cron_interval' ), $this->page, 'advanced-update' );
			add_settings_field( 'num_products_per_update', __( 'Products per Update', DFRPS_DOMAIN ), array( &$this, 'field_num_products_per_update' ), $this->page, 'advanced-update' );
			add_settings_field( 'preprocess_maximum', __( 'Preprocess Maximum', DFRPS_DOMAIN ), array( &$this, 'field_preprocess_maximum' ), $this->page, 'advanced-update' );
			add_settings_field( 'postprocess_maximum', __( 'Postprocess Maximum', DFRPS_DOMAIN ), array( &$this, 'field_postprocess_maximum' ), $this->page, 'advanced-update' );
		}

		function section_default_search_filters_desc() {
			//echo __( 'The following settings control the search form when you are searching for products to add to Product Sets.', DFRPS_DOMAIN );		
		}
	
		function section_general_update_desc() { 
			//echo __( 'Set up how updates are performed.', DFRPS_DOMAIN );
		}
	
		function section_advanced_update_desc() { 
			echo '<p class="dfrps_warning">';
			echo '<strong>' . __( 'WARNING', DFRPS_DOMAIN ) . '</strong> - ';
			echo __( 'Modifying the following settings could have severely negative effects on your server. We recommend you do not change the default settings unless you are sure that your server can handle the change.', DFRPS_DOMAIN );
			echo '</p>';
		}
		
		function field_num_products_per_search() {
			?>
			<select id="num_products_per_search" name="<?php echo $this->key; ?>[num_products_per_search]">
			<?php for ( $i=10; $i<=$this->account['max_length']; $i+=10 ) : ?>
				<option value="<?php echo $i; ?>" <?php selected( $this->options['num_products_per_search'], $i, true ); ?>><?php echo $i; ?></option>
			<?php endfor; ?>
			</select>
			<p class="description"><?php _e( 'Set the number of products to display per page of search results.', DFRPS_DOMAIN ); ?></p>
			<?php				
		}
		
		function field_default_filters() {
			?>
			<table class="widefat" id="dfrps_search_form_wrapper">
				<tbody>
					<tr>
						<td class="form">
							<?php
							$sform = new Dfrapi_SearchForm();
							echo $sform->render( $this->key . '[dfrps_query]', $this->options['default_filters']['dfrps_query'] );
							?>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="description"><?php _e( 'Set the default search parameters for creating new Product Sets.', DFRPS_DOMAIN ); ?></p>
			<?php
		}
		
		function field_default_cpt() {
		
			$registered_cpts = get_option( 'dfrps_registered_cpts', array() );
			$num_registered_cpts = count( $registered_cpts );
					
			// First check if there are any registered CPTs
			if ( $num_registered_cpts == 0 ) {
				
				// If there are no registered CPTs, notify user that no CPTs are registered for use with DFRPS.
				echo '<span class="dfrps_warning">';
				_e( 'No custom post types have been registered for use with the <em>Datafeedr Product Sets</em> plugin.', DFRPS_DOMAIN );
				echo '<br />';
				_e( 'Get an Importer Plugin ', DFRPS_DOMAIN );
				echo '<a href="' . admin_url('plugins.php') . '" target="_blank">';
				_e( ' here', DFRPS_DOMAIN );
				echo '</a>.';
				echo '</span>';
				
			} elseif ( $num_registered_cpts == 1 ) {
				
				// If there is 1 registered CPT, just display the name of the registered CPT.
				foreach ( $registered_cpts as $cpt ) {
					echo '<p><input type="radio" name="' . $this->key . '[default_cpt]" value="' . $cpt['post_type'] . '" '.checked( $this->options['default_cpt'], $cpt['post_type'], false ).' /> ' . $cpt['name'] . '</p>';
				}
				
			} elseif ( $num_registered_cpts > 1 ) {
				// If there is more than 1 registered CPT, display radio buttons which allows the user to choose their default CPT.
				foreach ( $registered_cpts as $cpt ) {
					echo '<p><input type="radio" name="' . $this->key . '[default_cpt]" value="' . $cpt['post_type'] . '" '.checked( $this->options['default_cpt'], $cpt['post_type'], false ).' /> ' . $cpt['name'] . '</p>';
				}
			}
			echo '<p class="description">' . __( 'Set the custom post type your Product Sets will import into.', DFRPS_DOMAIN ) . '</p>';
		}
		
		function field_delete_missing_products() {
			?>
			<p><input type="radio" value="yes" name="<?php echo $this->key; ?>[delete_missing_products]" <?php checked( $this->options['delete_missing_products'], 'yes', true ); ?> /> <?php _e( 'Yes', DFRPS_DOMAIN ); ?></p>
			<p><input type="radio" value="no" name="<?php echo $this->key; ?>[delete_missing_products]" <?php checked( $this->options['delete_missing_products'], 'no', true ); ?> /> <?php _e( 'No', DFRPS_DOMAIN ); ?></p>
			<p class="description"><?php _e( 'Delete products which are no longer available in the API.', DFRPS_DOMAIN ); ?></p>
			<?php
		}
		
		function field_updates_enabled() {
			?>
			<p><input type="radio" value="yes" name="<?php echo $this->key; ?>[updates_enabled]" <?php checked( $this->options['updates_enabled'], 'enabled', true ); ?> /> <?php _e( 'Enabled', DFRPS_DOMAIN ); ?></p>
			<p><input type="radio" value="no" name="<?php echo $this->key; ?>[updates_enabled]" <?php checked( $this->options['updates_enabled'], 'disabled', true ); ?> /> <?php _e( 'Disabled', DFRPS_DOMAIN ); ?></p>
			<p class="description"><?php _e( 'Enable or disable Product Set updates. Disabling updates will not immediately affect your website, however, over time your product data will become outdated.', DFRPS_DOMAIN ); ?></p>
			<?php
		}
		
		function field_update_interval() {
			?>
			<select id="update_interval" name="<?php echo $this->key; ?>[update_interval]">
				<option value="-1" <?php selected( $this->options['update_interval'], '-1', true ); ?>><?php _e( 'Continuous', DFRPS_DOMAIN ); ?></option>
				<option value="1" <?php selected( $this->options['update_interval'], '1', true ); ?>><?php _e( 'Every Day', DFRPS_DOMAIN ); ?></option>
				<option value="2" <?php selected( $this->options['update_interval'], '2', true ); ?>><?php _e( 'Every 2 Days', DFRPS_DOMAIN ); ?></option>
				<option value="3" <?php selected( $this->options['update_interval'], '3', true ); ?>><?php _e( 'Every 3 Days', DFRPS_DOMAIN ); ?></option>
				<option value="4" <?php selected( $this->options['update_interval'], '4', true ); ?>><?php _e( 'Every 4 Days', DFRPS_DOMAIN ); ?></option>
				<option value="5" <?php selected( $this->options['update_interval'], '5', true ); ?>><?php _e( 'Every 5 Days', DFRPS_DOMAIN ); ?></option>
				<option value="6" <?php selected( $this->options['update_interval'], '6', true ); ?>><?php _e( 'Every 6 Days', DFRPS_DOMAIN ); ?></option>
				<option value="7" <?php selected( $this->options['update_interval'], '7', true ); ?>><?php _e( 'Every 7 Days', DFRPS_DOMAIN ); ?></option>
				<option value="10" <?php selected( $this->options['update_interval'], '10', true ); ?>><?php _e( 'Every 10 Days', DFRPS_DOMAIN ); ?></option>
				<option value="14" <?php selected( $this->options['update_interval'], '14', true ); ?>><?php _e( 'Every 14 Days', DFRPS_DOMAIN ); ?></option>
				<option value="21" <?php selected( $this->options['update_interval'], '21', true ); ?>><?php _e( 'Every 21 Days', DFRPS_DOMAIN ); ?></option>
				<option value="30" <?php selected( $this->options['update_interval'], '30', true ); ?>><?php _e( 'Every 30 Days', DFRPS_DOMAIN ); ?></option>
				<option value="45" <?php selected( $this->options['update_interval'], '45', true ); ?>><?php _e( 'Every 45 Days', DFRPS_DOMAIN ); ?></option>
				<option value="60" <?php selected( $this->options['update_interval'], '60', true ); ?>><?php _e( 'Every 60 Days', DFRPS_DOMAIN ); ?></option>
			</select>
			<p class="description"><?php _e( 'How often a Product Set will be updated.<br />If updates are causing too much load on your server, update less frequently.', DFRPS_DOMAIN ); ?></p>
			<?php  
		}
		
		function field_cron_interval() {
			?>
			<select id="cron_interval" name="<?php echo $this->key; ?>[cron_interval]">
				<option value="10" <?php selected( $this->options['cron_interval'], '10', true ); ?>><?php _e( 'Every 10 seconds', DFRPS_DOMAIN ); ?></option>
				<option value="30" <?php selected( $this->options['cron_interval'], '30', true ); ?>><?php _e( 'Every 30 seconds', DFRPS_DOMAIN ); ?></option>
				<option value="60" <?php selected( $this->options['cron_interval'], '60', true ); ?>><?php _e( 'Every minute', DFRPS_DOMAIN ); ?></option>
				<option value="120" <?php selected( $this->options['cron_interval'], '120', true ); ?>><?php _e( 'Every 2 minutes', DFRPS_DOMAIN ); ?></option>
				<option value="300" <?php selected( $this->options['cron_interval'], '300', true ); ?>><?php _e( 'Every 5 minutes', DFRPS_DOMAIN ); ?></option>
				<option value="600" <?php selected( $this->options['cron_interval'], '600', true ); ?>><?php _e( 'Every 10 minutes', DFRPS_DOMAIN ); ?></option>
				<option value="900" <?php selected( $this->options['cron_interval'], '900', true ); ?>><?php _e( 'Every 15 minutes', DFRPS_DOMAIN ); ?></option>
				<option value="1800" <?php selected( $this->options['cron_interval'], '1800', true ); ?>><?php _e( 'Every 30 minutes', DFRPS_DOMAIN ); ?></option>
				<option value="3600" <?php selected( $this->options['cron_interval'], '3600', true ); ?>><?php _e( 'Every hour', DFRPS_DOMAIN ); ?></option>
			</select>
			<p class="description"><?php _e( 'How often WordPress Cron will check if Product Sets should be updated.<br />Increase this value if you are experiencing server load or timeout issues.', DFRPS_DOMAIN ); ?></p>
			<?php  
		}
				
		function field_num_products_per_update() {
			?>
			<select id="num_products_per_update" name="<?php echo $this->key; ?>[num_products_per_update]">
				<option value="10" <?php selected( $this->options['num_products_per_update'], '10', true ); ?>><?php _e( '10', DFRPS_DOMAIN ); ?></option>
				<option value="25" <?php selected( $this->options['num_products_per_update'], '25', true ); ?>><?php _e( '25', DFRPS_DOMAIN ); ?></option>
				<option value="50" <?php selected( $this->options['num_products_per_update'], '50', true ); ?>><?php _e( '50', DFRPS_DOMAIN ); ?></option>
				<option value="75" <?php selected( $this->options['num_products_per_update'], '75', true ); ?>><?php _e( '75', DFRPS_DOMAIN ); ?></option>
				<option value="100" <?php selected( $this->options['num_products_per_update'], '100', true ); ?>><?php _e( '100', DFRPS_DOMAIN ); ?></option>
			</select>
			<p class="description"><?php _e( 'The number of products per batch to update.<br />This process is server intensive. Reduce this number if you are experiencing server load or timeout issues.', DFRPS_DOMAIN ); ?></p>
			<?php		
		}
		
		function field_preprocess_maximum() {
			?>
			<select id="preprocess_maximum" name="<?php echo $this->key; ?>[preprocess_maximum]">
				<?php for ( $i=25; $i<100; $i+=25 ) : ?>
					<option value="<?php echo $i; ?>" <?php selected( $this->options['preprocess_maximum'], $i, true ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				<?php for ( $i=100; $i<=1000; $i+=100 ) : ?>
					<option value="<?php echo $i; ?>" <?php selected( $this->options['preprocess_maximum'], $i, true ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
			<p class="description"><?php _e( 'The number of products per batch to prepare for updating or deleting.<br />This process is server intensive. Reduce this number if you are experiencing server load or timeout issues.', DFRPS_DOMAIN ); ?></p>
			<?php				
		}
		
		function field_postprocess_maximum() {
			?>
			<select id="postprocess_maximum" name="<?php echo $this->key; ?>[postprocess_maximum]">
				<?php for ( $i=25; $i<100; $i+=25 ) : ?>
					<option value="<?php echo $i; ?>" <?php selected( $this->options['postprocess_maximum'], $i, true ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
				<?php for ( $i=100; $i<=1000; $i+=100 ) : ?>
					<option value="<?php echo $i; ?>" <?php selected( $this->options['postprocess_maximum'], $i, true ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
			<p class="description"><?php _e( 'The number of products per batch to delete.<br />This process is server intensive. Reduce this number if you are experiencing server load or timeout issues.', DFRPS_DOMAIN ); ?></p>
			<?php				
		}
		
		function validate( $input ) {
			
			if ( !isset( $input ) || !is_array( $input ) || empty( $input ) ) { return $input; }
			
			$new_input = array();
			
			foreach( $input as $key => $value ) {
			
				// Validate "update_interval"
				if ( $key == 'update_interval' ) {
					$value = intval( $value );
					if ( $value < -1 || $value > 60 ) {
						$new_input['update_interval'] = 3;
					} else {
						$new_input['update_interval'] = $value;
					}
				}
			
				// Validate "cron_interval"
				if ( $key == 'cron_interval' ) {
					$value = intval( $value );
					if ( $value < 10 || $value > 3600 ) {
						$new_input['cron_interval'] = 300;
					} else {
						$new_input['cron_interval'] = $value;
					}
				}	
			
				// Validate "num_products_per_update"
				if ( $key == 'num_products_per_update' ) {
					$value = intval( $value );
					if ( $value < 10 || $value > 100 ) {
						$new_input['num_products_per_update'] = 100;
					} else {
						$new_input['num_products_per_update'] = $value;
					}
				}
			
				// Validate "preprocess_maximum"
				if ( $key == 'preprocess_maximum' ) {
					$value = intval( $value );
					if ( $value < 25 || $value > 1000 ) {
						$new_input['preprocess_maximum'] = 100;
					} else {
						$new_input['preprocess_maximum'] = $value;
					}
				}
			
				// Validate "postprocess_maximum"
				if ( $key == 'postprocess_maximum' ) {
					$value = intval( $value );
					if ( $value < 25 || $value > 1000 ) {
						$new_input['postprocess_maximum'] = 500;
					} else {
						$new_input['postprocess_maximum'] = $value;
					}
				}
			
				// Validate "num_products_per_search"
				if ( $key == 'num_products_per_search' ) {
					$value = intval( $value );
					if ( $value < 10 ) {
						$new_input['num_products_per_search'] = 10;
					} elseif ( $value > $this->account['max_length'] ) {
						$new_input['num_products_per_search'] = $this->account['max_length'];
					} else {
						$new_input['num_products_per_search'] = $value;
					}
				}
			
				// Validate "default_cpt"
				if ( $key == 'default_cpt' ) {
					$new_input['default_cpt'] = trim( $value );
				}
			
				// Validate "delete_missing_products"
				if ( $key == 'delete_missing_products' ) {
					if ( $value == 'yes' ) {
						$new_input['delete_missing_products'] = 'yes';
					} else {
						$new_input['delete_missing_products'] = 'no';					
					}
				}
			
				// Validate "updates_enabled"
				if ( $key == 'updates_enabled' ) {
					if ( $value == 'yes' ) {
						$new_input['updates_enabled'] = 'enabled';
					} else {
						$new_input['updates_enabled'] = 'disabled';					
					}
				}
						
				// Validate "default_filters"
				if ( $key == 'dfrps_query' ) {
					$new_input['default_filters']['dfrps_query'] = $value;
				}
		
			} // foreach
			
			return $new_input;
		}
		
	} // class Dfrps_Configuration

} // class_exists check
