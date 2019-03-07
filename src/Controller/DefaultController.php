<?php

namespace Drupal\islandora_solr_metadata\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;

/**
 * Default controller for the islandora_solr_metadata module.
 */
class DefaultController extends ControllerBase {

  /**
   * Page callback for solr metadata admin page.
   */
  public function islandoraSolrMetadataAdminPageCallback() {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/admin');
    return islandora_solr_metadata_admin_page_callback();
  }

  /**
   * Title callback for solr metadata display configuration page.
   *
   * @param string $configuration_name
   *   The name of the desired metadata display configuration.
   */
  public function islandoraSolrMetadataDisplayConfigurationName($configuration_name) {
    return islandora_solr_metadata_display_configuration_name($configuration_name);
  }

  /**
   * Access callback for solr metadata.
   *
   * @param string $configuration_name
   *   The name of the desired metadata display configuration.
   */
  public function islandoraSolrMetadataAccess($configuration_name) {
    $perm = islandora_solr_metadata_access($configuration_name);
    return $perm ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Title callback for solr metadata display field configuration page.
   *
   * @param int $config_id
   *   The integer ID for the desired metadata display configuration.
   * @param string $escaped_field_name
   *   The string containing the escaped field name.
   */
  public function islandoraSolrMetadataDisplayFieldConfigurationName($config_id, $escaped_field_name) {
    module_load_include('module', 'islandora_solr_metadata');
    return islandora_solr_metadata_display_field_configuration_name($config_id, $escaped_field_name);
  }

  /**
   * Access callback for solr metadata field configuration.
   *
   * @param int $config_id
   *   The integer ID for the desired metadata display configuration.
   * @param string $escaped_field_name
   *   The string containing the escaped field name.
   */
  public function islandoraSolrMetadataFieldConfigurationAccess($config_id, $escaped_field_name) {
    module_load_include('module', 'islandora_solr_metadata');
    $perm = islandora_solr_metadata_field_configuration_access($config_id, $escaped_field_name);
    return $perm ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
