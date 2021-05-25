<?php

namespace MercadoPago\Core\Block\CustomPix;

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
    protected $_template = 'MercadoPago_Core::custom_pix/info.phtml';


    /**
     * Constructor
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        array $data=[]
    ) {
        parent::__construct($context, $data);
        $this->_orderFactory = $orderFactory;

    }//end __construct()


    /**
     * Prepare information specific to current payment method
     *
     * @param  null | array $transport
     * @return DataObject
     */
    protected function _prepareSpecificInformation($transport=null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data      = [];

        $info            = $this->getInfo();
        $paymentResponse = $info->getAdditionalInformation('paymentResponse');

        if (isset($paymentResponse['id'])) {
            $title = __('Payment id (Mercado Pago)');
            $data[$title->__toString()] = $paymentResponse['id'];
        }

        $data['QR Code'] = $this->_urlBuilder->getRouteUrl(
            'mercadopago/custompix/qrcode',
            [
                'order'   => $paymentResponse['external_reference'],
                'payment' => $paymentResponse['id'],
            ]
        );

        if (isset($paymentResponse['point_of_interaction'])
            && isset($paymentResponse['point_of_interaction']['transaction_data'])
        ) {
            $transactionData = $paymentResponse['point_of_interaction']['transaction_data'];
            if (isset($transactionData['qr_code'])) {
                $title = __('Pix Code');
                $data[$title->__toString()] = $transactionData['qr_code'];
            }

            if (isset($transactionData['qr_code_base64'])) {
                $title = __('Pix QR Code');
                $data[$title->__toString()] = $transactionData['qr_code_base64'];
            }
        }

        if (isset($paymentResponse['date_of_expiration'])) {
            $pixExpiration = strtotime($paymentResponse['date_of_expiration']);
            $title         = __('Pix Expiration');
            $data[$title->__toString()] = $this->_localeDate->date($pixExpiration)->format('d/m/Y H:i:s');
        }

        if (isset($paymentResponse['payment_method_id'])) {
            $title = __('Payment Method');
            $data[$title->__toString()] = ucfirst($paymentResponse['payment_method_id']);
        }

        if (isset($paymentResponse['statement_descriptor'])) {
            $title = __('Statement Descriptor');
            $data[$title->__toString()] = $paymentResponse['statement_descriptor'];
        }

        if (isset($paymentResponse['status'])) {
            $title = __('Payment Status');
            $data[$title->__toString()] = ucwords(__($paymentResponse['status']));
        }

        if (isset($paymentResponse['status_detail'])) {
            $title = __('Payment Status Detail');
            $data[$title->__toString()] = ucwords(preg_replace('/_/', ' ', $paymentResponse['status_detail']));
        }

        return $transport->setData(array_merge($data, $transport->getData()));

    }//end _prepareSpecificInformation()


}//end class
