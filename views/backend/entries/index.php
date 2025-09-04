<style>
  .alternate,
  .striped>tbody>:nth-child(odd),
  ul.striped>:nth-child(odd) {
    background-color: #f6f9fc !important;
  }

  td {
    border-bottom: 1px solid #ececf2;
    border-top: 1px solid #ececf2;
  }
</style>
<div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
  <div style="display: flex;align-items: center;gap:10px; margin-bottom:10px;flex-wrap: wrap;width:100%">
    <h1 class=" wp-heading-inline" style="padding-bottom: 10px;">
      ABC <?php echo $year ?> - Registration Entries</h1>
  </div>
  <hr class="wp-header-end">
  <form method="get" style="margin-bottom:1em;">
    <input type="hidden" name="page" value="abc_events_entries" />

    <!-- Global keyword search -->
    <input type="hidden" name="s"
      id="keywordSearch"
      placeholder="Search all fields..."
      value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" />

    <!-- Field dropdown -->
    <select name="field_key" id="fieldKey">
      <option value="">Filter by field...</option>
      <?php foreach ($form['fields'] as $field): ?>
        <?php if (in_array($field->type, ['captcha', 'consent'])) continue; ?>
        <option value="<?php echo esc_attr($field->id); ?>"
          data-type="<?php echo esc_attr($field->type); ?>"
          <?php selected($_GET['field_key'] ?? '', $field->id); ?>>
          <?php echo esc_html($field->label); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Operator -->
    <select name="operator" id="operator">
      <?php
      $operators = ['is', 'isnot', 'contains', '>', '<'];
      foreach ($operators as $op): ?>
        <option value="<?php echo $op; ?>"
          <?php selected($_GET['operator'] ?? '', $op); ?>>
          <?php echo $op; ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Value input (dynamic) -->
    <span id="fieldValueWrapper">
      <input type="text" name="field_value"
        id="fieldValue"
        placeholder="Value..."
        value="<?php echo esc_attr($_GET['field_value'] ?? ''); ?>" />
    </span>

    <button type="submit" class="button">Filter</button>
  </form>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const fieldKey = document.getElementById("fieldKey");
      const fieldValueWrapper = document.getElementById("fieldValueWrapper");
      const keywordSearch = document.getElementById("keywordSearch");

      // Preload PHP data for choices
      const fieldChoices = <?php
                            $choicesMap = [];
                            foreach ($form['fields'] as $field) {
                              if (!empty($field->choices)) {
                                $choicesMap[$field->id] = array_map(function ($c) {
                                  return ['text' => $c['text'], 'value' => $c['value']];
                                }, $field->choices);
                              }
                            }
                            echo json_encode($choicesMap);
                            ?>;

      function updateFieldInput() {
        const selected = fieldKey.options[fieldKey.selectedIndex];
        const type = selected.dataset.type;
        const fieldId = selected.value;

        let inputHtml = "";

        if (!fieldId) {
          // fallback: show normal text
          inputHtml = `<input type="text" name="field_value" id="fieldValue" placeholder="Value...">`;
          keywordSearch.style.display = "inline-block";
        } else if (fieldChoices[fieldId]) {
          // Dropdown / radio / checkbox field
          inputHtml = `<select name="field_value" id="fieldValue">`;
          fieldChoices[fieldId].forEach(choice => {
            inputHtml += `<option value="${choice.value}">${choice.text}</option>`;
          });
          inputHtml += `</select>`;
          keywordSearch.style.display = "none"; // hide global search
        } else if (["number"].includes(type)) {
          inputHtml = `<input type="number" name="field_value" id="fieldValue">`;
          keywordSearch.style.display = "none";
        } else if (["date"].includes(type)) {
          inputHtml = `<input type="date" name="field_value" id="fieldValue">`;
          keywordSearch.style.display = "none";
        } else {
          // text / email / phone etc.
          inputHtml = `<input type="text" name="field_value" id="fieldValue" placeholder="Value...">`;
          keywordSearch.style.display = "none";
        }

        fieldValueWrapper.innerHTML = inputHtml;
      }

      fieldKey.addEventListener("change", updateFieldInput);

      // Run once on load
      updateFieldInput();
    });
  </script>



</div>
<?php if (!empty($entries)): ?>
  <div style="overflow-x:auto; max-width:100%;">
    <table class="widefat striped">
      <thead>
        <tr>
          <th>#</th>
          <?php
          // Collect headers from the first entry
          $headers = array_keys(reset($entries));

          // Apply skip rules
          $filteredHeaders = array_filter($headers, function ($header) {
            if (in_array(strtolower($header), ['form_id', 'entry_id', 'participation option', 'telephone', 'country of residence',  'nationality'])) {
              return false;
            }
            if (strlen($header) > 20) { // skip overly long headers
              return false;
            }
            return true;
          });

          foreach ($filteredHeaders as $header): ?>
            <th><?php echo str_replace(' ', '&nbsp;', esc_html($header)); ?></th>
          <?php endforeach; ?>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $entryIndex => $entry): ?>
          <tr>
            <td><?php echo esc_html($entryIndex + 1); ?></td>
            <?php foreach ($filteredHeaders as $header): ?>
              <td style="<?php echo esc_attr($header) === 'Delegate Name' ? 'display:flex;gap:5px' : ''; ?>">
                <?php
                if (!isset($entry[$header])) {
                  echo '-';
                } elseif (is_array($entry[$header])) {
                  // Handle checkbox/multiselect/complex subfields
                  foreach ($entry[$header] as $subLabel => $subVal) {
                    if (is_numeric($subLabel)) {
                      echo '<span class="tag">' . esc_html($subVal) . '</span> ';
                    } elseif (in_array($subLabel, ['Prefix', 'First', 'Last', 'First Name', 'Middle Name', 'Last Name', 'Suffix'])) {
                      echo '<div> ' . esc_html($subVal) . '</div>';
                    } else {
                      echo '<div><strong>' . esc_html($subLabel) . ':</strong> ' . esc_html($subVal) . '</div>';
                    }
                  }
                } else {
                  echo esc_html($entry[$header]);
                }
                ?>
              </td>
            <?php endforeach; ?>
            <td>
              <a href="<?php echo esc_url(add_query_arg(['action' => 'details', 'id' => $entry['entry_id']])); ?>">
                View
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p>No entries found.</p>
<?php endif; ?>