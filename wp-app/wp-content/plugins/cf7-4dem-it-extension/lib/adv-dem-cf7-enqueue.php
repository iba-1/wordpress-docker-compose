<?php
function wpcf7_adv_dem_admin_enqueue_scripts() {
	global $plugin_page;
	if ( ! isset( $plugin_page ) || 'wpcf7' != $plugin_page ) return;
	wp_enqueue_style( 'wpcf7-adv-dem-admin', ADV_DEM_CF7_PLUGIN_URL . '/assets/css/adv-dem-cf7-style.css', array(), ADV_DEM_CF7_VERSION, 'all' );
	wp_enqueue_script( 'wpcf7-adv-dem-admin', ADV_DEM_CF7_PLUGIN_URL . '/assets/js/adv-dem-cf7-scripts.js', array( 'jquery', 'wpcf7-admin' ), ADV_DEM_CF7_VERSION, true );
}
add_action( 'admin_print_scripts', 'wpcf7_adv_dem_admin_enqueue_scripts' );

?>