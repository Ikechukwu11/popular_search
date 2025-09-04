<div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
  <div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
    <h1 class=" wp-heading-inline" style="padding-bottom: 10px;">
  Coupons</h1>

  </div>
  <hr class="wp-header-end">
</div>
<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th>Keyword</th>
      <th>Count Value</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($keywords)): ?>
      <?php foreach ($keywords as $row): ?>
        <tr>
          <td><?= esc_html($row['keyword']) ?></td>
          <td>%<?= esc_html($row['count']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="5">No keywords found.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

</div>