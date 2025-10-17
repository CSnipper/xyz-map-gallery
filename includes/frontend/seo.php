<?php
if (!defined('ABSPATH')) exit;

add_action('wp_head', function(){
  if (is_singular('map_marker')) {
    $url = get_permalink(); 
  } elseif (is_singular('map_photo')) {
  $url = get_permalink(); 
  } else {
    return;
  }
  echo '<link rel="canonical" href="'.esc_url($url).'" />'."\n";
}, 9);

add_action('wp_head', function(){
  if (is_admin()) return;

  // Place (map_marker)
  if (is_singular('map_marker')) {
    $id   = get_the_ID();
    $pos  = trim((string) get_post_meta($id,'_map_position',true));
    $img  = get_the_post_thumbnail_url($id, 'large');
    $geo  = null;
    if ($pos && strpos($pos,',')!==false){
      list($lat,$lng)=array_map('floatval',explode(',',$pos,2));
      if (is_finite($lat) && is_finite($lng)){
        $geo = ['@type'=>'GeoCoordinates','latitude'=>$lat,'longitude'=>$lng];
      }
    }
    $data = [
      '@context' => 'https://schema.org',
      '@type'    => 'Place',
      '@id'      => get_permalink($id).'#place',
      'name'     => get_the_title($id),
      'url'      => get_permalink($id),
    ];
    if ($geo) $data['geo'] = $geo;
    if ($img) $data['image'] = $img;

    echo '<script type="application/ld+json">'.wp_json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>'."\n";
    return;
  }

  // ImageObject (map_photo)
  if (is_singular('map_photo')) {
    $id   = get_the_ID();
    $img  = wp_get_attachment_image_src( get_post_thumbnail_id($id), 'full' );
    if (!$img) return;
    $data = [
      '@context' => 'https://schema.org',
      '@type'    => 'ImageObject',
      '@id'      => get_permalink($id).'#image',
      'name'     => get_the_title($id),
      'contentUrl' => $img[0],
      'url'        => get_permalink($id),
      'width'      => isset($img[1]) ? (int)$img[1] : null,
      'height'     => isset($img[2]) ? (int)$img[2] : null,
    ];
    echo '<script type="application/ld+json">'.wp_json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>'."\n";
  }
}, 11);
