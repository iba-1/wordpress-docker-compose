<?php
class Adv_dem_SyncroTools {

    private $apikey;
    private $syncroRecipientId;
    private $api4m;
    private $loginSuccess;
    private $entrypoint;

    public function __construct() {
        $options = get_option('adv_dem');
        $this->apikey = $options['apikey'];
        $this->entrypoint = $options['entrypoint'];
        $this->syncroRecipientId = $options['syncroRecipientId'];
        $this->api4m = new Adv_dem_InterfaceAPI( $this->apikey, $this->entrypoint );
        $this->loginSuccess = $this->api4m->getRequestSuccessful();
	}

    /**
     * Update user email address (wordpress login) on change
     *
     * @param int $subscriber_id
     * @param string $new_email_address
     * @return void
     */
    public function adv_update_email_address($subscriber_id, $new_email_address){
        if (!$this->loginSuccess) return false;
        $options = get_option('adv_dem');
        $args= array( "email_address" => $new_email_address );
        $apiResult = $this->api4m->updateContact( $this->syncroRecipientId , $subscriber_id , $args );
        return true;
    }

	/**
     * Update user subscription
     *
     * @param int $user_id
     * @param boolean $forceSubcribe
     * @return void
     */
    public function adv_subscribe_update_user($user_id, $forceSubcribe = false, $forcedStatus = "") {

        if (!$this->loginSuccess) return false;
        $options = get_option('adv_dem');
        $wordpressUserDetails =  array_map( function( $a ){ return $a[0]; }, get_user_meta( $user_id ) );
        $wordpressUserInfo = get_userdata($user_id);
        $emailAddress = $wordpressUserInfo->user_email;
        $argsCustomFields = array();
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
        $args= array(
            "email_address" => $emailAddress,
            "subscription" => array( //optional
                "ip" => !empty($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'UNKNOW', //if subscription exists is required
                "date" => date("Y-m-d H:i:s"),
                "status"=> "Opt-In Pending"
            ),
            "triggers" => array(
                "automation" => true,
                "behaviors" => true
            ),
            "update_if_duplicate" => true,
            "custom_fields" =>  $argsCustomFields
        );

        $userInfo = $this->api4m->getContactByEmail($this->syncroRecipientId, $args);

        if ( $forceSubcribe || $forcedStatus == "Subscribed" || $userInfo['data']['subscription']['status'] == "Subscribed" ) {
            $args["subscription"]["status"] = "Subscribed";
        }

        $apiResult = $this->api4m->subscribeContact( $this->syncroRecipientId , $args );
        if ($this->api4m->getRequestSuccessful()) {
            add_user_meta(  $user_id, 'adv_dem_consoleId', $apiResult, 'true' );
            update_user_meta( $user_id, 'adv_dem_consoleId', $apiResult );
        } else {
            throw new Exception(esc_html__('Errore in fase di aggiornamento/iscrizione utente', 'adv_dem'));
        }
	}

    /**
     * Subscribe the user during anonymous checkout using billing informations
     *
     * @param int $order_id
     * @param boolean $forceSubcribe
     * @return void
     */
    public function adv_subscribe_update_anonymous_checkout($order_id, $forceSubcribe = false, $forceStatus = "") {
        if (!$this->loginSuccess) return false;
        $options = get_option('adv_dem');
        $orderDetails =  array_map( function( $a ){ return $a[0]; }, get_post_meta( $order_id ) );
        $emailAddress = $orderDetails['_billing_email'];
        $argsCustomFields = array();
        $argsCustomFields[] = array('id' => $options['first_name'], 'value' 	=> isset($orderDetails['_billing_first_name']) 	    ? $orderDetails['_billing_first_name'] : '');
        $argsCustomFields[] = array('id' => $options['last_name'], 'value' 		=> isset($orderDetails['_billing_last_name']) 	    ? $orderDetails['_billing_last_name'] : '');
        $argsCustomFields[] = array('id' => $options['company'], 'value' 		=> isset($orderDetails['_billing_company']) 		? $orderDetails['_billing_company'] : '');
        $argsCustomFields[] = array('id' => $options['address_1'], 'value' 		=> isset($orderDetails['_billing_address_1'])   	? $orderDetails['_billing_address_1'] : '');
        $argsCustomFields[] = array('id' => $options['address_2'], 'value' 		=> isset($orderDetails['_billing_address_2'])   	? $orderDetails['_billing_address_2'] : '');
        $argsCustomFields[] = array('id' => $options['city'], 'value'			=> isset($orderDetails['_billing_city']) 		    ? $orderDetails['_billing_city'] : '');
        $argsCustomFields[] = array('id' => $options['postcode'], 'value' 		=> isset($orderDetails['_billing_postcode']) 	    ? $orderDetails['_billing_postcode'] : '');
        $argsCustomFields[] = array('id' => $options['country'], 'value' 		=> isset($orderDetails['_billing_country']) 		? $orderDetails['_billing_country'] : '');
        $argsCustomFields[] = array('id' => $options['state'], 'value' 			=> isset($orderDetails['_billing_state']) 		    ? $orderDetails['_billing_state'] : '');
        $argsCustomFields[] = array('id' => $options['phone'], 'value' 			=> isset($orderDetails['_billing_phone']) 		    ? $orderDetails['_billing_phone'] : '');
        $argsCustomFields[] = array('id' => $options['billing_email'], 'value' 	=> isset($orderDetails['_billing_email']) 		    ? $orderDetails['_billing_email'] : '');
        $args= array(
            "email_address" => $emailAddress,
            "subscription" => array( //optional
                "ip" => !empty($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'UNKNOW', //if subscription exists is required
                "date" => date("Y-m-d H:i:s"), //optional
                "status"=> "Opt-In Pending"
            ),
            "triggers" => array(
                "automation" => true,
                "behaviors" => true
            ),
            "update_if_duplicate" => true,
            "custom_fields" =>  $argsCustomFields
        );
        $userInfo = $this->api4m->getContactByEmail($this->syncroRecipientId, $args);
        // Se forzo l'iscrizione aggiungo un subscribed
        if ( $forceSubcribe || $forceStatus == "Subscribed" || $userInfo['data']['subscription']['status'] == "Subscribed" ) {
            $args["subscription"]["status"] = "Subscribed";
        }

        $apiResult = $this->api4m->subscribeContact( $this->syncroRecipientId , $args );
        if (!$this->api4m->getRequestSuccessful()) {
            throw new Exception(esc_html__('Errore in fase di aggiornamento/iscrizione utente', 'adv_dem'));
        }

	}

}