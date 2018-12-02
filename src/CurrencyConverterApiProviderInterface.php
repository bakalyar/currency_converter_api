<?php

namespace Drupal\currency_converter_api;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for currency converter API provider plugins.
 */
interface CurrencyConverterApiProviderInterface extends PluginInspectionInterface {

  /**
   * Returns the name of the currency converter API provider.
   *
   * @return string
   */
  public function getName();

  /**
   * Returns the host of the currency converter API provider.
   *
   * @return string
   */
  public function getHost();

}
