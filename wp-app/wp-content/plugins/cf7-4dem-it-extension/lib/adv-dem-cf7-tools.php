<?php
function adv_dem_cf7_author() {

	$author_pre = esc_html__('Contact form 7 4Dem.it extension by ', ADV_DEM_CF7_TEXTDOMAIN);
	$author_name = '';
	$author_url = '';
	$author_title = '';

	$adv_dem_author = '<p style="display: none !important">';
  $adv_dem_author .= $author_pre;
  $adv_dem_author .= '<a href="'.$author_url.'" ';
  $adv_dem_author .= 'title="'.$author_title.'" ';
  $adv_dem_author .= 'target="_blank">';
  $adv_dem_author .= ''.$author_title.'';
  $adv_dem_author .= '</a>';
  $adv_dem_author .= '</p>'. "\n";

  return $adv_dem_author;
}



function adv_dem_cf7_referer() {
  if(isset($_SERVER['HTTP_REFERER'])) {
    $adv_dem_cf7_referer_url = $_SERVER['HTTP_REFERER'];
  } else {
        $adv_dem_cf7_referer_url = 'direct visit';
  }
	$adv_dem_cf7_referer = '<p style="display: none !important"><span class="wpcf7-form-control-wrap referer-page">';
  $adv_dem_cf7_referer .= '<input type="hidden" name="referer-page" ';
  $adv_dem_cf7_referer .= 'value="'.$adv_dem_cf7_referer_url.'" ';
  $adv_dem_cf7_referer .= 'size="40" class="wpcf7-form-control wpcf7-text referer-page" aria-invalid="false">';
  $adv_dem_cf7_referer .= '</span></p>'. "\n";

  return $adv_dem_cf7_referer;
}



function adv_dem_cf7_getRefererPage( $form_tag ) {
  if ( $form_tag['name'] == 'referer-page' ) {
    $form_tag['values'][] = $_SERVER['HTTP_REFERER'];
  }
  return $form_tag;
}



if ( !is_admin() ) {
  add_filter( 'wpcf7_form_tag', 'adv_dem_cf7_getRefererPage' );
}

add_action( 'init', 'adv_dem_cf7_init_constants' );
function adv_dem_cf7_init_constants(){
  define( 'ADV_DEM_URL', '' );
  define( 'ADV_DEM_AUTH', '' );
  define( 'ADV_DEM_AUTH_COMM', '<!-- campaignmonitor extension -->' );
  define( 'ADV_DEM_NAME', 'adv_dem Contact Form 7 Extension' );
  define( 'ADV_DEM_SETT', admin_url( 'admin.php?page=wpcf7&post='.adv_dem_cf7_get_latest_item().'&active-tab=4' ) );
  define( 'ADV_DEM_DON', '' );
}


function adv_dem_cf7_get_latest_item(){
    $args = array(
            'post_type'         => 'wpcf7_contact_form',
            'posts_per_page'    => -1,
            'fields'            => 'ids',
        );
    // Get Highest Value from CF7Forms
    $form = max(get_posts($args));
    $out = '';
    if (!empty($form)) {
        $out .= $form;
    }
    return $out;
}


function wpcf7_form_adv_dem_tags() {
  // $manager = WPCF7_FormTagsManager::get_instance();
  $manager = class_exists('WPCF7_FormTagsManager') ? WPCF7_FormTagsManager::get_instance() : WPCF7_ShortcodeManager::get_instance(); // ff cf7 46. and earlier
  $form_tags = $manager->get_scanned_tags();
  return $form_tags;
}


function adv_dem_cf7_mail_tags($type) {
  $listatags = wpcf7_form_adv_dem_tags();
  $tag_submit = array_pop($listatags);
  $tagInfo = '';
  switch($type) {
    
    case 'email':     foreach($listatags as $tag){
                        if($tag['basetype'] == 'email'){
                          $tagInfo .= '<span class="mailtag code used">[' . $tag['name'].']</span>';
                        }
                      }
                      break;
    
    case 'checkbox':    foreach($listatags as $tag){
                        if($tag['basetype'] == 'checkbox'){
                          $tagInfo .= '<span class="mailtag code used">[' . $tag['name'].']</span>';
                        }
                      }
                      break;
    case 'all':       foreach($listatags as $tag){
                        $tagInfo .= '<span class="mailtag code used">[' . $tag['name'].']</span>';
                      }
                      break;

    default: break;
  }

  return $tagInfo;

}




