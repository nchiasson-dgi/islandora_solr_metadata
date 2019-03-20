<?php

/**
 * @file
 * Houses the form and utilities used in islandora_solr_metadata configurations.
 */

use Drupal\Core\Url;
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
    foreach ($data as $field_name => $field) {
      $rows[] = [
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
          '#markup' => Xss::filter($field_name),
        ],
        'label' => [
          '#type' => 'item',
          '#markup' => empty($field['display_label']) ? Xss::filter($field_name) : Xss::filter($field['display_label']),
        ],
        'operations' => [
          'edit' => [
            '#access' => isset($field['ajax-volatile']) ? !$field['ajax-volatile'] : TRUE,
            '#type' => 'link',
            '#url' => Url::fromRoute('islandora_solr_metadata.config_field', ['config_name' => $field['configuration_name'], 'escaped_field_name' => islandora_solr_replace_slashes($field_name)]),
            '#title' => t('edit'),
          ],
        ],
        'weight' => [
          '#type' => 'weight',
          '#delta' => $max_delta,
          '#default_value' => $field['weight'],
          '#attributes' => [
            'class' => [
              'islandora-solr-metadata-weight',
            ],
          ],
        ],
      ];
    }
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
