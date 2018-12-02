<?php

namespace Drupal\currency_converter_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Currency Converter API Provider annotation object.
 *
 * Plugin Namespace: Plugin\CurrencyConverterApiProvider
 *
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderManager
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderBase
 * @see plugin_api
 *
 * @Annotation
 */
class CurrencyConverterApiProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The host of the provider.
   *
   * @var string
   */
  public $host;

}