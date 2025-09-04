<?php

use ABCEvents\Core\Router;
use ABCEvents\Controllers\SettingsController;
use ABCEvents\Controllers\CouponController;
use ABCEvents\Controllers\EntriesController;
use ABCEvents\Controllers\PricingController;
use ABCEvents\Controllers\DashboardController;

$router = new Router();

// Main Dashboard
$router->register(
  'abc_events_dashboard',
  'ABC Event Manager',
  'manage_options',
  [new DashboardController(), 'index']
);

// Subpages
$router->register(
  'abc_events_entries',
  'Entries',
  'manage_options',
  [new EntriesController(), 'handle'],
  'abc_events_dashboard'
);

$router->register(
  'abc_events_coupons',
  'Coupons',
  'manage_options',
  [new CouponController(), 'handle'],
  'abc_events_dashboard'
);

$router->register(
  'abc_events_pricing',
  'Pricing',
  'manage_options',
  [new PricingController(), 'handle'],
  'abc_events_dashboard'
);

$router->register(
  'abc_events_settings',
  'Settings',
  'manage_options',
  [new SettingsController(), 'index'],
  'abc_events_dashboard'
);
$router->boot();
