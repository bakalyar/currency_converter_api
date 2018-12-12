<?php

namespace Drupal\currency_converter_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Element\Checkboxes;
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
   */
  protected $currencyConverterApiConfig;

  /**
   * If form loaded then all next requests are via ajax.
   *
   * @var bool
   */
  protected $formLoaded;

  /**
   * Constructs a new CurrencyConverterApiSettings form.
   *
   * @param \Drupal\currency_converter_api\CurrencyConverterApiProviderManager $currency_converter_api_provider_manager
   *   The Currency Converter API Provider plugin manager.
   */
  public function __construct(CurrencyConverterApiProviderManager $currency_converter_api_provider_manager) {
    $this->currencyConverterApiProviderManager = $currency_converter_api_provider_manager;
    $this->currencyConverterApiConfig = $this->config('currency_converter_api.settings');
    $this->formLoaded = FALSE;
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
        'callback' => '::getProviderDependentCurrencies',
      ],
    ];

    // Get selected provider.
    $selected_api_provider = $form_state->getValue('api_provider');
    $api_provider = $this->formLoaded && $selected_api_provider ? $selected_api_provider : $config_api_provider;

    // Wrapper for ajax response which contains dependent currencies.
    $form['provider_dependent'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'provider-dependent'],
    ];

    if ($api_provider) {
      // Provide all currencies for the selected provider.
      /* @var \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface $provider */
      $provider = $this->currencyConverterApiProviderManager->createInstance($api_provider);
      $allowed_currencies_array = $provider->getAllCurrencies();
      $allowed_currencies_options = [];

      foreach ($allowed_currencies_array as $key => $currency) {
        $allowed_currencies_options[$key] = $currency['currencyName'];
      }
      asort($allowed_currencies_options);
      $allowed_currencies = $this->currencyConverterApiConfig->get('allowed_currencies');

      $form['provider_dependent']['allowed_currencies'] = [
        '#type' => 'checkboxes',
        '#options' => $allowed_currencies_options,
        '#title' => $this->t('Allowed currencies'),
        '#default_value' => is_array($allowed_currencies) ? $allowed_currencies : [],
        '#ajax' => [
          'callback' => '::getSelectedAllowedCurrencies',
        ],
      ];

      // Wrapper for ajax response which should contain selected currencies.
      $form['provider_dependent']['currencies_dependent'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'currencies-dependent'],
      ];

      // Provide all selected currencies.
      $selected_allowed_currencies = $form_state->getValue('allowed_currencies');
      if (is_array($selected_allowed_currencies)) {
        $selected_allowed_currencies = Checkboxes::getCheckedCheckboxes($selected_allowed_currencies);
      }
      $config_allowed_currencies = $this->currencyConverterApiConfig->get('allowed_currencies');
      $allowed_currencies_keys = $selected_allowed_currencies ?: $config_allowed_currencies;

      if (is_array($allowed_currencies_keys)) {
        $main_currency_allowed_currencies = array_filter($allowed_currencies_options, function ($key) use ($allowed_currencies_keys) {
          return in_array($key, $allowed_currencies_keys);
        }, ARRAY_FILTER_USE_KEY);
        $allowed_currencies_options = array_merge(['' => $this->t('- Select -')], $main_currency_allowed_currencies);

        $main_currency = $this->currencyConverterApiConfig->get('main_currency');

        $form['provider_dependent']['currencies_dependent']['main_currency'] = [
          '#type' => 'select',
          '#title' => $this->t('Main currency'),
          '#options' => $allowed_currencies_options ?: [],
          '#default_value' => $main_currency ?: '',
          '#required' => TRUE,
        ];
      }
    }

    $this->formLoaded = TRUE;

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback to get a form element with provider's currencies.
   */
  public function getProviderDependentCurrencies(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#provider-dependent', $form['provider_dependent']));
    return $response;
  }

  /**
   * Ajax callback to get a form element with selected allowed currencies.
   */
  public function getSelectedAllowedCurrencies(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#currencies-dependent', $form['provider_dependent']['currencies_dependent']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('currency_converter_api.settings');
    $allowed_currencies = $form_state->getValue('allowed_currencies');
    if (is_array($allowed_currencies)) {
      $config->set('allowed_currencies', Checkboxes::getCheckedCheckboxes($allowed_currencies));
    }

    $config_keys = ['api_provider', 'main_currency'];
    foreach ($config_keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }
}