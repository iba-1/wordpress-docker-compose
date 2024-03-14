<?php

/**
 * Fired during plugin activation
 *
 * @link       www.4marketing.it
 * @since      1.0.0
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 * @author     4marketing.it <sviluppo@4marketing.it>
 */
class Adv_dem_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
	// Create custom table for Batch Operations
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$batchTableName = $wpdb->prefix . "adv_dem_batches";
		$wpdb->query( $wpdb->prepare("CREATE TABLE IF NOT EXISTS ".$batchTableName." (
			`id` INT NOT NULL AUTO_INCREMENT,
			`batch_Type` VARCHAR(255) NOT NULL,
			`batch_Id` VARCHAR(255) NOT NULL,
			`batch_Status` VARCHAR(255) NOT NULL,
			`batch_Operations` INT NOT NULL,
			`batch_Result` TEXT NULL,
			`batch_Start_Time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			`batch_Finish_Time` TIMESTAMP NULL,
			UNIQUE KEY id (id)
		)$charset_collate;") );

		$productTableName = $wpdb->prefix . "adv_dem_product_attributes";
		$wpdb->query( $wpdb->prepare("CREATE TABLE IF NOT EXISTS ".$productTableName." (
			`id` INT NOT NULL AUTO_INCREMENT,
			`property_Name` VARCHAR(255) NOT NULL,
			`property_Wc_Id` INT NOT NULL,
			`property_Adv_Id` INT NOT NULL,
			`property_Last_Update` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY id (id)
		)$charset_collate;") );

		$cartTableName = $wpdb->prefix ."adv_dem_carts";
		$wpdb->query( $wpdb->prepare("CREATE TABLE IF NOT EXISTS ".$cartTableName." (
			`id` INT NOT NULL AUTO_INCREMENT,
			`cart_Id` VARCHAR(255) NOT NULL,
			`cart_UserId` INT NOT NULL,
			`cart_Status` VARCHAR(50) NOT NULL,
			`cart_Creation_Time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			`cart_Last_Update` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY id (id)
		)$charset_collate;") );
	}

}
