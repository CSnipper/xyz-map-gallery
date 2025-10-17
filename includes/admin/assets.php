<?php
namespace XYZ_Map_Gallery\Admin;
if ( ! defined('ABSPATH') ) exit;

function enqueue_admin_scripts(){
  if (!function_exists('get_current_screen')) return;
  $s = get_current_screen();

  // enqueue on marker edit screens OR on our plugin's admin pages (maps list/edit)
  $should_enqueue = false;
  if ($s) {
    if ((isset($s->post_type) && $s->post_type === 'map_marker') || (isset($s->id) && $s->id === 'map_marker')) {
      $should_enqueue = true;
    }
    if (isset($s->id) && (strpos($s->id, 'xyz-maps') !== false || strpos($s->id, 'toplevel_page_xyz-map-gallery') !== false)) {
      $should_enqueue = true;
    }
  }
  // also allow direct page query param (e.g. admin.php?page=xyz-maps)
  if (!$should_enqueue && isset($_GET['page']) && in_array($_GET['page'], ['xyz-maps','xyz-map-gallery'], true)) {
    $should_enqueue = true;
  }

  if ($should_enqueue) {
    wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');
    // plugin admin styles for center picker responsiveness
    wp_enqueue_style('xyz-center-admin-css', plugins_url('assets/css/admin-center.css', \XYZ_MAP_GALLERY_FILE), [], '1.0');
    wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);
  wp_enqueue_script('xyz-marker-admin-js', plugins_url('assets/js/marker-admin.js', \XYZ_MAP_GALLERY_FILE), ['jquery','leaflet-js'],'1.0', true);
  wp_enqueue_script('xyz-center-admin-js', plugins_url('assets/js/admin-center.js', \XYZ_MAP_GALLERY_FILE), ['leaflet-js'], '1.0', true);
  wp_enqueue_script('xyz-admin-bulk-js', plugins_url('assets/js/admin-bulk.js', \XYZ_MAP_GALLERY_FILE), [], '1.0', true);

    $icons = glob(plugin_dir_path(\XYZ_MAP_GALLERY_FILE).'assets/icons/*.{png,svg}', GLOB_BRACE);
    $icons = array_map('basename', $icons);

    wp_localize_script('xyz-marker-admin-js','xyzMarkerAdmin',[
      'icons'=>$icons,
      'iconUrl'=>plugins_url('assets/icons/', \XYZ_MAP_GALLERY_FILE),
      'ajaxUrl'=>admin_url('admin-ajax.php'),
      'nonce'=>wp_create_nonce('xyz_marker_nonce'),
      'texts'=>['close'=>__('Close','xyz-map-gallery'),'select'=>__('Select','xyz-map-gallery')],
    ]);
    // small localization for center admin script (none required for now, kept for future)
    wp_localize_script('xyz-center-admin-js','xyzCenterAdmin',['texts'=>['reset'=>__('Reset Center','xyz-map-gallery')]]);
    // prepare maps list for bulk assign UI
    $icons = [];
    global $wpdb;
    $maps = $wpdb->get_results("SELECT id,name FROM " . $wpdb->prefix . "xyz_maps ORDER BY name ASC");
    $maps_js = array_map(function($m){ return ['id'=>intval($m->id),'label'=>($m->name?:('#'.intval($m->id)))]; }, $maps ?: []);
    wp_localize_script('xyz-admin-bulk-js','xyzBulkAdmin', [
      'maps' => $maps_js,
      'i18nMapLabel' => __('Map:', 'xyz-map-gallery'),
      'chooseText' => __('— choose —', 'xyz-map-gallery'),
      'pleaseChoose' => __('Please choose a map first.', 'xyz-map-gallery'),
    ]);
  }
}
add_action('admin_enqueue_scripts', __NAMESPACE__.'\\enqueue_admin_scripts');
