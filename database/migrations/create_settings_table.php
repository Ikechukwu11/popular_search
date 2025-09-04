<?php
function create_settings_table($wpdb)
{
  $table = $wpdb->prefix . 'abc_events_settings';
  $charset_collate = $wpdb->get_charset_collate();

  // Check if table exists
  $exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table
  ));

  if ($exists) {
    return; // Table already exists
  }

  $sql = "CREATE TABLE {$table} (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        year INT NOT NULL,
        option_type VARCHAR(100) NOT NULL,
        option_key VARCHAR(191) NOT NULL,
        option_value TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
