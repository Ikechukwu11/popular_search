<div class="gf-wrap">
  <form method="post" class="custom-gf-form" novalidate>
    <input type="hidden" name="gform_id" value="<?php echo esc_attr($form['id']); ?>">
    <input type="hidden" name="gform_page" value="<?php echo esc_attr($page); ?>">
    <input id="gform_source_page_number" name="gform_source_page_number" type="hidden" value="<?php echo esc_attr($page); ?>">
    <input id="gform_target_page_number" name="gform_target_page_number" type="hidden" value="<?php echo esc_attr($target); ?>">

    <?php if (!empty($state['failed_validation'])): ?>
      <div class="notice notice-error" style="padding:10px;margin:0 0 12px;">
        <p><strong>There were problems with your submission.</strong> Please correct the errors below and try again.</p>
        <?php if (!empty($errors['_summary'])): ?>
          <div class="gf-error-message"><?php echo wp_kses_post($errors['_summary']); ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php
    // Group fields by page
    $steps = [];
    foreach ($form['fields'] as $field) {
      $page = $field->pageNumber ?? 1;
      if (!isset($steps[$page])) {
        $steps[$page] = [];
      }
      $steps[$page][] = $field;
    }

    // Helper function to get posted value
    $get_val = function ($input_name, $default = '') use ($state) {
      return $state['data'][$input_name] ?? $default;
    };

    // Loop through steps/pages
    foreach ($steps as $page_num => $fields):
      $display = ($current_page === 1 || $page_num === 1) ? '' : 'style="display:none;"';
      echo '<div class="gf-step step-' . esc_attr($page_num) . '" ' . $display . '>';

      foreach ($fields as $field):
        $type = $field->type ?? '';
        //$hidden = $field['visibility'] ?? false;
        if ($type === 'hidden') {
          $hidden_key = 'input_' . $field['id'];
          $default    = $field['defaultValue'] ?? '';
          $value      = $val($hidden_key, $default);
          echo '<input type="hidden" name="' . esc_attr($hidden_key) . '" value="' . esc_attr($value) . '">';
          continue;
        }

        $fid = (string) $field['id'];

        $input_name = 'input_' . $fid;
        $required = !empty($field->isRequired);
        $isRequired = $required ? 'required' : '';
        $label = $field->label ?? '';
        $value = $get_val($input_name);
        $css_err    = $has_error($fid) ? ' has-error' : '';
        $err_msg    = $field_error($fid);
        $hideLabel = in_array($field['type'], ['html']);
        $cssClass = $field['cssClass'] ?? '';
        $readonly  = $cssClass === 'gf_readonly' ? 'readonly="readonly"' : '';

        echo '<div class="gf-field gf-type-' . esc_attr($type) . $css_err . '">';

        if ($label !== ''  && !$hideLabel) {
          echo '<legend for="input_' . esc_attr($form['id']) . '_' . esc_attr($fid) . '">' . esc_html($label);
          if ($required) echo ' <span class="req">*</span>';
          echo '</legend>';
        }
        echo '<div class="' . esc_attr($cssClass) . '">';
        // Render input types
        switch ($type) {
          case 'text':
          case 'email':
          case 'number':
          case 'phone':
          case 'date':
            $html_type = $type === 'phone' ? 'tel' : ($type === 'date' ? 'date' : $type);
            printf(
              '<input type="%s" id="input_%d_%s" name="%s" value="%s"  data-value="%s" ' . $readonly . ' %s/>',
              esc_attr($html_type),
              esc_attr($form['id']),
              esc_attr($fid),
              esc_attr($input_name),
              esc_attr($value),
              esc_attr($value),
              $isRequired
            );
            break;

          case 'textarea':
            printf(
              '<textarea id="input_%d_%s" name="%s" %s>%s</textarea>',
              esc_attr($form['id']),
              esc_attr($fid),
              esc_attr($input_name),
              $isRequired,
              esc_textarea($value)
            );
            break;

          case 'select':
            printf('<select id="input_%d_%s" name="%s" %s>', esc_attr($form['id']), esc_attr($fid), esc_attr($input_name), $isRequired);
            foreach ((array)($field['choices'] ?? []) as $choice) {
              $choiceVal = $choice['value'] ?? $choice['text'] ?? '';
              $choiceLab = $choice['text'] ?? $choiceVal;
              $selected = ($value === $choiceVal) ? 'selected' : '';
              printf('<option value="%s" %s>%s</option>', esc_attr($choiceVal), $selected, esc_html($choiceLab));
            }
            echo '</select>';
            break;

          case 'radio':
            echo '<div class="gf-inline-group">';
            foreach ((array)($field['choices'] ?? []) as $idx => $choice) {
              $choiceVal = $choice['value'] ?? $choice['text'] ?? '';
              $choiceLab = $choice['text'] ?? $choiceVal;
              $checked = ($value === $choiceVal) ? 'checked' : '';
              $rid = 'input_' . esc_attr($form['id']) . '_' . esc_attr($fid) . '_' . $idx;
              echo '<label><input type="radio" id="' . $rid . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($choiceVal) . '" ' . $checked . '> ' . esc_html($choiceLab) . '</label>';
            }
            echo '</div>';
            break;

          case 'checkbox':
            echo '<div class="gf-inline-group">';
            foreach ((array)($field['inputs'] ?? []) as $sub) {
              if (!empty($sub['isHidden'])) continue;
              $sub_id = $sub['id'];
              $sub_name = 'input_' . $sub_id;
              $checked = !empty($state['data'][$sub_name]) ? 'checked' : '';
              $labelTxt = $sub['label'] ?? 'Option';
              $cid = 'input_' . esc_attr($form['id']) . '_' . esc_attr($sub_id);
              echo '<label><input type="checkbox" id="' . $cid . '" name="' . esc_attr($sub_name) . '" value="' . esc_attr($labelTxt) . '" ' . $checked . '> ' . esc_html($labelTxt) . '</label>';
            }
            echo '</div>';
            break;

          case 'name':
            echo '<div class="gf-inline-group name">';
            foreach ((array)($field['inputs'] ?? []) as $sub) {
              if (!empty($sub['isHidden'])) continue;
              $sub_id    = (string)$sub['id'];
              $sub_name  = 'input_' . $sub_id;
              $placeholder = $sub['placeholder'] ?? $sub['customLabel'] ?? $sub['label'] ?? '';
              if (!empty($sub['choices'])) {
                echo '<div>';
                if (!empty($sub['label'])) {
                  echo '<div style="font-size:12px;margin-bottom:4px;">' . esc_html($sub['label']) . '</div>';
                }
                printf(
                  '<select id="input_%d_%s" name="%s" ' . $isRequired . '>',
                  esc_attr($form['id']),
                  esc_attr($sub_id),
                  esc_attr($sub_name)
                );
                foreach ($sub['choices'] as $ch) {
                  $cVal = (string)($ch['value'] ?? $ch['text'] ?? '');
                  $cLab = (string)($ch['text'] ?? $cVal);
                  $selected = ($val($sub_name) === $cVal) ? 'selected' : '';
                  printf('<option value="%s" %s>%s</option>', esc_attr($cVal), $selected, esc_html($cLab));
                }
                echo '</select>';
                echo '</div>';
              } else {
                echo '<div>';
                if (!empty($sub['label'])) {
                  echo '<div style="font-size:12px;margin-bottom:4px;">' . esc_html($sub['label']) . '</div>';
                }
                printf(
                  '<input type="text" id="input_%d_%s" name="%s" placeholder="%s" value="%s" data-value="%s" ' . $readonly . ' ' . $isRequired . ' />',
                  esc_attr($form['id']),
                  esc_attr($sub_id),
                  esc_attr($sub_name),
                  esc_attr($placeholder),
                  esc_attr($val($sub_name)),
                  esc_attr($val($sub_name))
                );
                echo '</div>';
              }
            }
            echo '</div>';
            break;

          case 'consent':
            $checked = $val($input_name) ? 'checked' : 'checked';
            $labelTxt = $field['checkboxLabel'] ?? $label;
            printf(
              '<label><input type="checkbox" id="input_%d_%s" name="%s" value="1" %s> %s</label>',
              esc_attr($form['id']),
              esc_attr($fid),
              esc_attr($input_name),
              $checked,
              esc_html($labelTxt)
            );
            break;

          case 'captcha':
            echo '<div class="gf-field gf-type-captcha' . ($errors['captcha'] ?? false ? ' has-error' : '') . '">';
            if ($recaptcha_site_key) {
              echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($recaptcha_site_key) . '"></div>';
            } else {
              echo '<em style="color:#b32d2e;">reCAPTCHA site key missing. Provide via GF settings or filter `abc_gf_recaptcha_site_key`.</em>';
            }
            // GF expects g-recaptcha-response to be posted; the widget handles that automatically.
            if (!empty($errors['captcha'])) {
              echo '<div class="gf-error-message" role="alert">' . esc_html($errors['captcha']) . '</div>';
            }
            echo '</div>';
            break;

          default:
            echo '<!-- unsupported field type: ' . esc_html($type) . ' -->';
        }
        echo '</div>';
        if ($err_msg) {
          echo '<div class="gf-error-message" role="alert">' . esc_html($err_msg) . '</div>';
        }
        echo '</div>';
      endforeach;
      if ($page_num === 2) {
        echo '
        <div class="gf-total gfield">
          <input type="hidden" id="discount" name="discount" value="0.0" />
          <label>SUBTOTAL: <strong id="subtotal_display"></strong></label>
          <label>DISCOUNT: <strong id="discount_display"></strong></label>
          <label>TOTAL: <strong id="total_display"></strong></label>
        </div>
        ';
      };
      echo '</div>'; // close step
    endforeach;
    ?>



    <div class="gf-actions">
      <button class="button" type="button" id="gf-prev" <?php echo $display ?>>Previous</button>
      <button class="button button-primary" type="button" id="gf-next" <?php echo $display ?>>Next</button>
      <button class="button button-primary" type="submit" id="gf-submit" <?php echo $display ?>>Submit</button>
    </div>
  </form>
</div>