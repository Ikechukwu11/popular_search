<?php

namespace ABCEvents\Controllers;

use ABCEvents\Models\Coupon;
use ABCEvents\Models\Pricing;
use ABCEvents\Models\Setting;

class AjaxController extends Controller
{
  protected $model;



  public static function hooks()
  {
    $controller = new self();

    return [
      [
        'hook' => 'wp_ajax_nopriv_validate_coupon',
        'callback' => [$controller, 'validate_coupon'],
        'type' => 'action'
      ],
      [
        'hook' => 'wp_ajax_validate_coupon',
        'callback' => [$controller, 'validate_coupon'],
        'type' => 'action'
      ]
    ];
  }

  public function validate_coupon()
  {
    try {
      // Values come from FormData ($_POST)
      $code                 = sanitize_text_field($_POST['code'] ?? '');
      $participation_option = sanitize_text_field($_POST['participation_option'] ?? '');
      $currency             = strtolower(sanitize_text_field($_POST['currency'] ?? ''));
      $email                = sanitize_email($_POST['email'] ?? '');

      $couponModel  = new Coupon();
      $pricingModel = new Pricing();
      $settingModel = new Setting();
      $year         = $this->event_year;

      // === Validation checks ===
      if (!$participation_option) {
        throw new \Exception('Please select a valid participation option.');
      }

      if (!$currency || !in_array($currency, ['ngn', 'usd'])) {
        throw new \Exception('Please select a valid currency.');
      }

      // Check payment enable
      $paymentEnable = $settingModel->getSettings('payment', $year);
      $paymentEnable = $paymentEnable['payment_enable'] ?? 'false';

      // Build pricing data
      $pricingData = [];
      if ($paymentEnable === 'true') {
        $currentPrices = $pricingModel->getCurrentPricing($year);
        foreach ($currentPrices as $price) {
          $pricingData[$price['slug']] = [
            'current_naira_price'  => (float)$price['current_naira_price'],
            'current_dollar_price' => (float)$price['current_dollar_price'],
          ];
        }
      } else {
        $pricingData = [
          'current_naira_price'  => 0,
          'current_dollar_price' => 0
        ];
      }

      // If payment is disabled, return immediately
      if ($paymentEnable === 'false') {
        return wp_send_json_success([
          'message'         => 'Coupon not available.',
          'discount_value'  => 0.00,
          'discount_amount' => 0.00,
          'pricing'         => $pricingData[$participation_option] ?? []
        ]);
      }

      // === Coupon validation ===
      $coupon = $couponModel->findByCode($code, $year);
      $coupon = (object)$coupon;
      if (!$coupon) {
        throw new \Exception('Coupon not found.');
      }

      $now = current_time('mysql');

      if (!$coupon->active) {
        throw new \Exception('This coupon is not active.');
      }

      if ($coupon->valid_from && $now < $coupon->valid_from) {
        throw new \Exception('This coupon is not yet valid.');
      }

      if ($coupon->valid_until && $now > $coupon->valid_until) {
        throw new \Exception('Coupon expired.');
      }

      if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
        throw new \Exception('Coupon usage limit reached.');
      }

      // --- Calculate discount amount ---
      $basePrice = 0;
      if (isset($pricingData[$participation_option])) {
        $basePrice = ($currency === 'ngn')
          ? $pricingData[$participation_option]['current_naira_price']
          : $pricingData[$participation_option]['current_dollar_price'];
      }

      $discountPercent = (float)$coupon->discount_value; // e.g. 5 for 5%
      $discountAmount  = ($basePrice * $discountPercent) / 100;

      // ✅ Success response
      return wp_send_json_success([
        'valid'           => true,
        'message'         => 'Coupon valid.',
        'discount_value'  => $discountPercent,   // percentage
        'discount_amount' => $discountAmount,    // amount in currency
        'pricing'         => $pricingData[$participation_option] ?? [],
        'currency'        => strtoupper($currency)
      ]);
    } catch (\Exception $e) {
      // ❌ Error response
      return wp_send_json_error(['valid' => false, 'message' => $e->getMessage()], 400);
    }
  }
}
