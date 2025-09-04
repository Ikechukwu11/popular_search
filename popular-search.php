<?php

/**
 * Plugin Name: Popular Search Tracker
 * Description: Track search terms and counts.
 * Version: 0.0.8
 * Author: Ikechukwu11
 * Author URI: https://github.com/Ikechukwu11
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Icon: assets/icon-128x128.png
 * Icon-128: assets/icon-128x128.png
 */

if (!defined('ABSPATH')) exit;

// ============================
// Plugin Constants
// ============================
define('SEARCH_PATH', plugin_dir_path(__FILE__));
define('SEARCH_URL', plugin_dir_url(__FILE__));
define('SEARCH_LOG', SEARCH_PATH . 'logs/plugin-errors.log'); // dedicated log file

// ============================
// Ensure logs directory exists safely
// ============================
$log_dir = dirname(SEARCH_LOG);
if (!is_dir($log_dir)) {
    // Use wp_mkdir_p for WordPress-safe recursive creation
    if (function_exists('wp_mkdir_p')) {
        wp_mkdir_p($log_dir);
    } else {
        mkdir($log_dir, 0755, true);
    }
}


// ============================
// Error & Exception Handlers
// ============================
set_error_handler(function ($severity, $message, $file, $line) {
  $log_message = sprintf(
    "[%s] PHP Error (%s): %s in %s on line %d\n",
    date('Y-m-d H:i:s'),
    $severity,
    $message,
    $file,
    $line
  );
  error_log($log_message, 3, SEARCH_LOG);
  return false; // continue with normal PHP error handling
});

set_exception_handler(function ($exception) {
  $log_message = sprintf(
    "[%s] Uncaught Exception: %s in %s on line %d\n",
    date('Y-m-d H:i:s'),
    $exception->getMessage(),
    $exception->getFile(),
    $exception->getLine()
  );
  error_log($log_message, 3, SEARCH_LOG);
});



// ============================
// Autoloader
// ============================
require_once SEARCH_PATH . '/app/Autoloader.php';
PopularSeach\Autoloader::load();

// ============================
// Boot Plugin Routes
// ============================
add_action('plugins_loaded', function () {
  require_once SEARCH_PATH . '/routes/routes.php';
  require_once SEARCH_PATH . '/config/hooks.php';
});

// ============================
// Activation Hook (Migrations)
// ============================
register_activation_hook(__FILE__, function () {
  require_once SEARCH_PATH . 'app/core/Migration.php';
  PopularSeach\Core\Migration::run();
});
