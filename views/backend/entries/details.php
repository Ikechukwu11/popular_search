<style>
  .entry-details-table {
    background: #fff;
    border: 1px solid #e3e6ef;
    border-radius: 3px;
    box-shadow: 0 1px 4px rgba(18, 25, 97, .0779552);
    min-width: 463px;
  }

  @media (max-width: 767px) {
    .entry-details-table {
      min-width: auto;
      width: 100%;
    }
  }

  .entry-details-table>thead>tr {
    align-items: center;
    border: none;
    display: flex;
    flex-grow: 1;
    font-size: .875rem;
    justify-content: space-around;
    line-height: 2.875rem;
    padding-bottom: 0;
    padding-left: 1.0625rem;
    padding-right: .875rem;
    padding-top: 0;
    position: relative;
  }

  .entry-details-table th {
    flex-grow: 1;
    font-size: .875rem;
    font-weight: 500;
    margin: 0;
    padding: 0;
    text-align: left;
  }

  .entry-details-table>tbody>:nth-child(odd) {
    background: #f6f9fc;
  }

  .entry-details-table .entry-view-field-value {
    background: #f6f9fc;
    border-bottom: 1px solid #ececf2;
    border-top: 1px solid #ececf2;
    color: #242748;
    font-size: 13px;
    line-height: 165%;
    padding: 12px 24px;
  }

  td.entry-view-field-name {
    background-color: #eaf2fa;
    border-bottom: 1px solid #fff;
    font-weight: 700;
    line-height: 1.5;
    padding: 7px 14px;
  }
</style>
<div class="wrap" class="wp-core-ui" style="margin-top: 20px;">
  <div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;">
    <h1 class=" wp-heading-inline" style="padding-bottom: 10px;">
      ABC <?php echo esc_html($year); ?> - Delegate Registration
    </h1>
    <hr class="wp-header-end">
  </div>

  <table cellspacing="0" class="entry-details-table ">
    <thead>
      <tr>
        <th id="details">
          Entry #<?php echo esc_html($entry['entry_id']); ?> </th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entry as $key => $value) :
        if (in_array($key, ['form_id', 'entry_id'])) {
          continue;
        }
      ?>
        <tr>
          <td colspan="2" class="entry-view-field-name"><?php echo esc_html($key); ?></td>
        </tr>
        <tr>
          <td colspan="2" class="entry-view-field-value" style="<?php echo esc_attr($key) === 'Delegate Name' ? 'display:flex;gap:5px' : ''; ?>">
            <?php
            if (!isset($key)) {
              echo '-';
            } elseif (is_array($entry[$key])) {
              // Handle checkbox/multiselect/complex subfields
              foreach ($entry[$key] as $subLabel => $subVal) {
                if (is_numeric($subLabel)) {
                  echo '<span class="tag">' . esc_html($subVal) . '</span> ';
                } elseif (in_array($subLabel, ['Prefix', 'First', 'Last', 'First Name', 'Middle Name', 'Last Name', 'Suffix'])) {
                  echo '<div> ' . esc_html($subVal) . '</div>';
                } else {
                  echo '<div><strong>' . esc_html($subLabel) . ':</strong> ' . esc_html($subVal) . '</div>';
                }
              }
            } else {
              echo $key === 'Email' ? '<a href="mailto:' . esc_html($entry[$key]) . '">' . esc_html($entry[$key]) . '</a>' : esc_html($entry[$key]);
            }
            ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>