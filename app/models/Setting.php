<?php

namespace ABCEvents\Models;

use wpdb;

class Setting
{
  protected $db;
  protected $table;

  public function __construct()
  {
    global $wpdb;
    $this->db = $wpdb;
    $this->table = $wpdb->prefix . 'abc_events_settings';
  }

  /**
   * Fetch all settings for a given type and year
   */
  public function getSettings($type, $year)
  {
    $results = $this->db->get_results(
      $this->db->prepare(
        "SELECT option_key, option_value FROM {$this->table} WHERE option_type = %s AND year = %d",
        $type,
        $year
      ),
      ARRAY_A
    );

    // Convert to key => value array
    $settings = [];
    foreach ($results as $row) {
      $settings[$row['option_key']] = maybe_unserialize($row['option_value']);
    }

    return $settings;
  }

  /**
   * Save or update settings for a given type and year
   */
  public function saveSettings($option_type, $year, $data): void
  {
    global $wpdb;
    $table = $this->table;

    foreach ($data as $key => $value) {
      try {
        // Serialize if needed
        $value_to_store = maybe_serialize($value);

        // Check if setting exists
        $existing_id = $wpdb->get_var(
          $wpdb->prepare(
            "SELECT id FROM {$table} WHERE option_type = %s AND option_key = %s AND year = %d",
            $option_type,
            $key,
            $year
          )
        );

        $payload = [
          'option_type'  => $option_type,
          'year'         => $year,
          'option_key'   => $key,
          'option_value' => $value_to_store,
          'updated_at'   => current_time('mysql'),
        ];

        if ($existing_id) {
          $result = $wpdb->update(
            $table,
            $payload,
            ['id' => $existing_id],
            ['%s', '%d', '%s', '%s', '%s'],
            ['%d']
          );
          if ($result === false) {
            throw new \Exception("Failed to update setting '{$key}' in {$table}. DB error: " . $wpdb->last_error);
          }
        } else {
          $payload['created_at'] = current_time('mysql');
          $result = $wpdb->insert(
            $table,
            $payload,
            ['%s', '%d', '%s', '%s', '%s', '%s']
          );
          if ($result === false) {
            throw new \Exception("Failed to insert setting '{$key}' in {$table}. DB error: " . $wpdb->last_error);
          }
        }
      } catch (\Exception $e) {
        // Log to plugin-specific log file if defined
        if (defined('ABCEVENTS_LOG')) {
          error_log("[" . date('Y-m-d H:i:s') . "] SettingsModel saveSettings error: " . $e->getMessage() . "\n", 3, ABCEVENTS_LOG);
        }

        // Throw again so caller can handle or show admin notice
        throw $e;
      }
    }
  }
}
