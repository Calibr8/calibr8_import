<?php

namespace Drupal\calibr8_import\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Provides the Calibr8 Importer plugin manager.
 */
class Calibr8ImporterManager extends DefaultPluginManager {

  /**
   * Constructs a new Calibr8ImporterManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Calibr8Importer', $namespaces, $module_handler, 'Drupal\calibr8_import\Plugin\Calibr8Importer\Calibr8ImporterInterface', 'Drupal\calibr8_import\Annotation\Calibr8Importer');

    $this->alterInfo('calibr8_import_calibr8_importer_info');
    $this->setCacheBackend($cache_backend, 'calibr8_import_calibr8_importer_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
