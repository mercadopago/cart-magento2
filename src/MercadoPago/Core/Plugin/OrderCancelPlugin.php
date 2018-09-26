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

    public function __construct(\Magento\Framework\App\Action\Context $context,
                                \MercadoPago\Core\Helper\Data $dataHelper,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->messageManager = $context->getMessageManager();
        $this->dataHelper = $dataHelper;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Closure                   $proceed
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
        if ($this->order->getExternalRequest()) {
            return;
        }

        $paymentMethod = $this->order->getPayment()->getMethodInstance()->getCode();
        if (!($paymentMethod == 'mercadopago_standard' || $paymentMethod == 'mercadopago_custom' || $paymentMethod == 'mercadopago_customticket')) {
            return;
        }

        //get data
        $dataOrder = $this->order->getData();

        //get status
        $orderStatus = isset($dataOrder['status']) ? $dataOrder['status'] : null;

        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();

        $this->dataHelper->log("salesOrderBeforeCancel 2 - ", 'mercadopago-custom.log', $orderStatus);

        $orderPaymentStatus = isset($additionalInformation['status']) ? $additionalInformation['status'] : null ;
        $paymentID = isset($additionalInformation['payment_id_detail']) ? $additionalInformation['payment_id_detail'] : null ;
        
        error_log("salesOrderBeforeCancel" . json_encode($additionalInformation));
        $isValidBasicData = $this->checkCancelationBasicData($paymentID, $paymentMethod);
        if ($isValidBasicData){
            $isValidaData = $this->checkCancelationData($orderStatus, $orderPaymentStatus);
            if ($isValidaData){
                $clientId = $this->dataHelper->getClientId();
                $clientSecret = $this->dataHelper->getClientSecret();
                $accessToken = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $response = null;

                $mp = $this->dataHelper->getApiInstance($accessToken);
                if ($paymentMethod == 'mercadopago_standard') {
                    $mp = $this->dataHelper->getApiInstance($clientId, $clientSecret);
                }
                
                $mp = $this->dataHelper->getApiInstance($accessToken);
                $response = $mp->get("/v1/payments/$paymentID?access_token=$accessToken");

                if ($response['status'] == 200 && ($response['response']['status'] == 'pending' || $response['response']['status'] == 'in_process')) {
                    $data = ["status" => 'cancelled'];
                    $response = $mp->put("/v1/payments/$paymentID?access_token=$accessToken", $data);
                    if ($response['status'] == 200) {
                        $this->messageManager->addSuccessMessage(__('Cancellation made by Mercado Pago'));
                    } else {
                        $this->messageManager->addErrorMessage(__('Failed to make the cancellation by Mercado Pago'));
                        $this->messageManager->addErrorMessage($response['status'] . ' ' . $response['response']['message']);
                        $this->throwCancelationException();
                    }
                }
            }
        }
    }

    /**
     * @param $paymentID
     * @param $paymentMethod
     *
     * @return bool
     */
    protected function checkCancelationBasicData($paymentID, $paymentMethod)
    {
      
        error_log("checkCancelationBasicData " . $paymentID . " " . $paymentMethod);
      
        $refundAvailable = $this->dataHelper->isRefundAvailable();

        if (!$refundAvailable) {
            $this->messageManager->addWarningMessage(__('Mercado Pago cancellation is disabled. The cancellation will be made through Magento'));
            return false;
        }

        if ($refundAvailable && $paymentID == null){
            $this->messageManager->addWarningMessage(__('The cancellation will be made through Magento. It was\'t possible to cancel on MercadoPago'));
            return false;
        }

        if (!($paymentMethod == 'mercadopago_standard' || $paymentMethod == 'mercadopago_custom' || $paymentMethod == 'mercadopago_customticket')) {
            $this->messageManager->addWarningMessage(__('Order payment wasn\'t made by Mercado Pago. The cancellation will be made through Magento'));
            return false;
        }

        return true;
    }

    /**
     * @param $orderStatus
     * @param $orderPaymentStatus
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkCancelationData($orderStatus, $orderPaymentStatus)
    {
        $isValidaData = true;

        if (!isset($orderPaymentStatus)){
            $this->messageManager->addErrorMessage(__('The order didn\'t have payment status'));
            $isValidaData = false;
        }else if (!($orderPaymentStatus == 'pending' || $orderPaymentStatus == 'in_process' || $orderPaymentStatus == 'rejected')) {
            $this->messageManager->addErrorMessage(__('You can only make cancellations on orders whose payment status is Rejected, Pending o In Process'));
            $isValidaData = false;
        }

        if (!$isValidaData) {
            $this->throwCancelationException();
        }

        return $isValidaData;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function throwCancelationException()
    {
        throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase('Mercado Pago - Cancellations not made'));
    }
}