<?php
namespace MercadoPago\Core\Block;

/**
 * Class Info
 *
 * @package MercadoPago\Core\Block
 */
class Info extends \Magento\Payment\Block\Info
{
  
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
  
    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_orderFactory = $orderFactory;
    }

    /**
     * Prepare information specific to current payment method
     *
     * @param null | array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
      $transport = parent::_prepareSpecificInformation($transport);
      $data = [];
      
      $info = $this->getInfo();
      $paymentResponse = $info->getAdditionalInformation();

      if(isset($paymentResponse['id'])){ 
        $title = __('Payment id (Mercado Pago)');
        $data[$title->getText()] = $paymentResponse['id'];
      }

      if(isset($paymentResponse['card']) && isset($paymentResponse['card']['first_six_digits']) && isset($paymentResponse['card']['last_four_digits'])){
        $title = __('Card Number');
        $data[$title->getText()] = $paymentResponse['card']['first_six_digits'] . "xxxxxx".$paymentResponse['card']['last_four_digits'];
      }

      if(isset($paymentResponse['card']) && isset($paymentResponse['card']['expiration_month']) && isset($paymentResponse['card']['expiration_year'])){
        $title = __('Expiration Date');
        $data[$title->getText()] = $paymentResponse['card']['expiration_month'] . "/". $paymentResponse['card']['expiration_year'];          
      }

      if(isset($paymentResponse['card']) && isset($paymentResponse['card']['cardholder']) && isset($paymentResponse['card']['cardholder']['name'])){ 
        $title = __('Card Holder Name');
        $data[$title->getText()] = $paymentResponse['card']['cardholder']['name'];       
      }

      if(isset($paymentResponse['payment_method_id'])){ 
        $title = __('Payment Method');
        $data[$title->getText()] = ucfirst($paymentResponse['payment_method_id']);      
      }

      if(isset($paymentResponse['installments'])){ 
        $title = __('Installments');
        $data[$title->getText()] = $paymentResponse['installments'];
      }

      if(isset($paymentResponse['statement_descriptor'])){
        $title = __('Statement Descriptor');
        $data[$title->getText()] = $paymentResponse['statement_descriptor'];    
      }

      if(isset($paymentResponse['status'])){ 
        $title = __('Payment Status');
        $data[$title->getText()] = ucfirst($paymentResponse['status']);  
      }

      if(isset($paymentResponse['id'])){
        $title = __('Payment Status Detail');
        $data[$title->getText()] = ucfirst($paymentResponse['status_detail']);   
      }
      
      // LINK TO TICKET disable
      //       if(isset($paymentResponse['transaction_details']) && $paymentResponse['transaction_details']['external_resource_url']){ 
      //         $data['Link'] = $paymentResponse['transaction_details']['external_resource_url'];
      //       }

      return $transport->setData(array_merge($data, $transport->getData()));
    }

}
