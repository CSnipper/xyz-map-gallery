<?php
if (!defined('ABSPATH')) exit;

/** ============= BIG MAP ============= */
function xyz_render_big_map($map_id){
  $map_id = (int) $map_id;
  if (!$map_id) return '';

  // assets
  wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');
  $css_path = plugin_dir_path(XYZ_MAP_GALLERY_FILE).'assets/css/frontend.css';
  $css_ver  = (defined('WP_DEBUG') && WP_DEBUG && file_exists($css_path)) ? filemtime($css_path) : '1.0.1';
  wp_enqueue_style('xyz-frontend-css', plugins_url('assets/css/frontend.css', XYZ_MAP_GALLERY_FILE), ['leaflet-css'], $css_ver);
  wp_enqueue_style('leaflet-markercluster-css','https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',['leaflet-css'],'1.5.3');
  wp_enqueue_style('leaflet-markercluster-default-css','https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',['leaflet-markercluster-css'],'1.5.3');

  wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);
  wp_enqueue_script('leaflet-markercluster-js','https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',['leaflet-js'],'1.5.3', true);

  $path = plugin_dir_path(XYZ_MAP_GALLERY_FILE).'assets/js/frontend-map.js';
  $ver  = (defined('WP_DEBUG') && WP_DEBUG && file_exists($path)) ? filemtime($path) : '1.0.1';
  wp_enqueue_script('xyz-frontend-js', plugins_url('assets/js/frontend-map.js', XYZ_MAP_GALLERY_FILE), ['leaflet-js','leaflet-markercluster-js'], $ver, true);

  // ...existing code...
  if (!is_admin()) {
    $page_id = get_queried_object_id();
    $opt_key = 'xyz_map_page_for_'.(int)$map_id;
    if ($page_id && (int)get_option($opt_key)!==(int)$page_id) {
      update_option($opt_key, (int)$page_id, false);
    }
  }

  // payload
  global $wpdb;
  $table = $wpdb->prefix.'xyz_maps';
  $map   = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $map_id));
  if (!$map) return '<p>'.esc_html__('Map not found.','xyz-map-gallery').'</p>';

  $b = json_decode($map->bounds ?: '', true);
  $bounds = ($b && isset($b['lat1'],$b['lng1'],$b['lat2'],$b['lng2']))
    ? [[(float)$b['lat1'], (float)$b['lng1']], [(float)$b['lat2'], (float)$b['lng2']]]
    : null;

  $vercache  = xyz_get_cache_ver($map_id);
  $cache_key = 'xyz_map_payload_'.$map_id.'_v'.$vercache;
  $payload   = get_transient($cache_key);

  if (!$payload) {
    $marker_ids = get_posts([
      'post_type'=>'gallery_item','post_status'=>'publish',
      'posts_per_page'=>-1,'fields'=>'ids','no_found_rows'=>true,
      'meta_query'=>[[ 'key'=>'_map_id','value'=>$map_id,'compare'=>'=' ]]
    ]);
    $markers = [];
    foreach($marker_ids as $id){
      $pos = get_post_meta($id,'_map_position',true);
      if (!$pos) continue;
      $latlng = array_map('floatval', explode(',', $pos));
      if (count($latlng)<2) continue;
      $icon = get_post_meta($id,'_map_icon',true);

      $tag_terms   = get_the_terms($id,'post_tag');  if (is_wp_error($tag_terms)||!is_array($tag_terms)) $tag_terms=[];
      $owner_terms = get_the_terms($id,'owner');     if (is_wp_error($owner_terms)||!is_array($owner_terms)) $owner_terms=[];
      $tag_names   = array_map(function($t){return $t->name;}, $tag_terms);
      $owner_names = array_map(function($t){return $t->name;}, $owner_terms);
      $owner       = !empty($owner_names) ? implode(', ', $owner_names) : '';

      $keywords_str = trim(implode(' ', array_filter(array_merge([ get_the_title($id) ], $tag_names, $owner_names))));

      $thumb_id     = (int) get_post_meta($id,'_photo_thumb_id',true);
      $photos_count = (int) get_post_meta($id,'_photo_count',true);
      $thumb_url    = $thumb_id ? wp_get_attachment_image_url($thumb_id,'thumbnail') : '';

      $markers[] = [
        'id'=>$id,
        'lat'=>$latlng[0],
        'lng'=>$latlng[1],
        'iconUrl'=> $icon ? plugins_url('assets/icons/'.$icon, XYZ_MAP_GALLERY_FILE) : '',
        'title'=> get_the_title($id),
        'link'=> get_permalink($id),
        'thumbUrl'=> $thumb_url,
        'count'=> $photos_count,
        'owner'=> $owner,
        'keywords'=> $keywords_str,
      ];
    }

    $payload = [
      'tilesUrl'        => !empty($map->tiles_url) ? $map->tiles_url : '',
      'imageSize'       => ['width'=>(int)$map->image_width, 'height'=>(int)$map->image_height],
      'zoomLevels'      => ['min'=>(int)$map->zoom_min, 'max'=>(int)$map->zoom_max],
      'bounds'          => $bounds,
      'mapMode'         => $map->mode,
      'markers'         => $markers,
      'cluster_markers' => (int)$map->cluster_markers,
      'pluginUrl'       => plugins_url('', XYZ_MAP_GALLERY_FILE),
      'ajaxUrl'         => admin_url('admin-ajax.php'),
      'bboxNonce'       => wp_create_nonce('xyz_bbox_nonce'),
    ];

    set_transient($cache_key, $payload, 5*MINUTE_IN_SECONDS);
  }

  wp_localize_script('xyz-frontend-js', 'xyzMapData_'.(string)$map_id, $payload);
  return sprintf('<div id="xyz-map-%d" style="height:100vh;width:100%%;"></div>', $map_id);
}

/** ============= MINI MAP (PLACE) ============= */
function xyz_render_place_minimap($place_id){
  $place_id = (int)$place_id ?: (int)get_the_ID();
  if (!$place_id) return '';

  global $wpdb;
  $map_id = (int) get_post_meta($place_id,'_map_id',true);
  $pos    = trim((string) get_post_meta($place_id,'_map_position',true));
  if (!$map_id || !$pos || strpos($pos,',')===false) return '';

  list($lat,$lng) = array_map('floatval', explode(',',$pos,2));
  $t = $wpdb->prefix.'xyz_maps';
  $m = $wpdb->get_row($wpdb->prepare("SELECT tiles_url, zoom_min, zoom_max FROM $t WHERE id=%d",$map_id));
  $tiles    = ($m && !empty($m->tiles_url)) ? $m->tiles_url : '';
  $zmin  = $m ? (int)$m->zoom_min : 0;
  $zmax  = $m ? (int)$m->zoom_max : 18;

  wp_enqueue_style('leaflet-css','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',[], '1.9.4');
  wp_enqueue_script('leaflet-js','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',[], '1.9.4', true);
  wp_enqueue_script('xyz-mini-map', plugins_url('assets/js/mini-map.js', XYZ_MAP_GALLERY_FILE), ['leaflet-js'], '1.0', true);

  return sprintf(
    '<div class="xyz-mini-map" data-lat="%s" data-lng="%s" data-map-id="%d" data-tiles="%s" data-zmin="%d" data-zmax="%d" style="height:220px;border-radius:8px;overflow:hidden"></div>',
    esc_attr($lat), esc_attr($lng), (int)$map_id, esc_attr($tiles), (int)$zmin, (int)$zmax
  );
}

/** ============= PHOTOS GRID (PLACE) ============= */
function xyz_render_place_gallery($place_id, $per_page=24){
  $place_id = (int)$place_id ?: (int)get_the_ID();
  if (!$place_id) return '';

  $q = new \WP_Query([
    'post_type'=>'photo_item','post_status'=>'publish',
    'meta_query'=>[['key'=>'_place_id','value'=>$place_id,'compare'=>'=']],
    'posts_per_page'=>(int)$per_page,
    'paged'=>max(1,(int)get_query_var('paged', get_query_var('page',1))),
    'orderby'=>'date','order'=>'DESC',
    'ignore_sticky_posts'=>true,
    'update_post_meta_cache'=>false,
    'update_post_term_cache'=>false,
  ]);

  ob_start();
  if ($q->have_posts()){
    echo '<ul class="xyz-photo-grid" style="list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">';
    while($q->have_posts()){ $q->the_post();
      echo '<li><a href="'.esc_url(get_permalink()).'" style="display:block;text-decoration:none;"><figure style="margin:0;">';
      if (has_post_thumbnail()) the_post_thumbnail('medium',['loading'=>'lazy','style'=>'width:100%;height:auto;display:block;']);
      else echo '<div style="width:100%;aspect-ratio:4/3;background:#eee;"></div>';
      echo '<figcaption style="font-size:.9rem;margin-top:6px;">'.esc_html(get_the_title()).'</figcaption>';
      echo '</figure></a></li>';
    }
    echo '</ul>';
    the_posts_pagination(['mid_size'=>2,'prev_text'=>'«','next_text'=>'»']);
  } else {
    echo '<p>'.esc_html__('No photos yet.','xyz-map-gallery').'</p>';
  }
  wp_reset_postdata();
  return ob_get_clean();
}
