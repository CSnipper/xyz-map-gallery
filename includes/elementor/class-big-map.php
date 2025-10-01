<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class XYZ_Widget_Big_Map extends \Elementor\Widget_Base {
  public function get_name(){ return 'xyz-big-map'; }
  public function get_title(){ return __('XYZ Big Map', 'xyz-map-gallery'); }
  public function get_icon(){ return 'eicon-map'; }
  public function get_categories(){ return ['xyz-map-gallery']; }

    protected function register_controls(){
      $this->start_controls_section('content', ['label'=>__('Content','xyz-map-gallery')]);

      // Zbierz mapy z bazy
      global $wpdb;
      $tbl = $wpdb->prefix . 'xyz_maps';
      $rows = $wpdb->get_results("SELECT id, name FROM {$tbl} ORDER BY name ASC");
      $map_opts = [];
      if ($rows){
        foreach ($rows as $r){
          $map_opts[(int)$r->id] = $r->name ? $r->name : ('#'.$r->id);
        }
      }

  // ...existing code...
      $this->add_control('map_id', [
        'label'        => __('Map','xyz-map-gallery'),
        'type'         => \Elementor\Controls_Manager::SELECT2,
        'options'      => $map_opts,
        'label_block'  => true,
        'multiple'     => false,
        'default'      => '',
        'description'  => $rows
          ? __('Start typing to filter the list.','xyz-map-gallery')
          : __('No maps found. Create one first.','xyz-map-gallery'),
      ]);

      $this->end_controls_section();
    }

    protected function render(){
      if (function_exists('xyz_can_show_elementor') && ! xyz_can_show_elementor()) return;
      $s = $this->get_settings_for_display();
      $map_id = (int)($s['map_id'] ?? 0);
      if (!$map_id){
        echo '<div style="opacity:.7">'.esc_html__('Pick a map in the sidebar.','xyz-map-gallery').'</div>';
        return;
      }

      // zasoby – zawsze (Elementor nie przechodzi przez the_content)
      wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');
      wp_enqueue_style('leaflet-markercluster-css','https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',['leaflet-css'],'1.5.3');
      wp_enqueue_style('leaflet-markercluster-default-css','https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',['leaflet-markercluster-css'],'1.5.3');
      wp_enqueue_style('xyz-frontend-css', plugins_url('assets/css/frontend.css', XYZ_MAP_GALLERY_FILE), ['leaflet-css'], '1.0.1');

      wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);
      wp_enqueue_script('leaflet-markercluster-js','https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',['leaflet-js'],'1.5.3', true);
      wp_enqueue_script('xyz-frontend-js', plugins_url('assets/js/frontend-map.js', XYZ_MAP_GALLERY_FILE), ['leaflet-js','leaflet-markercluster-js'], '1.0.1', true);

  // ...existing code...
      echo xyz_render_big_map($map_id);
    }



}
