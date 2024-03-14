<?php

/**
 * Fired during plugin deactivation
 *
 * @link       www.4marketing.it
 * @since      1.0.0
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 * @author     4marketing.it <sviluppo@4marketing.it>
 */
class Adv_dem_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
	// Drop table
		 global $wpdb;

		 $batchTableName = $wpdb->prefix . "adv_dem_batches";
		 $wpdb->query( $wpdb->prepare("DROP TABLE " . $batchTableName) );
		 $productTableName = $wpdb->prefix . "adv_dem_product_attributes";
		 $wpdb->query( $wpdb->prepare("DROP TABLE " . $productTableName) );
		 $cartTableName = $wpdb->prefix ."adv_dem_carts";
		 $wpdb->query( $wpdb->prepare("DROP TABLE ". $cartTableName) );
	}

	public static function uninstall() {
		// if uninstall.php is not called by WordPress, die
		if (!defined('WP_UNINSTALL_PLUGIN')) {
			die;
		}

		delete_option( 'adv_dem' );
		delete_option( 'adv_dem_secret_key' );
		delete_option( 'adv_dem' . 'sincronizza_utenti' );
	}

}
