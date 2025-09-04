<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
  <?php wp_nonce_field('update_recaptcha_settings', '_wpnonce'); ?>
  <input type="hidden" name="action" value="update_recaptcha">
  <input type="hidden" name="year" value="<?php echo esc_attr($year); ?>">

  <table class="form-table">

    <tr>
      <th><label for="recaptcha_site_key">Site Key</label></th>
      <td><input type="text" name="settings[recaptcha_site_key]" id="recaptcha_site_key"
          value="<?php echo esc_attr($settings['recaptcha_site_key'] ?? ''); ?>" class="regular-text"></td>
    </tr>
    <tr>
      <th><label for="recaptcha_secret_key">Secret Key</label></th>
      <td><input type="text" name="settings[recaptcha_secret_key]" id="recaptcha_secret_key"
          value="<?php echo esc_attr($settings['recaptcha_secret_key'] ?? ''); ?>" class="regular-text"></td>
    </tr>

  </table>

  <?php submit_button('Save Recaptcha Settings'); ?>
</form>