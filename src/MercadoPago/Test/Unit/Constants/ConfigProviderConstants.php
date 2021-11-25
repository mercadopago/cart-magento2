<?php

namespace MercadoPago\Test\Unit\Constants;

class ConfigProviderConstants
{

    public const PAYMENT_METHODS = [
        'response' => [
            0 => [
                'accreditation_time' => 2880,
                'additional_info_needed' => [
                    0 => 'cardholder_name',
                ],
                'deferred_capture' => 'unsupported',
                'financial_institutions' => [],
                'id' => 'debmaster',
                'max_allowed_amount' => 300000,
                'min_allowed_amount' => 5,
                'name' => 'Mastercard Débito',
                'payment_type_id' => 'debit_card',
                'payment_places' => [],
                'processing_modes' => [
                    0 => 'aggregator',
                ],
                'secure_thumbnail' => 'https://www.mercadopago.com/org-img/MP3/API/logos/debmaster.gif',
                'settings' => [
                    0 => [
                        'bin' => [
                            'exclusion_pattern' => '^(506302|506429)',
                            'installments_pattern' => '',
                            'pattern' => '^(506202|230868|506208|231018|542878|523595|506199|506305|506306|506307|506332|506333|588772|528430|551440|516016|524021|506269|535943|537030|526476|506323|551238|506259|529571|526354|539955|553467|516576|528074|534381|557604|506236|506257|532485|512280|516102|506250|506255|506304|526498|506212|539975|530113|506416|506414|550897|506392|506204|506249|529575|506201|506205|511265|538984|553800|554173|506275|557602|551515|523691|502275|506206|526194|526196|526197|551509|506415|511761|506383|506374|506402|506335|506265|535900|539944|506314|506411|506313|506245|506251|506386|526404|506274|506258|506353|506350|555924|506355|506410|506423|506424|506361|506247|506284|506352|506263|506432|506297|506287|506334|506364|506384|506320|506319|506344|506294|506283|506342|506282|506421|506393|506343|506439|506373|536783|549685|506367|506303|506254|506413|533922|516611|506337|506397|506398|506213|506277|506278|506279|506280|506377|511852|506422|506312|506441|506399|537938|506427|510586|538405|538088|506214|506215|506356|528480|506389|506351|506300|506354|506253|546378|516415|506309|533987|545290|545325|506311|506365|506380|506407|557920|557921|557922|557923|557924|506412|528598|531294|549672|506391|506428|511765|543924|506449|526400|551353|506405|506406|230884|230948|230951|557910|557909|557908|557907|557905|506217|545730|506218|589617|557906|517747|554628|504563|529093|517771|557991|551077|557561|550233|533609|551244|554627|504536|557875|557874|529028|525678|524711|520698|520694|520416|520116|517795|517721|517712|558426|554492|551081|517439|517440|549613|506228|557551|530686|518847|526424|516594|511114|506340|506270|506229|539177|559471|557671|511391|536324|543451|528514|538756|506458|555691)',
                        ],
                        'card_number' => [
                            'length' => 16,
                            'validation' => 'standard',
                        ],
                        'security_code' => [
                            'card_location' => 'back',
                            'length' => 3,
                            'mode' => 'mandatory',
                        ],
                    ],
                ],
                'status' => 'active',
                'thumbnail' => 'https://www.mercadopago.com/org-img/MP3/API/logos/debmaster.gif',
            ],
            1 => [
                'accreditation_time' => 2880,
                'additional_info_needed' => [
                    0 => 'cardholder_name',
                ],
                'deferred_capture' => 'supported',
                'financial_institutions' => [],
                'id' => 'amex',
                'max_allowed_amount' => 300000,
                'min_allowed_amount' => 1,
                'name' => 'American Express',
                'payment_type_id' => 'credit_card',
                'payment_places' => [],
                'processing_modes' => [
                    0 => 'aggregator',
                ],
                'secure_thumbnail' => 'https://www.mercadopago.com/org-img/MP3/API/logos/amex.gif',
                'settings' => [
                    0 => [
                        'bin' => [
                            'exclusion_pattern' => NULL,
                            'installments_pattern' => '^(?!341595|3603(2|4)|360732|36075(5|6)|360935|37159(3|5)|3747((5(8|9))|(62))|3751(3([0-9])|7(7|8))|3764((0([0-9]))|(1([0-8]))|(2(2|8|9))|(3(6|7))|(4[0-9]|5[0-8])|(6([1-7])|7[0-8]|8[0-9]|9(1|3)))|37652(0|9)|37660((1|2)|[5-9])|376((6(2[0-9]|3[5-9]|8[5-8]))|71[0-4])|3771(69|74)|37779[0-8]|3778(0[2-6]|1[3-6]|20)|37782(5|6)|3799(6[6-8]|7(5|7)))',
                            'pattern' => '^((34)|(37))',
                        ],
                        'card_number' => [
                            'length' => 15,
                            'validation' => 'standard',
                        ],
                        'security_code' => [
                            'card_location' => 'front',
                            'length' => 4,
                            'mode' => 'mandatory',
                        ],
                    ],
                ],
                'status' => 'active',
                'thumbnail' => 'http://img.mlstatic.com/org-img/MP3/API/logos/amex.gif',
            ],
            2 => [
                'accreditation_time' => 0,
                'additional_info_needed' => [
                  0 => 'identification_type',
                  1 => 'identification_number',
                  2 => 'entity_type',
                ],
                'deferred_capture' => 'unsupported',
                'financial_institutions' => [],
                'id' => 'paycash',
                'max_allowed_amount' => 60000,
                'min_allowed_amount' => 20,
                'name' => 'PayCash',
                'payment_type_id' => 'ticket',
                'processing_modes' => [
                  0 => 'aggregator',
                ],
                'secure_thumbnail' => 'https://www.mercadopago.com/org-img/MP3/API/logos/paycash.gif',
                'settings' => [],
                'status' => 'testing',
                'thumbnail' => 'https://www.mercadopago.com/org-img/MP3/API/logos/paycash.gif',
              ],
        ],
        'status' => 200,
    ];
}
