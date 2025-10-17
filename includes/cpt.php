<?php
if (!defined('ABSPATH')) exit;

// CPT: map_marker
add_action('init', function() {
  register_post_type('map_marker', [
    'labels' => ['name' => __('Markers','xyz-map-gallery')],
    'public' => true,
    'publicly_queryable' => true,
    'has_archive' => false,
    'rewrite' => ['slug'=>'map_marker','with_front'=>false],
    'show_in_rest'=> true,
    'supports' => ['title','editor','excerpt','thumbnail','author','page-attributes','revisions'],
    'taxonomies' => ['category','post_tag'],
    'show_in_menu' => 'xyz-map-gallery',
  ]);
});

// thumb support
add_action('after_setup_theme', function () {
  add_theme_support('post-thumbnails', ['map_photo','map_marker']);
});

/** CPT: map_photo */
add_action('init', function () {
    $args = [
        'labels' => [
            'name'          => __('Photos', 'xyz-map-gallery'),
            'singular_name' => __('Photo', 'xyz-map-gallery'),
        ],
        'public'             => true,
        'publicly_queryable' => true,
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'foto', 'with_front' => false],
        'supports'           => ['title', 'editor', 'thumbnail', 'comments', 'author', 'revisions'],
        'taxonomies'         => ['category', 'post_tag'],
        'menu_icon'          => 'dashicons-format-image',
        'show_in_rest'       => true,
        'show_in_menu'       => 'xyz-map-gallery', // spina pod menu wtyczki
    ];
    register_post_type('map_photo', $args);
});
