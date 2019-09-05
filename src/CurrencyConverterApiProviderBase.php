<?php

namespace Drupal\currency_converter_api;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides a base implementation for a currency converter API provider plugin.
 *
 * @see \Drupal\currency_converter_api\Annotation\CurrencyConverterApiProvider
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderManager
 * @see \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface
 * @see plugin_api
 */
abstract class CurrencyConverterApiProviderBase extends PluginBase implements CurrencyConverterApiProviderInterface, ContainerFactoryPluginInterface {

  use UseCacheBackendTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The config 'currency_converter_api.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $currencyConverterApiConfig;

  /**
   * The API url.
   *
   * @var string
   */
  protected $apiUrl;

  /**
   * Constructs a new object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, LoggerChannelFactoryInterface $logger, TimeInterface $time, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->cacheBackend = $cache_backend;
    $this->logger = $logger->get('currency_converter_api');
    $this->time = $time;
    $this->currencyConverterApiConfig = $config_factory->get('currency_converter_api.settings');
    $this->apiUrl = $this->getApiUrl();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('datetime.time'),
      $container->get('config.factory'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getApiUrl() {
    return $this->pluginDefinition['api_url'];
  }

}
