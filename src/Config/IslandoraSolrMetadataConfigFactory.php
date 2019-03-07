<?php

namespace Drupal\islandora_solr_metadata\Config;

use Drupal\Core\Config\ConfigFactory;
use Drupal\islandora_solr_metadata\Config\IslandoraSolrMetadataConfig;

/**
 * Config factory for Islandora Solr Metadata configs.
 */
class IslandoraSolrMetadataConfigFactory extends ConfigFactory {

  /**
   * {@inheritdoc}
   */
  protected function createConfigObject($name, $immutable) {
    return new IslandoraSolrMetadataConfig($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
  }

}
