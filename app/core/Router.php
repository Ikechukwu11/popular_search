<?php

namespace ABCEvents\Core;

class Router
{
  protected $routes = [];

  public function register($slug, $title, $capability, $callback, $parent = null, $hidden = false)
  {
    $this->routes[] = compact('slug', 'title', 'capability', 'callback', 'parent', 'hidden');
  }

  public function boot()
  {
    add_action('admin_menu', function () {
      foreach ($this->routes as $route) {
        if ($route['parent'] === null) {
          // Main menu page
          add_menu_page(
            $route['title'],
            $route['title'],
            $route['capability'],
            $route['slug'],
            $route['callback'],
            'dashicons-tickets-alt',
            3
          );
        } else {
          if (!empty($route['hidden'])) {
            // Hidden submenu page
            add_submenu_page(
              null, // no parent, won't show in sidebar
              $route['title'],
              $route['title'],
              $route['capability'],
              $route['slug'],
              $route['callback']
            );
          } else {
            // Normal submenu
            add_submenu_page(
              $route['parent'],
              $route['title'],
              $route['title'],
              $route['capability'],
              $route['slug'],
              $route['callback']
            );
          }
        }
      }
    });
  }
}
