<?php

namespace ABCEvents\Controllers;

use ABCEvents\Models\Setting;
use ABCEvents\Controllers\Controller;

class SettingsController extends Controller
{
  protected $model;

  public function __construct()
  {
    $this->model = new Setting();
  }

  public static function hooks()
  {
    $controller = new self();

    return [
      [
        'hook' => 'admin_post_update_paystack',
        'callback' => [$controller, 'update'],
      ],
      [
        'hook' => 'admin_post_update_payment',
        'callback' => [$controller, 'update'],
      ],
      [
        'hook' => 'admin_post_update_recaptcha',
        'callback' => [$controller, 'update'],
      ],
    ];
  }

  public function index()
  {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'payment';

    switch ($tab) {
      case 'payment':
        return $this->payment();
      case 'paystack':
        return $this->paystack();
      case 'recaptcha':
        return $this->recaptcha();
      case 'notifications_admin':
        return $this->adminNotifications();
      case 'notifications_user':
        return $this->userNotifications();
      default:
        return $this->paystack();
    }
  }

  protected function payment()
  {
    $year = $this->event_year;
    $settings = $this->model->getSettings('payment', $year);
    if(!$settings) {
      $settings = ['payment_enable'=>'false'];
    }

    return $this->render('backend/settings/payment', [
      'settings' => $settings ?? [],
      'year' => $year,
      'current_tab' => 'payment'
    ]);
  }

  protected function paystack()
  {
    $year = $this->event_year;
    $settings = $this->model->getSettings('paystack', $year);

    return $this->render('backend/settings/paystack', [
      'settings' => $settings ?? [],
      'year' => $year,
      'current_tab' => 'paystack'
    ]);
  }

  protected function recaptcha()
  {
    $year = $this->event_year;
    $settings = $this->model->getSettings('recaptcha', $year);

    return $this->render('backend/settings/recaptcha', [
      'settings' => $settings ?? [],
      'year' => $year,
      'current_tab' => 'recaptcha'
    ]);
  }

  protected function adminNotifications()
  {
    $year = $this->event_year;
    $settings = $this->model->getSettings('notifications_admin', $year);

    return $this->render('backend/settings/notifications_admin', [
      'settings' => $settings ?? [],
      'year' => $year,
      'current_tab' => 'notifications_admin'
    ]);
  }

  protected function userNotifications()
  {
    $year = $this->event_year;
    $settings = $this->model->getSettings('notification_user', $year);

    return $this->render('backend/settings/notification_user', [
      'settings' => $settings ?? [],
      'year' => $year,
      'current_tab' => 'notifications_user'
    ]);
  }

  public function update()
  {
    if (empty($_POST['action'])) {
      return;
    }

    $year   = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $action = sanitize_text_field($_POST['action']);

    try {
      switch ($action) {

         case 'update_payment':
          check_admin_referer('update_payment_settings');

          $settings = $_POST['settings'] ?? [];
          $sanitized = [];

          foreach ($settings as $key => $value) {
            $sanitized[$key] = sanitize_text_field($value);
          }

          $this->model->saveSettings('payment', $year, $sanitized);

          // Save success message in transient to persist across redirect
          set_transient('abc_events_admin_notice', [
            'type' => 'success',
            'message' => 'Payment settings saved.'
          ], 30); // lasts 30 seconds
          break;

        case 'update_paystack':
          check_admin_referer('update_paystack_settings');

          $settings = $_POST['settings'] ?? [];
          $sanitized = [];

          foreach ($settings as $key => $value) {
            $sanitized[$key] = sanitize_text_field($value);
          }

          $this->model->saveSettings('paystack', $year, $sanitized);

          // Save success message in transient to persist across redirect
          set_transient('abc_events_admin_notice', [
            'type' => 'success',
            'message' => 'Paystack settings saved.'
          ], 30); // lasts 30 seconds
          break;

        case 'update_recaptcha':
          check_admin_referer('update_recaptcha_settings');

          $settings = $_POST['settings'] ?? [];
          $sanitized = [];

          foreach ($settings as $key => $value) {
            $sanitized[$key] = sanitize_text_field($value);
          }

          $this->model->saveSettings('recaptcha', $year, $sanitized);

          // Save success message in transient to persist across redirect
          set_transient('abc_events_admin_notice', [
            'type' => 'success',
            'message' => 'Recaptcha settings saved.'
          ], 30); // lasts 30 seconds
          break;

        default:
          throw new \Exception("Unknown action: {$action}");
      }
    } catch (\Exception $e) {
      // Log the exception to plugin log
      if (defined('ABCEVENTS_LOG')) {
        error_log("[" . date('Y-m-d H:i:s') . "] SettingsController update error: " . $e->getMessage() . "\n", 3, ABCEVENTS_LOG);
      }

      // Show an error notice in admin
      set_transient('abc_events_admin_notice', [
        'type' => 'error',
        'message' => 'Error saving settings: ' . $e->getMessage()
      ], 30);
    }

    // Redirect back to the current tab
    $tab = str_replace('update_', '', $action);
    $redirect_url = add_query_arg(
      [
        'page' => 'abc_events_settings',
        'tab'  => $tab
      ],
      admin_url('admin.php')
    );

    wp_redirect($redirect_url);
    exit;
  }



  protected function render($view, $data = [])
  {
    extract($data);

    // Render the tab navigation first
    $this->renderTabs(isset($current_tab) ? $current_tab : '');

    // Then render the actual view
    include ABCEVENTS_PATH . 'views/' . $view . '.php';
  }

  protected function renderTabs($current_tab)
  {
    $tabs = [
      'payment'   => 'Payment Settings',
      'paystack'   => 'Paystack Settings',
      'recaptcha'  => 'Recaptcha Keys',
      'notifications_admin' => 'Admin Email Notifications',
      'notifications_user' => 'User Email Notifications',
    ];

    echo "<h1> ABC $this->event_year - $tabs[$current_tab] </h1>";
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab => $label) {
      $active_class = ($current_tab === $tab) ? 'nav-tab-active' : '';
      $tab_url = admin_url('admin.php?page=abc_events_settings&tab=' . $tab);
      echo '<a href="' . esc_url($tab_url) . '" class="nav-tab ' . esc_attr($active_class) . '">' . esc_html($label) . '</a>';
    }
    echo '</h2>';

    // Render notice if exists
    $notice = get_transient('abc_events_admin_notice');
    if ($notice) {
      $class = $notice['type'] === 'success' ? 'notice notice-success' : 'notice notice-error';
      printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
      delete_transient('abc_events_admin_notice'); // remove after showing
    }
  }
}
