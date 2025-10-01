<?php
if (!defined('ABSPATH')) exit;

/**
 * 1) Jeśli shortcody są całkowicie WYŁĄCZONE (Enable shortcodes = OFF),
 *    rejestrujemy „no-op” handlery i kończymy plik.
 */
if (function_exists('xyz_can_enable_shortcode') && ! xyz_can_enable_shortcode()) {
  foreach (['xyz_map','xyz_map_link','xyz_place_minimap','xyz_place_gallery','xyz_back_to_map'] as $tag) {
    add_shortcode($tag, '__return_empty_string');
  }
  return;
}

/**
 * Helper: szybkie sprawdzanie „Show shortcodes”.
 * Gdy SHOW=OFF — handler nic nie zwraca (nie ma surowego tekstu shortcodu).
 */
function xyz_sc_guard() {
  return function_exists('xyz_can_show_shortcode') && ! xyz_can_show_shortcode();
}

// — link „wróć do mapy”
add_shortcode('xyz_map_link', function($atts){
  if (xyz_sc_guard()) return '';
  $a = shortcode_atts([
    'url'=>'','map'=>'','marker'=>'','text'=>'Wróć na mapę','class'=>'','target'=>'',
  ], $atts, 'xyz_map_link');
  if (empty($a['url'])) return '';
  $args = [];
  if (!empty($a['map']))    $args['xyz_map_id'] = $a['map'];
  if (!empty($a['marker'])) $args['marker']     = $a['marker'];
  $href = add_query_arg($args, $a['url']);
  $class  = $a['class']  ? ' class="'.esc_attr($a['class']).'"' : '';
  $target = $a['target'] ? ' target="'.esc_attr($a['target']).'"' : '';
  return '<a href="'.esc_url($href).'"'.$class.$target.'>'.esc_html($a['text']).'</a>';
});

// — big map
add_shortcode('xyz_map', function($atts){
  if (xyz_sc_guard()) return '';
  $a = shortcode_atts(['id'=>0], $atts, 'xyz_map');
  return xyz_render_big_map((int)$a['id']);
});

// — mini map
add_shortcode('xyz_place_minimap', function($atts){
  if (xyz_sc_guard()) return '';
  $a = shortcode_atts(['place_id'=>0], $atts, 'xyz_place_minimap');
  return xyz_render_place_minimap((int)$a['place_id']);
});

// — photos grid
add_shortcode('xyz_place_gallery', function($atts){
  if (xyz_sc_guard()) return '';
  $a = shortcode_atts(['place_id'=>0,'per_page'=>24], $atts, 'xyz_place_gallery');
  return xyz_render_place_gallery((int)$a['place_id'], (int)$a['per_page']);
});

// — back to map
add_shortcode('xyz_back_to_map', function($atts){
  if (xyz_sc_guard()) return '';
  $a = shortcode_atts(['place_id'=>0,'label'=>'← '. __('Back to map','xyz-map-gallery')], $atts,'xyz_back_to_map');
  $pid = (int)($a['place_id'] ?: get_the_ID());
  $map_url  = function_exists('xyz_get_map_page_url_for_place') ? xyz_get_map_page_url_for_place($pid) : home_url('/');
  if ($pid) $map_url = add_query_arg('marker', $pid, $map_url);
  return '<a href="'.esc_url($map_url).'">'.esc_html($a['label']).'</a>';
});
