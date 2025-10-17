<?php
if (!defined('ABSPATH')) exit;

// ===== Breadcrumbs =====
function xyz_get_map_page_url_for_place($place_id){
  $map_id = (int) get_post_meta($place_id, '_map_id', true);
  if (!$map_id) return home_url('/');
  $pid = (int) get_option('xyz_map_page_for_'.$map_id);
  $url = $pid ? get_permalink($pid) : home_url('/');
  return add_query_arg(['xyz_map_id'=>$map_id, 'marker'=>$place_id], $url);
}

function xyz_render_breadcrumbs(){
  if (is_admin()) return '';
  if (is_singular('map_marker')) {
    $place_id = get_the_ID();
    $map_url  = xyz_get_map_page_url_for_place($place_id);
    return '<nav class="xyz-bc" aria-label="Breadcrumbs">'
         . '<a href="'.esc_url($map_url).'">'.esc_html__('Map','xyz-map-gallery').'</a>'
         . ' &raquo; <span>'.esc_html(get_the_title()).'</span>'
         . '</nav>';
  }
  if (is_singular('map_photo')) {
    $photo_id = get_the_ID();
    $place_id = (int) get_post_meta($photo_id,'_place_id',true);
    if ($place_id) {
      $map_url   = xyz_get_map_page_url_for_place($place_id);
      $place_url = get_permalink($place_id);
      return '<nav class="xyz-bc" aria-label="Breadcrumbs">'
           . '<a href="'.esc_url($map_url).'">'.esc_html__('Map','xyz-map-gallery').'</a>'
           . ' &raquo; <a href="'.esc_url($place_url).'">'.esc_html(get_the_title($place_id)).'</a>'
           . ' &raquo; <span>'.esc_html(get_the_title()).'</span>'
           . '</nav>';
    }
  }
  return '';
}

// Wstrzyknięcie nad treścią pojedynczych wpisów
add_filter('the_content', function($c){
  if (is_singular(['map_marker','map_photo'])) {
    return xyz_render_breadcrumbs() . $c;
  }
  return $c;
}, 5);


