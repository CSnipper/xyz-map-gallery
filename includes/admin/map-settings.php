<?php
if ( ! defined('ABSPATH') ) exit;

if (defined('XYZ_MG_ADMIN_MAP_INCLUDED')) return;
define('XYZ_MG_ADMIN_MAP_INCLUDED', true);

function xyz_map_admin_page() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $table_name = $wpdb->prefix . 'xyz_maps';
    $maps = $wpdb->get_results("SELECT * FROM $table_name");

    if (isset($_POST['xyz_map_submit'])) {
        check_admin_referer('xyz_map_nonce');

        $map_id = intval($_POST['map_id']);
        $tiles_input = isset($_POST['tiles_url']) ? wp_unslash($_POST['tiles_url']) : '';
        $tiles_input = trim($tiles_input);
        // przywróć klamry, jeśli przeglądarka/serwer je zakodował
        $tiles_input = str_replace(['%7B','%7D'], ['{','}'], $tiles_input);

        // opcjonalnie: bardzo lekka walidacja (musi być http/https)
        if (!preg_match('#^https?://#i', $tiles_input)) {
            $tiles_input = '';
        }

        if (isset($_POST['map_id']) && !empty($_POST['map_id'])) {

            if (empty($_POST['map_name']) || empty($_POST['map_mode']) || empty($tiles_input) || empty($_POST['zoom_min']) || empty($_POST['zoom_max'])) {
                echo '<div class="error"><p>' . __('All required fields must be filled.', 'xyz-map-gallery') . '</p></div>';
            } else {
            $map_data = [
              'name'         => sanitize_text_field( $_POST['map_name'] ),
              'mode'         => sanitize_text_field( $_POST['map_mode'] ),
              'tiles_url'    => $tiles_input,
              'image_width'  => isset($_POST['image_width']) ? sanitize_text_field($_POST['image_width']) : '',
              'image_height' => isset($_POST['image_height']) ? sanitize_text_field($_POST['image_height']) : '',
              'zoom_min'     => isset($_POST['zoom_min']) ? absint($_POST['zoom_min']) : 0,
              'zoom_max'     => isset($_POST['zoom_max']) ? absint($_POST['zoom_max']) : 18,
              'bounds'       => wp_json_encode([
                                  'lat1' => isset($_POST['lat1']) ? (float) $_POST['lat1'] : null,
                                  'lng1' => isset($_POST['lng1']) ? (float) $_POST['lng1'] : null,
                                  'lat2' => isset($_POST['lat2']) ? (float) $_POST['lat2'] : null,
                                  'lng2' => isset($_POST['lng2']) ? (float) $_POST['lng2'] : null,
                               ]),
              'cluster_markers' => isset($_POST['cluster_markers']) ? 1 : 0,
            ];
              $result = $wpdb->update($table_name, $map_data, ['id' => $map_id]);
                if ($result !== false) {
                    xyz_bump_cache_ver( (int) $map_id );
                    echo '<div class="updated"><p>' . __('Map updated.', 'xyz-map-gallery') . '</p></div>';
                }

            }
        } else {
            if (empty($_POST['map_name']) || empty($_POST['map_mode']) || empty($_POST['tiles_url']) || empty($_POST['zoom_min']) || empty($_POST['zoom_max'])) {
                echo '<div class="error"><p>' . __('All required fields must be filled.', 'xyz-map-gallery') . '</p></div>';
            } else {
                $map_data = [
                  'name'         => sanitize_text_field( $_POST['map_name'] ),
                  'mode'         => sanitize_text_field( $_POST['map_mode'] ),
                  'tiles_url'    => $tiles_input,
                  'image_width'  => isset($_POST['image_width']) ? sanitize_text_field($_POST['image_width']) : '',
                  'image_height' => isset($_POST['image_height']) ? sanitize_text_field($_POST['image_height']) : '',
                  'zoom_min'     => isset($_POST['zoom_min']) ? absint($_POST['zoom_min']) : 0,
                  'zoom_max'     => isset($_POST['zoom_max']) ? absint($_POST['zoom_max']) : 18,
                  'bounds'       => wp_json_encode([
                                      'lat1' => isset($_POST['lat1']) ? (float) $_POST['lat1'] : null,
                                      'lng1' => isset($_POST['lng1']) ? (float) $_POST['lng1'] : null,
                                      'lat2' => isset($_POST['lat2']) ? (float) $_POST['lat2'] : null,
                                      'lng2' => isset($_POST['lng2']) ? (float) $_POST['lng2'] : null,
                                   ]),
                  'cluster_markers' => isset($_POST['cluster_markers']) ? 1 : 0,
                ];
                $result = $wpdb->insert($table_name, $map_data);
                if ($result !== false) {
                    $new_id = (int) $wpdb->insert_id;
                    xyz_bump_cache_ver( $new_id );
                    echo '<div class="updated"><p>' . __('Map saved.', 'xyz-map-gallery') . '</p></div>';
                } else {
                    echo '<div class="error"><p>' . __('Failed to save map.', 'xyz-map-gallery') . '</p></div>';
                }

            }
        }
    }

    $edit_map = isset($_GET['edit']) && intval($_GET['edit']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit']))) : null;

    ?>
    <div class="wrap">
        <?php if ($edit_map) : ?>
            <h1><?php _e('Edit Map', 'xyz-map-gallery'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('xyz_map_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="map_name"><?php _e('Map Name', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="map_name" id="map_name" value="<?php echo esc_attr($edit_map->name); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="map_mode"><?php _e('Map Mode', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td>
                            <select name="map_mode" id="map_mode" required onchange="toggleMapFields(this.value)">
                                <option value="geo" <?php selected($edit_map->mode, 'geo'); ?>><?php _e('Geographic', 'xyz-map-gallery'); ?></option>
                                <option value="xy" <?php selected($edit_map->mode, 'xy'); ?>><?php _e('Image-based', 'xyz-map-gallery'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tiles_url"><?php _e('Tile URL', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="tiles_url" id="tiles_url" value="<?php echo esc_attr($edit_map->tiles_url); ?>" required></td>
                    </tr>
                    <tr id="image-size-fields" style="display: <?php echo $edit_map->mode === 'xy' ? 'table-row' : 'none'; ?>;">
                        <th><label for="image_width"><?php _e('Image Width', 'xyz-map-gallery'); ?></label></th>
                        <td><input type="text" name="image_width" id="image_width" value="<?php echo esc_attr($edit_map->image_width); ?>"></td>
                    </tr>
                    <tr id="image-size-fields-height" style="display: <?php echo $edit_map->mode === 'xy' ? 'table-row' : 'none'; ?>;">
                        <th><label for="image_height"><?php _e('Image Height', 'xyz-map-gallery'); ?></label></th>
                        <td><input type="text" name="image_height" id="image_height" value="<?php echo esc_attr($edit_map->image_height); ?>"></td>
                    </tr>
                    <tr id="bounds-field" style="display: <?php echo $edit_map->mode !== 'xy' ? 'table-row' : 'none'; ?>;">
                        <th><?php _e('Map Bounds', 'xyz-map-gallery'); ?></th>
                        <td>
                            <?php $bounds = json_decode($edit_map->bounds, true); ?>
                            <table>
                                <tr>
                                    <th><label for="lat1"><?php _e('Lat 1', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lat1" id="lat1" value="<?php echo esc_attr($bounds['lat1']); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="lng1"><?php _e('Lng 1', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lng1" id="lng1" value="<?php echo esc_attr($bounds['lng1']); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="lat2"><?php _e('Lat 2', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lat2" id="lat2" value="<?php echo esc_attr($bounds['lat2']); ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="lng2"><?php _e('Lng 2', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lng2" id="lng2" value="<?php echo esc_attr($bounds['lng2']); ?>"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="zoom_min"><?php _e('Min Zoom', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="zoom_min" id="zoom_min" value="<?php echo esc_attr($edit_map->zoom_min); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="zoom_max"><?php _e('Max Zoom', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="zoom_max" id="zoom_max" value="<?php echo esc_attr($edit_map->zoom_max); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="cluster_markers"><?php _e('Cluster Markers', 'xyz-map-gallery'); ?></label></th>
                        <td><input type="checkbox" name="cluster_markers" id="cluster_markers" value="1" <?php checked($edit_map->cluster_markers, 1); ?>></td>
                    </tr>
                </table>
                <input type="hidden" name="map_id" value="<?php echo $edit_map->id; ?>">
                <p><input type="submit" name="xyz_map_submit" class="button-primary" value="<?php _e('Save Map', 'xyz-map-gallery'); ?>"></p>
                <script type="text/javascript">
                    function toggleMapFields(mode) {
                        var imageFields = document.getElementById('image-size-fields');
                        var imageHeightFields = document.getElementById('image-size-fields-height');
                        var boundsField = document.getElementById('bounds-field');
                        if (mode === 'xy') {
                            imageFields.style.display = 'table-row';
                            imageHeightFields.style.display = 'table-row';
                            boundsField.style.display = 'none';
                        } else {
                            imageFields.style.display = 'none';
                            imageHeightFields.style.display = 'none';
                            boundsField.style.display = 'table-row';
                        }
                    }
                    document.addEventListener('DOMContentLoaded', function() {
                        var select = document.getElementById('map_mode');
                        toggleMapFields(select.value);
                    });
                </script>
            </form>
        <?php elseif (isset($_GET['add_new'])) : ?>
            <h1><?php _e('Add New Map', 'xyz-map-gallery'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('xyz_map_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="map_name"><?php _e('Map Name', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="map_name" id="map_name" value="" required></td>
                    </tr>
                    <tr>
                        <th><label for="map_mode"><?php _e('Map Mode', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td>
                            <select name="map_mode" id="map_mode" required onchange="toggleMapFields(this.value)">
                                <option value="geo"><?php _e('Geographic', 'xyz-map-gallery'); ?></option>
                                <option value="xy"><?php _e('Image-based', 'xyz-map-gallery'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tiles_url"><?php _e('Tile URL', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="tiles_url" id="tiles_url" value="" required></td>
                    </tr>
                    <tr id="image-size-fields" style="display: none;">
                        <th><label for="image_width"><?php _e('Image Width', 'xyz-map-gallery'); ?></label></th>
                        <td><input type="text" name="image_width" id="image_width" value=""></td>
                    </tr>
                    <tr id="image-size-fields-height" style="display: none;">
                        <th><label for="image_height"><?php _e('Image Height', 'xyz-map-gallery'); ?></label></th>
                        <td><input type="text" name="image_height" id="image_height" value=""></td>
                    </tr>
                    <tr id="bounds-field" style="display: table-row;">
                        <th><?php _e('Map Bounds', 'xyz-map-gallery'); ?></th>
                        <td>
                            <table>
                                <tr>
                                    <th><label for="lat1"><?php _e('Lat 1', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lat1" id="lat1" value=""></td>
                                </tr>
                                <tr>
                                    <th><label for="lng1"><?php _e('Lng 1', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lng1" id="lng1" value=""></td>
                                </tr>
                                <tr>
                                    <th><label for="lat2"><?php _e('Lat 2', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lat2" id="lat2" value=""></td>
                                </tr>
                                <tr>
                                    <th><label for="lng2"><?php _e('Lng 2', 'xyz-map-gallery'); ?></label></th>
                                    <td><input type="text" name="lng2" id="lng2" value=""></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="zoom_min"><?php _e('Min Zoom', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="zoom_min" id="zoom_min" value="" required></td>
                    </tr>
                    <tr>
                        <th><label for="zoom_max"><?php _e('Max Zoom', 'xyz-map-gallery'); ?> <span style="color: red;">*</span></label></th>
                        <td><input type="text" name="zoom_max" id="zoom_max" value="" required></td>
                    </tr>
                    <tr>
                        <th><label for="cluster_markers"><?php _e('Cluster Markers', 'xyz-map-gallery'); ?></label></th>
                        <td><input type="checkbox" name="cluster_markers" id="cluster_markers" value="1"></td>
                    </tr>
                </table>
                <p><input type="submit" name="xyz_map_submit" class="button-primary" value="<?php _e('Save Map', 'xyz-map-gallery'); ?>"></p>
                <script type="text/javascript">
                    function toggleMapFields(mode) {
                        var imageFields = document.getElementById('image-size-fields');
                        var imageHeightFields = document.getElementById('image-size-fields-height');
                        var boundsField = document.getElementById('bounds-field');
                        if (mode === 'xy') {
                            imageFields.style.display = 'table-row';
                            imageHeightFields.style.display = 'table-row';
                            boundsField.style.display = 'none';
                        } else {
                            imageFields.style.display = 'none';
                            imageHeightFields.style.display = 'none';
                            boundsField.style.display = 'table-row';
                        }
                    }
                    document.addEventListener('DOMContentLoaded', function() {
                        var select = document.getElementById('map_mode');
                        toggleMapFields(select.value);
                    });
                </script>
            </form>
        <?php else : ?>
            <h1><?php _e('Maps', 'xyz-map-gallery'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=xyz-maps&add_new=1'); ?>" class="page-title-action"><?php _e('Add New Map', 'xyz-map-gallery'); ?></a>
            <h2><?php _e('Map List', 'xyz-map-gallery'); ?></h2>
            <?php if ($maps) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mode</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maps as $map) : ?>
                            <tr>
                                <td><?php echo esc_html($map->id); ?></td>
                                <td><?php echo esc_html($map->name); ?></td>
                                <td><?php echo esc_html($map->mode); ?></td>
                                <td><a href="<?php echo admin_url('admin.php?page=xyz-maps&edit=' . $map->id); ?>"><?php _e('Edit', 'xyz-map-gallery'); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No maps found.', 'xyz-map-gallery'); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
?>