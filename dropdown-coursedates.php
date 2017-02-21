<?php
/*
Plugin Name: Do It Simply Select Courses by Dropdown Date
Description: WooCommerce Bookings add-on
Version: 0.1.0
Author: DO IT SIMPLY LTD
Author URI: http://doitsimply.co.uk/
GitHub URI: baperrou/WooBooking-Dropdown-coursedates
*/

defined( 'ABSPATH' ) or exit;
// decide if the other plugins must be activated to use.
//revisit

define ( 'WCCF_NAME', 'Woocommerce Plugin Example' ) ;
define ( 'WCCF_REQUIRED_PHP_VERSION', '5.4' ) ;                          // because of get_called_class()
define ( 'WCCF_REQUIRED_WP_VERSION', '4.6' ) ;                          // because of esc_textarea()
define ( 'WCCF_REQUIRED_WC_VERSION', '2.6' );                           // because of Shipping Class system

// ADD ACTIONS AND ADMIN MENUS

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function dis_ddd_requirements_met () {
    
    if ( ! is_plugin_active ( 'woocommerce-bookings/woocommmerce-bookings.php' ) ) {
        return false ;
    }
    

	/* if I want to add version control later    $woocommer_data = get_plugin_data(WP_PLUGIN_DIR .'/woocommerce-bookings/woocommmerce-bookings.php', false, false);

    if (version_compare ($woocommer_data['Version'] , WCCF_REQUIRED_WC_VERSION, '<')){
        return false;
    }
    */

    return true ;
}





//now flush so the tage is activated.
function dis_wc_ddd_flush_rewrites() {
	dis_wc_ddd_insert_term();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'dis_wc_ddd_flush_rewrites' );

// add scripts NNED TO CHECK AND VERIFY
add_action('wp_ajax_wswp_refresh_dates','dis_ddd_refresh_dates');
add_action('wp_ajax_nopriv_wswp_refresh_dates','dis_ddd_refresh_dates');

function dis_ddd_refresh_dates() {
    check_ajax_referer('woo-bookings-dropdown-refreshing-dates','security');
    $product = wc_get_product($_REQUEST['product_id']);
    $rules = $product->get_availability_rules($_REQUEST['resource_id']);
    $max = $product->get_max_date();
    $now = strtotime( 'midnight', current_time( 'timestamp' ) );
    $max_date = strtotime( "+{$max['value']} {$max['unit']}", $now );
    $dates = wswp_build_options($rules,$max_date);
    if (!empty($dates)) {
        $response = array(
            'success' => true,
            'dates' => $dates
        );
        wp_send_json($response);
    }
}

add_action('wp_enqueue_scripts','dis_wc_ddd_enqueue_script');

function dis_wc_ddd_enqueue_script() {
    wp_enqueue_script('dis_wc_ddd-dropdown',plugins_url('js/dis_wc_ddd-dropdown.js',__FILE__),array('jquery'));
    wp_localize_script('dis_wc_ddd-dropdown','WooBookingsDropdown',array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'secure' => wp_create_nonce('woo-bookings-dropdown-refreshing-dates')
    ));
}
// Create a product category to allow dropdown course date selection

function dis_wc_ddd_insert_term()
{
    wp_insert_term(
      'Select Course by Dropdown Date',
      'product_cat', // the taxonomy
      array(
        'description'=> 'Selecting a course by dropdown date instead of calendar',
        'slug' => 'dis_ddd_bookings',
      )
    );
    //think about how to remove the tag if plugin removed.
}
add_action('init', 'dis_wc_ddd_insert_term');

//dual credit goes to Webby Scots for adding some simplicity to my original build
// Webby Scots
// http://webbyscots.com/


//only call these action/filters if the category is set to dropdown and not in admin
if(!is_admin()){
add_action( 'wp', 'dis_ddd_check_product' );
	}
function dis_ddd_check_product() {
	global $post;
	
	$dis_ddd_categories = get_the_terms($post->ID, 'product_cat' );
	
	if($dis_ddd_categories) {
		//echo '<pre>'; print_r($dis_ddd_categories); echo '</pre>';
		foreach($dis_ddd_categories as $category ) {
			
			if($category->slug == 'dis_ddd_bookings') {
				add_filter('booking_form_fields','dis_ddd_booking_form_fields');
				add_action('wp_footer','dis_ddd_css');
				add_filter( 'body_class', 'dis_ddd_customclass' );
			}
		}
	}
	wp_reset_postdata();

}
$wswp_dates_built = false;
function dis_ddd_booking_form_fields($fields) {
	//echo '<pre>'; print_r($fields); echo '</pre>';
    global $wswp_dates_built;
    $i = 0;
    $selected_resource = 0;
    $reset_options = false;
    

    foreach($fields as $field) {
        $new_fields[$i] = $field;
                if ($field['type'] == "select") {
            $selected_resource = reset(array_keys($field['options']));
            if ($reset_options !== false)
                $new_fields[$reset_options]['options'] = wswp_build_options($field['availability_rules'][$selected_resource]);
        }
        if ($field['type'] == "date-picker" && $wswp_dates_built === false)
        {
            $s = $i;
            $new_fields[$s]['class'] = array('picker-hidden');
            $i++;
            $new_fields[$i] = $field;
            $new_fields[$i]['type'] = "select";
            if ($selected_resource == 0)
                $reset_options = $i;
            $max = $field['max_date'];
            $now = strtotime( 'midnight', current_time( 'timestamp' ) );
            $max_date = strtotime( "+{$max['value']} {$max['unit']}", $now );
            $new_fields[$i]['options'] = dis_ddd_build_options($field['availability_rules'][$selected_resource],$max_date);
            $new_fields[$i]['class'] = array('picker-chooser');
        }
        $i++;
    }
    return $new_fields;
}

function dis_ddd_build_options($rules,$building = false) {
	 
	//$booking=new WC_Product_Booking(get_the_ID());
	//echo '<pre>'; print_r($rules); echo '</pre>';
    global $wswp_dates_built;
    $dates = array();
    foreach($rules as $dateset) {
	    //be aware that this associative array changes depending on version of WooBookings
        if ($dateset['type'] == "custom") {
            $year = array_keys($dateset['range']);
            $year = reset($year);
            $month = array_keys($dateset['range'][$year]);
            $month = reset($month);
            $day = array_keys($dateset['range'][$year][$month]);
             $day = reset($day);
           
            // it seams the day key is empty if bookable is set to NO so check here
            if($dateset['range'][$year][$month][$day]) {
           
            $dtime = strtotime($year."-".$month."-".$day);
            $dates[$dtime] = date("d M, Y",$dtime);
            }
        }

    }
    ksort($dates);
    foreach($dates as $key => $date) {
        $dates[date("Y-m-d",$key)] = $date;
        unset($dates[$key]);
    }
    $wswp_dates_built = true;
    return $dates;
}

// add class to page to instigate dropdown picker


function dis_ddd_customclass( $classes ) {
	//check page category
	global $post;
	
	$dis_ddd_categories = get_the_terms($post->ID, 'product_cat' );
	foreach( $dis_ddd_categories as $category ) {
		if($category->slug == 'dis_ddd_bookings') {
        	$classes[] = 'dis-dropdown-class';
    	}
    }
    return $classes;
    wp_reset_postdata();

}


// add simple styles to hide picker


function dis_ddd_css() {
    //adding in footer as not enough to justify new stylesheet
    ?>
    	<style type="text/css">
        .picker-hidden .picker,.picker-hidden legend, .wc-bookings-date-picker-date-fields {
            display:none;
        }
        </style>
    <?php
}
