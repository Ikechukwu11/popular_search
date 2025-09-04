<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
  <?php wp_nonce_field('update_paystack_settings', '_wpnonce'); ?>
  <input type="hidden" name="action" value="update_paystack">
  <input type="hidden" name="year" value="<?php echo esc_attr($year); ?>">

  <table class="form-table">
    <tr>
      <th><label for="paystack_environment">Environment</label></th>
      <td>
        <select name="settings[paystack_environment]" id="paystack_environment" class="regular-text">
          <option value="live" <?php selected($settings['paystack_environment'] ?? '', 'live'); ?>>Live</option>
          <option value="test" <?php selected($settings['paystack_environment'] ?? '', 'test'); ?>>Test</option>
        </select>
      </td>
    </tr>
    <tr>
      <th><label for="paystack_public_key">Public Key</label></th>
      <td><input type="text" name="settings[paystack_public_key]" id="paystack_public_key"
          value="<?php echo esc_attr($settings['paystack_public_key'] ?? ''); ?>" class="regular-text"></td>
    </tr>
    <tr>
      <th><label for="paystack_secret_key">Secret Key</label></th>
      <td><input type="text" name="settings[paystack_secret_key]" id="paystack_secret_key"
          value="<?php echo esc_attr($settings['paystack_secret_key'] ?? ''); ?>" class="regular-text"></td>
    </tr>
    <tr>
      <th><label for="paystack_test_public_key">Test Public Key</label></th>
      <td><input type="text" name="settings[paystack_test_public_key]" id="paystack_test_public_key"
          value="<?php echo esc_attr($settings['paystack_test_public_key'] ?? ''); ?>" class="regular-text"></td>
    </tr>
    <tr>
      <th><label for="paystack_test_secret_key">Test Secret Key</label></th>
      <td><input type="text" name="settings[paystack_test_secret_key]" id="paystack_test_secret_key"
          value="<?php echo esc_attr($settings['paystack_test_secret_key'] ?? ''); ?>" class="regular-text"></td>
    </tr>
  </table>

  <?php submit_button('Save Paystack Settings'); ?>
</form>