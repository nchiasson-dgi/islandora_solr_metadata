<?php

namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\DrupalKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Configuration form for solr metadata.
 */
class IslandoraSolrMetadataConfigForm extends FormBase {

  /**
   * Kernel object for dependency injection.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('kernel')
    );
  }

  /**
   * Constructor for dependency injection.
   */
  public function __construct(DrupalKernel $kernel) {
    $this->kernel = $kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $configuration_id = NULL) {
    $form_state->loadInclude('islandora', 'inc', 'includes/content_model.autocomplete');
    $form_state->loadInclude('islandora_solr_metadata', 'inc', 'includes/db');
    $form_state->loadInclude('islandora_solr_metadata', 'inc', 'includes/config');
    $cmodel_to_add = FALSE;

    if (NULL === $form_state->get(['field_data'])) {
      $form_state->set(['field_data'], islandora_solr_metadata_get_fields($configuration_id, FALSE));
    }

    // AJAX callback handling.
    if (NULL !== $form_state->getTriggeringElement()['#name']) {
      if ($form_state->getTriggeringElement()['#name'] == 'islandora-solr-metadata-add-field') {
        $field_name = $form_state->getValue([
          'islandora_solr_metadata_fields',
          'table_wrapper',
          'add_fieldset',
          'available_solr_fields',
        ]);
        $form_state->set(['field_data', $field_name], [
          'configuration_id' => $configuration_id,
          'solr_field' => $field_name,
          'display_label' => $field_name,
          // Arbitrary large sort weight so it always comes last.
          'weight' => 10000,
          'ajax-volatile' => TRUE,
        ]);
      }

      if ($form_state->getTriggeringElement()['#name'] == 'islandora-solr-metadata-fields-remove-selected') {
        $to_remove = function ($row) {
          return $row['remove_field'];
        };
        $form_state->set(['field_data'], array_diff_key($form_state->get([
          'field_data',
        ]), array_filter($form_state->getValue([
          'islandora_solr_metadata_fields',
          'table_wrapper',
          'table',
          'table',
        ]), $to_remove)));
      }
      if ($form_state->getTriggeringElement()['#name'] == 'islandora-solr-metadata-cmodels-add-cmodel') {
        $cmodel_to_add = [
          'cmodel' => $form_state->getValue([
            'islandora_solr_metadata_cmodels',
            'table_wrapper',
            'cmodel_options',
            'cmodel_select',
          ]),
        ];
      }
      if ($form_state->getTriggeringElement()['#name'] == 'islandora-solr-metadata-cmodels-remove-selected') {
        foreach ($form_state->getValue([
          'islandora_solr_metadata_cmodels',
          'table_wrapper',
          'table',
        ]) as $key => $row) {
          if ($row !== 0) {
            unset($form_state->getCompleteForm()['islandora_solr_metadata_cmodels']['table_wrapper']['table']['#options'][$key]);
          }
        }
      }
    }
    $form = ['#tree' => TRUE];
    $form['islandora_solr_metadata_configuration_id'] = [
      '#type' => 'value',
      '#value' => $configuration_id,
    ];

    $form['islandora_solr_metadata_cmodels'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content Models'),
      '#collapsed' => TRUE,
      '#collapsible' => FALSE,
    ];
    $form['islandora_solr_metadata_cmodels']['table_wrapper'] = [
      '#prefix' => '<div id="islandora-solr-metadata-cmodels-wrapper">',
      '#suffix' => '</div>',
    ];
    // If there are values in the form_state use them for persistence in case of
    // AJAX callbacks, otherwise grab fresh values from the database.
    if (!empty($form_state->getValues())) {
      if (NULL !== $form_state->getValue([
        'islandora_solr_metadata_cmodels',
        'table_wrapper',
        'table',
      ])) {
        $cmodels_associated = $form_state->getCompleteForm()['islandora_solr_metadata_cmodels']['table_wrapper']['table']['#options'];
      }
    }
    else {
      $cmodels_associated = islandora_solr_metadata_get_cmodels($configuration_id);
    }

    if ($cmodel_to_add !== FALSE) {
      $cmodels_associated[$cmodel_to_add['cmodel']] = $cmodel_to_add;
    }

    $form['islandora_solr_metadata_cmodels']['table_wrapper']['table'] = [
      '#type' => 'tableselect',
      '#header' => [
        'cmodel' => [
          'data' => $this->t('Content Model Name'),
        ],
      ],
      '#options' => $cmodels_associated,
      '#empty' => $this->t('No content models associated.'),
    ];

    if (count($cmodels_associated)) {
      $form['islandora_solr_metadata_cmodels']['table_wrapper']['remove_selected'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove selected'),
        '#name' => 'islandora-solr-metadata-cmodels-remove-selected',
        '#ajax' => [
          'callback' => 'islandora_solr_metadata_cmodels_ajax',
          'wrapper' => 'islandora-solr-metadata-cmodels-wrapper',
        ],
      ];
    }

    // Retrieve all content models and unset those currently in use in this
    // configuration and any others from other configurations.
    $add_options = islandora_get_content_model_names();
    foreach ($cmodels_associated as $entry) {
      unset($add_options[$entry['cmodel']]);
    }

    if (!empty($add_options)) {
      $form['islandora_solr_metadata_cmodels']['table_wrapper']['cmodel_options'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Add content model'),
        '#collapsed' => TRUE,
        '#collapsible' => FALSE,
      ];

      $form['islandora_solr_metadata_cmodels']['table_wrapper']['cmodel_options']['cmodel_select'] = [
        '#type' => 'select',
        '#options' => $add_options,
      ];
      $form['islandora_solr_metadata_cmodels']['table_wrapper']['cmodel_options']['cmodel_add'] = [
        '#type' => 'button',
        '#value' => $this->t('Add'),
        '#name' => 'islandora-solr-metadata-cmodels-add-cmodel',
        '#ajax' => [
          'callback' => 'islandora_solr_metadata_cmodels_ajax',
          'wrapper' => 'islandora-solr-metadata-cmodels-wrapper',
        ],
      ];
    }

    $form['islandora_solr_metadata_fields'] = [
      '#type' => 'fieldset',
      '#title' => 'Display fields',
      '#collapsed' => TRUE,
      '#collapsible' => FALSE,
    ];

    $form['islandora_solr_metadata_fields']['table_wrapper'] = [
      '#prefix' => '<div id="islandora-solr-metadata-fields-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['islandora_solr_metadata_fields']['table_wrapper']['table'] = islandora_solr_metadata_management($form_state->get([
      'field_data',
    ]));
    if (count($form_state->get(['field_data']))) {
      $form['islandora_solr_metadata_fields']['table_wrapper']['remove_selected'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove selected'),
        '#name' => 'islandora-solr-metadata-fields-remove-selected',
        '#ajax' => [
          'callback' => 'islandora_solr_metadata_fields_ajax',
          'wrapper' => 'islandora-solr-metadata-fields-wrapper',
        ],
      ];
    }
    $form['islandora_solr_metadata_fields']['table_wrapper']['add_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add field'),
      '#collapsed' => TRUE,
      '#collapsible' => FALSE,
    ];
    $form['islandora_solr_metadata_fields']['table_wrapper']['add_fieldset']['available_solr_fields'] = [
      '#type' => 'textfield',
      '#description' => $this->t('A field from within Solr'),
      '#size' => 105,
      '#autocomplete_route_name' => 'islandora_solr.autocomplete_luke',
      '#default_value' => '',
    ];
    $form['islandora_solr_metadata_fields']['table_wrapper']['add_fieldset']['add_field'] = [
      '#type' => 'button',
      '#value' => $this->t('Add'),
      '#name' => 'islandora-solr-metadata-add-field',
      '#ajax' => [
        'callback' => 'islandora_solr_metadata_fields_ajax',
        'wrapper' => 'islandora-solr-metadata-fields-wrapper',
      ],
    ];
    $form['islandora_solr_metadata_fields']['description_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Description'),
      '#description' => $this->t("The field used for rendering the description beneath the object's content"),
      '#collapsed' => TRUE,
      '#collapsible' => FALSE,
    ];
    $description = islandora_solr_metadata_retrieve_description($configuration_id, FALSE);
    $form['islandora_solr_metadata_fields']['description_fieldset']['available_solr_fields'] = [
      '#type' => 'textfield',
      '#description' => $this->t('A field from within Solr'),
      '#size' => 105,
      '#autocomplete_route_name' => 'islandora_solr.autocomplete_luke',
      '#default_value' => $description['description_field'],
    ];
    $form['islandora_solr_metadata_fields']['description_fieldset']['display_label'] = [
      '#type' => 'textfield',
      '#description' => $this->t('A label for displaying'),
      '#size' => 45,
      '#default_value' => $description['description_label'],
      '#states' => [
        'visible' => [
          ':input[name="islandora_solr_metadata_fields[description_fieldset][available_solr_fields]"]' => [
            'empty' => FALSE,
          ],
        ],
      ],
    ];

    // Add in truncation fields for description.
    $truncation_config = [
      'default_values' => [
        'truncation_type' => $description['description_data']['truncation']['truncation_type'] ? $description['description_data']['truncation']['truncation_type'] : 'separate_value_option',
        'max_length' => $description['description_data']['truncation']['max_length'] ? $description['description_data']['truncation']['max_length'] : 0,
        'word_safe' => $description['description_data']['truncation']['word_safe'] ? $description['description_data']['truncation']['word_safe'] : FALSE,
        'ellipsis' => $description['description_data']['truncation']['ellipsis'] ? $description['description_data']['truncation']['ellipsis'] : FALSE,
        'min_wordsafe_length' => $description['description_data']['truncation']['min_wordsafe_length'] ? $description['description_data']['truncation']['min_wordsafe_length'] : 1,
      ],
      'min_wordsafe_length_input_path' => "islandora_solr_metadata_fields[description_fieldset][truncation][word_safe]",
    ];
    islandora_solr_metadata_add_truncation_to_form($form['islandora_solr_metadata_fields']['description_fieldset'], $truncation_config);

    $form['islandora_solr_metadata_save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#weight' => 10,
    ];
    $form['islandora_solr_metadata_delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete configuration'),
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement() == 'islandora-solr-metadata-add-field') {
      module_load_include('inc', 'islandora_solr', 'includes/luke');
      $solr_luke = islandora_solr_get_luke();
      $luke_fields = array_keys($solr_luke['fields']);

      if (!in_array($form_state->getValue([
        'islandora_solr_metadata_fields',
        'table_wrapper',
        'add_fieldset',
        'available_solr_fields',
      ]), $luke_fields)) {
        $form_state->setErrorByName('islandora_solr_metadata_fields][table_wrapper][add_fieldset][available_solr_fields', $this->t('The field @field is not a valid field within Solr!', [
          '@field' => $form_state->getValue([
            'islandora_solr_metadata_fields',
            'table_wrapper',
            'add_fieldset',
            'available_solr_fields',
          ]),
        ]));
      }
      else {
        $added_values = NULL !== $form_state->getValue([
          'islandora_solr_metadata_fields',
          'table_wrapper',
          'table',
          'table',
        ]) ?
          array_keys($form_state->getValue([
            'islandora_solr_metadata_fields',
            'table_wrapper',
            'table',
            'table',
          ])) :
          [];

        if (in_array($form_state->getValue([
          'islandora_solr_metadata_fields',
          'table_wrapper',
          'add_fieldset',
          'available_solr_fields',
        ]), $added_values)) {
          $form_state->setErrorByName('islandora_solr_metadata_fields][table_wrapper][add_fieldset][available_solr_fields', $this->t('The field @field already exists in this configuration!', [
            '@field' => $form_state->getValue([
              'islandora_solr_metadata_fields',
              'table_wrapper',
              'add_fieldset',
              'available_solr_fields',
            ]),
          ]));
        }
      }
    }

    if ($form_state->getTriggeringElement()['#name'] == 'islandora-solr-metadata-fields-remove-selected') {
      $rows_to_remove = [];
      foreach ($form_state->getValue([
        'islandora_solr_metadata_fields',
        'table_wrapper',
        'table',
        'table',
      ]) as $key => $row) {
        if ($row['remove_field'] == TRUE) {
          $rows_to_remove[] = $key;
        }
      }
      if (count($rows_to_remove) === 0) {
        $form_state->setErrorByName('islandora_solr_metadata', $this->t('Must select at least one entry to remove!'));
      }
    }

    if ($form_state->getTriggeringElement()['#name'] == 'islandora-solr-metadata-cmodels-remove-selected') {
      $rows_to_remove = [];
      foreach ($form_state->getValue([
        'islandora_solr_metadata_cmodels',
        'table_wrapper',
        'table',
      ]) as $key => $row) {
        if ($row !== 0) {
          $rows_to_remove[] = $key;
        }
      }
      if (count($rows_to_remove) === 0) {
        $form_state->setErrorByName('islandora_solr_metadata', $this->t('Must select at least one entry to remove!'));
      }
    }

    if ($form_state->getTriggeringElement()['#name'] == 'Save configuration') {
      $solr_field = $form_state->getValue([
        'islandora_solr_metadata_fields',
        'description_fieldset',
        'available_solr_fields',
      ]);
      if (!empty($solr_field)) {
        module_load_include('inc', 'islandora_solr', 'includes/luke');
        $solr_luke = islandora_solr_get_luke();
        $luke_fields = array_keys($solr_luke['fields']);
        if (!in_array($solr_field, $luke_fields)) {
          $form_state->setErrorByName('islandora_solr_metadata_fields][description_fieldset][available_solr_fields', $this->t('The field @field is not a valid field within Solr!', [
            '@field' => $solr_field,
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
    $configuration_id = $form_state->getValue(['islandora_solr_metadata_configuration_id']);
    if ($form_state->getTriggeringElement()['#value'] == 'Save configuration') {
      // Grab existing entries first for comparison.
      $remove_form_specifics = function ($field) {
        return array_diff_key($field, array_combine([
          'ajax-volatile',
          'remove',
          'remove_field',
        ], [
          'ajax-volatile',
          'remove',
          'remove_field',
        ]));
      };
      $form_state_row_values = $form_state->getValue([
        'islandora_solr_metadata_fields',
        'table_wrapper',
        'table',
        'table',
      ]);
      $rows = (NULL !== $form_state_row_values) ? $form_state_row_values : [];
      $fields_fs_mapped = array_map($remove_form_specifics, NestedArray::mergeDeep($form_state->get(['field_data']), $rows));
      $fields_db = islandora_solr_metadata_get_fields($configuration_id);

      $cmodels_db = islandora_solr_metadata_get_cmodels($configuration_id);
      $cmodels_fs = $form_state->getCompleteForm()['islandora_solr_metadata_cmodels']['table_wrapper']['table']['#options'];
      $cmodels_deletion = array_diff_key($cmodels_db, $cmodels_fs);
      $cmodels_insertion = array_diff_key($cmodels_fs, $cmodels_db);

      // Handle insertion and deletion of content models first.
      if (count($cmodels_insertion)) {
        islandora_solr_metadata_add_content_models($configuration_id, $cmodels_insertion);
      }
      if (count($cmodels_deletion)) {
        islandora_solr_metadata_delete_content_models($configuration_id, $cmodels_deletion);
      }

      $fields_deletion = array_diff_key($fields_db, $fields_fs_mapped);
      $fields_insertion = array_diff_key($fields_fs_mapped, $fields_db);

      if (count($fields_insertion)) {
        islandora_solr_metadata_add_fields($configuration_id, $fields_insertion);
      }
      if (count($fields_deletion)) {
        islandora_solr_metadata_delete_fields($configuration_id, $fields_deletion);
      }

      $fields_update = array_intersect_key($fields_fs_mapped, $fields_db);
      if (count($fields_update)) {
        islandora_solr_metadata_update_fields($configuration_id, $fields_update);
      }

      $description_field = $form_state->getValue([
        'islandora_solr_metadata_fields',
        'description_fieldset',
        'available_solr_fields',
      ]);
      $description_label = $form_state->getValue([
        'islandora_solr_metadata_fields',
        'description_fieldset',
        'display_label',
      ]);
      $truncation_array['truncation'] = [
        'truncation_type' => $form_state->getValue([
          'islandora_solr_metadata_fields',
          'description_fieldset',
          'truncation',
          'truncation_type',
        ]),
        'max_length' => $form_state->getValue([
          'islandora_solr_metadata_fields',
          'description_fieldset',
          'truncation',
          'max_length',
        ]),
        'word_safe' => $form_state->getValue([
          'islandora_solr_metadata_fields',
          'description_fieldset',
          'truncation',
          'word_safe',
        ]),
        'ellipsis' => $form_state->getValue([
          'islandora_solr_metadata_fields',
          'description_fieldset',
          'truncation',
          'ellipsis',
        ]),
        'min_wordsafe_length' => $form_state->getValue([
          'islandora_solr_metadata_fields',
          'description_fieldset',
          'truncation',
          'min_wordsafe_length',
        ]),
      ];
      islandora_solr_metadata_update_description($configuration_id, $description_field, $description_label, $truncation_array);
      drupal_set_message($this->t('The Solr metadata display configuration options have been saved.'));
    }

    if ($form_state->getTriggeringElement()['#value'] == 'Delete configuration') {
      $url = Url::fromRoute('islandora_solr_metadata.config_delete', ['configuration_id' => $configuration_id]);
      $response = new RedirectResponse($url->toString());
      $request = $this->getRequest();
      // Save the session so things like messages get saved.
      $request->getSession()->save();
      $response->prepare($request);
      // Make sure to trigger kernel events.
      $this->kernel->terminate($request, $response);
      $response->send();
    }
  }

}
