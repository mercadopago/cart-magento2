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
     * @param \MercadoPago\Core\Helper\Data                   $coreHelper
     * @param \MercadoPago\Core\Model\Core                    $coreModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \MercadoPago\Core\Model\Core $coreModel,
        \MercadoPago\Core\Helper\StatusUpdate $statusHelper
    )
    {
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
    try {

      $this->_requestData = $this->getRequest();

      $this->coreHelper->log("NotificationsCustom::execute - Custom Received notification", self::LOG_NAME, $this->_requestData->getParams());

      $dataId = $this->_requestData->getParam('data_id');
      $type = $this->_requestData->getParam('type');

      if (!empty($dataId) && $type == 'payment') {
        $response = $this->coreModel->getPaymentV1($dataId);

        if ($response['status'] == 200 || $response['status'] == 201) {

          $payment = $response['response'];
          $response =  $this->_statusHelper->updateStatusOrder($payment);

          //set response result update status
          $this->setResponseHttp($response['httpStatus'], $response['message'], $response['data']);

        }else{
          $this->setResponseHttp(\MercadoPago\Core\Helper\Response::HTTP_NOT_FOUND, "Mercado Pago - Payment not found, Mercado Pago API did not return the expected information.", $response);
        }
      } else {
        $this->setResponseHttp(\MercadoPago\Core\Helper\Response::HTTP_BAD_REQUEST, "Mercado Pago - Invalid Notification Parameters, data.id and type are expected.", $this->_requestData->getParams());
      }

      return;
    } catch (\Exception $e) {
      $this->setResponseHttp(
        \MercadoPago\Core\Helper\Response::HTTP_INTERNAL_ERROR, 
        "Mercado Pago - There was a serious error processing the notification. Could not handle the error.", array(
          "exception_error" => $e->getMessage()
        )
      );
    }
  }
  
  protected function setResponseHttp($httpStatus, $response, $data = array()){

    $response = array(
      "status" => $httpStatus,
      "message" => $response,
      "data" => $data
    );

    $this->coreHelper->log("NotificationsCustom::setResponseHttp - Response: " . json_encode($response), self::LOG_NAME);

    $this->getResponse()->setHeader('Content-Type', 'application/json', $overwriteExisting = true);
    $this->getResponse()->setBody(json_encode($response));
    $this->getResponse()->setHttpResponseCode($httpStatus);
    
    return;
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