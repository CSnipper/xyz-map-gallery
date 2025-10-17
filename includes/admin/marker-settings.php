<?php
if (defined('XYZ_MG_ADMIN_MARKER_INCLUDED')) return;
define('XYZ_MG_ADMIN_MARKER_INCLUDED', true);

function xyz_marker_register_meta_boxes() {
    add_meta_box('xyz_marker_settings',__('Marker Settings','xyz-map-gallery'),'xyz_marker_meta_box_callback','map_marker','normal','high');
}
add_action('add_meta_boxes','xyz_marker_register_meta_boxes');

function xyz_marker_meta_box_callback($post) {
    wp_nonce_field('xyz_marker_meta_nonce', 'xyz_map_nonce');

    $position       = get_post_meta($post->ID, '_map_position', true);
    $icon           = get_post_meta($post->ID, '_map_icon', true);
    $is_sale        = get_post_meta($post->ID, '_is_sale_item', true);
    $linked_product = get_post_meta($post->ID, '_linked_product_id', true);
    $map_id         = get_post_meta($post->ID, '_map_id', true) ?: 0;

    global $wpdb;
    $maps_table = $wpdb->prefix . 'xyz_maps';
    $maps = $wpdb->get_results("SELECT id, name FROM $maps_table");

    // lista lokalnych ikon
    $icons = glob(plugin_dir_path(XYZ_MAP_GALLERY_FILE) . 'assets/icons/*.{png,svg}', GLOB_BRACE);
    $icons = array_map('basename', $icons);

    $latlng = $position ? explode(',', $position) : [null, null];

    // ustal URL podglądu
    $icon_preview = '';
    if ($icon) {
        if (filter_var($icon, FILTER_VALIDATE_URL)) {
            $icon_preview = $icon; // pełny URL
        } else {
            $icon_preview = plugins_url('assets/icons/' . $icon, XYZ_MAP_GALLERY_FILE);
        }
    }
    ?>
    <div class="xyz-marker-settings">
        <div style="display:flex;">
            <div style="flex:1; padding-right:20px;">
                <p>
                    <label for="map_id"><?php _e('Select Map','xyz-map-gallery'); ?></label>
                    <select name="map_id" id="map_id">
                        <option value="0"><?php _e('Default Map','xyz-map-gallery'); ?></option>
                        <?php foreach ($maps as $map): ?>
                            <option value="<?php echo esc_attr($map->id); ?>" <?php selected($map_id, $map->id); ?>>
                                <?php echo esc_html($map->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label for="geo_address"><?php _e('Search Address','xyz-map-gallery'); ?></label>
                    <input type="text" id="geo_address" name="geo_address"
                           placeholder="<?php esc_attr_e('e.g., Maniowy 16, Poland','xyz-map-gallery'); ?>">
                </p>

                <p>
                    <label for="map_position"><?php _e('Pin Position','xyz-map-gallery'); ?></label>
                    <input type="text" name="map_position" id="map_position"
                           value="<?php echo esc_attr($position); ?>" readonly>
                </p>

                <p>
                    <label><?php _e('Icon','xyz-map-gallery'); ?>: </label>
                    <span id="icon-preview" style="margin-top:10px;">
                        <?php if ($icon_preview): ?>
                            <img src="<?php echo esc_url($icon_preview); ?>" style="max-width:24px;vertical-align: middle;">
                        <?php endif; ?>
                    </span>
                    <a href="#" id="select-icon"><?php _e('Choose Icon','xyz-map-gallery'); ?></a>
                    <input type="hidden" name="map_icon" id="map_icon" value="<?php echo esc_attr($icon); ?>">
                    
                </p>

                <?php if (get_option('xyz_map_woo_enabled', 0)): ?>
                <p>
                    <label><input type="checkbox" name="is_sale_item" value="1" <?php checked($is_sale,1); ?>>
                        <?php _e('Sale Item (WooCommerce)','xyz-map-gallery'); ?></label>
                </p>
                <p class="sale-field" style="display:<?php echo $is_sale ? 'block' : 'none'; ?>;">
                    <label for="linked_product"><?php _e('Linked Product','xyz-map-gallery'); ?></label>
                    <select name="linked_product" id="linked_product">
                        <option value=""><?php _e('Create New or Select','xyz-map-gallery'); ?></option>
                        <?php
                        if (class_exists('WooCommerce')) {
                            $products = wc_get_products(['limit'=>-1,'status'=>'publish']);
                            foreach ($products as $product) {
                                echo '<option value="'.$product->get_id().'"'.
                                     selected($linked_product, $product->get_id(), false).'>'
                                     .esc_html($product->get_name()).'</option>';
                            }
                        }
                        ?>
                    </select>
                </p>
                <?php endif; ?>

                <p>
                    <label for="photo_taxonomy"><?php _e('Photo linkage taxonomy','xyz-map-gallery'); ?></label>
                    <?php
                    // Find taxonomies that are registered for both map_marker and map_photo
                    $tax_marker = get_object_taxonomies('map_marker','objects');
                    $tax_photo  = get_object_taxonomies('map_photo','objects');
                    $marker_keys = is_array($tax_marker) ? array_keys($tax_marker) : [];
                    $photo_keys  = is_array($tax_photo)  ? array_keys($tax_photo)  : [];
                    $shared = array_values(array_intersect($marker_keys, $photo_keys));
                    $selected_tax = get_post_meta($post->ID, '_photo_taxonomy', true);
                    if (empty($shared)) {
                        echo '<p class="description">'.esc_html__('No shared taxonomies between markers and photos.','xyz-map-gallery').'</p>';
                    } else {
                        echo '<select name="photo_taxonomy" id="photo_taxonomy">';
                        echo '<option value="">'.esc_html__('&mdash; none &mdash;','xyz-map-gallery').'</option>';
                        foreach ($shared as $t) {
                            $label = isset($tax_marker[$t]) ? $tax_marker[$t]->labels->name : $t;
                            printf('<option value="%s" %s>%s</option>', esc_attr($t), selected($selected_tax, $t, false), esc_html($label));
                        }
                        echo '</select>';
                        echo '<p class="description">'.esc_html__('When set, the gallery will include photos that share the same term(s) in this taxonomy as the marker.','xyz-map-gallery').'</p>';
                    }
                    ?>
                </p>
            </div>

            <div style="flex:1; padding-left:20px;" id="map-column"
                 style="display:<?php echo $map_id ? 'block':'none'; ?>;">
                <div id="marker-map-preview" style="height:400px; width:100%;"></div>
            </div>
        </div>
    </div>
    <?php
}


function xyz_marker_save_post($post_id){
    if(!isset($_POST['xyz_map_nonce'])||!wp_verify_nonce($_POST['xyz_map_nonce'],'xyz_marker_meta_nonce')) return;
    $icon=sanitize_file_name($_POST['map_icon']??'');
    $icon_url=esc_url_raw($_POST['map_icon_url']??'');

    if($icon){ update_post_meta($post_id,'_map_icon',$icon); delete_post_meta($post_id,'_map_icon_url'); }
    elseif($icon_url){ update_post_meta($post_id,'_map_icon_url',$icon_url); delete_post_meta($post_id,'_map_icon'); }

    // Save selected photo taxonomy (optional)
    if (isset($_POST['photo_taxonomy'])) {
        $tax = sanitize_text_field($_POST['photo_taxonomy']);
        if ($tax) update_post_meta($post_id, '_photo_taxonomy', $tax);
        else delete_post_meta($post_id, '_photo_taxonomy');
    }
}
add_action('save_post_map_marker','xyz_marker_save_post');

/**
 * Admin list: add 'Map' column to markers list and a filter dropdown
 */
add_filter('manage_edit-map_marker_columns', function($cols){
    $new = [];
    foreach($cols as $k=>$v){
        $new[$k] = $v;
        if ($k === 'title') {
            // insert after title
            $new['xyz_map_column'] = __('Map','xyz-map-gallery');
        }
    }
    return $new;
});

add_action('manage_map_marker_posts_custom_column', function($column, $post_id){
    if ($column !== 'xyz_map_column') return;
    $map_id = get_post_meta($post_id, '_map_id', true);
    if (!$map_id) {
        echo '<span class="xyz-map-col">'.esc_html__('Default','xyz-map-gallery').'</span>';
        return;
    }
    global $wpdb;
    $maps_table = $wpdb->prefix . 'xyz_maps';
    $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $maps_table WHERE id=%d", $map_id));
    if ($name) {
        echo '<span class="xyz-map-col">'.esc_html($name).' ('.intval($map_id).')</span>';
    } else {
        echo '<span class="xyz-map-col">#'.intval($map_id).'</span>';
    }
}, 10, 2);

// Add filter dropdown above list table
add_action('restrict_manage_posts', function($post_type){
    if ($post_type !== 'map_marker') return;
    global $wpdb;
    $maps_table = $wpdb->prefix . 'xyz_maps';
    $maps = $wpdb->get_results("SELECT id,name FROM $maps_table ORDER BY name ASC");

    $current = isset($_GET['filter_map']) ? sanitize_text_field($_GET['filter_map']) : '';
    echo '<select name="filter_map" id="filter_map">';
    echo '<option value="">'.esc_html__('All maps','xyz-map-gallery').'</option>';
    echo '<option value="0"'.selected($current, '0', false).'>'.esc_html__('Unassigned','xyz-map-gallery').'</option>';
    foreach ($maps as $m) {
        printf('<option value="%d" %s>%s</option>', intval($m->id), selected($current, (string)$m->id, false), esc_html($m->name));
    }
    echo '</select>';
});

// Apply filter to query
add_action('pre_get_posts', function($query){
    if (!is_admin() || !$query->is_main_query()) return;
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-map_marker') return;
    if (!isset($_GET['filter_map']) || $_GET['filter_map']==='') return;
    $val = sanitize_text_field($_GET['filter_map']);
    if ($val === '0') {
        // unassigned: meta = 0 OR meta not exists
        $meta_query = [ 'relation' => 'OR',
            [ 'key' => '_map_id', 'value' => '0', 'compare' => '=' ],
            [ 'key' => '_map_id', 'compare' => 'NOT EXISTS' ],
        ];
    } else if (is_numeric($val)) {
        $meta_query = [ [ 'key' => '_map_id', 'value' => intval($val), 'compare' => '=' ] ];
    } else {
        return;
    }

    $existing = $query->get('meta_query');
    if (is_array($existing)) {
        $meta_query = array_merge($existing, $meta_query);
    }
    $query->set('meta_query', $meta_query);
});
