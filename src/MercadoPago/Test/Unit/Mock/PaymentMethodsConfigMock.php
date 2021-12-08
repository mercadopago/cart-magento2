<?php

namespace MercadoPago\Test\Unit\Mock;

class PaymentMethodsConfigMock
{   
  public const PAYMENT_METHODS_CONFIG_SUCCESS = [
    [
      'value' => '',
      'label' => 'Accept all payment methods',
    ],
    [
      'value' => 'paycash',
      'label' => 'PayCash (7 Eleven, Circle K, Soriana, Extra, Calimax)',
    ],
    [
      'value' => 'meliplace',
      'label' => 'Meliplaces',
    ],
    [
      'value' => 'banamex',
      'label' => 'Citibanamex',
    ],
    [
      'value' => 'bancomer',
      'label' => 'BBVA Bancomer',
    ],
    [
      'value' => 'serfin',
      'label' => 'Santander',
    ],
    [
      'value' => 'oxxo',
      'label' => 'OXXO',
    ],
  ];

  public const EMPTY_PAYMENT_METHODS_CONFIG = [
    [
      'value' => '',
      'label' => 'Accept all payment methods',
    ]
  ];
}