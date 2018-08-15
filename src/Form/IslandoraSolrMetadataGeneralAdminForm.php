<?php
namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

class IslandoraSolrMetadataGeneralAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_general_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_solr_metadata.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = $this->config('islandora_solr_metadata.settings');
    $form += array(
      'islandora_solr_metadata_omit_empty_values' => array(
        '#type' => 'checkbox',
        '#title' => t('Omit Empty Values'),
        '#description' => t('Avoid rendering fields which have no values associated with them.'),
        '#default_value' => $config->get('islandora_solr_metadata_omit_empty_values'),
      ),
      'islandora_solr_metadata_dedup_values' => array(
        '#type' => 'checkbox',
        '#title' => t('Omit Duplicate Values'),
        '#description' => t('Only show unique values from each field. NOTE: Uniqueness is checked on values directly out of Solr. Later formatting of the value could break uniqueness.'),
        '#default_value' => $config->get('islandora_solr_metadata_dedup_values'),
      ),
      'islandora_solr_metadata_field_value_separator' => array(
        '#type' => 'textfield',
        '#title' => t('Field value separator'),
        '#description' => t('Characters to separate values in multivalued fields. If left empty it will default to newline.'),
        '#default_value' => $config->get('islandora_solr_metadata_field_value_separator'),
      ),
      'actions' => array(
        '#type' => 'actions',
        'submit' => array(
          '#type' => 'submit',
          '#value' => t('Save configuration'),
        ),
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $variables = array(
      'islandora_solr_metadata_omit_empty_values',
      'islandora_solr_metadata_dedup_values',
      'islandora_solr_metadata_field_value_separator',
    );
    foreach ($variables as $variable) {
      $this->config('islandora_solr_metadata.settings')->set($variable, $form_state->getValue($variable));
    }
    $this->config('islandora_solr_metadata.settings')->save();
  }

}
