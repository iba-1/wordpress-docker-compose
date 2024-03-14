<?php
class Adv_dem_EcommercePlus {
    private $wpdb;
    private $product_table_name;
    private $batches_table_name;
    private $cart_table_name;
    private $apikey;
    private $storeId;
    private $syncroRecipientId;
    private $api4m;
    private $entrypoint;

    public function __construct() {
        // Init database interface
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->product_table_name =  $this->wpdb->prefix . "adv_dem_product_attributes";
        $this->batches_table_name = $this->wpdb->prefix . "adv_dem_batches";
        $this->cart_table_name = $this->wpdb->prefix . "adv_dem_carts";
        //  Init global parameters
        $options = get_option('adv_dem');
        $this->apikey = $options['apikey'];
        $this->entrypoint = $options['entrypoint'];
        $this->syncroRecipientId = $options['syncroRecipientId'];
        $this->storeId = isset($options["ecommercePlusStoreId"]) ? $options["ecommercePlusStoreId"] : 0;
        $this->api4m = new Adv_dem_InterfaceAPI( $this->apikey, $this->entrypoint);


	}

    /**
     * Check if the store is active or corrupted
     *
     * @return int the store id or 0 for errors
     */
    public function checkStoreId() {
        $this->api4m->getStoreInformation($this->storeId);
        if ($this->api4m->getRequestSuccessful()) {
            return $this->storeId;
        } else {
            return false;
        }
    }

    /**
     * Retrieve the store Id
     *
     * @return int
     */
    public function getStoreId() {
        return $this->storeId;
    }

    /**
     * Init E-commerce Plus on default recipient
     *
     * @param bool $forceReinit
     * @return int Current shop Id number or 0 if error
     */
    public function adv_ecplus_init_shop($forceReinit = false) {
        $options = get_option('adv_dem');
        if ($this->api4m->getRequestSuccessful()) {
            if ($forceReinit && isset($options["ecommercePlusStoreId"])) {
                // Butto via lo shop attuale e ne creo uno nuovo
                $this->api4m->deleteStoreAccount($options["ecommercePlusStoreId"]);
                $this->cleanDatabase();
            }
            $shopCurrency = get_woocommerce_currency();
            $shopName = get_bloginfo( 'name' );
            $shopBaseUrl = get_site_url();
            $shopData = array(
                "name" => $shopName,
                "type" => "Woocommerce",
                "currency" => $shopCurrency,
                "shop_id" => 1,
                "recipient_id" => $this->syncroRecipientId,
                "base_url" => $shopBaseUrl
            );
            $apiResult = $this->api4m->createNewStore( $shopData );
            if ($this->api4m->getRequestSuccessful()) {
                $options['ecommercePlusStoreId'] = $apiResult;
                update_option('adv_dem', $options);
                $this->storeId = $apiResult;
                // return $apiResult;
            }
            $this->adv_ecplus_reset_product_category();
        }
        // return false;
        return $apiResult;
    }

    public function adv_ecplus_reset_product_category(){
        $productsLoop = new WP_Query( array( 'post_type' => array('product', 'product_variation'), 'posts_per_page' => -1 ) );
        while ( $productsLoop->have_posts() ) : $productsLoop->the_post();
            $product_id = get_the_ID();
            update_post_meta( $product_id, 'adv_dem_primary_category_top', "" );
            update_post_meta( $product_id, 'adv_dem_primary_category_mid', "" );
            update_post_meta( $product_id, 'adv_dem_primary_category', "" );
        endwhile; wp_reset_query();
        return true;
    }

    /**
     * Update product attributes definition on E-commerce Plus
     *
     * @param array $selectedPropertyArray
     * @return bool $needProductAlign, new attribute/s created - Products must be resynch
     */
    public function adv_ecplus_set_product_attributes($selectedPropertyArray){
        $propertyInDatabase = $this->getPropertiesInDatabase();
        $propertyAlreadyDefined = array();
        $propertiesToCreate = array();
        $needProductAlign = false;

        foreach ($propertyInDatabase as $singleProperty) {
            if (!in_array($singleProperty["property_Wc_Id"], $selectedPropertyArray)) {
                $this->api4m->deleteAttributeFromStore($this->storeId , $singleProperty["property_Adv_Id"] );
                $this->wpdb->delete( $this->product_table_name, array( 'property_Adv_Id' => $singleProperty["property_Adv_Id"] ) );
            } else {
                $propertyAlreadyDefined[] = $singleProperty["property_Wc_Id"];
            }
        }
        $propertiesToCreate = array_diff($selectedPropertyArray , $propertyAlreadyDefined);
        $attributeTaxonomies = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name', 'attribute_id' );
        foreach ($propertiesToCreate as $propertyToCreate) {
            $propertyObject = array(
                "name" => $attributeTaxonomies[$propertyToCreate],
                "attribute_id" => $propertyToCreate,
                "type" => "varchar"
            );
            $apiResult = $this->api4m->createAttributeInStore($this->storeId, $propertyObject);
            if ($this->api4m->getRequestSuccessful()) {
                $this->wpdb->insert( $this->product_table_name, array( 'property_Name' => $attributeTaxonomies[$propertyToCreate], 'property_Wc_Id' => $propertyToCreate , 'property_Adv_Id' =>  $apiResult));
                $needProductAlign = true;
            }
        }
        return $needProductAlign;
    }

    /**
     * Update or create a product on E-commerce Plus
     *
     * @param int $product_id
     * @param boolean $checkForVariations
     * @return void
     */
    public function adv_ecplus_product_create_or_update($product_id, $checkForVariations = false){

        $productObject = $this->adv_dem_getWoocommerceProduct($product_id);

        $this->api4m->updateProductInformation($this->storeId, $productObject["product_id"], $productObject, (isset($productObject["variant_id"]) ? $productObject["variant_id"] : ""));
        $wcProduct = wc_get_product($product_id);
        if ($checkForVariations && $wcProduct->is_type( 'variable' ) ) {
            $available_variations = $wcProduct->get_available_variations();
            foreach ($available_variations as $key => $value)
            {
                $this->adv_ecplus_product_create_or_update($value["variation_id"]);
            }
        }
    }

    /**
     * Align E-commerce Plus products with Woocommerce Products
     *
     * @return void
     */
    public function adv_ecplus_products_align() {
        global $wpdb;
        $productArray = array();
        // $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' ) ? "https://" : "http://";
        $batchCall = array(
    		"callback_url" 	=> get_site_url(). "/wp-json/adv_dem_callback/batch/",
    		"skip_error"	=> true,
    		"operations"	=> array()
		);


        $maxBatchProducts = 500;
		$currentBatchProducts = 0;
		$productsCount = 0;
        $batches_operations_id = array();

        // Inizializzo il ciclo che spazzola tutti i prodotti presenti nel catalogo
        $productsLoop = new WP_Query( array( 'post_type' => array('product', 'product_variation'), 'posts_per_page' => -1 ) );
        $woocommerceProductsNumber = $productsLoop->post_count;
        $woocommerceProductsNumber = ($woocommerceProductsNumber > 15000 ? 15000 : $woocommerceProductsNumber);
        while ( $productsLoop->have_posts() ) : $productsLoop->the_post();
            $productsCount++;
            $product_id = get_the_ID();

		    $productArrayObject = $this->adv_dem_getWoocommerceProduct($product_id);
            $productArrayObject["store_id"] = $this->storeId;
            if (isset($singleOperation))  unset($singleOperation);

            //error_log("SINGLEOPERATION: ".json_encode($singleOperation));

            $singleOperation = array(
					"command" => "Stores.Product.Update",
					"custom_id" => $product_id,
					"body"	=> $productArrayObject
				);

                json_encode($singleOperation);
                $error = json_last_error_msg();

                if ($error != "No error")  {
                	error_log("PRODUCTIDERROR: ".json_encode($product_id));
                	error_log("JSONERROR: ".($error));
			continue;
                }

            //error_log("SINGLEOPERATION: ".json_encode($singleOperation));
            $batchCall["operations"][] = $singleOperation;

            $currentBatchProducts++;
            if ( ($currentBatchProducts == $maxBatchProducts) || ($productsCount == $woocommerceProductsNumber)) {

                $batch_id = $this->api4m->runBatchOperations($batchCall);
				$batches_operations_id[] = $batch_id;
				if($this->api4m->getRequestSuccessful()){
					$batchStatus = "ACTIVE";
				} else {
					$batchStatus = "ABORTED";
				}
				$wpdb->insert(
					$this->batches_table_name,
					array(
						'batch_Type' => "PRODUCT",
						'batch_Id' => $batch_id,
						'batch_Status' => $batchStatus,
						'batch_Operations' => $currentBatchProducts
					)
				);
				$currentBatchProducts = 0;
				$batchCall["operations"] = array();
            }
        endwhile; wp_reset_query();
        return array('productsCount' => $productsCount, 'batches_operations_id' => $batches_operations_id);
    }

    /**
     * Creates the array structure for product import based on a product ID
     *
     * @param int $product_id
     * @return array $productJson, the array to be used in update commands
     */
    private function adv_dem_getWoocommerceProduct($product_id) {
        $isProductVariation = false;
        $productJson = array(
            "product_id" => "",
            "title" => "",
            "description" => "",
            "category" => "",
            "status" => "enabled",
            "category_med" => "",
            "category_upper" => "",
            "product_url" => "",
            "product_image_url" => "",
            "brand" => "",
            "price" => "",
            "code" => "",
            "create_if_not_exist" => true
        );
        // $wcProduct = new WC_Product($product_id);
        $product_sku = get_post_meta($product_id, '_sku', true );
        if( get_post_type($product_id) == 'product_variation' ){
            $wcProduct = new WC_Product_Variation($product_id);
            $isProductVariation = true;
            $parent_id = wp_get_post_parent_id( $product_id );
            // ****** Some error checking for product database *******
            // check if variation sku is set
            if ($product_sku == '') {
                if ($parent_id == 0) {
                    // Remove unexpected orphaned variations.. set to auto-draft
                    $false_post = array();
                    $false_post['ID'] = $product_id;
                    $false_post['post_status'] = 'auto-draft';
                    wp_update_post( $false_post );
                } else {
                    // there's no sku for this variation > copy parent sku to variation sku
                    // & remove the parent sku so the parent check below triggers
                    $product_sku = get_post_meta($parent_id, '_sku', true );
                    update_post_meta($product_id, '_sku', $product_sku );
                    update_post_meta($parent_id, '_sku', '' );
                }
            }
            // ****************** end error checking *****************
                $productJson["product_id"] = $parent_id;
                $productJson["variant_id"] = $product_id;
                $productJson["title"] = get_the_title( $product_id );

                $prodotto = strip_tags(get_post($product_id)->post_content);
                $productJson["description"] = substr($prodotto, 0, 249);
                $productJson["status"] = (get_post($product_id)->post_status == "publish" ? "enabled" : "disabled");
                $productJson["product_url"] = mb_convert_encoding(get_permalink($product_id), "UTF-8", "UTF-8");
                $productImage = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ) );
                $productImageParent = wp_get_attachment_image_src( get_post_thumbnail_id( $parent_id ) );
                $productJson["product_image_url"] = isset($productImage [0]) ? $productImage [0] : ( isset($productImageParent [0]) ? $productImageParent [0] : "") ;

                if ($wcProduct->get_regular_price() != "") {
                    $productJson["price"] = $wcProduct->get_regular_price();
                } else {
                     $productJson["price"] ="0";
                     $productJson["status"] = "disabled";
                }
                $productJson["code"] = $product_sku;

            // its a simple product
        } else {
            $wcProduct = new WC_Product($product_id);
            $productJson["variant_id"] = 0;
            $productJson["product_id"] = $product_id;
            $productJson["title"] = mb_convert_encoding(get_the_title( $product_id ), "UTF-8", "UTF-8");
            $prodotto = mb_convert_encoding(strip_tags(get_post($product_id)->post_content), "UTF-8", "UTF-8");
            $productJson["description"] = substr( $prodotto , 0, 249);
            $productJson["status"] = (get_post($product_id)->post_status == "publish" ? "enabled" : "disabled");
            $productJson["product_url"] = mb_convert_encoding(get_permalink( $product_id ), "UTF-8", "UTF-8") ;
            $productImage = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ) );
            $productJson["product_image_url"] = isset($productImage[0]) ? mb_convert_encoding($productImage[0], "UTF-8", "UTF-8") : "" ;

            if ($wcProduct->get_regular_price() != "") {
                $productJson["price"] = $wcProduct->get_regular_price();
            } else {
                $productJson["price"] ="0";
                $productJson["status"] = "disabled";
            }
            $productJson["code"] = mb_convert_encoding(get_post_meta($product_id, '_sku', true ), "UTF-8", "UTF-8");
        }


        // Definisce la gerarchia delle categorie da importare su E-commerce Plus basandosi sul primary category id
        // $productCategoryName = get_post_meta( $productJson["product_id"], 'adv_dem_primary_category', true );
        // if ($productCategoryName != "") {
        // $productCategoryTerm = get_term_by("name", $productCategoryName, "product_cat");
        // if ($productCategoryTerm->parent > 0){
        //     $productCategoryMediumTerm = get_term_by("id", $productCategoryTerm->parent, "product_cat");
        //     $productCategoryMediumName =  $productCategoryMediumTerm->name;
        //     if ($productCategoryMediumTerm->parent > 0){
        //         $productCategoryUpperTerm = get_term_by("id", $productCategoryMediumTerm->parent, "product_cat");
        //         $productCategoryUpperName =  $productCategoryUpperTerm->name;
        //     } else {
        //         $productCategoryUpperName = "";
        //     }
        // }else{
        //     $productCategoryMediumName = "";
        //     $productCategoryUpperName = "";
        // }
        // } else {
        // $productCategoryName = esc_html__("NON SPECIFICATA" , "adv_dem");
        // $productCategoryMediumName = "";
        // $productCategoryUpperName =  "";
        // }

        // Nuova versione del codice: se non definite prende le prime tre del prodotto
        $productCategoryName = mb_convert_encoding(get_post_meta( $productJson["product_id"], 'adv_dem_primary_category', true ), "UTF-8", "UTF-8") ;
        $productCategoryMediumName = mb_convert_encoding(get_post_meta( $productJson["product_id"], 'adv_dem_primary_category_mid', true ), "UTF-8", "UTF-8");

        $productCategoryUpperName = mb_convert_encoding( get_post_meta( $productJson["product_id"], 'adv_dem_primary_category_top', true ), "UTF-8", "UTF-8");


        $post_categories =  wp_get_post_terms( $productJson["product_id"], 'product_cat' );
        if ($productCategoryUpperName == ""  ) {
            $productCategoryUpperName = (count((array)$post_categories) < 3 || is_null($post_categories[2]->name)? esc_html__("NON SPECIFICATA" , "adv_dem") : $post_categories[2]->name);
            update_post_meta(  $productJson["product_id"], 'adv_dem_primary_category_top', $productCategoryUpperName );
        }
        if ($productCategoryMediumName == "" ) {
            $productCategoryMediumName = (count((array)$post_categories) < 2 ||is_null($post_categories[1]->name)? esc_html__("NON SPECIFICATA" , "adv_dem") : $post_categories[1]->name);
            update_post_meta( $productJson["product_id"], 'adv_dem_primary_category_mid', $productCategoryMediumName );
        }
        if ($productCategoryName == "" ) {
            $productCategoryName = (count((array)$post_categories) < 1 || is_null($post_categories[0]->name)? esc_html__("NON SPECIFICATA" , "adv_dem") : $post_categories[0]->name);
            update_post_meta( $productJson["product_id"], 'adv_dem_primary_category', $productCategoryName );
        }



        $productJson["category"] = mb_convert_encoding($productCategoryName, "UTF-8", "UTF-8");
        $productJson["category_med"] = mb_convert_encoding($productCategoryMediumName, "UTF-8", "UTF-8");
        $productJson["category_upper"] = mb_convert_encoding($productCategoryUpperName, "UTF-8", "UTF-8");

        $productAttributesArray = $this->getProductProperties($wcProduct, $isProductVariation);
        if (count((array)$productAttributesArray)) $productJson["attributes"] = $productAttributesArray;



        return $productJson;
    }

    /**
     * Get product properties of a product
     *
     * @param itn $product_id
     * @return array $outputArray, the array of current product properties
     */
    private function getProductProperties($wcProduct, $isProductVariation = false) {
        $activePropertyArray = array();
        $propertyInDatabase = $this->getPropertiesInDatabase();

        if ($isProductVariation) {
            foreach ($propertyInDatabase as $singleProperty) {
                $activeProperty = array();
                $activeProperty["id"] = $singleProperty["property_Adv_Id"];
                $activeProperty["value"] = mb_convert_encoding(get_post_meta( $wcProduct->get_id() , 'attribute_pa_' . $singleProperty["property_Name"] , true), "UTF-8", "UTF-8");

                if ($activeProperty["value"] != "") $activePropertyArray[] = $activeProperty;
                unset($activeProperty);
            }
        } else {
            $productAttributes = $wcProduct->get_attributes();
            if (count((array)$productAttributes)) {
                foreach ($propertyInDatabase as $singleProperty) {
                    $activeProperty = array();
                    $activeProperty["id"] = $singleProperty["property_Adv_Id"];
                    $productTerm = wc_get_product_terms( $wcProduct->get_id(), "pa_" . $singleProperty["property_Name"]);

                    $activeProperty["value"] = count((array)$productTerm) ? mb_convert_encoding($productTerm[0]->name, "UTF-8", "UTF-8") : "";
                    if ($activeProperty["value"] != "") $activePropertyArray[] = $activeProperty;
                    unset($activeProperty);
                }
            }
        }
        return $activePropertyArray;
    }


    /**
    * Get active product properties from database
    * @return array $propertyArray, the array of the properties
    */
    private function getPropertiesInDatabase() {
        $query = "SELECT * FROM $this->product_table_name";
        $propertyArray = $this->wpdb->get_results($query , "ARRAY_A" );
        return $propertyArray;
    }

    /**
     * Cleanup database table when resetting the store
     *
     * @return void
     */
    private function cleanDatabase(){
        $cleanPropertyTableQuery = "TRUNCATE $this->product_table_name";
        $cleanCartTableQuery = "TRUNCATE $this->cart_table_name ";
        $this->wpdb->query($cleanPropertyTableQuery);
        $this->wpdb->query($cleanCartTableQuery);
        return true;
    }

    /**
     * Generate batches for order align
     *
     * @return void
     */
    public function adv_ecplus_order_align($days = "90") {
        global $wpdb;
        $orderArray = array();
        // $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' ) ? "https://" : "http://";
        $batchCall = array(
            "callback_url" 	=> get_site_url(). "/wp-json/adv_dem_callback/batch/",
            "skip_error"	=> true,
            "operations"	=> array()
    );

        $maxBatchOrders = 300;
        $currentBatchOrders = 0;
        $ordersCount = 0;
        $batches_operations_id = array();
        // Inizializzo il ciclo che spazzola tutti gli ordini a sistema
        $ordersLoop = get_posts(array(
            'post_type' => 'shop_order',
            'posts_per_page' => -1,
            'post_status' => array('wc-processing' , 'wc-pending' , 'wc-completed' , 'wc-on-hold', 'wc-cancelled', 'wc-refunded', 'wc-failed', 'trash'),
            'date_query' => array(
                'after' => date('Y-m-d', strtotime('-'.$days.' days')),
            )
            )
        );
        $woocommerceOrdersCount = count((array)$ordersLoop);
        foreach ($ordersLoop as $wcPostOrder) {
            $wcOrder = new WC_Order($wcPostOrder->ID);
            $ordersCount++;
            $anonymous_checkout_user_id = 0;
            // Verifico se l'ordine è anonimo o associato ad utente
            if (!$wcOrder->get_user_id()) {
                $billing_email = get_post_meta( $wcOrder->get_id(), "_billing_email", true );
                $subscriberObject = $this->api4m->getContactByEmail($this->syncroRecipientId, array('email_address' => $billing_email));
                if (!$this->api4m->getRequestSuccessful()){
                    $options = get_option('adv_dem');
                    $orderDetails =  array_map( function( $a ){ return $a[0]; }, get_post_meta( $wcOrder->get_id() ) );
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
                            "ip" => $_SERVER['REMOTE_ADDR'], //if subscription exists is required
                            "date" => date("Y-m-d H:i:s"), //optional
                            "status" => "Opt-In Pending"
                        ),
                        "triggers" => array(
                            "automation" => true,
                            "behaviors" => true
                        ),
                        "update_if_duplicate" => false,
                        "custom_fields" =>  $argsCustomFields
                    );
                    $apiResult = $this->api4m->subscribeContact( $this->syncroRecipientId , $args );
                    $anonymous_checkout_user_id = $apiResult;
                } else {
                    $anonymous_checkout_user_id = $subscriberObject['data']['id'];
                }
            }

            $orderArrayObject = $this->adv_dem_getWoocommerceOrder($wcOrder, $anonymous_checkout_user_id);
            if ($orderArrayObject) {
                $wcOrder_id = $wcOrder->get_id();
                $orderArrayObject["store_id"] = $this->storeId;
                if (isset($singleOperation))  unset($singleOperation);
                $singleOperation = array(
                        "command" => "Stores.Cart.Update",
                        "custom_id" => $wcOrder_id,
                        "body"	=> $orderArrayObject
                    );
                $batchCall["operations"][] = $singleOperation;
                $currentBatchOrders++;
            }
            if ( ($currentBatchOrders == $maxBatchOrders) || ($ordersCount == $woocommerceOrdersCount)) {
                $batch_id = $this->api4m->runBatchOperations($batchCall);
                $batches_operations_id[] = $batch_id;
                if($this->api4m->getRequestSuccessful()){
                    $batchStatus = "ACTIVE";
                } else {
                    $batchStatus = "ABORTED";
                }
                $wpdb->insert(
                    $this->batches_table_name,
                    array(
                        'batch_Type' => "ORDER",
                        'batch_Id' => $batch_id,
                        'batch_Status' => $batchStatus,
                        'batch_Operations' => $currentBatchOrders
                    )
                );
                $currentBatchOrders = 0;
                $batchCall["operations"] = array();
            }
        }
        return array('ordersCount' => $ordersCount, 'batches_operations_id' => $batches_operations_id);
    }

    /**
     * Create or update an order
     *
     * @param wc_order object $wcOrder_id
     * @return void
     */
    public function adv_ecplus_order_create_or_update($wcOrder_id){
        $wcOrder = new WC_Order($wcOrder_id);
        $wcOrderArray = $this->adv_dem_getWoocommerceOrder($wcOrder);
        if ($wcOrderArray) {
            $ecplusSource = json_decode(stripslashes($_COOKIE['ectDem']));
            if (!is_null($ecplusSource)) {
                $wcOrderArray["source"] = $ecplusSource->mode;
                $wcOrderArray["source_id"] = $ecplusSource->id;
            }
            // Creates the new order on the console
            $this->api4m->updateCart($this->storeId, $wcOrderArray, $wcOrder_id);
            // Drop user cart if exist
            if ($wcOrder->get_user_id()) {
                $user_cart = $this->wpdb->get_row("SELECT * FROM $this->cart_table_name WHERE cart_UserId = $wcOrder->user_id", ARRAY_A );
                if (count((array)$user_cart)) {
                    $this->wpdb->update(  $this->cart_table_name , array('cart_Status' => "CLOSE") ,  array( 'cart_UserId' => $wcOrder->user_id ));
                    $activeCartId = $user_cart["cart_Id"];
                    $this->api4m->dropCart($this->storeId, $activeCartId);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return an array with a specific order information
     *
     * @param wc_order object $wcOrder
     * @return array or boolean
     */
    private function adv_dem_getWoocommerceOrder($wcOrder, $anonymous_checkout_user_id = 0) {
        $subscriber_id = 0;
        $user_id = 0;
        if ($wcOrder->get_user_id()) {
            $subscriber_id = get_user_meta($wcOrder->get_user_id(), 'adv_dem_consoleId', true );
            $user_id = $wcOrder->get_user_id();
        } elseif ($anonymous_checkout_user_id) {
            $subscriber_id = $anonymous_checkout_user_id;
        } else {
            $billing_email = get_post_meta( $wcOrder->get_id(), "_billing_email", true );
            $subscriberObject = $this->api4m->getContactByEmail($this->syncroRecipientId, array('email_address' => $billing_email));
            if (!$this->api4m->getRequestSuccessful()) return false;
            $subscriber_id = $subscriberObject["data"]["id"];
            $user_id = 0;
        }
        $args = array();
        if ($subscriber_id) {
                $wcOrderDateCreated = $wcOrder->get_date_created();
                $options = get_option('adv_dem');
                $args = array(
                    "total" => $wcOrder->get_total(),
                    "opened_at" => $wcOrderDateCreated->date('Y-m-d H:i:s'),
                    "closed_at" => $wcOrderDateCreated->date('Y-m-d H:i:s'),
                    "source" => "",
                    "source_id" => 0,
                    "create_if_not_exist" => true,
                    "cart_id" => $wcOrder->get_id(),
                    "subscriber_id" => $subscriber_id,
                    "shop_user_id" => $user_id,
                    "triggers" => array("automation" => true),
                    "products" => $this->adv_ecplus_getOrderProducts($wcOrder)
                );
                $wcStatus = $wcOrder->get_status();
                switch ($wcStatus) {
                    case "on-hold":
                    case "processing":
                    case "completed":
                    case "pending":
                        if (in_array($wcOrder->get_status(), str_replace('wc-', '', $options['eplusOrderStatusClosed']))) {
                            $args["status"] = "close";
                        } else {
                            $args["status"] = "open";
                        }
                        break;

                    case "cancelled":
                    case "refunded":
                    case "failed":
                    case "trash":
                        $args["status"] = "drop";
                        break;

                    default:
                        $args["status"] = "close";
                    break;
                }
                return $args;
            }
            return false;
    }



    /**
     * Return an array with order products
     *
     * @param wc_order object $wcOrder
     * @return array
     */
    private function adv_ecplus_getOrderProducts ($wcOrder) {
        $itemsArray = array();
        $orderItems = $wcOrder->get_items();
        foreach ($orderItems as $orderItemKey => $orderItem) {
            $orderItemElement = array(
                "line_id" => $orderItemKey,
                "product_id" => $orderItem['product_id'],
                "variant_id" =>  $orderItem['variation_id'],
                "price" => $orderItem['line_total'],
                "quantity" => $orderItem['qty']
            );
            $itemsArray[] = $orderItemElement;
        }
        return $itemsArray;
    }

    /**
     * If the user is logged in check if a virtual cart is enabled. If not a new one is initialized
     *
     * @param boolean $orderPlaced
     * @return void
     */
    public function adv_ecplus_manage_cart(){
        if (!is_user_logged_in()) return true;
        $current_user = wp_get_current_user();
        $subscriber_id = get_user_meta($current_user->ID, 'adv_dem_consoleId', true );
        if ($subscriber_id && $current_user->ID) {
            $user_cart = $this->wpdb->get_row("SELECT * FROM $this->cart_table_name WHERE cart_UserId = $current_user->ID", ARRAY_A );
            if (count((array)$user_cart)) {
                $activeCartId = $user_cart["cart_Id"];
                $activeCartCreationDate = $user_cart["cart_Creation_Time"];
                $activeCartStatus = $user_cart["cart_Status"];
            } else {
                $activeCartId = md5($current_user->ID . time());
                $activeCartCreationDate = date('Y-m-d H:i:s', time());
                $this->wpdb->insert( $this->cart_table_name, array( 'cart_Id' => $activeCartId ,'cart_Status' => "OPEN",  'cart_UserId' => $current_user->ID, 'cart_Creation_Time' => $activeCartCreationDate));
                $activeCartStatus = "OPEN";
            }

            switch ($activeCartStatus) {
                case 'OPEN':
                    // Cart is open and active must be updated
                    $this->adv_ecplus_cart_create_or_update($current_user->ID, $subscriber_id, $activeCartId);
                    break;
                case 'CLOSE':
                     $this->wpdb->delete( $this->cart_table_name, array( 'cart_Id' => $activeCartId ) );
                break;
            }
        }
        return true;
    }

    /**
     * Creates or update a virtual cart on e-commerce plus for current logged in user
     *
     * @param int $user_id
     * @param int $subscriber_id
     * @param string $cart_id
     * @return void
     */
    private function adv_ecplus_cart_create_or_update ($user_id, $subscriber_id ,$cart_id) {
        global $woocommerce;
        $activeCart = $woocommerce->cart;
        $cartProducts = $activeCart->get_cart();
        $args = array(
            "total" => $activeCart->subtotal,
            "status" => "open",
            "opened_at" => date('Y-m-d H:i:s', time()),
            "create_if_not_exist" => true,
            "cart_id" => $cart_id,
            "subscriber_id" => $subscriber_id,
            "shop_user_id" => $user_id,
            "triggers" => array("automation" => true)
        );
        $ecplusSource = isset($_COOKIE['ectDem']) ? json_decode(stripslashes($_COOKIE['ectDem'])) : null;
        if (!is_null($ecplusSource)) {
            $args["source"] = $ecplusSource->mode;
            $args["source_id"] = $ecplusSource->id;
        }
        foreach ($cartProducts as $cartProductIndex => $cartProduct) {
            $orderItemElement = array(
                "line_id" => $cartProductIndex,
                "product_id" => $cartProduct['product_id'],
                "variant_id" =>  $cartProduct['variation_id'],
                "price" => $cartProduct['line_total'],
                "quantity" => $cartProduct['quantity']
            );
            $args["products"][] = $orderItemElement;
            unset($orderItemElement);
        }
        $this->api4m->updateCart($this->storeId, $args, $cart_id);
    }
    // FUNZIONE DI PROVA PER TESTARE LA CLASSE!!
    public function test() {
        delete_option( 'adv_dem' );
        delete_option( 'adv_demsincronizza_utenti' );
        $a=0;
    }
}

?>
