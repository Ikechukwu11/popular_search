<?php

namespace ABCEvents\Controllers;

use ABCEvents\Models\Coupon;

class CouponController extends Controller
{
  protected $model;

  public function __construct()
  {
    $this->model = new Coupon();
  }

  public static function hooks()
  {
    $controller = new self();

    return [
      [
        'hook' => 'admin_post_update_coupon',
        'callback' => [$controller, 'update'],
      ],
      [
        'hook' => 'admin_post_delete_coupon',
        'callback' => [$controller, 'delete'],
      ]
    ];
  }

  public function handle()
  {
    $action = $_GET['action'] ?? 'index';

    switch ($action) {
      case 'create':
        $this->create();
        break;
      case 'edit':
        $id = intval($_GET['id'] ?? 0);
        $this->edit($id);
        break;
      default:
        $this->index();
        break;
    }
  }

  public function index()
  {
    $year = $this->event_year;
    $coupons = $this->model->getAll($year);

    return $this->render('backend/coupon/index', [
      'coupons' => $coupons,
      'year'    => $year
    ]);
  }

  /**
   * Show the create/edit form
   */
  public function create()
  {
    $year = $this->event_year;
    $coupon = [];
    return $this->render('backend/coupon/edit', [
      'coupon' => $coupon,
      'year' => $year,
    ]);
  }

  /**
   * Show the create/edit form
   */
  public function edit($id)
  {
    $year = $this->event_year;
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    $coupon = $id ? $this->model->get($id, $year) : [];
    if (empty($coupon)) {
      return wp_redirect(admin_url('admin.php?page=abc_events_coupons'));
    }

    return $this->render('backend/coupon/edit', [
      'coupon' => $coupon,
      'year' => $year,
    ]);
  }

  /**
   * Save or update coupon
   */
  public function update()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    check_admin_referer('update_coupon');

    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $data = $_POST['coupon'][0] ?? [];


    try {
      // --- Sanitize inputs ---
      $sanitized = [
        'id'                => isset($data['id']) ? intval($data['id']) : null,
        'code'              => strtoupper(sanitize_text_field($data['code'] ?? '')),
        'discount_value'    => isset($data['discount_value']) ? floatval($data['discount_value']) : null,
        'discount_type'     => in_array($data['discount_type'] ?? '', ['fixed', 'percent'], true) ? $data['discount_type'] : 'fixed',
        'usage_limit'       => isset($data['usage_limit']) ? intval($data['usage_limit']) : null,
        'per_customer_limit' => isset($data['per_customer_limit']) ? intval($data['per_customer_limit']) : null,
        'valid_from'        => !empty($data['valid_from']) ? sanitize_text_field($data['valid_from']) : null,
        'valid_until'       => !empty($data['valid_until']) ? sanitize_text_field($data['valid_until']) : null,
        // 'active'            => isset($data['active']) ? (bool)$data['active'] : true,
        'active' => (bool) ($data['active'] ?? 0),
        'year'              => $this->event_year ?? $year
      ];

      // --- Validations ---
      $errors = [];

      if (empty($sanitized['code'])) {
        $errors[] = 'Coupon code is required.';
      } else {
        // Ensure unique (ignore current ID when editing)
        $exists = $this->model->findByCode($sanitized['code'], $year);
        // if ($exists && (!$sanitized['id'] || $exists['id'] !== $sanitized['id'])) {
        //   $errors[] = 'Coupon code already exists.';
        // }

        if ($exists) {
          $currentId = !empty($sanitized['id']) ? (int) $sanitized['id'] : null;

          if (!$currentId || (int) $exists['id'] !== $currentId) {
            $errors[] = 'Coupon code already exists.';
          }
        }
      }

      if ($sanitized['discount_value'] === null || $sanitized['discount_value'] <= 0) {
        $errors[] = 'Discount value must be greater than zero.';
      }

      if ($sanitized['usage_limit'] !== null && $sanitized['usage_limit'] < 0) {
        $errors[] = 'Usage limit must be a positive number.';
      }

      if ($sanitized['per_customer_limit'] !== null && $sanitized['per_customer_limit'] < 0) {
        $errors[] = 'Per-customer limit must be a positive number.';
      }

      if ($sanitized['valid_from'] && $sanitized['valid_until']) {
        $from = strtotime($sanitized['valid_from']);
        $until = strtotime($sanitized['valid_until']);
        if ($from && $until && $until < $from) {
          $errors[] = 'Valid until date cannot be earlier than valid from date.';
        }
      }
      if (!empty($errors)) {
        // store errors + previous form data in transient
        set_transient('abc_events_admin_notice', [
          'type'    => 'error',
          'message' => implode('| ', $errors),
          'old'     => $data
        ], expiration: 30);

        // detect action
        $theaction = !empty($sanitized['id']) ? 'edit' : 'create';

        // redirect back with previous form state (including id if editing)
        $redirect_args = [
          'page'   => 'abc_events_coupons',
          'action' => $theaction,
          'year'   => $year,
        ];

        if (!empty($sanitized['id'])) {
          $redirect_args['id'] = $sanitized['id'];
        }

        wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
      }


      // --- Save ---
      $this->model->save($year, $sanitized);

      $message = $sanitized['id'] ? 'Coupon updated successfully.' : 'Coupon created successfully.';
      set_transient('abc_events_admin_notice', [
        'type' => 'success',
        'message' => $message
      ], 30);
    } catch (\Exception $e) {
      if (defined('ABCEVENTS_LOG')) {
        error_log("[" . date('Y-m-d H:i:s') . "] CouponController update error: " . $e->getMessage() . "\n", 3, ABCEVENTS_LOG);
      }

      set_transient('abc_events_admin_notice', [
        'type' => 'error',
        'message' => 'Error saving coupon: ' . $e->getMessage(),
        'old' => $data
      ], 30);
    }

    $theaction = $sanitized['id'] ? 'edit' : 'create';
    wp_redirect(add_query_arg([
      'page' => 'abc_events_coupons',
      'action' => $theaction,
      'year' => $year
    ], admin_url('admin.php')));
    exit;
  }

  /**
   * Delete a coupon entry
   */
  public function delete()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    check_admin_referer('delete_coupon');

    if (!isset($_POST['id'])) {
      wp_die('Missing ID');
    }

    $id = intval($_POST['id']);

    try {

      $this->model->delete($id);

      set_transient('abc_events_admin_notice', [
        'type' => 'success',
        'message' => 'Coupon deleted successfully.'
      ], 30);
    } catch (\Exception $e) {
      if (defined('ABCEVENTS_LOG')) {
        error_log("[" . date('Y-m-d H:i:s') . "] CouponController delete error: " . $e->getMessage() . "\n", 3, ABCEVENTS_LOG);
      }

      set_transient('abc_events_admin_notice', [
        'type' => 'error',
        'message' => 'Error deleting coupon: ' . $e->getMessage()
      ], 30);
    }

    wp_redirect(add_query_arg(['page' => 'abc_events_coupons', 'year' => $this->event_year], admin_url('admin.php')));
    exit;
  }

  protected function render($view, $data = [])
  {
    extract($data);

    // Admin notice
    $notice = get_transient('abc_events_admin_notice');
    if ($notice) {
      $class = $notice['type'] === 'success' ? 'notice notice-success' : 'notice notice-error';
      printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
      delete_transient('abc_events_admin_notice');
    }

    include ABCEVENTS_PATH . 'views/' . $view . '.php';
  }
}
