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

  /**
   * Returns the form with specific for provider settings.
   *
   * @return array
   *   Settings form.
   */
  public function getProviderSettingsForm();

  /**
   * Get all possible currencies for the provider.
   *
   * @return array
   *   Array with all currencies.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getAllCurrencies();

  // TODO: Provide the method 'convert($from, $to)' with caching and checking for accessible currencies for the provider;
}
