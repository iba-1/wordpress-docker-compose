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
 * This class defines methods for API2.0 interface.
 *
 * @since      1.0.0
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 * @author     4marketing.it <sviluppo@4marketing.it>
 */

class Adv_dem_InterfaceAPI{

    private $api_key;
    private $api_endpoint;
    private $token = "";
    private $token_expired_data = null;
    private $verify_ssl = false;
    private $operationTimeout = 10;
    private $total_time;
    /*control parameters*/
    private $request_successful = false;
    private $last_error         = '';
    private $last_response      = array();
    private $last_request       = array();

    public function __construct($api_key, $api_endpoint, $autologin = true,  $operationTimeout = 10 ) {
        $this->api_endpoint = $api_endpoint;
        $this->api_key      = $api_key;
        $this->operationTimeout = $operationTimeout;
        if ($autologin) $this->login();
    }

    public function getRequestSuccessful () {
        return $this->request_successful;
    }

    public function getLastError () {
        return $this->last_error;
    }

    private function call ( $http_verb, $method, $args = array(), $timeout = self::TIMEOUT){
        $url = $this->api_endpoint . '/' . $method;
        $callArgs = array(
            'headers'=> array(
                'Content-Type'  => 'application/json; charset=utf-8',
                'Accept: application/json',
                'User-Agent'    => !empty($_SERVER['SERVER_NAME'])?sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])):'UNKNOW'
            ),
            'method' => strtoupper($http_verb),
            'timeout'       => $timeout,
            'sslverify'     => $this->verify_ssl,
        );
        if ( $http_verb === 'get' ){
            $query = http_build_query($args, '', '&');
            $url = $url . '?' . $query;
        }else{
            $callArgs['body'] = json_encode($args);
        }
        if ( $method != "authenticate") { $callArgs['headers']['Authorization'] = 'Bearer ' . $this->token; }

        $this->last_error = '';
        $this->request_successful = false;
        $res = wp_remote_request( $url, $callArgs );
        $body = wp_remote_retrieve_body( $res );
        $response_message = wp_remote_retrieve_response_message( $res );
        if(is_wp_error($res) && empty($response_message)){
            $this->last_error=$response_message;
            $this->request_successful=$response_message;
            return false;
        }

        $this->determineSuccess($res);
        $formattedResponse = $this->formatResponse($res);
        $value = $this->getRequestSuccessful();

        if(!$this->getRequestSuccessful() ){
            error_log("ADV4DEM ERROR LOG >>>>>>");
            error_log("Req: ".json_encode(array(
                'method'  => $http_verb,
                'path'    => $method,
                'url'     => $url,
                'body'    => $body,
                'timeout' => $timeout,
            )));
            error_log("Res: ".json_encode($formattedResponse));
            error_log("<<<<<<");
        }
        return $formattedResponse;
    }

    private function determineSuccess($response) {
        $status = wp_remote_retrieve_response_code( $response );
        if ($status >= 200 && $status <= 299) {
            $this->request_successful = true;
            return true;
        }
        $this->last_error = $status;
        return false;
    }
    private function formatResponse($response){

        if (!empty($response['body'])) {
            $jsonResponse = json_decode($response['body'], true);
            if (!$this->request_successful) $jsonResponse['status'] = $this->last_error;
            return $jsonResponse;
        }
        return false;
    }

    public function login() {
        $args = array("APIKey" => $this->api_key);
        $method = "authenticate";
        $responseArray = $this->call('post',$method,$args,$this->operationTimeout);
        if($this->request_successful) {
            $this->token = $responseArray['token'];
            $this->token_expired_data = date("Y-m-d H:i:s", time() + $responseArray['expired_in']);
        }
        return $responseArray;
    }

    private function controlToken () {
        if( strtotime(date("Y-m-d H:i:s")) > strtotime($this->token_expired_data) ) {
            $args = array();
            $method = "refresh_token";
            $responseArray = $this->call('get',$method,$args,$this->operationTimeout);
            if($this->request_successful) {
                $this->token = $responseArray['refreshedToken'];
                $this->token_expired_data = date("Y-m-d H:i:s", time() + $responseArray['expired_in']);
            }
        }
    }

    public function userInfo() {
        $this->controlToken();
        $args = array();
        $method = "me";
        return $this->call('get',$method,$args,$this->operationTimeout);
    }


    public function getRecipients() {
        $this->controlToken();
        $args = array();
        $method = "recipients";
        return $this->call('get',$method,$args,$this->operationTimeout);
    }

    public function getRecipientInformation( $recipient_id ) {
        $this->controlToken();
        $args = array();
        $method = "recipients/".$recipient_id ;
        return $this->call('get',$method,$args,$this->operationTimeout);
    }

    public function getRecipientCustomFields($recipient_id) {
        $this->controlToken();
        $args = array();
        $method = "recipients/".$recipient_id."/custom_fields/";
        return $this->call('get',$method,$args,$this->operationTimeout);
    }

    public function createRecipient( $args ) {
        $this->controlToken();
        $method = "recipients";
        return $this->call('post',$method,$args,$this->operationTimeout);
    }

    public function createNewCustomField( $args ) {
        $this->controlToken();
        $method = "custom_fields/";
        return $this->call('post',$method,$args,$this->operationTimeout);
    }

    public function subscribeContact($recipient_id ,  $args ) {
        $this->controlToken();
        $method = "recipients/".$recipient_id."/subscribe";
        return $this->call('post',$method,$args,$this->operationTimeout);
    }

    public function getContactByEmail( $recipient_id, $args ) {
        $this->controlToken();
        $method = "recipients/".$recipient_id."/contacts/search/";

        return $this->call('post',$method,$args,$this->operationTimeout);
    }

    public function getContact( $recipient_id, $subscriber_id ) {
        $this->controlToken();
        $args= array();
        $method = "recipients/".$recipient_id."/contacts/".$subscriber_id;

        return $this->call('get',$method,$args,$this->operationTimeout);
    }

    public function updateContact( $recipient_id, $subscriber_id, $args) {
        $this->controlToken();
        $method =  "recipients/".$recipient_id."/contacts/".$subscriber_id."/update";
        return $this->call( 'put',$method,$args,$this->operationTimeout );
    }

    public function unsubscribeContact( $recipient_id, $args ) {
        $this->controlToken();
        $method = "/recipients/".$recipient_id."/unsubscribe";
        return $this->call( 'post',$method,$args,$this->operationTimeout );
    }

    // batch operations caller
    public function runBatchOperations( $args ) {
        $this->controlToken();
        $method = "batches/";
        return $this->call( 'post',$method,$args, 120 );
    }

    public function createImport( $args ) {
        $this->controlToken();
        $method = "imports/";
        return $this->call( 'post',$method,$args, 120 );
    }

    // batch operations caller
    public function startImport( $importId ) {
        $this->controlToken();
        $args = array();
        $method = "imports/".$importId."/start";
        return $this->call( 'get',$method,$args, 120 );
    }

    /*e-commerce plus method*/
    /*STORE METHODS*/

    public function createNewStore( $args ) {
        $this->controlToken();
        $method = "stores/";
        return $this->call( 'post',$method,$args,$this->operationTimeout );
    }

    public function getStores() {
        $this->controlToken();
        $args = array();
        $method = "stores";
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function deleteStoreAccount( $store_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id;
        return $this->call( 'delete',$method,$args,$this->operationTimeout );
    }

    public function getStoreInformation( $store_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id;
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function updateStore( $store_id, $args ) {
        $this->controlToken();
        $method = "stores/".$store_id ;
        return $this->call( 'put',$method,$args,$this->operationTimeout );
    }
    /*END STORE METHODS*/

    /* ORDER METHODS */
    public function updateCart( $store_id, $args, $cart_id ) {
        $this->controlToken();
        $method = "stores/".$store_id . "/carts/" .  $cart_id ;
        return $this->call( 'put',$method,$args,$this->operationTimeout );
    }

    public function dropCart($store_id, $cart_id){
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id . "/carts/" .  $cart_id ;
        return $this->call( 'delete',$method,$args,$this->operationTimeout );
    }
    /* END ORDER METHODS */

    /* ATTRIBUTE METHODS*/
    public function createAttributeInStore( $store_id, $args ) {
        $this->controlToken();
        $method = "stores/".$store_id."/attributes";
        return $this->call( 'post',$method,$args,$this->operationTimeout );
    }

    public function deleteAttributeFromStore( $store_id, $attribute_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/attributes/" . $attribute_id;
        return $this->call( 'delete',$method,$args,$this->operationTimeout );
    }


    /*PRODUCT METHODS*/
    public function getStoreProducts( $store_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/products/all";
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function createStoreProduct( $store_id, $args ) {
        $this->controlToken();
        $method = "stores/".$store_id."/products";
        return $this->call( 'post',$method,$args,$this->operationTimeout );
    }

    public function deleteStoreProduct( $store_id, $product_id, $variant_id = "" ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/products/?productId=".$product_id;
        if($variant_id != ""){ $method = $method."&variantId=".$variant_id; }
        return $this->call( 'delete',$method,$args,$this->operationTimeout );
    }

    public function getProductInformation( $store_id, $product_id, $variant_id = "" ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/products/?productId=".$product_id;
        if($variant_id != ""){ $method = $method."&variantId=".$variant_id; }
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function updateProductInformation( $store_id, $product_id, $args, $variant_id = "") {
        $this->controlToken();
        $method = "stores/".$store_id."/products/?productId=".$product_id;
        if($variant_id != ""){ $method = $method."&variantId=".$variant_id; }
        return $this->call( 'put',$method,$args,$this->operationTimeout );
    }


    /*END PRODUCTS METHODS*/

    /*PRODUCT ATTRIBUTES METHODS*/
    public function getAllProductAttributes( $store_id, $product_id, $variant_id = "" ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/products/attributes/all?productId=".$product_id;
        if($variant_id != ""){ $method = $method."&variantId=".$variant_id; }
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function createUpdateProductAttribute( $store_id, $product_id, $args, $variant_id = "" ) {
        $this->controlToken();
        $method = "stores/".$store_id."/products/attributes/all?productId=".$product_id;
        if($variant_id != ""){ $method = $method."&variantId=".$variant_id; }
        return $this->call( 'post',$method,$args,$this->operationTimeout );
    }

    public function deleteProductAttribute( $store_id, $product_id, $attribute_id, $variant_id = "" ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/products/attributes/".$attribute_id."all?productId=".$product_id;
        if($variant_id != ""){ $method = $method."&variantId=".$variant_id; }
        return $this->call( 'delete',$method,$args,$this->operationTimeout );
    }

    public function getProductAttribute( $store_id, $product_id, $attribute_id, $variant_id = "" ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/products/attributes/".$attribute_id."all?productId=".$product_id;
        if($variant_id != ""){ $method = $method."&variantId=".$variant_id; }
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    /*END PRODUCT ATTRIBUTES*/

    /*CART METHODS*/
    public function getAllCartProducts( $store_id, $cart_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/carts/".$cart_id."/products/all";
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function createCartProduct( $store_id, $cart_id, $args ) {
        $this->controlToken();
        $method = "stores/".$store_id."/carts/".$cart_id."/products";
        return $this->call( 'post',$method,$args,$this->operationTimeout );
    }

    public function deleteCartProduct( $store_id, $cart_id, $line_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/carts/".$cart_id."/products/".$line_id;
        return $this->call( 'delete',$method,$args,$this->operationTimeout );
    }

    public function getCartProduct( $store_id, $cart_id, $line_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/carts/".$cart_id."/products/".$line_id;
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function updateCartProduct( $store_id, $cart_id, $line_id , $args ) {
        $this->controlToken();
        $method = "stores/".$store_id."/carts/".$cart_id."/products/".$line_id;
        return $this->call( 'put',$method,$args,$this->operationTimeout );
    }

    public function getStoreCarts( $store_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/carts";
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function createStoreCart( $store_id, $args) {
        $this->controlToken();
        $method = "stores/".$store_id."/carts";
        return $this->call( 'post',$method,$args,$this->operationTimeout );
    }

    public function deleteStoreCart( $store_id, $cart_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/carts/".$cart_id ;
        return $this->call( 'delete',$method,$args,$this->operationTimeout );
    }

    public function getStoreCartInformation( $store_id, $cart_id ) {
        $this->controlToken();
        $args = array();
        $method = "stores/".$store_id."/carts/".$cart_id ;
        return $this->call( 'get',$method,$args,$this->operationTimeout );
    }

    public function updateStoreCart( $store_id, $cart_id, $args ) {
        $this->controlToken();
        $method = "stores/".$store_id."/carts/".$cart_id ;
        return $this->call( 'put',$method,$args,$this->operationTimeout );
    }
    /*END CART METHODS*/


}
?>
