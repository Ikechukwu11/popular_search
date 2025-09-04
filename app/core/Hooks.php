<?php

namespace PopularSeach\Core;

class Hooks
{
  public static function register($controllersPath)
  {
    foreach (glob($controllersPath . '*.php') as $file) {
      require_once $file;

      $class = basename($file, '.php');
      $fqcn = "\\PopularSeach\\Controllers\\$class";

      if (class_exists($fqcn) && method_exists($fqcn, 'hooks')) {
        $controllerHooks = $fqcn::hooks();

        foreach ($controllerHooks as $hook) {
          if (!isset($hook['hook']) || !isset($hook['callback'])) {
            continue;
          }

          // add_action($hook['hook'], $hook['callback']);

          // action type: default = 'action'
          $type = $hook['type'] ?? 'action';

          switch ($type) {
            case 'filter':
              add_filter($hook['hook'], $hook['callback']);
              break;

            case 'shortcode':
              add_shortcode($hook['hook'], $hook['callback']);
              break;

            case 'action':
            default:
              add_action($hook['hook'], $hook['callback']);
              break;
          }
        }
      }
    }
  }
}
