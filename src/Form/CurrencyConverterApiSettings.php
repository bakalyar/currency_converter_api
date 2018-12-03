<?php

namespace Drupal\currency_converter_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for the module Currency Converter API.
 */
class CurrencyConverterApiSettings extends ConfigFormBase {

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
    $config = $this->config('currency_converter_api.settings');
    $manager = \Drupal::service('plugin.manager.currency_converter_api');
    $plugins = $manager->getDefinitions();
    $api_provider_options = [];

    foreach ($plugins as $key => $plugin) {
      $api_provider_options[$key] = $plugin['name'];
    }
    $api_provider = $config->get('api_provider');
    $form['api_provider'] = array(
      '#type' => 'select',
      '#title' => $this->t('API provider'),
      '#options' => $api_provider_options,
      '#default_value' => $api_provider ?: $api_provider_options['free_currency_converter_api'],
      '#required' => TRUE,
    );

    /* @var \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface $provider */
    $provider = $manager->createInstance('free_currency_converter_api');
    $allowed_currencies_array = $provider->getAllCurrencies();
    $allowed_currencies_options = [];

    foreach ($allowed_currencies_array as $key => $currency) {
      $allowed_currencies_options[$key] = $currency['currencyName'];
    }
    asort($allowed_currencies_options);

    $allowed_currencies = $config->get('allowed_currencies');
    $form['allowed_currencies'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed currencies'),
      '#default_value' => $allowed_currencies ?: array_keys($allowed_currencies_options),
      '#options' => $allowed_currencies_options,
    );

    $main_currency = $config->get('main_currency');
    $form['main_currency'] = array(
      '#type' => 'select',
      '#title' => $this->t('Main currency'),
      '#options' => $allowed_currencies_options,
      '#default_value' => $main_currency ?: $allowed_currencies_options['eur'],
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('currency_converter_api.settings');
    $config_keys = ['api_provider', 'allowed_currencies', 'main_currency'];

    foreach ($config_keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }
}