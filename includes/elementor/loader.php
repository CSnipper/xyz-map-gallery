<?php
if (!defined('ABSPATH')) exit;

add_action('elementor/elements/categories_registered', function($mgr){
    if (method_exists($mgr, 'add_category')) {
        $mgr->add_category('xyz-map-gallery', [
            'title' => __('XYZ Map Gallery', 'xyz-map-gallery'),
        ], 1);
    }
});

add_action('elementor/widgets/register', function($widgets_manager){
    $base = plugin_dir_path(XYZ_MAP_GALLERY_FILE) . 'includes/elementor/';

    require_once $base . 'class-mini-map.php';
    require_once $base . 'class-photos-grid.php';
    require_once $base . 'class-big-map.php';

    if (class_exists('XYZ_Widget_Mini_Map'))    $widgets_manager->register(new \XYZ_Widget_Mini_Map());
    if (class_exists('XYZ_Widget_Photos_Grid')) $widgets_manager->register(new \XYZ_Widget_Photos_Grid());
    if (class_exists('XYZ_Widget_Big_Map'))     $widgets_manager->register(new \XYZ_Widget_Big_Map());
}, 0);

add_action('elementor/frontend/after_enqueue_styles', function () {
    wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');
    wp_enqueue_style('xyz-frontend-css', plugins_url('assets/css/frontend.css', XYZ_MAP_GALLERY_FILE), ['leaflet-css'], '1.0.1');
});

add_action('elementor/frontend/after_enqueue_scripts', function () {
    wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);
    wp_register_script('leaflet-markercluster-js','https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',['leaflet-js'],'1.5.3',true);
});

add_action('elementor/editor/after_enqueue_scripts', function(){
    wp_enqueue_script(
        'xyz-el-ac',
        plugins_url('includes/elementor/admin-autocomplete.js', XYZ_MAP_GALLERY_FILE),
        ['jquery','wp-api-fetch'],
        '1.0',
        true
    );
});
