<?php

namespace Drupal\islandora_solr_metadata\Config;

/**
 * Trait to complement FieldConfigInterface, offering some default behavior.
 */
trait FieldConfigTrait {

  /**
   * Gets an empty field using the default settings.
   *
   * @return array
   *   An empty Solr metadata display field config.
   */
  public static function getEmptyField() {
    $def = [
      'weight' => 0,
      'display_label' => '',
      'hyperlink' => FALSE,
      'uri_replacement' => FALSE,
      'date_format' => '',
      'enable_permissions' => FALSE,
      'permissions' => [],
      'truncation' => static::getEmptyTruncation(),
    ];

    assert(0 === \Drupal::service('config.typed')
      ->createFromNameAndData('islandora_solr_metadata.field', $def)
      ->validate()
      ->count(), "Default empty field config is valid.");

    return $def;
  }

  /**
   * Gets an empty config using the default settings.
   *
   * @return array
   *   An empty Solr metadata display config.
   */
  public static function getEmptyConfig() {
    $def = [
      'label' => '',
      'cmodel_associations' => [],
      'description' => [
        'description_field' => '',
        'description_label' => '',
        'truncation' => static::getEmptyTruncation(),
      ],
      'fields' => [],
    ];

    assert(0 === \Drupal::service('config.typed')
      ->createFromNameAndData('islandora_solr_metadata.config', $def)
      ->validate()
      ->count(), "Default empty config is valid.");

    return $def;
  }

  /**
   * Gets an empty truncation configuration using the default settings.
   *
   * @return array
   *   An empty truncation configuration.
   */
  public static function getEmptyTruncation() {
    $def = [
      'truncation_type' => 'separate_value_option',
      'max_length' => 0,
      'word_safe' => FALSE,
      'ellipsis' => FALSE,
      'min_wordsafe_length' => 0,
    ];

    assert(0 === \Drupal::service('config.typed')
      ->createFromNameAndData('islandora_solr_metadata.truncation_options', $def)
      ->validate()
      ->count(), "Default truncation config is valid.");

    return $def;
  }

}
