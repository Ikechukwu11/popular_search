<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
  <?php wp_nonce_field('update_payment_settings', '_wpnonce'); ?>
  <input type="hidden" name="action" value="update_payment">
  <input type="hidden" name="year" value="<?php echo esc_attr($year); ?>">
  <strong style="margin-top: 20px;display: flex;font-weight:600;color:<?php echo $settings['payment_enable'] === 'true' ? 'green' : 'red'?>">
    <?php echo $settings['payment_enable'] === 'true' ? "Payments are currently enabled for  ABC $year" : "Payments are currently disabled for  ABC $year, so registration is free"?>
  </strong>
  <table class="form-table">
    <tr>
      <th><label for="payment_enable">Enable Payment for ABC <?php echo $year?></label></th>
      <td>
        <select name="settings[payment_enable]" id="payment_enable" class="regular-text">
          <option value="true" <?php selected($settings['payment_enable'] ?? '', 'true'); ?>>Yes</option>
          <option value="false" <?php selected($settings['payment_enable'] ?? '', 'false'); ?>>No</option>
        </select>
      </td>
</tr>
  </table>

  <?php submit_button('Save payment Settings'); ?>
</form>