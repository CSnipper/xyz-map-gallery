<?php
/*
Plugin Name: XYZ Map Gallery
Description: A plugin to create and manage interactive maps with markers based on XYZ tile maps. Geocode or pixel coords.
Version: 1.0
Author: Marek Wojtaszek
Text Domain: xyz-map-gallery
Domain Path: /lang
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) exit;


define('XYZ_MG_FILE', __FILE__);
define('XYZ_MAP_GALLERY_FILE', __FILE__);
define('XYZ_MAP_GALLERY_URL', plugin_dir_url(__FILE__));

// Load plugin textdomain early from the plugin's /lang/ directory so admin menus can be translated
load_plugin_textdomain( 'xyz-map-gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

// Debug notice removed — textdomain is loaded earlier to ensure admin labels are translated

// Temporary debug notice: shows get_locale() and translation for the editor sidebar hint.
// Visible only to users with manage_options. Remove after verification.
add_action('admin_notices', function(){
        if (!is_admin() || !current_user_can('manage_options')) return;
        $msg = __('Marker will be linked according to plugin settings. If you want to force a specific marker, pick a place in the sidebar.', 'xyz-map-gallery');
        printf('<div class="notice notice-info"><p><strong>%s</strong><br/>%s: <code>%s</code></p></div>',
            esc_html__('xyz-map-gallery i18n debug','xyz-map-gallery'),
            esc_html__('get_locale()','xyz-map-gallery'),
            esc_html(get_locale())
        );
        printf('<div class="notice"><p>%s: <code>%s</code></p></div>', esc_html__('Translation for sidebar hint','xyz-map-gallery'), esc_html($msg));
});

add_action('wp_enqueue_scripts', function () {
    // Załaduj jQuery z pakietu WP
    wp_enqueue_script('jquery');
 
});

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
