<?php

/*
Plugin Name: WP e-Commerce Cross Sales (Also Bought)
Description: This plugin will add 'also bought' cross-sale functionality to WP e-Commerce 3.9+ (which must be installed and active for this plugin to do anything). This is the same functionality that existed as standard in previous versions of WP e-Commerce.
Author: Ben Huson
Version: 0.3
*/

class WPEC_CrossSales {
	
	var $db_version            = 1;
	var $required_wp_version   = '3.0';
	var $required_wpsc_version = '3.9';
	var $plugin_file           = __FILE__;
	var $db_table              = 'wpsc_also_bought';
	var $product_id            = null;
	var $admin;
	
	var $default_also_bought_limit = 3;
	var $default_crosssale_image_size = 96;
	
	/**
	 * Cross Sales class constructor.
	 */
	function WPEC_CrossSales() {
		global $wpdb;
		
		// Language
		load_plugin_textdomain( 'wpsc-cross-sales', false, dirname( $this->plugin_file ) . '/languages' );
		
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		if ( $this->wpec_is_compatible() ) {
			
			// Hooks
			add_action( 'init', array( $this, 'disable_wpsc_populate_also_bought_list'), 0 );
			add_action( 'wpsc_submit_checkout', array( $this, 'wpsc_submit_checkout' ), 5 );
			add_filter( '_wpsc_also_bought', array( $this, '_wpsc_also_bought' ), 10, 2 );
			
			add_filter( 'option_wpsc_also_bought_limit', array( $this, 'default_wpsc_also_bought_limit' ), 5 );
			add_filter( 'option_wpsc_crosssale_image_width', array( $this, 'default_wpsc_crosssale_image_size' ), 5 );
			add_filter( 'option_wpsc_crosssale_image_height', array( $this, 'default_wpsc_crosssale_image_size' ), 5 );
		}
		
		// Activation
		register_activation_hook( $this->plugin_file, array( $this, 'register_activation_hook' ) );
	}
	
	function default_wpsc_also_bought_limit( $value ) {
		if ( ! is_numeric( $value ) || empty( $value ) )
			$value = $this->default_also_bought_limit;
		return $value;
	}
	
	function default_wpsc_crosssale_image_size( $value ) {
		if ( empty( $value ) )
			$value = $this->default_crosssale_image_size;
		return $value;
	}
	
	function disable_wpsc_populate_also_bought_list(){
		remove_action( 'wpsc_submit_checkout', 'wpsc_populate_also_bought_list' );
	}
	
	function _wpsc_also_bought( $output, $product_id ) {
		return $this->cross_sales( $product_id );
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
		global $wpdb, $wpec_cross_sales;
		
		$output = '';
		
		// Returns nothing if this is off or set to display none
		if ( get_option( 'wpsc_also_bought' ) == 0 || get_option( 'wpsc_also_bought_limit' ) == 0 ) {
			return $output;
		}

		$also_bought_limit    = get_option( 'wpsc_also_bought_limit' ); // Default 3
		$image_display_width  = get_option( 'wpsc_crosssale_image_width' ); // Default 96
		$image_display_height = get_option( 'wpsc_crosssale_image_height' );  // Default 96
		
		// Get current product ID and its variations
		$also_bought_variations = $wpdb->get_col( "SELECT `" . $wpdb->posts . "`.ID FROM `" . $wpdb->posts . "` WHERE `post_parent`='" . wpsc_cross_sales_product_id() . "' AND `" . $wpdb->posts . "`.`post_status` IN('inherit')" );
		$also_bought_variations[] = wpsc_cross_sales_product_id();
		
		// Get also bought products and variations
		$also_bought_vars = $wpdb->get_results( "SELECT `" . $wpdb->posts . "`.ID, `" . $wpdb->posts . "`.post_parent, `" . $wpdb->posts . "`.post_status, `" . $wpec_cross_sales->get_db_table() . "`.`quantity` FROM `" . $wpec_cross_sales->get_db_table() . "`, `" . $wpdb->posts . "` WHERE `selected_product` IN ('" . implode( "','", $also_bought_variations ) . "') AND (`" . $wpec_cross_sales->get_db_table() . "`.`associated_product` = `" . $wpdb->posts . "`.`id`) AND `" . $wpdb->posts . "`.`post_status` IN('publish','protected','inherit') ORDER BY `" . $wpec_cross_sales->get_db_table() . "`.`quantity` DESC LIMIT $also_bought_limit" );
		$also_bought_products = array();
		foreach ( $also_bought_vars as $also_bought_var ) {
			if ( $also_bought_var->post_parent > 0 ) {
				if ( ! isset( $also_bought_products[$also_bought_var->post_parent] ) )
					$also_bought_products[$also_bought_var->post_parent] = 0;
				$also_bought_products[$also_bought_var->post_parent] += $also_bought_var->quantity;
			} else {
				if ( ! isset( $also_bought_products[$also_bought_var->ID] ) )
					$also_bought_products[$also_bought_var->ID] = 0;
				$also_bought_products[$also_bought_var->ID] += $also_bought_var->quantity;
			}
		}
		
		// Get also bought products and variation product IDs
		$also_bought_products = array_keys( $also_bought_products );
		//$also_bought = $wpdb->get_results( "SELECT `" . $wpdb->posts . "`.* FROM `" . $wpdb->posts . "` WHERE `ID` IN ('" . implode( "','", $also_bought_products ) . "') AND `" . $wpdb->posts . "`.`post_status` IN('publish','protected') ORDER BY `menu_order` ASC LIMIT $also_bought_limit", ARRAY_A );
		
		// Get also bought products
		$wpsc_cross_sales_query = new WP_Query( array(
			'post_type'      => 'wpsc-product',
			'posts_per_page' => -1,
			'post__in'       => $also_bought_products
		) );
		
		if ( $wpsc_cross_sales_query->have_posts() ) {
			$output .= '<h2 class="prodtitles wpsc_also_bought">' . __( 'People who bought this item also bought', 'wpsc-cross-sales' ) . '</h2>';
			$output .= '<div class="wpsc_also_bought">';
			while ( $wpsc_cross_sales_query->have_posts() ) {
				$wpsc_cross_sales_query->the_post();
				$output .= '<div class="wpsc_also_bought_item">';
				if ( get_option( 'show_thumbnails' ) == 1 ) {
					$image_path = wpsc_the_product_thumbnail( $image_display_width, $image_display_height, get_the_ID() );
					if ( $image_path ) {
						$output .= '<a href="' . esc_attr( get_permalink() ) . '" class="preview_link" rel="' . esc_attr( sanitize_html_class( get_the_title() ) ) . '">';
						$output .= '<img src="' . esc_attr( $image_path ) . '" id="product_image_' . get_the_ID() . '" class="product_image" />';
						$output .= '</a>';
					} else {
						$width_and_height = '';
						if ( get_option( 'product_image_width' ) != '' ) {
							$width_and_height = 'width="' . $image_display_height . '" height="' . $image_display_height . '" ';
						}
						$output .= '<img src="' . trailingslashit( WPSC_CORE_THEME_URL ) . 'wpsc-images/noimage.png" title="' . esc_attr( get_the_title() ) . '" alt="' . esc_attr( get_the_title() ) . '" id="product_image_' . get_the_ID() . '" class="product_image" ' . $width_and_height . '/>';
					}
				}
				
				$output .= '<a class="wpsc_product_name" href="' . esc_attr( get_permalink() ) . '">' . get_the_title() . '</a>';
				
				if ( ! wpsc_product_is_donation( get_the_ID() ) ) {
					$price = get_product_meta( get_the_ID(), 'price', true );
					$special_price = get_product_meta( get_the_ID(), 'special_price', true );
					if ( ! empty( $special_price ) ) {
						$output .= '<span style="text-decoration: line-through;">' . wpsc_currency_display( $price ) . '</span>';
						$output .= wpsc_currency_display( $special_price );
					} else {
						$output .= wpsc_currency_display( $price );
					}
				}
				
				$output .= '</div>';
			} 
			$output .= '</div>';
			$output .= '<br clear="all" />';
			wp_reset_postdata();
		}
		return $output;
	}
	
	/**
	 * Save cross sale information on checkout submit
	 * Originally called 'wpsc_alsobought_submit_checkout' and located in wpsc-includes/ajax.functions.php
	 *
	 * @param array $args Array of 'purchase_log_id' and 'our_user_id'
	 */
	function wpsc_submit_checkout( $args ) {
		global $wpdb, $wpsc_cart, $wpsc_coupons;
		
		// Only do this if wpsc_populate_also_bought_list function does not exist
		// Originally defined in wpsc-includes/misc.functions.php
		if ( ! function_exists( 'wpsc_populate_also_bought_list' ) || wpsc_populate_also_bought_list() === false ) {
			if ( get_option( 'wpsc_also_bought' ) == 1 ) {

				$new_also_bought_data = array();
				foreach ( $wpsc_cart->cart_items as $outer_cart_item ) {
					$new_also_bought_data[$outer_cart_item->product_id] = array();
					foreach ( $wpsc_cart->cart_items as $inner_cart_item ) {
						if ( $outer_cart_item->product_id != $inner_cart_item->product_id ) {
							$new_also_bought_data[$outer_cart_item->product_id][$inner_cart_item->product_id] = $inner_cart_item->quantity;
						} else {
							continue;
						}
					}
				}
			
				$insert_statement_parts = array();
				foreach ( $new_also_bought_data as $new_also_bought_id => $new_also_bought_row ) {
					$new_other_ids = array_keys( $new_also_bought_row );
					$also_bought_data = $wpdb->get_results( $wpdb->prepare( "SELECT `id`, `associated_product`, `quantity` FROM `" . WPSC_TABLE_ALSO_BOUGHT . "` WHERE `selected_product` IN(%d) AND `associated_product` IN('" . implode( "','", $new_other_ids ) . "')", $new_also_bought_id ), ARRAY_A );
					$altered_new_also_bought_row = $new_also_bought_row;
			
					foreach ( (array)$also_bought_data as $also_bought_row ) {
						$quantity = $new_also_bought_row[$also_bought_row['associated_product']] + $also_bought_row['quantity'];
			
						unset( $altered_new_also_bought_row[$also_bought_row['associated_product']] );
						$wpdb->update(
							WPSC_TABLE_ALSO_BOUGHT,
							array(
								'quantity' => $quantity
							),
							array(
								'id' => $also_bought_row['id']
							),
							'%d',
							'%d'
						);
					}
			
					if ( count( $altered_new_also_bought_row ) > 0 ) {
						foreach ( $altered_new_also_bought_row as $associated_product => $quantity ) {
							$insert_statement_parts[] = "(" . absint( esc_sql( $new_also_bought_id ) ) . "," . absint( esc_sql( $associated_product ) ) . "," . absint( esc_sql( $quantity ) ) . ")";
						}
					}
				}
			
				if ( count( $insert_statement_parts ) > 0 ) {
					$insert_statement = "INSERT INTO `" . WPSC_TABLE_ALSO_BOUGHT . "` (`selected_product`, `associated_product`, `quantity`) VALUES " . implode( ",\n ", $insert_statement_parts );
					$wpdb->query( $insert_statement );
				}
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
		
		// Admin
		if ( is_admin() ) {
			require_once( dirname( $this->plugin_file ) . '/includes/admin.php' );
			$this->admin = new WPEC_CrossSales_Admin();
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
		
		add_option( 'wpsc_also_bought_limit', $this->default_also_bought_limit );
		add_option( 'wpsc_crosssale_image_width', $this->default_crosssale_image_size );
		add_option( 'wpsc_crosssale_image_height', $this->default_crosssale_image_size );
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
		if ( $this->wpec_is_installed() && version_compare( WPSC_VERSION, $this->required_wpsc_version, '>=' ) && ( ! function_exists( 'wpsc_populate_also_bought_list' ) || ! wpsc_populate_also_bought_list() ) ) {
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

?>