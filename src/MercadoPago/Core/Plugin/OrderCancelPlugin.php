<?php

namespace MercadoPago\Core\Plugin;

use Closure;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Model\Transaction;

/**
 * Class OrderCancelPlugin
 *
 * @package MercadoPago\Core\Plugin
 */
class OrderCancelPlugin
{

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Data
     */
    protected $dataHelper;

    protected $scopeConfig;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * OrderCancelPlugin constructor.
     * @param Context $context
     * @param Data $dataHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Transaction $transaction
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        ScopeConfigInterface $scopeConfig,
        Transaction $transaction
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->dataHelper     = $dataHelper;
        $this->scopeConfig    = $scopeConfig;
        $this->transaction    = $transaction;
    } //end __construct()

    /**
     * @param Order $order
     * @param Closure                   $proceed
     *
     * @return mixed
     */
    public function aroundCancel(Order $order, Closure $proceed)
    {
        $this->order = $order;
        $this->salesOrderBeforeCancel();
        $result = $proceed();

        return $result;
    } //end aroundCancel()

    /**
     * @throws LocalizedException
     */
    protected function salesOrderBeforeCancel()
    {
        // Does not repeat the return of payment, if it is done through the Mercado Pago
        if ($this->order->getExternalRequest()) {
            return;
        }

        $paymentMethod = $this->order->getPayment()->getMethodInstance()->getCode();
        if (false === ($paymentMethod === 'mercadopago_custom'
                || $paymentMethod === 'mercadopago_customticket'
                || $paymentMethod === 'mercadopago_custom_bank_transfer'
                || $paymentMethod === 'mercadopago_basic'
                || $paymentMethod === 'mercadopago_custom_pix')
        ) {
            return;
        }

        $cancelAvailable = $this->scopeConfig->getValue(ConfigData::PATH_ORDER_CANCEL_AVAILABLE, ScopeInterface::SCOPE_STORE);

        if (!$cancelAvailable) {
            $this->dataHelper->log('OrderCancelPlugin::salesOrderBeforeCancel - Cancellation not enabled', 'mercadopago-custom.log');
            return;
        }

        // Get payment info
        $paymentResponse = $this->order->getPayment()->getAdditionalInformation('paymentResponse');

        if (!isset($paymentResponse['id'])) {
            $this->throwCancelationException(__('Cancellation can not be executed because the payment id was not found.'));
            return;
        }

        $accessToken = $this->scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);

        if (empty($accessToken)) {
            $this->throwCancelationException(__('Cancellation can not be performed because ACCESS_TOKEN has not been configured.'));
            return;
        }

        // Get Payment Id
        $paymentID = $paymentResponse['id'];

        // Get Sdk Instance
        $mp = $this->dataHelper->getApiInstance($accessToken);

        // Get Payment detail
        $urlGet   = '/v1/payments/' . $paymentID;
        $response = $mp->get($urlGet);

        if ($response['status'] == 200) {
            if ($response['response']['status'] == 'pending' || $response['response']['status'] == 'in_process') {
                $data = json_encode(['status' => 'cancelled']);
                $response = $mp->put("/v1/payments/" . $paymentID, $data);

                if ($response['status'] == 200) {
                    $this->dataHelper->log('OrderCancelPlugin::salesOrderBeforeCancel - Payment canceled', 'mercadopago-custom.log', $response);
                    $this->messageManager->addSuccessMessage('Mercado Pago - ' . __('Payment canceled.'));
                    if ($this->scopeConfig->isSetFlag(ConfigData::PATH_ADVANCED_SAVE_TRANSACTION, ScopeInterface::SCOPE_STORE)) {
                        $this->transaction->update($this->order->getPayment(), $this->order, $response['response']['status']);
                    }
                } else {
                    $this->throwCancelationException(__('Could not cancel the payment because of an error returned by the API Mercado Pago.'), $response);
                    $this->messageManager->addErrorMessage($response['status'] . ' ' . $response['response']['message']);
                }
            } else {
                if ($response['response']['status'] == 'rejected') {
                    $this->dataHelper->log('OrderCancelPlugin::salesOrderBeforeCancel - Payment was not canceled because the status is rejected.', 'mercadopago-custom.log', $response);
                    $this->messageManager->addSuccessMessage('Mercado Pago - ' . __('Payment was not canceled because the status is rejected.'));
                } elseif ($response['response']['status'] == 'cancelled') {
                    $this->dataHelper->log('OrderCancelPlugin::salesOrderBeforeCancel - Payment has already been canceled at Mercado Pago.', 'mercadopago-custom.log', $response);
                    $this->messageManager->addSuccessMessage('Mercado Pago - ' . __('Payment has already been canceled at Mercado Pago.'));
                } else {
                    $this->throwCancelationException(__('The payment has not been canceled, you can only cancel payments with status pending or in_process. The payment status is ') . $response['response']['status'] . '.');
                }
            } //end if
        } else {
            $this->throwCancelationException(__('An error occurred while getting the status of the payment in the API Mercado Pago.'), $response);
        } //end if

        return;
    } //end salesOrderBeforeCancel()

    /**
     * @throws LocalizedException
     */
    protected function throwCancelationException($message, $data = [])
    {
        $this->dataHelper->log('OrderCancelPlugin::salesOrderBeforeCancel - ' . $message, 'mercadopago-custom.log', $data);
        throw new LocalizedException(new Phrase('Mercado Pago - ' . $message));
    }
}
