<?php
if (!defined('ABSPATH')) exit;

/**
 * 1) Dodaj pozycję w menu akcji zbiorczych
 */
add_filter('bulk_actions-edit-gallery_item', function($actions){
    $actions['xyz_assign_map'] = __('Assign to map…', 'xyz-map-gallery');
    return $actions;
});

/**
 * 2) UI: select z mapami obok „Działania zbiorcze”
 */
add_action('admin_footer-edit.php', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-gallery_item') return;

    global $wpdb;
    $tbl = $wpdb->prefix.'xyz_maps';
    $rows = $wpdb->get_results("SELECT id, name FROM {$tbl} ORDER BY name ASC");
    ?>
    <script>
    (function(){
      const forms = document.querySelectorAll('form#posts-filter');
      if(!forms.length) return;

      forms.forEach(function(form){
        // wstaw select obok pierwszego selecta „action”
        const bulk1 = form.querySelector('select[name="action"]');
        if(!bulk1) return;

        const wrap = document.createElement('span');
        wrap.style.marginLeft = '8px';
        wrap.innerHTML = '<?php
          $html  = '<label style="margin-left:6px;">'.esc_js(__('Map:', 'xyz-map-gallery')).' ';
          $html .= '<select name="xyz_target_map" id="xyz_target_map">';
          $html .= '<option value="">'.esc_js(__('— choose —','xyz-map-gallery')).'</option>';
          foreach($rows as $r){
            $label = $r->name ?: ('#'.$r->id);
            $html .= '<option value="'.(int)$r->id.'">'.esc_js($label).'</option>';
          }
          $html .= '</select></label>';
          echo $html;
        ?>';
        bulk1.after(wrap);
      });

      // walidacja: jeśli wybrano naszą akcję, wymagaj mapy
      document.addEventListener('submit', function(e){
        const form = e.target;
        if(!form || form.id !== 'posts-filter') return;
        const actionSel = form.querySelector('select[name="action"], select[name="action2"]');
        const actionVal = actionSel ? actionSel.value : '';
        if(actionVal === 'xyz_assign_map'){
          const mapSel = form.querySelector('#xyz_target_map');
          if(!mapSel || !mapSel.value){
            e.preventDefault();
            alert('<?php echo esc_js(__('Please choose a map first.', 'xyz-map-gallery')); ?>');
          }
        }
      }, true);
    })();
    </script>
    <?php
});

/**
 * 3) Obsługa akcji (ustawia meta _map_id)
 */
add_filter('handle_bulk_actions-edit-gallery_item', function($redirect_to, $doaction, $post_ids){
    if ($doaction !== 'xyz_assign_map') return $redirect_to;

    if (!current_user_can('edit_posts')) {
        return add_query_arg(['xyz_assign_map'=>'denied'], $redirect_to);
    }

    check_admin_referer('bulk-posts'); // nonce z formularza listy postów

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
