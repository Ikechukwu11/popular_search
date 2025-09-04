<?php

namespace ABCEvents\Controllers;

use GFAPI;
use ABCEvents\Models\Pricing;
use ABCEvents\Models\Setting;
use ABCEvents\Controllers\Controller;

class ABCFormController extends Controller
{
  public static function hooks()
  {
    $controller = new self();
    // List of form IDs to target
    $form_ids = [1];
    $formID = 1;

    return [
      // [
      //   'hook' => 'abc_registration_form',
      //   'callback' => [$controller, 'render_normal'],
      //   'type' => 'shortcode',
      // ],
      [
        'hook' => 'abc_registration_form_js',
        'callback' => [$controller, 'render_form_js'],
        'type' => 'shortcode',
      ]
    ];
  }

  public static function render_normal($atts)
  {
    $instance  = new static();
    $setting   = new Setting();
    $recaptcha = $setting->getSettings('recaptcha', $instance->event_year);

    $atts = shortcode_atts(['id' => 1], $atts);
    $form = GFAPI::get_form($atts['id']);

    if (!$form) {
      echo '<p>Form not found.</p>';
      return;
    }

    $state = [
      'data'              => [],
      'errors'            => [],
      'page'              => 1,
      'target'            => 0,
      'failed_validation' => false,
    ];

    if (
      $_SERVER['REQUEST_METHOD'] === 'POST'
      && !empty($_POST['gform_id'])
      && intval($_POST['gform_id']) === intval($form['id'])
    ) {
      // Keep posted values for re-render
      $state['data'] = stripslashes_deep($_POST);

      // Determine source and target (use numeric names expected)
      $source_page = isset($_POST['gform_source_page_number'])
        ? (int) $_POST['gform_source_page_number']
        : (int) ($_POST['gform_page'] ?? 1);

      $target_page = isset($_POST['gform_target_page_number'])
        ? (int) $_POST['gform_target_page_number']
        : 0;

      // field_values placeholder (dynamic population support)
      $form['fields'] = array_filter($form['fields'], function ($field) {
        return $field->type !== 'captcha';
      });

      //var_dump($state['data']);

      // ✅ Skip captcha globally or conditionally
      add_filter('gform_field_validation', function ($result, $value, $form, $field) {
        if ($field->type === 'captcha') {
          $result['is_valid'] = true;
          $result['message']  = '';
        }
        return $result;
      }, 10, 4);


      // Call GFAPI::submit_form (core API). initiated_by = 1 => webform
      $result = GFAPI::validate_form(
        $form['id'],
        $_POST,
        [],
        $target_page,
        $source_page,
      );


      // If WP_Error, add general error and re-render
      if (is_wp_error($result)) {
        $state['failed_validation'] = true;
        $state['errors']['_general'] = $result->get_error_message();
        $state['page'] = $source_page;
        $state['target'] = $target_page;
      } else {
        // result is an array as per GF docs
        // Update $form with possible GF-updated form object (with validation messages)
        if (!empty($result['form']) && is_array($result['form'])) {
          $form = $result['form'];
        }

        // If valid
        if (!empty($result['is_valid'])) {
          // GF returns 'page_number' — the page that should be displayed next.
          $next_page = isset($result['page_number']) ? (int) $result['page_number'] : 0;

          if ($next_page === 0) {
            $validated_post = $_POST;

            $entry = ['form_id' => $form['id']];

            foreach ($form['fields'] as $field) {

              // Handle nested inputs
              if (!empty($field->inputs) && is_array($field->inputs)) {
                foreach ($field->inputs as $input) {
                  $sub_input_id = $input['id']; // e.g., 3.2, 3.3
                  $post_key = 'input_' . str_replace('.', '_', $sub_input_id); // GF POST uses input_3_2

                  if (!isset($validated_post[$post_key])) {
                    continue;
                  }

                  $value = $validated_post[$post_key];

                  if (is_array($value)) {
                    $value = $value[0];
                  }

                  // Use the **dot notation key** for the entry
                  $entry[$sub_input_id] = $value;
                }
              }

              // Handle normal fields
              else {
                $post_key = 'input_' . $field->id;
                if (!isset($validated_post[$post_key])) {
                  continue;
                }

                $value = $validated_post[$post_key];

                if (is_array($value)) {
                  $value = $value[0];
                }

                $entry[$field->id] = $value;
              }
            }

            // Add the entry
            $entry_result = GFAPI::add_entry($entry);

            if (!is_wp_error($entry_result)) {

              GFAPI::send_notifications($form, $entry, $event = 'form_submission');
              // FINAL SUBMISSION — entry created
              // result may contain entry_id, confirmation_type, confirmation_redirect/message
              // If GF wants to redirect, follow that
              if (!empty($result['confirmation_type']) && $result['confirmation_type'] === 'redirect' && !empty($result['confirmation_redirect'])) {
                wp_safe_redirect($result['confirmation_redirect']);
                exit;
              }

              // Otherwise display confirmation message (and stop rendering the form)
              if (!empty($result['confirmation_message'])) {
                echo '<div class="gform_confirmation">' . wp_kses_post($result['confirmation_message']) . '</div>';
                return;
              }

              // Fallback: mark as completed and show simple message
              echo '<div class="gform_confirmation">Thank you — your submission is complete.</div>';
              return;
            }
          } else {
            // Not final — move to next page (GF returns the page to show)
            $state['page'] = $next_page;
            $state['target'] = $target_page;
            // GFAPI::get_current_page($form['id'])

            if ($next_page > 1) {
              $new_fields = [];

              foreach ($form['fields'] as $field) {
                $new_fields[] = $field; // keep original intact

                if ($field->pageNumber === 1) {
                  $value = rgpost("input_{$field->id}");

                  if ($value !== null) {
                    // clone field as hidden
                    $hidden = clone $field;
                    $hidden->type = 'hidden';
                    $hidden->defaultValue = $value;
                    $hidden->pageNumber = $next_page; // attach to next page

                    $new_fields[] = $hidden;
                  }
                }
              }
              $form['fields'] = $new_fields;
            }
          }
        } else {
          // Validation failed
          $state['failed_validation'] = true;

          // GF returns 'page_number' (page that should be shown next) and 'source_page_number' (submitted page)
          $state['page'] = isset($result['source_page_number']) ? (int) $result['source_page_number'] : $source_page;
          $state['target'] = isset($result['page_number']) ? (int) $result['page_number'] : $target_page;

          if (!empty($result['validation_messages']) && is_array($result['validation_messages'])) {
            $state['errors'] = $result['validation_messages']; // keyed by input name like "3" or "3.3"
          }

          // In some GF versions there is a validation_summary — keep it as general error
          if (!empty($result['validation_summary'])) {
            $state['errors']['_summary'] = $result['validation_summary'];
          }
        }
      }
    }

    // Always render form with the up-to-date $form and $state
    static::abc_gf_form_normal($form, $state, $recaptcha);
  }

  public static function abc_gf_form_normal(array $form, array $state = [], $recaptcha = null)
  {
    $data   = $state['data']   ?? [];
    $errors = $state['errors'] ?? [];
    $page   = max(1, (int)($state['page'] ?? 1));
    $target = isset($state['target']) ? (int)$state['target'] : 0;

    // Find if multi-step and group fields per page
    $has_captcha = false;
    $is_multistep = false;
    $pages = []; // pageNumber => [fields]

    foreach ($form['fields'] as $fld) {
      if (($fld['type'] ?? '') === 'page') {
        $is_multistep = true;
      }
      if (($fld['type'] ?? '') === 'captcha') {
        $has_captcha = true;
      }

      $pnum = (int)($fld['pageNumber'] ?? 1);
      $pages[$pnum] = $pages[$pnum] ?? [];
      $pages[$pnum][] = $fld;
    }

    $page_count = count($pages);
    if ($page > $page_count) $page = $page_count;

    // Simple progress width
    $progress_pct = $page_count > 0 ? (int) floor(($page / $page_count) * 100) : 0;

    // Try to get reCAPTCHA site key from $recaptcha
    $recaptcha_site_key = is_array($recaptcha) && isset($recaptcha['recaptcha_site_key']) ? $recaptcha['recaptcha_site_key'] : '';

    // Helpers
    $val = function ($key, $default = '') use ($data) {
      if (!isset($data[$key])) return $default;
      return is_array($data[$key]) ? $data[$key] : (string)$data[$key];
    };
    $field_error = function ($fid) use ($errors) {
      return $errors[(string)$fid] ?? '';
    };
    $has_error = function ($fid) use ($errors) {
      return !empty($errors[(string)$fid]);
    };

    // Styles (same as yours)
?>
    <style>
      .gf-wrap {
        max-width: 880px;
      }

      .gf-progress {
        background: #f1f1f1;
        border-radius: 8px;
        height: 10px;
        margin: 0 0 16px;
        overflow: hidden;
      }

      .gf-progress>span {
        display: block;
        height: 100%;
        background: #0073aa;
        width: 0;
        transition: width .3s;
      }

      .gf-field {
        margin: 14px 0;
        gap: 8px;
        display: grid;
      }

      .gf-field label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        font-size: 15px;
      }

      .gf-field input[type=text],
      .gf-field input[type=email],
      .gf-field input[type=number],
      .gf-field input[type=tel],
      .gf-field input[type=date],
      .gf-field select,
      .gf-field textarea {
        width: 100%;
        max-width: 100%;
        appearance: textfield;
        padding: 5px 10px;
      }

      .gf-error-message {
        color: #b32d2e;
        font-size: 13px;
        margin-top: 6px;
      }

      .gf-field.has-error input,
      .gf-field.has-error select,
      .gf-field.has-error textarea {
        border-color: #b32d2e;
      }

      .gf-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
      }

      .gf-actions button,
      .gf-actions button:hover {
        background: #61CE70 !important;
        color: white !important;
        border: none !important;
        min-width: 145px !important;
        border-radius: 3px;
        display: inline-block;
        font-size: 16px;
        padding: 1rem;
        text-align: center;
        transition: all .3s;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
        white-space: nowrap;
      }

      .apply-coupon-btn {
        background: #61CE70 !important;
        color: white !important;
        border: none !important;
        display: inline-block;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
        white-space: nowrap;
        right: 3px;
        position: absolute;
        top: 2px;
        padding: 5px 10px;
        outline: none;
      }

      .gf-coupon-input {
        position: relative;
      }

      .req {
        color: #b32d2e;
      }

      .gf-inline-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }

      .gf-inline-group label {
        font-weight: 400;
      }

      .gf-inline-group.name {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
      }

      @media screen and (max-width: 600px) {
        .gf-inline-group.name {
          grid-template-columns: 1fr;
        }
      }

      .gf-inline-group legend,
      legend {
        display: inline-block;
        color: #7a7a7a;
        font-size: 16px !important;
        font-weight: 700;
        /* margin-bottom: 8px; */
        padding: 0;
      }

      .gf-field.gf-type-radio .gf-inline-group {
        display: block;
      }
    </style>

    <div class="gf-wrap">
      <form method="post" class="custom-gf-form" novalidate>
        <input type="hidden" name="gform_id" value="<?php echo esc_attr($form['id']); ?>">
        <input type="hidden" name="gform_page" value="<?php echo esc_attr($page); ?>">
        <!-- Important multipage hidden fields names -->
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

        <?php if ($is_multistep && !empty($form['pagination'])): ?>
          <div class="gf-progress" aria-hidden="true">
            <span style="width: <?php echo esc_attr($progress_pct); ?>%"></span>
          </div>
        <?php endif; ?>

        <?php
        // Render only the current page’s fields for multi-step; else render all
        $render_pages = $is_multistep ? [$page => ($pages[$page] ?? [])] : $pages;

        foreach ($render_pages as $pnum => $fields):
          foreach ($fields as $field):
            $type = $field['type'] ?? '';
            $hidden = $field['visibility'] ?? false;
            if ($type === 'page') {
              continue;
            }

            // Hidden fields (render as hidden inputs)
            if ($type === 'hidden') {
              $hidden_key = 'input_' . $field['id'];
              $default    = $field['defaultValue'] ?? '';
              $value      = $val($hidden_key, $default);
              echo '<input type="hidden" name="' . esc_attr($hidden_key) . '" value="' . esc_attr($value) . '">';
              continue;
            }


            $fid        = (string)$field['id'];
            $required   = !empty($field['isRequired']);
            $isRequired = $required ? 'required="required"' : '';
            $label      = trim((string)($field['label'] ?? ''));
            $css_err    = $has_error($fid) ? ' has-error' : '';
            $err_msg    = $field_error($fid);
            $input_name = 'input_' . $fid;
            $hideLabel = in_array($field['type'], ['html']);
            $cssClass = $field['cssClass'] ?? '';

            echo '<div class="gf-field gf-type-' . esc_attr($type) . $css_err . '">';

            if ($label !== '' && !$hideLabel) {
              echo '<legend for="input_' . esc_attr($form['id']) . '_' . esc_attr($fid) . '">' . esc_html($label);
              if ($required) echo ' <span class="req">*</span>';
              echo '</legend>';
            }

            echo '<div class="' . esc_attr($cssClass) . '">';
            // --- render types (same as your switch) ---
            switch ($type) {
              case 'text':
              case 'email':
              case 'number':
              case 'phone':
              case 'date':
                $html_type = $type === 'phone' ? 'tel' : ($type === 'date' ? 'date' : $type);
                $readonly  = $cssClass === 'gf_readonly' ? 'readonly="readonly"' : '';
                printf(
                  '<input type="%s" id="input_%d_%s" name="%s" value="%s" ' . $isRequired . '  data-value="%s" ' . $readonly . '/>',
                  esc_attr($html_type),
                  esc_attr($form['id']),
                  esc_attr($fid),
                  esc_attr($input_name),
                  esc_attr($val($input_name)),
                  esc_attr($val($input_name)),
                );
                break;

              case 'textarea':
                printf(
                  '<textarea id="input_%d_%s" name="%s" ' . $isRequired . '>%s</textarea>',
                  esc_attr($form['id']),
                  esc_attr($fid),
                  esc_attr($input_name),
                  esc_textarea($val($input_name))
                );
                break;

              case 'radio':
                echo '<div class="gf-inline-group">';
                foreach ((array)($field['choices'] ?? []) as $idx => $choice) {
                  $choiceVal = (string)($choice['value'] ?? $choice['text'] ?? '');
                  $choiceLab = (string)($choice['text'] ?? $choiceVal);
                  $checked   = ($val($input_name) === $choiceVal) ? 'checked' : '';
                  $rid = 'input_' . esc_attr($form['id']) . '_' . esc_attr($fid) . '_' . $idx;
                  echo '<label><input type="radio" id="' . $rid . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($choiceVal) . '" ' . $checked . '> ' . esc_html($choiceLab) . '</label>';
                }
                echo '</div>';
                break;

              case 'select':
                printf('<select id="input_%d_%s" name="%s" ' . $isRequired . '>', esc_attr($form['id']), esc_attr($fid), esc_attr($input_name));
                foreach ((array)($field['choices'] ?? []) as $choice) {
                  $choiceVal = (string)($choice['value'] ?? $choice['text'] ?? '');
                  $choiceLab = (string)($choice['text'] ?? $choiceVal);
                  $selected  = ($val($input_name) === $choiceVal) ? 'selected' : '';
                  printf('<option value="%s" %s>%s</option>', esc_attr($choiceVal), $selected, esc_html($choiceLab));
                }
                echo '</select>';
                break;

              case 'checkbox':
                echo '<div class="gf-inline-group">';
                foreach ((array)($field['inputs'] ?? []) as $sub) {
                  if (!empty($sub['isHidden'])) continue;
                  $sub_id   = (string)$sub['id'];
                  $sub_name = 'input_' . $sub_id;
                  $checked  = $val($sub_name) ? 'checked' : '';
                  $labelTxt = (string)($sub['label'] ?? 'Option');
                  $cid      = 'input_' . esc_attr($form['id']) . '_' . esc_attr($sub_id);
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
                      '<select id="input_%d_%s" name="%s" ' . $isRequired . '  data-value="%s" >',
                      esc_attr($form['id']),
                      esc_attr($sub_id),
                      esc_attr($sub_name),
                      esc_attr($val($sub_name)),
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
                    $readonly  = $cssClass === 'gf_readonly' ? 'readonly="readonly"' : '';
                    printf(
                      '<input type="text" id="input_%d_%s" name="%s" placeholder="%s" value="%s"  data-value="%s" ' . $readonly . '/>',
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
                $checked = $val($input_name) ? 'checked' : '';
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
                echo '<label>Captcha</label>';
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

              case 'html':
                echo $field['content'];
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
        endforeach;
        ?>

        <div class="gf-actions">
          <?php if ($is_multistep): ?>
            <?php if ($page > 1): ?>
              <button class="button" type="submit" name="gform_previous" value="1"
                onclick="
                  this.form['gform_source_page_number'].value=<?php echo (int) $page + 1; ?>;
                  this.form['gform_target_page_number'].value=<?php echo (int) ($page - 1); ?>;
                ">Previous</button>
            <?php endif; ?>

            <?php if ($page < $page_count): ?>
              <button class="button button-primary" type="submit" name="gform_next" value="1"
                onclick="
                  this.form['gform_source_page_number'].value=<?php echo (int) $page; ?>;
                  this.form['gform_target_page_number'].value=<?php echo (int) ($page + 1); ?>;
                ">Next</button>
            <?php else: ?>
              <button class="button button-primary" type="submit"
                onclick="
                  this.form['gform_source_page_number'].value=<?php echo (int) $page; ?>;
                  this.form['gform_target_page_number'].value=0;
                ">
                <?php echo esc_html($form['buttonText'] ?? 'Submit'); ?>
              </button>
            <?php endif; ?>
          <?php else: ?>
            <button class="button button-primary" type="submit"
              onclick="
                this.form['gform_source_page_number'].value=1;
                this.form['gform_target_page_number'].value=0;
              ">
              <?php echo esc_html($form['buttonText'] ?? 'Submit'); ?>
            </button>
          <?php endif; ?>
        </div>


      </form>
    </div>

    <script type="text/javascript">
      jQuery(document).ready(function() {
        function formatDate() {
          let d = new Date(),
            str = Math.random().toString(20).substr(2, 5) +
            d.getTime().toString(36).substr(0, 9);
          return str.toUpperCase();
        }
        let original_val = jQuery(".gf_readonly input").data("value");
        if (original_val) {
          jQuery(".gf_readonly input").val(original_val);
        } else {
          //Apply only to inputs with class .gf_readonly
          //jQuery(".gf_readonly input").attr("readonly", "readonly");
          jQuery(".gf_readonly input").val(formatDate());
        }
      });
    </script>

    <?php if ($has_captcha && $recaptcha_site_key): ?>
      <script src="https://www.google.com/recaptcha/api.js" async defer></script>

<?php endif;
  }

  // ---- updated render_form ----
  public static function render_form_js($atts)
  {
    $instance  = new static();
    $setting   = new Setting();
    $pricing = new Pricing();
    $currentPrices = $pricing->getCurrentPricing($instance->event_year);
    $paymentEnable = $setting->getSettings('payment', $instance->event_year);
    $recaptcha = $setting->getSettings('recaptcha', $instance->event_year);

    if (!$paymentEnable) {
      $paymentEnable = ['payment_enable' => 'false'];
    }

    $atts = shortcode_atts(['id' => 1], $atts);
    $form = GFAPI::get_form($atts['id']);

    if (!$form) {
      echo '<p>Form not found.</p>';
      return;
    }

    $state = [
      'data'              => [],
      'errors'            => [],
      'page'              => 1,
      'target'            => 0,
      'failed_validation' => false,
    ];

    if (
      $_SERVER['REQUEST_METHOD'] === 'POST'
      && !empty($_POST['gform_id'])
      && intval($_POST['gform_id']) === intval($form['id'])
    ) {
      // Keep posted values for re-render
      $state['data'] = stripslashes_deep($_POST);

      // Determine source and target (use numeric names expected)
      $source_page = isset($_POST['gform_source_page_number'])
        ? (int) $_POST['gform_source_page_number']
        : (int) ($_POST['gform_page'] ?? 1);

      $target_page = isset($_POST['gform_target_page_number'])
        ? (int) $_POST['gform_target_page_number']
        : 0;

      // field_values placeholder (dynamic population support)
      $form['fields'] = array_filter($form['fields'], function ($field) {
        return $field->type !== 'captcha';
      });

      // ✅ Skip captcha globally or conditionally
      add_filter('gform_field_validation', function ($result, $value, $form, $field) {
        if ($field->type === 'captcha') {
          $result['is_valid'] = true;
          $result['message']  = '';
        }
        return $result;
      }, 10, 4);



      // Call GFAPI::submit_form (core API). initiated_by = 1 => webform
      $result = GFAPI::validate_form(
        $form['id'],
        $_POST
      );

      // If WP_Error, add general error and re-render
      if (is_wp_error($result)) {
        $state['failed_validation'] = true;
        $state['errors']['_general'] = $result->get_error_message();
        $state['page'] = $source_page;
        $state['target'] = $target_page;
      } else {
        // result is an array as per GF docs
        // Update $form with possible GF-updated form object (with validation messages)
        if (!empty($result['form']) && is_array($result['form'])) {
          $form = $result['form'];
        }

        // If valid
        if (!empty($result['is_valid'])) {
          // GF returns 'page_number' — the page that should be displayed next.
          $next_page = isset($result['page_number']) ? (int) $result['page_number'] : 0;

          if ($next_page === 0) {
            $validated_post = $_POST;

            $entry = ['form_id' => $form['id']];

            foreach ($form['fields'] as $field) {

              // Handle nested inputs
              if (!empty($field->inputs) && is_array($field->inputs)) {
                foreach ($field->inputs as $input) {
                  $sub_input_id = $input['id']; // e.g., 3.2, 3.3
                  $post_key = 'input_' . str_replace('.', '_', $sub_input_id); // GF POST uses input_3_2

                  if (!isset($validated_post[$post_key])) {
                    continue;
                  }

                  $value = $validated_post[$post_key];

                  if (is_array($value)) {
                    $value = $value[0];
                  }

                  // Use the **dot notation key** for the entry
                  $entry[$sub_input_id] = $value;
                }
              }

              // Handle normal fields
              else {
                $post_key = 'input_' . $field->id;
                if (!isset($validated_post[$post_key])) {
                  continue;
                }

                $value = $validated_post[$post_key];

                if (is_array($value)) {
                  $value = $value[0];
                }

                $entry[$field->id] = $value;
              }
            }

            // Add the entry
            $entry_result = GFAPI::add_entry($entry);

            if (!is_wp_error($entry_result)) {

              GFAPI::send_notifications($form, $entry, $event = 'form_submission');
              // FINAL SUBMISSION — entry created
              // result may contain entry_id, confirmation_type, confirmation_redirect/message
              // If GF wants to redirect, follow that
              if (!empty($result['confirmation_type']) && $result['confirmation_type'] === 'redirect' && !empty($result['confirmation_redirect'])) {
                wp_safe_redirect($result['confirmation_redirect']);
                exit;
              }

              // Otherwise display confirmation message (and stop rendering the form)
              if (!empty($result['confirmation_message'])) {
                echo '<div class="gform_confirmation">' . wp_kses_post($result['confirmation_message']) . '</div>';
                return;
              }

              // Fallback: mark as completed and show simple message
              echo '<div class="gform_confirmation">Thank you — your submission is complete.</div>';
              return;
            }
          } else {
            // Not final — move to next page (GF returns the page to show)
            $state['page'] = $next_page;
            $state['target'] = $target_page;
            // GFAPI::get_current_page($form['id'])


            // if ($next_page > 1) {
            //   $new_fields = [];

            //   foreach ($form['fields'] as $field) {
            //     $new_fields[] = $field; // keep original intact

            //     if ($field->pageNumber === 1) {
            //       $value = rgpost("input_{$field->id}");

            //       if ($value !== null) {
            //         // clone field as hidden
            //         $hidden = clone $field;
            //         $hidden->type = 'hidden';
            //         $hidden->defaultValue = $value;
            //         $hidden->pageNumber = $next_page; // attach to next page

            //         $new_fields[] = $hidden;
            //       }
            //     }
            //   }

            //   $form['fields'] = $new_fields;
            // }
          }
        } else {
          // Validation failed
          $state['failed_validation'] = true;

          // GF returns 'page_number' (page that should be shown next) and 'source_page_number' (submitted page)
          $state['page'] = isset($result['source_page_number']) ? (int) $result['source_page_number'] : $source_page;
          $state['target'] = isset($result['page_number']) ? (int) $result['page_number'] : $target_page;

          if (!empty($result['validation_messages']) && is_array($result['validation_messages'])) {
            $state['errors'] = $result['validation_messages']; // keyed by input name like "3" or "3.3"
          }

          // In some GF versions there is a validation_summary — keep it as general error
          if (!empty($result['validation_summary'])) {
            $state['errors']['_summary'] = $result['validation_summary'];
          }
        }
      }
    }

    // Always render form with the up-to-date $form and $state
    static::abc_render_gf_form_js($form, $state, $recaptcha, $currentPrices, $paymentEnable);
  }

  // ---- updated abc_render_gf_form ----
  public static function abc_render_gf_form_js(array $form, array $state = [], $recaptcha = null, $currentPrices = null, $paymentEnable = null)
  {
    $data   = $state['data']   ?? [];
    $errors = $state['errors'] ?? [];
    $page   = max(1, (int)($state['page'] ?? 1));
    $target = isset($state['target']) ? (int)$state['target'] : 0;

    // Find if multi-step and group fields per page
    $has_captcha = false;
    $is_multistep = false;
    $pages = []; // pageNumber => [fields]

    foreach ($form['fields'] as $fld) {
      if (($fld['type'] ?? '') === 'page') {
        $is_multistep = true;
      }
      if (($fld['type'] ?? '') === 'captcha') {
        $has_captcha = true;
      }

      $pnum = (int)($fld['pageNumber'] ?? 1);
      $pages[$pnum] = $pages[$pnum] ?? [];
      $pages[$pnum][] = $fld;
    }

    $page_count = count($pages);
    if ($page > $page_count) $page = $page_count;

    // Simple progress width
    $progress_pct = $page_count > 0 ? (int) floor(($page / $page_count) * 100) : 0;

    // Try to get reCAPTCHA site key from $recaptcha
    $recaptcha_site_key = is_array($recaptcha) && isset($recaptcha['recaptcha_site_key']) ? $recaptcha['recaptcha_site_key'] : '';

    // Helpers
    $val = function ($key, $default = '') use ($data) {
      if (!isset($data[$key])) return $default;
      return is_array($data[$key]) ? $data[$key] : (string)$data[$key];
    };
    $field_error = function ($fid) use ($errors) {
      return $errors[(string)$fid] ?? '';
    };
    $has_error = function ($fid) use ($errors) {
      return !empty($errors[(string)$fid]);
    };

    $current_page = $target < 1 ? 1 : $target;

    $pricingData = [];

    $paymentEnable = $paymentEnable['payment_enable'];
    if ($paymentEnable === 'true') {
      foreach ($currentPrices as $price) {
        $pricingData[$price['slug']] = [
          'current_naira' => (float)$price['current_naira_price'],
          'current_dollar' => (float)$price['current_dollar_price'],
        ];
      }
    }

    if ($paymentEnable === 'false') {
      $pricingData = [
        'current_naira' => 0,
        'current_dollar' => 0,
        'regular_naira' => 0, // For display only
        'regular_dollar' => 0 // For display only
      ];
    }

    // Create an inline script to define the constant
    $inline_script = 'const page = ' . json_encode($current_page) . ';';
    $pricing_script = 'window.pricingData = ' . json_encode($pricingData) . ';';


    wp_enqueue_style('abc-css', plugins_url('abc-event-registration/assets/css/abc.css'), [],    '0.1');
    if ($has_captcha && $recaptcha_site_key) {
      wp_enqueue_script('recaptcha-js', 'https://www.google.com/recaptcha/api.js', [], null, true);
    }
    wp_enqueue_script('abc-scripts', plugins_url('abc-event-registration/assets/js/abc.js'), ['jquery'], '0.1', true);
    // Output the inline script
    wp_add_inline_script('abc-scripts', $inline_script);
    wp_add_inline_script('abc-scripts', $pricing_script);
    wp_localize_script('abc-scripts', 'abcAjax', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('abc_nonce'),
    ]);


    $instance  = new static();

    // Simple conversion to JavaScript format


    return $instance->render('frontend/form/index', [
      'form' => $form,
      'data' => $data,
      'errors' => $errors,
      'page' => $page,
      'target' => $target,
      'pages' => $pages,
      'page_count' => $page_count,
      'progress_pct' => $progress_pct,
      'is_multistep' => $is_multistep,
      'has_captcha' => $has_captcha,
      'recaptcha_site_key' => $recaptcha_site_key,
      'recaptcha' => $recaptcha,
      'field_error' => $field_error,
      'has_error' => $has_error,
      'val' => $val,
      'state' => $state,
      'current_page' => $current_page,
      'currentPrices' => $pricingData
    ]);
  }

  protected function render($view, $data = [])
  {
    extract($data);
    include ABCEVENTS_PATH . 'views/' . $view . '.php';
  }
}
