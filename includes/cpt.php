<?php
if (!defined('ABSPATH')) exit;

// CPT: gallery_item
add_action('init', function() {
  register_post_type('gallery_item', [
    'labels' => ['name' => __('Markers','xyz-map-gallery')],
    'public' => true,
    'publicly_queryable' => true,
    'has_archive' => false,
    'rewrite' => ['slug'=>'gallery_item','with_front'=>false],
    'show_in_rest'=> true,
    'supports' => ['title','editor','excerpt','thumbnail','author','page-attributes','revisions'],
    'taxonomies' => ['category','post_tag'],
    'show_in_menu' => 'xyz-map-gallery',
  ]);
});

// thumb support
add_action('after_setup_theme', function () {
  add_theme_support('post-thumbnails', ['photo_item','gallery_item']);
});
