<?php
// Admin notice (if any)
$notice = get_transient('abc_events_admin_notice');
if ($notice) {
  $class = $notice['type'] === 'success' ? 'notice notice-success' : 'notice notice-error';
  printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
  delete_transient('abc_events_admin_notice');
}

$edit_url_base = admin_url('admin.php?page=abc_events_pricing&edit&year=' . $year);
$create_url = esc_url(add_query_arg(['action' => 'create', 'year' => $year], admin_url('admin.php?page=abc_events_pricing')));
$enable_payment_url = esc_url(add_query_arg(['tab' => 'payment', 'year' => $year], admin_url('admin.php?page=abc_events_settings')));
?>

<div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
  <div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
    <h1 class=" wp-heading-inline" style="padding-bottom: 10px;">
      ABC <?php echo $year ?> Prices</h1>
    <a class="page-title-action" href="<?= esc_url($create_url) ?>">New Price</a>
  </div>
  <hr class="wp-header-end">
</div>
  <strong style="margin-bottom:10px;font-weight:600;color:<?php echo $payment['payment_enable'] === 'true' ? 'green' : 'red'?>">
    <?php echo $payment['payment_enable'] === 'true' ?
    "Payments are currently enabled for  ABC $year"
    : "Payments are currently disabled for  ABC $year, so registration is free.
    <a class='page-title-action'
    href='".esc_url($enable_payment_url)."'>Enable Payment</a>"?>
  </strong>

<table class="wp-list-table widefat fixed striped" style="margin-top:10px">
  <thead>
    <tr>
      <th>Name</th>
      <th>Naira Price</th>
      <th>Dollar Price</th>
      <th>Early Bird</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($pricing)): ?>
      <?php foreach ($pricing as $row): ?>
        <tr>
          <td><?= esc_html($row['name']) ?></td>
          <td><?= esc_html(abc_format_currency($row['naira_price'], 'NGN')) ?></td>
          <td><?= esc_html(abc_format_currency($row['dollar_price'], 'USD')) ?></td>
          <td>
            <?= esc_html(abc_format_currency($row['early_bird_naira_price'], 'NGN')) ?> | <?= esc_html(abc_format_currency($row['early_bird_dollar_price'], 'USD')) ?>
            <br>
            <?= esc_html(date('F j, Y', strtotime($row['early_bird_start']))) ?> - <?= esc_html(date('F j, Y', strtotime($row['early_bird_end']))) ?>
          </td>
          <td>
            <div style="display: flex;gap:5px;">
              <a href="<?= esc_url(add_query_arg(['id' => $row['id'], 'action' => 'edit', 'year' => $year], admin_url('admin.php?page=abc_events_pricing'))) ?>">Edit</a>
              |
              <div>
                <form style="display: inline;" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Are you sure you want to delete this record?');">
                  <?php wp_nonce_field('delete_pricing'); ?>
                  <input type="hidden" name="action" value="delete_pricing">
                  <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>">
                  <button type="submit" style="color:red;background:none">Delete</button>
                </form>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="5">No pricing found.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

</div>