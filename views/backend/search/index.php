<?php
// Admin notice (if any)
$notice = get_transient('abc_events_admin_notice');
if ($notice) {
  $class = $notice['type'] === 'success' ? 'notice notice-success' : 'notice notice-error';
  printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
  delete_transient('abc_events_admin_notice');
}

$edit_url_base = admin_url('admin.php?page=abc_events_coupons&edit&year=' . $year);
$create_url = esc_url(add_query_arg(['action' => 'create', 'year' => $year], admin_url('admin.php?page=abc_events_coupons')));
?>

<div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
  <div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
    <h1 class=" wp-heading-inline" style="padding-bottom: 10px;">
      ABC <?php echo $year ?> Coupons</h1>
    <a class="page-title-action" href="<?= esc_url($create_url) ?>">New Coupon</a>
  </div>
  <hr class="wp-header-end">
</div>
<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th>Code</th>
      <th>Discount Value</th>
      <th>Discount Type</th>
      <th>Valid From</th>
      <th>Valid Until</th>
      <th>Active</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($coupons)): ?>
      <?php foreach ($coupons as $row): ?>
        <tr>
          <td><?= esc_html($row['code']) ?></td>
          <td>%<?= esc_html($row['discount_value']) ?></td>
          <td><?= esc_html($row['discount_type']) ?></td>
          <td>
            <?= esc_html(date('F j, Y', strtotime($row['valid_from']))) ?>
          </td>
          <td>
            <?= esc_html(date('F j, Y', strtotime($row['valid_until']))) ?>

          </td>
          <td>
            <?= ($row['active'] == 1 ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>') ?>

          </td>
          <td>
            <div style="display: flex;gap:5px;">
              <a href="<?= esc_url(add_query_arg(['id' => $row['id'], 'action' => 'edit', 'year' => $year], admin_url('admin.php?page=abc_events_coupons'))) ?>">Edit</a>
              |
              <div>
                <form style="display: inline;" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Are you sure you want to delete this record?');">
                  <?php wp_nonce_field('delete_coupons'); ?>
                  <input type="hidden" name="action" value="delete_coupons">
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
        <td colspan="5">No coupons found.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

</div>