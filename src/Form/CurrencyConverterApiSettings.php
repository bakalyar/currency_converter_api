<?php

namespace Drupal\currency_converter_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\currency_converter_api\CurrencyConverterApiProviderManager;

/**
 * Configure settings for the module Currency Converter API.
 */
class CurrencyConverterApiSettings extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The Currency Converter API Provider plugin manager.
   *
   * @var \Drupal\currency_converter_api\CurrencyConverterApiProviderManager
   */
  protected $currencyConverterApiProviderManager;

  /**
   * The config 'currency_converter_api.settings'.
   *
   * @var array
   */
  protected $currencyConverterApiConfig;

  /**
   * Constructs a new CurrencyConverterApiSettings form.
   *
   * @param \Drupal\currency_converter_api\CurrencyConverterApiProviderManager $currency_converter_api_provider_manager
   *   The Currency Converter API Provider plugin manager.
   */
  public function __construct(CurrencyConverterApiProviderManager $currency_converter_api_provider_manager) {
    $this->currencyConverterApiProviderManager = $currency_converter_api_provider_manager;
    $this->currencyConverterApiConfig = $this->config('currency_converter_api.settings');
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'currency_converter_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'currency_converter_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all Currency Converter Api providers.
    $plugins = $this->currencyConverterApiProviderManager->getDefinitions();

    // Provide providers options.
    $api_provider_options = [];
    foreach ($plugins as $key => $plugin) {
      $api_provider_options[$key] = $plugin['name'];
    }

    $config_api_provider = $this->currencyConverterApiConfig->get('api_provider');
    $config_api_provider = $config_api_provider ?: 'free_currency_converter_api';
    $form['api_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('API provider'),
      '#options' => $api_provider_options,
      '#default_value' => $config_api_provider,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::getProviderDependentSettings',
      ],
    ];

    // Get selected provider.
    $selected_api_provider = $form_state->getValue('api_provider');
    $api_provider = $selected_api_provider ?: $config_api_provider;

    // Wrapper for ajax response which contains dependent currencies.
    $form['provider_dependent'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'provider-dependent'],
    ];

    if ($api_provider) {
      /* @var \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface $provider */
      $provider = $this->currencyConverterApiProviderManager->createInstance($api_provider);
      $form += $provider->getProviderSettingsForm($this->currencyConverterApiConfig->getRawData());
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback to get a form element with provider's settings.
   */
  public function getProviderDependentSettings(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#provider-dependent', $form['provider_dependent']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('currency_converter_api.settings');
    $clean_value_keys = array_merge($form_state->getCleanValueKeys(), ['submit']);
    // Get all dynamic values.
    $form_values = array_diff_key($form_state->getValues(), array_flip($clean_value_keys));

    foreach ($form_values as $key => $value) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
