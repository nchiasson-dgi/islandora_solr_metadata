<?php /**
 * @file
 * Contains \Drupal\islandora_solr_metadata\Controller\DefaultController.
 */

namespace Drupal\islandora_solr_metadata\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the islandora_solr_metadata module.
 */
class DefaultController extends ControllerBase {

  public function islandora_solr_metadata_admin_page_callback() {
    module_load_include('inc', 'islandora_solr_metadata', 'includes/config.admin');
    module_load_include('inc', 'islandora_solr_metadata', 'includes/general.admin');
    return [
      'tabset' => [
        '#type' => 'vertical_tabs',
        'field_config' => [
          '#type' => 'fieldset',
          '#title' => t('Field Configuration'),
          '#group' => 'tabset',
          'form' => \Drupal::formBuilder()->getForm('islandora_solr_metadata_admin_form'),
        ],
        'general_config' => [
          '#type' => 'fieldset',
          '#title' => t('General Configuration'),
          '#group' => 'tabset',
          'form' => \Drupal::formBuilder()->getForm('islandora_solr_metadata_general_admin_form'),
        ],
      ]
      ];
  }

}
