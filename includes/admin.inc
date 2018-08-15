<?php

/**
 * @file
 * Contains page callback for admin screen.
 */

/**
 * Page callback for admin screen.
 *
 * Vertical tabs containing multiple forms.
 */
function islandora_solr_metadata_admin_page_callback() {
  return [
    'field_config' => [
      '#type' => 'details',
      '#title' => t('Field Configuration'),
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_solr_metadata\Form\IslandoraSolrMetadataAdminForm'),
      '#open' => FALSE,
    ],
    'general_config' => [
      '#type' => 'details',
      '#title' => t('General Configuration'),
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_solr_metadata\Form\IslandoraSolrMetadataGeneralAdminForm'),
      '#open' => FALSE,
    ],
  ];
}
