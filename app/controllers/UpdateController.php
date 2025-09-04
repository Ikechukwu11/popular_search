<?php

namespace PopularSeach\Controllers;

class UpdateController extends Controller
{
  protected $repo = 'Ikechukwu11/popular_search'; // GitHub repo
  protected $pluginFile = 'popular-search/popular-search.php';
  protected $slug = 'popular-search';

  public static function hooks()
  {
    $controller = new self();

    return [
      [
        'hook' => 'site_transient_update_plugins',
        'callback' => [$controller, 'checkForUpdates'],
        'type' => 'filter'
      ],
      [
        'hook' => 'plugins_api',
        'callback' => [$controller, 'pluginInfo'],
        'type' => 'filter'
      ]
    ];
  }

  /**
   * Fetch latest GitHub release
   */
  protected function fetchLatestRelease()
  {
    $url = "https://api.github.com/repos/{$this->repo}/releases/latest";
    $response = wp_remote_get($url, [
      'headers' => ['User-Agent' => 'WordPress']
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
      return null;
    }

    return json_decode(wp_remote_retrieve_body($response));
  }

  public function checkForUpdates($transient)
  {
    if (empty($transient->checked)) {
      return $transient;
    }

    $release = $this->fetchLatestRelease();
    if (!$release || empty($release->tag_name)) {
      return $transient;
    }

    $currentVersion = $transient->checked[$this->pluginFile] ?? '0.0.0';
    $latestVersion  = ltrim($release->tag_name, 'v'); // strip "v" prefix if any

    if (version_compare($currentVersion, $latestVersion, '<')) {
      $transient->response[$this->pluginFile] = (object)[
        'slug'        => $this->slug,
        'plugin'      => $this->pluginFile,
        'new_version' => $latestVersion,
        'url'         => $release->html_url,
        'package'     => $release->assets[0]->browser_download_url ?? $release->zipball_url,
      ];
    }

    return $transient;
  }

  public function pluginInfo($result, $action, $args)
  {
    if ($action !== 'plugin_information' || $args->slug !== $this->slug) {
      return $result;
    }

    $release = $this->fetchLatestRelease();
    if (!$release) {
      return $result;
    }

    return (object)[
      'name'          => 'Popular Search',
      'slug'          => $this->slug,
      'version'       => ltrim($release->tag_name, 'v'),
      'author'        => '<a href="https://github.com/Ikechukwu11">You</a>',
      'homepage'      => $release->html_url,
      'download_link' => $release->assets[0]->browser_download_url ?? $release->zipball_url,
      'sections'      => [
        'description' => $release->body ?? 'Track search terms and counts.',
      ],
    ];
  }
}
