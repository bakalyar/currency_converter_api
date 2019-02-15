<?php

namespace Drupal\currency_converter_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\currency_converter_api\CurrencyConverterApiProviderManager;

/**
 * Returns responses for rates-related routes.
 */
class RatesController extends ControllerBase {

  /**
   * The Currency Converter API Provider plugin manager.
   *
   * @var \Drupal\currency_converter_api\CurrencyConverterApiProviderManager
   */
  protected $currencyConverterApiProviderManager;

  /**
   * The config 'currency_converter_api.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $currencyConverterApiConfig;

  /**
   * The factory for expirable key value stores.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpirableFactory;

  /**
   * Constructs a new RatesController.
   *
   * @param \Drupal\currency_converter_api\CurrencyConverterApiProviderManager $currency_converter_api_provider_manager
   *   The Currency Converter API Provider plugin manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The factory for expirable key value stores.
   */
  public function __construct(CurrencyConverterApiProviderManager $currency_converter_api_provider_manager, KeyValueExpirableFactoryInterface $key_value_expirable_factory) {
    $this->currencyConverterApiProviderManager = $currency_converter_api_provider_manager;
    $this->keyValueExpirableFactory = $key_value_expirable_factory;
    $this->currencyConverterApiConfig = $this->config('currency_converter_api.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.currency_converter_api'),
      $container->get('keyvalue.expirable')
    );
  }

  /**
   * Get a list of rates.
   */
  public function getList() {
    // TODO: Get from configuration.
    $base_currency = 'EUR';
    $allowed_currencies = $this->currencyConverterApiConfig->get('allowed_currencies');
    /** @var \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface $converter */
    $converter = $this->currencyConverterApiProviderManager->createInstance('free_currency_converter_api');
    $converter->updateAllRates($base_currency, $allowed_currencies);

    $expirable_collection = $this->keyValueExpirableFactory->get('currency_converter_api.free_currency_converter_api');
    $list = [];

    foreach ($allowed_currencies as $allowed_currency) {
      $rates_ids = [
        'buy' => "{$base_currency}_{$allowed_currency}",
        'sell' => "{$allowed_currency}_{$base_currency}",
      ];

      foreach ($rates_ids as $type => $convert_id) {
        if ($rate = $expirable_collection->get($convert_id)) {
          $list[$allowed_currency][$type] = round($rate, 6);
        }
      }
    }

    dump($list);

    return ['#markup' => 'Test'];
  }

}
