<?php

namespace MercadoPago\Core\Block;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Info
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var string
     */
    protected $_template = 'MercadoPago_Core::info.phtml';

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_orderFactory = $orderFactory;
    } //end __construct()


    /**
     * Prepare information specific to current payment method
     *
     * @param  null | array $transport
     * @return DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data      = [];

        $info            = $this->getInfo();
        $paymentResponse = $info->getAdditionalInformation('paymentResponse');

        if (isset($paymentResponse['id'])) {
            $data['Payment id (Mercado Pago)'] = $paymentResponse['id'];
        }

        if (isset($paymentResponse['card']) && isset($paymentResponse['card']['first_six_digits']) && isset($paymentResponse['card']['last_four_digits'])) {
            $data['Card Number'] = $paymentResponse['card']['first_six_digits'] . 'xxxxxx' . $paymentResponse['card']['last_four_digits'];
        }

        if (isset($paymentResponse['card']) && isset($paymentResponse['card']['expiration_month']) && isset($paymentResponse['card']['expiration_year'])) {
            $data['Expiration Date'] = $paymentResponse['card']['expiration_month'] . '/' . $paymentResponse['card']['expiration_year'];
        }

        if (isset($paymentResponse['card']) && isset($paymentResponse['card']['cardholder']) && isset($paymentResponse['card']['cardholder']['name'])) {
            $data['Card Holder Name'] = $paymentResponse['card']['cardholder']['name'];
        }

        if (isset($paymentResponse['payment_method_id'])) {
            $data['Payment Method'] = ucfirst($paymentResponse['payment_method_id']);
        }

        if (isset($paymentResponse['installments'])) {
            $data['Installments'] = $paymentResponse['installments'];
        }

        if (isset($paymentResponse['statement_descriptor'])) {
            $data['Statement Descriptor'] = $paymentResponse['statement_descriptor'];
        }

        if (isset($paymentResponse['status'])) {
            $data['Payment Status'] = ucfirst(__($paymentResponse['status']));
        }

        if (isset($paymentResponse['status_detail'])) {
            $data['Payment Status Detail'] = ucwords(str_replace("_", ' ', $paymentResponse['status_detail']));
        }

        if (isset($paymentResponse['transaction_details']) && $paymentResponse['transaction_details']['external_resource_url']) {
            $data['Link'] = $paymentResponse['transaction_details']['external_resource_url'];
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    } //end _prepareSpecificInformation()
}//end class
