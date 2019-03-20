<?php

/**
 * @file
 * This file contains DB query functions.
 */

/**
 * Get all display configurations based upon an object's content models.
 *
 * @param array $cmodels
 *   An array containing content models of the object we are retrieving from.
 *
 * @return array
 *   An array of strings, representing display configuration IDs.
 */
function islandora_solr_metadata_get_associations_by_cmodels(array $cmodels) {
  // There's an extra model that needs to be ignored.
  $to_ignore = array_search('fedora-system:FedoraObject-3.0', $cmodels);
  if ($to_ignore) {
    unset($cmodels[$to_ignore]);
  }
  $results = db_select('islandora_solr_metadata_cmodels', 'i')
    ->fields('i', ['configuration_name'])
    ->condition('cmodel', $cmodels)
    ->execute()
    ->fetchAllAssoc('configuration_name', PDO::FETCH_ASSOC);
  return $results;
}

/**
 * Get models associated with the given configuration.
 *
 * @param string $configuration_name
 *   The name of the configuration for which to grab the content models.
 *
 * @return array
 *   An array of content model PIDs, mapped to keys so they can be used for
 *   select elements.
 */
function islandora_solr_metadata_get_cmodels($configuration_name) {
  $associations = \Drupal::config('islandora_solr_metadata.configs')
    ->get("configs.$configuration_name.cmodel_associations");
  return array_combine($associations, $associations);
}

/**
 * Get all fields in the given display.
 *
 * XXX: This isn't strictly necessary anymore, but is being maintained because
 * it appears to be in place in custom modules. Could consider deprecating.
 *
 * @param string $configuration_name
 *   The name of the configuration for which to get the list of fields.
 *
 * @return array
 *   An associative array of associative arrays, representing Solr fields
 *   mapped to the details about the given field, including:
 *   - display_label
 *   - weight
 *   - solr_field.
 *
 * @see IslandoraSolrMetadataConfig::getFields()
 */
function islandora_solr_metadata_get_fields($configuration_name) {
  return \Drupal::service('islandora_solr_metadata.field_config')
    ->getFields($configuration_name);
}

/**
 * Delete the given configuration indicated by the given ID.
 *
 * @param string $configuration_name
 *   The name of the configuration to remove.
 */
function islandora_solr_metadata_delete_configuration($configuration_name) {
  \Drupal::service('config.factory')
    ->getEditable('islandora_solr_metadata.configs')
    ->clear("configs.$configuration_name")
    ->save();
}

/**
 * Check that a configuration exists with the given name.
 *
 * @param string $configuration_name
 *   A name to test.
 *
 * @return bool
 *   Whether or not the configuration exists.
 */
function islandora_solr_metadata_configuration_exists($configuration_name) {
  return !is_null(\Drupal::config('islandora_solr_metadata.configs')->get("configs.$configuration_name"));
}

/**
 * Add fields to a specified configuration ID.
 *
 * TODO: Do we even need this anymore?
 *
 * @param string $configuration_name
 *   The name of the configuration which to add to.
 * @param array $fields
 *   An associative array pairing Solr fields with field config info.
 */
function islandora_solr_metadata_add_fields($configuration_name, array $fields) {
  islandora_solr_metadata_update_fields($configuration_name, $fields);
}

/**
 * Delete fields from a configuration given configuration ID.
 *
 * @param string $configuration_name
 *   The name of the configuration which to add to.
 * @param array $fields
 *   An array of fields to remove.
 */
function islandora_solr_metadata_delete_fields($configuration_name, array $fields) {
  \Drupal::service('islandora_solr_metadata.field_config')
    ->deleteFields($fields, $configuration_name);
}

/**
 * Adds or updates fields in a configuration given configuration ID.
 *
 * @param string $configuration_name
 *   The name of the configuration which to add to.
 * @param array $fields
 *   An associative array of associative arrays, representing Solr fields
 *   mapped to the details about the given field, including:
 *   - display_label
 *   - weight
 *   - solr_field.
 *
 * @see IslandoraSolrMetadataConfig::setFields()
 */
function islandora_solr_metadata_update_fields($configuration_name, array $fields) {
  \Drupal::service('islandora_solr_metadata.field_config')
    ->setFields($fields, $configuration_name);
}

/**
 * Retrieves description information about a configuration.
 *
 * @param string $configuration_name
 *   The name of the configuration to retrieve the description for.
 *
 * @return array
 *   An associative array containing:
 *   -description_field: The Solr field used to generate the description.
 *   -description_label: The display label associated with the description.
 *   -description_data: An array of extra data for custom solr metadata display.
 *     -truncation: An array of saved field values for default display.
 *       -truncation_type: As excepted by truncate_utf8().
 *       -max_length: As excepted by truncate_utf8().
 *       -word_safe: As excepted by truncate_utf8().
 *       -ellipsis: As excepted by truncate_utf8().
 *       -min_wordsafe_length: As excepted by truncate_utf8().
 */
function islandora_solr_metadata_retrieve_description($configuration_name) {
  return \Drupal::config('islandora_solr_metadata.configs')
    ->get("configs.$configuration_name.description");
}

/**
 * Updates the description field and label in the database.
 *
 * @param string $configuration_name
 *   The configuration ID of the configuration which to update.
 * @param string $description_field
 *   The Solr field which we are grabbing the description value from.
 * @param string $description_label
 *   The label for the description metadata.
 * @param array $truncation_data
 *   An array of formatted truncation data.
 */
function islandora_solr_metadata_update_description($configuration_name, $description_field, $description_label, array $truncation_data) {
  if (empty($description_field)) {
    $description_field = NULL;
    $description_label = NULL;
    $truncation_data = [];
  }
  \Drupal::service('config.factory')
    ->getEditable('islandora_solr_metadata.configs')
    ->set("configs.$configuration_name.description.description_field", $description_field)
    ->set("configs.$configuration_name.description.description_label", $description_label)
    ->set("configs.$configuration_name.description.truncation", $truncation_data)
    ->save();
}
