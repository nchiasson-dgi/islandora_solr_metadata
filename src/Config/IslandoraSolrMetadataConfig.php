<?php

namespace Drupal\islandora_solr_metadata\Config;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Component\Utility\SortArray;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Metadata display profile config.
 *
 * Provides implementing code a method of accessing field info by using
 * actual Solr field names.
 */
class IslandoraSolrMetadataConfig extends Config {

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
    $field_config = $this->get("$config_name.fields");
    foreach ($field_config as $machine_name => $config) {
      $fields[static::getFieldName($machine_name)] = $config;
    }
    uasort($fields, [SortArray::class, 'sortByWeightElement']);
    return $fields;
  }

  /**
   * Adds or overwrites the fields for a config with a provided set.
   *
   * @param string $config_name
   *   The configuration to set.
   * @param array $fields
   *   Formatted field info to set in this config.
   */
  public function setFields($config_name, $fields) {
    foreach ($fields as $field => $config) {
      $this->setField($config_name, $field, $config);
    }
  }

  /**
   * Adds or overwrites a field configuration.
   *
   * @param string $config_name
   *   The configuration to set.
   * @param string $field_name
   *   The name of the Solr field to set the config for.
   * @param array $config
   *   The configuration to use for this field.
   */
  public function setField($config_name, $field_name, $config) {
    $machine_name = static::getMachineName($field);
    $this->set("$config_name.$machine_name", $config);
  }

  /**
   * Removes the given fields from the config, if they exist.
   *
   * @param string $config_name
   *   The configuration to set.
   * @param array $fields
   *   An array of fields to remove.
   */
  public function deleteFields($config_name, $fields) {
    foreach ($fields as $field) {
      $machine_name = static::getMachineName($field);
      $this->clear("$config_name.$machine_name");
    }
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
      'weight' => 0,
      'description' => [
        'field' => '',
        'label' => '',
        'truncation' => static::getEmptyTruncation(),
        'enable_permissions' => FALSE,
        'permissions' => [],
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
      'type' => 'separate_value_option',
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
