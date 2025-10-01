<?php
// includes/import/step-upload.php
if ( ! defined('ABSPATH') ) exit;

function xyz_import_step_upload(){
    check_admin_referer('xyz_import_nonce');
    require_once ABSPATH.'wp-admin/includes/file.php';

    $file=$_FILES['xyz_import_file'];
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    $allowed=['csv','geojson','json'];
    if(!in_array($ext,$allowed,true)) return [1,__('Unsupported file','xyz-map-gallery'),null,[],null];

    $upload=wp_handle_upload($file,['test_form'=>false,'mimes'=>null]);
    if(isset($upload['error'])) return [1,$upload['error'],null,[],null];

    $path=$upload['file'];
    if($ext==='csv'){
        require_once __DIR__.'/csv.php';
        $cols=xyz_import_csv_detect_columns($path);
    }else{
        require_once __DIR__.'/geojson.php';
        $cols=xyz_import_geojson_detect_columns($path);
    }
    return [2,null,$path,$cols,$ext];
}

function xyz_import_step_upload_form(){ ?>
  <form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('xyz_import_nonce'); ?>
    <input type="hidden" name="xyz_step" value="1">
    <p><input type="file" name="xyz_import_file" required></p>
    <p><button class="button button-primary" type="submit"><?php esc_html_e('Upload file','xyz-map-gallery'); ?></button></p>
  </form>
<?php }
