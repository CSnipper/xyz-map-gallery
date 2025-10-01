<?php if (!defined('ABSPATH')) exit;
function xyz_block_render_big_map($atts){
  $map_id = isset($atts['map_id']) ? (int)$atts['map_id'] : 0;
  return $map_id ? xyz_render_big_map($map_id) : '<p style="opacity:.7">'.esc_html__('No map selected.','xyz-map-gallery').'</p>';
}
