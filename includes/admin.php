<?php

/**
 * Admin
 * This class contains admin functionality.
 */

class WPEC_CrossSales_Admin {
	
	/**
	 * Admin class constructor.
	 */
	function WPEC_CrossSales_Admin() {
		add_action( 'admin_init', array ( $this, 'admin_init' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}
	
	/**
	 * Admin Init
	 */
	function admin_init() {
		add_meta_box( 'wpsc_alsobought', __( 'Cross Sales (Also Bought)', 'wpsc-cross-sales' ), array( $this, 'alsobought_meta_box' ), 'wpsc', 'advanced' );
		
		// Submit Options
		if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'submit_options' ) )
			$this->submit_alsobought_options();
	}
	
	/**
	 * Settings Meta Boxes
	 * Originally called 'wpsc_alsobought_meta_box' and located in wpsc-admin/includes/settings-pages/marketing.php
	 */
	function alsobought_meta_box() {
		
		// Options
		$wpsc_also_bought            = get_option( 'wpsc_also_bought' );
		$wpsc_also_bought_limit      = get_option( 'wpsc_also_bought_limit' );
		$wpsc_crosssale_image_width  = get_option( 'wpsc_crosssale_image_width' );
		$wpsc_crosssale_image_height = get_option( 'wpsc_crosssale_image_height' );
		
		$wpsc_also_bought1 = '';
	
		if ( '1' == $wpsc_also_bought )
			$wpsc_also_bought1 = "checked ='checked'";
			
		echo '<form method="post" action="" id="wpsc_alsobought_options" name="wpsc_alsobought_options" class="wpsc_form_track">
				<input type="hidden" name="change-settings" value="true" />
				<p>
					<span class="input_label">' . __( 'Display Cross Sales', 'wpsc-cross-sales' ) . '</span>
					<input ' . $wpsc_also_bought1 . ' type="checkbox" name="wpsc_also_bought" />
					<span class="description">' . __( 'Adds the \'Users who bought this also bought\' item to the single products page.', 'wpsc-cross-sales' ) . '</span>
				</p>
				<p>
					<span class="input_label">' . __( 'Cross Sales Limit', 'wpsc-cross-sales' ) . '</span>
					<input type="text" name="wpsc_also_bought_limit" value="' . $wpsc_also_bought_limit . '" size="5" />
					<span class="description">' . __( 'Maximumm number of cross sale products to display.', 'wpsc-cross-sales' ) . '</span>
				</p>
				<p>
					<span class="input_label">' . __( 'Image Width', 'wpsc-cross-sales' ) . '</span>
					<input type="text" name="wpsc_crosssale_image_width" value="' . $wpsc_crosssale_image_width . '" size="5" />
				</p>
				<p>
					<span class="input_label">' . __( 'Image Height', 'wpsc-cross-sales' ) . '</span>
					<input type="text" name="wpsc_crosssale_image_height" value="' . $wpsc_crosssale_image_height . '" size="5" />
				</p>
				<div class="submit">
					<input type="hidden" name="wpsc_admin_action" value="submit_options" />
					' . wp_nonce_field( 'update-options', 'wpsc-update-options' ) . '
					<input type="submit" class="button-primary" value="' . __( 'Update', 'wpsc-cross-sales' ) . ' »" name="form_submit" />
				</div>
			</form>';
	}
	
	/**
	 * Submit Also Bought Options
	 * Originally called 'wpsc_submit_alsobought_options' and located in wpsc-admin/ajax-and-init.php
	 */
	function submit_alsobought_options() {
		
		// Do or Die?
		check_admin_referer( 'update-options', 'wpsc-update-options' );
		
		// Save Settings
		if ( isset( $_POST['change-settings'] ) ) {
			if ( isset( $_POST['wpsc_also_bought'] ) && $_POST['wpsc_also_bought'] == 'on' ) {
				update_option( 'wpsc_also_bought', 1 );
			} else {
				update_option( 'wpsc_also_bought', 0 );
			}
			if ( isset( $_POST['wpsc_also_bought_limit'] ) && is_numeric( $_POST['wpsc_also_bought_limit'] ) ) {
				update_option( 'wpsc_also_bought_limit', absint( $_POST['wpsc_also_bought_limit'] ) );
			}
			if ( isset( $_POST['wpsc_crosssale_image_width'] ) && is_numeric( $_POST['wpsc_crosssale_image_width'] ) ) {
				update_option( 'wpsc_crosssale_image_width', absint( $_POST['wpsc_crosssale_image_width'] ) );
			}
			if ( isset( $_POST['wpsc_crosssale_image_height'] ) && is_numeric( $_POST['wpsc_crosssale_image_height'] ) ) {
				update_option( 'wpsc_crosssale_image_height', absint( $_POST['wpsc_crosssale_image_height'] ) );
			}
		}
	}
	
	/**
	 * Show admin notice if WP e-Commerce is not installed or needs upgrading
	 */
	function admin_notices() {
		global $wpec_cross_sales, $pagenow;
		
		if ( current_user_can( 'install_plugins' ) && $pagenow == 'plugins.php' ) {
			$plugin  = '<strong>' . __( 'Cross Sales', 'wpsc-cross-sales' ) . '</strong>';
			$wpec_version = __( 'WP e-Commerce', 'wpsc-cross-sales' ) . ' ' . $wpec_cross_sales->required_wpsc_version . '+';
			$wpec_link = ' <a href="http://wordpress.org/extend/plugins/wp-e-commerce/" target="_blank">' . __( 'WP e-Commerce', 'wpsc-cross-sales' ) . '</a>';
			
			$deactivate_plugin_file = plugin_basename( $wpec_cross_sales->plugin_file );
			$deactivate_url = wp_nonce_url( admin_url( 'plugins.php?action=deactivate&amp;s&amp;plugin_status=all&amp;plugin=' . $deactivate_plugin_file ), 'deactivate-plugin_' . $deactivate_plugin_file );
			$deactivate = '<a href="' . $deactivate_url . '">' . __( 'deactivate' ) . '</a>';
			
			if ( ! $wpec_cross_sales->wpec_is_installed() ) {
				$msg = __( "The %s plugin requires %s. Please %s the plugin or install %s." , 'wpsc-cross-sales' );
				echo '<div id="message" class="updated"><p>' . sprintf( $msg, $plugin, $wpec_version, $deactivate, $wpec_link ) . '</p></div>';
			} elseif ( !$wpec_cross_sales->wpec_is_compatible() ) {
				$msg = __( "The %s plugin is only compatible with %s. Please %s the plugin or upgrade %s.", 'wpsc-cross-sales' );
				echo '<div id="message" class="updated"><p>' . sprintf( $msg, $plugin, $wpec_version, $deactivate, $wpec_link ) . '</p></div>';
			}
		}
	}
	
}

?>