<?php
namespace Drupal\islandora_solr_metadata;

class IslandoraSolrMetadataConfigFieldForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_config_field_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $config_id = NULL, $escaped_field_name = NULL) {
    $form_state->loadInclude('islandora_solr', 'inc', 'includes/utilities');
    $field_name = islandora_solr_restore_slashes($escaped_field_name);
    $get_default = function ($value, $default = '') use ($config_id, $field_name) {
      static $field_info = NULL;
      if ($field_info === NULL) {
        $fields = islandora_solr_metadata_get_fields($config_id);
        $field_info = $fields[$field_name];
      }
      $exists = FALSE;
      $looked_up = \Drupal\Component\Utility\NestedArray::getValue($field_info, (array) $value, $exists);
      return $exists ? $looked_up : $default;
    };

    $form['#tree'] = TRUE;
    $form['wrapper'] = [
      '#type' => 'fieldset',
      '#title' => t('Field config'),
    ];

    $set = & $form['wrapper'];
    $set['display_label'] = [
      '#type' => 'textfield',
      '#title' => t('Display Label'),
      '#description' => t('A human-readable label to display alongside values found for this field.'),
      '#default_value' => $get_default('display_label', $field_name),
    ];
    $set['hyperlink'] = [
      '#type' => 'checkbox',
      '#title' => t('Hyperlink?'),
      '#description' => t('Should each value for this field be linked to a search to find objects with the value in this field?'),
      '#default_value' => $get_default('hyperlink', FALSE),
    ];
    $set['uri_replacement'] = [
      '#type' => 'textfield',
      '#title' => t('URI/PID Replacement Field'),
      '#description' => t('If the value of this field represents a Fedora URI or PID, a Solr field can be specified to replace that value, e.g., with the object label instead of the full URI.'),
      '#default_value' => $get_default('uri_replacement', ''),
      '#autocomplete_path' => 'islandora_solr/autocomplete_luke',
    ];
    if (islandora_solr_is_date_field($field_name)) {
      $set['date_format'] = [
        '#type' => 'textfield',
        '#title' => t('Date format'),
        '#default_value' => $get_default('date_format', ''),
        '#description' => t('The format of the date, as it will be displayed in the search results. Use <a href="!url" target="_blank">PHP date()</a> formatting. Works best when the date format matches the granularity of the source data. Otherwise it is possible that there will be duplicates displayed.', [
          '!url' => 'http://php.net/manual/function.date.php'
          ]),
      ];
    }
    // Add in truncation fields for metadata field.
    $truncation_config = [
      'default_values' => [
        'truncation_type' => $get_default([
          'truncation',
          'truncation_type',
        ], 'separate_value_option'),
        'max_length' => $get_default([
          'truncation',
          'max_length',
        ], 0),
        'word_safe' => $get_default(['truncation', 'word_safe'], FALSE),
        'ellipsis' => $get_default([
          'truncation',
          'ellipsis',
        ], FALSE),
        'min_wordsafe_length' => $get_default([
          'truncation',
          'min_wordsafe_length',
        ], 1),
      ],
      'min_wordsafe_length_input_path' => "wrapper[truncation][word_safe]",
    ];
    islandora_solr_metadata_add_truncation_to_form($set, $truncation_config);
    $permissions = $get_default(['permissions'], [
      'enable_permissions' => FALSE,
      'permissions' => [],
    ]);
    islandora_solr_metadata_append_permissions_and_actions($permissions, $set);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save field configuration'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['wrapper', 'hyperlink']) && $form_state->getValue([
      'wrapper',
      'truncation',
      'max_length',
    ]) > 0) {
      $form_state->setError($form['wrapper']['hyperlink'], t('Either hyperlinking or truncation can be used, but not both together on the same field. Disable one.'));
      $form_state->setError($form['wrapper']['truncation']['max_length']);
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    list($config_id, $escaped_field_name) = $form_state->getBuildInfo();
    $field_name = islandora_solr_restore_slashes($escaped_field_name);

    $fields = islandora_solr_metadata_get_fields($config_id);
    $field_info = $fields[$field_name];

    $field_info = $form_state->getValue(['wrapper']) + $field_info;
    islandora_solr_metadata_update_fields($config_id, [$field_info]);

    $form_state->set(['redirect'], [
      "admin/islandora/search/islandora_solr_metadata/config/$config_id"
      ]);
  }

}
