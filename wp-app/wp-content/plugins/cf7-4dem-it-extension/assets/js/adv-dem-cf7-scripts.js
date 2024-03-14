jQuery(document).ready(function() {



        if (!jQuery('#wpcf7-adv-dem-cf-active').is(':checked'))

            jQuery('.adv-dem-custom-fields').hide();

        jQuery('#wpcf7-adv-dem-cf-active').click(function() {

            if (jQuery('.adv-dem-custom-fields').is(':hidden') &&
                jQuery('#wpcf7-adv-dem-cf-active').is(':checked')) {

                jQuery('.adv-dem-custom-fields').slideDown('fast');
            } else if (jQuery('.adv-dem-custom-fields').is(':visible') &&
                jQuery('#wpcf7-adv-dem-cf-active').not(':checked')) {

                jQuery('.adv-dem-custom-fields').slideUp('fast');
                jQuery(this).closest('form').find(".adv-dem-custom-fields input[type=text]").val("");

            }

        });

        jQuery(".adv-dem-trigger2").click(function() {
            jQuery(".adv-dem-support2").slideToggle("fast");
            return false; //Prevent the browser jump to the link anchor
        });


        jQuery(".adv-dem-trigger3").click(function() {
            jQuery(".adv-dem-support3").slideToggle("fast");
            return false; //Prevent the browser jump to the link anchor
        });



});