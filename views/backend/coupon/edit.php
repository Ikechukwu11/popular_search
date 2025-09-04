<?php
// Grab notice (errors + old values if any)
$notice = get_transient('abc_events_admin_notice');
$old = $notice['old'] ?? null;
$random = 'abc2026' . substr(str_shuffle(MD5(microtime())), 0, 8);
$code = strtoupper($random);

// Prefer "old" values first, fallback to DB record if editing
$coupon = [
  'id'                 => $old['id'] ?? ($coupon['id'] ?? ''),
  'code'               => $old['code'] ?? ($coupon['code'] ?? $code),
  'discount_value'     => $old['discount_value'] ?? ($coupon['discount_value'] ?? ''),
  'discount_type'      => $old['discount_type'] ?? ($coupon['discount_type'] ?? 'fixed'),
  'usage_limit'        => $old['usage_limit'] ?? ($coupon['usage_limit'] ?? '0'),
  'per_customer_limit' => $old['per_customer_limit'] ?? ($coupon['per_customer_limit'] ?? '1'),
  'valid_from'         => $old['valid_from'] ?? (!empty($coupon['valid_from']) ? date('Y-m-d\TH:i', strtotime($coupon['valid_from'])) : ''),
  'valid_until'        => $old['valid_until'] ?? (!empty($coupon['valid_until']) ? date('Y-m-d\TH:i', strtotime($coupon['valid_until'])) : ''),
  'active'             => $old['active'] ?? ($coupon['active'] ?? 1),
  'year'               => $old['year'] ?? ($coupon['year'] ?? $year),
];
$id = $coupon['id'];
?>

<div class="wrap" class="wp-core-ui" style="margin-top: 20px;">
  <div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;">
    <h1 class=" wp-heading-inline" style="padding-bottom: 10px;">
      ABC <?php echo esc_html($coupon['year']); ?> - <?= $id ? 'Edit Coupon' : 'Add New Coupon' ?>
    </h1>
    <hr class="wp-header-end">
  </div>

  <?php if ($notice && $notice['type'] === 'error'): ?>
    <div class="notice notice-error">
      <p><?= wp_kses_post($notice['message']); ?></p>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('update_coupon'); ?>
    <input type="hidden" name="action" value="update_coupon">
    <input type="hidden" name="coupon[0][id]" value="<?= esc_attr($id) ?>">
    <input type="hidden" name="year" value="<?= esc_attr($coupon['year']); ?>">

    <table class="form-table">
      <tr>
        <th>Coupon Code</th>
        <td><input type="text" class="regular-text" name="coupon[0][code]" value="<?= esc_attr($coupon['code']); ?>" required></td>
      </tr>
      <tr>
        <th>Discount Value (%) <br> <small>The percentage to discount</small></th>
        <td>
          <input type="number" step="0.01" max="100" class="regular-text" name="coupon[0][discount_value]" value="<?= esc_attr($coupon['discount_value']); ?>" required>
        </td>
      </tr>
      <tr>
        <th>Discount Type</th>
        <td>
          <select class="regular-text" name="coupon[0][discount_type]" required>
            <option value="percent" selected>Percent</option>
          </select>
        </td>
      </tr>
      <tr>
        <th>Usage Limit</th>
        <td><input type="number" class="regular-text" name="coupon[0][usage_limit]" value="<?= esc_attr($coupon['usage_limit']); ?>"></td>
      </tr>
      <tr>
        <th>Per Customer Limit</th>
        <td><input type="number" class="regular-text" name="coupon[0][per_customer_limit]" value="<?= esc_attr($coupon['per_customer_limit']); ?>"></td>
      </tr>
      <tr>
        <th>Valid From</th>
        <td><input type="datetime-local" class="regular-text" name="coupon[0][valid_from]" value="<?= esc_attr($coupon['valid_from']); ?>" required></td>
      </tr>
      <tr>
        <th>Valid Until</th>
        <td><input type="datetime-local" class="regular-text" name="coupon[0][valid_until]" value="<?= esc_attr($coupon['valid_until']); ?>" required></td>
      </tr>
      <tr>
        <th>Active</th>
        <td>
          <label>
            <input type="hidden" name="coupon[0][active]" value="0">
            <input type="checkbox" name="coupon[0][active]" value="1" <?= checked($coupon['active'], 1, false); ?>>
            Active
          </label>
        </td>
      </tr>
    </table>

    <?php submit_button($id ? 'Update Coupon' : 'Add Coupon'); ?>
  </form>
</div>