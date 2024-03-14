<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       www.4marketing.it
 * @since      1.0.0
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/admin
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/admin
 * @author     4marketing.it <sviluppo@4marketing.it>
 */
class Adv_dem_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * An array containing the custom fields to be created by the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $customFieldsArray   The custom fields array
	 */
	private $customFieldsArray;

	/**
	 * Supported algorithms to sign the token.
	 *
	 * @var array|string[]
	 * @since 1.3.1
	 * @see https://www.rfc-editor.org/rfc/rfc7518#section-3
	 */
	private $supported_algorithms = [ 'HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512', 'ES256', 'ES384', 'ES512', 'PS256', 'PS384', 'PS512' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->customFieldsArray = array("first_name","last_name","company","address_1","address_2","city","postcode","country","state","phone", "billing_email");
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Adv_dem_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Adv_dem_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/adv_dem-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'toggles-style', plugin_dir_url( __FILE__ ) . 'css/toggles-full.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Adv_dem_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Adv_dem_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/adv_dem-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'toggles-js', plugin_dir_url( __FILE__ ) . 'js/jquery-toggles-master/toggles.js', array(), '' , false );


	}

	/**
	* Register the administration menu for this plugin into the WordPress Dashboard menu.
	*
	* @since    1.0.0
	*/

	public function add_plugin_admin_menu() {

		/*
		* Add a settings page for this plugin to the Settings menu.
		*
		* NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		*
		*        Administration Menus: http://codex.wordpress.org/Administration_Menus
		*
		*/
		add_menu_page( 'Newsletter Integration Setup', ADV_DEM_COMPANY . " - " .  esc_html__('Integration', $this->plugin_name), 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page') , ADV_DEM_COMPANY_ICON);
		add_submenu_page($this->plugin_name, ADV_DEM_COMPANY . " - " . esc_html__('Configura API Key di accesso', $this->plugin_name),  		esc_html__('Configura API Key' , $this->plugin_name), 'manage_options' , $this->plugin_name,  array($this, 'display_plugin_setup_page' ));
		add_submenu_page($this->plugin_name, ADV_DEM_COMPANY . " - " . esc_html__('Sincronizza Utenti', $this->plugin_name), 			esc_html__('Sincronizza Utenti', $this->plugin_name), 'manage_options' , $this->plugin_name . "_sync_users",  array($this, 'display_sincronizzazione_utenti_page' ));
		add_submenu_page($this->plugin_name, ADV_DEM_COMPANY . " - " . esc_html__('Configurazione E-commerce Plus', $this->plugin_name),  	esc_html__('E-commerce Plus', $this->plugin_name), 'manage_options' , $this->plugin_name . "_ecommerce_plus",  array($this, 'display_plugin_setup_ecommerceplus' ));
		add_submenu_page($this->plugin_name, ADV_DEM_COMPANY . " - " . esc_html__('Riepilogo operazioni di gruppo', $this->plugin_name),		esc_html__('Gruppi di operazioni', $this->plugin_name), 'manage_options' , $this->plugin_name . "_batch_table",  array($this, 'display_plugin_batch_table' ));
	}

	/**
	* Add settings action link to the plugins page.
	*
	* @since    1.0.0
	*/

	public function add_action_links( $links ) {
		/*
		*  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
		*/
		$settings_link = array(
			'<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . esc_html__('Settings', $this->plugin_name) . '</a>',
		);
		return array_merge(  $settings_link, $links );
	}

	/**
	* Render the settings page for this plugin.
	*
	* @since    1.0.0
	*/

	public function display_plugin_setup_page() {
		include_once( 'partials/adv_dem-admin-display.php' );
	}

	public function display_sincronizzazione_utenti_page(){
		include_once( 'partials/adv_dem-admin-display-sincronizzazione-utenti.php' );
	}

	public function display_plugin_batch_table() {
		include_once( 'partials/adv_dem-admin-display-batch-table.php' );
	}

	public function display_plugin_setup_ecommerceplus() {
		include_once( 'partials/adv_dem-admin-display-ecommerce-plus.php' );
	}

	/**
	 * Validation function for plugin options
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate($input) {
		$valid = array();
		$eplusOrderStatusClosed = array('wc-on-hold','wc-processing','wc-completed','wc-pending');
		$options = get_option($this->plugin_name);
		$valid["apikey"] 				 = sanitize_text_field(isset($input['apikey']) 				  ? $input['apikey'] : "");
		$valid["entrypoint"] 			 = sanitize_text_field(isset($input['entrypoint']) 			  ? $input['entrypoint'] : "");
		$valid["syncroRecipientId"] 	 = sanitize_text_field(isset($input['syncroRecipientId']) 	  ? $input['syncroRecipientId'] : "");
		$valid["initPluginDate"] 		 = sanitize_text_field(isset($input['initPluginDate']) 		  ? $input['initPluginDate'] : "");
		$valid["ecommercePlusStoreId"] 	 = sanitize_text_field(isset($input['ecommercePlusStoreId'])   ? $input['ecommercePlusStoreId'] : "");
		$valid["first_name"] 			 = sanitize_text_field(isset($input['first_name']) 			  ? $input['first_name'] : "");
		$valid["last_name"] 			 = sanitize_text_field(isset($input['last_name']) 			  ? $input['last_name'] : "");
		$valid["company"] 				 = sanitize_text_field(isset($input['company']) 				  ? $input['company'] : "");
		$valid["address_1"] 			 = sanitize_text_field(isset($input['address_1']) 			  ? $input['address_1'] : "");
		$valid["address_2"] 			 = sanitize_text_field(isset($input['address_2']) 			  ? $input['address_2'] : "");
		$valid["city"] 					 = sanitize_text_field(isset($input['city']) 				  ? $input['city'] : "");
		$valid["postcode"] 				 = sanitize_text_field(isset($input['postcode']) 			  ? $input['postcode'] : "");
		$valid["country"] 				 = sanitize_text_field(isset($input['country']) 				  ? $input['country'] : "");
		$valid["state"] 				 = sanitize_text_field(isset($input['state']) 				  ? $input['state'] : "");
		$valid["phone"] 				 = sanitize_text_field(isset($input['phone']) 				  ? $input['phone'] : "");
		$valid["billing_email"] 		 = sanitize_text_field(isset($input['billing_email']) 		  ? $input['billing_email'] : "");
		$valid["eplusOrderStatusClosed"] = sanitize_text_field(isset($input['eplusOrderStatusClosed']) ? $input['eplusOrderStatusClosed'] : $eplusOrderStatusClosed);
		return $valid;
	}

	/**
	 * Validate function for synchro users form
	 *
	 * @param array $input
	 * @return void
	 */
	public function validate2 ($input) {
		$valid = array();
		$valid['forceSubcribe'] = sanitize_text_field(isset($input['forceSubcribe']) ? $input['forceSubcribe'] : "0");
		$valid['syncroAuto']    = sanitize_text_field(isset($input['syncroAuto']) ? $input['syncroAuto'] : "1");
		$valid['syncromessage'] = sanitize_text_field((!isset($input['syncromessage']) || trim($input['syncromessage']) == "") ? esc_html__("Voglio rimanere aggiornato sulle novità del sito" , $this->plugin_name) : $input['syncromessage']);
		$valid['syncroPosition']= sanitize_text_field(isset($input['syncroPosition']) ? $input['syncroPosition'] : 'woocommerce_checkout_before_customer_details');
		return $valid;
	}

	/**
	 * Controls options validators
	 *
	 * @return void
	 */
	public function options_update() {

		$option = get_option($this->plugin_name);
		if (!isset($option)) {
			register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
		}
		if (!isset($option['eplusOrderStatusClosed'])) {
			$option['eplusOrderStatusClosed'] = array('wc-on-hold','wc-processing','wc-completed','wc-pending');
			update_option($this->plugin_name, $option);
		}
		register_setting($this->plugin_name . "sincronizza_utenti", $this->plugin_name . "sincronizza_utenti" , array($this, 'validate2'));
	}

	/**
	 * Check if the ecommerce plus platform is configured or not
	 *
	 * @return void
	 */
	public function checkShopIntegrity() {
		$option = get_option($this->plugin_name);
		$storeInit = isset($option['ecommercePlusStoreId']) ? $option['ecommercePlusStoreId'] : "" ;
		$api_key = ( isset( $option['apikey'] ) )? $option['apikey'] : "" ;
		$entrypoint = ( isset( $option['entrypoint'] ) )? $option['entrypoint'] : "" ;
		$api4m = new Adv_dem_InterfaceAPI( $api_key, $entrypoint );
		$consoleStore = $api4m->getStoreInformation( $storeInit );
		return $api4m->getRequestSuccessful();
	}

	/**
	 * Check mail recipient integrity
	 *
	 * @return void
	 */
	public function checkRecipientIntegrity() {
		$option = get_option($this->plugin_name);
		$recipient_id = isset( $option['syncroRecipientId'] ) ? $option['syncroRecipientId'] : "" ;
		$api_key =  isset( $option['apikey'] ) ? $option['apikey'] : "" ;
		$entrypoint = ( isset( $option['entrypoint'] ) )? $option['entrypoint'] : "" ;
		$api4m = new Adv_dem_InterfaceAPI( $api_key, $entrypoint );
		$recipientCustomFields = $api4m->getRecipientCustomFields( $recipient_id );
		if($api4m->getRequestSuccessful()){
			return $recipientCustomFields;
		}else{
			return false;
		}
	}

	/**
	 * Test valid apikey
	 *
	 * @return void
	 */
	public function check_apikey() {
		$option = get_option($this->plugin_name);
		$api_key = ( isset( $option['apikey'] ) )? $option['apikey'] : "" ;
		$entrypoint = ( isset( $option['entrypoint'] ) )? $option['entrypoint'] : "" ;
		$api4m = new Adv_dem_InterfaceAPI($api_key, $entrypoint);
		$api4m->login();
		return $api4m->getRequestSuccessful();

	}

	public function console_stores_number() {
		$option = get_option($this->plugin_name);
		$api_key = ( isset( $option['apikey'] ) )? $option['apikey'] : "" ;
		$entrypoint = ( isset( $option['entrypoint'] ) )? $option['entrypoint'] : "" ;
		$api4m = new Adv_dem_InterfaceAPI( $api_key, $entrypoint );
		$nStores = $api4m->getStores();
		return count($nStores['data']);
	}

	public function console_user_info() {
		$option = get_option($this->plugin_name);
		$api_key = ( isset( $option['apikey'] ) )? $option['apikey'] : "" ;
		$entrypoint = ( isset( $option['entrypoint'] ) )? $option['entrypoint'] : "" ;
		$api4m = new Adv_dem_InterfaceAPI($api_key, $entrypoint);
		return $api4m->userInfo();
	}


	public function ajax_plugin_verify_apikey () {
		$entrypoint = isset($_POST['data']['entrypoint'])?sanitize_text_field(wp_unslash($_POST['data']['entrypoint'])):'';
		$apikey = isset($_POST['data']['api_key'])?sanitize_text_field(wp_unslash($_POST['data']['api_key'])):'';
		if (trim($apikey) == "") {
			echo json_encode(array('success' => false , 'isEmpty' => true, 'message' => esc_html__('ApiKey non può essere vuota')));
			wp_die();
		}
		$api4m = new Adv_dem_InterfaceAPI($apikey, $entrypoint, false);
		$api4m->login();
		if (!$api4m->getRequestSuccessful()) {
			echo json_encode( array('success' => false , 'isEmpty' => false, 'message' => esc_html__('ApiKey non valida')));
			wp_die();
		}
		echo json_encode( array('success' => true , 'isEmpty' => false, 'message' => esc_html__('ApiKey valida')));
		wp_die();
	}

	public function ajax_plugin_initialize () {

		$updatedOptions = array();
		$forceReInit = isset($_POST['data']['forceReInit'])?boolval(sanitize_text_field(wp_unslash($_POST['data']['forceReInit']))):'';
		$entrypoint = isset($_POST['data']['entrypoint'])?sanitize_text_field(wp_unslash($_POST['data']['entrypoint'])):'';
		$apikey = isset($_POST['data']['apikey'])?sanitize_text_field(wp_unslash($_POST['data']['apikey'])):'';

		// Se Apikey è vuota esce di brutto
		if (trim($apikey) == "") {
			echo json_encode(array('success' => false , 'message' => esc_html__('ApiKey non può essere vuota')));
			wp_die();
		}
		if($forceReInit){
			delete_option( $this->plugin_name );
        	delete_option( $this->plugin_name . 'sincronizza_utenti' );
			$syncroRecipientId = "";
		} else {
			$options = get_option($this->plugin_name);
			$updatedOptions = $options ? $options : array();
			$syncroRecipientId = isset($options['syncroRecipientId']) ? $options['syncroRecipientId'] : "";
		}

		$api4m = new Adv_dem_InterfaceAPI($apikey, $entrypoint, false);
		$api4m->login();
		if (!$api4m->getRequestSuccessful()) {
			echo json_encode( array('success' => false , 'message' => esc_html__('ApiKey non valida')));
			wp_die();
		}

		$newListCreated = false;
		$server_name = !empty($_SERVER['SERVER_NAME'])?sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])):'UNKNOW';
		if($syncroRecipientId == ""){
			// Devo creare una nuova lista: o sono al primo init oppure ho fatto un force reinit
			$args = array(
				"name" => $server_name,
				"opt_in" => array(
					"mode" => "single"
				),
				"opt_out" => array (
					"mode" => "direct",
					"scope" => "this_list",
					"add_to_global_suppression" => false
				),
				"integration" => array(
					"last_sync_date" => date("Y-m-d H:i:s"),
					"active" => false,
					"domain" => $server_name,
					"module" => "Woocommerce"
				)
			);
			$newRecipientId = $api4m->createRecipient( $args );
			if (!$api4m->getRequestSuccessful()) {
				switch($api4m->getLastError()){
					case 400:
						echo json_encode(array('success' => false , 'message' => esc_html__('La lista di sincronizzazione è già presente sulla console. Rinomina o elimina la lista e riprova.')));
					break;
					case 403:
						echo json_encode(array('success' => false , 'message' => esc_html__('Hai raggiunto il numero massimo di liste consentite per la tua console. Impossibile creare una nuova lista.')));
					break;
					default:
						echo json_encode(array('success' => false , 'message' => esc_html__('Errore durante la creazione della lista.')));
					break;
					}
				wp_die();
			}else{
				$newListCreated = array( 'listName' => $server_name, 'initDate' => date("Y-m-d") );
			}
			$updatedOptions['syncroRecipientId'] = $newRecipientId;
			$updatedOptions['initPluginDate'] = date("Y-m-d");
			// A questo punto creiamo i campi personalizzati della lista
			// first_name
			// last_name
			// company
			// address_1
			// address_2
			// city
			// postcode
			// country
			// state
			// phone
			// billing_email
			$customFieldsId = array();
			foreach($this->customFieldsArray as $singleCustomField){
			$args =  array(
				"recipient_id"	=> $newRecipientId,
				"name"			=> $singleCustomField,
				"type"			=> $singleCustomField == 'email' ? "Email" : "Single line",
				"visibility"	=> "Public",
				"global"		=> false,
				"validation" => array(
					"unique" => $singleCustomField == 'email'
					)
				);
				$updatedOptions[$singleCustomField] = $api4m->createNewCustomField( $args );
			}

			$cf = $api4m->getRecipientCustomFields($newRecipientId);
			foreach($cf['data'] as $singleCf){
				if ($singleCf['type'] == 'Email') {
					$updatedOptions['email'] = $singleCf['id'];
				}

			}
		}

		// Aggiorniamo la modalità di sincronizzazione degli utenti. Se non ho mai salvato le preferenze viene impostata la sincro automatica in optin pending.
		$synchOptions = get_option($this->plugin_name . "sincronizza_utenti");
		$updateSynchOptions =  ($synchOptions) ? $synchOptions : array();
		$updateSynchOptions['forceSubcribe'] = isset($updateSynchOptions['forceSubcribe']) ? $updateSynchOptions['forceSubcribe'] : "0";
		$updateSynchOptions['syncroAuto'] = isset($updateSynchOptions['syncroAuto']) ? $updateSynchOptions['syncroAuto'] : "1";
        update_option($this->plugin_name . "sincronizza_utenti", $updateSynchOptions);
		// La lista esiste già devo solo aggiornare la mia apikey
		$updatedOptions['apikey'] = $apikey;
		$updatedOptions['entrypoint'] = $entrypoint;
        update_option($this->plugin_name, $updatedOptions);
		echo json_encode(array('success' => true , 'message' => esc_html__('Operazione terminata con successo'), 'newList' => $newListCreated));
		wp_die();
	}

	public function ajax_synch_registered_users() {
		$options = get_option($this->plugin_name);
		$api_key = $options['apikey'];
		$entrypoint = $options['entrypoint'];
		$subscribeMode = isset($_POST['data']['subscribe_mode'])?sanitize_text_field(wp_unslash($_POST['data']['subscribe_mode'])):'';
		$updateIfDuplicate = isset($_POST['data']['update_if_duplicate'])?boolval(sanitize_text_field(wp_unslash($_POST['data']['update_if_duplicate']))):'';
		$api4m = new Adv_dem_InterfaceAPI( $api_key, $entrypoint);
		// get_users restituisce un array con tutti gli utenti iscritti al sito
		$wordpressUsers = get_users();
		$subscribersID = array();
		$subscribersCount = 0;
		$subscribersErrors = 0;
		$i = 0;
		foreach ($wordpressUsers as $wordpressUser) {
			$i++;
			if ($i == 10) break;
			// get_user_meta restituisce un array con tutti i meta di uno specifico utente
				$wordpressUserDetails =  array_map( function( $a ){ return $a[0]; }, get_user_meta( $wordpressUser->ID ) );
				unset($argsCustomFields);
				$argsCustomFields = array();

				$emailAddress = $wordpressUser->user_email;

			if (class_exists('Woocommerce')) {
				// $emailAddress = $wordpressUserDetails['billing_email'];
				$argsCustomFields[] = array('id' => $options['first_name'], 'value' 	=> isset($wordpressUserDetails['billing_first_name']) 	? $wordpressUserDetails['billing_first_name'] : '');
				$argsCustomFields[] = array('id' => $options['last_name'], 'value' 		=> isset($wordpressUserDetails['billing_last_name']) 	? $wordpressUserDetails['billing_last_name'] : '');
				$argsCustomFields[] = array('id' => $options['company'], 'value' 		=> isset($wordpressUserDetails['billing_company']) 		? $wordpressUserDetails['billing_company'] : '');
				$argsCustomFields[] = array('id' => $options['address_1'], 'value' 		=> isset($wordpressUserDetails['billing_address_1']) 	? $wordpressUserDetails['billing_address_1'] : '');
				$argsCustomFields[] = array('id' => $options['address_2'], 'value' 		=> isset($wordpressUserDetails['billing_address_2']) 	? $wordpressUserDetails['billing_address_2'] : '');
				$argsCustomFields[] = array('id' => $options['city'], 'value'			=> isset($wordpressUserDetails['billing_city']) 		? $wordpressUserDetails['billing_city'] : '');
				$argsCustomFields[] = array('id' => $options['postcode'], 'value' 		=> isset($wordpressUserDetails['billing_postcode']) 	? $wordpressUserDetails['billing_postcode'] : '');
				$argsCustomFields[] = array('id' => $options['country'], 'value' 		=> isset($wordpressUserDetails['billing_country']) 		? $wordpressUserDetails['billing_country'] : '');
				$argsCustomFields[] = array('id' => $options['state'], 'value' 			=> isset($wordpressUserDetails['billing_state']) 		? $wordpressUserDetails['billing_state'] : '');
				$argsCustomFields[] = array('id' => $options['phone'], 'value' 			=> isset($wordpressUserDetails['billing_phone']) 		? $wordpressUserDetails['billing_phone'] : '');
				$argsCustomFields[] = array('id' => $options['billing_email'], 'value' 	=> isset($wordpressUserDetails['billing_email']) 		? $wordpressUserDetails['billing_email'] : '');
			} else {
				$argsCustomFields[] = array('id' => $options['first_name'], 'value' 	=> isset($wordpressUserDetails['first_name']) ? $wordpressUserDetails['first_name'] : '');
				$argsCustomFields[] = array('id' => $options['last_name'], 'value' 		=> isset($wordpressUserDetails['last_name']) ? $wordpressUserDetails['last_name'] : '');
			}

			if ($subscribeMode == "ignore") {
				$args= array(
					"email_address" => $emailAddress,
					"subscription" => array( //optional
						"ip" => !empty($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'UNKNOW', //if subscription exists is required
						"date" => date("Y-m-d H:i:s") //optional
					),
					"triggers" => array(
						"automation" => true,
						"behaviors" => true
					),
					"update_if_duplicate" => $updateIfDuplicate,
					"custom_fields" =>  $argsCustomFields
				);
			} else {
				$args= array(
					"email_address" => $emailAddress,
					"subscription" => array( //optional
						"ip" => !empty($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'UNKNOW', //if subscription exists is required
						"status" => $subscribeMode,
						"date" => date("Y-m-d H:i:s") //optional
					),
					"triggers" => array(
						"automation" => true,
						"behaviors" => true
					),
					"update_if_duplicate" => $updateIfDuplicate,
					"custom_fields" =>  $argsCustomFields
				);
			}
			$subscriberID = $api4m->subscribeContact($options['syncroRecipientId'] , $args);
			$subscribersID[] = $subscriberID;
			if($api4m->getRequestSuccessful()){
				// add_user_meta aggiunge un meta value ad un utente wordpress
				add_user_meta(  $wordpressUser->ID, 'adv_dem_consoleId', $subscriberID, 'true' );
				// update_user_meta aggiorna un meta value di un utente wordpress
				update_user_meta( $wordpressUser->ID, 'adv_dem_consoleId', $subscriberID );
				$subscribersCount++;
			}else{
				$subscribersErrors++;
			}
		}
		echo json_encode(array('subscriptions' => $subscribersCount, 'errors' => $subscribersErrors , 'subscribersId' => $subscribersID));
		wp_die();
	}

	public function ajax_synch_registered_users_batch() {
		global $wpdb;
		$options = get_option($this->plugin_name);
		$api_key = $options['apikey'];
		$subscribeMode = isset($_POST['data']['subscribe_mode'])?sanitize_text_field(wp_unslash($_POST['data']['subscribe_mode'])):'';
		$updateIfDuplicate = isset($_POST['data']['update_if_duplicate'])?boolval(sanitize_text_field(wp_unslash($_POST['data']['update_if_duplicate']))):'';
		$entrypoint = $options['entrypoint'];
		$api4m = new Adv_dem_InterfaceAPI( $api_key, $entrypoint );
		$argsCustomFields = array();
		$subscribersCount = count_users()["total_users"];
		$token = $this->generate_token($api_key);

		if (class_exists('Woocommerce')) {
			$argsCustomFields = array(
				$options['first_name'] => 'first_name',
				$options['last_name'] =>'last_name',
				$options['company'] =>'company',
				$options['address_1'] =>'address_1',
				$options['address_2'] =>'address_2',
				$options['city'] =>'city',
				$options['postcode'] =>'postcode',
				$options['country'] =>'country',
				$options['state'] =>'state',
				$options['phone'] =>'phone',
				$options['billing_email'] =>'billing_email',
				$options['email'] =>'email',
			);
		} else {
			$argsCustomFields = array(
				$options['first_name'] =>'first_name',
				$options['last_name'] =>'last_name',
				$options['email'] =>'email',
			);
		}

		$import = array(
    		"callback_url" => get_rest_url()."adv_dem_callback/users_from_url/",
			"entity_type"=>"recipient",
			"entity_id"=> $options['syncroRecipientId'],
			"settings"=> [
				"update_if_duplicate"=> $updateIfDuplicate,
				"subscriber_status"=> $subscribeMode == 'Opt-In Pending' ? $subscribeMode : 'Subscribed',
				"triggers"=>[
					"automation"=>false,
					"behaviors"=>false
				]
			],
			"import_strategy"=>"url",
			"import_strategy_settings"=> [
				"url" => get_rest_url()."adv_dem_callback/import_users?token=".$token,
				"is_paginated" => false,
				"timeout" => "3000",
				"path" => "",
			],
			"mapped_fields"=> $argsCustomFields
		);
		$table_name = $wpdb->prefix . "adv_dem_batches";
		$import_id = $api4m->createImport($import);

		$api4m->startImport($import_id);

		$wpdb->insert(
			$table_name,
			array(
				'batch_Type' => "USER",
				'batch_Id' => 'import_'.$import_id,
				'batch_Status' => "ACTIVE",
				'batch_Result' => "",
				'batch_Operations' => $subscribersCount
			)
		);
		//gestire errore campo mancante
		echo json_encode(array('subscriptions' => 0, 'batches_operations_id' => array($import_id)));
		wp_die();
	}


	/**
	* Check the database for active batch process
	*
	* @access public
	* @return array
	*/
	public function ajax_verify_active_batch() {
		global $wpdb;
		$batchType = isset($_POST['batch_Type'])?sanitize_text_field(wp_unslash($_POST['batch_Type'])):'';
		switch ($batchType) {
			case 'USER':
				$queryBatchType = ' AND batch_Type = "USER"';
				$queryBatchTypeArray = array('batch_Status' => "COMPLETE", 'batch_Type' => 'USER');
				$queryBatchTypeArray2 = array('batch_Status' => "COMPLETE_WITH_ERRORS", 'batch_Type' => 'USER');
				break;
			case 'PRODUCT':
				$queryBatchType = ' AND batch_Type = "PRODUCT"';
				$queryBatchTypeArray = array('batch_Status' => "COMPLETE", 'batch_Type' => 'PRODUCT');
				$queryBatchTypeArray2 = array('batch_Status' => "COMPLETE_WITH_ERRORS", 'batch_Type' => 'PRODUCT');
				break;
			case 'ORDER':
				$queryBatchType = ' AND batch_Type = "ORDER"';
				$queryBatchTypeArray = array('batch_Status' => "COMPLETE", 'batch_Type' => 'ORDER');
				$queryBatchTypeArray2 = array('batch_Status' => "COMPLETE_WITH_ERRORS", 'batch_Type' => 'ORDER');
				break;
			case 'PRODUCT-ORDER':
				$queryBatchType = ' AND batch_Type IN ("PRODUCT", "ORDER")';
				$queryBatchTypeArray = array('batch_Status' => "COMPLETE", 'batch_Type' => 'ORDER');
				$queryBatchTypeArray2 = array('batch_Status' => "COMPLETE_WITH_ERRORS", 'batch_Type' => 'ORDER');
				$queryBatchTypeArray3 = array('batch_Status' => "COMPLETE", 'batch_Type' => 'PRODUCT');
				$queryBatchTypeArray4 = array('batch_Status' => "COMPLETE_WITH_ERRORS", 'batch_Type' => 'PRODUCT');
				break;
			case 'ALL':
				$queryBatchType = "";
				$queryBatchTypeArray = array('batch_Status' => "COMPLETE");
				$queryBatchTypeArray2 = array('batch_Status' => "COMPLETE_WITH_ERRORS");
				break;

			default:
				$queryBatchType = "";
				$queryBatchTypeArray = array('batch_Status' => "COMPLETE");
				$queryBatchTypeArray2 = array('batch_Status' => "COMPLETE_WITH_ERRORS");
				break;
		}
		$table_name = $wpdb->prefix . "adv_dem_batches";
		$activeBatches = $wpdb->get_var('SELECT COUNT(*) FROM '.$table_name.' WHERE batch_Status = "ACTIVE"'.$queryBatchType);
		$completeBatches = $wpdb->get_var('SELECT COUNT(*) FROM  '.$table_name.' WHERE ( batch_Status = "COMPLETE" OR batch_Status = "COMPLETE_WITH_ERRORS" )'.$queryBatchType);
		$numberOfSubscription = $wpdb->get_var('SELECT SUM(batch_Operations) FROM  '.$table_name.' WHERE batch_Status IN ("COMPLETE", "COMPLETE_WITH_ERRORS", "ACTIVE") '.$queryBatchType);
		$wpdb->query($wpdb->prepare('UPDATE '.$table_name.' SET batch_Status = "OVER TIME" WHERE batch_Start_Time < (NOW() - INTERVAL 10 MINUTE) AND batch_Status = "ACTIVE"'));
		if (!$activeBatches) {
			if($batchType == "PRODUCT-ORDER"){
				$wpdb->update( $table_name, array('batch_Status' => 'ARCHIVED'), $queryBatchTypeArray );
				$wpdb->update( $table_name, array('batch_Status' => 'ARCHIVED_WITH_ERRORS'), $queryBatchTypeArray2 );
				$wpdb->update( $table_name, array('batch_Status' => 'ARCHIVED'), $queryBatchTypeArray3 );
				$wpdb->update( $table_name, array('batch_Status' => 'ARCHIVED_WITH_ERRORS'), $queryBatchTypeArray4 );
			}else{
				$wpdb->update( $table_name, array('batch_Status' => 'ARCHIVED'), $queryBatchTypeArray );
				$wpdb->update( $table_name, array('batch_Status' => 'ARCHIVED_WITH_ERRORS'), $queryBatchTypeArray2 );
			}
		}
		echo json_encode(array("activeBatches"=>$activeBatches, "completeBatches"=>$completeBatches, "numberOfSubscription"=>$numberOfSubscription ));

		wp_die();
	}

	/**
	* Delete batches from database table (table page)
	*
	* @access public
	* @return array
	*/
	public function ajax_delete_batch() {
		global $wpdb;
		$batch_Id = isset($_POST['data']['batch_Id'])?sanitize_text_field(wp_unslash($_POST['data']['batch_Id'])):'';
		$table_name = $wpdb->prefix . "adv_dem_batches";
		if(isset($batch_Id) && is_numeric($batch_Id)){
			$deleteBatch = $wpdb->delete( $table_name, array( 'batch_Id' => $batch_Id ));
		}elseif (isset($batch_Id) && ($batch_Id == "removeArchivedItem")) {
			$deleteBatch = $wpdb->delete( $table_name, array( 'batch_Status' => "ARCHIVED" ));
			$deleteBatch = $wpdb->delete( $table_name, array( 'batch_Status' => "ARCHIVED_WITH_ERRORS" ));
			$deleteBatch = $wpdb->delete( $table_name, array( 'batch_Status' => "ABORTED" ));
			$deleteBatch = $wpdb->delete( $table_name, array( 'batch_Status' => "OVER TIME" ));
		}/*elseif (isset($batch_Id) && ($batch_Id == "removeAllBatches")){
			$deleteBatch = $wpdb->query("TRUNCATE TABLE $table_name");
		}*/else{
			$deleteBatch = false;
		}
		echo json_encode(array("deleteBatch"=>$deleteBatch));
		wp_die();
	}

	/**
	* Check if Apikey is valid
	*
	* @access public
	* @return array
	*/
	public function ajax_check_apikey() {
		try {
			if ( empty( sanitize_text_field(wp_unslash($_POST['data']['api_key'])) ) ) {
				throw new Exception( esc_html__('Per favore inserisci una Apikey.' , $this->plugin_name) );
			}
			$api_key = sanitize_text_field(wp_unslash($_POST['data']['api_key']));
			$entrypoint = isset($_POST['data']['entrypoint'])?sanitize_text_field(wp_unslash($_POST['data']['entrypoint'])):'';
			$api4m = new Adv_dem_InterfaceAPI($api_key, $$entrypoint);
			$result = $api4m->login();
		}
		catch ( Exception $e ) {
		}
		echo json_encode($result);
		wp_die();
	}

/**
	* Init shop and update attributes
	*
	* @access public
	* @return array
	*/
	public function ajax_init_shop() {
		$response = array();
		$options = get_option($this->plugin_name);
		$ecPlus = new Adv_dem_EcommercePlus();
		$button_id = isset($_POST['button_id'])?sanitize_text_field(wp_unslash($_POST['button_id'])):'';
		switch($button_id){
			case "init_shop":
				$response['init'] = false;
				$force_reinit = isset($_POST['force_reinit'])?sanitize_text_field(wp_unslash($_POST['force_reinit'])):'';
				if(boolval($force_reinit)){
					$storeReinit = true;
				} else {
					$storeReinit = (isset($options['ecommercePlusStoreId']) && $options['ecommercePlusStoreId'] != "") ? false : true;
				}

				// Inizializzazione globale shop
				if ($storeReinit) {
					$initResponse = $ecPlus->adv_ecplus_init_shop($storeReinit);
					switch ($initResponse["status_code"]) {
						case 403:
							$response['init'] = false;
							$response['error_message'] = esc_html__('Hai terminato il numero di licenze E-commerce Plus a tua disposizione. Non è possibile inizializzare lo shop.' , $this->plugin_name);
						break;
						default:
							$response['init'] = $initResponse;
						break;
					}
					// if($response['init']) $this->needProductAlign($storeReinit);
				}

				/*Scommentare se si vogliono pure i processi di salvataggio degli ordini e prodotti dopo l'init*/

				// $active_attribute = $_POST['active_attribute'];
				// $response['batches_products'] = false;
				// $response['batches_order'] = false;

				// // Inizializzazione ed aggiornamento delle proprietà personalizzate
				// $response['needProductAlign'] = $ecPlus->adv_ecplus_set_product_attributes($active_attribute);

				// // Riallineamento dei prodotti
				// if($response['needProductAlign']){
				// 	$response['needProductAlign'] = true;
				// 	$response['batches_products'] = $ecPlus->adv_ecplus_products_align();
				// }

				// // Riallineamento degli ordini
				// if( $storeReinit ) {
				// 	$response['batches_order'] = $ecPlus->adv_ecplus_order_align();
				// }
			;
			break;
			case "save_option":
				if(!isset($_POST['active_attribute'])){
					break;
				}
				$active_attribute = sanitize_text_field(wp_unslash($_POST['active_attribute']));
				$response['batches_products'] = false;
				$response['batches_order'] = false;

				// Inizializzazione ed aggiornamento delle proprietà personalizzate
				$response['needProductAlign'] = $ecPlus->adv_ecplus_set_product_attributes($active_attribute);

				// Riallineamento dei prodotti
				if($response['needProductAlign']){
					$response['needProductAlign'] = true;
					$response['batches_products'] = $ecPlus->adv_ecplus_products_align();
				}

			;
			break;
			case "product_update":
				$response['batches_products'] = $ecPlus->adv_ecplus_products_align();
			break;
			case "order_update":
				if(!isset($_POST['order_time'])) {
				    break;
				}
				$order_time = sanitize_text_field(wp_unslash($_POST['order_time']));
				switch( $order_time ){
					case 1 : $days = "30";break;
					case 2 : $days = "90";break;
					case 3 : $days = "180";break;
				}
				$response['batches_order'] = $ecPlus->adv_ecplus_order_align($days);
			break;
		}

		echo json_encode($response);
		wp_die();
	}

	// FUNZIONI CHIAMATE VIA AJAX    -----------    FINE

	public function ajax_update_eplus_order_status_closed_option() {
		$updatedOptions = array();
		$options = get_option($this->plugin_name);
		$updatedOptions = $options ? $options : array();
		$eplusOrderStatusArr = isset($_POST['orderStatus'])? $_POST['orderStatus'] : '';
		$updatedOptions['eplusOrderStatusClosed'] = $eplusOrderStatusArr;
		update_option($this->plugin_name, $updatedOptions);
		echo json_encode(array('success' => true , 'message' => esc_html__('Operazione terminata con successo')));
		wp_die();
	}


	/**
	* Update a user to the console (used my real time syncro process with wordpress profile data)
	*
	* @param integer The id of wordpress user to be subscribed/updated
	*/
	public function adv_profile_update($user_id, $oldUserData = false) {
		$optionsSincronizzaUtenti = get_option($this->plugin_name . "sincronizza_utenti");
		$forceSubcribe = isset($optionsSincronizzaUtenti["forceSubcribe"]) ?  boolval($optionsSincronizzaUtenti["forceSubcribe"]) : false;
		$newUserData = get_userdata( $user_id );
		$userConsoleId = get_user_meta( $user_id, 'adv_dem_consoleId', true );
		$synchroTool = new Adv_dem_SyncroTools();
		if (($newUserData->user_email != $oldUserData->user_email) && ($userConsoleId != '')) {
			$synchroTool->adv_update_email_address( $userConsoleId, $newUserData->user_email);
		}
		$result = $synchroTool->adv_subscribe_update_user($user_id, $forceSubcribe);
		unset($synchroTool);
	}

	/**
	* Subscribe a user to the console (used my real time syncro process)
	*
	* @param integer The id of wordpress user to be subscribed/updated
	*/
	public function adv_user_register($user_id){
		$optionsSincronizzaUtenti = get_option($this->plugin_name . "sincronizza_utenti");
		$forceSubcribe = isset($optionsSincronizzaUtenti["forceSubcribe"]) ?  boolval($optionsSincronizzaUtenti["forceSubcribe"]) : false;
		$synchroTool = new Adv_dem_SyncroTools();
		$result = $synchroTool->adv_subscribe_update_user($user_id, $forceSubcribe);
		unset($synchroTool);
	}

	/**
	* Adds a custom endpoint to active isntallation
	*
	*/

	public function adv_webhook_endpoint() {

		register_rest_route( 'adv_dem_callback', '/batch', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'adv_dem_callback'),
		) );

		register_rest_route( 'adv_dem_callback', '/users_from_url', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'adv_dem_import_user_from_url_callback'),
		) );

		register_rest_route( 'adv_dem_callback', '/import_users', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'adv_dem_import_users')
		) );

		register_rest_route( 'adv_dem_callback', '/dump', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'ajax_dump_configuration'),
		) );

		register_rest_route( 'adv_dem_callback', '/exportCat', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'ajax_export_product_csv'),
		) );
	}

	private function generate_secret_key() {
		$randomString = wp_generate_password(32, true, true);

		add_option($this->plugin_name."_secret_key", $randomString);
	}

	/**
	 * Get the user and password in the request body and generate a JWT
	 *
	 * @param WP_REST_Request $request
	 *
	 */
	public function generate_token( $api_key ) {
		$secret_key = get_option($this->plugin_name."_secret_key");
		if (! $secret_key) {
			$this->generate_secret_key();
			$secret_key = get_option($this->plugin_name."_secret_key");
		}

		/** First thing, check the secret key if not exist return an error*/
		if ( ! $secret_key ) {
			return $this->setResponseCode(401,'JWT is not configured properly, please contact the admin');
		}
		/** Try to authenticate the user with the passed credentials*/

		/** Valid credentials, the user exists create the according Token */
		$issuedAt  = time();
		$notBefore = $issuedAt;
		$expire    = $issuedAt + ( HOUR_IN_SECONDS );

		$token = [
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => $issuedAt,
			'nbf'  => $notBefore,
			'exp'  => $expire,
			'data' => [
				'apikey' => md5($api_key),
			],
		];

		/** Let the user modify the token data before the sign. */
		$algorithm = $this->get_algorithm();

		if ( $algorithm === false ) {
			return $this->setResponseCode(401,'Algorithm not supported');
		}

		$token = JWT::encode($token, $secret_key, $algorithm);
		return $token;
	}

	/**
	 * Sets the response code and reason
	 *
	 * @param int    $code
	 * @param string $reason
	 */
	function setResponseCode($code, $reason = null) {
		$code = intval($code);

		if (version_compare(phpversion(), '5.4', '>') && is_null($reason))
			http_response_code($code);
		else
			header(trim("HTTP/1.0 $code $reason"));
	}

	/**
	 * Main validation function
	 *
	 * This function is used by the /token/validate endpoint and
	 * by our middleware.
	 *
	 * The function take the token and try to decode it and validated it.
	 *
	 * @param WP_REST_Request $request
	 * @param bool|string $custom_token
	 *
	 * @return WP_Error | Object | Array
	 */
	public function validate_token( $token ) {

		/**
		 * if the format is not valid return an error.
		 */
		if ( !$token ) {
			$this->setResponseCode(401,'JWT auth code missing');
			return false;
		}

		$option = get_option($this->plugin_name);
		$api_key =  isset( $option['apikey'] ) ? $option['apikey'] : "" ;
		$secret_key = get_option($this->plugin_name."_secret_key");

		/** Get the Secret Key */

		// da salvare option a db
		if ( !$secret_key ) {
			$this->setResponseCode(401,'JWT is not configured properly, please contact the admin');
			return false;
		}

		/** Try to decode the token */
		try {
			$algorithm = $this->get_algorithm();
			if ( $algorithm === false ) {
				$this->setResponseCode(401,'Algorithm not supported');
				return false;
			}

			$token = JWT::decode( $token, new Key( $secret_key, $algorithm ) );

			/** The Token is decoded now validate the iss */
			if ( $token->iss !== get_bloginfo( 'url' ) ) {
				/** The iss do not match, return error */
				$this->setResponseCode(401,'The iss do not match with this server');
				return false;
			}

			/** So far so good, validate the user id in the token */
			if ( ! isset( $token->data->apikey ) ) {
				/** No user id in the token, abort!! */
				$this->setResponseCode(401,'Apikey not found in the token');
				return false;
			}

			if ( $token->data->apikey !== md5($api_key)) {
				$this->setResponseCode(401,'Apikey is incorrect');
				return false;
			}

			return true;
		} catch ( Exception $e ) {
			/** Something were wrong trying to decode the token, send back the error */
			$this->setResponseCode(401,$e->getMessage());
			return false;
		}
	}

	/**
	 * Get the algorithm used to sign the token via the filter jwt_auth_algorithm.
	 * and validate that the algorithm is in the supported list.
	 *
	 * @return false|mixed|null
	 */
	private function get_algorithm() {
		$algorithm = 'HS256';
		if ( ! in_array( $algorithm, $this->supported_algorithms ) ) {
			return false;
		}

		return $algorithm;
	}

	public function adv_dem_callback( $request ) {
		global $wpdb;
		$body = file_get_contents("php://input");
		$body = json_decode($body, TRUE);
		$batch_Id = $body["id"];
		$table_name = $wpdb->prefix . "adv_dem_batches";
		$output = "";
		$operations = array_keys($body["response"]);
		if ($body["failed_operations"] == "0") {
			$sqlResult = $wpdb->update( $table_name, array('batch_Status' => 'COMPLETED', 'batch_Result'=> "OK", "batch_Finish_Time" => current_time( 'mysql' ) ), array('batch_Id' => $batch_Id) );
		} else {
			$responseWithErrors = $body["response"];
			$errorDet = array();
			foreach ($responseWithErrors as $thisKey=>$thisError) {
				if (isset($thisError["error"])) {
					$errorDet[$thisKey]= $thisError;
				}
			}
			$sqlResult = $wpdb->update( $table_name, array('batch_Status' => 'COMPLETED_WITH_ERRORS', 'batch_Result'=> json_encode($errorDet) , 'batch_Finish_Time' => current_time( 'mysql' )) , array('batch_Id' => $batch_Id) );
		}

		$batchType = $wpdb->get_var($wpdb->prepare("SELECT batch_Type FROM ".$table_name." WHERE batch_Id = %d", $batch_Id));
		if ($batchType  == "USER") {
			foreach ($operations as $operation) {
				$operation_id = $operation;
				// add_user_meta aggiunge un meta value ad un utente wordpress
				add_user_meta(  $operation_id, 'adv_dem_consoleId', $body["response"][$operation]["SubscriberID"], 'true' );
				// update_user_meta aggiorna un meta value di un utente wordpress
				update_user_meta( $operation_id, 'adv_dem_consoleId', $body["response"][$operation]["SubscriberID"] );
				$output .= "\n OPERAZIONE NUMERO: " . $operation_id . "  --  SUBSCRIBER ID: " . $body["response"][$operation]["SubscriberID"];
			}
		}

        return $output;
	}

	/**
	* Callback for console batch process
	*
	* @param array $request
	* @return string Resume of completed operations (debug pourpose only)
	*/
	public function adv_dem_import_user_from_url_callback() {
		global $wpdb;
		$body = file_get_contents("php://input");
		$body = json_decode($body, TRUE);
		$body = $body["data"];
		$batch_Id = $body["id"];

		$table_name = $wpdb->prefix . "adv_dem_batches";
		$data_update = array(
			'batch_Status' => ($body["status"] !== 'failed' && $body["total_error_contacts"] === 0) ? 'COMPLETED' : 'COMPLETED_WITH_ERRORS',
			'batch_Result' => ($body["status"] !== 'failed' && $body["total_error_contacts"] === 0) ? 'OK' : 'ERRORS',
			'batch_Finish_Time' => current_time( 'mysql' )
		);
 		$data_where = array('batch_Id' => 'import_'.$batch_Id, 'batch_Status' => 'ACTIVE');
		$wpdb->update($table_name, $data_update, $data_where);
	}

	public function adv_dem_import_users( $request ) {
		$parameters = $request->get_params();
		$token = $parameters['token'];
		$users = array();

		$validToken = $this->validate_token($token);
		if (!$validToken) {
			echo json_encode($users);
			die();
		}

		$wordpressUsers = get_users();
		foreach ($wordpressUsers as $wordpressUser) {
			$wordpressUserDetails =  array_map( function( $a ){ return $a[0]; }, get_user_meta( $wordpressUser->ID ) );
			$argsCustomFields = array();
			if (class_exists('Woocommerce')) {
				$customer = new WC_Customer( $wordpressUser->ID );
				$argsCustomFields = $customer->get_billing();
				$argsCustomFields['billing_email'] = $argsCustomFields['email'];
			} else {
				$argsCustomFields['first_name'] = isset($wordpressUserDetails['first_name']) ? $wordpressUserDetails['first_name'] : '';
				$argsCustomFields['last_name'] = isset($wordpressUserDetails['last_name']) ? $wordpressUserDetails['last_name'] : '';
			}
			$argsCustomFields['email'] = $wordpressUser->user_email;
			$users[] = $argsCustomFields;
		}

		// return rest_ensure_response($request);
		echo json_encode($users);
		die();
	}


	/**
	* Init primary category object for product types
	*
	* @param array $request
	* @return string Resume of completed operations (debug pourpose only)
	*/
	public function adv_pc_meta_box() {
        // Retrieve all post types and add meta box to all post types, including custom post types
    	$post_types = get_post_types();
    	foreach ( $post_types as $post_type ) {
    		// Skip the "page" post type
    		if ( $post_type != 'product' ) {
    			continue;
    		}
    		add_meta_box (
    			'adv_dem_primary_category',
    			esc_html__('Categoria E-commerce Plus' , $this->plugin_name),
    			array( $this, 'adv_pc_meta_box_content' ),
    			$post_type,
    			'side',
    			'high'
    		);
    	}
    }

	/**
	* Add primary category box for woocommerce products
	*
	* @param array $request
	* @return string Resume of completed operations (debug pourpose only)
	*/
    public function adv_pc_meta_box_content() {
        global $post;
    	$primary_category = '';
    	$primary_category_mid = '';
    	$primary_category_top = '';
    	// Retrieve data from primary_category custom field
    	$current_selected_primary = get_post_meta( $post->ID, 'adv_dem_primary_category', true );
    	$current_selected_mid = get_post_meta( $post->ID, 'adv_dem_primary_category_mid', true );
    	$current_selected_top = get_post_meta( $post->ID, 'adv_dem_primary_category_top', true );
    	// Set variable so that select element displays the set primary category on page load
    	if ( $current_selected_primary != '' ) {
    		$primary_category = $current_selected_primary;
		}
		if ( $current_selected_mid != '' ) {
    		$primary_category_mid = $current_selected_mid;
		}
		if ( $current_selected_top != '' ) {
    		$primary_category_top = $current_selected_top;
		}


    	// Get list of categories associated with post
		$taxonomy     = 'product_cat';
		$orderby      = 'name';
		$show_count   = 0;      // 1 for yes, 0 for no
		$pad_counts   = 0;      // 1 for yes, 0 for no
		$hierarchical = 0;      // 1 for yes, 0 for no
		$title        = '';
		$empty        = 1;

		$args = array(
				'taxonomy'     => $taxonomy,
				'orderby'      => $orderby,
				'show_count'   => $show_count,
				'pad_counts'   => $pad_counts,
				'hierarchical' => $hierarchical,
				'title_li'     => $title,
				'hide_empty'   => $empty
		);
		$post_categories = get_categories( $args );

    	// $post_categories =  wp_get_post_terms( $post->ID, 'product_cat' );
		$html = '<label for="adv_dem_primary_category_top">Categoria Generale</label><br><select name="adv_dem_primary_category_top" id="adv_dem_primary_category_top">';
    	// Load each associated category into select element and display set primary category on page load
		$html .= '<option value="' . esc_html__("NON SPECIFICATA" , "adv_dem") . '" >' . esc_html__("NON SPECIFICATA" , "adv_dem") . '</option>';
    	foreach( $post_categories as $category ) {
    		$html .= '<option value="' . esc_attr($category->name) . '" ' . selected( $primary_category_top, $category->name, false ) . '>' . esc_attr($category->name) . '</option>';
    	}

    	$html .= '</select><br>';

		$html .= '<label for="adv_dem_primary_category_mid">Categoria Media</label><br><select name="adv_dem_primary_category_mid" id="adv_dem_primary_category_mid">';
    	// Load each associated category into select element and display set primary category on page load
		$html .= '<option value="' . esc_html__("NON SPECIFICATA" , "adv_dem") . '" >' . esc_html__("NON SPECIFICATA" , "adv_dem") . '</option>';
    	foreach( $post_categories as $category ) {
    		$html .= '<option value="' . esc_attr($category->name) . '" ' . selected( $current_selected_mid, $category->name, false ) . '>' . esc_attr($category->name) . '</option>';
    	}
    	$html .= '</select><br>';
		$html .= '<label for="adv_dem_primary_category">Categoria Specifica</label><br><select name="adv_dem_primary_category" id="adv_dem_primary_category">';
    	// Load each associated category into select element and display set primary category on page load
		$html .= '<option value="' . esc_html__("NON SPECIFICATA" , "adv_dem") . '" >' . esc_html__("NON SPECIFICATA" , "adv_dem") . '</option>';
    	foreach( $post_categories as $category ) {
    		$html .= '<option value="' . esc_attr($category->name) . '" ' . selected( $primary_category, $category->name, false ) . '>' . esc_attr($category->name) . '</option>';
    	}

    	$html .= '</select><br>';


    	echo $html;

    }

    /**
	* Save primary category for current product
	*
	* @param array $request
	* @return string Resume of completed operations (debug pourpose only)
	*/
    public function adv_pc_field_data() {
        global $post;
    	if ( isset( $_POST[ 'adv_dem_primary_category' ] ) ) {
    		$primary_category = sanitize_text_field( wp_unslash($_POST[ 'adv_dem_primary_category' ]) );
    		update_post_meta( $post->ID, 'adv_dem_primary_category', $primary_category );
    	}
		if ( isset( $_POST[ 'adv_dem_primary_category_mid' ] ) ) {
    		$primary_category = sanitize_text_field( wp_unslash($_POST[ 'adv_dem_primary_category_mid' ]) );
    		update_post_meta( $post->ID, 'adv_dem_primary_category_mid', $primary_category );
    	}
		if ( isset( $_POST[ 'adv_dem_primary_category_top' ] ) ) {
    		$primary_category = sanitize_text_field( wp_unslash($_POST[ 'adv_dem_primary_category_top' ]) );
    		update_post_meta( $post->ID, 'adv_dem_primary_category_top', $primary_category );
    	}
    }

	/**
	* Add custom columns to product list view
	*
	* @param array $columns
	* @return array The array with updated columns list
	*/
	public function adv_edit_product_columns($columns){
		//remove column
		unset( $columns['adv_dem_primary_category_column'] );
		unset( $columns['adv_dem_primary_category_column_mid'] );
		unset( $columns['adv_dem_primary_category_column_top'] );

		//add column
		$columns['adv_dem_primary_category_column_top'] = esc_html__('Categoria generale E-Plus' , $this->plugin_name);
		$columns['adv_dem_primary_category_column_mid'] = esc_html__('Categoria media E-Plus' , $this->plugin_name);
		$columns['adv_dem_primary_category_column'] = esc_html__('Categoria specifica E-Plus' , $this->plugin_name);
  	 	return $columns;
	}

	/**
	* Display values in custom columns
	*
	* @param array $columns
	* @param int $product_id
	* @return string The value to be displayed
	*/
	function adv_pc_custom_column( $column, $product_id ) {
		if ( $column == 'adv_dem_primary_category_column' ) {
			echo get_post_meta( $product_id, 'adv_dem_primary_category', true );
		}
		if ( $column == 'adv_dem_primary_category_column_mid' ) {
			echo get_post_meta( $product_id, 'adv_dem_primary_category_mid', true );
		}
		if ( $column == 'adv_dem_primary_category_column_top' ) {
			echo get_post_meta( $product_id, 'adv_dem_primary_category_top', true );
		}
	}


	/**
	 * Update objects on E-commerce Plus when saved on Woocommerce.
	 *
	 * @param int $post_id
	 * @param object $post_object
	 * @return void
	 */
	public function adv_update_woocommerce_post($post_id, $post_object = null){
		$postType =  is_null($post_object) ? 'product_variation' : get_post_type($post_object);
		$considerProductVariations = ($postType == "product") ? true : false;
		switch ($postType) {
			case 'product':
				if ( isset( $_POST[ 'adv_dem_primary_category' ] ) ) {
					$primary_category = sanitize_text_field( wp_unslash($_POST[ 'adv_dem_primary_category' ]) );
					update_post_meta( $post_id, 'adv_dem_primary_category', $primary_category );
				}
				if ( isset( $_POST[ 'adv_dem_primary_category_mid' ] ) ) {
					$primary_category_mid = sanitize_text_field( wp_unslash($_POST[ 'adv_dem_primary_category_mid' ]) );
					update_post_meta( $post_id, 'adv_dem_primary_category_mid', $primary_category_mid );
				}
				if ( isset( $_POST[ 'adv_dem_primary_category_top' ] ) ) {
					$primary_category_top = sanitize_text_field( wp_unslash($_POST[ 'adv_dem_primary_category_top' ]) );
					update_post_meta( $post_id, 'adv_dem_primary_category_top', $primary_category_top );
				}
			case 'product_variation':
				$ecommercePlus = new Adv_dem_EcommercePlus();
				$ecommercePlus->adv_ecplus_product_create_or_update($post_id, $considerProductVariations);
				break;

			case 'shop_order':
				$ecommercePlus = new Adv_dem_EcommercePlus();
				$ecommercePlus->adv_ecplus_order_create_or_update($post_id);
			break;

			default:
				# code...
				break;
		}
		// $aaa = get_post_type( $post_object );
		return true;
	}

	/**
	 * Hook for cart management
	 *
	 * @param array $fragments
	 * @return void
	 */
	public function adv_header_add_to_cart_fragment( $fragments ) {
		global $woocommerce;
		ob_start();
		$this->woocommerce_cart_link();
		$fragments['a.cart-button'] = ob_get_clean();
		$ecommercePlus = new Adv_dem_EcommercePlus();
		$ecommercePlus->adv_ecplus_manage_cart();
		return $fragments;
	}

	/**
	 * Utility function for adv_header_add_to_cart_fragment
	 *
	 * @return void
	 */
    private function woocommerce_cart_link() {
        global $woocommerce;
        ?>
        <a href="<?php echo esc_url($woocommerce->cart->get_cart_url()); ?>" title="<?php echo sprintf(_n('%d item', '%d items', esc_attr($woocommerce->cart->cart_contents_count), 'woothemes'), esc_attr($woocommerce->cart->cart_contents_count));?> <?php esc_attr_e('in your shopping cart', 'woothemes'); ?>"
            class="cart-button ">
            <span class="label"><?php esc_attr_e('My Basket:', 'woothemes'); ?></span>
                <?php echo esc_html($woocommerce->cart->get_cart_total());  ?>
                <span class="items"><?php echo sprintf(_n('%d item', '%d items', esc_html($woocommerce->cart->cart_contents_count), 'woothemes'), esc_html($woocommerce->cart->cart_contents_count)); ?></span>
		</a>
		<?php
	}

	public function ajax_dump_configuration () {
		global $wpdb;
		$outputArray = array();
		$batchTableName = $wpdb->prefix . "adv_dem_batches";
		$$productTableName = $wpdb->prefix . "adv_dem_product_attributes";
		//$outputArray ["config"]["api_endpoint"] = API_ENDPOINT;
		$outputArray ["config"]["company"] = ADV_DEM_COMPANY;
		$outputArray ["config"]["companyLogo"] = ADV_DEM_COMPANY_LOGO;
		$outputArray ["config"]["companyIcon"] = ADV_DEM_COMPANY_ICON;
		$outputArray ["options"]["generic"] = get_option( $this->plugin_name);
		$outputArray ["options"]["generic"]["apikey"] = md5($outputArray ["options"]["generic"]["apikey"]);
		$outputArray ["options"]["generic"]["entrypoint"] = md5($outputArray ["options"]["generic"]["entrypoint"]);
		$outputArray ["options"]["syncrhronization"] = get_option( $this->plugin_name . 'sincronizza_utenti');
		$outputArray["woocommerce"]["status"] = (class_exists('Woocommerce')) ? "active" : "not active";
		$outputArray ["woocommerce"]["attributes"] = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $productTableName) , ARRAY_A);
		$outputArray ["woocommerce"]["batches"] = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $batchTableName), ARRAY_A);
		// If the file is NOT requested via AJAX, force-download
		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUESTED_WITH']))) != 'xmlhttprequest') {
			$filename = !empty($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])):'UNKNOW';
			header("Content-type: application/json; charset=utf-8");
			header('Content-disposition: attachment;filename=' . $filename .'-'. time() .'.json');
			$fp = fopen("php://output", "w");
			fwrite ($fp, json_encode($outputArray, JSON_UNESCAPED_UNICODE));
			fclose($fp);
			die();
		} else {
			echo json_encode($outputArray);
			wp_die();
		}
	}

	public function ajax_export_product_csv () {
		global $wpdb;
		$outputArray = array();
		$full_product_list = array(array("ID", "SKU", "PRODUCT_NAME", "PRODUCT_CATEGORIES(NAME)","E_COMMERCE_PLUS_GENERAL_CATEGORY(NAME)","E_COMMERCE_PLUS_MEDIUM_CATEGORY(NAME)","E_COMMERCE_PLUS_SPECIFIC_CATEGORY(NAME)"));
		$loop = new WP_Query( array( 'post_type' => array('product'), 'posts_per_page' => -1 ) );
		while ( $loop->have_posts() ) : $loop->the_post();
			$theid = get_the_ID();
			$product_sku = get_post_meta($theid, '_sku', true );
			$product = new WC_Product($theid);
			$thetitle = get_the_title();
			$primary_category_top = get_post_meta($theid , 'adv_dem_primary_category_top', true );
			$primary_category_mid = get_post_meta($theid , 'adv_dem_primary_category_mid', true );
			$primary_category = get_post_meta($theid , 'adv_dem_primary_category', true );
			$term =  get_the_terms( $theid, 'product_cat' );
			$categories = "";
			if($term){
				$numItems = count($term);
				$i = 0;
				foreach( $term as $singleTerm ) {
					$separator = (++$i === $numItems)? '' : ', ';
					$categories .= $singleTerm->name.$separator;
				}
			}else{
				$categories .= '';
			}
			$singleRow = array($theid,$product_sku, $thetitle, $categories, $primary_category_top, $primary_category_mid, $primary_category);
			$full_product_list[] = $singleRow;
		endwhile; wp_reset_query();
		$filename = "e_commerce_plus_categories.csv";
		$delimiter=",";
		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUESTED_WITH']))) != 'xmlhttprequest') {
			ob_start();
			// tell the browser it's going to be a csv file
			header('Content-Type: text/csv; charset=utf-8');
			// tell the browser we want to save it instead of displaying it
			header('Content-Disposition: attachment; filename='.$filename.'');

			// open raw memory as file so no temp files needed, you might run out of memory though
			$f = fopen('php://output', 'w');
			// loop over the input array
			foreach ($full_product_list as $line) {
				// generate csv lines from the inner arrays
				fputcsv($f, $line, ",");
			}
			fclose($f);
			die();
		} else {
			echo json_encode($full_product_list);
			wp_die();
		}
	}

	// public function needProductAlign($reinit = false){
	// 	global $wpdb;
	// 	$loop = new WP_Query( array( 'post_type' => array('product', 'product_variation'), 'posts_per_page' => -1 ) );
	// 	$products_need_align = array();
	// 	while ( $loop->have_posts() ) : $loop->the_post();
	// 		$theid = get_the_ID();
	// 		$product = new WC_Product($theid);
	// 		if($reinit){
	// 			// add_user_meta aggiunge un meta value ad un post wordpress
	// 			add_post_meta(  $operation_id, 'adv_dem_product_align', false , 'true' );
	// 			// update_user_meta aggiorna un meta value di un post wordpress
	// 			update_post_meta( $operation_id, 'adv_dem_product_align', "" );
	// 			$products_need_align[] = $theid;
	// 		}else{
	// 			$singleProductAlign = get_post_meta($theid , 'adv_dem_product_align', true );
	// 			if(!$singleProductAlign) {
	// 				$products_need_align[] = $theid;
	// 			}
	// 		}
	// 	endwhile; wp_reset_query();
	// 	if(count( $products_need_align ) > 0 ){
	// 		return	$products_need_align;
	// 	} else{
	// 		return false;
	// 	}
	// }
}
