<?php


/**
* Fired during plugin activation
 *
 * @link       www.4marketing.it
 * @since      1.1.0
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 */


/**
* Fired during plugin activation.
 *
 * This class defines methods for API2.0 interface.
 *
 * @since      1.1.0
 * @package    Adv_dem
 * @subpackage Adv_dem/includes
 * @author     4marketing.it
 */

class Adv_dem_cf7_InterfaceAPI{
	private $api_key;
	private $api_endpoint = ADV_DEM_CF7_API_ENDPOINT;
	private $token = "";
	private $token_expired_data = null;
	private $verify_ssl = false;
	private $operationTimeout = 10;
	
	
	/*control parameters*/
	private $request_successful = false;
	private $last_error         = '';
	private $last_response      = array();
	private $last_request       = array();
	
	public function __construct($api_key, $autologin = true, $api_endpoint = ADV_DEM_CF7_API_ENDPOINT, $operationTimeout = 10 ) {
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
	
	private function call ( $http_verb, $method, $args = array(), $timeout = 10){
		
		if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
			throw new \Exception("cURL support is required, but can't be found.");
		}
		
		$url = $this->api_endpoint . '/' . $method;
		$this->last_error = '';
		$this->request_successful = false;
		$response = array(
		            'headers'     => null, // 		array of details from curl_getinfo()
		            'httpHeaders' => null, // 		array of HTTP headers
		            'body'        => null // 		content of the response
		        );
		$this->last_response = $response;
		$this->last_request = array(
		            'method'  => $http_verb,
		            'path'    => $method,
		            'url'     => $url,
		            'body'    => '',
		            'timeout' => $timeout,
		        );
		
		$curlHttpHeaderArray = array(
		            'Accept: application/json',
		            'Content-Type: application/json'
		        );
		if ($method != "authenticate") {
			$curlHttpHeaderArray[] = 'Authorization: Bearer ' . $this->token;
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHttpHeaderArray );
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		switch ($http_verb) {
			case 'post':
			                curl_setopt($ch, CURLOPT_POST, true);
			$this->attachRequestPayload($ch, $args);
			break;
			case 'get':
			                $query = http_build_query($args, '', '&');
			curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
			break;
			case 'delete':
			                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
			case 'patch':
			                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
			$this->attachRequestPayload($ch, $args);
			break;
			case 'put':
			                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			$this->attachRequestPayload($ch, $args);
			break;
		}
		
		$responseContent = curl_exec($ch);
		
		$response['headers'] = curl_getinfo($ch);
		if ($responseContent === false) {
			$this->last_error = curl_error($ch);
		}
		else {
			$headerSize = $response['headers']['header_size'];
			
			$response['httpHeaders'] = $this->getHeadersAsArray(substr($responseContent, 0, $headerSize));
			$response['body'] = substr($responseContent, $headerSize);
			if (isset($response['headers']['request_header'])) {
				$this->last_request['headers'] = $response['headers']['request_header'];
			}
		}
		curl_close($ch);
		$this->determineSuccess($response, $timeout);
		$formattedResponse = $this->formatResponse($response);
		return $formattedResponse;
	}
	
	
	/**
	* Encode the data and attach it to the request
	     * @param   resource $ch cURL session handle, used by reference
	     * @param   array $data Assoc array of data to attach
	     */
	    private function attachRequestPayload(&$ch, $data) {
		$encoded = json_encode($data);
		$this->last_request['body'] = $encoded;
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
	}
	
	
	/**
	* Get the HTTP headers as an array of header-name => header-value pairs.
	     * 
	     * The "Link" header is parsed into an associative array based on the
	     * rel names it contains. The original value is available under
	     * the "_raw" key.
	     * 
	     * @param string $headersAsString
	     * @return array
	     */
	    private function getHeadersAsArray($headersAsString) {
		$headers = array();
		foreach (explode("\r\n", $headersAsString) as $i => $line) {
			if ($i === 0) {
				// 				HTTP code
				                continue;
			}
			
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			list($key, $value) = explode(': ', $line);
			
			if ($key == 'Link') {
				$value = array_merge(
				                    array('_raw' => $value),
				                    $this->getLinkHeaderAsArray($value)
				                );
			}
			$headers[$key] = $value;
		}
		return $headers;
	}	
	
	/**
	* Extract all rel => URL pairs from the provided Link header value
	     * @param string $linkHeaderAsString
	     * @return array
	     */
	    private function getLinkHeaderAsArray($linkHeaderAsString) {
		$urls = array();
		
		if (preg_match_all('/<(.*?)>\s*;\s*rel="(.*?)"\s*/', $linkHeaderAsString, $matches)) {
			foreach ($matches[2] as $i => $relName) {
				$urls[$relName] = $matches[1][$i];
			}
		}
		
		return $urls;
	}
	
	
	/**
	* Decode the response and format any error messages for debugging
	     * @param array $response The response from the curl request
	     * @return array|false    The JSON decoded into an array
	     */
	    private function formatResponse($response)
	    {
		$this->last_response = $response;
		if (!empty($response['body'])) {
			$jsonResponse = json_decode($response['body'], true);
			if (!$this->request_successful) $jsonResponse['status'] = $this->last_error;
			return $jsonResponse;
		}
		return false;
	}
	
	
	/**
	* Check if the response was successful or a failure. If it failed, store the error.
	     * @param array $response The response from the curl request
	     * @param int $timeout The timeout supplied to the curl request.
	     * @return bool     If the request was successful
	     */
	    private function determineSuccess($response, $timeout) {
		$status = $this->findHTTPStatus($response);
		if ($status >= 200 && $status <= 299) {
			$this->request_successful = true;
			return true;
		}
		
		if( $timeout > 0 && $response['headers'] && $response['headers']['total_time'] >= $timeout ) {
			$this->last_error = sprintf('Request timed out after %f seconds.', $response['headers']['total_time'] );
			return false;
		}
		
		$this->last_error = $status;
		return false;
	}
	
	/**
	* Find the HTTP status code from the headers or API response body
	     * @param array $response The response from the curl request
	     * @return int  HTTP status code
	     */
	    private function findHTTPStatus($response) {
		if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
			return (int) $response['headers']['http_code'];
		}
		return 418;
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
		$method = "recipients/".$recipient_id. "/custom_fields/?limit=100&";
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
	
	// 	batch operations caller
	    public function runBatchOperations( $args ) {
		$this->controlToken();
		$method = "batches/";
		return $this->call( 'post',$method,$args, 120 );
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
		if($variant_id != ""){
			$method = $method."&variantId=".$variant_id;
		}
		return $this->call( 'delete',$method,$args,$this->operationTimeout );
	}
	
	public function getProductInformation( $store_id, $product_id, $variant_id = "" ) {
		$this->controlToken();
		$args = array();
		$method = "stores/".$store_id."/products/?productId=".$product_id;
		if($variant_id != ""){
			$method = $method."&variantId=".$variant_id;
		}
		return $this->call( 'get',$method,$args,$this->operationTimeout );
	}
	
	public function updateProductInformation( $store_id, $product_id, $args, $variant_id = "") {
		$this->controlToken();
		$method = "stores/".$store_id."/products/?productId=".$product_id;
		if($variant_id != ""){
			$method = $method."&variantId=".$variant_id;
		}
		return $this->call( 'put',$method,$args,$this->operationTimeout );
	}
	
	
	
	/*END PRODUCTS METHODS*/
	
	
	/*PRODUCT ATTRIBUTES METHODS*/
	public function getAllProductAttributes( $store_id, $product_id, $variant_id = "" ) {
		$this->controlToken();
		$args = array();
		$method = "stores/".$store_id."/products/attributes/all?productId=".$product_id;
		if($variant_id != ""){
			$method = $method."&variantId=".$variant_id;
		}
		return $this->call( 'get',$method,$args,$this->operationTimeout );
	}
	
	public function createUpdateProductAttribute( $store_id, $product_id, $args, $variant_id = "" ) {
		$this->controlToken();
		$method = "stores/".$store_id."/products/attributes/all?productId=".$product_id;
		if($variant_id != ""){
			$method = $method."&variantId=".$variant_id;
		}
		return $this->call( 'post',$method,$args,$this->operationTimeout );
	}
	
	public function deleteProductAttribute( $store_id, $product_id, $attribute_id, $variant_id = "" ) {
		$this->controlToken();
		$args = array();
		$method = "stores/".$store_id."/products/attributes/".$attribute_id."all?productId=".$product_id;
		if($variant_id != ""){
			$method = $method."&variantId=".$variant_id;
		}
		return $this->call( 'delete',$method,$args,$this->operationTimeout );
	}
	
	public function getProductAttribute( $store_id, $product_id, $attribute_id, $variant_id = "" ) {
		$this->controlToken();
		$args = array();
		$method = "stores/".$store_id."/products/attributes/".$attribute_id."all?productId=".$product_id;
		if($variant_id != ""){
			$method = $method."&variantId=".$variant_id;
		}
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