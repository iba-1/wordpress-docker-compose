<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.4marketing.it
 * @since      1.0.0
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/public
 * @author     4marketing.it <sviluppo@4marketing.it>
 */
class Adv_dem_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/adv_dem-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
		$options = get_option($this->plugin_name);
		$entrypoint = isset( $options['entrypoint'] ) ? $options['entrypoint'] : "" ;
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/adv_dem-public.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( md5( $entrypoint . '/c4rt/js/ect.min.js'), $entrypoint . '/c4rt/js/ect.min.js');
		wp_localize_script($this->plugin_name, 'adv_ajax_object', array( 'ajaxurl' => admin_url('admin-ajax.php') ));
	}

	/**
	* Aggiunge al processo di checkout una checkbox per il consenso alla ricezione delle comunicazioni personali (regola la modalità di iscrizione alla lista)
	*
	*/
	public function adv_custom_checkbox_iscrizione() {
		$optionsGeneric = get_option($this->plugin_name);
		$apikey = $optionsGeneric['apikey'];
		$entrypoint = $optionsGeneric['entrypoint'];
		$recipient_id = $optionsGeneric['syncroRecipientId'];

		$options = get_option($this->plugin_name . "sincronizza_utenti");
		if (is_user_logged_in()) {
			$currentUserId = get_current_user_id();
			$currentUserEmail = get_user_meta( $currentUserId, 'billing_email', true );
			$api4m = new Adv_dem_InterfaceAPI($apikey, $entrypoint);
			$consoleUser = $api4m->getContactByEmail($recipient_id, array('email_address' => $currentUserEmail));
			if ($api4m->getRequestSuccessful()) {
				$consoleStatus = $consoleUser["data"]["subscription"]["status"];
			}else {
				$consoleStatus = "Opt-In Pending";
			}
		} else {
			$consoleStatus = "Opt-In Pending";
		}
		$checkboxString =  '<p class="form-row adv-woocommerce-opt-in"><label for="adv_wc_opt_in"><input type="checkbox" name="adv_wc_opt_in" id="adv_wc_opt_in" value="1" ' . (($consoleStatus == "Subscribed") ? "checked" : "")  . ' />' . $options['syncromessage'] . '</label></p>';
		echo apply_filters( 'ss_wc_mailchimp_opt_in_checkbox', $checkboxString);
	}
	/**
	* Si occupa del salvataggio del valore della checkbox dei meta dell'ordine
	*
	*/
	public function adv_maybe_save_checkout_fields($order_id ) {
		$opt_in = isset( $_POST[ 'adv_wc_opt_in'] ) ? '1' : '0';
		update_post_meta( $order_id, 'adv_wc_opt_in', $opt_in );
	}

	/**
	* Registra nel sistema il nuovo widget
	*
	*/
	public function register_widgets() {
		register_widget( 'Adv_dem_Widget' );
	}

	// FUNZIONI CHIAMATE VIA AJAX    -----------    INIZIO

	/**
	* Effettua un test sull'apikey via Ajax
	*
	* @access public
	* @return array
	*/
	public function ajax_widget_subscribe() {
		try {
			if ( empty( $_POST['data'] ) ) {
				throw new Exception( 'form empty');
			}

			$options = get_option($this->plugin_name);

			$apikey = $options['apikey'];
			$entrypoint = $options['entrypoint'];
			$api4m = new Adv_dem_InterfaceAPI($apikey, $entrypoint);
			$recipient_id = sanitize_text_field(wp_unslash($_POST['data']['recipientId']));
			$emailAddress = sanitize_email(wp_unslash($_POST['data']['email']));
			$args= array(
				"email_address" => $emailAddress,
				"subscription" => array( //optional
					"ip" => !empty($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'UNKNOW', //if subscription exists is required
					"date" => date("Y-m-d H:i:s"), //optional
					"status" => "Subscribed"
				),
				"triggers" => array(
					"automation" => true,
					"behaviors" => true
				),
				"update_if_duplicate" => false
			);
			$result = $api4m->subscribeContact( $recipient_id, $args );

		}
		catch ( Exception $e ) {
			// return $this->toJSON( array( 'error' => $e->getMessage() ) );
		}
		echo json_encode($result);
		wp_die();
	} //end function ajax_check_apikey

	// FUNZIONI CHIAMATE VIA AJAX    -----------    FINE

	/**
	* Modifica / creazione di un indirizzo utente woocommerce
	*
	* @access public
	* @return array
	*/
	public function adv_woocommerce_customer_save_address($user_id) {
		// Leggo le impostazioni di sincronizzazione
		$optionsSincronizzaUtenti = get_option($this->plugin_name . "sincronizza_utenti");
		$forceSubcribe = isset($optionsSincronizzaUtenti["forceSubcribe"]) ?  boolval($optionsSincronizzaUtenti["forceSubcribe"]) : false;

		$synchroTool = new Adv_dem_SyncroTools();
		$result = $synchroTool->adv_subscribe_update_user($user_id, $forceSubcribe);
		unset($synchroTool);
	}

	/**
	* Complemtamento di un ordine woocommerce
	*
	* @access public
	* @return array
	*/
	public function adv_woocommerce_checkout_order_processed($order_id) {
		$optionsSincronizzaUtenti = get_option($this->plugin_name . "sincronizza_utenti");
		$isSyncActive = $optionsSincronizzaUtenti["syncroAuto"];

		if($isSyncActive){
			// Leggo le impostazioni di sincronizzazione
			$forceSubcribe = isset($optionsSincronizzaUtenti["forceSubcribe"]) ?  boolval($optionsSincronizzaUtenti["forceSubcribe"]) : false; //valore in backoffice, true = Subscribed, false = optin pending
			// Recupero i dati dell'ordine appena effettuato
			$thisOrder = wc_get_order($order_id);

			if ($forceSubcribe) {
				$checkboxSubcribeValue = true;
			} else {
				$checkboxSubcribeValue = false;
				$forcedStatus = boolval(get_post_meta( $order_id, 'adv_wc_opt_in', true )) ? "Subscribed" : "Opt-In Pending"; //valore del flag
			}

			$synchroTool = new Adv_dem_SyncroTools();
			if ($thisOrder->user_id) {
				$result = $synchroTool->adv_subscribe_update_user($thisOrder->user_id, $checkboxSubcribeValue, $forcedStatus);
			} else {
				$result = $synchroTool->adv_subscribe_update_anonymous_checkout($order_id, $checkboxSubcribeValue, $forcedStatus);
			}
			unset($synchroTool);
		}
	}

}
