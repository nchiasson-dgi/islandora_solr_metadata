<?php
namespace Drupal\islandora_solr_metadata\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;

/**
 * Default controller for the islandora_solr_metadata module.
 */
class DefaultController extends ControllerBase {

  public function islandoraSolrMetadataAdminPageCallback() {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/admin');
    return islandora_solr_metadata_admin_page_callback();
  }

  public function islandoraSolrMetadataDisplayConfigurationName($configuration_id) {
    return islandora_solr_metadata_display_configuration_name($configuration_id);
  }

  public function islandoraSolrMetadataAccess($configuration_id) {
    $perm = islandora_solr_metadata_access($configuration_id);
    return $perm ? AccessResult::allowed() : AccessResult::forbidden();
  }

  public function islandoraSolrMetadataDisplayFieldConfigurationName($config_id, $escaped_field_name) {
    module_load_include('module', 'islandora_solr_metadata');
    return islandora_solr_metadata_display_field_configuration_name($config_id, $escaped_field_name);
  }

  public function islandoraSolrMetadataFieldConfigurationAccess($config_id, $escaped_field_name) {
    module_load_include('module', 'islandora_solr_metadata');
    $perm = islandora_solr_metadata_field_configuration_access($config_id, $escaped_field_name);
    return $perm ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
