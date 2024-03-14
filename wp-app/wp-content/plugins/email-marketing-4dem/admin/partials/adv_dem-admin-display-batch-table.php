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


<div class="wrap adv_wrapper_table">

	<h1><span class="dashicons dashicons-list-view"></span> <?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors(); ?>
	<?php 
	$checkApikey = $this->check_apikey();
	?>
	<?php if(!$checkApikey): ?>
			<div id="configuration-form-error" class="postbox adv_section adv_dem_logo" style="background-image: url('<?php echo esc_url(ADV_DEM_COMPANY_LOGO); ?>')"><div class="allert-box"><h2><?php echo esc_html__("Devi configurare l'API Key correttamente","adv_dem"); ?></h2></div></div>
	<?php else: ?>
	<div class="postbox adv_section adv_dem_logo" style="background-image: url('<?php echo esc_url(ADV_DEM_COMPANY_LOGO); ?>')">
		<h2><?php echo esc_html__("Tabella delle operazioni","adv_dem") ?></h2>
		<p><?php echo esc_html__("Qui sotto puoi vedere le operazioni di esportazione dati verso la console e il loro stato, generate dal sistema. In questo elenco non troverai l’aggiornamento dei dati legato alla sincronizzazione in tempo reale.","adv_dem") ?></p>
		<div id="progress-container">
			<div id="progressbar" class="" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="20"><div id="innerbar" class="" style="width: 0px;"></div></div>
			<p id="batch-loader"><?php echo esc_html__("Operazione in corso","adv_dem")?>... <span id="batch-operation-division"></span><span id="syncro-spinner" class="spinner is-active" style="float:none;width:auto;padding:0px 0 0px 50px;background-position:0px 0;"></span></p>
		</div>
		<div id="the-list">
			<?php

				$wp_list_table = new Batch_List_Table();

				$wp_list_table->set_table_before_after('','<button class="button-secondary" id="removeArchivedItem">'.esc_html__("Rimuovi Inattivi","adv_dem").'</button>');

				$wp_list_table->prepare_items();

				//Table of elements
				$wp_list_table->display();
			?>
		</div>
	</div>
	<?php $generic_site_url = get_site_url() . '/wp-json/adv_dem_callback/dump/' ?>
	<a class="button-primary" href= "<?php echo esc_url($generic_site_url)  ?>" ><?php echo esc_html__("Esporta configurazione attuale","adv_dem"); ?></a>
	</div>
	<?php endif ?>
<script>
	jQuery(document).ready(function($){
		loopToResponseBatch(null, false);
		$('.table-batch-delete').on('click', function(){
			var batch_Id = $(this).attr('data-target');
			$.post(
				ajaxurl, {
					'action': 'delete_batch',
					'data' : {'batch_Id': batch_Id}
				},
				function(response) {
					var result = $.parseJSON( response );
					location.reload();
				}
			);
		});

		$('#removeArchivedItem, #removeAllBatches').on('click', function(){
			var batch_Id = $(this).attr('id');
			$.post(
				ajaxurl, {
					'action': 'delete_batch',
					'data' : {'batch_Id': batch_Id}
				},
				function(response) {
					var result = $.parseJSON( response );
					location.reload();
				}
			);
		});

		function loopToResponseBatch(operationNumberSection, inloop) {
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
						if( operationNumberSection != null ){location.reload();}
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
						setTimeout(function(){
							$('.segment-'+(segmentCounter+1) ).addClass('segment-done');
							setTimeout( function(){
								$('.segment-'+(segmentCounter+1) ).removeClass('segment-done');
							},1400);
						},100);
						setTimeout(function(){ loopToResponseBatch(operationNumberSection, true); }, 3000);
					}else{
						$('#batch-loader').hide();
						if(inloop){
							location.reload();
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


	});
</script>