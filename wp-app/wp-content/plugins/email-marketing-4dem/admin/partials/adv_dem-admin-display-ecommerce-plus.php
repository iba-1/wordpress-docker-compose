<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.4marketing.it
 * @since      1.0.0
 *
 * @package    Adv_dem
 * @subpackage Adv_dem/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<!-- SELEZIONE MULTIPLA PROPRIETA' DA IMPORTARE -->
<?php
	global $wpdb;
	$csvProductAlign = false;
	$importCsv = array();
	if(sanitize_text_field(isset($_POST['csv-import-submit']))){ //check if form was submitted
		if( isset($_FILES['csv-import']) && !empty($_FILES['csv-import']['name'])){ //check if there is a file

			$csvArray = array_map('str_getcsv', file( sanitize_text_field(wp_unslash($_FILES['csv-import']['tmp_name'])) ) );
			/*devo ciclare nel file e prendere i primi valori corrispondenti all'id e gli ultimi delle categorie ecp*/
			$i= 0;
			foreach( $csvArray as $singleLine ){
				if($i == 0){
					if($singleLine[0] == "ID" && $singleLine[1] == "SKU" && $singleLine[2] == "PRODUCT_NAME" && $singleLine[3] == "PRODUCT_CATEGORIES(NAME)" && $singleLine[4] == "E_COMMERCE_PLUS_GENERAL_CATEGORY(NAME)" && $singleLine[5] == "E_COMMERCE_PLUS_MEDIUM_CATEGORY(NAME)" && $singleLine[6] == "E_COMMERCE_PLUS_SPECIFIC_CATEGORY(NAME)" ){
						$singleLineLength = count((array)$singleLine);
					}else{
						$importCsv['error'] = esc_html__("Il csv inserito non è corretto: scarica il csv con l'apposito bottone e modifica solamente le colonne riservate alle categorie E-commerce Plus .", "adv_dem");
						break;
					}
				}else{
					$product_id = intval( trim($singleLine[0]) );
					$primary_category_top = sanitize_text_field( trim($singleLine[4])  );
					$primary_category_mid = sanitize_text_field( trim($singleLine[5])  );
					$primary_category = sanitize_text_field( trim($singleLine[6])  );

					//	Prendo la configurazione attuale
					$old_primary_category_top = get_post_meta($product_id , 'adv_dem_primary_category_top', true );
					$old_primary_category_mid = get_post_meta($product_id , 'adv_dem_primary_category_mid', true );
					$old_primary_category = get_post_meta($product_id , 'adv_dem_primary_category', true );


					$term =  get_the_terms( $product_id, 'product_cat' );



					$productCategoryTopExist = false;
					$productCategoryMidExist = false;
					$productCategoryExist = false;
					if($term){
						foreach($term as $singleTerm){
							if( ($singleTerm->name == $primary_category_top) || ($singleTerm->name == esc_html__("NON SPECIFICATA" , "adv_dem"))){
								$productCategoryTopExist = true;
							}
							if( ($singleTerm->name == $primary_category_mid) || ($singleTerm->name ==  esc_html__("NON SPECIFICATA" , "adv_dem"))) {
								$productCategoryMidExist = true;
							}
							if( ($singleTerm->name == $primary_category) || ($singleTerm->name ==  esc_html__("NON SPECIFICATA" , "adv_dem"))){
								$productCategoryExist = true;
							}
						}
					}

					if( $primary_category_top != "" && $primary_category_top !=  $old_primary_category_top && $productCategoryTopExist ){
						update_post_meta( $product_id, 'adv_dem_primary_category_top', $primary_category_top );
						$csvProductAlign = true;
					}
					if( $primary_category_mid != "" && $primary_category_mid !=  $old_primary_category_mid && $productCategoryMidExist ){
						update_post_meta( $product_id, 'adv_dem_primary_category_mid', $primary_category_mid );
						$csvProductAlign = true;
					}
					if( $primary_category != "" && $primary_category !=  $old_primary_category && $productCategoryExist ){
						update_post_meta( $product_id, 'adv_dem_primary_category', $primary_category );
						$csvProductAlign = true;
					}
				}
				$i++;
			}
			if($csvProductAlign == false){
				$importCsv['success'] = esc_html__("Nessuna modifica riscontrata", "adv_dem");
			}else{
				$importCsv['success'] = esc_html__("Categorie modificate con successo", "adv_dem");
			}
		}else{
			$importCsv['error'] = esc_html__("ATTENZIONE: non hai selezionato nessun file CSV. Clicca su Scegli file e selezionane uno prima di procedere con l'importazione.", "adv_dem");
		}
	}
?>
<?php
	$productTableName = $wpdb->prefix . "adv_dem_product_attributes";
	$eplusOrderStatusClosed = array('wc-on-hold','wc-processing','wc-completed','wc-pending');
	$consoleAttributes = array_map(function($a) {  return array_pop($a); }, $wpdb->get_results( $wpdb->prepare("SELECT property_Wc_Id FROM " . $productTableName),  'ARRAY_N' ));
	if($consoleAttributes == false) {$consoleAttributes = array();}
	$options = get_option($this->plugin_name);
	$storeInit = isset($options['ecommercePlusStoreId']) ? $options['ecommercePlusStoreId'] : "" ;
	$eplusOrderStatusClosedArr = isset($options['eplusOrderStatusClosed']) ? $options['eplusOrderStatusClosed'] : $eplusOrderStatusClosed ;
	$checkApikey = $this->check_apikey();
	$wcIsActive = class_exists( 'Woocommerce' );
	$recipientIntegrity = $this->checkRecipientIntegrity();
	if($storeInit != "") {
		$shopIntegrity = $this->checkShopIntegrity();
	} else {
		$shopIntegrity = false;
	}
	$info = $this->console_user_info();

	//Obtain stores number
	$numberStores = $this->console_stores_number();

	$license = true;
	$licenseInfo['products'] = 0;

?>


<div class="wrap adv_wrapper">
	<h1><span class="dashicons dashicons-cart"></span> <?php echo esc_html( get_admin_page_title() ); ?></h1>
  	<?php settings_errors(); ?>
	<div id="saveResult"></div>
	<?php if($checkApikey && $wcIsActive && $recipientIntegrity && $license): ?>

		<div class="postbox adv_section">
			<h2><span class="dashicons dashicons-store" ></span> <?php echo esc_html__("E-commerce Plus","adv_dem") ?></h2>
			<table>
				<tr>
					<th>
						<?php echo esc_html__("Attivazione E-commerce Plus","adv_dem") ?>:
						<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Per lavorare con E-commerce Plus è necessario attivare il modulo. Una volta attivato, fai le prime sincronizzazioni manuali per avere la console aggiornata con WooCommerce. Le sincronizzazioni manuali vanno ripetute ogni volta che fai modifiche massive sugli ordini o sui prodotti importati da file CSV. Se non fai modifiche massive ai prodotti ti basta eseguire una volta la sincronizzazione, al momento del caricamento dei prodotti","adv_dem"); ?></div></span>
					</th>
					<th><?php echo ($storeInit!= "" ?  '<span class="adv_success">'.esc_html__("ATTIVO","adv_dem").'</span>' :  '<span class="adv_error">'.esc_html__("NON ATTIVO","adv_dem").'</span>'); ?></th>
				</tr>
				<tr>
					<th>
						<?php echo esc_html__("Licenza E-commerce Plus","adv_dem") ?>:
						<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Indica se hai acquistato la licenza del modulo","adv_dem"); ?></div></span>
					</th>
					<th><?php echo ($license ?  '<span class="adv_success">'.esc_html__("ATTIVA","adv_dem").'</span>' :  '<span class="adv_error">'.esc_html__("NON ATTIVA","adv_dem").'</span>'); ?></th>
				</tr>
				<!-- <?php if($license): ?> -->
				<!-- <tr>
					<th>
						<?php echo esc_html__("Prodotti da sincronizzare (n. max)","adv_dem") ?>:
						<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Indica il numero massimo dei prodotti che puoi sincronizzare con la console.","adv_dem"); ?></div></span>
					</th>
					<th><span class="adv_success"> <?php echo esc_html($licenseInfo['products']) ?> </span></th>
				</tr>
				<?php endif ?> -->
			</table>

			<?php if(!$storeInit): ?>
			<div class="submit">
				<button class="button-primary control-button" id="init_shop"><?php echo ($storeInit)? esc_html__("Inizializza","adv_dem") : esc_html__("Attiva Shop","adv_dem"); ?></button>
				<span id="init_shop_spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Operazione in corso...","adv_dem") ?></span>
			</div>
			<?php endif ?>
		</div>

		<?php if($shopIntegrity && $storeInit): ?>
			<div class="postbox adv_section">
				<h2><span class="dashicons dashicons-admin-tools"></span> <?php echo esc_html__("Impostazioni di gestione dei prodotti","adv_dem") ?></h2>
				<h2><?php echo esc_html__("Sincronizzazione attributi personalizzati","adv_dem") ?></h2>
				<table>
					<tr>
						<th>
							<span><?php echo esc_html__("Seleziona quali attributi utilizzare su E-commerce Plus (max 10)","adv_dem") ?> : </span>
							<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Questi sono gli attributi dei prodotti presenti sul tuo WooCommerce. Scegli quali utilizzare anche sul modulo E-commerce Plus. Puoi selezionare massimo 10 attributi.","adv_dem"); ?></div></span>
						</th>
						<th></th>
					</tr>
				</table>

				<div class="attributes-ecommerceplus">
					<?php
					$attribute_taxonomies = wc_get_attribute_taxonomies();
					$consoleDropAttributes = $consoleAttributes;
					if ( $attribute_taxonomies ) :
						foreach ($attribute_taxonomies as $tax) :
							//ciclo nel db -> se presente nel db aggiungo class active e data-toggle-on="true" altrimenti tolgo la classe active e data-toggle-on="false"
							if(in_array($tax->attribute_id, $consoleDropAttributes)){
								$data_toggle_on = true;
								$checkboxActive = 'active';
								// Search
								$pos = array_search($tax->attribute_id, $consoleDropAttributes);
								unset($consoleDropAttributes[$pos]);
							}else{
								$data_toggle_on = false;
								$checkboxActive = '';
							}
							echo '	<div id="" class="toggle toggle-modern '.$checkboxActive.'" data-toggle-height="20" data-toggle-width="60" data-toggle-on="'.$data_toggle_on.'" attribute-id="' . $tax->attribute_id . '"></div><span>' . $tax->attribute_label . '</span><br>';
						endforeach;
					else :
						echo '<p>'.esc_html__("Non ci sono attributi personalizzati.","adv_dem").'</p>';
					endif;
					?>
				</div>
				<?php //if( count($consoleDropAttributes) > 0 ){ echo 'ci sono attributi presenti in console non più presenti su Woocommerce: aggiornare le impostazioni';} ?>

				<div class="submit">
					<button class="button-primary control-button" id="save_option"><?php echo ($storeInit)? esc_html__("Salva attributi personalizzati","adv_dem") : esc_html__("Inizializza nuovo shop","adv_dem"); ?></button>
					<span id="syncro-spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Preparazione dell'operazione in corso...","adv_dem") ?></span>
				</div>

				<h2><?php echo esc_html__("Impostazione categorie dei prodotti su E-commerce Plus","adv_dem"); ?></h2>
				<p>
					<?php echo esc_html__("Su E-commerce Plus è possibile sincronizzare al massimo tre categorie per prodotto. Al momento della prima sincronizzazione dei prodotti il sistema selezionerà le prime tre categorie in ordine alfabetico. Successivamente potrai modificarle in qualsiasi momento con una delle seguenti modalità:","adv_dem"); ?><br />
				</p>
				<ol>
					<li>
						<?php echo esc_html__("Imposta le categorie utilizzando i menù a tendina nella scheda di ciascun prodotto.","adv_dem"); ?><br />
					</li>
					<li>
						<?php echo esc_html__("Se vuoi modificare le categorie di più prodotti contemporaneamente, scarica il template CSV (puoi scaricarlo sotto), modificalo e reimportalo utilizzando il pulsante \"Avvia importazione da file\" dopo averlo selezionato.","adv_dem"); ?><br />
					</li>
				</ol>
				<form id="csv-import" action="" method="POST" enctype="multipart/form-data">
					<table>
						<tr>
							<th>
								<span><?php echo esc_html__("Scarica tutti i prodotti e abbina categorie (file formato CSV) ","adv_dem") ?></span>
								<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Scarica il template di esempio in formato CSV precompilato per aggiornare le categorie principali di tutti i tuoi prodotti contemporaneamente.","adv_dem"); ?></div></span>
							</th>
							<th>
								<a class="" href="<?php  echo esc_url(get_site_url() . '/wp-json/adv_dem_callback/exportCat/') ?>"><?php echo esc_html__("Scarica template CSV","adv_dem"); ?><span class="dashicons dashicons-download" style=" text-decoration:none; "></span></a>
							</th>
						</tr>
						<tr>
							<th>
								<span><?php echo esc_html__("Importa le categorie principali da file: ","adv_dem") ?></span>
								<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Utilizza il file CSV che ti abbiamo dato in esempio per aggiornare le categorie principali di più prodotti contemporaneamente. Modifica le colonne E_COMMERCE_PLUS_GENERAL_CATEGORY(NAME), E_COMMERCE_PLUS_MEDIUML_CATEGORY(NAME), E_COMMERCE_PLUS_SPECIFIC_CATEGORY(NAME). Il valore delle categorie E-commerce Plus deve essere uno di quelli presenti nella colonna PRODUCT_CATEGORIES(NAME). Altri valori non verranno considerati. Se i prodotti non hanno ancora una categoria associata su WooCommerce, bisognerà prima assegnare una categoria al prodotto tramite la pagina del prodotto WooCommerce.","adv_dem"); ?></div></span>
							</th>
							<th>
								<input type="file" accept=".csv" name="csv-import" value="<?php echo esc_html__("Carica file CSV","adv_dem") ?>">
							</th>
						</tr>
					</table>
					<div class="submit">
						<input type="submit" class="button-primary control-button" id="import_csv_button" name="csv-import-submit" value="<?php echo esc_html__("Avvia importazione da file","adv_dem"); ?>">
						<span id="import_csv_button_spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Preparazione dell'operazione in corso...","adv_dem") ?></span>
					</div>
				</form>
			</div>
			<?php if(isset( $importCsv['success'] ) ): ?>
				<div id="saveMessage4" class="notice notice-success is-dismissible"><p><?php echo $importCsv['success']; ?></p><button id="delete-message4" type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>
			<?php elseif(isset( $importCsv['error'] ) ): ?>
				<div id="saveMessage4" class="notice notice-error is-dismissible"><p><?php echo $importCsv['error']; ?></p><button id="delete-message4" type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>
			<?php endif ?>
			<div class="postbox adv_section">
				<h2><span class="dashicons dashicons-products" ></span> <?php echo esc_html__("Importazione e sincronizzazione manuale dei prodotti","adv_dem") ?></h2>
				<p><?php echo esc_html__("Importa tutti i prodotti di WooCommerce sulla console: i prodotti già esistenti verranno aggiornati automaticamente.","adv_dem") ?></p>
				<div class="submit">
					<button class="button-primary control-button" id="product_update"><?php echo esc_html__("Avvia l'importazione/aggiornamento dei prodotti","adv_dem"); ?></button>
					<span id="product_update_spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Preparazione dell'operazione in corso...","adv_dem") ?></span>
				</div>
			</div>
			<div class="postbox adv_section">
				<h2><span class="dashicons dashicons-clipboard" ></span> <?php echo esc_html__("Importazione manuale degli ordini pregressi","adv_dem") ?></h2>
				<p><?php echo esc_html__("Importa gli ordini pregressi di WooCommerce sulla console: prima di procedere con l'importazione degli ordini assicurati di aver correttamente importato gli utenti e i prodotti. Le sincronizzazioni manuali vanno ripetute ogni volta che fai modifiche massive sugli ordini o sui prodotti importati da file CSV. Se non fai modifiche massive ai prodotti ti basta eseguire una volta la sincronizzazione, al momento dei caricamento dei prodotti","adv_dem") ?></p>
				<table>
					<tr>
						<th>
							<span><?php echo esc_html__("Quali ordini vuoi importare?","adv_dem")?> </span>
							<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Tramite questa selezione puoi impostare il periodo dal quale far partire l'importazione degli ordini di WooCommerce.","adv_dem"); ?></div></span>
						</th>
						<th>
							<select id="order_time">
								<option value="1"><?php echo esc_html__("Ultimo mese","adv_dem"); ?></option>
								<option value="2"><?php echo esc_html__("Ultimi tre mesi","adv_dem"); ?></option>
								<option value="3"><?php echo esc_html__("Ultimi sei mesi","adv_dem"); ?></option>
							</select>
						</th>
					</tr>
				</table>

				<div class="submit">
					<button class="button-primary control-button" id="order_update"><?php echo esc_html__("Avvia l'importazione degli ordini","adv_dem"); ?></button>
					<span id="order_update_spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Preparazione dell'operazione in corso...","adv_dem") ?></span>
				</div>
			</div>
			<div id="saveResult2"></div>
			<div class="postbox adv_section adv_dem_logo">
				<div style="margin-bottom:25px">
					<h2>Seleziona con quale status vuoi considerare l'ordine come concluso</h2>
				</div>
				<form name="frmOrderStatus[]" id="fm_submit">
					<div style="margin-bottom:25px">
						<input type="checkbox" id="onHoldStatus" name="onHold" value="wc-on-hold" class="wcOrderStatus" <?php echo in_array("wc-on-hold", $eplusOrderStatusClosedArr) ? 'checked' : '' ?> >
						<label for="onHoldStatus">On Hold</label>
						<input type="checkbox" id="pendingStatus" name="pending" value="wc-pending" class="wcOrderStatus" <?php echo in_array("wc-pending", $eplusOrderStatusClosedArr) ? 'checked' : '' ?> >
						<label for="pendingStatus">Pending</label>
						<input type="checkbox" id="processingStatus" name="processing" value="wc-processing" class="wcOrderStatus" <?php echo in_array("wc-processing", $eplusOrderStatusClosedArr) ? 'checked' : '' ?> >
						<label for="processingStatus">Processing</label>
						<input type="checkbox" id="completedStatus" name="completed" value="wc-completed" class="wcOrderStatus" <?php echo in_array("wc-completed", $eplusOrderStatusClosedArr) ? 'checked' : '' ?> >
						<label for="completedStatus">Completed</label>
					</div>
					<div id="statusError" class="statusErrorHide" style="color:red; margin-bottom:25px">Devi selezionare almeno uno status</div>
					<div id="statusUpdate" class="statusUpdateHide" style="color:green; margin-bottom:25px"></div>
					<div style="margin-bottom:25px">
						<button class="button-primary control-button" type="submit" name="fm_submit">Salva</button>
					</div>
				</form>
			</div>
			<div class="postbox adv_section">
				<h2><span class="dashicons dashicons-update" ></span> <?php echo esc_html__("Riattivazione forzata del modulo E-commerce Plus","adv_dem") ?></h2>
				<div id="reinit-box" class="allert-box">
					<label for="forceReinit"><input type="checkbox" name="forceReinit" id="forceReinit"><?php echo esc_html__("Voglio riattivare il modulo E-commerce Plus. Sono sicuro.","adv_dem"); ?> </label>
					<p><b><?php echo esc_html__("ATTENZIONE:","adv_dem"); ?></b> <?php echo esc_html__("la riattivazione forzata del modulo comporta la cancellazione di qualsiasi shop attualmente associato alla tua lista di sincronizzazione. Perderai tutti i dati statistici di vendita associati allo shop. Questa operazione è necessaria nel caso in cui la lista di sincronizzazione predefinita sulla piattaforma di email marketing sia stata corrotta o cancellata.","adv_dem"); ?><br /><b><?php echo esc_html__("QUESTA OPERAZIONE NON E' REVERSIBILE.","adv_dem"); ?></b></p>
				</div>
				<div class="submit">
					<button class="button-primary control-button" id="init_shop"><?php echo esc_html__("Riattiva il modulo","adv_dem"); ?></button>
					<span id="init_shop_spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Operazione in corso...","adv_dem") ?></span>
				</div>
			</div>

		<?php elseif( $shopIntegrity ) : ?>
			<div class="postbox adv_section">
					<h2><span class="dashicons dashicons-update" ></span> <?php echo esc_html__("Reinizializzazione Modulo","adv_dem") ?></h2>
				<tr>
					<th><?php echo esc_html__("Errori","adv_dem") ?>: </th>
					<th><span class="adv_error"><?php echo esc_html__("Corrotto oppure non presente sulla console (verificare che sia corretta l'API Key oppure in caso di lista corrotta reinizializzare lo shop resettando i dati)","adv_dem"); ?></span></th>
				</tr>
				<div id="reinit-box" class="allert-box">
					<label for="forceReinit"><input type="checkbox" name="forceReinit" id="forceReinit"><?php echo esc_html__("Forza la reinizializzazione","adv_dem"); ?> </label>
					<p><?php echo esc_html__("(se attivo, lo shop verrà reinizializzato comportando la perdita di tutti i dati precedenti. Consigliato in caso di liste compromesse)","adv_dem"); ?></p>
				</div>
				<div class="submit">
					<button class="button-primary control-button" id="init_shop"><?php echo esc_html__("Reinizializza","adv_dem"); ?></button>
					<span id="init_shop_spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Operazione in corso...","adv_dem") ?></span>
				</div>
			</div>
		<?php endif ?>
	<?php else: ?>

		<?php if(!$checkApikey): ?>
			<div id="configuration-form-error" class="postbox adv_section adv_dem_logo" style="background-image: url('<?php echo esc_url(ADV_DEM_COMPANY_LOGO); ?>')"><div class="allert-box"><h2><?php echo esc_html__("Devi configurare l'API Key correttamente","adv_dem"); ?></h2></div></div>
		<?php else: ?>
			<div class="postbox adv_section adv_dem_logo" style="background-image: url('<?php echo esc_url(ADV_DEM_COMPANY_LOGO); ?>')">
				<h2><?php echo esc_html__("Requisiti","adv_dem") ?></h2>
				<table>
					<tr>
						<th><span><?php echo esc_html__("API Key","adv_dem") ?>: </span></th>
						<th><?php echo ($checkApikey ?  '<span class="adv_success">'.esc_html__("Corretta","adv_dem").'</span>' :  '<span class="adv_error">'.esc_html__("Errata","adv_dem").'</span>'); ?></th>
					</tr>
					<tr>
						<th><span><?php echo esc_html__("Lista","adv_dem") ?>: </span></th>
						<th><?php echo ($recipientIntegrity ?  '<span class="adv_success">'.esc_html__("Inizializzata correttamente","adv_dem").'</span>' :  '<span class="adv_error">'.esc_html__("Danneggiata o non trovata","adv_dem").'</span>'); ?></th>
					</tr>
					<tr>
						<th><span><?php echo esc_html__("Woocommerce","adv_dem") ?>: </span></th>
						<th><?php echo ($wcIsActive ?  '<span class="adv_success">'.esc_html__("Attivo","adv_dem").'</span>' :  '<span class="adv_error">'.esc_html__("Non attivo","adv_dem").'</span>'); ?></th>
					</tr>
					<tr>
						<th><span><?php echo esc_html__("Licenza","adv_dem") ?>: </span></th>
						<th><?php echo ($license ?  '<span class="adv_success">'.esc_html__("Attiva","adv_dem").'</span>' :  '<span class="adv_error">'.esc_html__("Non attiva","adv_dem").'</span>'); ?></th>
					</tr>
				</table>
			</div>
		<?php endif ?>

	<?php endif ?>
	<div id="progress-container">
		<div id="progress-close"><span class="dashicons dashicons-dismiss progress-close-icon"></span></div>
		<div id="progressbar" class="" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="20"><div id="innerbar" class="" style="width: 0px;"></div></div>
		<p id="batch-loader"><?php echo esc_html__("operazione in corso","adv_dem")?>... <span id="batch-operation-division"></span><span id="syncro-spinner" class="spinner is-active" style="float:none;width:auto;padding:0px 0 0px 50px;background-position:0px 0;"></span></p>
		<div id="batch-finish"></div>
	</div>
</div>
<script>
	jQuery(document).ready(function($){

		$('#fm_submit').submit(function(e){
			e.preventDefault();
			$('#statusUpdateMessage').remove()
			$('#statusUpdate').removeClass("statusUpdateVisible").addClass("statusUpdateHide")
			var options = $('input.wcOrderStatus[type="checkbox"]:checked');
			if(options.length === 0){
				$('#statusError').removeClass("statusErrorHide").addClass("statusErrorVisible")
				return
			}
			$('#statusError').removeClass("statusErrorVisible").addClass("statusErrorHide")
			var orderStatusArr = []
			options.each(function() {
				console.log('vvalue', this.value);
				orderStatusArr.push(this.value)
			});
			if (orderStatusArr.length > 0) {
				$.post(
					ajaxurl, {
						'action': 'update_eplus_order_status_closed_option',
						'orderStatus' : orderStatusArr,
					},
					function(response) {
						var result = $.parseJSON( response );
						if (result.success) {
							$('#statusUpdate').append("<p id='statusUpdateMessage'>" + result.message + "</p>")
							$('#statusUpdate').removeClass("statusUpdateHide").addClass("statusUpdateVisible")
						} else {
							$('#statusUpdate').append("<p id='statusUpdateMessage'>Qualcosa è andato storto, riprova in un secondo momento</p>")
							$('#statusUpdate').removeClass("statusUpdateHide").addClass("statusUpdateVisible")
						}
					}
				);
			}
		});

		loopToResponseBatch(null, false);
		$('.toggle').toggles();
		var theCheckboxes = $(".attributes-ecommerceplus input[type='checkbox']");
		theCheckboxes.click(function()
		{
			if (theCheckboxes.filter(":checked").length > 10)
				$(this).removeAttr("checked");
		});

		$('.toggle').on('toggle', function(e, active) {
			if (active) {
				$(this).addClass('active');
			} else {
				$(this).removeClass('active');
			}
			if($('.toggle.active').length > 10){
				$(this).click();
				$(this).after('<div class="attribute-error-message"><?php echo esc_html__("Puoi sincronizzare fino ad un massimo di 10 attributi contemporaneamente","adv_dem") ?></div>');
				$('.attribute-error-message').show('fast');
				setTimeout(function(){
					$('.attribute-error-message').hide('fast').remove();
				},2000);
			}
		});

		<?php if($csvProductAlign): ?>
			$('#batch-finish').html('');
			$('.control-button').attr('disabled','disabled');
			$('#product_update_spinner').addClass('is-active');
			$.post(
				ajaxurl, {
					'action': 'init_shop',
					'button_id' : 'product_update',
				},
				function(response) {
					var result = $.parseJSON( response );
					if( result.batches_products.batches_operations_id.length > 0 ){
						loopToResponseBatch(null, false);
					}else{
						$('.control-button').removeAttr('disabled');
					}
					$('#product_update_spinner').removeClass('is-active');
				}
			);
		<?php endif ?>

		$('#product_update').on('click', function(){
			$('#batch-finish').html('');
			$('.control-button').attr('disabled','disabled');
			$('#product_update_spinner').addClass('is-active');
			$.post(
				ajaxurl, {
					'action': 'init_shop',
					'button_id' : $(this).attr('id'),
				},
				function(response) {
					var result = $.parseJSON( response );
					if( result.batches_products.batches_operations_id.length > 0 ){
						loopToResponseBatch(null, false);
					}else{
						$('.control-button').removeAttr('disabled');
					}
					$('#product_update_spinner').removeClass('is-active');
				}
			);
		});

		$('#order_update').on('click', function(){
			$('.control-button').attr('disabled','disabled');
			$('#order_update_spinner').addClass('is-active');
			$('#batch-finish').html('');
			$.post(
				ajaxurl, {
					'action': 'init_shop',
					'button_id' : $(this).attr('id'),
					'order_time': $('#order_time').val()
				},
				function(response) {
					var result = $.parseJSON( response );
					if( result.batches_order.batches_operations_id.length > 0 ){
						loopToResponseBatch(null, false);
					}else{
						$('.control-button').removeAttr('disabled');
					}
					$('#order_update_spinner').removeClass('is-active');
				}
			);
		});

		$('#init_shop').on('click', function(){
			var force_reinit = 0;
			$('#init_shop').attr('disabled','disabled');
			$('#init_shop_spinner').addClass('is-active');
			if( $('#forceReinit').length > 0 ){
				force_reinit = ($('#forceReinit').is(':checked'))? 1 : 0;
			}
			$.post(
				ajaxurl, {
					'action': 'init_shop',
					'button_id' : $(this).attr('id'),
					'force_reinit' : force_reinit
				},
				function(response) {
					var result = $.parseJSON( response );
					if(result.init){
						location.reload();
					}else{
						if(result.hasOwnProperty('error_message')){
							$('#saveResult').html("<div id='saveMessage' class='notice notice-error is-dismissible'></div>");
							$('#saveMessage').append("<p>" + result.error_message + "</p><button id='delete-message' type='button' class='notice-dismiss'><span class='screen-reader-text'><?php echo esc_html__('Nascondi questa notifica','adv_dem'); ?>.</span></button>").show();
							$('#delete-message').on('click',function(){
								$('#saveMessage').hide('slow');
							});
						}

						$('#saveResult2').html("<div id='saveMessage2' class='notice notice-error is-dismissible'></div>");
						$('#saveMessage2').append("<p><?php echo esc_html__('ATTENZIONE: per reinizializzare il modulo è necessario spuntare la casella di controllo prima di procedere.','adv_dem'); ?></p><button id='delete-message2' type='button' class='notice-dismiss'><span class='screen-reader-text'><?php echo esc_html__('Nascondi questa notifica','adv_dem'); ?>.</span></button>").show();
						$('#delete-message2').on('click',function(){
							$('#saveMessage2').hide('slow');
						});
						$('#init_shop').removeAttr('disabled','disabled');
						$('#init_shop_spinner').removeClass('is-active');
					}
				}
			);
		});

		$('#save_option').on('click', function(){
			var active_attribute = {};
			$('#batch-finish').html('');
			$('.control-button').attr('disabled','disabled');
			$('#save_option_spinner').addClass('is-active');
			$('.toggle.active').each(function(index){
				active_attribute[index] = $(this).attr('attribute-id');
			});
			$.post(
				ajaxurl, {
					'action': 'init_shop',
					'button_id' : $(this).attr('id'),
					'active_attribute': active_attribute,
				},
				function(response) {
					var result = $.parseJSON( response );
					if(result.needProductAlign){
						message = '<?php echo htmlentities(esc_html__('Impostazioni salvate, prodotti in riallineamento','adv_dem'),ENT_QUOTES); ?>';
						messageClass= 'notice-success';
						var batchesNumber = result.batches_products.batches_operations_id.length;
						//if(result.batches_order) batchesNumber = batchesNumber + result.batches_order.batches_operations_id.length;
						if(batchesNumber > 0) {
							loopToResponseBatch(null, false);
						}
					}else{
						message = '<?php echo htmlentities(esc_html__('Impostazioni salvate','adv_dem'),ENT_QUOTES); ?>';
						messageClass= 'notice-success';
						$('.control-button').removeAttr('disabled');
					}
					$('#saveResult').html("<div id='saveMessage' class='notice "+messageClass+" is-dismissible'></div>");
					$('#saveMessage').append("<p>"+message+"</p><button id='delete-message' type='button' class='notice-dismiss'><span class='screen-reader-text'><?php echo esc_html__('Nascondi questa notifica','adv_dem'); ?>.</span></button>").show();
					$('#delete-message').on('click',function(){
						$('#saveMessage').hide('slow');
					});
					$('#save_option_spinner').removeClass('is-active');
				}
			);
		});


		/*VECCHIA OPERAZIONE CON TUTTO IN UNO*/
		// $('#init-cart-button').on('click',function(){
		// 	$('#init-cart-button').attr('disabled','disabled');
		// 	$('#syncro-spinner').addClass('is-active');
		// 	$('#batch-finish').html('');
		// 	var active_attribute = {};
		// 	var force_reinit = ($('#forceReinit').is(':checked'))? 1 : 0;
		// 	$('.toggle.active').each(function(index){
		// 		active_attribute[index] = $(this).attr('attribute-id');
		// 	});
		// 	$.post(
		// 		ajaxurl, {
		// 			'action': 'init_shop',
		// 			'active_attribute': active_attribute,
		// 			'force_reinit' : force_reinit
		// 		},
		// 		function(response) {
		// 			var result = $.parseJSON( response );
		// 			var message = '';
		// 			var messageClass= '';
		// 			if(result.init){
		// 				message = '<?php echo htmlentities(esc_html__('Lista inizializzata','adv_dem'),ENT_QUOTES); ?>';
		// 				messageClass= 'notice-success';
		// 			}
		// 			if(result.needProductAlign){
		// 				message = '<?php echo htmlentities(esc_html__('Impostazioni salvate, prodotti in riallineamento','adv_dem'),ENT_QUOTES); ?>';
		// 				messageClass= 'notice-success';
		// 				var batchesNumber = result.batches_products.batches_operations_id.length;
		// 				if(result.batches_order) batchesNumber = batchesNumber + result.batches_order.batches_operations_id.length;
		// 				if(batchesNumber > 0) {
		// 					loopToResponseBatch(null, false);
		// 				}
		// 			}else{
		// 				message = '<?php echo htmlentities(esc_html__('Impostazioni salvate','adv_dem'),ENT_QUOTES); ?>';
		// 				messageClass= 'notice-success';
		// 			}
		// 			$('#saveResult').html("<div id='saveMessage' class='notice "+messageClass+" is-dismissible'></div>");
		// 			$('#saveMessage').append("<p>"+message+"</p><button id='delete-message' type='button' class='notice-dismiss'><span class='screen-reader-text'><?php echo esc_html__('Nascondi questa notifica','adv_dem'); ?>.</span></button>").show();
		// 			$('#delete-message').on('click',function(){
		// 				$('#saveMessage').hide('slow');
		// 			});
		// 			$('#syncro-spinner').removeClass('is-active');
		// 			$('#init-cart-button').removeAttr('disabled');
		// 		}
		// 	);
		// });

		function loopToResponseBatch(operationNumberSection, inloop) {
 			if( inloop == false && $('#progress-container').hasClass('active') ){
				return;
			}
			$('.control-button').attr('disabled', 'disabled');
			$.post(
				ajaxurl, {
					'action': 'verify_active_batch',
					'batch_Type' : 'ALL'
				},
				function(response2) {
					var result2 = $.parseJSON( response2 );
					var batchOperationNumber = parseInt(result2.completeBatches)+parseInt(result2.activeBatches);
					var numberOfSegment = (100/batchOperationNumber)-1;
					var progressbarinit = 100 * parseInt(result2.completeBatches) / parseInt(batchOperationNumber);
					if(operationNumberSection == null || result2.completeBatches != operationNumberSection) {
						operationNumberSection = result2.completeBatches;
					}
					var progressbarlength = 100 * parseInt(result2.completeBatches) / parseInt(batchOperationNumber);
					$('#batch-operation-division').html('('+result2.completeBatches+'/'+batchOperationNumber+')');
					if(result2.activeBatches != "0") {
						$('#progress-container').addClass('active');
						$('#progress-container').show();
						$('#batch-loader').show();
						var segments = '';
						var segmentClasses = '';
						var segmentCounter = -1;
						for(var i = 0; i < batchOperationNumber; i++) {
							if( i < parseInt(result2.completeBatches) ) {
								segmentClasses = 'segment-done';
								segmentCounter = i;
							}else{
								segmentClasses = '';
							}
							segments += '<div class="batch-segment segment-'+i+' '+segmentClasses+'" style="width: '+numberOfSegment+'%;"></div>'
						}
						$('#progressbar').html(segments);
						setTimeout(function(){
							$('.segment-'+(segmentCounter+1) ).addClass('segment-done');
							setTimeout( function(){
								$('.segment-'+(segmentCounter+1) ).removeClass('segment-done');
							},1400);
						},100);
						setTimeout(function(){ loopToResponseBatch(operationNumberSection, true); }, 3000);
					}else{
						if(inloop){
							$('#progress-container').removeClass('active');
							$('#progress-close').show();
							$('#batch-finish').html('<?php echo esc_html__('Operazioni terminate!','adv_dem'); ?>');
							$('#saveResult').html("<div id='saveMessage' class='notice notice-success is-dismissible'></div>");
							$('#saveMessage').append("<p><?php echo esc_html__('Operazioni terminate','adv_dem'); ?></p><button id='delete-message' type='button' class='notice-dismiss'><span class='screen-reader-text'><?php echo esc_html__('Nascondi questa notifica','adv_dem'); ?>.</span></button>").show();
							$('#delete-message').on('click',function(){
								$('#saveMessage').hide('slow');
							});
						}
						$('.batch-segment:last-child').addClass('segment-done');
						$('.control-button').removeAttr('disabled');
						$('#batch-loader').hide();
					}

				}
			);
		}

		$('#delete-message4').on('click',function(){
			$('#saveMessage4').hide('slow');
		});

		$('.tooltip_information').on('click',function(event){
			event.stopPropagation();
			var tooltip = $(this).children('.tooltip_information_text');
			tooltip.toggleClass('tooltip_visible');
			if(tooltip.hasClass('tooltip_visible')){
				tooltip.show('fast');
			}else{
				tooltip.hide('fast');
			}
		});

		$('.tooltip_information_text').on('mouseleave',function(){
			$(this).removeClass('tooltip_visible');
			$(this).hide('fast');
		});

		$(document).on('click', function(){
			$('.tooltip_information_text').removeClass('tooltip_visible')
			$('.tooltip_information_text').hide('slow');
		});

		$('#progress-close').on('click',function(){
			$('#progress-container').hide();
			$(this).hide();
		});

	});
</script>
<style>
	.statusErrorHide{
		display: none;
	}
	.statusErrorVisible{
		display: block;
	}
	.statusUpdateHide{
		display: none;
	}
	.statusUpdateVisible{
		display: block;
	}
</style>
<!-- fine SELEZIONE MULTIPLA PROPRIETA DA IMPORTARE -->
