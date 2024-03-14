<?php
    require_once( ADV_DEM_CF7_PLUGIN_DIR . '/lib/adv-dem-cf7-activate.php' );
    require_once( ADV_DEM_CF7_PLUGIN_DIR . '/lib/adv-dem-cf7-enqueue.php' );
    require_once( ADV_DEM_CF7_PLUGIN_DIR . '/lib/adv-dem-cf7-tools.php' );
    require_once( ADV_DEM_CF7_PLUGIN_DIR . '/lib/adv-dem-cf7-functions.php' );

    if (!class_exists('Adv_dem_cf7_InterfaceAPI')) {
    require_once( ADV_DEM_CF7_PLUGIN_DIR . '/api/class-adv-dem-cf7-interfaceapi.php');
    }
?>