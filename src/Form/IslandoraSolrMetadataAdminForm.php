<?php

namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Admin form for solr metadata.
 */
class IslandoraSolrMetadataAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_solr_metadata', 'inc', 'includes/db');
    $associations = islandora_solr_metadata_get_associations();
    $form = [];
    $rows = [];
    foreach ($associations as $association) {
      $cmodels = islandora_solr_metadata_get_cmodels($association['id']);
      $associated_cmodels = [];
      if (empty($cmodels)) {
        $associated_cmodels = [
          '#type' => 'item',
          '#markup' => $this->t('No content models currently associated'),
        ];
      }
      else {
        $associated_cmodels = [
          '#theme' => 'item_list',
          '#items' => array_keys($cmodels),
        ];
      }
      $rows[] = [
        'name' => [
          '#type' => 'link',
          '#title' => $association['name'],
          '#url' => Url::fromRoute('islandora_solr_metadata.config', ['configuration_id' => $association['id']]),
        ],
        'associated_cmodels' => $associated_cmodels,
        'machine_name' => [
          '#type' => 'item',
          '#markup' => $association['machine_name'],
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
    ];
    $form['add_configuration']['machine_name'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Machine name'),
      '#description' => $this->t('A unique machine name used in the exportation of features'),
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
    if (empty($form_state->getValue('machine_name'))) {
      $form_state->setErrorByName('machine_name', $this->t('Please enter a non-empty machine name!'));
    }
    else {
      module_load_include('inc', 'islandora_solr_metadata', 'db');
      $config_exists = islandora_solr_metadata_retrieve_configuration_from_machine_name($form_state->getValue('machine_name'));
      if ($config_exists !== FALSE) {
        $form_state->setErrorByName('machine_name', $this->t('The machine name of @machine already exists in the database!', ['@machine' => $form_state->getValue('machine_name')]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_solr_metadata', 'inc', 'includes/db');
    islandora_solr_metadata_add_configuration($form_state->getValue('configuration_name'), $form_state->getValue('machine_name'));
    drupal_set_message($this->t('A new empty configuration has been created for @config_name', ['@config_name' => $form_state->getValue('configuration_name')]));
  }

}
