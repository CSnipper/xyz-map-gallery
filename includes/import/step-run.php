<?php
// includes/import/step-run.php
if ( ! defined('ABSPATH') ) exit;

function xyz_import_step_run(){
    check_admin_referer('xyz_import_nonce');

    $path    = sanitize_text_field($_POST['xyz_import_path']);
    $ext     = strtolower(pathinfo($path,PATHINFO_EXTENSION));
    $map_id  = (int)($_POST['xyz_import_map_id'] ?? 0);
    $dry     = !empty($_POST['xyz_import_dry']);
    $overwrite=!empty($_POST['xyz_import_overwrite']);
    $mapping = $_POST['mapping']??[];

    if($ext==='csv'){
        require_once __DIR__.'/csv.php';
        $result=xyz_import_csv_run($path,compact('map_id','dry','overwrite','mapping'));
    }else{
        require_once __DIR__.'/geojson.php';
        $result=xyz_import_geojson_run($path,compact('map_id','dry','overwrite','mapping'));
    }
    return [3,$result,null];
}
