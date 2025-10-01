<?php
if ( ! defined('ABSPATH') ) exit;
namespace XYZ_Map_Gallery;

if (!defined('ABSPATH')) exit;

class Install {
  public static function activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'xyz_maps';

    $sql = "CREATE TABLE `$table` (
      `id` MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NOT NULL,
      `mode` VARCHAR(10) NOT NULL DEFAULT 'geo',
      `tiles_url` TEXT NOT NULL,
      `image_width` VARCHAR(50) NOT NULL DEFAULT '0',
      `image_height` VARCHAR(50) NOT NULL DEFAULT '0',
      `zoom_min` TINYINT UNSIGNED NOT NULL DEFAULT 0,
      `zoom_max` TINYINT UNSIGNED NOT NULL DEFAULT 18,
      `bounds` LONGTEXT NOT NULL,
      `cluster_markers` TINYINT(1) NOT NULL DEFAULT 0,
      PRIMARY KEY(`id`),
      KEY `name_idx` (`name`),
      KEY `mode_idx` (`mode`)
    ) $charset_collate;";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    flush_rewrite_rules();
  }

  public static function deactivate() {
    // no-op
  }
}
