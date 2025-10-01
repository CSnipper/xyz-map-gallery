<?php
if (!defined('ABSPATH')) exit;

// cache helpers
function xyz_cache_ver_key($map_id){ return 'xyz_map_cache_ver_'.(int)$map_id; }
function xyz_bump_cache_ver($map_id){
  $k = xyz_cache_ver_key($map_id);
  $v = (int) get_option($k, 1);
  update_option($k, $v+1, false);
}
function xyz_get_cache_ver($map_id){
  return (int) get_option( xyz_cache_ver_key($map_id), 1 );
}
function xyz_invalidate_map_cache($map_id){
  if ($map_id) delete_transient('xyz_map_payload_'.(int)$map_id);
}

// bump after marker changes
add_action('save_post_gallery_item', function($post_id,$post){
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if ($post->post_type!=='gallery_item') return;
  $map_id = (int) get_post_meta($post_id,'_map_id',true);
  if ($map_id) xyz_bump_cache_ver($map_id);
},10,2);

add_action('deleted_post', function($post_id){
  $post = get_post($post_id);
  if (!$post || $post->post_type!=='gallery_item') return;
  $map_id = (int) get_post_meta($post_id,'_map_id',true);
  if ($map_id) xyz_bump_cache_ver($map_id);
},10,1);

add_action('set_object_terms', function($post_id,$terms,$tt_ids,$taxonomy){
  if (get_post_type($post_id)!=='gallery_item') return;
  if (!in_array($taxonomy, ['post_tag','owner'], true)) return;
  $map_id = (int) get_post_meta($post_id,'_map_id',true);
  if ($map_id) xyz_bump_cache_ver($map_id);
},10,4);

add_action('save_post_map', function($post_id, $post){
  if ($post->post_type !== 'map') return;
  xyz_invalidate_map_cache($post_id);
}, 10, 2);