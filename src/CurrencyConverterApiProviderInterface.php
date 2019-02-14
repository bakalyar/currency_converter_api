<?php

namespace Drupal\currency_converter_api;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for currency converter API provider plugins.
 */
interface CurrencyConverterApiProviderInterface extends PluginInspectionInterface {

  // TODO: Provide all methods.
  /**
   * Returns the name of the currency converter API provider.
   *
   * @return string
   *   Name of the provider.
   */
  public function getName();

  /**
   * Returns the API url of the currency converter API provider.
   *
   * @return string
   *   API url.
   */
  public function getApiUrl();

}
