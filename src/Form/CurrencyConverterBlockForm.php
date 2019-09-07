<?php

namespace Drupal\currency_converter_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\currency_converter_api\CurrencyConverterApiProviderManager;

/**
 * Builds the form for converting currency from one to another.
 *
 * @internal
 */
class CurrencyConverterBlockForm extends FormBase {

  /**
   * The Currency Converter API Provider plugin manager.
   *
   * @var \Drupal\currency_converter_api\CurrencyConverterApiProviderManager
   */
  protected $currencyConverterApiProviderManager;

  /**
   * The Currency Converter API provider.
   *
   * @var \Drupal\currency_converter_api\CurrencyConverterApiProviderInterface
   */
  protected $currencyConverterApiProvider = NULL;

  /**
   * Constructs a new SearchBlockForm.
   *
   * @param \Drupal\currency_converter_api\CurrencyConverterApiProviderManager $currency_converter_api_provider_manager
   *   The Currency Converter API Provider plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(CurrencyConverterApiProviderManager $currency_converter_api_provider_manager, ConfigFactoryInterface $config_factory) {
    $this->currencyConverterApiProviderManager = $currency_converter_api_provider_manager;
    $api_provider_name = $config_factory->get('currency_converter_api.settings')->get('api_provider');

    if (!empty($api_provider_name)) {
      $this->currencyConverterApiProvider = $this->currencyConverterApiProviderManager->createInstance($api_provider_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.currency_converter_api'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'currency_converter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->currencyConverterApiProvider) {
      return ['#markup' => $this->t('Please configure the provider')];
    }

    // Get all currencies for the selected provider.
    $all_currencies = $this->currencyConverterApiProvider->getAllCurrencies();

    $form['amount'] = [
      '#type' => 'textfield',
      '#title' => t('Amount'),
      '#default_value' => 1,
      '#required' => TRUE,
    ];
    $form['from'] = [
      '#type' => 'select',
      '#title' => t('From'),
      '#options' => $all_currencies,
    ];
    $form['to'] = [
      '#type' => 'select',
      '#title' => t('To'),
      '#options' => $all_currencies,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#ajax' => [
        'callback' => '::getResult',
        'wrapper' => 'result',
      ],
      '#value' => $this->t('Convert'),
    ];

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'result'],
    ];
    $form['container']['result'] = [
      '#type' => 'markup',
      '#markup' => '',
    ];

    $form['#attached']['library'][] = 'currency_converter_api/currency_converter_api.library';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Callback for getting a result of the converting.
   */
  public function getResult(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $from = $form_state->getValue('from');
    $to = $form_state->getValue('to');

    $rate = $this->currencyConverterApiProvider->convert($from, $to);
    $rate_rounded = round($rate, 2);
    $result = round($amount * $rate, 2);

    $element = $form['container'];
    $element['result']['#markup'] = '<p>1' . $from . ' = ' . $rate_rounded . $to . '</p>';
    $element['result']['#markup'] .= '<p><b>' . $amount . $from . ' = ' . $result . $to . '</b></p>';

    return $element;
  }

}
