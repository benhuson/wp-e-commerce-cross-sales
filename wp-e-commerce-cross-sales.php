<?php

/*
Plugin Name: WP e-Commerce Cross Sales (Also Bought)
Description: This plugin will add 'also bought' cross-sale functionality to WP e-Commerce 3.9+ (which must be installed and active for this plugin to do anything). This is the same functionality that existed as standard in previous versions of WP e-Commerce.
Author: Ben Huson
Version: 0.2
*/

class WPEC_CrossSales {
	
	var $db_version            = 1;
	var $required_wp_version   = '3.0';
	var $required_wpsc_version = '3.9';
	var $plugin_file           = __FILE__;
	var $db_table              = 'wpsc_also_bought';
	var $product_id            = null;
	var $admin;
	
	/**
	 * Cross Sales class constructor.
	 */
	function WPEC_CrossSales() {
		global $wpdb;
		
		// Language
		load_plugin_textdomain( 'wpsc-cross-sales', false, dirname( $this->plugin_file ) . '/languages' );
		
		// Hooks
		add_action( 'init', 'wpsc_alsobought_init', 10 );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'wpsc_submit_checkout', array( $this, 'wpsc_submit_checkout' ) );
		
		// Admin
		if ( is_admin() ) {
			require_once( dirname( $this->plugin_file ) . '/includes/admin.php' );
			$this->admin = new WPEC_CrossSales_Admin();
		}
		
		// Activation
		register_activation_hook( $this->plugin_file, array( $this, 'register_activation_hook' ) );
	}
	
	/**
	 * Get DB Table
	 *
	 * @return string Database table reference.
	 */
	function get_db_table() {
		global $wpdb;
		return $wpdb->prefix . $this->db_table;
	}
	
	/**
	 * Cross Sales Product ID
	 */
	function product_id() {
		if ( $this->product_id != null ) {
			return $this->product_id;
		}
		return wpsc_the_product_id();
	}
	
	/**
	 * Displays products that were bought along with the product defined
	 * by $product_id. Most of it scarcely needs describing
	 * Originally called 'wpsc_also_bought' and defined in wpsc-includes/display.functions.php
	 * @todo Use WP_Query?
	 */
	function cross_sales( $product_id ) {
		global $wpdb;
		
		// Returns nothing if this is off or set to display none
		if ( get_option( 'wpsc_also_bought' ) == 0 || get_option( 'wpsc_also_bought_limit' ) == 0 ) {
			return '';
		}
		
		// Theme file
		require_once( dirname( $this->plugin_file ) . '/theme/wpsc-cross-sales.php' );
	}
	
	/**
	 * Save cross sale information on checkout submit
	 * Originally called 'wpsc_alsobought_submit_checkout' and located in wpsc-includes/ajax.functions.php
	 *
	 * @param array $args Array of 'purchase_log_id' and 'our_user_id'
	 */
	function wpsc_submit_checkout( $args ) {
		// Only do this if wpsc_populate_also_bought_list function does not exist
		// Originally defined in wpsc-includes/misc.functions.php
		if ( ! function_exists( 'wpsc_populate_also_bought_list' ) || wpsc_populate_also_bought_list() === false ) {
			if ( get_option( 'wpsc_also_bought' ) == 1 ) {
				$log = new WPSC_Purchase_Log( $args['purchase_log_id'] );
				$cart_contents = $log->get_cart_contents();
				$also_bought_data = $this->get_cart_cross_sale_data( $cart_contents );
				$this->populate_also_bought_list( $also_bought_data );
			}
		}
		
	}
	
	/**
	 * Populate also bought list.
	 * Runs on checking out, populates the also bought list.
	 * Originally defined in wpsc-includes/misc.functions.php
	 */
	function populate_also_bought_list( $new_also_bought_data ) {
		global $wpdb;
		
		$insert_statement_parts = array();
		foreach ( $new_also_bought_data as $new_also_bought_id => $new_also_bought_row ) {
			$new_other_ids = array_keys( $new_also_bought_row );
			$also_bought_data = $wpdb->get_results( "SELECT `id`, `associated_product`, `quantity` FROM `" . $this->get_db_table() . "` WHERE `selected_product` IN('$new_also_bought_id') AND `associated_product` IN('" . implode( "','", $new_other_ids ) . "')", ARRAY_A );
			$altered_new_also_bought_row = $new_also_bought_row;
			
			// Update existing data
			foreach ( (array)$also_bought_data as $also_bought_row ) {
				$quantity = $new_also_bought_row[$also_bought_row['associated_product']] + $also_bought_row['quantity'];
				unset( $altered_new_also_bought_row[$also_bought_row['associated_product']] );
				$wpdb->query( "UPDATE `" . $this->get_db_table() . "` SET `quantity` = {$quantity} WHERE `id` = '{$also_bought_row['id']}' LIMIT 1;" );
			}
			
			// Collect new data
			if ( count( $altered_new_also_bought_row ) > 0 ) {
				foreach ( $altered_new_also_bought_row as $associated_product => $quantity ) {
					$insert_statement_parts[] = '(' . absint( $new_also_bought_id ) . ',' . absint( $associated_product ) . ',' . absint( $quantity ) . ')';
				}
			}
		}
		
		// Bulk insert all new data
		if ( count( $insert_statement_parts ) > 0 ) {
			$insert_statement = "INSERT INTO `" . $this->get_db_table() . "` (`selected_product`, `associated_product`, `quantity`) VALUES " . implode( ",\n ", $insert_statement_parts );
			$wpdb->query( $insert_statement );
		}
	}
	
	/**
	 * Get Cart Cross Sale Data.
	 * Returns a multi-dimensional array of product IDs, cross sale products
	 * and their quantities for this cart.
	 *
	 * @param array $cart_contents Array of cart items.
	 * @return array Multi-dimensional array of product IDs.
	 */
	function get_cart_cross_sale_data( $cart_contents ) {
		$cross_sale_data = array();
		foreach ( $cart_contents as $outer_cart_item ) {
			$cross_sale_data[$outer_cart_item->prodid] = array();
			foreach ( $cart_contents as $inner_cart_item ) {
				if ( $outer_cart_item->prodid != $inner_cart_item->prodid ) {
					$cross_sale_data[$outer_cart_item->prodid][$inner_cart_item->prodid] = $inner_cart_item->quantity;
				}
			}
		}
		return $cross_sale_data;
	}
	
	/**
	 * Plugins Loaded
	 * Update DB Schema if required.
	 */
	function plugins_loaded() {
		// Update DB Schema
		$installed_db_version = get_option( 'wpsc_crosssales_db_version', 0 );
		if ( $installed_db_version != $this->db_version ) {
			require_once( dirname( $this->plugin_file ) . '/includes/install.php' );
			$install = new WPEC_CrossSales_Install();
			$install->upgrade_db_schema( $this->db_version );
			$install->set_default_options();
		}
	}
	
	/**
	 * Register Activation Hook
	 */
	function register_activation_hook() {
		global $wp_version;
		if ( (float)$wp_version < $this->required_wp_version ) {
			deactivate_plugins( plugin_basename( $this->plugin_file ) );
			wp_die( sprintf( __( "Looks like you're running an older version of WordPress, you need to be running at least WordPress %s to use the WP e-Commerce Cross Sales plugin.", 'wpsc-cross-sales' ), $this->required_wp_version ), __( 'WP e-Commerce Cross Sales not compatible', 'wpsc-cross-sales' ), array( 'back_link' => true ) );
			return;
		}
	}
	
	/**
	 * If WP e-Commerce is installed
	 */
	function wpec_is_installed() {
		if ( defined( 'WPSC_VERSION' ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * If WP e-Commerce is installed and compatible
	 */
	function wpec_is_compatible() {
		if ( $this->wpec_is_installed() && ( ! function_exists( 'wpsc_populate_also_bought_list' ) || ! wpsc_populate_also_bought_list() ) ) {
			return true;
		}
		return false;
	}
	
}

// Start WPEC
global $wpec_cross_sales;
$wpec_cross_sales = new WPEC_CrossSales();

function wpsc_cross_sales( $product_id = 0 ) {
	global $wpec_cross_sales;
	return $wpec_cross_sales->cross_sales( $product_id );
}

function wpsc_cross_sales_product_id() {
	global $wpec_cross_sales;
	return $wpec_cross_sales->product_id();
}

function wpsc_alsobought_init() {
	/**
	 * Displays products that were bought along with the product defined
	 * by $product_id. Most of it scarcely needs describing
	 * Originally defined in wpsc-includes/display.functions.php
	 */
	if ( !function_exists( 'wpsc_also_bought' ) ) {
		function wpsc_cross_sales( $product_id ) {
			global $wpec_cross_sales;
			return $wpec_cross_sales->cross_sales( $product_id );
		}
	}
	
}

?>