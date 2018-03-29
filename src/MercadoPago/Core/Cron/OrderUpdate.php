<?php

namespace MercadoPago\Core\Cron;

class OrderUpdate
{

    /**
     * @var \MercadoPago\Core\Helper\StatusUpdate
     */
    protected $_statusHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_helper;

    /**
     * @var \MercadoPago\Core\Model\Core
     */
    protected $_core;

    const LOG_FILE = 'mercadopago-order-synchronized.log';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \MercadoPago\Core\Helper\StatusUpdate $statusUpdate,
        \MercadoPago\Core\Helper\Data $helper,
        \MercadoPago\Core\Model\Core $core,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        array $data = []
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_statusHelper = $statusUpdate;
        $this->_helper = $helper;
        $this->_core = $core;
        $this->_eventManager = $eventManager;
    }

    public function execute(){
       $hours = $this->_scopeConfig->getValue('payment/mercadopago/number_of_hours');

        // filter to date:
        $fromDate = date('Y-m-d H:i:s', strtotime('-'.$hours. ' hours'));
        $toDate = date('Y-m-d H:i:s', strtotime("now"));

        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id=payment.parent_id',
                ['payment_method' => 'payment.method']
            )
            ->addFieldToFilter('status' ,["nin" => ['canceled','complete']])
            ->addFieldToFilter('created_at', ['from'=>$fromDate, 'to'=>$toDate])
        ;

        // For all Orders to analyze
        foreach($collection as $orderByPayment){
            $order = $orderByPayment;
            $paymentOrder = $order->getPayment();
            $infoPayments = $paymentOrder->getAdditionalInformation();

            $method = $paymentOrder->getMethod();

            $order->canCreditmemo();

            if ($method == "mercadopago_custom" || $method == "mercadopago_customticket" || $method == "mercadopago_standard"){

                if (isset($infoPayments['merchant_order_id']) && $order->getStatus() !== 'complete') {

                    $merchantOrderId =  $infoPayments['merchant_order_id'];

                    $response = $this->_core->getMerchantOrder($merchantOrderId);

                    if ($response['status'] == 201 || $response['status'] == 200) {
                        $merchantOrderData = $response['response'];

                        $paymentData = $this->getDataPayments($merchantOrderData);
                        $statusFinal = $this->_statusHelper->getStatusFinal($paymentData['status'], $merchantOrderData);
                        $statusDetail = $infoPayments['status_detail'];

                        $statusOrder = $this->_statusHelper->getStatusOrder($statusFinal, $statusDetail, $order->canCreditmemo());

                        $shipmentData = $this->_statusHelper->getShipmentsArray($merchantOrderData);

                        if (isset($statusOrder) && ($order->getStatus() !== $statusOrder)) {
                            $this->_helper->log("OrderUpdate merchant_order:", self::LOG_FILE, $merchantOrderData);

                            // if this happens, we need to generate a credit memo
                            if (isset($paymentData["amount_refunded"]) && $paymentData["amount_refunded"] > 0) {
                                $this->_statusHelper->generateCreditMemo($paymentData, $order);
                                $this->_helper->log("Update Order generated CreditMemo", self::LOG_FILE);
                            }

                            if ((!empty($shipmentData) && !empty($merchantOrderData))) {
                                $this->_eventManager->dispatch(
                                    'mercadopago_standard_notification_before_set_status',
                                    ['shipmentData' => $shipmentData, 'orderId' => $merchantOrderData['external_reference']]
                                );
                            }

                            $this->_updateOrder($order, $statusOrder, $paymentOrder);
                        }
                    } else{
                        $this->_helper->log('Error updating status order using cron whit the merchantOrder num: '. $merchantOrderId .'mercadopago.log');
                    }
                }
            }
        }
    }

    /**
     * @param $order \Magento\Sales\Model\ResourceModel\Order
     * @param $statusOrder
     * @param $paymentOrder
     */
    protected function _updateOrder($order, $statusOrder, $paymentOrder){
        $order->setState($this->_statusHelper->_getAssignedState($statusOrder));
        $order->addStatusToHistory($statusOrder, $this->_statusHelper->getMessage($statusOrder, $statusOrder), true);
        $order->sendOrderUpdateEmail(true, $this->_statusHelper->getMessage($statusOrder, $paymentOrder));
        $order->save();
    }

    protected function getDataPayments($merchantOrderData)
    {
        $data = array();
        foreach ($merchantOrderData['payments'] as $payment) {
            $data = $this->getFormattedPaymentData($payment['id'], $data);
        }

        return $data;
    }

    protected function getFormattedPaymentData($paymentId, $data = [])
    {
        $response = $this->_core->getPayment($paymentId);
        if ($response['status'] == 400 || $response['status'] == 401) {
            return [];
        }
        $payment = $response['response']['collection'];

        return $this->_statusHelper->formatArrayPayment($data, $payment, self::LOG_FILE);
    }

}