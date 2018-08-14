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

}
