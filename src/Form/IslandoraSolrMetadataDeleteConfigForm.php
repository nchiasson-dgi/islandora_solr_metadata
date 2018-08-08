<?php

/**
 * @file
 * Contains \Drupal\islandora_solr_metadata\Form\IslandoraSolrMetadataDeleteConfigForm.
 */

namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraSolrMetadataDeleteConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_delete_config_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $configuration_id = NULL) {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
    $form_state->set(['configuration_id'], $configuration_id);
    $configuration_name = islandora_solr_metadata_retrieve_configuration_name($configuration_id);
    $form_state->set(['configuration_name'], $configuration_name);
    return confirm_form($form, t('Are you sure you want to delete the @configuration_name display configuration?', [
      '@configuration_name' => $configuration_name
      ]), "admin/islandora/search/islandora_solr_metadata/config/$configuration_id", t('This action cannot be undone.'), t('Delete'), t('Cancel'));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/db');
    islandora_solr_metadata_delete_configuration($form_state->get(['configuration_id']));
    $form_state->set(['redirect'], 'admin/islandora/search/islandora_solr/metadata');
    drupal_set_message(t('The @configuration_name display configuration has been deleted!', [
      '@configuration_name' => $form_state->get(['configuration_name'])
      ]));
  }

}
?>
