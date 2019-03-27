<?php

namespace Drupal\islandora_solr_metadata\Config;

/**
 * Field configuration storage interface.
 */
interface FieldConfigInterface {

  /**
   * Gets the fields property of this configuration.
   *
   * Machine names of keys are replaced with the actual field names.
   *
   * @param string $config_name
   *   The name of the configuration to get fields for.
   *
   * @return array
   *   An associative array mapping Solr field names to their configurations.
   *   These will come back sorted by weight.
   */
  public function getFields($config_name);

  /**
   * Gets an individual field config from this configuration.
   *
   * @param string $field_name
   *   The name of the field to get.
   * @param string $config_name
   *   The name of the configuration to get the field config from.
   *
   * @return array|null
   *   The field config, or NULL if no such field exists.
   */
  public function getField($field_name, $config_name);

  /**
   * Adds or overwrites the fields for a config with a provided set.
   *
   * @param array $fields
   *   Formatted field info to set in this config.
   * @param string $config_name
   *   The configuration to set.
   */
  public function setFields(array $fields, $config_name);

  /**
   * Adds or overwrites a field configuration.
   *
   * @param string $field_name
   *   The name of the Solr field to set the config for.
   * @param array $config
   *   The configuration to use for this field.
   * @param string $config_name
   *   The configuration this field is being set for.
   */
  public function setField($field_name, array $config, $config_name);

  /**
   * Removes the given fields from the config, if they exist.
   *
   * @param array $fields
   *   An array of fields to remove.
   * @param string $config_name
   *   The configuration to set.
   */
  public function deleteFields(array $fields, $config_name);

  /**
   * Atomically replace all the defined fields in the given configuration.
   *
   * @param array $fields
   *   The fields definitions to configure.
   * @param string $config_name
   *   The name of the configuration.
   */
  public function replaceFields(array $fields, $config_name);

  /**
   * Gets an empty config using the default settings.
   *
   * @return array
   *   An empty Solr metadata display config.
   */
  public static function getEmptyConfig();

  /**
   * Gets an empty field using the default settings.
   *
   * @return array
   *   An empty Solr metadata display field config.
   */
  public static function getEmptyField();

}
