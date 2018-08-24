<?php
namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

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
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
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
          '#markup' => t('No content models currently associated'),
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
          '#url' => Url::fromRoute('islandora_solr_metadata.config_1', ['configuration_id' => $association['id']]),
        ],
        'associated_cmodels' => $associated_cmodels,
        'machine_name' => [
          '#type' => 'item',
          '#markup' => $association['machine_name'],
        ],
      ];
    }
    $form['table'] = array(
      '#title' => t('Solr metadata associations'),
      '#type' => 'table',
      '#header' => array(
        t('Name'),
        t('Associated content models'),
        t('Machine name'),
      ),
      '#empty' => t('No associations currently present.'),
    ) + $rows;
    $form['add_configuration'] = array(
      '#type' => 'fieldset',
      '#title' => t('Add a configuration'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['add_configuration']['configuration_name'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => t('Configuration name'),
    );
    $form['add_configuration']['machine_name'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => t('Machine name'),
      '#description' => t('A unique machine name used in the exportation of features'),
    );
    $form['add_configuration']['save_content_model'] = array(
      '#type' => 'submit',
      '#value' => t('Add configuration'),
      '#name' => 'islandora_solr_metadata_add_configuration',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if (empty($form_state->getValue('configuration_name'))) {
      $form_state->setErrorByName('configuration_name', t('Please enter a non-empty configuration name!'));
    }
    if (empty($form_state->getValue('machine_name'))) {
      $form_state->setErrorByName('machine_name', t('Please enter a non-empty machine name!'));
    }
    else {
      module_load_include('inc', 'islandora_solr_metadata', 'db');
      $config_exists = islandora_solr_metadata_retrieve_configuration_from_machine_name($form_state->getValue('machine_name'));
      if ($config_exists !== FALSE) {
        $form_state->setErrorByName('machine_name', t('The machine name of @machine already exists in the database!', array('@machine' => $form_state->getValue('machine_name'))));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_solr_metadata', 'inc', 'includes/db');
    islandora_solr_metadata_add_configuration($form_state->getValue('configuration_name'), $form_state->getValue('machine_name'));
    drupal_set_message(t('A new empty configuration has been created for @config_name', array('@config_name' => $form_state->getValue('configuration_name'))));
  }

}
