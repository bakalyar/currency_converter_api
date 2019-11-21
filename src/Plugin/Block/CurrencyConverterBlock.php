<?php

namespace Drupal\currency_converter_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\currency_converter_api\CurrencyConverterApiProviderManager;

/**
 * Provides a 'Currency Converter' block.
 *
 * @Block(
 *   id = "currency_converter_block",
 *   admin_label = @Translation("Currency converter"),
 *   category = @Translation("Currency converter API"),
 * )
 */
class CurrencyConverterBlock extends BlockBase implements FormInterface, ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The Currency Converter API Provider plugin manager.
   *
   * @var \Drupal\currency_converter_api\CurrencyConverterApiProviderManager
   */
  protected $currencyConverterApiProviderManager;

  /**
   * The Currency Converter API provider name.
   *
   * @var string
   */
  protected $currencyConverterApiProviderName;

  /**
   * Creates a CurrencyConverterBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\currency_converter_api\CurrencyConverterApiProviderManager $currency_converter_api_provider_manager
   *   The Currency Converter API Provider plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory, CurrencyConverterApiProviderManager $currency_converter_api_provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->currencyConverterApiProviderManager = $currency_converter_api_provider_manager;
    $this->currencyConverterApiProviderName = $config_factory->get('currency_converter_api.settings')->get('api_provider');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('config.factory'),
      $container->get('plugin.manager.currency_converter_api')
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
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Get all currencies for the selected provider.
    $provider = $this->currencyConverterApiProviderManager->createInstance($this->currencyConverterApiProviderName);
    $currencies = $provider->getAllCurrencies();

    $header = [
      ['data' => $this->t('Currency'), 'colspan' => '2'],
      $this->t('Enabled'),
      $this->t('Weight'),
    ];

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => ['id' => 'currency-converter-api-ordered-list-id'],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'currency-converter-api-ordered-list',
        ],
      ],
    ];

    $rows = [];
    $i = 0;
    foreach ($currencies as $id => $currency) {
      $weight = !empty($config['currencies_order'][$id]['weight']) ? $config['currencies_order'][$id]['weight'] : $i;
      $rows[$id]['#attributes']['class'][] = 'draggable';
      $rows[$id]['#weight'] = $weight;

      $rows[$id]['title'] = [
        '#prefix' => '<strong>',
        '#markup' => $currency,
        '#suffix' => '</strong>',
      ];
      $rows[$id]['name'] = [
        '#type' => 'hidden',
        '#value' => $currency,
      ];

      $rows[$id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $currency,
        '#title_display' => 'invisible',
        '#default_value' => isset($config['currencies_order'][$id]['enabled']) ? $config['currencies_order'][$id]['enabled'] : TRUE,
      ];
      $rows[$id]['weight'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', ['@title' => $currency]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#size' => 4,
        '#attributes' => ['class' => ['currency-converter-api-ordered-list']],
      ];

      $i++;
    }

    $weights = array_column($rows, '#weight');
    array_multisort($weights, SORT_ASC, $rows);

    $form['currencies'] = array_merge($table, $rows);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    $currencies = $values['currencies'];
    $weights = array_column($currencies, 'weight');
    array_multisort($weights, SORT_ASC, $currencies);
    $this->configuration['currencies_order'] = $currencies;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->currencyConverterApiProviderName)) {
      return ['#markup' => $this->t('Please configure the provider')];
    }
    $config = $this->getConfiguration();

    // Get all currencies for the selected provider.
    $currencies_list = [];
    if (!empty($config['currencies_order'])) {
      $currencies = $config['currencies_order'];

      foreach ($currencies as $id => $currency) {
        if (!empty($currency['enabled']) && !empty($currency['name'])) {
          $currencies_list[$id] = $currency['name'];
        }
      }
    }
    else {
      $provider = $this->currencyConverterApiProviderManager->createInstance($this->currencyConverterApiProviderName);
      $currencies_list = $provider->getAllCurrencies();
    }

    $form['amount'] = [
      '#type' => 'textfield',
      '#title' => t('Amount'),
      '#default_value' => 1,
      '#suffix' => '<div class="amount-validate-message"></div>',
      '#ajax' => [
        'callback' => '::validateAmount',
        'wrapper' => 'amount-validate-message',
        'disable-refocus' => TRUE,
      ],
    ];
    $form['amount_err_msg_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'amount-validate-message'],
    ];

    $form['from'] = [
      '#type' => 'select',
      '#title' => t('From'),
      '#options' => $currencies_list,
    ];
    $form['to'] = [
      '#type' => 'select',
      '#title' => t('To'),
      '#options' => $currencies_list,
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

    $form['result_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'result'],
    ];
    $form['result_wrapper']['result'] = [
      '#type' => 'markup',
      '#markup' => '',
    ];

    $form['#attached']['library'][] = 'currency_converter_api/currency_converter_api.library';

    return $form;
  }

  /**
   * Validates amount.
   */
  public function validateAmount(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $message = '';
    $element = $form['amount_err_msg_wrapper'];
    if (!is_numeric($amount)) {
      $message = '<div class="form form-item--error-message">' . $this->t('Amount has to be a number and higher than 0.') . '</div>';
    }
    $element['amount_err_msg']['#markup'] = $message;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Add to settings ability turn off ajax.
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Callback for getting a result of the converting.
   */
  public function getResult(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $from = $form_state->getValue('from');
    $to = $form_state->getValue('to');

    $rate = $this->currencyConverterApiProviderManager->createInstance($this->currencyConverterApiProviderName)->convert($from, $to);
    $rate_rounded = round($rate, 2);
    $result = round($amount * $rate, 2);

    $element = $form['result_wrapper'];
    if ($amount != 1) {
      $element['result']['#markup'] = '<p>1' . $from . ' = ' . $rate_rounded . $to . '</p>';
    }
    $element['result']['#markup'] .= '<p><b>' . $amount . $from . ' = ' . $result . $to . '</b></p>';

    return $element;
  }

}
