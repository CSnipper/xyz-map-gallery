<?php
if ( ! defined('ABSPATH') ) exit;
/*
Plugin Name: XYZ Map Gallery
Description: A plugin to create and manage interactive maps with markers based on XYZ tile maps. Geocode or pixel coords.
Version: 1.4
Author: Marek Wojtaszek
Text Domain: xyz-map-gallery
Domain Path: /languages
*/
if (!defined('ABSPATH')) exit;


define('XYZ_MG_FILE', __FILE__);
define('XYZ_MAP_GALLERY_FILE', __FILE__);
define('XYZ_MAP_GALLERY_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {
    if ( is_admin() ) {
        $base = plugin_dir_path(XYZ_MAP_GALLERY_FILE) . 'includes/admin/';
        foreach (['map-settings.php','marker-settings.php','photo-settings.php'] as $f) {
            $p = $base . $f;
            if (file_exists($p)) require_once $p;
        }
        require_once plugin_dir_path(XYZ_MAP_GALLERY_FILE).'includes/admin/bulk-assign.php';
        require_once plugin_dir_path(XYZ_MAP_GALLERY_FILE) . 'includes/import/import.php';
    }
});

add_action('init', function(){
  require_once __DIR__.'/includes/rest.php';
  require_once __DIR__.'/includes/frontend/renderers.php';
  require_once __DIR__.'/includes/blocks/loader.php';
  require_once __DIR__.'/includes/integrations/wpbakery.php';
}, 9);



// ——— Requires ———
require_once __DIR__.'/includes/helpers.php'; 
require_once __DIR__.'/includes/install.php';
require_once __DIR__.'/includes/cpt.php';
require_once __DIR__.'/includes/cache.php';
require_once __DIR__.'/includes/admin/assets.php';
require_once __DIR__.'/includes/frontend/assets.php';
require_once __DIR__.'/includes/frontend/shortcodes.php';
require_once __DIR__.'/includes/frontend/breadcrumbs.php';
require_once __DIR__.'/includes/frontend/seo.php'; 
require_once __DIR__.'/includes/class-plugin.php';
require_once plugin_dir_path(XYZ_MAP_GALLERY_FILE) . 'includes/elementor/loader.php';

// ——— Hooks install ———
register_activation_hook(__FILE__, ['XYZ_Map_Gallery\\Install', 'activate']);
register_deactivation_hook(__FILE__, ['XYZ_Map_Gallery\\Install', 'deactivate']);

// ——— Start ———
$GLOBALS['xyz_map_gallery_plugin'] = new \XYZ_Map_Gallery\Plugin();
