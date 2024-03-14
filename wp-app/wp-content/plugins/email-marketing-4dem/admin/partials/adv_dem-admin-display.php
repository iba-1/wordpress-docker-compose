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
<?php
	$checkApikey = $this->check_apikey();
	$recipientIntegrity = $this->checkRecipientIntegrity();
?>

<div class="wrap adv_wrapper">
	<h1><span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors(); ?>
	<div id="saveResult"></div>

	<div class="postbox adv_section adv_dem_logo" style="background-image: url('<?php echo esc_url(ADV_DEM_COMPANY_LOGO); ?>')">

		<h2>
			<span class="dashicons dashicons-admin-network"></span>
			<?php esc_attr_e( 'API Key', 'wp_admin_style' ); ?>

		</h2>
		<label>

		</label>
		<?php
			//Grab all options
			$options = get_option($this->plugin_name);
			// Configurazioni e preimpostazione form
			$apikey = isset($options['apikey']) ? $options['apikey'] : "" ;
			$entrypoint = isset($options['entrypoint']) ? $options['entrypoint'] : "" ;
			$syncroRecipientId = isset($options['syncroRecipientId']) ? $options['syncroRecipientId'] : "";
			$initPluginDate = isset($options['initPluginDate']) ? $options['initPluginDate'] : "" ;

		?>

		<?php
			settings_fields($this->plugin_name);
			do_settings_sections($this->plugin_name);
		?>
		<table>
			<?php if($initPluginDate != ""): ?>
				<tr>
					<th><span><?php echo esc_html__("Plugin :","adv_dem"); ?></span></th>
                    <th class="init-table"><span class="adv_success"><?php echo esc_html("ATTIVATO IN DATA: ".$initPluginDate) ?></span></th>
				</tr>
			<?php else: ?>
				<tr>
					<th><span><?php echo esc_html__("Plugin :","adv_dem"); ?></span></th>
					<th class="init-table"><span class="adv_error"><?php echo esc_html__("NON ATTIVO","adv_dem");?></span></th>
				</tr>
			<?php endif ?>
			<tr>
				<th><span><?php echo esc_html__('API Key','adv_dem'); ?> : </th>
				<th>
					<span class="apikey-response">
						<?php echo ($checkApikey)? '<span class="adv_success">'. esc_html__("CORRETTA","adv_dem").'</span>' : '<span class="adv_error">'.esc_html__("ERRATA O MANCANTE","adv_dem").'</span>' ?></span>
					</span>
				</th>
			</tr>
			<tr>
				<th><span><?php echo esc_html__("Inserisci l'url delle API della console:","adv_dem"); ?> </span> </span> <span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("Inserisci l'url per collegarti alle API della console di 4Dem. Esempio : https://api.4dem.it","adv_dem"); ?></div></span></th>
				<th>
                    <input type="text" name="entrypoint" id="entrypoint" value="<?php echo esc_attr($entrypoint) ?>" class="regular-text"/><br>
				</th>
			</tr>
			<tr>
				<th><span><?php echo esc_html__("Inserisci la tua API Key:","adv_dem"); ?> </span> </span> <span class="dashicons dashicons-info tooltip_information"><div class="tooltip_information_text"><?php echo esc_html__("In questo campo inserisci la API Key generata all'interno della sezione dedicata che trovi nella sezione Strumenti della console.","adv_dem"); ?></div></span></th>
				<th>
                    <input type="text" name="apikey" id="apikey" value="<?php echo esc_attr($apikey) ?>" class="regular-text"/><br>
				</th>
			</tr>

		</table>

		<div id="congratulation">
			<button id="delete-congratulation" type="button" class="notice-dismiss" ><span class="screen-reader-text"></span></button>
			<h1>
				<?php echo esc_html__("Congratulazioni!!!","adv_dem"); ?>
			</h1>
			<h2>
				<?php echo esc_html__("Nuova lista creata con successo sulla tua console:","adv_dem"); ?>
			</h2>
			<h2>
				<span id="list-name"></span>
			</h2>
			<div id="congratulation-image"></div>
		</div>

		<div id="correct-apikey">

			<?php if ( $checkApikey && $recipientIntegrity == false ): ?>
				<div class="allert-box">
					<h2><?php echo esc_html__("ATTENZIONE! Non trovo più la lista.")?></h2>
					<p>
						<?php echo esc_html__("È possibile che la lista sia stata rimossa, danneggiata oppure l'API Key fa riferimento ad un altro utente della console.","adv_dem"); ?>
					</p>
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo esc_html__("Forza riattivazione del plugin","adv_dem"); ?></span></legend>
						<label for="forceReInit">
							<input name="forceReInit" type="checkbox" id="forceReInit" value="1" />
							<span><?php echo esc_html__("Forza la reinizializzazione del plugin","adv_dem"); ?></span>
						</label>
						<p><?php echo esc_html__("(se il plugin è attivo e forzi la riattivazione, verrà attivato da capo. I dati precedenti andranno persi. Questa azione è consigliata in caso di liste compromesse).","adv_dem"); ?></p>
					</fieldset>
				</div>
			<?php endif ?>

		</div>
		<p class="submit">
			<button class="button button-primary control-button" id="init-button"><?php echo ($syncroRecipientId)? esc_html__("Aggiorna API Key","adv_dem") : esc_html__("Attiva il plugin","adv_dem") ; ?></button>
			<span id="init-spinner" class="spinner" style="float:none;width:auto;padding:0px 0 10px
50px;background-position:20px 0;"><?php echo esc_html__("Operazione in corso...","adv_dem") ?></span>
		</p>

	</div>
	<div id="progress-container">
		<div id="progress-close"><span class="dashicons dashicons-dismiss progress-close-icon"></span></div>
		<div id="progressbar" class="" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="20"><div id="innerbar" class="" style="width: 0px;"></div></div>
		<p id="batch-loader"><?php echo esc_html__("operazione in corso","adv_dem")?>... <span id="batch-operation-division"></span><span id="syncro-spinner" class="spinner is-active" style="float:none;width:auto;padding:0px 0 0px 50px;background-position:0px 0;"></span></p>
		<div id="batch-finish"></div>
	</div>
</div>
<script>
	jQuery(document).ready(function($) {
		loopToResponseBatch(null, false);

		$('#apikey, #entrypoint').on('change', function() {
			$('.apikey-response').html('<span id="syncro-spinner" class="spinner is-active" style="float:none;width:auto;height:auto;padding:0 0 10px 50px;background-position:20px 0;"> <?php echo esc_html__("Verifica in corso...","adv_dem"); ?></span>');
			var entrypoint = $('#entrypoint').val().trim();
			var apikey = $('#apikey').val();
			if (entrypoint == "") {
				entrypoint = "https:\/\/api.4dem.it";
			}
			$.post(
				ajaxurl, {
					'action': 'verify_apikey',
					'data': { 'api_key': apikey,'entrypoint':entrypoint }
				},
				function(response) {
					var result = $.parseJSON(response);
					if(result && result.success == true){
						$('.apikey-response').html('<span class="adv_success"><?php echo esc_html__("CORRETTA","adv_dem"); ?></span>');
					} else if (result && result.isEmpty == true) {
						$('.apikey-response').html('<span class="adv_error"><?php echo esc_html__("MANCANTE","adv_dem"); ?></span>');
					}else {
						$('.apikey-response').html('<span class="adv_error"><?php echo esc_html__("ERRATA","adv_dem"); ?></span>');
					}
				}
			);
		});

		$('#delete-congratulation').on('click',function(){
			$('#congratulation').hide('fast');
		})

		$('#init-button').on('click',function(){
			event.preventDefault();
			$('#init-button').attr('disabled', 'disabled');
			$('#init-spinner').addClass('is-active');
			var apikey = $('#apikey').val();
			var forceReInit = "0";
			var entrypoint = $('#entrypoint').val().trim();
			console.log(entrypoint)
			if (entrypoint == "") {
				entrypoint = "https:\/\/api.4dem.it";
			}
			if($('#forceReInit').length > 0) {
				forceReInit = ($('#forceReInit').is(':checked'))? "1" : "0";
			}
			$.post(
				ajaxurl, {
					'action': 'init_plugin',
					'data': { 'apikey': apikey, 'forceReInit': forceReInit, 'entrypoint':entrypoint }
				},
				function(response) {
					var result = $.parseJSON( response );
					if(result.success && forceReInit == "1"){
						$('#correct-apikey').hide();
					}
					if(result.success) {
						$('.apikey-response').html('<span class="adv_success"><?php echo esc_html__("CORRETTA","adv_dem"); ?></span>');
					}else{
						$('.apikey-response').html('<span class="adv_error"><?php echo esc_html__("ERRATA O MANCANTE","adv_dem"); ?></span>');
					}
					console.log(response);
					var noticeClass = (result.success)? 'success' : 'error';
					if(result.newList){
						$('.init-table').html('<span class="adv_success"><?php echo esc_html__("ATTIVATO IN DATA: ") ?>'+result.newList.initDate+'</span>');
						$('#init-button').html( '<?php echo esc_html__("Aggiorna API Key","adv_dem"); ?>' );
						$('#congratulation span#list-name').html(result.newList.listName);
						$('#congratulation').show('fast');
					}
					$('#saveResult').html("<div id='saveMessage' class='notice notice-"+noticeClass+" is-dismissible'></div>");
					$('#saveMessage').append("<p>"+result.message+"</p><button id='delete-message' type='button' class='notice-dismiss'><span class='screen-reader-text'><?php echo esc_html__('Nascondi questa notifica','adv_dem'); ?>.</span></button>").show();
					$('#delete-message').on('click',function(){
						$('#saveMessage').hide('slow');
					});
					$('#init-spinner').removeClass('is-active');
					$('#init-button').removeAttr('disabled');
				}
			);
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

		$('#progress-close').on('click',function(){
			$('#progress-container').hide();
			$(this).hide();
		});

	});
</script>
