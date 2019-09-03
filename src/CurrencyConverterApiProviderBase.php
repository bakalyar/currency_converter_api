<?php

namespace Drupal\currency_converter_api;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;

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
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface;
   */
  protected $logger;

  /**
   * The API url.
   *
   * @var string
   */
  protected $apiUrl;

  /**
   * The factory for expirable key value stores.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpirableFactory;

  /**
   * Constructs a new object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, LoggerChannelFactoryInterface $logger, KeyValueExpirableFactoryInterface $key_value_expirable_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->logger = $logger->get('currency_converter_api');
    $this->keyValueExpirableFactory = $key_value_expirable_factory;
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
      $container->get('keyvalue.expirable')
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
