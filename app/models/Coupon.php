<?php

namespace ABCEvents\Models;

class Coupon
{
  protected $db;
  protected $table;

  public function __construct()
  {
    global $wpdb;
    $this->db   = $wpdb;
    $this->table = $wpdb->prefix . 'abc_events_coupons';
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

  public function findByCode($code, $year)
  {
    return $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table} WHERE year = %d AND code = %s",
        $year,
        $code
      ),
      ARRAY_A
    );
  }

  public function save($year, $data)
  {
    try {
      $payload = [
        'year'              => intval($year),
        'code'              => strtoupper(sanitize_text_field($data['code'])), // unique
        'discount_value'    => floatval($data['discount_value']),
        'discount_type'     => in_array($data['discount_type'], ['fixed', 'percent'], true) ? $data['discount_type'] : 'fixed',
        'usage_limit'       => isset($data['usage_limit']) ? intval($data['usage_limit']) : null,
        'per_customer_limit' => isset($data['per_customer_limit']) ? intval($data['per_customer_limit']) : null,
        'valid_from'        => !empty($data['valid_from']) ? sanitize_text_field($data['valid_from']) : null,
        'valid_until'       => !empty($data['valid_until']) ? sanitize_text_field($data['valid_until']) : null,
        'active'            => isset($data['active']) ? (bool)$data['active'] : true,
        'updated_at'        => current_time('mysql'),
      ];

      if (!empty($data['id'])) {
        // ✅ Update existing coupon
        $result = $this->db->update(
          $this->table,
          $payload,
          ['id' => intval($data['id'])],
          null,
          ['%d']
        );

        if ($result === false) {
          throw new \Exception('Failed to update coupon. DB Error: ' . $this->db->last_error);
        }

        return intval($data['id']);
      } else {
        // ✅ Insert new coupon
        $payload['created_at'] = current_time('mysql');
        $result = $this->db->insert($this->table, $payload);

        if ($result === false) {
          throw new \Exception('Failed to insert coupon. DB Error: ' . $this->db->last_error);
        }

        return $this->db->insert_id;
      }
    } catch (\Exception $e) {
      error_log('ABC Coupon Save Error: ' . $e->getMessage());
      throw $e;
    }
  }

  public function delete($id)
  {
    $result = $this->db->delete($this->table, ['id' => intval($id)], ['%d']);
    if ($result === false) {
      throw new \Exception('Failed to delete coupon. Reason: ' . $this->db->last_error);
    }

    return $result;
  }
}
