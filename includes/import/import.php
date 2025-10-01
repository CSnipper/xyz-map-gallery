<?php
// includes/import/import.php
if ( ! defined('ABSPATH') ) exit;

add_action('admin_menu', function(){
    add_submenu_page(
        'xyz-map-gallery',
        __('Import markers','xyz-map-gallery'),
        __('Import','xyz-map-gallery'),
        'manage_options',
        'xyz-import',
        'xyz_import_admin_page'
    );
});

add_filter('upload_mimes', function($m){
    $m['csv']     = 'text/csv';
    $m['geojson'] = 'application/geo+json';
    $m['json']    = 'application/json';
    return $m;
});
add_filter('wp_check_filetype_and_ext', function($types, $file, $filename){
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, ['csv','geojson','json'], true)) {
        $types['ext']  = $ext;
        $types['type'] = $ext==='csv' ? 'text/csv' : ($ext==='geojson' ? 'application/geo+json' : 'application/json');
    }
    return $types;
}, 10, 3);


function xyz_import_admin_page(){
    if ( ! current_user_can('manage_options') ) return;

    $step   = isset($_POST['xyz_step']) ? (int)$_POST['xyz_step'] : 1;
    $result = null; $error = null;

    if ($step === 1 && isset($_FILES['xyz_import_file'])) {
        require_once __DIR__.'/step-upload.php';
        list($step,$error,$path,$cols,$ext) = xyz_import_step_upload();
    }
    elseif ($step === 2 && isset($_POST['xyz_step']) && $_POST['xyz_step']==2) {
        require_once __DIR__.'/step-run.php';
        list($step,$result,$error) = xyz_import_step_run();
    }

    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Import markers','xyz-map-gallery'); ?></h1>

      <?php if ($error): ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
      <?php endif; ?>

      <?php if ($step===1): ?>
        <?php require __DIR__.'/step-upload.php'; xyz_import_step_upload_form(); ?>
      <?php elseif ($step===2): ?>
        <?php require __DIR__.'/step-mapping.php'; xyz_import_step_mapping_form($path,$cols,$ext); ?>
      <?php elseif ($step===3 && $result): ?>
        <div class="notice notice-success">
          <p><?php printf(
            esc_html__('Imported: %d, Updated: %d, Skipped: %d','xyz-map-gallery'),
            $result['imported'],$result['updated'],$result['skipped']
          ); ?></p>
        </div>
        <?php if (!empty($result['errors'])): ?>
          <details><summary><?php esc_html_e('Errors','xyz-map-gallery'); ?></summary>
            <pre><?php echo esc_html(implode("\n",$result['errors'])); ?></pre>
          </details>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
}

function xyz_import_upsert_marker($in,$opts){
    $dry=!empty($opts['dry']); $overwrite=!empty($opts['overwrite']);
    $title=wp_strip_all_tags($in['title']??'');
    $desc=$in['description']??'';
    $pos=$in['position']??'';
    $owners=$in['owners']??''; $tags=$in['tags']??'';
    $icon=$in['icon']??''; $ext_id=$in['ext_id']??''; $map_id=(int)($in['map_id']??0);

    if($title===''||$pos==='') return new WP_Error('bad_row','Missing required');

    // znajdź istniejący
    $existing_id=0;
    if($ext_id!==''){
        $q=get_posts(['post_type'=>'gallery_item','meta_key'=>'_ext_id','meta_value'=>$ext_id,'fields'=>'ids','posts_per_page'=>1]);
        if($q) $existing_id=(int)$q[0];
    }

    $postarr=['post_title'=>$title,'post_content'=>$desc,'post_status'=>'publish','post_type'=>'gallery_item'];

    if($existing_id && !$overwrite) return ['action'=>'skipped','message'=>"Skipped $title"];

    if($existing_id){
        $postarr['ID']=$existing_id;
        if($dry) return ['action'=>'updated','message'=>"DRY update $title"];
        $pid=wp_update_post($postarr,true);
        if(is_wp_error($pid)) return $pid;
        $action='updated';
    }else{
        if($dry) return ['action'=>'created','message'=>"DRY create $title"];
        $pid=wp_insert_post($postarr,true);
        if(is_wp_error($pid)) return $pid;
        $action='created';
    }

    update_post_meta($pid,'_map_position',$pos);
    if($map_id) update_post_meta($pid,'_map_id',$map_id);
    if($ext_id!=='') update_post_meta($pid,'_ext_id',$ext_id);
    if($icon!=='') update_post_meta($pid,'_map_icon',$icon);

    return ['action'=>$action,'message'=>"$action #$pid $title"];
}
