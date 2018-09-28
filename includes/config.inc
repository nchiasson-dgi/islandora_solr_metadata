<?php

/**
 * @file
 * Houses the form and utilities used in islandora_solr_metadata configurations.
 */

use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;

/**
 * AJAX callback for the fields portion of the configuration form.
 *
 * @param array $form
 *   An array representing a Drupal form.
 * @param Drupal\Core\Form\FormStateInterface $form_state
 *   An array representing the Drupal form state.
 *
 * @return array
 *   An array representing the portion of the form we are rendering.
 */
function islandora_solr_metadata_fields_ajax(array &$form, FormStateInterface &$form_state) {
  return $form['islandora_solr_metadata_fields']['table_wrapper'];
}

/**
 * AJAX callback for the content models portion of the configuration form.
 *
 * @param array $form
 *   An array representing a Drupal form.
 * @param Drupal\Core\Form\FormStateInterface $form_state
 *   An array representing the Drupal form state.
 *
 * @return array
 *   An array representing the portion of the form we are rendering.
 */
function islandora_solr_metadata_cmodels_ajax(array &$form, FormStateInterface &$form_state) {
  return $form['islandora_solr_metadata_cmodels']['table_wrapper'];
}

/**
 * Helper function to construct the fields tableselect.
 *
 * @param array $data
 *   An array of data representing rows to be rendered in the tableselect.
 *
 * @return array
 *   An array representing the tableselect to be rendered.
 */
function islandora_solr_metadata_management(array $data) {
  module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
  module_load_include('inc', 'islandora_solr', 'includes/utilities');
  $form = [];
  $rows = [];
  if ($data) {
    uasort($data, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $max_delta = max(count($data), 10);
    $weight = 0;
    $map = function ($field) use ($max_delta, &$weight) {
      $weight++;
      return [
        '#attributes' => [
          'class' => [
            'draggable',
          ],
        ],
        'remove_field' => [
          '#type' => 'checkbox',
          '#default_value' => isset($field['remove']) ? $field['remove'] : FALSE,
        ],
        'solr_field_markup' => [
          '#type' => 'item',
          '#markup' => Xss::filter($field['solr_field']),
        ],
        'label' => [
          '#type' => 'item',
          '#markup' => empty($field['display_label']) ? Xss::filter($field['solr_field']) : Xss::filter($field['display_label']),
        ],
        'operations' => [
          'edit' => [
            '#access' => isset($field['ajax-volatile']) ? !$field['ajax-volatile'] : TRUE,
            '#type' => 'link',
            '#url' => Url::fromRoute('islandora_solr_metadata.config_2', ['config_id' => $field['configuration_id'], 'escaped_field_name' => islandora_solr_replace_slashes($field['solr_field'])]),
            '#title' => t('edit'),
          ],
        ],
        'weight' => [
          '#type' => 'weight',
          '#delta' => $max_delta,
          '#default_value' => $weight,
          '#attributes' => [
            'class' => [
              'islandora-solr-metadata-weight',
            ],
          ],
        ],
      ];
    };
    $rows = array_map($map, $data);
  }
  // Add the table to the form.
  $form['#tree'] = TRUE;
  $form['table'] = [
    '#type' => 'table',
    '#header' => [
      t('Remove'),
      t('Solr Field'),
      t('Label'),
      t('Operations'),
      t('Weight'),
    ],
    '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'islandora-solr-metadata-weight',
      ],
    ],
    '#empty' => t('No fields associated'),
  ] + $rows;
  return $form;
}

/**
 * Form building function; allow configuration of a particular field.
 */
function islandora_solr_metadata_config_field_form($form, &$form_state, $config_id, $escaped_field_name) {
  $form_state->loadInclude('islandora_solr', 'inc', 'includes/utilities');
  $field_name = islandora_solr_restore_slashes($escaped_field_name);
  $get_default = function ($value, $default = '') use ($config_id, $field_name) {
    static $field_info = NULL;
    if ($field_info === NULL) {
      $fields = islandora_solr_metadata_get_fields($config_id);
      $field_info = $fields[$field_name];
    }
    $exists = FALSE;
    $looked_up = NestedArray::getValue($field_info, (array) $value, $exists);
    return $exists ? $looked_up : $default;
  };

  $form['#tree'] = TRUE;
  $form['wrapper'] = [
    '#type' => 'fieldset',
    '#title' => t('Field config'),
  ];

  $set =& $form['wrapper'];
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
      '#description' => t('The format of the date, as it will be displayed in the search results. Use <a href="!url" target="_blank">PHP date()</a> formatting. Works best when the date format matches the granularity of the source data. Otherwise it is possible that there will be duplicates displayed.', ['!url' => 'http://php.net/manual/function.date.php']),
    ];
  }
  // Add in truncation fields for metadata field.
  $truncation_config = [
    'default_values' => [
      'truncation_type' => $get_default(['truncation', 'truncation_type'], 'separate_value_option'),
      'max_length' => $get_default(['truncation', 'max_length'], 0),
      'word_safe' => $get_default(['truncation', 'word_safe'], FALSE),
      'ellipsis' => $get_default(['truncation', 'ellipsis'], FALSE),
      'min_wordsafe_length' => $get_default(['truncation', 'min_wordsafe_length'], 1),
    ],
    'min_wordsafe_length_input_path' => "wrapper[truncation][word_safe]",
  ];
  islandora_solr_metadata_add_truncation_to_form($set, $truncation_config);
  $permissions = $get_default(
    ['permissions'],
    [
      'enable_permissions' => FALSE,
      'permissions' => [],
    ]
  );
  islandora_solr_metadata_append_permissions_and_actions($permissions, $set);

  $form['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save field configuration'),
  ];
  return $form;
}

/**
 * Generate the element definition for the permission fieldset.
 *
 * @param array $permissions
 *   The selected permissions array containing
 *   key values:
 *     'enable_permissions' => bool (enable/disable permissions),
 *     'permissions' => array (an array of permissions to apply)
 * @param array $permissions_default
 *   The permissions to select by default.
 * @param array $permissions_disable
 *   Some permissions which we will not allow to be changed (set as disabled in
 *   the form).
 *
 * @return array
 *   An associative array containing the definition for the permissions
 *   fieldset.
 */
function islandora_solr_metadata_get_admin_permissions_fieldset(array $permissions, array $permissions_default, array $permissions_disable) {
  $permissions_fieldset = [
    '#type' => 'fieldset',
    '#title' => t('Permissions'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    'enable_permissions' => [
      '#type' => 'checkbox',
      '#title' => t('Enable permissions'),
      '#default_value' => $permissions['enable_permissions'],
    ],
    'permissions' => [
      '#type' => 'checkboxes',
      '#title' => t('Permissions'),
      '#options' => user_role_names(),
      '#default_value' => !empty($permissions['permissions']) ? $permissions['permissions'] : $permissions_default,
      '#description' => t('Select which roles can access this field.<br /><strong>Note:</strong> checkboxes may be grayed out for roles which do not have permission to search the Solr index.'),
      '#states' => [
        'visible' => [
          ':input[name="wrapper[permissions][enable_permissions]"]' => ['checked' => TRUE],
        ],
      ],
    ],
  ];
  foreach ($permissions_disable as $rid) {
    $permissions_fieldset['permissions'][$rid] = [
      '#disabled' => TRUE,
    ];
  }

  return $permissions_fieldset;
}

/**
 * Utility function to append permissions and actions to the modal.
 *
 * @param array $permissions
 *   The selected permissions array containing
 *   key values:
 *     'enable_permissions' => bool (enable/disable permissions),
 *     'permissions' => array (an array of permissions to apply)
 * @param array $form
 *   An array representing the Drupal form, passed by reference.
 */
function islandora_solr_metadata_append_permissions_and_actions(array $permissions, array &$form) {
  module_load_include('inc', 'islandora_solr', 'includes/admin');
  $permissions_disable = _islandora_solr_permissions_disable();
  $permissions_default = _islandora_solr_permissions_default();
  $form['permissions'] = islandora_solr_metadata_get_admin_permissions_fieldset($permissions, $permissions_default, $permissions_disable);
}

/**
 * Form validation handler; validate some values.
 */
function islandora_solr_metadata_config_field_form_validate(&$form, &$form_state) {
  if ($form_state['values']['wrapper']['hyperlink'] && $form_state['values']['wrapper']['truncation']['max_length'] > 0) {
    form_error($form['wrapper']['hyperlink'], t('Either hyperlinking or truncation can be used, but not both together on the same field. Disable one.'));
    form_error($form['wrapper']['truncation']['max_length']);
  }
}

/**
 * Form submission handler; save the configuration of the field.
 */
function islandora_solr_metadata_config_field_form_submit(array &$form, FormStateInterface &$form_state) {
  list($config_id, $escaped_field_name) = $form_state['build_info']['args'];
  $field_name = islandora_solr_restore_slashes($escaped_field_name);

  $fields = islandora_solr_metadata_get_fields($config_id);
  $field_info = $fields[$field_name];

  $field_info = $form_state['values']['wrapper'] + $field_info;
  islandora_solr_metadata_update_fields($config_id, [$field_info]);

  $form_state['redirect'] = [
    "admin/islandora/search/islandora_solr_metadata/config/$config_id",
  ];
}

/**
 * Generates the truncation field settings section to attach to a form.
 *
 * @param array $form
 *   The location in the form that the truncation should be appended to.
 * @param array $field_config
 *   An associative array containing the following:
 *   - default_values
 *      - truncation_type
 *      - max_length
 *      - word_safe
 *      - ellipsis
 *      - min_wordsafe_length
 *   - min_wordsafe_length_input_path.
 */
function islandora_solr_metadata_add_truncation_to_form(array &$form, array $field_config) {
  $form['truncation'] = [
    '#type' => 'fieldset',
    '#title' => t('Truncation'),
    'truncation_type' => [
      '#type' => 'radios',
      '#title' => t('Truncation Type'),
      '#options' => [
        'separate_value_option' => t('Limit length of each separate value'),
        'whole_field_option' => t('Limit Length of the whole field'),
      ],
      '#default_value' => $field_config['default_values']['truncation_type'],
    ],
    'max_length' => [
      '#type' => 'number',
      '#title' => t('Max Length'),
      '#description' => t('The field contents will be truncated to be at most this length (in characters) for display. 0 or less to disable truncation.<br /> When truncating based on the whole field the max length may be exceeded by the length of ellispse string.'),
      '#default_value' => $field_config['default_values']['max_length'],
    ],
    'word_safe' => [
      '#type' => 'checkbox',
      '#title' => t('Word-safe'),
      '#description' => t('Attempt to truncate on a word boundary. See truncate_utf8() for more info.'),
      '#default_value' => $field_config['default_values']['word_safe'],
    ],
    'ellipsis' => [
      '#type' => 'checkbox',
      '#title' => t('Ellipsis'),
      '#description' => t('Append ellipses when truncating occurs.'),
      '#default_value' => $field_config['default_values']['ellipsis'],
    ],
    'min_wordsafe_length' => [
      '#type' => 'number',
      '#title' => t('Minimum word-safe length'),
      '#default_value' => $field_config['default_values']['min_wordsafe_length'],
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="' . $field_config['min_wordsafe_length_input_path'] . '"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ],
  ];
}
