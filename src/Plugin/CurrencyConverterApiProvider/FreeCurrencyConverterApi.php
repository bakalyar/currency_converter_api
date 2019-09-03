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
   * {@inheritdoc}
   */
  public function getProviderSettingsForm(array $settings = []) {
    $form = [];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#default_value' => !empty($settings['api_key']) ? $settings['api_key'] : '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCurrencies() {
    $cache_id = 'free_currency_converter_api:all_currencies';
    $api_key = $this->currencyConverterApiConfig->get('api_key');
    $endpoint = $this->apiUrl . '/currencies?apiKey=' . $api_key;
    $results = [];

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->request('GET', $endpoint);
      $response_result = Json::decode($response->getBody()->__toString());
      $results = !empty($response_result['results']) ? $response_result['results'] : [];
      if (!empty($results)) {
        $this->cacheSet($cache_id, $results, $this->time->getCurrentTime() + 86400);
      }
    }
    catch (RequestException $e) {
      $this->logger->error("Cannot retrieve the currencies: %msg.", [
        '%endpoint' => $endpoint,
        '%msg' => $e->getMessage(),
      ]);
    }

    return $results;
  }

  // TODO: Use in the block.
//  /**
//   * Import and update expired(older than 1hr) rates for all allowed currencies.
//   *
//   * @param string $base_currency
//   *   The base currency.
//   * @param array $allowed_currencies
//   *   All allowed currencies.
//   *
//   * @throws \GuzzleHttp\Exception\GuzzleException
//   */
//  public function updateAllRates($base_currency = '', array $allowed_currencies = []) {
//    if (!$base_currency || empty($allowed_currencies)) {
//      return;
//    }
//
//    if (isset($allowed_currencies[$base_currency])) {
//      unset($allowed_currencies[$base_currency]);
//    }
//
//    $endpoint = $this->apiUrl . '/convert';
//    $expirable_collection = $this->keyValueExpirableFactory->get('currency_converter_api.free_currency_converter_api');
//
//    foreach ($allowed_currencies as $allowed_currency) {
//      if ($allowed_currency !== $base_currency) {
//        $buy_id = "{$base_currency}_{$allowed_currency}";
//        $sell_id = "{$allowed_currency}_{$base_currency}";
//
//        foreach ([$sell_id, $buy_id] as $convert_id) {
//          if (!$expirable_collection->get($convert_id)) {
//            try {
//              $options = [
//                'query' => [
//                  'q' => $convert_id,
//                  'compact' => 'ultra',
//                ],
//              ];
//              $response = $this->httpClient->request('GET', $endpoint, $options);
//              $response_result = Json::decode($response->getBody()->__toString());
//              if (!empty($response_result[$convert_id])) {
//                $expirable_collection->setWithExpire($convert_id, $response_result[$convert_id], 3600);
//              }
//            }
//            catch (RequestException $e) {
//              throw new RequestException("Could not retrieve the rate from $endpoint, for $sell_id and $buy_id", NULL, $e);
//            }
//          }
//        }
//      }
//    }
//  }

}
