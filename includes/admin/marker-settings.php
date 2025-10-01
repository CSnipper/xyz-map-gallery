<?php
if (defined('XYZ_MG_ADMIN_MARKER_INCLUDED')) return;
define('XYZ_MG_ADMIN_MARKER_INCLUDED', true);

function xyz_marker_register_meta_boxes() {
    add_meta_box('xyz_marker_settings',__('Marker Settings','xyz-map-gallery'),'xyz_marker_meta_box_callback','gallery_item','normal','high');
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
            $icon_preview = plugins_url('assets/icons/' . $icon, __FILE__);
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
                    <label><?php _e('Icon','xyz-map-gallery'); ?></label>
                    <a href="#" id="select-icon"><?php _e('Choose Icon','xyz-map-gallery'); ?></a>
                    <input type="hidden" name="map_icon" id="map_icon" value="<?php echo esc_attr($icon); ?>">
                    <div id="icon-preview" style="margin-top:10px;">
                        <?php if ($icon_preview): ?>
                            <img src="<?php echo esc_url($icon_preview); ?>" style="max-width:50px;">
                        <?php endif; ?>
                    </div>
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
}
add_action('save_post_gallery_item','xyz_marker_save_post');
