<?php
if (!defined('ABSPATH')) exit;

/**
 * 1) Add an item to the bulk actions menu
 */
add_filter('bulk_actions-edit-map_marker', function($actions){
    $actions['xyz_assign_map'] = __('Assign to map…', 'xyz-map-gallery');
    return $actions;
});

/**
 * 2) UI: select with maps next to "Bulk actions"
 */
add_action('admin_footer-edit.php', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-map_marker') return;

    global $wpdb;
    $tbl = $wpdb->prefix.'xyz_maps';
    $rows = $wpdb->get_results("SELECT id, name FROM {$tbl} ORDER BY name ASC");
    ?>
    <!-- Bulk assign UI behavior moved to enqueued script assets/js/admin-bulk.js -->
    <?php
});

/**
 * ...existing code...
 */
add_filter('handle_bulk_actions-edit-map_marker', function($redirect_to, $doaction, $post_ids){
    if ($doaction !== 'xyz_assign_map') return $redirect_to;

    if (!current_user_can('edit_posts')) {
        return add_query_arg(['xyz_assign_map'=>'denied'], $redirect_to);
    }

    check_admin_referer('bulk-posts');

    $map_id = isset($_REQUEST['xyz_target_map']) ? absint($_REQUEST['xyz_target_map']) : 0;
    if (!$map_id) {
        return add_query_arg(['xyz_assign_map'=>'nomap'], $redirect_to);
    }

    $updated = 0;
    foreach ($post_ids as $pid){
        if (!current_user_can('edit_post', $pid)) continue;
        update_post_meta($pid, '_map_id', $map_id);
        $updated++;
    }

    return add_query_arg([
        'xyz_assign_map' => 'ok',
        'count'          => $updated,
        'map'            => $map_id,
    ], $redirect_to);
}, 10, 3);

/**
 * 4) Komunikat po przekierowaniu
 */
add_action('admin_notices', function(){
    if (!is_admin() || !isset($_GET['xyz_assign_map'])) return;

    $code = sanitize_key($_GET['xyz_assign_map']);
    if ($code === 'ok') {
        $count = isset($_GET['count']) ? absint($_GET['count']) : 0;
        $map   = isset($_GET['map'])   ? absint($_GET['map'])   : 0;
        echo '<div class="notice notice-success is-dismissible"><p>'.
             sprintf(esc_html__('%d item(s) assigned to map #%d.', 'xyz-map-gallery'), $count, $map).
             '</p></div>';
    } elseif ($code === 'nomap') {
        echo '<div class="notice notice-error is-dismissible"><p>'.
             esc_html__('No map selected for bulk action.', 'xyz-map-gallery').
             '</p></div>';
    } elseif ($code === 'denied') {
        echo '<div class="notice notice-error is-dismissible"><p>'.
             esc_html__('You do not have permission to perform this action.', 'xyz-map-gallery').
             '</p></div>';
    }
});
