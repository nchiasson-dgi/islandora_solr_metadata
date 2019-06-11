<?php

namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Confirmation form for when user decides to delete a configuration.
 */
class DeleteConfigForm extends ConfirmFormBase {

  /**
   * The name of the item to delete.
   *
   * @var string
   */
  protected $configurationName;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_delete_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
    return $this->t('Are you sure you want to delete the @configuration_name display configuration?', [
      '@configuration_name' => $this->configurationName,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('islandora_solr_metadata.config', ['configuration_name' => $this->configurationName]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $configuration_name = NULL) {
    $this->configurationName = $configuration_name;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
    islandora_solr_metadata_delete_configuration($this->configurationName);
    $form_state->setRedirect('islandora_solr_metadata.metadata_display');
    drupal_set_message($this->t('The @configuration_name display configuration has been deleted!', [
      '@configuration_name' => $this->configurationName,
    ]));
  }

}
