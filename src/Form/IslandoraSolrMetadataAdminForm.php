<?php

namespace Drupal\islandora_solr_metadata\Form;

use Drupal\islandora_solr_metadata\Config\IslandoraSolrMetadataFieldConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for solr metadata.
 */
class IslandoraSolrMetadataAdminForm extends ConfigFormBase {

  /**
   * Solr metadata field configuration object.
   *
   * @var \Drupal\islandora_solr_metadata\Config\IslandoraSolrMetadataFieldConfig
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
  public function __construct(ConfigFactoryInterface $config, IslandoraSolrMetadataFieldConfig $field_config) {
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
    $associations = array_keys($this->config('islandora_solr_metadata.configs')->get('configs'));
    $form = [];
    $rows = [];
    foreach ($associations as $association) {
      $associated_cmodels = $this->config('islandora_solr_metadata.configs')->get("configs.$association.cmodel_associations");
      if (empty($associated_cmodels)) {
        $associated_cmodels = [
          '#type' => 'item',
          '#markup' => $this->t('No content models currently associated'),
        ];
      }
      else {
        $associated_cmodels = [
          '#theme' => 'item_list',
          '#items' => $associated_cmodels,
        ];
      }
      $rows[] = [
        'name' => [
          '#type' => 'link',
          '#title' => $association,
          '#url' => Url::fromRoute('islandora_solr_metadata.config', ['configuration_name' => $association]),
        ],
        'associated_cmodels' => $associated_cmodels,
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
    ];
    $form['add_configuration']['save_content_model'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add configuration'),
      '#name' => 'islandora_solr_metadata_add_configuration',
    ];
    return $form;
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
    $form_state->loadInclude('islandora_solr_metadata', 'inc', 'includes/db');
    $config_name = $form_state->getValue('configuration_name');
    $this->config('islandora_solr_metadata.configs')
      ->set("configs.$config_name", $this->fieldConfig->getEmptyConfig())
      ->save();
    drupal_set_message($this->t('A new empty configuration has been created for @config_name', ['@config_name' => $config_name]));
  }

}
