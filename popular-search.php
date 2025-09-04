<?php

/**
 * Plugin Name: Popular Search Tracker
 * Description: Track search terms and counts.
 * Version: 0.0.4
 * Author: Ikechukwu11
 */

if (!defined('ABSPATH')) exit;

// ============================
// Plugin Constants
// ============================
define('SEARCH_PATH', plugin_dir_path(__FILE__));
define('SEARCH_URL', plugin_dir_url(__FILE__));
define('SEARCH_LOG', SEARCH_PATH . 'logs/plugin-errors.log'); // dedicated log file

// ============================
// Ensure logs directory exists
// ============================
if (!file_exists(dirname(SEARCH_LOG))) {
  mkdir(dirname(SEARCH_LOG), 0755, true);
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
