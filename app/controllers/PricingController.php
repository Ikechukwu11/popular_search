<?php

namespace ABCEvents\Controllers;

use ABCEvents\Models\Pricing;
use ABCEvents\Models\Setting;

class PricingController extends Controller
{
  protected $model;
  protected $settingModel;

  public function __construct()
  {
    $this->model = new Pricing();
    $this->settingModel = new Setting();
  }

  public static function hooks()
  {
    $controller = new self();

    return [
      [
        'hook' => 'admin_post_update_pricing',
        'callback' => [$controller, 'update'],
      ],
      [
        'hook' => 'admin_post_delete_pricing',
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
    $pricing = $this->model->getAll($year);
    $payment = $this->settingModel->getSettings('payment', $year);
    if (!$payment) {
      $payment = ['payment_enable' => 'false'];
    }

    return $this->render('backend/pricing/index', [
      'pricing' => $pricing,
      'payment' => $payment,
      'year'    => $year
    ]);
  }

  /**
   * Show the create/edit form
   */
  public function create()
  {
    $year = $this->event_year;
    $pricing = [];
    return $this->render('backend/pricing/edit', [
      'pricing' => $pricing,
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

    $pricing = $id ? $this->model->get($id, $year) : [];
    if (empty($pricing)) {
      return wp_redirect(admin_url('admin.php?page=abc_events_pricing'));
    }

    return $this->render('backend/pricing/edit', [
      'pricing' => $pricing,
      'year' => $year,
    ]);
  }

  /**
   * Save or update pricing
   */
  public function update()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    check_admin_referer('update_pricing');

    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $data = $_POST['pricing'][0] ?? [];

    try {
      // Sanitize inputs
      $sanitized = [
        'id' => isset($data['id']) ? intval($data['id']) : null,
        'name' => sanitize_text_field($data['name'] ?? ''),
        'naira_price' => floatval($data['naira_price'] ?? 0),
        'dollar_price' => floatval($data['dollar_price'] ?? 0),
        'early_bird_naira_price' => floatval($data['early_bird_naira_price'] ?? 0),
        'early_bird_dollar_price' => floatval($data['early_bird_dollar_price'] ?? 0),
        'early_bird_start' => sanitize_text_field($data['early_bird_start'] ?? ''),
        'early_bird_end' => sanitize_text_field($data['early_bird_end'] ?? ''),
        'year' => $year
      ];

      if ($sanitized['id']) {
        $message = 'Pricing updated successfully.';
      } else {
        $message = 'Pricing created successfully.';
      }
      $this->model->save($year, $sanitized);

      set_transient('abc_events_admin_notice', [
        'type' => 'success',
        'message' => $message
      ], 30);
    } catch (\Exception $e) {
      // Log the exception
      if (defined('ABCEVENTS_LOG')) {
        error_log("[" . date('Y-m-d H:i:s') . "] PricingController update error: " . $e->getMessage() . "\n", 3, ABCEVENTS_LOG);
      }

      set_transient('abc_events_admin_notice', [
        'type' => 'error',
        'message' => 'Error saving pricing: ' . $e->getMessage()
      ], 30);
    }

    $theaction = $sanitized['id'] ? 'edit' : 'create';
    wp_redirect(add_query_arg(['page' => 'abc_events_pricing', 'action' => $theaction, 'year' => $year], admin_url('admin.php')));
    exit;
  }

  /**
   * Delete a pricing entry
   */
  public function delete()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    check_admin_referer('delete_pricing');

    if (!isset($_POST['id'])) {
      wp_die('Missing ID');
    }

    $id = intval($_POST['id']);

    try {

      $this->model->delete($id);

      set_transient('abc_events_admin_notice', [
        'type' => 'success',
        'message' => 'Pricing deleted successfully.'
      ], 30);
    } catch (\Exception $e) {
      if (defined('ABCEVENTS_LOG')) {
        error_log("[" . date('Y-m-d H:i:s') . "] PricingController delete error: " . $e->getMessage() . "\n", 3, ABCEVENTS_LOG);
      }

      set_transient('abc_events_admin_notice', [
        'type' => 'error',
        'message' => 'Error deleting pricing: ' . $e->getMessage()
      ], 30);
    }

    wp_redirect(add_query_arg(['page' => 'abc_events_pricing', 'year' => $this->event_year], admin_url('admin.php')));
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
