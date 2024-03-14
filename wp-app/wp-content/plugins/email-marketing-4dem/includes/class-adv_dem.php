<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.4marketing.it
 * @since      1.0.0
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 * @author     4marketing.it <sviluppo@4marketing.it>
 */
class Adv_dem {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Adv_dem_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'adv_dem';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Adv_dem_Loader. Orchestrates the hooks of the plugin.
	 * - Adv_dem_i18n. Defines internationalization functionality.
	 * - Adv_dem_Admin. Defines all hooks for the admin area.
	 * - Adv_dem_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-adv_dem-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-adv_dem-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-adv_dem-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-adv_dem-public.php';

		/**
		 * The class responsible for API 2.0 usage.
		 *
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-adv_dem-interfaceapi.php';

		/**
		 * The class responsible for widget definition
		 *
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-adv_dem-widget.php';

		/**
		 * The class responsible for user syncro management
		 *
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-adv_dem_syncroTools.php';

		/**
		 * The class responsible for ecommerce plus functions
		 *
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-adv_dem_ecommerceplus.php';

		// TEST TABLE
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-adv_batch_table.php';


		$this->loader = new Adv_dem_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Adv_dem_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Adv_dem_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		// Leggo le impostazioni di sincronizzazione
		$optionsSincronizzaUtenti = get_option($this->plugin_name . "sincronizza_utenti");
		$optionsGlobal = get_option($this->plugin_name);

		$woocommerceAttivo = (in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) ? true : false;
		$ecommercePlusAttivo = isset($optionsGlobal["ecommercePlusStoreId"]) ?  boolval($optionsGlobal["ecommercePlusStoreId"]) : false;
		$abilitaSincronia = isset($optionsSincronizzaUtenti["syncroAuto"]) ?  boolval($optionsSincronizzaUtenti["syncroAuto"]) : false;


		$plugin_admin = new Adv_dem_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Add menu item
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Add Settings link to the plugin
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

		// Save/Update our plugin options
		$this->loader->add_action('admin_init', $plugin_admin, 'options_update');

        // Definiamo un hook ajax per verificare l'apikey
		$this->loader->add_action( 'wp_ajax_check_apikey', $plugin_admin, 'ajax_check_apikey' );
		$this->loader->add_action( 'wp_ajax_nopriv_check_apikey', $plugin_admin, 'ajax_check_apikey' );

		// Definiamo un hook per il dump della configurazione di sistema in caso di richiesta di supporto
		$this->loader->add_action( 'wp_ajax_dump_configuration', $plugin_admin, 'ajax_dump_configuration' );
		$this->loader->add_action( 'wp_ajax_nopriv_dump_configuration', $plugin_admin, 'ajax_dump_configuration' );

		// Definiamo un hook ajax per la sincronizzazione degli utenti via Ajax
		$this->loader->add_action( 'wp_ajax_syncro_users', $plugin_admin, 'ajax_synch_registered_users' );
		$this->loader->add_action( 'wp_ajax_nopriv_syncro_users', $plugin_admin, 'ajax_synch_registered_users' );
		$this->loader->add_action( 'wp_ajax_syncro_users_batch', $plugin_admin, 'ajax_synch_registered_users_batch' );
		$this->loader->add_action( 'wp_ajax_nopriv_syncro_users_batch', $plugin_admin, 'ajax_synch_registered_users_batch' );


		$this->loader->add_action( 'wp_ajax_verify_active_batch', $plugin_admin, 'ajax_verify_active_batch' );
		$this->loader->add_action( 'wp_ajax_nopriv_verify_active_batch', $plugin_admin, 'ajax_verify_active_batch' );
		$this->loader->add_action( 'wp_ajax_delete_batch', $plugin_admin, 'ajax_delete_batch' );
		$this->loader->add_action( 'wp_ajax_nopriv_delete_batch', $plugin_admin, 'ajax_delete_batch' );


		$this->loader->add_action( 'wp_ajax_init_shop', $plugin_admin, 'ajax_init_shop' );
		$this->loader->add_action( 'wp_ajax_nopriv_init_shop', $plugin_admin, 'ajax_init_shop' );

		$this->loader->add_action( 'wp_ajax_update_eplus_order_status_closed_option', $plugin_admin, 'ajax_update_eplus_order_status_closed_option' );
		$this->loader->add_action( 'wp_ajax_nopriv_update_eplus_order_status_closed_option', $plugin_admin, 'ajax_update_eplus_order_status_closed_option' );

		$this->loader->add_action( 'wp_ajax_init_plugin', $plugin_admin, 'ajax_plugin_initialize' );
		$this->loader->add_action( 'wp_ajax_nopriv_init_plugin', $plugin_admin, 'ajax_plugin_initialize' );

		$this->loader->add_action( 'wp_ajax_verify_apikey', $plugin_admin, 'ajax_plugin_verify_apikey' );
		$this->loader->add_action( 'wp_ajax_nopriv_verify_apikey', $plugin_admin, 'ajax_plugin_verify_apikey' );

		// Definizione hook per mantenere la sincroina degli utenti e degli ordini
		// if ($abilitaSincronia) {
		// 	$this->loader->add_action( 'profile_update', $plugin_admin, 'adv_profile_update' , null, 2  );
		// 	$this->loader->add_action( 'user_register', $plugin_admin, 'adv_user_register', 10, 1 );
		// }



		// Definizione di un Hook per i messaggi di ritorno dalla console
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'adv_webhook_endpoint');

		// Hooks riservati a woocommerce
		if (  $woocommerceAttivo && $ecommercePlusAttivo ) {
			// Hook per la gestione della categoria predefinita di Ecommerce Plus
			$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'adv_pc_meta_box');
			// $this->loader->add_action( 'save_post', $plugin_admin, 'adv_pc_field_data');
			$this->loader->add_action( 'manage_edit-product_columns', $plugin_admin, 'adv_edit_product_columns' , 15);
			$this->loader->add_action( 'manage_product_posts_custom_column', $plugin_admin, 'adv_pc_custom_column' , 10 , 2);
			// do_action( 'deleted_post', $postid );
			$this->loader->add_action( 'save_post', $plugin_admin, 'adv_update_woocommerce_post', 10, 2 );
			$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'adv_update_woocommerce_post', 10, 1 );

			// Hook per la gestione del carrello virtuale
			// $this->loader->add_action('woocommerce_add_to_cart', $plugin_admin , 'adv_manage_custom_cart');
			// $this->loader->add_action('woocommerce_cart_item_removed', $plugin_admin , 'adv_manage_custom_cart');
			$this->loader->add_action('add_to_cart_fragments', $plugin_admin , 'adv_header_add_to_cart_fragment');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		// Leggo le impostazioni di sincronizzazione
		$optionsSincronizzaUtenti = get_option($this->plugin_name . "sincronizza_utenti");
		$abilitaSincronia = isset($optionsSincronizzaUtenti["syncroAuto"]) ?  boolval($optionsSincronizzaUtenti["syncroAuto"]) : false;
		$forceSubcribe = isset($optionsSincronizzaUtenti["forceSubcribe"]) ?  boolval($optionsSincronizzaUtenti["forceSubcribe"]) : false;

		$plugin_public = new Adv_dem_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'widgets_init', $plugin_public, 'register_widgets');

		// Definiamo un hook ajax per verificare l'apikey
		$this->loader->add_action( 'wp_ajax_adv_dem_widget_subscribe', $plugin_public, 'ajax_widget_subscribe' );
		$this->loader->add_action( 'wp_ajax_nopriv_adv_dem_widget_subscribe', $plugin_public, 'ajax_widget_subscribe' );


		// Hook riservati a Woocommerce
		if ( (in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) && $abilitaSincronia) {
			// SALVATAGGIO UTENTE WOOCOMMERCE
			$this->loader->add_action( 'woocommerce_customer_save_address', $plugin_public, 'adv_woocommerce_customer_save_address' );
			// CREAZIONE NUOVO ORDINE PROCESSATO
			$this->loader->add_action( 'woocommerce_checkout_order_processed', $plugin_public, 'adv_woocommerce_checkout_order_processed' );

			// Se non ho impostato l'iscrizione automatica da bandito visualizzo la checkbox nel checkout
			if (!$forceSubcribe) {
				// Definisce un hook per l'aggancio della checkbox di richiesta ricezione messaggi promozionali
				$this->loader->add_action($optionsSincronizzaUtenti['syncroPosition'], $plugin_public, 'adv_custom_checkbox_iscrizione');
				// Definisce un hook per il salvataggio del valore della checkbox nei meta dell'ordine'
				$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $plugin_public, 'adv_maybe_save_checkout_fields' );
			}
		}

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Adv_dem_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
