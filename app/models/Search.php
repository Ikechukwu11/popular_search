<?php

namespace PopularSeach\Models;

class Search
{
  protected $db;
  protected $table;

  public function __construct()
  {
    global $wpdb;
    $this->db   = $wpdb;
    $this->table = $wpdb->prefix . 'popular_search';
  }

  public function all()
  {
    return $this->db->get_results(
      $this->db->prepare(
        "SELECT * FROM {$this->table} ORDER BY id DESC"
      ),
      ARRAY_A
    );
  }

  public function get($keyword)
  {
    return $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM {$this->table} WHERE keyword = %d",
        $keyword
      ),
      ARRAY_A
    );
  }

  public function save($data)
  {
    try {
      $payload = [
        'keyword'              => strtoupper(sanitize_text_field($data['keyword'])),
        'count'    => intval($data['count']),
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

        return true;
      } else {
        // ✅ Insert new coupon
        $payload['created_at'] = current_time('mysql');
        $result = $this->db->insert($this->table, $payload);

        if ($result === false) {
          throw new \Exception('Failed to insert coupon. DB Error: ' . $this->db->last_error);
        }

        return true;
      }
    } catch (\Exception $e) {
      error_log('PopularSeach Error: ' . $e->getMessage());
      throw $e;
    }
  }
}
