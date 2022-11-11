<?php

namespace MercadoPago\Core\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Sales\Api\TransactionRepositoryInterface;
use MercadoPago\Core\Helper\Data as MercadopagoData;
use Magento\Framework\Data\Collection;

class Transaction
{
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROCESS = 'in_process';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CHARGED_BACK = 'charged_back';
    public const STATUS_IN_MEDIATION = 'in_mediation';
    public const STATUS_REFUNDED = 'refunded';
    //public const STATUS_PARTIALLY_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CANCELED = 'canceled';
    //public const STATUS_REFUND_AVAILABLE = 'refunded_available';
    //public const STATUS_CANCEL_AVAILABLE = 'cancel_available';

    private Builder $_transactionBuilder;

    private MercadopagoData $_mercadoPagoData;

    private TransactionRepositoryInterface $_transactionRepository;

    private FilterBuilder $_filterBuilder;

    private SearchCriteriaBuilder $_searchCriteriaBuilder;

    private SortOrderBuilder $_sortOrderBuilder;

    public function __construct(
        Builder $transactionBuilder,
        MercadopagoData $mercadoPagoData,
        TransactionRepositoryInterface $transactionRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder
    )
    {
        $this->_transactionBuilder = $transactionBuilder;
        $this->_mercadoPagoData = $mercadoPagoData;
        $this->_transactionRepository = $transactionRepository;
        $this->_filterBuilder = $filterBuilder;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_sortOrderBuilder = $sortOrderBuilder;
    }

    public function create(
        Payment $payment,
        Order $order,
        string $transactionId
    )
    {
        try {
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(false);

            return $this->_transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionId)
                ->build(TransactionInterface::TYPE_ORDER);
        } catch (\Exception $e) {
            $this->_mercadoPagoData->log("Failed creating transaction id $transactionId with message {$e->getMessage()}");
        }
    }

    public function update(
        Payment $payment,
        Order $order,
        string $status
    ): void {
        $orderId = $order->getIncrementId();
        try {
            $this->_mercadoPagoData->log('Transaction - Start');
            $this->_mercadoPagoData->log('Transaction - getListTransactionByOrderId', 'transaction', $orderId);
            $result = $this->getListTransactionByOrderId($orderId);
            $this->_mercadoPagoData->log('Transaction - getListTransactionByOrderId Result', 'transaction', $result);

            if (empty($result)) {
                return;
            }
            $transactionId = reset($result)->getTxnId();
            $this->_mercadoPagoData->log('Transaction - transactionId', 'transaction', $transactionId);

            $transactionIdIncrement = $transactionId . "_" . sizeof($result);
            $this->_mercadoPagoData->log("Transaction - transactionId $transactionId - transactionIdIncrement $transactionIdIncrement");

            $payment->setTransactionId($transactionIdIncrement);
            $payment->setParentTransactionId($transactionId);
            $payment->setIsTransactionClosed(true);

            $this->_transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionIdIncrement)
                ->build($this->statusTransform($status));
        } catch (\Exception $e) {
            $this->_mercadoPagoData->log("Failed creating transaction to order $orderId with message {$e->getMessage()}");
        }
    }

    public function save(
        TransactionInterface $transaction
    ): TransactionInterface {
        return $this->_transactionRepository->save($transaction);
    }

    private function statusTransform(string $status): string
    {
        switch ($status) {
            case self::STATUS_APPROVED:
                return TransactionInterface::TYPE_CAPTURE;
            case (self::STATUS_CANCELLED || self::STATUS_REJECTED || self::STATUS_CANCELED):
                return TransactionInterface::TYPE_VOID;
            case (self::STATUS_REFUNDED || self::STATUS_CHARGED_BACK):
                return TransactionInterface::TYPE_REFUNDED;
            default:
                return TransactionInterface::TYPE_ORDER;
        }
    }

    private function getListTransactionByOrderId(string $orderId)
    {
        $orderFilter = $this->_filterBuilder
            ->setField(TransactionInterface::INCREMENT_ID)
            ->setValue($orderId)
            ->create();

        $transactionIdSort = $this->_sortOrderBuilder
            ->setField(TransactionInterface::TRANSACTION_ID)
            ->setDirection(Collection::SORT_ORDER_ASC)
            ->create();

        return $this->_transactionRepository->getList(
            $this->_searchCriteriaBuilder
                ->addFilters([$orderFilter])
                ->addSortOrder($transactionIdSort)
                ->create()
        )->getItems();
    }
}
