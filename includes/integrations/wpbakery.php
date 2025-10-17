<?php
if (!defined('ABSPATH')) exit;

if ( function_exists('xyz_can_enable_wpbakery') && ! xyz_can_enable_wpbakery() ) {
    return;
}


add_action('vc_before_init', function () {

  if (!function_exists('vc_map')) return;

  // === BIG MAP ===  [xyz_map id="..."]
  vc_map([
    'name'     => __('XYZ Big Map','xyz-map-gallery'),
    'base'     => 'xyz_map',
    'category' => __('XYZ Map Gallery','xyz-map-gallery'),
    'params'   => [
      [
        'type'        => 'autocomplete',
        'heading'     => __('Map','xyz-map-gallery'),
        'param_name'  => 'id',
        'settings'    => [
          'multiple'        => false,
          'min_length'      => 2,
          'display_inline'  => true,
          'auto_focus'      => true,
          'unique_values'   => true,
        ],
        'description' => __('Type map title to search…','xyz-map-gallery'),
      ],
    ],
  ]);

  // === MINI MAP ===  [xyz_place_minimap place_id="..."]
  vc_map([
    'name'     => __('XYZ Mini Map','xyz-map-gallery'),
    'base'     => 'xyz_place_minimap',
    'category' => __('XYZ Map Gallery','xyz-map-gallery'),
    'params'   => [
      [
        'type'        => 'autocomplete',
        'heading'     => __('Place','xyz-map-gallery'),
        'param_name'  => 'place_id',
        'settings'    => [
          'multiple'        => false,
          'min_length'      => 2,
          'display_inline'  => true,
          'auto_focus'      => true,
          'unique_values'   => true,
        ],
        'description' => __('Type place title to search…','xyz-map-gallery'),
      ],
    ],
  ]);

  // === PHOTOS GRID ===  [xyz_place_gallery place_id="..." per_page="..."]
  vc_map([
    'name'     => __('XYZ Photos Grid','xyz-map-gallery'),
    'base'     => 'xyz_place_gallery',
    'category' => __('XYZ Map Gallery','xyz-map-gallery'),
    'params'   => [
      [
        'type'        => 'autocomplete',
        'heading'     => __('Place','xyz-map-gallery'),
        'param_name'  => 'place_id',
        'settings'    => [
          'multiple'        => false,
          'min_length'      => 2,
          'display_inline'  => true,
          'auto_focus'      => true,
          'unique_values'   => true,
        ],
        'description' => __('Type place title to search…','xyz-map-gallery'),
      ],
      [
        'type'        => 'textfield',
        'heading'     => __('Per page','xyz-map-gallery'),
        'param_name'  => 'per_page',
        'value'       => '24',
      ],
    ],
  ]);
});


/**
 * AUTOCOMPLETE: Big Map → map (po tytule z tabeli xyz_maps)
 */
add_filter('vc_autocomplete_xyz_map_id_callback', function($query){
  global $wpdb;
  $like = '%' . $wpdb->esc_like( trim((string)$query) ) . '%';
  $tbl  = $wpdb->prefix . 'xyz_maps';
  $rows = $wpdb->get_results($wpdb->prepare(
    "SELECT id, name FROM $tbl WHERE name LIKE %s ORDER BY name ASC LIMIT 20", $like
  ));
  $out = [];
  foreach ($rows as $r) {
    $out[] = [
      'value' => (int)$r->id,
      'label' => $r->name ? $r->name : ('#'.$r->id),
    ];
  }
  return $out;
}, 10, 1);

add_filter('vc_autocomplete_xyz_map_id_render', function($term){
  global $wpdb;
  $id  = (int) ($term['value'] ?? 0);
  if (!$id) return false;
  $tbl = $wpdb->prefix . 'xyz_maps';
  $row = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM $tbl WHERE id=%d", $id));
  if (!$row) return false;
  return ['value'=>(int)$row->id, 'label'=>$row->name ? $row->name : ('#'.$row->id)];
}, 10, 1);


/**
 * AUTOCOMPLETE: Mini Map / Photos Grid → place_id (CPT `map_marker` po tytule)
 */
function xyz_vc_autocomplete_places_callback($query){
  $s = trim((string)$query);
  $posts = get_posts([
    'post_type'      => 'map_marker',
    'post_status'    => 'publish',
    's'              => $s,
    'fields'         => 'ids',
    'posts_per_page' => 20,
    'no_found_rows'  => true,
  ]);
  $out = [];
  foreach ($posts as $pid) {
    $out[] = ['value'=>(int)$pid, 'label'=> get_the_title($pid)];
  }
  return $out;
}
function xyz_vc_autocomplete_places_render($term){
  $id = (int) ($term['value'] ?? 0);
  if (!$id) return false;
  $title = get_the_title($id);
  if (!$title) return false;
  return ['value'=>$id, 'label'=>$title];
}

// Mini Map
add_filter('vc_autocomplete_xyz_place_minimap_place_id_callback', 'xyz_vc_autocomplete_places_callback', 10, 1);
add_filter('vc_autocomplete_xyz_place_minimap_place_id_render',   'xyz_vc_autocomplete_places_render',   10, 1);

// Photos Grid
add_filter('vc_autocomplete_xyz_place_gallery_place_id_callback', 'xyz_vc_autocomplete_places_callback', 10, 1);
add_filter('vc_autocomplete_xyz_place_gallery_place_id_render',   'xyz_vc_autocomplete_places_render',   10, 1);
