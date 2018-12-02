<?php

namespace Drupal\currency_converter_api;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Currency Converter API Provider plugin manager.
 *
 * @see \Drupal\currency_converter_api\Annotation\CurrencyConverterApiProvider
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderBase
 * @see plugin_api
 */
class CurrencyConverterApiProviderManager extends DefaultPluginManager {

  /**
   * Constructs a new class instance.
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
    parent::__construct('Plugin/CurrencyConverterApiProvider', $namespaces, $module_handler, 'Drupal\currency_converter_api\CurrencyConverterApiProviderInterface', 'Drupal\currency_converter_api\Annotation\CurrencyConverterApiProvider');
    $this->alterInfo('currency_converter_api_info');
    $this->setCacheBackend($cache_backend, 'currency_converter_api_info');
  }

}
