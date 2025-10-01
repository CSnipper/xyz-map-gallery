<?php
namespace XYZ_Map_Gallery\Admin;
if ( ! defined('ABSPATH') ) exit;

function enqueue_admin_scripts(){
  if (!function_exists('get_current_screen')) return;
  $s = get_current_screen();
  if ($s && ( (isset($s->post_type) && $s->post_type==='gallery_item') || (isset($s->id) && $s->id==='gallery_item') ) ) {
    wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');
    wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);
    wp_enqueue_script('xyz-marker-admin-js', plugins_url('assets/js/marker-admin.js', \XYZ_MAP_GALLERY_FILE), ['jquery','leaflet-js'],'1.0', true);

    $icons = glob(plugin_dir_path(\XYZ_MAP_GALLERY_FILE).'assets/icons/*.{png,svg}', GLOB_BRACE);
    $icons = array_map('basename', $icons);

    wp_localize_script('xyz-marker-admin-js','xyzMarkerAdmin',[
      'icons'=>$icons,
      'iconUrl'=>plugins_url('assets/icons/', \XYZ_MAP_GALLERY_FILE),
      'ajaxUrl'=>admin_url('admin-ajax.php'),
      'nonce'=>wp_create_nonce('xyz_marker_nonce'),
      'texts'=>['close'=>__('Close','xyz-map-gallery'),'select'=>__('Select','xyz-map-gallery')],
    ]);
  }
}
add_action('admin_enqueue_scripts', __NAMESPACE__.'\\enqueue_admin_scripts');
