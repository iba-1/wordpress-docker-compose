<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.4marketing.it
 * @since             1.0.0
 * @package           Adv_dem
 *
 * @wordpress-plugin
 * Plugin Name:       Email Marketing 4Dem
 * Plugin URI:        https://www.4marketing.it/
 * Description:       Questo plugin permette di integrare Wordpress/Woocommerce con la tua console di invio newsletter. Qualora disponibili vengono abilitate le funzioni estese per la sincronizzazione degli utenti Woocommerce e l'integrazione dei servizi di E-commerce Plus.
 * Version:           2.0.4
 * Author:            4marketing.it
 * Author URI:        www.4marketing.it
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       adv_dem
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-adv_dem-activator.php
 */
function activate_adv_dem() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-adv_dem-activator.php';
	Adv_dem_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-adv_dem-deactivator.php
 */
function deactivate_adv_dem() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-adv_dem-deactivator.php';
	Adv_dem_Deactivator::deactivate();
}

function uninstall_adv_dem() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-adv_dem-deactivator.php';
	Adv_dem_Deactivator::uninstall();
}

register_activation_hook( __FILE__, 'activate_adv_dem' );
register_deactivation_hook( __FILE__, 'deactivate_adv_dem' );
register_uninstall_hook(__FILE__, 'uninstall_adv_dem' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-adv_dem.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_adv_dem() {
	$json_string = file_get_contents(plugin_dir_path( __FILE__ ) ."config.json");
	$json_array = json_decode($json_string, true);
	DEFINE ("ADV_DEM_COMPANY", $json_array["company"]);
	DEFINE ("ADV_DEM_COMPANY_LOGO", plugin_dir_url(  __FILE__  ) . '/admin/images/logo_plugin.png');
	DEFINE ("ADV_DEM_COMPANY_ICON", plugin_dir_url(  __FILE__  ) . '/admin/images/icon_plugin.png');

	foreach ( glob( dirname( __FILE__ ) . '/includes/php-jwt/*.php' ) as $filename ) {
		require_once $filename;
	}
	$plugin = new Adv_dem();
	$plugin->run();

}
run_adv_dem();
