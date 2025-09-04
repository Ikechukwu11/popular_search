<?php

namespace ABCEvents\Core;

class Migration
{
  public static function run()
  {
    global $wpdb;
    $migrations_path = plugin_dir_path(__DIR__) . '../database/migrations/';

    foreach (glob($migrations_path . '*.php') as $file) {
      require_once $file;

      $function = basename($file, '.php'); // e.g. create_coupons_table
      if (function_exists($function)) {
        $function($wpdb);
      }
    }
  }
}
