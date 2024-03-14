<?php
/*
Plugin Name: Contact Form 7 4Dem.it Extension
Version: 1.1.2
Plugin URI: 
Description: Integrate Contact Form 7 with 4Dem.it email marketing platform. Automatically add form submissions to predetermined lists in 4Dem console, using its latest API.
Author: 4marketing.it
Author URI: http://www.4marketing.it
Text Domain: adv_dem_cf7
Domain Path: /languages
*/
define( 'ADV_DEM_CF7_VERSION', '1.1.1' );
define( 'ADV_DEM_CF7_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ADV_DEM_CF7_PLUGIN_NAME', trim( dirname( ADV_DEM_CF7_PLUGIN_BASENAME ), '/' ) );
define( 'ADV_DEM_CF7_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define( 'ADV_DEM_CF7_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'ADV_DEM_CF7_AGENCY_NAME', '4Dem.it');
define( 'ADV_DEM_CF7_API_ENDPOINT', 'https://api.4dem.it');
define( 'ADV_DEM_CF7_MAILCHEF', 'http://mailchef.4dem.it');
define('ADV_DEM_CF7_TEXTDOMAIN', 'adv_dem_cf7') ;
require_once( ADV_DEM_CF7_PLUGIN_DIR . '/lib/adv-dem-cf7.php' );


function adv_dem_cf7_meta_links( $links, $file ) {
    if ( $file === 'contact-form-7-adv-dem-extension/cf7-adv-dem-ext.php' ) {
        $links[] = '<a href="'.ADV_DEM_CF7_MAILCHEF.'" target="_blank" title="4Dem.it Console">4Dem Newsletter Console</a>';
    }
    return $links;
}
add_filter( 'plugin_row_meta', 'adv_dem_cf7_meta_links', 10, 2 );


function adv_dem__cf7_settings_link( $links ) {
    $url = get_admin_url() . 'admin.php?page=wpcf7&post='.adv_dem_cf7_get_latest_item().'&active-tab=4' ;
    $settings_link = '<a href="' . $url . '">' . esc_html__('Settings', ADV_DEM_CF7_TEXTDOMAIN) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}


function adv_dem_cf7_after_setup_theme() {
     add_filter('plugin_action_links_' . ADV_DEM_CF7_PLUGIN_BASENAME, 'adv_dem__cf7_settings_link');
}
add_action ('after_setup_theme', 'adv_dem_cf7_after_setup_theme');


