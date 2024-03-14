<?php
/**
 * WPCF7 Filters and Actions
 */
add_action( 'wpcf7_after_save'			, 'wpcf7_adv_dem_save_advdem' );
add_filter( 'wpcf7_editor_panels'		, 'wpcf7_show_adv_dem_metabox' );
add_filter( 'wpcf7_form_response_output', 'wpcf7_adv_dem_author', 40, 4);
add_action( 'wpcf7_before_send_mail'	, 'wpcf7_adv_dem_subscribe' );
add_filter( 'wpcf7_form_class_attr'		, 'wpcf7_adv_dem_class_attr' );

/**
 * Custom Ajax actions for plugin
 */
add_action( 'wp_ajax_adv_dem_cf7_get_customfields'	, 'adv_dem_cf7_ajax_get_customfields' );
add_action( 'wp_ajax_adv_dem_cf7_add_customfields'	, 'adv_dem_cf7_ajax_add_customfields' );
add_action( 'wp_ajax_adv_dem_cf7_add_list'			, 'adv_dem_cf7_ajax_add_list' );

/**
 * 4Dem - wpcf7_afeter_save custom function
 */
function wpcf7_adv_dem_save_advdem($args) {
	if (!empty($_POST)){
		if( $_POST['wpcf7-adv-dem']['list'] == "newList"){
			$_POST['wpcf7-adv-dem']['list'] == "";
		}
		update_option( 'cf7_adv_dem_'.$args->id(), $_POST['wpcf7-adv-dem'] );
	}
}

/**
 *4Dem - wpcf7_editor_panels custom function
 */
function wpcf7_show_adv_dem_metabox ( $panels ) {
	$new_page = array(
	    'adv-dem-Extension' => array(
	      'title' => esc_html__( '4Dem.it Email Marketing' , ADV_DEM_CF7_TEXTDOMAIN ),
	      'callback' => 'wpcf7_adv_dem_add_advdem'
	    )
	  );
	$panels = array_merge($panels, $new_page);
	return $panels;
}

/**
 *4Dem - wpcf7_show_adv_dem_metabox callback
 */
function wpcf7_adv_dem_add_advdem($args) {
	$cf7_adv_dem_defaults = array();
	$cf7_adv_dem = get_option( 'cf7_adv_dem_'.$args->id(), $cf7_adv_dem_defaults );
	$host = esc_url_raw( $_SERVER['HTTP_HOST'] );
	$url = $_SERVER['REQUEST_URI'];
	$urlactual = $url;
	include ADV_DEM_CF7_PLUGIN_DIR . '/lib/adv-dem-cf7-view.php';
}

/**
 *4Dem - wpcf7_form_response_output custom function
 */
function wpcf7_adv_dem_author( $adv_dem_supps, $class, $content, $args ) {
	$cf7_adv_dem_defaults = array();
	$cf7_adv_dem = get_option( 'cf7_adv_dem_'.$args->id(), $cf7_adv_dem_defaults );
	$cfsupp = ( isset( $cf7_adv_dem['cf-supp'] ) ) ? $cf7_adv_dem['cf-supp'] : 0;
	
	if ( 1 == $cfsupp ) {
		$adv_dem_supps .= adv_dem_cf7_referer();
		$adv_dem_supps .= adv_dem_cf7_author();
	}
	else {
		$adv_dem_supps .= adv_dem_cf7_referer();
		$adv_dem_supps .= '<!-- 4Dem extension -->';
	}
	return $adv_dem_supps;
}

/**
 *4Dem - custom plugin function
 */
function cf7_adv_dem_tag_replace( $pattern, $subject, $posted_data, $html = false ) {
	if( preg_match($pattern,$subject,$matches) > 0) {
		if ( isset( $posted_data[$matches[1]] ) ) {
			$submitted = $posted_data[$matches[1]];
			if ( is_array( $submitted ) )
			        $replaced = join( ', ', $submitted );
			else
			        $replaced = $submitted;
			if ( $html ) {
				$replaced = strip_tags( $replaced );
				$replaced = wptexturize( $replaced );
			}
			
			$replaced = apply_filters( 'wpcf7_mail_tag_replaced', $replaced, $submitted );
			return stripslashes( $replaced );
		}
		
		if ( $special = apply_filters( 'wpcf7_special_mail_tags', '', $matches[1] ) )
		      return $special;
		return $matches[0];
	}
	return $subject;	
}

/**
 *4Dem - wpcf7_before_send_mail custom function
 */
function wpcf7_adv_dem_subscribe($obj) {
	$cf7_adv_dem = get_option( 'cf7_adv_dem_'.$obj->id() );
	$submission = WPCF7_Submission::get_instance();

	if( $cf7_adv_dem ) {
		$subscribe = false;
		$regex = '/\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\]/';
		$callback = array( &$obj, 'cf7_adv_dem_callback' );
		$email = cf7_adv_dem_tag_replace( $regex, $cf7_adv_dem['email'], $submission->get_posted_data() );
		$list = (isset($cf7_adv_dem['list']))? $cf7_adv_dem['list']: "";
		$subscription = array(
		                    "ip" => $_SERVER['REMOTE_ADDR'],
		                    "date" => date("Y-m-d H:i:s") 
		                  );
		
		if( isset($cf7_adv_dem['accept']) && strlen($cf7_adv_dem['accept']) != 0 ){
			$accept = cf7_adv_dem_tag_replace( $regex, $cf7_adv_dem['accept'], $submission->get_posted_data() );
			if($accept != $cf7_adv_dem['accept']){
				if(strlen($accept) > 0){
					$subscribe = true;
				}
				else{
					$subscribe = false;
				}
			}
		}
		else{
			$subscribe = true;
		}
		
		if($subscribe && $email != $cf7_adv_dem['email'] && $list != "") {
			try {
				$api   = $cf7_adv_dem['api'];
				$adv_api = new Adv_dem_cf7_InterfaceAPI($api);
				if($adv_api->getRequestSuccessful()){
					$consoleCustomFields = $adv_api->getRecipientCustomFields($list);
				}
				$CustomFields = array();
				if($adv_api->getRequestSuccessful()){
					for ($i=0;$i<count($consoleCustomFields['data']);$i++){
						
						if( isset($cf7_adv_dem['CustomKey'.$i]) && isset($cf7_adv_dem['CustomValue'.$i]) && strlen(trim($cf7_adv_dem['CustomValue'.$i])) != 0 )
						{
							$value = cf7_adv_dem_tag_replace( $regex, trim($cf7_adv_dem['CustomValue'.$i]), $submission->get_posted_data() );
							if($consoleCustomFields['data'][$i]['type'] === 'BooleanCheckbox'){
								$value = ((int)$value === 1) ? true : false;
							}

							$CustomFields[] = array('id'=>(int)trim($cf7_adv_dem['CustomKey'.$i]), 'value'=> $value);
						}
						
					}
				}
				else{
					$CustomFields[] = array();
				}
				
				$args_subscriber= array(
				          "email_address" => $email,
				          "subscription" => $subscription,
				          "triggers" => array(
				            "automation" => true,
				            "behaviors" => true
				          ),
				          "update_if_duplicate" => true,
				          "custom_fields" => $CustomFields
				        );
				$resp = $adv_api->subscribeContact( $list, $args_subscriber );
				
			}
			// 			end try
			
			catch (Exception $e) {
				
			}
			// 			end catch		
			
		}
		// 		end $subscribe
		
	}
	
}

/**
 *4Dem - wpcf7_before_send_mail custom function
 */
function wpcf7_adv_dem_class_attr( $class ) {
	$class .= ' adv-dem-ext-' . ADV_DEM_CF7_VERSION;
	return $class;
}

/**
 *4Dem - Define Ajax code for getting custom fields
 */
function adv_dem_cf7_ajax_get_customfields() {
	$response = array();
	$listId = $_POST['listId'];
	$api   = trim($_POST['apikey']);
	$adv_api = new Adv_dem_cf7_InterfaceAPI($api);
	if($adv_api->getRequestSuccessful()){
		$response['successAuth'] = true;
		$response['listinfo'] = $adv_api->getRecipientInformation($listId);
		$response['successlist'] = $adv_api->getRequestSuccessful();
		$response['customfields'] = $adv_api->getRecipientCustomFields($listId);
		if($adv_api->getRequestSuccessful()){
			$response['success'] = true;
			$response['message'] = true;
		}
		else{
			$response['success'] = false;
			$response['message'] = true;
		}
	}
	else{
		$response['successAuth'] = false;
	}
	echo json_encode($response);
	wp_die();
}

/**
 *4Dem - Define Ajax code for creating new custom fields
 */
function adv_dem_cf7_ajax_add_customfields() {
	$listId = $_POST['listId'];
	$api   = trim($_POST['apikey']);
	$customFieldName = trim($_POST['customFieldName']);
	$adv_api = new Adv_dem_cf7_InterfaceAPI($api);
	if($adv_api->getRequestSuccessful()){
		$args_customField = array(
		      "recipient_id" => $listId,
		      "name" => $customFieldName,
		      "global" => false,
		      "type" => "Single line"
		    );
		$customFieldId = $adv_api->createNewCustomField( $args_customField );
	}
	else{
		$customFieldId = false;
	}
	echo json_encode($customFieldId);
	wp_die();
}

/**
 *4Dem - Custom function for APIKey check and validation
 */
function check_apikey($apikey) {
	$api_key = ( isset( $apikey ) )? $apikey : "" ;
	$api_adv = new Adv_dem_cf7_InterfaceAPI($api_key);
	return $api_adv->getRequestSuccessful();
}

/**
 *4Dem - Define Ajax code for creating new recipients
 */
function adv_dem_cf7_ajax_add_list() {
	$response = array();
	$api   = trim($_POST['apikey']);
	$listName = trim($_POST['listName']);
	$OptInMode = ($_POST['doubleOptIn'] == 1)? "double":"single";
	$adv_api = new Adv_dem_cf7_InterfaceAPI($api);
	$api_args = array("name"=> $listName, "opt_in"=> array("mode" => $OptInMode));
	$listId = $adv_api->createRecipient( $api_args );
	if($adv_api->getRequestSuccessful()){
		$response['success'] = true;
		$response['data'] = $listId;
		$response['message'] = esc_html__("List created successfully!", ADV_DEM_CF7_TEXTDOMAIN);
	}
	else{
		$response['success'] = false;
		$response['data'] = $listId;
		switch($adv_api->getLastError()){
			case 400: 
			        $response['message'] = esc_html__("List's name is already present on the console. Rename or remove the list and try again.", ADV_DEM_CF7_TEXTDOMAIN);
			break;
			case 403: 
			        $response['message'] = esc_html__('You have reached the maximum number of lists allowed for your console. Unable to create a new list.', ADV_DEM_CF7_TEXTDOMAIN);
			break;
			default: 
			        $response['message'] = esc_html__('Error creating the list.', ADV_DEM_CF7_TEXTDOMAIN);
			break;
		}
	}
	
	echo json_encode($response);
	wp_die();
}
