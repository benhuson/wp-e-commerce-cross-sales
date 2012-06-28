<?php

global $wpdb, $wpec_cross_sales;

$also_bought_limit    = get_option( 'wpsc_also_bought_limit' );
$image_display_width  = get_option( 'wpsc_crosssale_image_width' );
$image_display_height = get_option( 'wpsc_crosssale_image_height' );

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
$also_bought = $wpdb->get_results( "SELECT `" . $wpdb->posts . "`.* FROM `" . $wpdb->posts . "` WHERE `ID` IN ('" . implode( "','", $also_bought_products ) . "') AND `" . $wpdb->posts . "`.`post_status` IN('publish','protected') ORDER BY `menu_order` ASC LIMIT $also_bought_limit", ARRAY_A );

// Get also bought products
$wpsc_cross_sales_query = new WP_Query( array(
	'post_type'      => 'wpsc-product',
	'posts_per_page' => -1,
	'post__in'       => $also_bought_products
) );

if ( $wpsc_cross_sales_query->have_posts() ) {
	echo '<h2 class="prodtitles wpsc_also_bought">' . __( 'People who bought this item also bought', 'wpsc-cross-sales' ) . '</h2>';
	echo '<div class="wpsc_also_bought">';
	while ( $wpsc_cross_sales_query->have_posts() ) {
		$wpsc_cross_sales_query->the_post();
		echo '<div class="wpsc_also_bought_item">';
		if ( get_option( 'show_thumbnails' ) == 1 ) {
			if ( wpsc_the_product_thumbnail( null, null, get_the_ID() ) ) {
				$image_path = wpsc_the_product_thumbnail( $image_display_width, $image_display_height, get_the_ID() );
				echo '<a href="' . get_permalink() . '" class="preview_link"  rel="' . str_replace( ' ', '_', get_the_title() ) . '">';
				echo '<img src="' . $image_path . '" id="product_image_' . get_the_ID() . '" class="product_image" />';
				echo '</a>';
			} else {
				$width_and_height = '';
				if ( get_option( 'product_image_width' ) != '' ) {
					$width_and_height = 'width="' . $image_display_height . '" height="' . $image_display_height . '" ';
				}
				echo '<img src="' . WPSC_CORE_THEME_URL . 'wpsc-images/noimage.png" title="' . get_the_title() . '" alt="' . htmlentities( stripslashes( get_the_title() ), ENT_QUOTES, 'UTF-8' ) . '" id="product_image_' . get_the_ID() . '" class="product_image" ' . $width_and_height . '/>';
			}
		}
		echo '<a class="wpsc_product_name" href="' . get_permalink() . '">' . get_the_title() . '</a>';
		$price = get_product_meta( get_the_ID(), 'price', true );
		$special_price = get_product_meta( get_the_ID(), 'special_price', true );
		if ( ! empty( $special_price ) ) {
			echo '<span style="text-decoration: line-through;">' . wpsc_currency_display( $price ) . '</span>';
			echo wpsc_currency_display( $special_price );
		} else {
			echo wpsc_currency_display( $price );
		}
		echo '</div>';
	} 
	echo '</div>';
	echo '<br clear="all" />';
	wp_reset_postdata();
}

?>