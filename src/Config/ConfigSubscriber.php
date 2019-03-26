<?php

namespace Drupal\islandora_solr_metadata\Config;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to update cmodel associations table.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor for dependency injection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ConfigEvents::SAVE => ['onConfigSave']];
  }

  /**
   * Applies cmodel association config changes to the DB.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if ($config->getName() == 'islandora_solr_metadata.configs') {
      $this->refreshAssociations($config);
    }
  }

  /**
   * Refreshes content mdoel associations in the database.
   */
  protected function refreshAssociations(Config $config) {
    $tx = $this->database->startTransaction();

    try {
      $this->database->delete('islandora_solr_metadata_cmodels')
        ->execute();
      foreach ($config->get('configs') as $config_name => $config_definition) {
        foreach ($config_definition['cmodel_associations'] as $cmodel) {
          $this->database->insert('islandora_solr_metadata_cmodels')
            ->fields([
              'configuration_name' => $config_name,
              'cmodel' => $cmodel,
            ])
            ->execute();
        }
      }
    }
    catch (Exception $e) {
      $tx->rollback();
      throw $e;
    }
  }

}
