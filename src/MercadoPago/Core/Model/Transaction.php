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
use MercadoPago\Core\Helper\Data as mpHelper;

class Transaction
{
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';

    private Builder $_transactionBuilder;

    private MercadopagoData $_mercadoPagoData;

    private TransactionRepositoryInterface $_transactionRepository;

    private FilterBuilder $_filterBuilder;

    private SearchCriteriaBuilder $_searchCriteriaBuilder;

    private SortOrderBuilder $_sortOrderBuilder;

     /**
     * @var mpHelper
     */
    protected $_mpHelper;

    public function __construct(Builder $transactionBuilder,
        MercadopagoData $mercadoPagoData,
        TransactionRepositoryInterface $transactionRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        mpHelper $mpHelper
    )
    {
        $this->_transactionBuilder = $transactionBuilder;
        $this->_mercadoPagoData = $mercadoPagoData;
        $this->_transactionRepository = $transactionRepository;
        $this->_filterBuilder = $filterBuilder;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_sortOrderBuilder = $sortOrderBuilder;
        $this->_mpHelper = $mpHelper;
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
    ): void
    {
        try {
            $this->_mpHelper->log('Transaction - Start', 'mercadopago.log');
            $result = $this->getListTransactionByOrderId($order->getIncrementId());
            $this->_mpHelper->log('Transaction - getListTransactionByOrderId', 'mercadopago.log', $order->getIncrementId());
            $this->_mpHelper->log('Transaction - getListTransactionByOrderId Result', 'mercadopago.log', $result);

            if (empty($result)){
                return;
            }

            $transactionId = $result[0]->getTransactionId();
            $transactionIdIncrement = $transactionId + "_" + sizeof($result);

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
            $this->_mercadoPagoData->log("Failed creating transaction id $transactionId with message {$e->getMessage()}");
        }
    }

    public function save($transaction)
    {
        return $this->_transactionRepository->save($transaction);
    }

    private function statusTransform(string $status): string
    {
        switch ($status)
        {
            case self::STATUS_APPROVED:
                return TransactionInterface::TYPE_CAPTURE;
            case self::STATUS_PENDING:
                return TransactionInterface::TYPE_AUTH;
            case self::STATUS_PROCESSING:
                return TransactionInterface::TYPE_AUTH;
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
