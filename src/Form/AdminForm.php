<?php

namespace Drupal\islandora_solr_metadata\Form;

use Drupal\islandora_solr_metadata\Config\FieldConfigInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for solr metadata.
 */
class AdminForm extends ConfigFormBase {

  /**
   * Solr metadata field configuration object.
   *
   * @var \Drupal\islandora_solr_metadata\Config\FieldConfigInterface
   */
  protected $fieldConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('islandora_solr_metadata.field_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config, FieldConfigInterface $field_config) {
    $this->config = $config;
    $this->fieldConfig = $field_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'islandora_solr_metadata.configs',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_solr_metadata', 'inc', 'includes/db');

    $associations = $this->config('islandora_solr_metadata.configs')->get('configs');
    $rows = [];
    foreach ($associations as $association => $info) {
      $associated_cmodels = $info['cmodel_associations'];
      if (empty($associated_cmodels)) {
        $associated_cmodels = [
          '#type' => 'item',
          '#markup' => $this->t('No content models currently associated.'),
        ];
      }
      else {
        $associated_cmodels = [
          '#theme' => 'item_list',
          '#items' => $associated_cmodels,
        ];
      }
      $rows[] = [
        [
          '#type' => 'link',
          '#title' => $info['label'],
          '#url' => Url::fromRoute('islandora_solr_metadata.config', [
            'configuration_name' => $association,
          ]),
        ],
        $associated_cmodels,
        [
          '#markup' => $association,
        ],
      ];
    }
    $form['table'] = [
      '#title' => $this->t('Solr metadata associations'),
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Associated content models'),
        $this->t('Machine name'),
      ],
      '#empty' => $this->t('No associations currently present.'),
    ] + $rows;

    $form['add_configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add a configuration'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['add_configuration']['configuration_name'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Configuration name'),
      '#required' => TRUE,
      '#description' => $this->t('A human-readable name for a new configuration.'),
    ];
    $form['add_configuration']['machine_name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [
          $this,
          'configMachineNameExists',
        ],
        'source' => [
          'add_configuration',
          'configuration_name',
        ],
      ],
      '#required' => TRUE,
    ];
    $form['add_configuration']['add_config'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add configuration'),
    ];
    return $form;
  }

  /**
   * Check for the existence of a config with the given machine name.
   *
   * As per #machine_name['exists'].
   */
  public function configMachineNameExists($value, array $element, FormStateInterface $form_state) {
    return islandora_solr_metadata_configuration_exists($value);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('configuration_name'))) {
      $form_state->setErrorByName('configuration_name', $this->t('Please enter a non-empty configuration name!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $machine_name = $form_state->getValue('machine_name');
    $configuration_name = $form_state->getValue('configuration_name');

    $this->config('islandora_solr_metadata.configs')
      ->set("configs.$machine_name", $this->fieldConfig->getEmptyConfig())
      ->set("configs.$machine_name.label", $configuration_name)
      ->save();

    drupal_set_message($this->t('A new empty configuration has been created for @configuration_name (@machine_name).', [
      '@configuration_name' => $configuration_name,
      '@machine_name' => $machine_name,
    ]));
  }

}
