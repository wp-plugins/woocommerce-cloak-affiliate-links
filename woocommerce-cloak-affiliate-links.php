<?php
/*
Plugin Name: WooCommerce Cloak Affiliate Links
Version: 1.0.3
Plugin URI: https://v4.datafeedr.com
Description: Cloak your WooCommerce external & affiliate links.
Author: datafeedr.com
Author URI: https://v4.datafeedr.com
License: GPL v3
Requires at least: 3.8
Tested up to: 3.9

WooCommerce Cloak Affiliate Links plugin
Copyright (C) 2014, Datafeedr - eric@datafeedr.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define constants.
 */
define( 'WCCAL_VERSION', 	'1.0.3' );
define( 'WCCAL_URL', 		plugin_dir_url( __FILE__ ) );
define( 'WCCAL_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'WCCAL_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'WCCAL_DOMAIN', 	'wccal' );

if ( ! class_exists( 'Wccal' ) ) {

	/**
	 * Configuration page.
	 */
	class Wccal {
	
		public function __construct() {
		
			register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
		
			$this->base 	= self::get_affiliate_base();
			$this->options 	= $this->load_options();
			
			add_filter( 'query_vars', 					array( $this, 'query_vars' ) );
			add_filter( 'rewrite_rules_array', 			array( $this, 'rewrite_rules_array' ) );
			add_filter( 'woocommerce_product_class', 	array( $this, 'woocommerce_product_class' ), 40, 4 );
			add_filter( 'robots_txt', 					array( $this, 'robots_txt' ), 10, 2 );
			add_filter( 'plugin_action_links_' . WCCAL_BASENAME, array( $this, 'action_links' ) );
			 
			add_action( 'template_redirect', 			array( $this, 'template_redirect' ) );
			add_action( 'plugins_loaded', 				array( $this, 'plugins_loaded' ) );
			add_action( 'admin_init', 					array( $this, 'permalink_settings_init' ) );
			add_action( 'admin_init', 					array( $this, 'permalink_settings_save' ) );
			add_action( 'admin_menu', 					array( $this, 'options_page' ) );
			add_action( 'admin_init', 					array( $this, 'register_settings' ) );
			add_action( 'wccal_clickthrough', 			array( $this, 'count_clickthrough' ) );
		}
		
		/**
		 * Flush rerwrite rules on plugin activation.
		 */		
		function activate_plugin() {
			flush_rewrite_rules();
		}

		/**
		 * Flush rerwrite rules on plugin deactivation.
		 */	
		function deactivate_plugin() {
			flush_rewrite_rules();
		}
		
		/**
		 * Add "permalink" path to robots.txt file.
		 */
		function robots_txt( $output, $public ) {
			if ( $this->options['robots'] == 'yes' &&  get_option('permalink_structure') ) {
				$site_url = parse_url( site_url() );
				$path = ( !empty( $site_url['path'] ) ) ? $site_url['path'] : '';
				$text = "Disallow: $path/" . $this->base . "/\n";
				$text = apply_filters( 'wccal_robots_txt', $text );
				$output .= $text;
			}
			return $output;
		}
		
		/**
		 * Get the base permalink settings.
		 */
		static public function get_affiliate_base() {
			$permalinks = get_option( 'wccal_permalinks' );
			if ( ! $permalinks || !isset( $permalinks['affiliate_base'] ) || $permalinks['affiliate_base'] == '' ) {
				return 'redirect';
			}
			return $permalinks['affiliate_base'];
		}

		/**
		 * Set default option values.
		 */
		function default_options() {
			return array(
				'status' => '302',
				'robots' => 'yes',
			);
		}

		/**
		 * Load default or configured options.
		 */
		function load_options() {
			$options = array_merge( 
				$this->default_options(), 
				get_option( 'wccal_options', array() ) 
			);
			update_option( 'wccal_options', $options );
			return $options;
		}
		
		/**
		 * Add "Settings" page to "Settings" menu.
		 */
		function options_page() {  
			add_options_page( 
				__( 'WooCommerce Cloak Affiliate Links Settings', WCCAL_DOMAIN ), 
				__( 'WC Cloak Links', WCCAL_DOMAIN ), 
				'manage_options', 
				'wccal-options', 
				array( $this, 'build_options_page' )
			);
		}
		
		/**
		 * Add "Settings" link to plugin page.
		 */
		function action_links( $links ) {
			return array_merge(
				array(
					'settings' => '<a href="' . admin_url( 'options-general.php?page=wccal-options' ) . '">' . __( 'Settings', WCCAL_DOMAIN ) . '</a>',
				),
				$links
			);
		}
		
		/**
		 * Set up the options page to configure the WCCAL plugin.
		 */
		function build_options_page() {
			echo '<div class="wrap" id="wccal_options">';
			echo '<h2>' . __( 'WooCommerce Cloak Affiliate Links Settings', WCCAL_DOMAIN ) . '</h2>';
			echo '<form method="post" action="options.php">';
			wp_nonce_field( 'update-wccal-options' );
			settings_fields( 'wccal-options' );
			do_settings_sections( 'wccal-options' );
			submit_button();
			echo '</form>';		
			echo '</div>';
		}

		/**
		 * Register settings.
		 */
		function register_settings() {
			register_setting( 'wccal-options', 'wccal_options', array( $this, 'validate' ) );
			add_settings_section( 'general_settings', __( 'General Settings', WCCAL_DOMAIN ), array( &$this, 'section_general_settings_desc' ), 'wccal-options' );
			add_settings_field( 'status', __( 'Status Code', WCCAL_DOMAIN ), array( &$this, 'field_status' ), 'wccal-options', 'general_settings' );
			if (  get_option('permalink_structure') ) {
				add_settings_field( 'robots', __( 'Add Redirect Path to Robots.txt', WCCAL_DOMAIN ), array( &$this, 'field_robots' ), 'wccal-options', 'general_settings' );
			}
		}

		/**
		 * General settings decription.
		 */
		function section_general_settings_desc() {
			// _e( 'General plugin settings.', WCCAL_DOMAIN );
		}

		/**
		 * Field to select Status Code.
		 */
		function field_status() { ?>
			<select id="wwcal_status" name="wccal_options[status]">
				<option value="301" <?php selected( $this->options['status'], '301', true ); ?>><?php _e( '301 (Moved Permanently)', WCCAL_DOMAIN ); ?></option>
				<option value="302" <?php selected( $this->options['status'], '302', true ); ?>><?php _e( '302 (Found/Temporary Redirect)', WCCAL_DOMAIN ); ?></option>
				<option value="307" <?php selected( $this->options['status'], '307', true ); ?>><?php _e( '307 (Temporary Redirect)', WCCAL_DOMAIN ); ?></option>
			</select>
			<p class="description"><?php _e( 'The status code to use when performing the redirect', WCCAL_DOMAIN ); ?></p>
		<?php
		}

		/**
		 * Field to enabled/disable robots.txt.
		 */
		function field_robots() { ?>
			<p><input type="radio" value="yes" name="wccal_options[robots]" <?php checked( $this->options['robots'], 'yes', true ); ?> /> <?php _e( 'Yes', WCCAL_DOMAIN ); ?></p>
			<p><input type="radio" value="no" name="wccal_options[robots]" <?php checked( $this->options['robots'], 'no', true ); ?> /> <?php _e( 'No', WCCAL_DOMAIN ); ?></p>
			<p class="description"><?php _e( 'Add the path configured on your Permalinks page to your robots.txt file to prevent any search engines from attempting to view or index that path.', WCCAL_DOMAIN ); ?></p>
		<?php
		}
		
		/**
		 * Register a new var.
		 */
		function query_vars( $vars) {
			$vars[] = $this->base;
			return $vars;
		}

		/**
		 * Add the new rewrite rule to existings ones.
		 */
		function rewrite_rules_array( $rules ) {
			$new_rules = array( $this->base . '/([^/]+)/?$' => 'index.php?' . $this->base . '=$matches[1]' );
			$rules = $new_rules + $rules;
			return $rules;
		}

		/**
		 * Redirect the user to external link.
		 */
		function template_redirect() {
			
			global $wp_query;
			
			if ( isset( $wp_query->query_vars[$this->base] ) ) {
	
				$post_id = intval( get_query_var( $this->base ) );
				$external_link = get_post_meta( $post_id, '_product_url', true );
				$external_link = apply_filters( 'wccal_filter_url', $external_link, $post_id );
				$external_link = trim( $external_link );
				
				if ( $external_link != '' ) { 
					$url = $external_link;
					do_action( 'wccal_clickthrough', $post_id );
				} else {
					$url = get_permalink( $post_id );
					do_action( 'wccal_clickthrough_fail', $post_id );
				}
				
				wp_redirect( $url, $this->options['status'] );
				exit();
			}
		}

		/**
		 * Add 1 to clickthrough count.
		 */
		function count_clickthrough( $post_id ) {
			$count = intval( get_post_meta( $post_id, '_wccal_clickthrough_count', true ) );
			update_post_meta( $post_id, '_wccal_clickthrough_count', ( $count + 1 ) );
		}

		/**
		 * Change "WC_Product_External" class to our own class if class is "WC_Product_External".
		 */
		function woocommerce_product_class( $classname, $product_type, $post_type, $product_id ) {
			return ( $classname != 'WC_Product_External' ) ? $classname : 'Wccal_Product_External';
		}

		/**
		 * This loads our class only after all plugins have loaded.
		 */
		function plugins_loaded() {
			if ( ! class_exists( 'WC_Product_External' ) ) { return; }			
			require_once( WCCAL_PATH . 'class-wccal-product-external.php' );
		}
		
		/**
		 * permalink_settings_init function.
		 */
		function permalink_settings_init() {
			add_settings_field(
				'wccal_redirect_slug',
				__( 'Affiliate link base', WCCAL_DOMAIN ),
				array( $this, 'permalink_input' ),
				'permalink',
				'optional'
			);
		}

		/**
		 * permalink_input function.
		 */
		function permalink_input() {
			$permalinks = get_option( 'wccal_permalinks' );
			?>
			<input name="wccal_affiliate_base" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['affiliate_base'] ) ) echo esc_attr( $permalinks['affiliate_base'] ); ?>" placeholder="<?php echo _x( 'redirect', 'slug', WCCAL_DOMAIN ); ?>" /><code>/%post_id%/</code>
			<?php
		}
		
		/**
		 * permalink_settings_save function.
		 */
		function permalink_settings_save() {
			if ( ! is_admin() ) { return; }
			if ( isset( $_POST['wccal_affiliate_base'] ) ) {
				$wccal_affiliate_base = woocommerce_clean( $_POST['wccal_affiliate_base'] );
				$permalinks = get_option( 'wccal_permalinks' );
				if ( ! $permalinks ) {
					$permalinks = array();
				}
				$permalinks['affiliate_base'] = untrailingslashit( $wccal_affiliate_base );
				update_option( 'wccal_permalinks', $permalinks );
			}
		}

		/**
		 * Validate options submitted.
		 */
		function validate( $input ) {			
			if ( !isset( $input ) || !is_array( $input ) || empty( $input ) ) { return $input; }
			$new_input = array();
			foreach( $input as $key => $value ) {
				if ( $key == 'status' ) { $new_input['status'] = $value; }							
				if ( $key == 'robots' ) { $new_input['robots'] = $value; }				
			}
			return $new_input;
		}
	
	
	} // class Wccal
	
	new Wccal();

} // class_exists check

