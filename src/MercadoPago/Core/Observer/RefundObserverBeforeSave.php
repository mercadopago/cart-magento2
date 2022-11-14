<?php

namespace MercadoPago\Core\Observer;

use Magento\Framework\Event\ObserverInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Model\Transaction;
use Magento\Store\Model\ScopeInterface;

/**
 * Class RefundObserverBeforeSave
 *
 * @package MercadoPago\Core\Observer
 *
 * @codeCoverageIgnore
 */
class RefundObserverBeforeSave implements ObserverInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * RefundObserverBeforeSave constructor.
     *
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Framework\App\Action\Context $context
     * @param \MercadoPago\Core\Helper\Data $dataHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MercadoPago\Core\Model\Transaction $transaction
     */
    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \MercadoPago\Core\Helper\Data $dataHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Model\Transaction $transaction
    ) {
        $this->session = $session;
        $this->messageManager = $context->getMessageManager();
        $this->dataHelper = $dataHelper;
        $this->scopeConfig = $scopeConfig;
        $this->transaction = $transaction;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $creditMemo = $observer->getData('creditmemo');
        $order = $creditMemo->getOrder();
        $this->creditMemoRefundBeforeSave($order, $creditMemo);
    }

    /**
     * @param $order      \Magento\Sales\Model\Order
     * @param $creditMemo \Magento\Sales\Model\Order\Creditmemo
     */
    protected function creditMemoRefundBeforeSave($order, $creditMemo)
    {
        // Does not repeat the return of payment, if it is done through the Mercado Pago
        if ($order->getExternalRequest()) {
            return;
        }

        //get payment order object
        $paymentOrder = $order->getPayment();
        $paymentMethod = $paymentOrder->getMethodInstance()->getCode();
        if (!($paymentMethod == 'mercadopago_basic'
            || $paymentMethod == 'mercadopago_custom'
            || $paymentMethod == 'mercadopago_customticket'
            || $paymentMethod == 'mercadopago_custom_bank_transfer'
            || $paymentMethod == 'mercadopago_custom_pix')) {
            return;
        }

        //Check refund available
        $refundAvailable = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_REFUND_AVAILABLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$refundAvailable) {
            $this->dataHelper->log("RefundObserverBeforeSave::creditMemoRefundBeforeSave - Refund not enabled", 'mercadopago-custom.log');
            return;
        }

        //Get amount refund
        $amountRefund = $creditMemo->getGrandTotal();
        if ($amountRefund <= 0) {
            $this->throwRefundException(__("The refunded amount must be greater than 0."));
            return;
        }

        //Get payment info
        $paymentResponse = $paymentOrder->getAdditionalInformation("paymentResponse");
        if (!isset($paymentResponse['id'])) {
            $this->throwRefundException(__("Refund can not be executed because the payment id was not found."));
            return;
        }

        //Get access token
        $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (empty($accessToken)) {
            $this->throwRefundException(__("Refund can not be performed because ACCESS_TOKEN has not been configured."));
            return;
        }

        //Get Payment Id
        $paymentID = $paymentResponse['id'];

        //Get Sdk Instance
        $mp = $this->dataHelper->getApiInstance($accessToken);

        //Get Payment detail
        $response = $mp->get("/v1/payments/" . $paymentID);

        if ($response['status'] == 200) {

            if ($response['response']['status'] == 'approved') {

                $responseRefund = null;


                if ($amountRefund == $response['response']['transaction_amount']) {
                    //total refund
                    $responseRefund = $mp->post("/v1/payments/$paymentID/refunds", null);
                } else {
                    //partial refund
                    $params = [
                        "amount" => $amountRefund,
                    ];
                    $responseRefund = $mp->post("/v1/payments/$paymentID/refunds", $params);
                }

                if (is_null($responseRefund)) {
                    $this->throwRefundException(__("Could not process the refund, The Mercado Pago API returned an unexpected error. Check the log files."));
                }

                if ($responseRefund['status'] == 200 || $responseRefund['status'] == 201) {
                    $successMessageRefund = "Mercado Pago - " . __('Refund of %1 was processed successfully.', $amountRefund);
                    $this->messageManager->addSuccessMessage($successMessageRefund);
                    $this->dataHelper->log("RefundObserverBeforeSave::creditMemoRefundBeforeSave - " . $successMessageRefund, 'mercadopago-custom.log', $responseRefund);

                    if ($this->scopeConfig->isSetFlag(ConfigData::PATH_ADVANCED_SAVE_TRANSACTION, ScopeInterface::SCOPE_STORE)) {
                        $responseUpdate = $mp->get("/v1/payments/" . $paymentID);
                        if ($responseUpdate['response']['status_detail'] == 'partially_refunded') {
                            $this->transaction->update($paymentOrder, $order, Transaction::STATUS_REFUNDED);
                        } else {
                            $this->transaction->update($paymentOrder, $order, $responseUpdate['response']['status']);
                        }
                    }

                } else {
                    $this->throwRefundException(__("Could not process the refund, The Mercado Pago API returned an unexpected error. Check the log files."), $responseRefund);
                }
            } else {
                $this->throwRefundException(__("The payment has not been refunded, you can only refund payments with status approved. The payment status is ") . $response['response']['status'] . ".");
            }
        } else {
            $this->throwRefundException(__("An error occurred while getting the status of the payment in the API Mercado Pago."), $response);
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function throwRefundException($message, $data = [])
    {
        $this->dataHelper->log("RefundObserverBeforeSave::sendRefundRequest - " . $message, 'mercadopago-custom.log', $data);
        $this->messageManager->addErrorMessage('Mercado Pago - ' . $message);
        throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase('Mercado Pago - ' . $message));
    }
}
