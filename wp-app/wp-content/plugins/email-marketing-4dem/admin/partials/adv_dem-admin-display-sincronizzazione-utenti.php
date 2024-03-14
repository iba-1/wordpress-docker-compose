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

<div class="wrap adv_wrapper">
	<div id="icon-options-general" class="icon32"></div>
	<h1><span class="dashicons dashicons-networking"></span> <?php echo esc_html( get_admin_page_title() ); ?></h1>
  	<?php settings_errors(); ?>
	<div id="saveResult"></div>
	<?php
		//Grab all options
		$optionsLoc = get_option($this->plugin_name . "sincronizza_utenti");
		// Configurazioni
		$syncroAuto = ( isset( $optionsLoc['syncroAuto'] ) )? $optionsLoc['syncroAuto'] : "1";
		$forceSubscribe = ( isset( $optionsLoc['forceSubcribe'] ) )? $optionsLoc['forceSubcribe'] : "0" ;
		$syncromessage = ( isset( $optionsLoc['syncromessage'] ) )? $optionsLoc['syncromessage'] : "" ;
		$syncroPosition = ( isset( $optionsLoc['syncroPosition'] ) )? $optionsLoc['syncroPosition'] : "woocommerce_checkout_before_customer_details" ;
		$checkApikey = $this->check_apikey();
	?>
	<?php if(!$checkApikey): ?>
		<div id="configuration-form-error" class="postbox adv_section adv_dem_logo" style="background-image: url('<?php echo esc_url(ADV_DEM_COMPANY_LOGO); ?>')"><div class="allert-box"><h2><?php echo esc_html__("Devi configurare l'API Key correttamente","adv_dem"); ?></h2></div></div>
	<?php else: ?>
	<div id="configuration-form">
		<div class="postbox adv_section">
			<h2><span class="dashicons dashicons-admin-tools"></span> <?php echo esc_html__("Preferenze di sincronizzazione","adv_dem"); ?></h2>

			<form method="post" name="sincronizzazione_options" action="options.php" >
				<?php
					settings_fields($this->plugin_name . "sincronizza_utenti");
					do_settings_sections($this->plugin_name . "sincronizza_utenti");
				?>
				<table>
						<!--VERIFICA PRESENZA WOOCOMMERCE-->
					<?php if( class_exists( 'WooCommerce' )) : ?>
						<tr>
							<th><?php echo esc_html__("WooCommerce","adv_dem"); ?> : </th>
							<th><?php echo "<span class='adv_success'>".esc_html__('ATTIVO','adv_dem')."</span>"; ?></th>
						</tr>
					<?php endif ?>
					<tr>
						<th for="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[forceSubcribe]">
							<?php echo esc_html__("Sincronizzazione dati attiva: ","adv_dem"); ?> <span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Se selezionato attiva verranno sincronizzati i dati di utenti e ordini sulla piattaforma. La scelta dell’impostazione disattiva equivale alla disattivazione del plugin senza la perdita di dati di configurazione su wordpress e sulla piattaforma.","adv_dem"); ?></div></span>
						</th>

						<th>
							<select class="right-col" name="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[syncroAuto]" id="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>-syncroAuto">
								<option value="1" <?php if($syncroAuto == 1): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Attivata (consigliato)","adv_dem"); ?></option>
								<option value="0" <?php if($syncroAuto == 0): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Disattivata","adv_dem"); ?></option>
							</select>
						</th>
					</tr>
					<tr class="syncro-on">
						<th for="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[forceSubcribe]">
							<?php echo esc_html__("Modalità di iscrizione: ","adv_dem"); ?> <span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("\"Richiedi iscrizione\" - Ti permette di richiedere all’utente, tramite una checkbox, se vuole iscriversi.","adv_dem"); ?><br><?php echo esc_html__("\"Iscrivi immediatamente\" - Il contatto viene inserito immediatamente in lista come iscritto.","adv_dem"); ?><br><?php echo esc_html__("FAI ATTENZIONE! Se l’utente non ha personalmente confermato l’iscrizione, per legge, non puoi inviargli comunicazioni di alcun genere.","adv_dem"); ?></div></span>
						</th>
						<th>
							<select class="right-col" name="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[forceSubcribe]" id="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>-forceSubcribe">
							<option value="0" <?php if($forceSubscribe == 0): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Richiedi iscrizione","adv_dem"); ?></option>
								<option value="1" <?php if($forceSubscribe == 1): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Iscrivi immediatamente","adv_dem"); ?></option>
							</select>
						</th>

					</tr>
					<?php if(class_exists( 'WooCommerce' )): ?>
						<tr class="with-permission syncro-on">
							<th for="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[syncromessage]">
								<span><?php echo esc_html__("Cosa scrivere nella checkbox di autorizzazione:","adv_dem"); ?> </span>
								<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Inserisci il testo da visualizzare affianco alla checkbox di autorizzazione di iscrizione alla newsletter presente nel form di WooCommerce. L’opzione è attiva solamente se hai inserito gli utenti in modalità \"Richiedi iscrizione\".","adv_dem"); ?></div></span>
							</th>
							<th>
								<input class="right-col regular-text" type="text" name="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[syncromessage]" id="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>-syncromessage" value="<?php echo esc_attr($syncromessage); ?>" class="regular-text"/><br>
							</th>
						</tr>

						<tr class="with-permission syncro-on">
							<th for="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[syncroPosition]">
								<span><?php echo esc_html__("Dove posizionare la checkbox di autorizzazione:","adv_dem"); ?> </span>
								<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Scegli dove posizionare la checkbox di iscrizione alla newsletter all'interno del form di WooCommerce. L’opzione è attiva solamente se hai inserito gli utenti in modalità \"Richiedi iscrizione\".","adv_dem"); ?></div></span>
							</th>
							<th>
								<select class="right-col" name="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>[syncroPosition]" id="<?php echo esc_attr($this->plugin_name . "sincronizza_utenti"); ?>-syncroPosition">
									<option value="woocommerce_checkout_before_customer_details" <?php if($syncroPosition == "woocommerce_checkout_before_customer_details"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Prima dei dettagli utente","adv_dem"); ?></option>
									<option value="woocommerce_checkout_after_customer_details" <?php if($syncroPosition == "woocommerce_checkout_after_customer_details"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Dopo i dettagli dell'utente","adv_dem"); ?></option>
									<option value="woocommerce_review_order_before_submit" <?php if($syncroPosition == "woocommerce_review_order_before_submit"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Prima del bottone degli ordini","adv_dem"); ?></option>
									<option value="woocommerce_review_order_after_submit" <?php if($syncroPosition == "woocommerce_review_order_after_submit"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Dopo il bottone degli ordini","adv_dem"); ?></option>
									<option value="woocommerce_review_order_before_order_total" <?php if($syncroPosition == "woocommerce_review_order_before_order_total"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Prima del totale degli ordini","adv_dem"); ?></option>
									<option value="woocommerce_checkout_billing" <?php if($syncroPosition == "woocommerce_checkout_billing"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Nella pagina di fatturazione","adv_dem"); ?></option>
									<option value="woocommerce_checkout_shipping" <?php if($syncroPosition == "woocommerce_checkout_shipping"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Nella pagina di spedizione","adv_dem"); ?></option>
									<option value="woocommerce_after_checkout_billing_form" <?php if($syncroPosition == "woocommerce_after_checkout_billing_form"): ?>selected="selected"<?php endif ?>><?php echo esc_html__("Dopo il form della pagina di fatturazione","adv_dem"); ?></option>
								</select>
							</th>
						</tr>
					<?php endif ?>
				</table>

				<?php submit_button(esc_html__("Aggiorna Preferenze","adv_dem")); ?>
			</form>
		</div>
		<div class="postbox adv_section adv_dem_logo" style="background-image: url('<?php echo esc_url(ADV_DEM_COMPANY_LOGO); ?>')">
			<h2><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html__("Sincronizzazione manuale","adv_dem"); ?></h2>

			<?php if ( $this->checkRecipientIntegrity() == true ): ?>
				<p><?php echo esc_html__("Scegli come salvare i tuoi utenti Wordpress sulla lista della console ed esegui una sincronizzazione manuale.","adv_dem"); ?><br>
					<?php echo esc_html__("In questo modo tutti gli utenti registrati su Wordpress verranno inseriti nella lista della console creata.","adv_dem"); ?><br>
					<?php echo esc_html__("Imposta questo tipo di sincronizzazione se il plugin WP-Integration è attivo, se su Wordpress ci sono utenti già registrati e se vuoi partire con la lista aggiornata. La sincronizzazione manuale serve anche per trasferire i dati alla console nel caso la modalità automatica non sia attiva.","adv_dem"); ?>

				</p>
				<form id="manual-syncro-form">
					<table>
						<tr>
							<th>
								<?php echo esc_html__("Modalità di iscrizione:","adv_dem"); ?>
								<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("\"Richiedi iscrizione\" - Ti permette di richiedere all’utente, tramite una checkbox, se vuole iscriversi.","adv_dem"); ?><br><?php echo esc_html__("\"Iscrivi immediatamente\" - Il contatto viene inserito immediatamente in lista come iscritto.","adv_dem"); ?><br><?php echo esc_html__("\"Aggiorna solo i campi personalizzati\" scegli questa voce se hai già fatto una prima sincronizzazione. In questo modo aggiorni tutti i campi personalizzati senza modificare la modalità di inserimento ed eviti di forzare tutte le iscrizioni già effettuate in passato. Per gli utenti nuovi sarà impostato come predefinito \"Subscribed\".","adv_dem"); ?></div></span>
							</th>
							<th>
								<select class="right-col" name="subscribeMode" id="subscribeMode">
									<option value="" selected="selected" disabled><?php echo esc_html__("Seleziona la modalità di iscrizione","adv_dem"); ?></option>
									<option value="Opt-In Pending"><?php echo esc_html__("Richiedi iscrizione","adv_dem"); ?></option>
									<option value="Subscribed"><?php echo esc_html__("Iscrivi immediatamente","adv_dem"); ?></option>
									<option value="ignore"><?php echo esc_html__("Aggiorna solo i campi personalizzati","adv_dem"); ?></option>
								</select>
							</th>
						</tr>
						<tr class="subscribe-mode-ignore">
							<th>
								<?php echo esc_html__("Se l’utente è già iscritto alla lista:","adv_dem"); ?>
								<span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("“Aggiorna i campi personalizzati” - verranno aggiornati i campi personalizzati degli utenti già presenti in lista. Questa azione sovrascrive i campi personalizzati con gli ultimi inseriti.","adv_dem"); ?><br><?php echo esc_html__("“Non aggiornare i campi personalizzati” - non verranno aggiornati i campi personalizzati degli utenti già presenti in lista. Sono mantenuti i campi impostati al momento della prima iscrizione.","adv_dem"); ?></div></span>
							</th>
							<th>
								<select class="right-col" name="updateIfDuplicate" id="updateIfDuplicate">
									<option value="1" selected="selected"><?php echo esc_html__("Aggiorna i campi personalizzati","adv_dem"); ?></option>
									<option value="0"><?php echo esc_html__("Non aggiornare i campi personalizzati","adv_dem"); ?></option>
								</select>
							</th>
						</tr>
					</table>
					<p class="submit">
						<button class="button button-primary" id="sync-button" disabled><?php echo esc_html__("Avvia Sincronizzazione","adv_dem"); ?></button>
						<span id="syncro-spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:20px 0;"><?php echo esc_html__("Preparazione dell'operazione in corso...","adv_dem") ?></span>
					</p>
				</form>
				<div id="progress-container">
					<div id="progress-close"><span class="dashicons dashicons-dismiss progress-close-icon"></span></div>
					<div id="progressbar" class="" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="20"><div id="innerbar" class="" style="width: 0px;"></div></div>
					<p id="batch-loader"><?php echo esc_html__("operazione in corso","adv_dem")?>... <span id="batch-operation-division"></span><span id="syncro-spinner" class="spinner is-active" style="float:none;width:auto;padding:0px 0 10px 50px;background-position:0px 0;"></span></p>
					<div id="batch-finish"></div>
				</div>
			<?php else: ?>
				<div class="allert-box">
					<h2><?php echo esc_html__("Non ho trovato nessuna lista, oppure è stata danneggiata.","adv_dem"); ?></h2>
					<p><?php echo esc_html__("È possibile che l'API Key inserita sia corretta, ma riferita ad un altro utente.","adv_dem"); ?> <br><?php echo esc_html__("Modifica l'API Key oppure rimuovi la lista e riattiva il plugin nella pagina di configurazione API Key.","adv_dem"); ?> </p>
				</div>
			<?php endif ?>
		</div>
		<?php endif ?>
	</div>

</div>
<script>
	jQuery(document).ready(function($) {
		loopToResponseBatch(null, false);

		if( $('#adv_demsincronizza_utenti-syncroAuto').val() == "0" ){
			$('.syncro-on').hide();
		}

		$('#adv_demsincronizza_utenti-syncroAuto').on('change', function(){
			if($(this).val() == "0"){
				$('.syncro-on').fadeOut(500);
			}else{
				$('.syncro-on').fadeIn(500);
				if( $('#adv_demsincronizza_utenti-forceSubcribe').val() == true ){
					$('.with-permission').hide();
				}
			}
		});

		if( $('#adv_demsincronizza_utenti-forceSubcribe').val() == true ){
			$('.with-permission').hide();
		}

		$('#adv_demsincronizza_utenti-forceSubcribe').on('change', function(){
			if($(this).val() == true){
				$('.with-permission').fadeOut(500);
			}else{
				$('.with-permission').fadeIn(500);
			}
		});

		$('#subscribeMode').on('change', function(){
			if($(this).val() != ""){
				$('#sync-button').removeAttr('disabled');
				($(this).val() == "ignore")? $('.subscribe-mode-ignore').fadeOut(500) : $('.subscribe-mode-ignore').fadeIn(500);
			}else{
				$('#sync-button').attr('disabled', 'disabled');
				$('.subscribe-mode-ignore').fadeOut(500);
			}
		});

		$('#sync-button').on('click',function(event){
			event.preventDefault();
			$('#batch-finish').html('');
			$('#sync-button').attr('disabled', 'disabled');
			$('#syncro-spinner').addClass('is-active');
			var subscribeMode = $('#subscribeMode').val();
			var updateIfDuplicate = $('#updateIfDuplicate').val();
			if(subscribeMode != ""){
				$.post(
					ajaxurl, {
						action: 'syncro_users_batch',
						data: { 'subscribe_mode': subscribeMode, 'update_if_duplicate': updateIfDuplicate }
					},
					function(response) {
						var result = $.parseJSON( response );
						console.log(response);
						/*TEST BATCH OPERATION RESPONSE*/
						if(result.batches_operations_id.length > 0) {
							loopToResponseBatch(null, false);
						}
						$('#syncro-spinner').removeClass('is-active');
						$('#sync-button').removeAttr('disabled');
					}
				);
			}else{
				$('#syncro-spinner').removeClass('is-active');
				$('#batch-button').removeAttr('disabled');
			}
		});


 		function loopToResponseBatch(operationNumberSection, inloop) {
			$('#sync-button').hide();
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
					if(operationNumberSection == null || parseInt(result2.completeBatches) != operationNumberSection) {
						operationNumberSection = result2.completeBatches;
					}
					var progressbarlength = 100 * parseInt(result2.completeBatches) / parseInt(batchOperationNumber);
					$('#batch-operation-division').html('('+result2.completeBatches+'/'+batchOperationNumber+')');
					if(result2.activeBatches != "0") {
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
						setTimeout( function(){
							$('.segment-'+(segmentCounter+1) ).addClass('segment-done');
							setTimeout( function(){
								$('.segment-'+(segmentCounter+1) ).removeClass('segment-done');
							},1400);
						},100);
						setTimeout(function(){ loopToResponseBatch(operationNumberSection, true); }, 3000);
					}else{
						$('.batch-segment:last-child').addClass('segment-done');
						$('#sync-button').show();
						$('#batch-loader').hide();
						if(inloop){
							$('#progress-close').show();
							$('#batch-finish').html('<?php echo esc_html__('Operazioni terminate!','adv_dem'); ?>');
							$('#saveResult').html("<div id='saveMessage' class='notice notice-success is-dismissible'></div>");
							$('#saveMessage').append("<p><?php echo esc_html__('Operazioni terminate.','adv_dem'); ?></p><button id='delete-message' type='button' class='notice-dismiss'><span class='screen-reader-text'><?php echo esc_html__('Nascondi questa notifica','adv_dem'); ?>.</span></button>").show();
							$('#delete-message').on('click',function(){
								$('#saveMessage').hide('slow');
							});
						}
					}

				}
			);
		}

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
			$('.tooltip_information_text').hide('fast');
		});

		$('#progress-close').on('click',function(){
			$('#progress-container').hide();
			$(this).hide();
		});

	});
</script>



<!--VARDUMP DELLE IMPOSTAZIONI-->
