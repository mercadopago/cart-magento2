<?php

namespace MercadoPago\Core\Plugin;
/**
 * Class OrderCancelPlugin
 *
 * @package MercadoPago\Core\Plugin
 */
class OrderCancelPlugin
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $dataHelper;

    protected $scopeConfig;

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \MercadoPago\Core\Helper\Data $dataHelper,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->messageManager = $context->getMessageManager();
        $this->dataHelper = $dataHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Closure $proceed
     *
     * @return mixed
     */
    public function aroundCancel(\Magento\Sales\Model\Order $order, \Closure $proceed)
    {
        $this->order = $order;
        $this->salesOrderBeforeCancel();
        $result = $proceed();

        return $result;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function salesOrderBeforeCancel()
    {
        // Does not repeat the return of payment, if it is done through the Mercado Pago
        if ($this->order->getExternalRequest()) {
            return;
        }

        $paymentMethod = $this->order->getPayment()->getMethodInstance()->getCode();
        if (!($paymentMethod == 'mercadopago_custom' || $paymentMethod == 'mercadopago_customticket' || $paymentMethod == 'mercadopago_custom_bank_transfer' || $paymentMethod == 'mercadopago_basic')) {
            return;
        }

        $cancelAvailable = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_CANCEL_AVAILABLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (!$cancelAvailable) {
            $this->dataHelper->log("OrderCancelPlugin::salesOrderBeforeCancel - Cancellation not enabled", 'mercadopago-custom.log');
            return;
        }

        //Get payment info
        $paymentResponse = $this->order->getPayment()->getAdditionalInformation("paymentResponse");

        if (!isset($paymentResponse['id'])) {
            $this->throwCancelationException(__("Cancellation can not be executed because the payment id was not found."));
            return;
        }

        $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (empty($accessToken)) {
            $this->throwCancelationException(__("Cancellation can not be performed because ACCESS_TOKEN has not been configured."));
            return;
        }

        //Get Payment Id
        $paymentID = $paymentResponse['id'];

        //Get Sdk Instance
        $mp = $this->dataHelper->getApiInstance($accessToken);

        //Get Payment detail
        $urlGet = "/v1/payments/" . $paymentID;
        $response = $mp->get($urlGet);

        if ($response['status'] == 200) {

            if ($response['response']['status'] == 'pending' || $response['response']['status'] == 'in_process') {
                $data = ["status" => 'cancelled'];
                $response = $mp->put("/v1/payments/" . $paymentID, $data);

                if ($response['status'] == 200) {
                    $this->dataHelper->log("OrderCancelPlugin::salesOrderBeforeCancel - Payment canceled", 'mercadopago-custom.log', $response);
                    $this->messageManager->addSuccessMessage("Mercado Pago - " . __('Payment canceled.'));
                } else {
                    $this->throwCancelationException(__("Could not cancel the payment because of an error returned by the API Mercado Pago."), $response);
                    $this->messageManager->addErrorMessage($response['status'] . ' ' . $response['response']['message']);
                }

            } else {

                if ($response['response']['status'] == 'rejected') {
                    $this->dataHelper->log("OrderCancelPlugin::salesOrderBeforeCancel - Payment was not canceled because the status is rejected.", 'mercadopago-custom.log', $response);
                    $this->messageManager->addSuccessMessage("Mercado Pago - " . __('Payment was not canceled because the status is rejected.'));
                } else {
                    $this->throwCancelationException(__("The payment has not been canceled, you can only cancel payments with status pending or in_process. The payment status is ") . $response['response']['status'] . ".");
                }
            }

        } else {
            $this->throwCancelationException(__("An error occurred while getting the status of the payment in the API Mercado Pago."), $response);
        }

        return;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function throwCancelationException($message, $data = array())
    {
        $this->dataHelper->log("OrderCancelPlugin::salesOrderBeforeCancel - " . $message, 'mercadopago-custom.log', $data);
        throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase('Mercado Pago - ' . $message));
    }
}