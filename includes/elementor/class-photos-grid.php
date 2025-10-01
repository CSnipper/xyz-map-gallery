<?php
if (!defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class XYZ_Widget_Photos_Grid extends Elementor\Widget_Base {
    public function get_name(){ return 'xyz-photos-grid'; }
    public function get_title(){ return __('XYZ Photos Grid','xyz-map-gallery'); }
    public function get_icon(){ return 'eicon-gallery-grid'; }
    public function get_categories(){ return ['xyz-map-gallery']; }

    protected function register_controls(){
      $this->start_controls_section('content', ['label'=>__('Content','xyz-map-gallery')]);

      $this->add_control('place_title', [
        'label' => __('Place (title)','xyz-map-gallery'),
        'type'  => Controls_Manager::TEXT,
        'label_block' => true,
  'description' => __('Enter place title (optional, for editing only).','xyz-map-gallery'),
      ]);

      $this->add_control('place_id', [
        'label' => __('Place ID','xyz-map-gallery'),
        'type'  => Controls_Manager::NUMBER,
        'default' => 0,
      ]);

      $this->add_control('per_page', [
        'label' => __('Per page','xyz-map-gallery'),
        'type'  => Controls_Manager::NUMBER,
        'default' => 24,
        'min' => 1, 'max' => 200,
      ]);

      $this->end_controls_section();
    }

    protected function render(){
      if (function_exists('xyz_can_show_elementor') && ! xyz_can_show_elementor()) return;
      $s = $this->get_settings_for_display();
      $place_id = (int)($s['place_id'] ?? get_the_ID());
      $per_page = (int)($s['per_page'] ?? 24);
      if (!$place_id){ echo '<div style="opacity:.7">'.esc_html__('Pick a place.','xyz-map-gallery').'</div>'; return; }

      echo xyz_render_place_gallery($place_id, $per_page);
    }



}
