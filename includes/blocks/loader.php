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
