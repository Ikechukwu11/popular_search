<form class="form-notification" method="post" action="" style="max-width: 500px;">
  <table class="form-table">

    <tr class="" style="width:100%;display:flex;flex-direction:column;">
      <td>
        <div class="notice notice-info is-dismissible">
          <p>You can use the following to insert dynamic content: </p>
          <p><b>{donor_email}, {donor_firstname},{donor_lastname}, {donor_amount}, {donor_date}</b></p>
        </div>
      </td>
    </tr>

    <tr class="" style="width:100%;display:flex;flex-direction:column;">
      <th scope="row"><label for="donor_email_subject">Donor Email Subject</label></th>
      <td><input style="width:100%;" type="text" name="donor_email_subject" id="donor_email_subject" value="<?= esc_attr(get_option('donor_email_subject')); ?>" /></td>
    </tr>
    <tr class="" style="width:100%;display:flex;flex-direction:column;">
      <th scope="row"><label for="donor_email_body">Donor Email Template</label></th>
      <td>
        <?php
        wp_editor(
          get_option('donor_email_body'),
          'donor_email_body',
          [
            'textarea_name' => 'donor_email_body',
            'textarea_rows' => 10,
            'media_buttons' => true,
          ]
        );
        ?>
      </td>
    </tr>
  </table>
  <?php submit_button(); ?>
</form>