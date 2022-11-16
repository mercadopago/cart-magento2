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
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CHARGED_BACK = 'charged_back';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CANCELED = 'canceled';

    /**
     * @var Builder
     */
    private $_transactionBuilder;

    /**
     * @var MercadopagoData
     */
    private $_mercadoPagoData;

    /**
     * @var TransactionRepositoryInterface
     */
    private $_transactionRepository;

    /**
     * @var FilterBuilder
     */
    private $_filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $_sortOrderBuilder;

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
            $orderId = $order->getIncrementId();

            $this->_mercadoPagoData->log("Create Transaction - Order $orderId");

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
            $this->_mercadoPagoData->log("Update Transaction - Order $orderId - Status $status");
            $result = $this->getListTransactionByOrderId($orderId);
            if (empty($result)) {
                return;
            }

            $transactionId = reset($result)->getTxnId();
            $transactionIdIncrement = $transactionId . "_" . sizeof($result);
            $typeStatus = $this->statusTransform($status);

            $payment->setTransactionId($transactionIdIncrement);
            $payment->setParentTransactionId($transactionId);
            $payment->setIsTransactionClosed($typeStatus == TransactionInterface::TYPE_ORDER ? false : true);

            $this->_transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionIdIncrement)
                ->build($typeStatus);
        } catch (\Exception $e) {
            $this->_mercadoPagoData->log("Failed creating transaction to order $orderId with message {$e->getMessage()}");
        }
    }

    public function save(TransactionInterface $transaction): TransactionInterface
    {
        return $this->_transactionRepository->save($transaction);
    }

    private function statusTransform(string $status): string
    {
        switch ($status) {
            case self::STATUS_APPROVED:
                return TransactionInterface::TYPE_CAPTURE;
            case self::STATUS_CANCELLED:
            case self::STATUS_CANCELED:
            case self::STATUS_REJECTED:
                return TransactionInterface::TYPE_VOID;
            case self::STATUS_REFUNDED:
            case self::STATUS_CHARGED_BACK:
                return TransactionInterface::TYPE_REFUND;
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
