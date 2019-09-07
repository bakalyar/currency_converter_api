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
 *   api_url = "https://free.currconv.com/api/v7"
 * )
 */
class FreeCurrencyConverterApi extends CurrencyConverterApiProviderBase {

  // TODO: add to settings form.
  const CACHE_TIME = 86400;

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
    $cid = 'free_currency_converter_api:all_currencies';
    $api_key = $this->currencyConverterApiConfig->get('api_key');
    $endpoint = $this->apiUrl . '/currencies?apiKey=' . $api_key;
    $results = [];

    $cached = $this->cacheGet($cid);
    if ($cached) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->request('GET', $endpoint);
      $response_result = Json::decode($response->getBody()->__toString());
      $all_currencies_data = !empty($response_result['results']) ? $response_result['results'] : [];
      if (!empty($all_currencies_data)) {
        $column_sort_by = array_column($all_currencies_data, 'currencyName');
        if ($column_sort_by) {
          array_multisort($column_sort_by, SORT_ASC, $all_currencies_data);
        }

        foreach ($all_currencies_data as $currency) {
          $results[$currency['id']] = $currency['currencyName'];
        }
        $this->cacheSet($cid, $results, $this->time->getCurrentTime() + self::CACHE_TIME);
      }
    }
    catch (RequestException $e) {
      $this->logger->error("Cannot retrieve the currencies: %msg.", [
        '%msg' => $e->getMessage(),
      ]);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($from, $to) {
    $results = [];
    $all_currencies = array_keys($this->getAllCurrencies());
    if (!in_array($from, $all_currencies) || !in_array($to, $all_currencies)) {
      $this->logger->error("Cannot convert from %from to %to.", [
        '%from' => $from,
        '%to' => $to,
      ]);
      return $results;
    }

    $api_key = $this->currencyConverterApiConfig->get('api_key');
    $from_to_key = $from . '_' . $to;
    $from_to_cid = 'free_currency_converter_api:rate:' . $from_to_key;
    $from_to_reverse_key = $to . '_' . $from;
    $from_to_reverse_cid = 'free_currency_converter_api:rate:' . $from_to_reverse_key;
    $endpoint = $this->apiUrl . '/convert?apiKey=' . $api_key . '&q=' . $from_to_key . ',' . $from_to_reverse_key;

    $cached = $this->cacheGet($from_to_cid);
    if ($cached) {
      return $cached->data;
    }

    try {
      $response = $this->httpClient->request('GET', $endpoint);
      $response_result = Json::decode($response->getBody()->__toString());
      $cache_time = $this->time->getCurrentTime() + self::CACHE_TIME;

      // Get requested rate.
      $results = !empty($response_result['results'][$from_to_key]['val']) ? $response_result['results'][$from_to_key]['val'] : [];
      if (!empty($results)) {
        $this->cacheSet($from_to_cid, $results, $cache_time);
      }

      // Cache for possible using reverse rate to reduce count of requests.
      $reverse_results = !empty($response_result['results'][$from_to_reverse_key]['val']) ? $response_result['results'][$from_to_reverse_key]['val'] : [];
      if (!empty($reverse_results)) {
        $this->cacheSet($from_to_reverse_cid, $reverse_results, $cache_time);
      }

    }
    catch (RequestException $e) {
      $this->logger->error("Cannot convert from %from to %to: %msg.", [
        '%from' => $from,
        '%to' => $to,
        '%msg' => $e->getMessage(),
      ]);
    }

    return $results;

  }

}
