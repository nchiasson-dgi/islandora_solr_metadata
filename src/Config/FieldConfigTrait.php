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
   * Gets an empty truncation configuration using the default settings.
   *
   * @return array
   *   An empty truncation configuration.
   */
  protected static function getEmptyTruncation() {
    return [
      'truncation_type' => 'separate_value_option',
      'max_length' => 0,
      'word_safe' => FALSE,
      'ellipsis' => FALSE,
      'min_wordsafe_length' => 0,
    ];
  }

}
