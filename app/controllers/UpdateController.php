<?php

namespace PopularSeach\Controllers;

class UpdateController extends Controller
{
  protected $remoteUrl = 'https://raw.githubusercontent.com/Ikechukwu11/popular_search/master/update.json';
  protected $pluginFile = 'popular-search/popular-search.php';
  protected $slug = 'popular-search';

  // public function __construct()
  // {
  //   add_filter('site_transient_update_plugins', [$this, 'checkForUpdates']);
  //   add_filter('plugins_api', [$this, 'pluginInfo'], 10, 3);
  //   echo 'Yoooo';
  // }

    public static function hooks()
  {
    $controller = new self();

    return [
      [
        'hook' => 'site_transient_update_plugins',
        'callback' => [$controller, 'checkForUpdates'],
        'type'=>'filter'
      ],
      [
        'hook' => 'plugins_api',
        'callback' => [$controller, 'pluginInfo'],
        'type'=>'filter'
      ]
    ];
  }

  public function checkForUpdates($transient)
  {
    if (empty($transient->checked)) {
      return $transient;
    }

    $remote = wp_remote_get($this->remoteUrl);

    if (!is_wp_error($remote) && wp_remote_retrieve_response_code($remote) == 200) {
      $remote = json_decode(wp_remote_retrieve_body($remote));

      $currentVersion = $transient->checked[$this->pluginFile] ?? '0.0.0';

      if ($remote && version_compare($currentVersion, $remote->version, '<')) {
        $transient->response[$this->pluginFile] = (object)[
          'slug'        => $this->slug,
          'plugin'      => $this->pluginFile,
          'new_version' => $remote->version,
          'url'         => $remote->homepage,
          'package'     => $remote->download_url,
        ];
      }
    }

    return $transient;
  }

  public function pluginInfo($result, $action, $args)
  {
    if ($action !== 'plugin_information') {
      return $result;
    }

    if ($args->slug !== $this->slug) {
      return $result;
    }

    $remote = wp_remote_get($this->remoteUrl);

    if (!is_wp_error($remote) && wp_remote_retrieve_response_code($remote) == 200) {
      $remote = json_decode(wp_remote_retrieve_body($remote));

      return (object)[
        'name'          => $remote->name,
        'slug'          => $remote->slug,
        'version'       => $remote->version,
        'author'        => $remote->author,
        'homepage'      => $remote->homepage,
        'download_link' => $remote->download_url,
        'sections'      => [
          'description' => 'Track search terms and counts.',
        ],
      ];
    }

    return $result;
  }
}
