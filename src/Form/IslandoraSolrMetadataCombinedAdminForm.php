<?php
namespace Drupal\islandora_solr_metadata\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

class IslandoraSolrMetadataCombinedAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_solr_metadata_combined_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // XXX: Yo dawg I heard you like forms, so I put forms in your forms
    // because vertical tabs can only be rendered in forms and this is a dumb
    // port so it isn't the time to completely redo how these tabs are set up.
    return [
      'tabset' => [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'field-config',
      ],
      'field_config' => [
        '#type' => 'details',
        '#title' => t('Field Configuration'),
        '#group' => 'tabset',
        'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_solr_metadata\Form\IslandoraSolrMetadataAdminForm'),
      ],
      'general_config' => [
        '#type' => 'details',
        '#title' => t('General Configuration'),
        '#group' => 'tabset',
        'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_solr_metadata\Form\IslandoraSolrMetadataGeneralAdminForm'),
      ],
    ];
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
  }

}
