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
 *   api_url = "https://free.currencyconverterapi.com/api/v6"
 * )
 */
class FreeCurrencyConverterApi extends CurrencyConverterApiProviderBase {

  /**
   * Get all possible currencies for the provider.
   *
   * @return array
   *   Array with all currencies.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getAllCurrencies() {
    $cache_id = 'free_currency_converter_api:all_currencies';
    $endpoint = $this->apiUrl . '/currencies';

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->request('GET', $endpoint);
      $response_result = Json::decode($response->getBody()->__toString());
    }
    catch (RequestException $e) {
      throw new RequestException("Could not retrieve the currencies from $endpoint", NULL, $e);
    }

    $results = !empty($response_result['results']) ? $response_result['results'] : [];

    if (!empty($results)) {
      $this->cacheSet($cache_id, $results);
    }

    return $results;
  }

  /**
   * Import and update rates for all allowed currencies.
   *
   * @param string $base_currency
   *   The base currency.
   * @param array $allowed_currencies
   *   All allowed currencies.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function updateAllRates($base_currency = '', array $allowed_currencies = []) {
    if (!$base_currency || empty($allowed_currencies)) {
      return;
    }

    if (isset($allowed_currencies[$base_currency])) {
      unset($allowed_currencies[$base_currency]);
    }

    // TODO: Test it.
    $endpoint = $this->apiUrl . '/convert';

    foreach ($allowed_currencies as $allowed_currency) {
      try {
        $sell_id = "{$base_currency}_{$allowed_currency}";
        $buy_id = "{$allowed_currency}_{$base_currency}";
        $options = [
          'query' => [
            'q' => "$sell_id,$buy_id",
          ],
        ];
        $response = $this->httpClient->request('GET', $endpoint, $options);
        $response_result = Json::decode($response->getBody()->__toString());

        foreach ([$sell_id, $buy_id] as $convert_id) {
          if (!empty($response_result['results'][$convert_id]['val'])) {
            \Drupal::state()->set($convert_id, $response_result['results'][$convert_id]['val']);
          }
        }
      }
      catch (RequestException $e) {
        throw new RequestException("Could not retrieve the rate from $endpoint, for $sell_id and $buy_id", NULL, $e);
      }
    }
  }

}
