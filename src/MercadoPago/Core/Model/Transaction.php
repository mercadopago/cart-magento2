<?php

namespace MercadoPago\Core\Model;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use MercadoPago\Core\Helper\Data as MercadopagoData;

class Transaction
{
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';

    private Builder $_transactionBuilder;

    private MercadopagoData $_mercadoPagoData;

    public function __construct(Builder $transactionBuilder, MercadopagoData $mercadoPagoData)
    {
        $this->_transactionBuilder = $transactionBuilder;
        $this->_mercadoPagoData = $mercadoPagoData;
    }

    public function create(
        Payment $payment,
        Order $order,
        string $transactionId,
        array $additionalData,
        string $status
    ): void
    {
        try {
            $payment->setTransactionId($transactionId);

            $this->_transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionId)
                ->setAdditionalInformation([Payment\Transaction::RAW_DETAILS => $additionalData])
                ->build($this->statusTransform($status));
        } catch (\Exception $e) {
            $this->_mercadoPagoData->log("Failed creating transaction id $transactionId with message {$e->getMessage()}");
        }
    }

    private function statusTransform(string $status): string
    {
        switch ($status)
        {
            case self::STATUS_APPROVED:
                return TransactionInterface::TYPE_CAPTURE;
            case self::STATUS_PENDING:
                return TransactionInterface::TYPE_AUTH;
            default:
                return TransactionInterface::TYPE_ORDER;
        }
    }
}
