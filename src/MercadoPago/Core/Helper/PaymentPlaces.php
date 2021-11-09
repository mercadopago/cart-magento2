<?php

namespace MercadoPago\Core\Helper;

class PaymentPlaces
{
    /**
    * @return array
    */
    public static function getPaymentPlaces($paymentId)
    {
        $payment_places = [
            "paycash" => [
                [
                    "payment_option_id" => "7eleven",
                    "name"              => "7 Eleven",
                    "status"            => "active",
                    "thumbnail"         => "https://http2.mlstatic.com/storage/logos-api-admin/417ddb90-34ab-11e9-b8b8-15cad73057aa-s.png"
                ],
                [
                    "payment_option_id" => "circlek",
                    "name"              => "Circle K",
                    "status"            => "active",
                    "thumbnail"         => "https://http2.mlstatic.com/storage/logos-api-admin/6f952c90-34ab-11e9-8357-f13e9b392369-s.png"
                ],
                [
                    "payment_option_id" => "soriana",
                    "name"              => "Soriana",
                    "status"            => "active",
                    "thumbnail"         => "https://http2.mlstatic.com/storage/logos-api-admin/dac0bf10-01eb-11ec-ad92-052532916206-s.png"
                ],
                [
                    "payment_option_id" => "extra",
                    "name"              => "Extra",
                    "status"            => "active",
                    "thumbnail"         => "https://http2.mlstatic.com/storage/logos-api-admin/9c8f26b0-34ab-11e9-b8b8-15cad73057aa-s.png"
                ],
                [
                    "payment_option_id" => "calimax",
                    "name"              => "Calimax",
                    "status"            => "active",
                    "thumbnail"         => "https://http2.mlstatic.com/storage/logos-api-admin/52efa730-01ec-11ec-ba6b-c5f27048193b-s.png"
                ]
            ],
        ];

        return $payment_places[$paymentId] ?? [];
    }
}
