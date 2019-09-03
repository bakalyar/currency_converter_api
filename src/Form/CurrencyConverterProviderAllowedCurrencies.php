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
class CurrencyConverterProviderAllowedCurrencies extends ConfigFormBase implements ContainerInjectionInterface {

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
    // TODO: Move this setting to the block.
    $config_api_provider = $this->currencyConverterApiConfig->get('api_provider');

    if (empty($config_api_provider)) {
      return [
        '#markup' => $this->t('Please configure the provider'),
      ];
    }

    /* @var \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface $provider */
    $provider = $this->currencyConverterApiProviderManager->createInstance($config_api_provider);


    // Provide all currencies for the selected provider.
    $allowed_currencies_array = $provider->getAllCurrencies();
    $allowed_currencies_options = [];

    foreach ($allowed_currencies_array as $key => $currency) {
      $allowed_currencies_options[$key] = $currency['currencyName'];
    }
    asort($allowed_currencies_options);
    $allowed_currencies = $this->currencyConverterApiConfig->get('allowed_currencies');

    $form['allowed_currencies'] = [
      '#type' => 'checkboxes',
      '#options' => $allowed_currencies_options,
      '#title' => $this->t('Allowed currencies'),
      '#default_value' => is_array($allowed_currencies) ? $allowed_currencies : [],
    ];

    return parent::buildForm($form, $form_state);
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

    $config->save();

    parent::submitForm($form, $form_state);
  }
}