<?php

use PopularSeach\Core\Router;
use PopularSeach\Controllers\PopularSearchController;

$router = new Router();

// Main Dashboard
$router->register(
  'popular_search',
  'Popular Serach',
  'manage_options',
  [new PopularSearchController(), 'index']
);

$router->boot();
