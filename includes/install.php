<?php

/**
 * Install
 * This class stores the schema for the database table
 * and is used to update the table when the plugin is activated.
 */

class WPEC_CrossSales_Install {
	
	/**
	 * DB Schema class constructor.
	 * Doesn't do anything by default.
	 */
	function WPEC_CrossSales_Install() {
	}
	
	/**
	 * Upgrade the DB Schema.
	 * Updates the DB table schema and stores DB update version.
	 * All lines withing the CREATE TABLE function must be prexised
	 * by 2 spaces. The PRIMARY KEY must also be followed by 2 spaces.
	 */
	function upgrade_db_schema( $db_version ) {
		global $wpdb, $table_prefix;
		
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";
		
		// Payments Table
		$table_also_bought = "CREATE TABLE " . $wpdb->prefix . "wpsc_also_bought (
		  id bigint(20) unsigned NOT NULL auto_increment,
		  selected_product bigint(20) unsigned NOT NULL DEFAULT 0,
		  associated_product bigint(20) unsigned NOT NULL DEFAULT 0,
		  quantity int(10) unsigned NOT NULL DEFAULT 0,
		  PRIMARY KEY  (id)
		) $charset_collate;";
		
		// Update Schema
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $table_also_bought );
		
		// Update DB version info
		update_option( 'wpsc_crosssales_db_version', $db_version );
	}
	
	/**
	 * Set Default Options.
	 */
	function set_default_options( $override = false ) {
		
		// Display Cross Sells
		if ( $override ) {
			update_option( 'wpsc_also_bought', 0 );
		}
		
		// Also Bought Limit
		$wpsc_also_bought_limit = get_option( 'wpsc_also_bought_limit' );
		if ( $override || ! is_numeric( $wpsc_also_bought_limit ) ) {
			update_option( 'wpsc_also_bought_limit', 3 );
		}
		
		// Image Sizes
		$wpsc_crosssale_image_width = get_option( 'wpsc_crosssale_image_width' );
		if ( $override || ! is_numeric( $wpsc_crosssale_image_width ) ) {
			$product_image_width = get_option( 'product_image_width' );
			if ( ! is_numeric( $product_image_width ) ) {
				$product_image_width = get_option( 'thumbnail_size_w' );
			}
			update_option( 'wpsc_crosssale_image_width', $product_image_width );
		}
		$wpsc_crosssale_image_height = get_option( 'wpsc_crosssale_image_height' );
		if ( $override || ! is_numeric( $wpsc_crosssale_image_height ) ) {
			$product_image_height = get_option( 'product_image_height' );
			if ( ! is_numeric( $product_image_height ) ) {
				$product_image_height = get_option( 'thumbnail_size_h' );
			}
			update_option( 'wpsc_crosssale_image_height', $product_image_height );
		}
	}
	
}

?>