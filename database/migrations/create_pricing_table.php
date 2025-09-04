<?php

function create_pricing_table($wpdb)
{
  $table = $wpdb->prefix . 'abc_events_pricing';
  $charset_collate = $wpdb->get_charset_collate();

  // Check if table exists
  $exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table
  ));

  if ($exists) {
    return; // table already exists
  }

  $sql = "CREATE TABLE {$table} (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        year INT NOT NULL,
        name VARCHAR(191) NOT NULL,
        slug VARCHAR(191) NOT NULL,
        naira_price DECIMAL(10,2) NOT NULL,
        dollar_price DECIMAL(10,2) NOT NULL,
        early_bird_naira_price DECIMAL(10,2) NULL,
        early_bird_dollar_price DECIMAL(10,2) NULL,
        early_bird_start TIMESTAMP NULL DEFAULT NULL,
        early_bird_end TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
