<?php

global $wpdb, $wpec_cross_sales;

$also_bought_limit    = get_option( 'wpsc_also_bought_limit' );
$image_display_width  = get_option( 'wpsc_crosssale_image_width' );
$image_display_height = get_option( 'wpsc_crosssale_image_height' );

$output = '';

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

// Get also bought products and variation products
$also_bought_products = array_keys( $also_bought_products );
$also_bought = $wpdb->get_results( "SELECT `" . $wpdb->posts . "`.* FROM `" . $wpdb->posts . "` WHERE `ID` IN ('" . implode( "','", $also_bought_products ) . "') AND `" . $wpdb->posts . "`.`post_status` IN('publish','protected') ORDER BY `menu_order` ASC LIMIT $also_bought_limit", ARRAY_A );


if ( count( $also_bought ) > 0 ) {
	$output .= '<h2 class="prodtitles wpsc_also_bought">' . __( 'People who bought this item also bought', 'wpsc-cross-sales' ) . '</h2>';
	$output .= '<div class="wpsc_also_bought">';
	foreach ( (array)$also_bought as $also_bought_data ) {
		$output .= '<div class="wpsc_also_bought_item">';
		if ( get_option( 'show_thumbnails' ) == 1 ) {
			if ( wpsc_the_product_thumbnail( null, null, $also_bought_data['ID'] ) ) {
				$image_path = wpsc_the_product_thumbnail( $image_display_width, $image_display_height, $also_bought_data['ID'] );
				$output .= '<a href="' . get_permalink( $also_bought_data['ID'] ) . '" class="preview_link"  rel="' . str_replace( " ", "_", get_the_title( $also_bought_data['ID'] ) ) . '">';
				$output .= '<img src="' . $image_path . '" id="product_image_' . $also_bought_data['ID'] . '" class="product_image" />';
				$output .= '</a>';
			} else {
				$width_and_height = '';
				if ( get_option( 'product_image_width' ) != '' ) {
					$width_and_height = 'width="' . $image_display_height . '" height="' . $image_display_height . '" ';
				}
				$output .= '<img src="' . WPSC_CORE_THEME_URL . 'wpsc-images/noimage.png" title="' . get_the_title( $also_bought_data['ID'] ) . '" alt="' . htmlentities( stripslashes( get_the_title($also_bought_data['ID']) ), ENT_QUOTES, 'UTF-8' ) . '" id="product_image_' . $also_bought_data['ID'] . '" class="product_image" ' . $width_and_height . '/>';
			}
		}
		$output .= '<a class="wpsc_product_name" href="' . get_permalink( $also_bought_data['ID'] ) . '">' . get_the_title( $also_bought_data['ID'] ) . '</a>';
		$price = get_product_meta( $also_bought_data['ID'], 'price', true );
		$special_price = get_product_meta( $also_bought_data['ID'], 'special_price', true );
		if ( !empty( $special_price ) ) {
			$output .= '<span style="text-decoration: line-through;">' . wpsc_currency_display( $price ) . '</span>';
			$output .= wpsc_currency_display( $special_price );
		} else {
			$output .= wpsc_currency_display( $price );
		}
		$output .= '</div>';
	}
	$output .= '</div>';
	$output .= '<br clear="all" />';
}
echo $output;

?>