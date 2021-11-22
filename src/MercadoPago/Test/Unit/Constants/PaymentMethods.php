<?php

namespace MercadoPago\Test\Unit\Constants;

class PaymentMethods 
{
    
    public const PAYMENT_METHODS_SUCCESS = [
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

    public const EMPTY_PAYMENT_METHODS = [
        0 => [
            'value' => '',
            'label' => 'Accept all payment methods',
        ]
    ];

    public const PAYMENT_METHODS_URI = '/v1/payment_methods';
}