<?php
if ( ! defined('ABSPATH') ) exit;

function xyz_import_upsert_marker($in,$opts){
    $dry=!empty($opts['dry']); $overwrite=!empty($opts['overwrite']);
    $title=wp_strip_all_tags($in['title']??'');
    $desc=$in['description']??'';
    $pos=$in['position']??'';
    $owners=$in['owners']??''; $tags=$in['tags']??'';
    $icon=$in['icon']??''; $ext_id=$in['ext_id']??''; $map_id=(int)($in['map_id']??0);

    if($title===''||$pos==='') return new WP_Error('bad_row','Missing required');

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
