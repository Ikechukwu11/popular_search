<?php
function create_popular_search_table($wpdb)
{
  $table = $wpdb->prefix . 'popular_search';
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
    keyword TEXT NOT NULL,
    count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
