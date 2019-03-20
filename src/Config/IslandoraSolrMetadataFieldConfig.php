<?php

namespace Drupal\islandora_solr_metadata\Config;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\SortArray;

/**
 * Metadata display profile field config.
 *
 * Provides implementing code a method of accessing field info by using
 * actual Solr field names.
 */
class IslandoraSolrMetadataFieldConfig {

  /**
   * Islandora Solr Metadata configs object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to use for this field config.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->getEditable('islandora_solr_metadata.configs');
  }

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
  public function getFields($config_name) {
    $fields = [];
    foreach ($this->config->get("configs.$config_name.fields") as $machine_name => $config) {
      $field_name = static::getFieldName($machine_name);
      $fields[$field_name] = $config;
      $fields[$field_name]['configuration_name'] = $config_name;
    }
    uasort($fields, [SortArray::class, 'sortByWeightElement']);
    return $fields;
  }

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
  public function getField($field_name, $config_name) {
    $machine_name = static::getMachineName($field_name);
    return $this->config->get("configs.$config_name.fields.$machine_name");
  }

  /**
   * Adds or overwrites the fields for a config with a provided set.
   *
   * @param array $fields
   *   Formatted field info to set in this config.
   * @param string $config_name
   *   The configuration to set.
   */
  public function setFields(array $fields, $config_name) {
    foreach ($fields as $field => $config) {
      $this->setField($field, $config, $config_name);
    }
  }

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
  public function setField($field_name, array $config, $config_name) {
    $machine_name = static::getMachineName($field_name);
    $new_config = [
      'weight' => isset($config['weight']) ? $config['weight'] : 0,
      'display_label' => isset($config['display_label']) ? $config['display_label'] : $field_name,
      'hyperlink' => isset($config['hyperlink']) ? $config['hyperlink'] : FALSE,
      'uri_replacement' => isset($config['uri_replacement']) ? $config['uri_replacement'] : FALSE,
      'date_format' => isset($config['date_format']) ? $config['date_format'] : '',
      'enable_permissions' => isset($config['enable_permissions']) ? $config['enable_permissions'] : FALSE,
      'permissions' => isset($config['permissions']) ? $config['permissions'] : [],
      'truncation' => [
        'truncation_type' => isset($config['truncation']['truncation_type']) ? $config['truncation']['truncation_type'] : 'separate_value_option',
        'max_length' => isset($config['truncation']['max_length']) ? $config['truncation']['max_length'] : 0,
        'word_safe' => isset($config['truncation']['word_safe']) ? $config['truncation']['word_safe'] : FALSE,
        'ellipsis' => isset($config['truncation']['ellipsis']) ? $config['truncation']['ellipsis'] : FALSE,
        'min_wordsafe_length' => isset($config['truncation']['ellipsis']) ? $config['truncation']['ellipsis'] : 0,
      ],
    ];
    $this->config->set("configs.$config_name.fields.$machine_name", $new_config)
      ->save();
  }

  /**
   * Removes the given fields from the config, if they exist.
   *
   * @param array $fields
   *   An array of fields to remove.
   * @param string $config_name
   *   The configuration to set.
   */
  public function deleteFields(array $fields, $config_name) {
    foreach ($fields as $field) {
      $machine_name = static::getMachineName($field);
      $this->config->clear("configs.$config_name.fields.$machine_name");
    }
    $this->config->save();
  }

  /**
   * Gets an empty config using the default settings.
   *
   * @return array
   *   An empty Solr metadata display config.
   */
  public static function getEmptyConfig() {
    return [
      'cmodel_associations' => [],
      'description' => [
        'description_field' => '',
        'description_label' => '',
        'truncation' => static::getEmptyTruncation(),
      ],
      'fields' => [],
    ];
  }

  /**
   * Gets an empty field using the default settings.
   *
   * @return array
   *   An empty Solr metadata display field config.
   */
  public static function getEmptyField() {
    return [
      'weight' => 0,
      'display_label' => '',
      'hyperlink' => FALSE,
      'uri_replacement' => FALSE,
      'date_format' => '',
      'enable_permissions' => FALSE,
      'permissions' => [],
      'truncation' => static::getEmptyTruncation(),
    ];
  }

  /**
   * Gets an empty truncation configuration using the default settings.
   *
   * @return array
   *   An empty truncation configuration.
   */
  public static function getEmptyTruncation() {
    return [
      'truncation_type' => 'separate_value_option',
      'max_length' => 0,
      'word_safe' => FALSE,
      'ellipsis' => FALSE,
      'min_wordsafe_length' => 0,
    ];
  }

  /**
   * Defines the replacement string for periods in field names.
   *
   * @return string
   *   The string to use to replace periods in machine names. This obviously
   *   should not contain periods; if it does, your configs may blow up.
   */
  public static function getPeriodReplacement() {
    return '~dot~';
  }

  /**
   * Converts a Solr field into a field machine name.
   *
   * @param string $field_name
   *   The name of the field to get.
   *
   * @return string
   *   The machine name for this field.
   */
  public static function getMachineName($field_name) {
    return str_replace('.', static::getPeriodReplacement(), $field_name);
  }

  /**
   * Converts a machine name into a Solr field name.
   *
   * @param string $machine_name
   *   The machine name of the field to get.
   *
   * @return string
   *   The name of the Solr field.
   */
  protected function getFieldName($machine_name) {
    return str_replace(static::getPeriodReplacement(), '.', $machine_name);
  }

}
