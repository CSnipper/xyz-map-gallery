<?php if (!defined('ABSPATH')) exit;
function xyz_block_render_mini_map($atts){
  $place_id = isset($atts['place_id']) ? (int)$atts['place_id'] : 0;
  return $place_id ? xyz_render_place_minimap($place_id) : '';
}
