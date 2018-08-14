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
  // XXX: Hacky page callback because vertical tabs cannot be used outside of
  // forms.
  return \Drupal::formBuilder()->getForm('Drupal\islandora_solr_metadata\Form\IslandoraSolrMetadataCombinedAdminForm');
}
