<?php

namespace Drupal\currency_converter_api\Plugin\CurrencyConverterApiProvider;

use Drupal\currency_converter_api\CurrencyConverterApiProviderBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;

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

  public function getAllCurrencies() {
    $cache_id = 'free_currency_converter_api:all_currencies';
    $host = $this->getHost();
    $url = $host . '/api/v6/currencies';

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->request('GET', $url);
    }
    catch (RequestException $e) {
      throw new RequestException("Could not retrieve the currencies from $url", NULL, $e);
    }

    $response_result = Json::decode($response->getBody()->__toString());
    $results = !empty($response_result['results']) ? $response_result['results'] : [];

    if (empty($response_result['results'])) {
      return [];
    }

    $this->cacheSet($cache_id, $results);

    return $results;
  }

}
