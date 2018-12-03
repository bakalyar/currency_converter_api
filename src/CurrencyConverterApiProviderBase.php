<?php

namespace Drupal\currency_converter_api;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;

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
   * Constructs a new object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
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
  public function getHost() {
    return $this->pluginDefinition['host'];
  }

}
