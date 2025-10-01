<?php
if ( ! defined('ABSPATH') ) exit;
namespace XYZ_Map_Gallery;

if (!defined('ABSPATH')) exit;

class Plugin {
    public function __construct() {
      add_action('init',               [$this,'load_textdomain']);
      add_action('admin_menu',         [$this,'admin_menu']);
      add_action('admin_init',         [$this,'register_settings']);

      // AJAX
      add_action('wp_ajax_xyz_get_map_data',          [$this,'xyz_get_map_data']);
      add_action('wp_ajax_xyz_get_markers_in_bbox',   [$this,'ajax_get_markers_in_bbox']);
      add_action('wp_ajax_nopriv_xyz_get_markers_in_bbox', [$this,'ajax_get_markers_in_bbox']);
    }

    public function load_textdomain(){
      load_plugin_textdomain('xyz-map-gallery', false, dirname(plugin_basename(XYZ_MAP_GALLERY_FILE)).'/languages/');
    }

    public function admin_menu(){
      add_menu_page(
        __('XYZ Map Gallery','xyz-map-gallery'),
        __('XYZ Map Gallery','xyz-map-gallery'),
        'manage_options',
        'xyz-map-gallery',
        [$this,'settings_page'],
        'dashicons-location-alt',
        4
      );

      add_submenu_page('xyz-map-gallery', __('Maps','xyz-map-gallery'),     __('Maps','xyz-map-gallery'),     'manage_options','xyz-maps', 'xyz_map_admin_page');
      add_submenu_page('xyz-map-gallery', __('Settings','xyz-map-gallery'), __('Settings','xyz-map-gallery'), 'manage_options','xyz-map-gallery', [$this,'settings_page']);
    }

    public function register_settings(){
        register_setting('xyz_maps_group','xyz_map_woo_enabled');

        register_setting('xyz_maps_group','xyz_enable_gutenberg', ['type'=>'integer','default'=>1]);
        register_setting('xyz_maps_group','xyz_show_gutenberg',   ['type'=>'integer','default'=>1]);
        register_setting('xyz_maps_group','xyz_enable_elementor', ['type'=>'integer','default'=>1]);
        register_setting('xyz_maps_group','xyz_show_elementor',   ['type'=>'integer','default'=>1]);
        register_setting('xyz_maps_group','xyz_enable_wpbakery',  ['type'=>'integer','default'=>1]);
        register_setting('xyz_maps_group','xyz_show_wpbakery',    ['type'=>'integer','default'=>1]);
        register_setting('xyz_maps_group','xyz_enable_shortcode', ['type'=>'integer','default'=>1]);
        register_setting('xyz_maps_group','xyz_show_shortcode',   ['type'=>'integer','default'=>1]);
    }

    public function settings_page(){
        if (!current_user_can('manage_options')) return;
            if ( isset($_POST['xyz_map_settings']) ) {
                check_admin_referer('xyz_map_settings_nonce');

                update_option('xyz_map_woo_enabled',   !empty($_POST['woo_enabled']) ? 1 : 0);

                update_option('xyz_enable_gutenberg',  !empty($_POST['xyz_enable_gutenberg']) ? 1 : 0);
                update_option('xyz_show_gutenberg',    !empty($_POST['xyz_show_gutenberg'])   ? 1 : 0);
                update_option('xyz_enable_elementor',  !empty($_POST['xyz_enable_elementor']) ? 1 : 0);
                update_option('xyz_show_elementor',    !empty($_POST['xyz_show_elementor'])   ? 1 : 0);
                update_option('xyz_enable_wpbakery',   !empty($_POST['xyz_enable_wpbakery'])  ? 1 : 0);
                update_option('xyz_show_wpbakery',     !empty($_POST['xyz_show_wpbakery'])    ? 1 : 0);
                update_option('xyz_enable_shortcode',  !empty($_POST['xyz_enable_shortcode']) ? 1 : 0);
                update_option('xyz_show_shortcode',    !empty($_POST['xyz_show_shortcode'])   ? 1 : 0);

                echo '<div class="updated"><p>'.esc_html__('Settings saved.','xyz-map-gallery').'</p></div>';
              }

              $woo_enabled     = (int) get_option('xyz_map_woo_enabled', 0);
              $gutenberg_on    = (int) get_option('xyz_enable_gutenberg', 1);
              $gutenberg_front = (int) get_option('xyz_show_gutenberg',   1);
              $elementor_on    = (int) get_option('xyz_enable_elementor', 1);
              $elementor_front = (int) get_option('xyz_show_elementor',   1);
              $wpbakery_on     = (int) get_option('xyz_enable_wpbakery',  1);
              $wpbakery_front  = (int) get_option('xyz_show_wpbakery',    1);
              $short_on        = (int) get_option('xyz_enable_shortcode', 1);
              $short_front     = (int) get_option('xyz_show_shortcode',   1);
            ?>
        <div class="wrap">
          <h1><?php _e('XYZ Map Gallery Settings','xyz-map-gallery'); ?></h1>
          <form method="post" action="">
            <?php wp_nonce_field('xyz_map_settings_nonce'); ?>
            <table class="form-table">
              <tr>
                <th><label for="woo_enabled"><?php _e('Enable WooCommerce','xyz-map-gallery'); ?></label></th>
                <td>
                  <input type="checkbox" name="woo_enabled" id="woo_enabled" value="1" <?php checked($woo_enabled,1); ?> <?php echo !class_exists('WooCommerce')?'disabled':''; ?>>
                  <?php if (!class_exists('WooCommerce')) echo '<em>'.esc_html__('WooCommerce not detected.','xyz-map-gallery').'</em>'; ?>
                </td>
              </tr>

              <tr><th><?php _e('Gutenberg','xyz-map-gallery'); ?></th>
                <td>
                  <label><input type="checkbox" name="xyz_enable_gutenberg" id="xyz_enable_gutenberg" value="1" <?php checked($gutenberg_on,1); ?>> <?php _e('Enable blocks','xyz-map-gallery'); ?></label><br>
                  <label><input type="checkbox" name="xyz_show_gutenberg"   id="xyz_show_gutenberg"   value="1" <?php checked($gutenberg_front,1); ?>> <?php _e('Render on front-end','xyz-map-gallery'); ?></label>
                </td>
              </tr>

              <tr><th><?php _e('Elementor','xyz-map-gallery'); ?></th>
                <td>
                  <label><input type="checkbox" name="xyz_enable_elementor" id="xyz_enable_elementor" value="1" <?php checked($elementor_on,1); ?>> <?php _e('Enable widgets','xyz-map-gallery'); ?></label><br>
                  <label><input type="checkbox" name="xyz_show_elementor"   id="xyz_show_elementor"   value="1" <?php checked($elementor_front,1); ?>> <?php _e('Render on front-end','xyz-map-gallery'); ?></label>
                </td>
              </tr>

              <tr><th><?php _e('WPBakery','xyz-map-gallery'); ?></th>
                <td>
                  <label><input type="checkbox" name="xyz_enable_wpbakery" id="xyz_enable_wpbakery" value="1" <?php checked($wpbakery_on,1); ?>> <?php _e('Enable integration','xyz-map-gallery'); ?></label><br>
                  <label><input type="checkbox" name="xyz_show_wpbakery"   id="xyz_show_wpbakery"   value="1" <?php checked($wpbakery_front,1); ?>> <?php _e('Render on front-end','xyz-map-gallery'); ?></label>
                </td>
              </tr>

              <tr><th><?php _e('Shortcodes','xyz-map-gallery'); ?></th>
                <td>
                  <label><input type="checkbox" name="xyz_enable_shortcode" id="xyz_enable_shortcode" value="1" <?php checked($short_on,1); ?>> <?php _e('Enable shortcodes','xyz-map-gallery'); ?></label><br>
                  <label><input type="checkbox" name="xyz_show_shortcode"   id="xyz_show_shortcode"   value="1" <?php checked($short_front,1); ?>> <?php _e('Render on front-end','xyz-map-gallery'); ?></label>
                </td>
              </tr>
            </table>

            <p><input type="submit" name="xyz_map_settings" class="button-primary" value="<?php esc_attr_e('Save Settings','xyz-map-gallery'); ?>"></p>
          </form>
        </div>
        <?php
    }


    public function render_map_block($attributes){
      $map_id = isset($attributes['map_id']) ? (int)$attributes['map_id'] : 0;
      return $map_id ? xyz_render_big_map($map_id) : '<p>'.esc_html__('No map selected.','xyz-map-gallery').'</p>';
    }


    public function xyz_get_map_data(){
      send_nosniff_header();
      nocache_headers();

      if ( ! current_user_can('edit_posts') ) {
        wp_send_json_error(['message'=>'forbidden'], 403);
      }
      if ( ! check_ajax_referer('xyz_marker_nonce', '_wpnonce', false) ) {
        wp_send_json_error(['message'=>'bad nonce'], 403);
      }

      $map_id = isset($_GET['map_id']) ? absint($_GET['map_id']) : 0;
      if ( ! $map_id ) {
        wp_send_json_error(['message'=>'missing map_id'], 400);
      }

      global $wpdb;
      $table = $wpdb->prefix.'xyz_maps';
      $map = $wpdb->get_row($wpdb->prepare(
        "SELECT id, tiles_url, mode, zoom_min, zoom_max, bounds, image_width, image_height
         FROM $table WHERE id=%d", $map_id
      ));
      if ( ! $map ) {
        wp_send_json_error(['message'=>'map not found'], 404);
      }

      $bounds = null;
      if (!empty($map->bounds)) {
        $b = json_decode($map->bounds, true);
        if (is_array($b) && isset($b['lat1'],$b['lng1'],$b['lat2'],$b['lng2'])) {
          $bounds = [
            [ (float)$b['lat1'], (float)$b['lng1'] ],
            [ (float)$b['lat2'], (float)$b['lng2'] ],
          ];
        }
      }

      $data = [
        'tiles_url' => !empty($map->tiles_url) ? esc_attr($map->tiles_url) : '',
        'mode'         => in_array($map->mode, ['geo','xy'], true) ? $map->mode : 'geo',
        'zoom_min'     => max(0, (int)$map->zoom_min),
        'zoom_max'     => max(0, (int)$map->zoom_max ?: 18),
        'bounds'       => $bounds,
        'image_width'  => max(0, (int)$map->image_width ?: 400),
        'image_height' => max(0, (int)$map->image_height ?: 400),
      ];

      wp_send_json_success($data);
    }


    public function ajax_get_markers_in_bbox(){
      send_nosniff_header();
      nocache_headers();

      if ( ! check_ajax_referer('xyz_bbox_nonce', 'nonce', false) ) {
        wp_send_json_error(['message'=>'Bad nonce'], 403);
      }

      $map_id = isset($_REQUEST['map_id']) ? absint($_REQUEST['map_id']) : 0;
      $bbox   = isset($_REQUEST['bbox'])   ? trim((string)$_REQUEST['bbox']) : '';
      $zoom   = isset($_REQUEST['zoom'])   ? max(0, min(30, absint($_REQUEST['zoom']))) : 0;
      $limit  = isset($_REQUEST['limit'])  ? absint($_REQUEST['limit']) : 300;
      if ($limit < 1)  $limit = 1;
      if ($limit > 500) $limit = 500;

      if (!$map_id || !$bbox) {
        wp_send_json_error(['message'=>'Missing map_id or bbox'], 400);
      }

      if ( ! preg_match('/^\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*$/', $bbox) ) {
        wp_send_json_error(['message'=>'Bad bbox format'], 400);
      }
      list($south,$west,$north,$east) = array_map('floatval', explode(',', $bbox, 4));

      global $wpdb;
      $tbl = $wpdb->prefix.'xyz_maps';
      $map = $wpdb->get_row($wpdb->prepare("SELECT bounds FROM $tbl WHERE id=%d", $map_id));
      if ($map && $map->bounds) {
        $b = json_decode($map->bounds, true);
        if (isset($b['lat1'],$b['lng1'],$b['lat2'],$b['lng2'])) {
          $minLat = min((float)$b['lat1'], (float)$b['lat2']);
          $maxLat = max((float)$b['lat1'], (float)$b['lat2']);
          $minLng = min((float)$b['lng1'], (float)$b['lng2']);
          $maxLng = max((float)$b['lng1'], (float)$b['lng2']);
          $south = max($south, $minLat);
          $north = min($north, $maxLat);
          $west  = max($west,  $minLng);
          $east  = min($east,  $maxLng);
          if ($south > $north || $west > $east) {
            wp_send_json_success([]);
        }
      }

      $ip  = xyz_client_ip();
      $key = 'xyz_bbox_rl_'.md5($ip.'|'.(string)$map_id);
      $hits = (int) get_transient($key);
      $hits++;
      if ($hits > 15) {
        wp_send_json_error(['message'=>'rate limited'], 429);
      }
      set_transient($key, $hits, 30);

    $ids = get_posts([
      'post_type'=>'gallery_item','post_status'=>'publish','fields'=>'ids',
      'posts_per_page'=>-1,'no_found_rows'=>true,
      'meta_query'=>[[ 'key'=>'_map_id','value'=>$map_id,'compare'=>'=' ]]
    ]);

    if (empty($ids)) wp_send_json_success([]);

    $bbox_key = implode(',', array_map(function($v){ return number_format((float)$v, 6, '.', ''); }, [ $south,$west,$north,$east ]));
    $cache_key = 'xyz_bbox_resp_'.md5($map_id.'|'.$bbox_key.'|'.$zoom);
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        wp_send_json_success($cached);
    }

    
    $out = [];
    foreach($ids as $id){
      $pos = get_post_meta($id,'_map_position',true);
      if (!$pos || strpos($pos,',')===false) continue;
      [$lat,$lng] = array_map('floatval', explode(',', $pos, 2));
      if ($lat<$south || $lat>$north) continue;
      if ($lng<$west  || $lng>$east)  continue;

      $icon = get_post_meta($id,'_map_icon',true);
      $tag_terms   = get_the_terms($id,'post_tag');  if (is_wp_error($tag_terms)||!is_array($tag_terms)) $tag_terms=[];
      $owner_terms = get_the_terms($id,'owner');     if (is_wp_error($owner_terms)||!is_array($owner_terms)) $owner_terms=[];
      $tag_names   = array_map(fn($t)=>$t->name, $tag_terms);
      $owner_names = array_map(fn($t)=>$t->name, $owner_terms);
      $owner       = !empty($owner_names) ? implode(', ', $owner_names) : '';
      $keywords_str = trim(implode(' ', array_filter(array_merge([get_the_title($id)], $tag_names, $owner_names))));

      $thumb_id     = (int) get_post_meta($id,'_photo_thumb_id',true);
      $photos_count = (int) get_post_meta($id,'_photo_count',true);
      $thumb_url    = $thumb_id ? wp_get_attachment_image_url($thumb_id,'thumbnail') : '';

      $out[] = [
        'id'=>$id,'lat'=>$lat,'lng'=>$lng,
        'iconUrl'=> $icon ? plugins_url('assets/icons/'.$icon, XYZ_MAP_GALLERY_FILE) : '',
        'title'=> get_the_title($id),
        'link'=> get_permalink($id),
        'thumbUrl'=> $thumb_url,
        'count'=> $photos_count,
        'owner'=> $owner,
        'keywords'=> $keywords_str,
      ];
      if (count($out) >= $limit) break;
    }
    set_transient($cache_key, $out, 8);
    wp_send_json_success($out);
  }
}
