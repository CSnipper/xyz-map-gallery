<?php if (!defined('ABSPATH')) exit;
function xyz_block_render_photos_grid($atts){
  $place_id = isset($atts['place_id']) ? (int)$atts['place_id'] : 0;
  $per_page = isset($atts['per_page']) ? (int)$atts['per_page'] : 24;
  return $place_id ? xyz_render_place_gallery($place_id, $per_page) : '';
}
