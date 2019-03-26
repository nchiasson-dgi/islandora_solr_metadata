<?php

namespace Drupal\islandora_solr_metadata\Config;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Metadata display profile field config.
 *
 * Provides implementing code a method of accessing field info by using
 * actual Solr field names.
 */
class FieldConfig implements FieldConfigInterface, RefinableCacheableDependencyInterface {
  use FieldConfigTrait;
  use RefinableCacheableDependencyTrait;

  /**
   * Defines the replacement string for periods in field names.
   */
  const PERIOD_REPLACEMENT = '~dot~';

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
    $this->addCacheableDependency($this->config);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getField($field_name, $config_name) {
    $machine_name = static::getMachineName($field_name);
    return $this->config->get("configs.$config_name.fields.$machine_name");
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields, $config_name) {
    foreach ($fields as $field => $config) {
      $this->setField($field, $config, $config_name);
    }
  }

  /**
   * {@inheritdoc}
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

    $this->config
      ->set("configs.$config_name.fields.$machine_name", $new_config)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFields(array $fields, $config_name) {
    foreach ($fields as $field) {
      $machine_name = static::getMachineName($field);
      $this->config->clear("configs.$config_name.fields.$machine_name");
    }
    $this->config->save();
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
  protected static function getMachineName($field_name) {
    return str_replace('.', static::PERIOD_REPLACEMENT, $field_name);
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
    return str_replace(static::PERIOD_REPLACEMENT, '.', $machine_name);
  }

}
