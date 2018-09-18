<?php
namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Confirmation form for when user decides to delete a configuration.
 */
class IslandoraSolrMetadataDeleteConfigForm extends ConfirmFormBase {

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $configurationId;

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
    $configuration_name = islandora_solr_metadata_retrieve_configuration_name($this->configurationId);
    return t('Are you sure you want to delete the @configuration_name display configuration?', [
      '@configuration_name' => $configuration_name,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('islandora_solr_metadata.config_1', ['configuration_id' => $this->configurationId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $configuration_id = NULL) {
    $this->configurationId = $configuration_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
    islandora_solr_metadata_delete_configuration($this->configurationId);
    $form_state->setRedirect('islandora_solr_metadata.metadata_display');
    drupal_set_message(t('The @configuration_name display configuration has been deleted!', [
      '@configuration_name' => $this->configurationId,
    ]));
  }

}
