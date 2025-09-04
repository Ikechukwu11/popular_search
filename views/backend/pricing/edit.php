<?php
$id = $pricing['id'] ?? '';
$name = $pricing['name'] ?? '';
$naira_price = $pricing['naira_price'] ?? '';
$dollar_price = $pricing['dollar_price'] ?? '';
$early_bird_naira_price = $pricing['early_bird_naira_price'] ?? '';
$early_bird_dollar_price = $pricing['early_bird_dollar_price'] ?? '';
$early_bird_start = !empty($pricing['early_bird_start']) ? date('Y-m-d\TH:i', strtotime($pricing['early_bird_start'])) : '';
$early_bird_end = !empty($pricing['early_bird_end']) ? date('Y-m-d\TH:i', strtotime($pricing['early_bird_end'])) : '';
?>
<div class="wrap" class="wp-core-ui" style="margin-top: 20px;">
  <div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;">
    <h1 class=" wp-heading-inline" style="padding-bottom: 10px;">
      ABC <?php echo $year ?> - <?= $id ? 'Edit Pricing' : 'Add New Pricing' ?></h1>
    <hr class="wp-header-end">
  </div>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('update_pricing'); ?>
    <input type="hidden" name="action" value="update_pricing">
    <input type="hidden" name="pricing[0][id]" value="<?= esc_attr($id) ?>">
    <input type="hidden" name="year" value="<?= esc_attr($year) ?>">

    <table class="form-table">
      <tr>
        <th>Name</th>
        <td><input type="text" class="regular-text" name="pricing[0][name]" value="<?= esc_attr($name) ?>" class="regular-text" required></td>
      </tr>
      <tr>
        <th>Naira Price</th>
        <td><input type="number" step="0.01" class="regular-text" name="pricing[0][naira_price]" value="<?= esc_attr($naira_price) ?>" required></td>
      </tr>
      <tr>
        <th>Dollar Price</th>
        <td><input type="number" step="0.01" class="regular-text" name="pricing[0][dollar_price]" value="<?= esc_attr($dollar_price) ?>" required></td>
      </tr>
      <tr>
        <th>Early Bird Naira Price</th>
        <td><input type="number" step="0.01" class="regular-text" name="pricing[0][early_bird_naira_price]" value="<?= esc_attr($early_bird_naira_price) ?>"></td>
      </tr>
      <tr>
        <th>Early Bird Dollar Price</th>
        <td><input type="number" step="0.01" class="regular-text" name="pricing[0][early_bird_dollar_price]" value="<?= esc_attr($early_bird_dollar_price) ?>"></td>
      </tr>
      <tr>
        <th>Early Bird Start</th>
        <td><input type="datetime-local" class="regular-text" name="pricing[0][early_bird_start]" value="<?= esc_attr($early_bird_start) ?>"></td>
      </tr>
      <tr>
        <th>Early Bird End</th>
        <td><input type="datetime-local" class="regular-text" name="pricing[0][early_bird_end]" value="<?= esc_attr($early_bird_end) ?>"></td>
      </tr>
    </table>

    <?php submit_button($id ? 'Update Pricing' : 'Add Pricing'); ?>
  </form>