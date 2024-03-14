<?php
function adv_dem_cf7_error() {
	if( !file_exists(WP_PLUGIN_DIR.'/contact-form-7/wp-contact-form-7.php') ) {
		$adv_dem_error_out = '<div id="message" class="error is-dismissible"><p>';
		$adv_dem_error_out .= esc_html__('The Contact Form 7 plugin must be installed for the <b>4Dem.it Extension</b> to work.' , ADV_DEM_CF7_TEXTDOMAIN) . ' <b><a href="'.admin_url('plugin-install.php?tab=plugin-information&plugin=contact-form-7&from=plugins&TB_iframe=true&width=600&height=550').'" class="thickbox" title="Contact Form 7">' . esc_html__('Install Contact Form 7 Now.', ADV_DEM_CF7_TEXTDOMAIN) . '</a></b>';
		$adv_dem_error_out .= '</p></div>';
		echo $adv_dem_error_out;
	}
	else if ( !class_exists( 'WPCF7') ) {
		$adv_dem_error_out = '<div id="message" class="error is-dismissible"><p>';
		$adv_dem_error_out .= esc_html__('The Contact Form 7 is installed, but <b>you must activate Contact Form 7</b> below for the <b>4Dem.it Extension</b> to work.', ADV_DEM_CF7_TEXTDOMAIN);
		$adv_dem_error_out .= '</p></div>';
		echo $adv_dem_error_out;
	}
	
}
add_action('admin_notices', 'adv_dem_cf7_error');

function adv_dem_cf7_act_redirect( $plugin ) {
	if( $plugin == ADV_DEM_CF7_PLUGIN_BASENAME ) {
		exit( wp_redirect( admin_url( 'admin.php?page=wpcf7&post='.adv_dem_cf7_get_latest_item().'&active-tab=4' ) ) );
	}
	delete_option('adv_dem_cf7_show_notice', 0);
	update_site_option('adv_dem_cf7_show_notice', 1);
}
add_action( 'activated_plugin', 'adv_dem_cf7_act_redirect' );