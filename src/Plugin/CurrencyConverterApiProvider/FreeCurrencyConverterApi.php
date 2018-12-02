<?php

namespace Drupal\currency_converter_api\Plugin\CurrencyConverterApiProvider;

use Drupal\currency_converter_api\CurrencyConverterApiProviderBase;

/**
 * Provides a provider for Free Currency Converter API.
 *
 * @CurrencyConverterApiProvider(
 *   id = "free_currency_converter_api",
 *   name = @Translation("Free Currency Converter API"),
 *   host = "https://free.currencyconverterapi.com"
 * )
 */
class FreeCurrencyConverterApi extends CurrencyConverterApiProviderBase {
}
