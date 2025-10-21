<?php
if (!defined('ABSPATH')) exit;
if ( ! ( xyz_can_enable_gutenberg() || xyz_can_show_gutenberg() ) ) return;

/**
 * Registers block category and blocks.
 */
add_filter('block_categories_all', function ($categories, $post) {
    $slug = 'xyz-map-gallery';
    foreach ($categories as $c) { if (!empty($c['slug']) && $c['slug'] === $slug) return $categories; }
    $categories[] = ['slug' => $slug, 'title' => __('XYZ Map Gallery', 'xyz-map-gallery')];
    return $categories;
}, 10, 2);

/**
 * Register editor scripts (lightweight) once.
 */
add_action('enqueue_block_editor_assets', function () {
    if ( ! xyz_can_enable_gutenberg() ) return;
    wp_register_script(
      'xyz-big-map-editor',
      plugins_url('includes/blocks/big-map/index.js', XYZ_MAP_GALLERY_FILE),
      ['wp-blocks','wp-element','wp-components','wp-i18n','wp-block-editor'],
      '1.0.0',
      true
    );

    wp_register_script(
        'xyz-mini-map-editor',
        plugins_url('includes/blocks/mini-map/index.js', XYZ_MAP_GALLERY_FILE),
        ['wp-blocks','wp-element','wp-components','wp-i18n','wp-block-editor','wp-api-fetch'],
        '1.0.0',
        true
    );

    wp_register_script(
        'xyz-photos-grid-editor',
        plugins_url('includes/blocks/photos-grid/index.js', XYZ_MAP_GALLERY_FILE),
        ['wp-blocks','wp-element','wp-components','wp-i18n','wp-block-editor','wp-api-fetch'],
        '1.0.0',
        true
    );

        // Small always-loaded script to expose localized block settings to the editor console
        wp_register_script(
            'xyz-block-settings',
            plugins_url('includes/blocks/block-settings.js', XYZ_MAP_GALLERY_FILE),
            ['wp-i18n'],
            '1.0.0',
            true
        );

        // Localize editor settings for blocks (whether linking taxonomy is enabled)
        $link_tax = (string) get_option('xyz_link_taxonomy', '');
        $settings = [
            'linkingEnabled' => !empty($link_tax) ? true : false,
            'sidebarHint'    => __('Marker will be linked according to plugin settings. If you want to force a specific marker, pick a place in the sidebar.','xyz-map-gallery'),
        ];
        // Localize into a small always-enqueued script so the object is available in console even
        // when the block-specific script isn't loaded yet by the editor.
        wp_localize_script('xyz-block-settings', 'xyzBlockSettings', $settings);
        wp_enqueue_script('xyz-block-settings');

        // Editor CSS for small hints
        wp_enqueue_style('xyz-editor-hints', plugins_url('assets/css/editor-hints.css', XYZ_MAP_GALLERY_FILE), [], '1.0.0');
});


/**
 * Big map (server rendered via your existing render_map_block).
 */
require_once __DIR__ . '/big-map/render.php';

register_block_type('xyz-map-gallery/big-map', [
    'render_callback' => 'xyz_block_render_big_map',
    'editor_script'   => 'xyz-big-map-editor',
    'attributes'      => [
        'mapId' => [
            'type'    => 'number',
            'default' => 0
        ],
    ],
]);

/**
 * Mini map (server rendered via shortcode).
 */
require_once __DIR__ . '/mini-map/render.php';
register_block_type('xyz-map-gallery/mini-map', [
  'render_callback' => 'xyz_block_render_mini_map',
  'editor_script'   => 'xyz-mini-map-editor',
  'attributes'      => ['placeId'=>['type'=>'number','default'=>0]],
]);

/**
 * Photos grid (server rendered via shortcode).
 */
require_once __DIR__ . '/photos-grid/render.php';
register_block_type('xyz-map-gallery/photos-grid', [
  'render_callback' => 'xyz_block_render_photos_grid',
  'editor_script'   => 'xyz-photos-grid-editor',
  'attributes'      => ['placeId'=>['type'=>'number','default'=>0]],
]);
