<?php
/*  
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

global $wpdb;

$apikey = (isset($cf7_adv_dem['api']))? $cf7_adv_dem['api'] : "";
$check_apikey = check_apikey($apikey);
$recipientInfo = "";
$recipientList = array();
$dest_recipientId = (isset( $cf7_adv_dem['list']) ) ?  esc_attr( $cf7_adv_dem['list']) : '' ;
if($apikey != "" ){
    $api_adv = new Adv_dem_cf7_InterfaceAPI($apikey);
    if ($api_adv->getRequestSuccessful()) {
      $recipientList = $api_adv->getRecipients();
      if($dest_recipientId != ""){
        $customFields = $api_adv->getRecipientCustomFields($dest_recipientId);
      }
    }
}

?>

<h2><?php echo ADV_DEM_CF7_AGENCY_NAME." ".esc_html__('Extension', ADV_DEM_CF7_TEXTDOMAIN); ?> </h2>

<div class="adv-dem-main-fields">


  <p class="cf7-adv-dem-mail-field">
    <label for="wpcf7-adv-dem-api"><?php echo esc_html( esc_html__( 'APIKey:', ADV_DEM_CF7_TEXTDOMAIN ) ); ?> </label><br />
    <input type="text" id="wpcf7-adv-dem-api" name="wpcf7-adv-dem[api]" class="wide" size="70" placeholder=" " value="<?php echo $apikey; ?>" />
    <small class="cf7-adv-dem-description">f5a5-2a37-fe0c-bgfd-f101-0fgf-d562-5248 << <?php echo esc_html__("A number like this", ADV_DEM_CF7_TEXTDOMAIN); ?></small>
  </p>

  <p class="notapikey"><?php echo esc_html__("Insert a correct APIKey to work with 4Dem!", ADV_DEM_CF7_TEXTDOMAIN); ?></p>

  <div class="withapikey">
  <p class="cf7-adv-dem-mail-field">
    <label for="wpcf7-adv-dem-list"><?php echo esc_html__("Destination List:" , ADV_DEM_CF7_TEXTDOMAIN) ?></label><br />
    <select class="wide"  id="wpcf7-adv-dem-list" name="wpcf7-adv-dem[list]">
      <option value=""><?php echo esc_html__("NO LIST SELECTED" , ADV_DEM_CF7_TEXTDOMAIN) ?></option>
      <option value="newList"><?php echo esc_html__("NEW LIST" , ADV_DEM_CF7_TEXTDOMAIN) ?></option>
      <?php
        if( count($recipientList) > 0 ) {
          foreach ($recipientList['data'] as $recipient) {						
      ?>
        <option value="<?php echo $recipient["id"]; ?>" <?php echo ($dest_recipientId == $recipient["id"]) ? "selected" : "" ; ?> data-list-opt-in="<?php echo $recipient["opt_in"]["mode"] ; ?>" ><?php echo $recipient["name"]; ?></option>
    <?php }} ?>
    </select>
  </p>

  <p class="cf7-adv-dem-mail-field" id="new-list-name">
    <label for="wpcf7-adv-dem-newlist"><?php echo esc_html( esc_html__( 'List Name:', ADV_DEM_CF7_TEXTDOMAIN ) ); ?></label><br />
    <input type="text" id="wpcf7-adv-dem-newlist" class="wide" size="70" placeholder="<?php echo esc_html__("Insert the new list's name", ADV_DEM_CF7_TEXTDOMAIN) ?>" /><br /><br />
    <input type="checkbox" id="wpcf7-adv-dem-double-opt-in" value="1" />
    <label for="wpcf7-adv-dem-double-opt-in"><?php echo esc_html( esc_html__( 'Enable Double Opt-in (checked = true)', ADV_DEM_CF7_TEXTDOMAIN ) ); ?>  </label><br /><br />
    <button class="button-secondary" id="wpcf7-adv-dem-add-list-button"><?php echo esc_html__( 'ADD', ADV_DEM_CF7_TEXTDOMAIN ); ?></button>     
  </p>

  <p class="nolist">
    <?php echo esc_html__("List subscription mode: ", ADV_DEM_CF7_TEXTDOMAIN). '<span id="opt-in-mode"></span> ' . esc_html__("Opt-in", ADV_DEM_CF7_TEXTDOMAIN) ; ?>
  </p>

  <p class="cf7-adv-dem-mail-field nolist">
    <label for="wpcf7-adv-dem-email"><?php echo esc_html( esc_html__( 'Subscriber Email:', ADV_DEM_CF7_TEXTDOMAIN ) ); ?></label><br />
    <input type="text" id="wpcf7-adv-dem-email" name="wpcf7-adv-dem[email]" class="wide" size="70" placeholder="<?php echo esc_html__("insert email field like this [your-email]","ADV_DEM_CF7_TEXTDOMAIN") ; ?>" value="<?php echo (isset ( $cf7_adv_dem['email'] ) ) ? esc_attr( $cf7_adv_dem['email'] ) : ''; ?>" />
    <small class="cf7-adv-dem-description"><?php echo adv_dem_cf7_mail_tags('email'); ?> << <?php echo esc_html__("you can use these mail-tags",ADV_DEM_CF7_TEXTDOMAIN); ?></small>
  </p>

  <div class="cf7-adv-dem-advanced-fields" style="display:none">
    <div class="nolist"> 
      <p class="cf7-adv-dem-mail-field mt0">
        <label for="wpcf7-adv-dem-accept"><?php echo esc_html( esc_html__( 'Acceptance Field:', ADV_DEM_CF7_TEXTDOMAIN ) ); ?> </label><br />
        <input type="text" id="wpcf7-adv-dem-accept" name="wpcf7-adv-dem[accept]" class="wide" size="70" placeholder="<?php echo esc_html__("[opt-in] <= Leave Empty if you are NOT using the checkbox","ADV_DEM_CF7_TEXTDOMAIN") ; ?>" value="<?php echo (isset($cf7_adv_dem['accept'])) ? $cf7_adv_dem['accept'] : '';?>" />
        <small class="cf7-adv-dem-description"><?php echo adv_dem_cf7_mail_tags('checkbox'); ?>  << <?php echo esc_html__("you can use these mail-tags", ADV_DEM_CF7_TEXTDOMAIN); ?></small>
      </p>

      <p class="cf7-adv-dem-mail-field">
        <input type="checkbox" id="wpcf7-adv-dem-cf-active" name="wpcf7-adv-dem[cfactive]" value="1"<?php echo ( isset($cf7_adv_dem['cfactive']) ) ? ' checked="checked"' : ''; ?> />
        <label for="wpcf7-adv-dem-cf-active"><?php echo esc_html( esc_html__( 'Use Custom Fields', ADV_DEM_CF7_TEXTDOMAIN ) ); ?>  </label>
      </p>


      <div class="adv-dem-custom-fields">
        <p><?php echo esc_html__( 'In the following fields, you can use these mail-tags:', ADV_DEM_CF7_TEXTDOMAIN ); ?> <?php echo adv_dem_cf7_mail_tags('all'); ?></p>
        <div class="col-6 nolist">
          <label for="wpcf7-adv-dem-add-newcustomfieldname"><?php echo esc_html__( 'Add New Custom Field:', ADV_DEM_CF7_TEXTDOMAIN ); ?></label><br />
          <input type="text" class="wide" size="22" id="wpcf7-adv-dem-add-newcustomfieldname" placeholder="<?php echo esc_html__( 'New Custom Field Name', ADV_DEM_CF7_TEXTDOMAIN ); ?>">
          <button class="button-secondary" id="wpcf7-adv-dem-add-customfield-button"><?php echo esc_html__( 'ADD', ADV_DEM_CF7_TEXTDOMAIN ); ?></button>
        </div>

        <div id="new-custom-fields">
          <?php
            if( isset($customFields['data']) && count($customFields['data']) > 0 ) {
              $i= 0;
              foreach ($customFields['data'] as $singleField) {
              $saveCustomField = (isset( $cf7_adv_dem['CustomKey'.$i]) ) ?  esc_attr( $cf7_adv_dem['CustomKey'.$i] ) : '' ;						
          ?>
              <div class="col-6">
                  <input type="hidden" value="<?php echo $singleField["id"]; ?>" id="wpcf7-adv-dem-CustomKey<?php echo $i; ?>" name="wpcf7-adv-dem[CustomKey<?php echo $i; ?>]"></input>
                  <label for="wpcf7-adv-dem-CustomValue<?php echo $i; ?>"><?php echo $singleField["name"]; ?></label><br />
                  <input type="text" id="wpcf7-adv-dem-CustomValue<?php echo $i; ?>" name="wpcf7-adv-dem[CustomValue<?php echo $i; ?>]" class="wide" size="70" placeholder="[your-mail-tag]" value="<?php echo (isset( $cf7_adv_dem['CustomValue'.$i]) ) ?  esc_attr( $cf7_adv_dem['CustomValue'.$i] ) : '' ;  ?>" />
              </div>
              
          <?php $i++; }
            }else{ ?>
            <p><?php echo esc_html__( 'The list selected has not custom fields', ADV_DEM_CF7_TEXTDOMAIN ); ?></p>
          <?php }?>


        </div>



      </div>

    </div>

  </div>

    <p class="p-author nolist">
      <a type="button" aria-expanded="false" class="adv-dem-trigger cf7-adv-dem-support "><?php echo esc_html__("Show advanced settings", ADV_DEM_CF7_TEXTDOMAIN) ; ?></a> &nbsp; 
    </p>

    </div>

  <script>
    jQuery(document).ready(function(){

      jQuery(".cme-trigger-sys").click(function() {

        jQuery( "#toggle-sys" ).slideToggle(250);

      });

      if( jQuery('#wpcf7-adv-dem-list').val() == "newList" ){
        jQuery('#new-list-name').show('fast');
        jQuery('.nolist').hide('fast');
      }else if(jQuery('#wpcf7-adv-dem-list').val() == ""){
        jQuery('#new-list-name').hide('fast');
        jQuery('.nolist').hide('fast');
      }else{
        jQuery('#opt-in-mode').html(jQuery('#wpcf7-adv-dem-list option[value = "'+ jQuery('#wpcf7-adv-dem-list').val() +'"]').attr('data-list-opt-in'));
        jQuery('#new-list-name').hide('fast');
        jQuery('.nolist').show('fast');
      }

      if( "<?php echo $check_apikey; ?>" != "" ) {
        jQuery('.notapikey').hide();
        jQuery('.withapikey').show();
      }else{
        jQuery('.notapikey').show();
        jQuery('.withapikey').hide();
      }

      jQuery('#wpcf7-adv-dem-add-list-button').on('click',function(event){
        event.preventDefault();
        jQuery('.adv-msg').remove();
        jQuery(this).attr('disabled','disabled');
        var error = false;
        var apikey = '<?php echo $apikey ?>';
        var listName = jQuery('#wpcf7-adv-dem-newlist').val();
        var doubleOptIn = jQuery('#wpcf7-adv-dem-double-opt-in:checked').length;
        if(apikey == "") error = '<?php echo esc_html__("No APIKey", ADV_DEM_CF7_TEXTDOMAIN); ?>';
        if(listName == "") error = '<?php echo esc_html__("No list name", ADV_DEM_CF7_TEXTDOMAIN); ?>';
        if(!error){
          jQuery.post(
            ajaxurl, 
            {
              'action': 'adv_dem_cf7_add_list',
              'listName': listName ,
              'apikey': apikey,
              'doubleOptIn': doubleOptIn
            }, 
            function(response) {
              var result = jQuery.parseJSON(response);
              if(result.success){
                jQuery('#wpcf7-adv-dem-list').append('<option value="'+result.data+'">'+listName+'</option>');
                jQuery('#new-list-name').append('<p class="list-success-msg adv-msg" >'+result.message+'</p>'); 
                jQuery('#wpcf7-adv-dem-newlist').val("");
              }else{
                jQuery('#new-list-name').append('<p class="list-error-msg adv-msg" >'+result.message+'</p>');                    
              }
              jQuery('#wpcf7-adv-dem-add-list-button').removeAttr('disabled');
            }
          );
        }else{
          jQuery('#new-list-name').append('<p class="list-error-msg adv-msg" >'+error+'</p>');
          jQuery('#wpcf7-adv-dem-add-list-button').removeAttr('disabled');
        }
      });

      jQuery('#wpcf7-adv-dem-add-customfield-button').on('click',function(event){
        event.preventDefault();
        jQuery('.adv-msg').remove();
        jQuery(this).attr('disabled','disabled');
        var error = false;
        var listId = jQuery('#wpcf7-adv-dem-list').val();
        var apikey = '<?php echo $apikey ?>';
        var customFieldName = jQuery('#wpcf7-adv-dem-add-newcustomfieldname').val();
        if(customFieldName == "") error = '<?php echo esc_html__("Name is required to add a new custom field", ADV_DEM_CF7_TEXTDOMAIN); ?>';
        if(listId == "") error = '<?php echo esc_html__("No destination list selected", ADV_DEM_CF7_TEXTDOMAIN); ?>';
        if(apikey == "") error = '<?php echo esc_html__("APIKey not found", ADV_DEM_CF7_TEXTDOMAIN); ?>';
        if(!error){
          jQuery.post(
            ajaxurl, 
            {
              'action': 'adv_dem_cf7_add_customfields',
              'listId': listId ,
              'apikey': apikey,
              'customFieldName': customFieldName
            }, 
            function(response) {
              var result = jQuery.parseJSON(response);
              jQuery('#wpcf7-adv-dem-add-newcustomfieldname').val('');              
              jQuery('#wpcf7-adv-dem-add-customfield-button').removeAttr('disabled');          
              jQuery('#wpcf7-adv-dem-list').trigger('change');
            }
          );
        }else{
          jQuery('#wpcf7-adv-dem-add-customfield-button').after('<p class="list-error-msg adv-msg" >'+error+'</p>');                            
          jQuery('#wpcf7-adv-dem-add-customfield-button').removeAttr('disabled');
        }
        
      });


      jQuery('#wpcf7-adv-dem-list').on('change',function(){
        jQuery('.adv-msg').remove();
        if( jQuery(this).val() == "newList" ){
          jQuery('.nolist').hide('fast');
          jQuery('#new-list-name').show('fast');
          var optionTags = '<option value=""><?php echo esc_html__("The list selected has not custom fields" , ADV_DEM_CF7_TEXTDOMAIN) ?></option>';
          jQuery('#new-custom-fields').html(optionTags);
        }else if(jQuery(this).val() == ""){
          jQuery('.nolist').hide('fast');
          jQuery('#new-list-name').hide('fast');
        }else{
          jQuery('#opt-in-mode').html(jQuery('#wpcf7-adv-dem-list option[value = "'+ jQuery('#wpcf7-adv-dem-list').val() +'"]').attr('data-list-opt-in'));
          jQuery('#list-type').html(jQuery('#wpcf7-adv-dem-list option[value = "'+ jQuery('#wpcf7-adv-dem-list').val() +'"]').attr('data-list-type'));
          jQuery('.nolist').show('fast');
          jQuery('#new-list-name').hide('fast');
          var listId = jQuery(this).val();
          var apikey = '<?php echo $apikey ?>';
          jQuery.post(
            ajaxurl, 
            {
              'action': 'adv_dem_cf7_get_customfields',
              'listId': listId ,
              'apikey': apikey
            }, 
            function(response) {
              var result = jQuery.parseJSON(response);
              var customfieldsinputs = '';
              if(result.successAuth){
                if(result.success){
                  if(result.successlist){
                    jQuery('#opt-in-mode').html(result.listinfo.data.opt_in.mode);
                  }
                  if(result.customfields.data.length > 0){
                    for(var i = 0; result.customfields.data.length > i ; i++){
                      customfieldsinputs+='<div class="col-6"><input type="hidden" value="'+result.customfields.data[i]['id']+'" id="wpcf7-adv-dem-CustomKey'+i+'" name="wpcf7-adv-dem[CustomKey'+i+']"></input><label for="wpcf7-adv-dem-CustomValue'+i+'">'+result.customfields.data[i]['name']+'</label><br /><input type="text" id="wpcf7-adv-dem-CustomValue'+i+'" name="wpcf7-adv-dem[CustomValue'+i+']" class="wide" size="70" placeholder="[your-mail-tag]" value="" /></div>'
                    }
                    jQuery('#new-custom-fields').html(customfieldsinputs);
                  }else{
                    customfieldsinputs = '<p><?php echo esc_html__("The list selected has not custom fields" , ADV_DEM_CF7_TEXTDOMAIN) ?></p>';
                    jQuery('#new-custom-fields').html(customfieldsinputs);
                  }
                }else{
                    jQuery('#wpcf7-adv-dem-list').val("");
                    jQuery('#wpcf7-adv-dem-list').trigger("change");          
                    jQuery('#wpcf7-adv-dem-list option[value='+listId+']').remove();
                    jQuery('#wpcf7-adv-dem-list').after('<p class="list-error-msg adv-msg" ><?php echo esc_html__("List not found in console, removed from the list", ADV_DEM_CF7_TEXTDOMAIN); ?></p>');
                }    
              }else{
                //console.log('auth error');
              }
            }
          );
        }
      });

      jQuery(".adv-dem-trigger").click(function() {

          jQuery(".cf7-adv-dem-advanced-fields").slideToggle("fast");

          jQuery(this).text(function(i, text) {
              return text === "<?php echo esc_html__("Show advanced settings", ADV_DEM_CF7_TEXTDOMAIN) ; ?>" ? "<?php echo esc_html__("Hide advanced settings", ADV_DEM_CF7_TEXTDOMAIN) ; ?>" : "<?php echo esc_html__("Show advanced settings", ADV_DEM_CF7_TEXTDOMAIN) ; ?>";
          });

          return false; //Prevent the browser jump to the link anchor

      });

    });

  </script>
</div>





