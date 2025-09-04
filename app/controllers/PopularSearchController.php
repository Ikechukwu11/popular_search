<?php

namespace PopularSeach\Controllers;

use PopularSeach\Models\Search;

class PopularSearchController extends Controller
{
  protected $model;

  public function __construct()
  {
    $this->model = new Search();
  }

  public static function hooks()
  {
    $controller = new self();

    return [
      [
        'hook' => 'admin_post_update_search',
        'callback' => [$controller, 'save'],
      ],
      [
        'hook' => 'show_popular_search',
        'callback' => [$controller, 'show'],
        'type'=>'shortcode'
      ]
    ];
  }

  public function index()
  {
    $keywords = $this->model->all();
    return $this->render('backend/search/index', [
      'keywords' => $keywords
    ]);
  }

  /**
   * Save or update pricing
   */
  public function save()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    $keyword = $_POST['keyword'][0] ?? [];
    $count = 0;
    $data = [];
    $thecount = $this->model->get($keyword);
    if(!empty($thecount)) {
      $count = intval($thecount['count']);
      $data['id']=$thecount['id'];
    }

    try {
      // Sanitize inputs
      $data = [
        'keyword' => sanitize_text_field($keyword),
        'count' => intval($count + 1)
      ];

      $this->model->save($data);

    } catch (\Exception $e) {
      // Log the exception
      if (defined(SEARCH_LOG)) {
        error_log("[" . date('Y-m-d H:i:s') . "] Popular Search Controller error: " . $e->getMessage() . "\n", 3, SEARCH_LOG);
      }
    }
    exit;
  }

  /**
   * Render
   */
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

    include SEARCH_PATH . 'views/' . $view . '.php';
  }
}
