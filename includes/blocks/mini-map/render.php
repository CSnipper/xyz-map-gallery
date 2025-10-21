<?php if (!defined('ABSPATH')) exit;
function xyz_block_render_mini_map($atts){
  // Support both snake_case and camelCase attribute keys just in case
  $place_id = 0;
  if (isset($atts['place_id'])) $place_id = (int)$atts['place_id'];
  elseif (isset($atts['placeId'])) $place_id = (int)$atts['placeId'];

  $marker_link = '';
  if (!$place_id){
    if (is_singular('map_photo')){
      $place_id = (int) xyz_get_linked_place_for_photo(get_the_ID());
      if ($place_id){
        $marker_link = get_permalink($place_id);
      }
    } elseif (is_singular('map_marker')){
      $place_id = (int) get_the_ID();
    }
  }

  if (!$place_id) return '';
  // Render the mini map HTML and inject data-marker-link when available
  $html = xyz_render_place_minimap($place_id);
  if ($marker_link){
    // insert data-marker-link into the opening div
    $html = preg_replace('/<div class="xyz-mini-map"/', '<div class="xyz-mini-map" data-marker-link="'.esc_attr($marker_link).'"', $html, 1);
  }
  return $html;
}
