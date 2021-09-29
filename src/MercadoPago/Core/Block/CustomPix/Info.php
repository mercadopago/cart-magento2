<?php

namespace MercadoPago\Core\Block\CustomPix;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;

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
     * @var bool
     */
    protected $isPdf = false;

    /**
     * Constructor
     *
     * @param Context $context
     * @param array   $data
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

        $data['QR Code'] = $this->_urlBuilder->getRouteUrl(
            'mercadopago/custompix/qrcode',
            [
                'order'   => $paymentResponse['external_reference'],
                'payment' => $paymentResponse['id'],
            ]
        );

        if (isset($paymentResponse['id'])) {
            $data['Payment id (Mercado Pago)'] = $paymentResponse['id'];
        }

        if (isset($paymentResponse['point_of_interaction'])
            && isset($paymentResponse['point_of_interaction']['transaction_data'])
        ) {
            $transactionData = $paymentResponse['point_of_interaction']['transaction_data'];

            if (isset($transactionData['qr_code_base64']) && !$this->isPdf()) {
                $data['Pix QR Code'] = $transactionData['qr_code_base64'];
            }
            if (isset($transactionData['qr_code'])) {
                $data['Pix Code'] = $transactionData['qr_code'];
            }
        }

        if (isset($paymentResponse['date_of_expiration'])) {
            $pixExpiration = strtotime($paymentResponse['date_of_expiration']);
            $data['Pix Expiration'] = $this->_localeDate->date($pixExpiration)->format('d/m/Y H:i:s');
        }

        if (isset($paymentResponse['payment_method_id'])) {
            $data['Payment Method'] = ucfirst($paymentResponse['payment_method_id']);
        }

        if (isset($paymentResponse['statement_descriptor'])) {
            $data['Statement Descriptor'] = $paymentResponse['statement_descriptor'];
        }

        if (isset($paymentResponse['status'])) {
            $data['Payment Status'] = ucwords(__($paymentResponse['status']));
        }

        if (isset($paymentResponse['status_detail'])) {
            $data['Payment Status Detail'] = ucwords(str_replace("_", ' ', $paymentResponse['status_detail']));
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    } //end _prepareSpecificInformation()

    /**
     * @return bool
     */
    public function isPdf(): bool
    {
        return $this->isPdf;
    }

    /**
     * @param bool $isPdf
     */
    public function setIsPdf(bool $isPdf): void
    {
        $this->isPdf = $isPdf;
    }

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setIsPdf(true);
        return parent::toPdf();
    }
}//end class
