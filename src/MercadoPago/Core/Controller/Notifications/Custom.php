<?php
namespace MercadoPago\Core\Controller\Notifications;


/**
 * Class Custom
 *
 * @package MercadoPago\Core\Controller\Notifications
 */
class Custom
    extends \Magento\Framework\App\Action\Action

{
    /**
     * @var \MercadoPago\Core\Model\Standard\PaymentFactory
     */
    protected $_paymentFactory;

    /**
     * @var \MercadoPago\Core\Helper\
     */
    protected $coreHelper;

    /**
     * @var \MercadoPago\Core\Model\Core
     */
    protected $coreModel;
    protected $_order;
    protected $_statusHelper;
    protected $_requestData;

    /**
     * Log file name
     */
    const LOG_NAME = 'custom_notification';


    /**
     * Standard constructor.
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \MercadoPago\Core\Model\Standard\PaymentFactory $paymentFactory
     * @param \MercadoPago\Core\Helper\Data                   $coreHelper
     * @param \MercadoPago\Core\Model\Core                    $coreModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MercadoPago\Core\Model\Standard\PaymentFactory $paymentFactory,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \MercadoPago\Core\Model\Core $coreModel,
        \MercadoPago\Core\Helper\StatusUpdate $statusHelper
    )
    {
        $this->_paymentFactory = $paymentFactory;
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->_statusHelper = $statusHelper;
        parent::__construct($context);
    }

    /**
     * Controller Action
     */
    public function execute()
    {
        $this->_requestData = $this->getRequest();
        //$request = $this->getRequest();
        $this->coreHelper->log("Custom Received notification", self::LOG_NAME, $this->_requestData->getParams());

        $dataId = $this->_requestData->getParam('data_id');
        $type = $this->_requestData->getParam('type');
        if (!empty($dataId) && $type == 'payment') {
            $response = $this->coreModel->getPaymentV1($dataId);
            $this->coreHelper->log("Return payment", self::LOG_NAME, $response);

            if ($response['status'] == 200 || $response['status'] == 201) {
                $payment = $response['response'];
                $payment = $this->coreHelper->setPayerInfo($payment);

                $this->_order = $this->coreModel->_getOrder($payment['external_reference']);
                if (!$this->_orderExists() || $this->_order->getStatus() == 'canceled') {
                    return;
                }

                $this->coreHelper->log("Update Order", self::LOG_NAME);
                $this->_statusHelper->setStatusUpdated($payment, $this->_order);

                $data = $this->_statusHelper->formatArrayPayment($data = [], $payment, self::LOG_FILE);

                $this->_statusHelper->updateOrder($data, $this->_order);
                $setStatusResponse = $this->_statusHelper->setStatusOrder($payment);
                $this->getResponse()->setBody($setStatusResponse['text']);
                $this->getResponse()->setHttpResponseCode($setStatusResponse['code']);
                $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());

                return;
            }
        }

        $this->coreHelper->log("Payment not found", self::LOG_NAME, $this->_requestData->getParams());
        $this->getResponse()->getBody("Payment not found");
        $this->getResponse()->setHttpResponseCode(\MercadoPago\Core\Helper\Response::HTTP_NOT_FOUND);
        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());
    }

    protected function _orderExists()
    {
        if ($this->_order->getId()) {
            return true;
        }
        $this->coreHelper->log(\MercadoPago\Core\Helper\Response::INFO_EXTERNAL_REFERENCE_NOT_FOUND, self::LOG_NAME, $this->_requestData->getParams());
        $this->getResponse()->getBody(\MercadoPago\Core\Helper\Response::INFO_EXTERNAL_REFERENCE_NOT_FOUND);
        $this->getResponse()->setHttpResponseCode(\MercadoPago\Core\Helper\Response::HTTP_NOT_FOUND);
        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());

        return false;
    }
}