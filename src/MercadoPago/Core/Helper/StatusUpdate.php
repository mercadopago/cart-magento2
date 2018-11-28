<?php
namespace MercadoPago\Core\Helper;

/**
 * Class StatusUpdate
 *
 * @package MercadoPago\Core\Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StatusUpdate
    extends \Magento\Payment\Helper\Data
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \MercadoPago\Core\Helper\Message\MessageInterface
     */
    protected $_messageInterface;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Status\Collection
     */
    protected $_statusFactory;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $_creditmemoFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    protected $_dataHelper;
    protected $_coreHelper;

    public function __construct(
        \MercadoPago\Core\Helper\Message\MessageInterface $messageInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Sales\Model\ResourceModel\Status\Collection $statusFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \MercadoPago\Core\Helper\Data $dataHelper,
        \MercadoPago\Core\Model\Core $coreHelper,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_messageInterface = $messageInterface;
        $this->_orderFactory = $orderFactory;
        $this->_statusFactory = $statusFactory;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_dataHelper = $dataHelper;
        $this->_coreHelper = $coreHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_orderSender = $orderSender;
    }

    public function updateStatusOrder($paymentResponse){

      $order = $this->_coreHelper->_getOrder($paymentResponse['external_reference']);
      
      if(!$order->getId()){
        return array(
          "httpStatus" => \MercadoPago\Core\Helper\Response::HTTP_NOT_FOUND,
          "message" => "Mercado Pago - The order was not found in Magento. You will not be able to follow the process without this information.",
          "data" => $paymentResponse['external_reference']
        );
      }
      
      //get message by status
      $message = $this->getMessage($paymentResponse);
      
      //check status already updated
      $statusAlreadyUpdated = $this->checkStatusAlreadyUpdated($paymentResponse, $order);
      
      //get new status (status MP <> status magento) according to the configuration
      $newOrderStatus = $this->getStatusOrder($paymentResponse, $order->canCreditmemo());
      
      //get actual status
      $currentOrderStatus = $order->getState();
      
      if($statusAlreadyUpdated){        
        
        //Update payment response in order
        $this->updatePaymentResponse($order, $paymentResponse);

        //Save chances 
        $order->save();

        return array(
          "httpStatus" => \MercadoPago\Core\Helper\Response::HTTP_OK,
          "message" => "Mercado Pago - Status has already been updated.",
          "data" => array(
            "message" => $message,
            "order_id" => $order->getIncrementId(),
            "current_order_status" => $currentOrderStatus,
            "new_order_status" => $newOrderStatus
          )
        );
      }
      
      $responseStatus   = $this->setStatusAndComment($order, $newOrderStatus, $message);
      $responseEmail    = $this->sendEmailCreateOrUpdate($order, $message);
      $responseInvoice  = false;
      
      if($paymentResponse['status'] == 'approved'){
        $responseInvoice  = $this->createInvoice($order, $message);  
        $responseCustomerAndCards = $this->addCardInCustomer($paymentResponse);
      }

      //Update payment response in order
      $this->updatePaymentResponse($order, $paymentResponse);
      
      //Save chances 
      $order->save();
      
      return array(
        "httpStatus" => \MercadoPago\Core\Helper\Response::HTTP_OK,
        "message" => "Mercado Pago - Status successfully updated.",
        "data" => array(
          "message" => $message,
          "order_id" => $order->getIncrementId(),
          "new_order_status" => $newOrderStatus,
          "old_order_status" => $currentOrderStatus,
          "created_invoice" => $responseInvoice
        )
      );
    }

    public function checkStatusAlreadyUpdated($paymentResponse, $order)
    {
      //set default status
      $orderUpdated = false;
       
      //get status configured in module
      $statusToUpdate = $this->getStatusOrder($paymentResponse, false);
      
      //get list comments
      $commentsObject = $order->getStatusHistoryCollection(true);

      //check if the status has been updated in some time
      foreach ($commentsObject as $commentObj) {
        if($commentObj->getStatus() == $statusToUpdate){
           $orderUpdated = true;
        }
      }
      
      return $orderUpdated;
    }
  
  
  /**
     * @param $order        \Magento\Sales\Model\Order
     * @param $newStatusOrder
     * @param $message
     */
  
  protected function setStatusAndComment($order, $newStatusOrder, $message)
  {
    if ($order->getState() !== \Magento\Sales\Model\Order::STATE_COMPLETE) {
      if($newStatusOrder == 'canceled' && $order->getState() != 'canceled'){
        //cancel order
        $order->cancel();
      }else{
        //change status order
        $order->setState($this->_getAssignedState($newStatusOrder));
      }

      //add comment to history
      $order->addStatusToHistory($newStatusOrder, $message, true);
    }

    return;
  }
  
  
  protected function sendEmailCreateOrUpdate($order, $message){
    //get scope config
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $scopeConfig = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
    $emailOrderCreate = $scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_EMAIL_CREATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    
    //set variable email sent
    $emailAlreadySent = false;

    //ckeck is active send email when create order
    if($emailOrderCreate){

      if (!$order->getEmailSent()){
        $this->_orderSender->send($order, true, $message);
        $emailAlreadySent = true;
      }
    }

    //if the email has not been sent check sent in status
    if($emailAlreadySent === false){
      // search the list of statuses that can send email
      $statusEmail = $scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_EMAIL_UPDATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $statusEmailList = explode(",", $statusEmail);

      //check if the status is on the authorized list
      if(in_array($order->getStatus(), $statusEmailList)) {
        $orderCommentSender = $objectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
        $orderCommentSender->send($order, $notify = '1' , str_replace("<br/>", "", $message));
      }
    }
    
    return;
  }
  
  
  protected function createInvoice($order, $message)
  {
    if (!$order->hasInvoices()) {
      
      $invoice = $order->prepareInvoice();
      $invoice->register();
      $invoice->pay();
      $invoice->addComment(str_replace("<br/>", "", $message), false, true);
      
      $transaction = $this->_transactionFactory->create();
      $transaction->addObject($invoice);
      $transaction->addObject($invoice->getOrder());
      $transaction->save();
     

      $this->_invoiceSender->send($invoice, true, $message);
      return true;
    }
    
    return false;
  }
  
  public function addCardInCustomer($paymentResponse){
    
    if(isset($paymentResponse['metadata']) && 
       isset($paymentResponse['metadata']['customer_id']) && 
       isset($paymentResponse['metadata']['token']) && 
       isset($paymentResponse['payment_method_id']) && 
       isset($paymentResponse['issuer_id']) ){
      
      $customer_id = $paymentResponse['metadata']['customer_id'];
      $token = $paymentResponse['metadata']['token'];
      $payment_method_id = $paymentResponse['payment_method_id'];
      $issuer_id = (int) $paymentResponse['issuer_id'];


      $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

      $request =  array(
        "token" => $token,
        "issuer_id" => $issuer_id,
        "payment_method_id" => $payment_method_id
      );

      $card = \MercadoPago\Core\Lib\RestClient::post("/v1/customers/" . $customer_id . "/cards?access_token=" . $accessToken, $request);
      
      return $card;
    }
  }

    /**
       * Return order status mapping based on current configuration
       *
       * @param $status
       *
       * @return mixed
       */
    public function getStatusOrder($paymentResponse, $isCanCreditMemo)
    {

      $status = $paymentResponse['status'];
      $statusDetail = $paymentResponse['status_detail'];

      switch ($status) {
        case 'approved': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_APPROVED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

          if ($statusDetail == 'partially_refunded' && $isCanCreditMemo) {
            $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_PARTIALLY_REFUNDED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          }
          break;
        }
        case 'in_process': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_IN_PROCESS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          break;
        }

        case 'pending': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_PENDING, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          break;
        }
        case 'rejected': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_REJECTED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          break;
        }
        case 'cancelled': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_CANCELLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          break;
        }
        case 'chargeback': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_CHARGEBACK, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          break;
        }
        case 'in_mediation': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_IN_MEDIATION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          break;
        }
        case 'refunded': {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_REFUNDED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          break;
        }
        default: {
          $status = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ORDER_PENDING, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
      }

      return $status;
    }

    /**
       * Return raw message for payment detail
       *
       * @param $status
       * @param $payment
       *
       * @return \Magento\Framework\Phrase|string
       */
    public function getMessage($paymentResponse)
    {
      $rawMessage = __($this->_messageInterface->getMessage($paymentResponse['status']));
      $rawMessage .= __('<br/> Payment id: %1', $paymentResponse['id']);
      $rawMessage .= __('<br/> Status: %1', $paymentResponse['status']);
      $rawMessage .= __('<br/> Status Detail: %1', $paymentResponse['status_detail']);

      return $rawMessage;
    }

    /**
       * Get the assigned state of an order status
       *
       * @param string $status
       */
    public function _getAssignedState($status)
    {
      $collection = $this->_statusFactory
        ->joinStates()
        ->addFieldToFilter('main_table.status', $status);

      $collectionItems = $collection->getItems();

      return array_pop($collectionItems)->getState();
    }
  
  
    public function updatePaymentResponse($order, $paymentResponse){
      $payment = $order->getPayment();
      $payment->setAdditionalInformation("paymentResponse", $paymentResponse);
    }
  
    // @refactor refund
  
    /**
     * @param $payment \Magento\Sales\Model\Order\Payment
     */
    public function generateCreditMemo($payment, $order = null)
    {
        if (empty($order)) {
            $order = $this->_orderFactory->create()->loadByIncrementId($payment["order_id"]);
        }

        if ($payment['amount_refunded'] == $payment['total_paid_amount']) {
            $this->_createCreditmemo($order, $payment);
            $order->setForcedCanCreditmemo(false);
            $order->setActionFlag('ship', false);
            $order->save();
        } else {
            $this->_createCreditmemo($order, $payment);
        }
    }

    /**
     * @var $order      \Magento\Sales\Model\Order
     * @var $creditMemo \Magento\Sales\Model\Order\Creditmemo
     * @var $payment    \Magento\Sales\Model\Order\Payment
     */
    protected function _createCreditmemo($order, $data)
    {
        $order->setExternalRequest(true);
        $creditMemos = $order->getCreditmemosCollection()->getItems();

        $previousRefund = 0;
        foreach ($creditMemos as $creditMemo) {
            $previousRefund = $previousRefund + $creditMemo->getGrandTotal();
        }
        $amount = $data['amount_refunded'] - $previousRefund;
        if ($amount > 0) {
            $order->setExternalType('partial');
            $creditmemo = $this->_creditmemoFactory->createByOrder($order, [-1]);
            if (count($creditMemos) > 0) {
                $creditmemo->setAdjustmentPositive($amount);
            } else {
                $creditmemo->setAdjustmentNegative($amount);
            }
            $creditmemo->setGrandTotal($amount);
            $creditmemo->setBaseGrandTotal($amount);
            //status "Refunded" for creditMemo
            $creditmemo->setState(2);
            $creditmemo->getResource()->save($creditmemo);
            $order->setTotalRefunded($data['amount_refunded']);
            $order->getResource()->save($order);
        }
    }
}