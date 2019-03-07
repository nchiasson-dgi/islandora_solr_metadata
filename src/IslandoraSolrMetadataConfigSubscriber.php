<?php

namespace Drupal\islandora_solr_metadata;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Override the config object for Solr Metadata configs.
 *
 * Returning a custom extended config object with some extra bits. This will
 * allow us to do some behind-the-scenes managing of some information about the
 * metadata configs while allowing it to be accessible via standard means.
 *
 */
class IslandoraSolrMetadataConfigSubscriber implements EventSubscriberInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs the configuration subscriber.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if ($event->config->name == 'islandora_solr_metadata.configs') {
      $this->refreshCModelAssociations($event->config);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  protected function refreshCModelAssociations(Config $config) {
    \Drupal::database()
      ->delete('islandora_solr_metadata_cmodels')
      ->execute();
    foreach ($config->get('configs') as $config_name => $association) {
      foreach ($association->get('cmodel_associations') as $cmodel) {
        \Drupal::database()
          ->insert('islandora_solr_metadata_cmodels', 'i')
          ->fields('i', [
            'configuration_name' => $config_name,
            'cmodel' => $cmodel,
          ])
          ->execute();
    }
  }

}
