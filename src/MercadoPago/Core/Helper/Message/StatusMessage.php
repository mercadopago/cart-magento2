<?php

namespace MercadoPago\Core\Helper\Message;

/**
 * Payment response user friendly messages
 */
class StatusMessage extends AbstractMessage
{
    /**
     * maps messages by status
     *
     * @var array
     */
    protected $messagesMap = [
        "approved" => [
            'title' => 'Done, your payment was accredited!',
            'message' => ''
        ],

        "in_process" => [
            'title' => 'We are processing the payment.',
            'message' => 'In less than an hour we will send you by e-mail the result.'
        ],

        "authorized" => [
            'title' => 'We are processing the payment.',
            'message' => 'In less than an hour we will send you by e-mail the result.'
        ],

        "pending" => [
            'title' => 'We are processing the payment.',
            'message' => ''
        ],

        "rejected" => [
            'title' => 'We could not process your payment.',
            'message' => ''
        ],

        "cancelled" => [
            'title' => 'Payments were canceled.',
            'message' => 'Contact for more information.'
        ],

        "other" => [
            'title' => 'Thank you for your purchase!',
            'message' => ''
        ]
    ];

    /**
     * return array map
     *
     * @return array
     */
    public function getMessageMap()
    {
        return $this->messagesMap;
    }
}
