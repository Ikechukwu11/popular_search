<style>
  .hidden {
    display: none;
  }
</style>
<?php if ($form): ?>
  <form id="custom-event-form" method="post" enctype="multipart/form-data">
    <?php foreach ($form['fields'] as $field): ?>
      <div class="field-wrapper">
        <label for="input_<?= $field->id ?>"><?= esc_html($field->label); ?></label>

        <?php switch ($field->type):
          case 'text':
          case 'email':
          case 'number':
          case 'phone':
          case 'url':
          case 'password':
          case 'hidden': ?>
            <input
              type="<?= $field->type ?>"
              name="input_<?= $field->id ?>"
              id="input_<?= $field->id ?>"
              value=""
              <?= $field->type === 'hidden' ? 'hidden' : '' ?> />
            <?php break; ?>

          <?php
          case 'name': ?>
            <div class="name-field-group">
              <?php foreach ($field->inputs as $input): ?>
                <?php
                $input_id = $input['id'];
                $input_name = "input_$input_id";
                $label = esc_html($input['label']);
                $is_hidden = isset($input['isHidden']) && $input['isHidden'];
                $input_type = isset($input['inputType']) ? $input['inputType'] : 'text';
                $autocomplete = isset($input['autocompleteAttribute']) ? $input['autocompleteAttribute'] : '';
                $input_classes = $is_hidden ? 'hidden' : '';
                ?>

                <div class="sub-field <?= $input_classes ?>">
                  <label for="<?= esc_attr($input_name) ?>"><?= $label ?></label>

                  <?php if ($input_type === 'radio' && isset($input['choices'])): ?>
                    <?php foreach ($input['choices'] as $choice): ?>
                      <label>
                        <input
                          type="radio"
                          name="<?= esc_attr($input_name) ?>"
                          value="<?= esc_attr($choice['value']) ?>"
                          autocomplete="<?= esc_attr($autocomplete) ?>" />
                        <?= esc_html($choice['text']) ?>
                      </label>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <input
                      type="text"
                      name="<?= esc_attr($input_name) ?>"
                      id="<?= esc_attr($input_name) ?>"
                      autocomplete="<?= esc_attr($autocomplete) ?>" />
                  <?php endif; ?>
                </div>

              <?php endforeach; ?>
            </div>
            <?php break; ?>


          <?php
          case 'textarea': ?>
            <textarea
              name="input_<?= $field->id ?>"
              id="input_<?= $field->id ?>"></textarea>
            <?php break; ?>

          <?php
          case 'select': ?>
            <select name="input_<?= $field->id ?>" id="input_<?= $field->id ?>">
              <?php foreach ($field->choices as $choice): ?>
                <option value="<?= esc_attr($choice['value']) ?>">
                  <?= esc_html($choice['text']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php break; ?>

          <?php
          case 'radio': ?>
            <?php foreach ($field->choices as $index => $choice): ?>
              <label>
                <input
                  type="radio"
                  name="input_<?= $field->id ?>"
                  value="<?= esc_attr($choice['value']) ?>" />
                <?= esc_html($choice['text']) ?>
              </label>
            <?php endforeach; ?>
            <?php break; ?>

          <?php
          case 'checkbox': ?>
            <?php foreach ($field->choices as $index => $choice): ?>
              <label>
                <input
                  type="checkbox"
                  name="input_<?= $field->id ?>[]"
                  value="<?= esc_attr($choice['value']) ?>" />
                <?= esc_html($choice['text']) ?>
              </label>
            <?php endforeach; ?>
            <?php break; ?>

          <?php
          case 'date': ?>
            <input
              type="date"
              name="input_<?= $field->id ?>"
              id="input_<?= $field->id ?>" />
            <?php break; ?>

          <?php
          case 'file': ?>
            <input
              type="file"
              name="input_<?= $field->id ?>"
              id="input_<?= $field->id ?>" />
            <?php break; ?>

          <?php
          default: ?>
            <p>Unsupported field type: <?= esc_html($field->type) ?></p>
        <?php endswitch; ?>
      </div>
    <?php endforeach; ?>

    <button type="submit">Register</button>
  </form>
<?php else: ?>
  <p>Form not found.</p>
<?php endif; ?>