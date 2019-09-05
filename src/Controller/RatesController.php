<?php

namespace Drupal\currency_converter_api\Controller;

use Drupal\Core\Controller\ControllerBase;
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
   * Constructs a new RatesController.
   *
   * @param \Drupal\currency_converter_api\CurrencyConverterApiProviderManager $currency_converter_api_provider_manager
   *   The Currency Converter API Provider plugin manager.
   */
  public function __construct(CurrencyConverterApiProviderManager $currency_converter_api_provider_manager) {
    $this->currencyConverterApiProviderManager = $currency_converter_api_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.currency_converter_api')
    );
  }

  /**
   * Get a list of rates.
   */
  public function convert() {
    /** @var \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface $converter */
    $converter = $this->currencyConverterApiProviderManager->createInstance('free_currency_converter_api');
    $rate = $converter->convert('UAH', 'USD');

    return ['#markup' => $rate];
  }

}
