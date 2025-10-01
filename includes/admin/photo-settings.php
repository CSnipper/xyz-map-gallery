<?php
if (!defined('ABSPATH')) exit;

if (defined('XYZ_MG_ADMIN_PHOTO_INCLUDED')) return;
define('XYZ_MG_ADMIN_PHOTO_INCLUDED', true);

/** CPT: photo_item */
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
    register_post_type('photo_item', $args);
});

add_filter('default_comment_status', function ($status, $post_type) {
    return $post_type === 'photo_item' ? 'open' : $status;
}, 10, 2);

/** Metabox: Linked Place (autocomplete) */
function xyz_photo_register_meta_boxes() {
    add_meta_box(
        'xyz_photo_place_meta',
        __('Linked Place', 'xyz-map-gallery'),
        'xyz_photo_meta_box_callback',
        'photo_item',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'xyz_photo_register_meta_boxes');

function xyz_photo_meta_box_callback($post) {
    wp_nonce_field('xyz_photo_meta_nonce', 'xyz_photo_nonce');
    $current_place_id = (int) get_post_meta($post->ID, '_place_id', true);
    $current_title    = $current_place_id ? get_the_title($current_place_id) : '';

    ?>
    <p>
        <input type="text"
               id="xyz_place_search"
               placeholder="<?php esc_attr_e('Type to search places…', 'xyz-map-gallery'); ?>"
               value="<?php echo esc_attr($current_title); ?>"
               style="width:100%;"/>
        <input type="hidden" id="xyz_place_id" name="xyz_place_id" value="<?php echo esc_attr($current_place_id); ?>"/>
        <button type="button" class="button-link" id="xyz_place_clear" style="margin-top:6px;"><?php _e('Clear', 'xyz-map-gallery'); ?></button>
    </p>
    <?php
}

/** Save */
function xyz_photo_save_post($post_id) {
    if (!isset($_POST['xyz_photo_nonce']) || !wp_verify_nonce($_POST['xyz_photo_nonce'], 'xyz_photo_meta_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (get_post_type($post_id) !== 'photo_item') return;
    if (!current_user_can('edit_post', $post_id)) return;

    $place_id = isset($_POST['xyz_place_id']) ? (int) $_POST['xyz_place_id'] : 0;
    update_post_meta($post_id, '_place_id', $place_id);
}
add_action('save_post_photo_item', 'xyz_photo_save_post', 10);

/** Admin assets only on photo_item edit */
function xyz_photo_admin_assets($hook) {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'photo_item') return;

    wp_enqueue_script('jquery-ui-autocomplete');
    wp_register_script(
        'xyz-photo-admin',
        plugins_url('assets/js/photo-admin.js', XYZ_MAP_GALLERY_FILE),
        ['jquery', 'jquery-ui-autocomplete'],
        '1.0',
        true
    );
    wp_localize_script('xyz-photo-admin', 'xyzPhotoAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('xyz_search_places'),
        'i18n'    => [
            'noResults' => __('No places found', 'xyz-map-gallery'),
        ],
    ]);
    wp_enqueue_script('xyz-photo-admin');
}
add_action('admin_enqueue_scripts', 'xyz_photo_admin_assets');

/** AJAX: search places by title */
add_action('wp_ajax_xyz_search_places', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'forbidden'], 403);
    check_ajax_referer('xyz_search_places', 'nonce');

    $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
    $q = new WP_Query([
        'post_type'      => 'gallery_item',
        'post_status'    => 'publish',
        's'              => $term,
        'posts_per_page' => 20,
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $results = [];
    foreach ($q->posts as $pid) {
        $results[] = [
            'id'    => $pid,
            'label' => get_the_title($pid),
            'value' => get_the_title($pid),
        ];
    }
    wp_send_json($results);
});

// UTL: przelicz statystyki zdjęć dla miejsca
function xyz_update_place_photo_stats($place_id){
    $place_id = (int) $place_id; if(!$place_id) return;

    // 1 zapytanie: policz i weź najnowsze foto (dla miniatury)
    $q = new WP_Query([
        'post_type'      => 'photo_item',
        'post_status'    => 'publish',
        'meta_query'     => [[ 'key'=>'_place_id','value'=>$place_id,'compare'=>'=','type'=>'NUMERIC' ]],
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
        'no_found_rows'  => false, // potrzebne do found_posts
    ]);
    $count    = (int) $q->found_posts;
    $thumb_id = 0;
    if (!empty($q->posts)) {
        $p = (int) $q->posts[0];
        $thumb_id = (int) get_post_thumbnail_id($p);
    }
    wp_reset_postdata();

    update_post_meta($place_id, '_photo_count', $count);
    update_post_meta($place_id, '_photo_thumb_id', $thumb_id);
}

// Zapis photo_item: uwzględnij przeniesienie między miejscami
add_action('save_post_photo_item', function($post_id){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;

    $new = isset($_POST['xyz_place_id']) ? (int) $_POST['xyz_place_id'] : (int) get_post_meta($post_id,'_place_id',true);
    $old = (int) get_post_meta($post_id,'_place_id',true);
    // meta _place_id zapisz gdzie indziej jak już masz — ważne: zaktualizuj $old po odczycie
    if ($old && $old !== $new) xyz_update_place_photo_stats($old);
    if ($new) xyz_update_place_photo_stats($new);
}, 20);

// Zmiany statusu / kosz
add_action('trashed_post', function($post_id){
    if (get_post_type($post_id)!=='photo_item') return;
    $place = (int) get_post_meta($post_id,'_place_id',true);
    if ($place) xyz_update_place_photo_stats($place);
});
add_action('untrashed_post', function($post_id){
    if (get_post_type($post_id)!=='photo_item') return;
    $place = (int) get_post_meta($post_id,'_place_id',true);
    if ($place) xyz_update_place_photo_stats($place);
});
add_action('before_delete_post', function($post_id){
    if (get_post_type($post_id)!=='photo_item') return;
    $place = (int) get_post_meta($post_id,'_place_id',true);
    if ($place) xyz_update_place_photo_stats($place);
});

function xyz_rebuild_all_place_photo_stats(){
    $ids = get_posts([
        'post_type'      => 'gallery_item',
        'post_status'    => 'any',
        'fields'         => 'ids',
        'numberposts'    => -1,
        'no_found_rows'  => true,
        'suppress_filters'=> true,
    ]);
    foreach ($ids as $pid) xyz_update_place_photo_stats($pid);
}
