<?php
if (!defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class XYZ_Widget_Mini_Map extends Elementor\Widget_Base {
public function get_name(){ return 'xyz-mini-map'; }
public function get_title(){ return __('XYZ Mini Map', 'xyz-map-gallery'); }
public function get_icon(){ return 'eicon-google-maps'; }
public function get_categories(){ return ['xyz-map-gallery']; }


    protected function register_controls(){
      $this->start_controls_section('content', ['label'=>__('Content','xyz-map-gallery')]);

      $opts = [];
      $posts = get_posts([
        'post_type'      => 'map_marker',
        'post_status'    => 'publish',
        'posts_per_page' => 500,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'suppress_filters' => true,
      ]);
      foreach ($posts as $pid){
        $opts[$pid] = get_the_title($pid);
      }

      $this->add_control('place_id', [
        'label'        => __('Place','xyz-map-gallery'),
        'type'         => Controls_Manager::SELECT2,
        'options'      => $opts,
        'label_block'  => true,
        'multiple'     => false,
        'default'      => '',
        'description'  => __('Start typing to filter the list.','xyz-map-gallery'),
      ]);

      $this->add_control('height', [
        'label'   => __('Height (px)','xyz-map-gallery'),
        'type'    => Controls_Manager::NUMBER,
        'default' => 220,
      ]);

      $this->end_controls_section();
    }

    protected function render(){
      if ( function_exists('xyz_can_show_elementor') && ! xyz_can_show_elementor() ) return;
      $s = $this->get_settings_for_display();
      $place_id = isset($s['place_id']) ? (int)$s['place_id'] : 0;

      echo '<div class="xyz-debug" style="font:12px/1.4 sans-serif;opacity:.6;margin-bottom:6px"></div>';

      if (!$place_id){
        echo '<div style="opacity:.7">'.esc_html__('Pick a place in widget settings.','xyz-map-gallery').'</div>';
        return;
      }

      wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');
      wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);
      wp_enqueue_script('xyz-mini-map', plugins_url('assets/js/mini-map.js', XYZ_MAP_GALLERY_FILE), ['leaflet-js'], '1.0.2', true);

      if (function_exists('xyz_render_place_minimap')) {
        echo xyz_render_place_minimap($place_id);
      } else {
        echo '<div style="color:#b00">'.esc_html__('Renderer missing (xyz_render_place_minimap).','xyz-map-gallery').'</div>';
      }
    }




}
