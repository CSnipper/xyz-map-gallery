<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route('xyz/v1','search',[
    'methods'  => 'GET',
    'permission_callback' => '__return_true',
    'callback' => function(WP_REST_Request $r){
      global $wpdb;

    $type = sanitize_key($r->get_param('type'));
    $q    = trim((string)$r->get_param('q'));
    $q    = sanitize_text_field($q);
    $q    = mb_substr($q, 0, 80);
    if (strlen($q) < 2) return rest_ensure_response([]);

  // throttling per IP + route
      $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
      $key = 'xyz_search_rl_'.md5($ip.'|'.$type);
      $hits = (int) get_transient($key); $hits++;
  if ($hits > 20) return new WP_REST_Response(['message'=>__('Rate limited','xyz-map-gallery')], 429);
      set_transient($key, $hits, 30);

      $out = [];
      if ($type === 'map') {
        $tbl  = $wpdb->prefix.'xyz_maps';
        $like = '%'.$wpdb->esc_like($q).'%';
        $rows = $wpdb->get_results($wpdb->prepare(
          "SELECT id, name FROM $tbl WHERE name LIKE %s ORDER BY name LIMIT 20", $like
        ));
        foreach($rows as $row){
          $out[] = ['id'=>(int)$row->id,'label'=>wp_strip_all_tags((string)$row->name)];
        }
      } elseif ($type === 'place') {
        $posts = get_posts([
          'post_type'=>'gallery_item','s'=>$q,'posts_per_page'=>20,'fields'=>'ids',
          'post_status'=>'publish','no_found_rows'=>true,
        ]);
        foreach($posts as $pid){
          $out[] = ['id'=>(int)$pid,'label'=>get_the_title($pid)];
        }
      }
      return rest_ensure_response($out);
    }
  ]);
});
