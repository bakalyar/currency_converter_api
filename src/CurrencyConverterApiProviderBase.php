<?php

namespace Drupal\currency_converter_api;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides a base implementation for a currency converter API provider plugin.
 *
 * @see \Drupal\currency_converter_api\Annotation\CurrencyConverterApiProvider
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderManager
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface
 * @see plugin_api
 */
abstract class CurrencyConverterApiProviderBase extends PluginBase implements CurrencyConverterApiProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    return $this->pluginDefinition['host'];
  }

}
