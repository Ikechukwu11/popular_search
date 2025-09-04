<?php

namespace ABCEvents\Models;


class Pricing
{
  protected $db;
  protected $table;

  public function __construct()
  {
    global $wpdb;
    $this->db = $wpdb;
    $this->table = $wpdb->prefix . 'abc_events_pricing';
  }

  public function getAll($year)
  {
    return $this->db->get_results(
      $this->db->prepare(
        "SELECT * FROM {$this->table} WHERE year = %d ORDER BY id ASC",
        $year
      ),
      ARRAY_A
    );
  }

  // Consider adding current date check for early bird pricing
public function getCurrentPricing($year) {
    $currentDate = current_time('mysql');
    return $this->db->get_results(
        $this->db->prepare(
            "SELECT *,
            CASE WHEN %s BETWEEN early_bird_start AND early_bird_end
                 THEN early_bird_naira_price
                 ELSE naira_price
            END as current_naira_price,
            CASE WHEN %s BETWEEN early_bird_start AND early_bird_end
                 THEN early_bird_dollar_price
                 ELSE dollar_price
            END as current_dollar_price
            FROM {$this->table} WHERE year = %d ORDER BY id ASC",
            $currentDate, $currentDate, $year
        ),
        ARRAY_A
    );
}

  public function get($id, $year)
  {
    return $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table} WHERE year = %d AND id = %d",
        $year,
        $id
      ),
      ARRAY_A
    );
  }

  public function save($year, $data)
  {
    try {
      // Prepare payload (without slug first)
      $payload = [
        'year'              => intval($year),
        'name'              => sanitize_text_field($data['name']),
        'naira_price'       => floatval($data['naira_price']),
        'dollar_price'      => floatval($data['dollar_price']),
        'early_bird_naira_price'  => isset($data['early_bird_naira_price']) ? floatval($data['early_bird_naira_price']) : null,
        'early_bird_dollar_price' => isset($data['early_bird_dollar_price']) ? floatval($data['early_bird_dollar_price']) : null,
        'early_bird_start'  => !empty($data['early_bird_start']) ? $data['early_bird_start'] : null,
        'early_bird_end'    => !empty($data['early_bird_end']) ? $data['early_bird_end'] : null,
        'updated_at'        => current_time('mysql'),
      ];

      if (!empty($data['id'])) {
        // ✅ Update existing row (slug is not changed)
        $result = $this->db->update(
          $this->table,
          $payload,
          ['id' => intval($data['id'])],
          null,
          ['%d']
        );

        if ($result === false) {
          throw new \Exception(
            'Failed to update record. DB Error: ' . $this->db->last_error
            //' | Query: ' . $this->db->last_query
          );
        }
      } else {
        // ✅ Insert new row (slug generated once)
        $base_slug         = sanitize_title($data['name']);
        $payload['slug']   = abc_make_unique_slug($base_slug, $this->table);
        $payload['created_at'] = current_time('mysql');

        $result = $this->db->insert(
          $this->table,
          $payload,
          ['%d', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
          throw new \Exception(
            'Failed to insert record. DB Error: ' . $this->db->last_error
            //' | Query: ' . $this->db->last_query
          );
        }
      }
    } catch (\Exception $e) {
      // Log the error safely
      error_log('ABC Pricing Save Error: ' . $e->getMessage());
      throw $e;
    }
  }



  public function delete($id)
  {
    $result = $this->db->delete($this->table, ['id' => intval($id)], ['%d']);
    if ($result === false) {
      // Get DB error for debugging
      throw new \Exception('Failed to delete record. Reason: ' . $this->db->last_error);
    }

    return $result; // returns number of rows deleted (0 or 1 typically)
  }
}
