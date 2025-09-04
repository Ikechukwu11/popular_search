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
    year INT NOT NULL,
    code VARCHAR(100) NOT NULL UNIQUE,
    discount_value DECIMAL(10,2) NOT NULL,
    discount_type ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
    valid_from TIMESTAMP NULL DEFAULT NULL,
    valid_until TIMESTAMP NULL DEFAULT NULL,
    active BOOLEAN DEFAULT TRUE,
    usage_limit INT NULL,
    per_customer_limit INT NULL,
    times_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
