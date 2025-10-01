<?php
// includes/import/step-mapping.php
if ( ! defined('ABSPATH') ) exit;

function xyz_import_step_mapping_form($path,$cols,$ext){
    global $wpdb;
    $maps = $wpdb->get_results("SELECT id,name FROM {$wpdb->prefix}xyz_maps ORDER BY name ASC");
    ?>
    <form method="post">
      <?php wp_nonce_field('xyz_import_nonce'); ?>
      <input type="hidden" name="xyz_step" value="2">
      <input type="hidden" name="xyz_import_path" value="<?php echo esc_attr($path); ?>">

      <h2><?php esc_html_e('Assign to map','xyz-map-gallery'); ?></h2>
      <select name="xyz_import_map_id">
        <option value="0"><?php esc_html_e('— none —','xyz-map-gallery'); ?></option>
        <?php foreach($maps as $m): ?>
          <option value="<?php echo (int)$m->id; ?>"><?php echo esc_html("#{$m->id} ".$m->name); ?></option>
        <?php endforeach; ?>
      </select>

      <h2><?php esc_html_e('Column mapping','xyz-map-gallery'); ?></h2>
      <table class="form-table">
        <?php
        $targets=['title'=>'Title','description'=>'Description','owner'=>'Owner','tags'=>'Tags','icon'=>'Icon','ext_id'=>'External ID'];
        if($ext==='csv'){ $targets=array_merge($targets,['lat'=>'Latitude','lng'=>'Longitude']); }
        foreach($targets as $k=>$label): ?>
          <tr><th><?php echo esc_html($label); ?></th><td>
            <select name="mapping[<?php echo esc_attr($k); ?>]">
              <option value=""><?php esc_html_e('— none —','xyz-map-gallery'); ?></option>
              <?php foreach($cols as $c): ?>
                <option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option>
              <?php endforeach; ?>
            </select>
          </td></tr>
        <?php endforeach; ?>
      </table>

      <h2><?php esc_html_e('Options','xyz-map-gallery'); ?></h2>
      <label><input type="checkbox" name="xyz_import_dry" value="1"> <?php esc_html_e('Dry-run','xyz-map-gallery'); ?></label><br>
      <label><input type="checkbox" name="xyz_import_overwrite" value="1"> <?php esc_html_e('Overwrite existing','xyz-map-gallery'); ?></label>

      <p><button class="button button-primary" name="xyz_do_import" value="1"><?php esc_html_e('Run import','xyz-map-gallery'); ?></button></p>
    </form>
<?php }
