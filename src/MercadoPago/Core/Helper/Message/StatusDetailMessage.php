<?php

namespace MercadoPago\Core\Helper\Message;

/**
 * Map Payment Messages with the Credit Card Payment response detail
 * @package MercadoPago\Core\Helper
 */
class StatusDetailMessage extends AbstractMessage
{
    /**
     * Map error messages
     *
     * @var array
     */
    protected $messagesMap = [
        "cc_rejected_bad_filled_card_number" => 'Check the card number.',
        "cc_rejected_bad_filled_date" => 'Check the expiration date.',
        "cc_rejected_bad_filled_other" => 'Check the data.',
        "cc_rejected_bad_filled_security_code" => 'Check the security code.',
        "cc_rejected_blacklist" => 'We could not process your payment.',
        "cc_rejected_call_for_authorize" => 'Contact your card issuer and authorize the payment to Mercado Pago.',
        "cc_rejected_card_disabled" => 'Contact your card issuer to activate your card. The phone is on the back of your card.',
        "cc_rejected_card_error" => 'We could not process your payment.',
        "cc_rejected_duplicated_payment" => 'You already made a payment by that value. If you need to repay, use another card or other payment method.',
        "cc_rejected_high_risk" => 'Your payment was rejected. Choose another payment method, we recommend cash methods.',
        "cc_rejected_insufficient_amount" => 'Card with insufficient limit.',
        "cc_rejected_invalid_installments" => 'The card does not process payments with this number of installments.',
        "cc_rejected_max_attempts" => 'You have got to the limit of allowed attempts. Choose another card or another payment method.',
        "cc_rejected_other_reason" => 'The card company did not process the payment. Please contact your card issue.',
    ];

    /**
     * Return array map error messages
     *
     * @return array
     */
    public function getMessageMap()
    {
        return $this->messagesMap;
    }
}
