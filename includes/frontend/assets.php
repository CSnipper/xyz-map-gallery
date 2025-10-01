<?php
if (!defined('ABSPATH')) exit;

function xyz_enqueue_mini_map_assets() {
  wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');

  $css_path = plugin_dir_path(XYZ_MAP_GALLERY_FILE).'assets/css/frontend.css';
  $css_ver  = (defined('WP_DEBUG') && WP_DEBUG && file_exists($css_path)) ? filemtime($css_path) : '1.0.1';
  wp_enqueue_style('xyz-frontend-css', plugins_url('assets/css/frontend.css', XYZ_MAP_GALLERY_FILE), ['leaflet-css'], $css_ver);

  wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);

  $js_path = plugin_dir_path(XYZ_MAP_GALLERY_FILE).'assets/js/mini-map.js';
  $js_ver  = (defined('WP_DEBUG') && WP_DEBUG && file_exists($js_path)) ? filemtime($js_path) : '1.0.1';
  wp_enqueue_script('xyz-mini-map', plugins_url('assets/js/mini-map.js', XYZ_MAP_GALLERY_FILE), ['leaflet-js'], $js_ver, true);
}

add_action('wp_enqueue_scripts', function () {
  if ( is_singular('gallery_item') || is_singular('photo_item') ) {
    xyz_enqueue_mini_map_assets();
  }
});
