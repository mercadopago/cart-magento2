<?php

namespace MercadoPago\Core\Controller\CustomPix;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use MercadoPago\Core\Lib\RestClient;

/**
 * Class Qrcode
 */
class Qrcode extends Action
{

    /**
     * @var OrderInterface
     */
    protected $order;


    /**
     * Qrcode constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context,
        OrderInterface $order
    ) {
        parent::__construct(
            $context
        );
        $this->order = $order;
    }//end __construct()


    /**
     * Controller action
     */
    public function execute()
    {
        $orderId = $this->_request->getParam('order', false);
        $paymentId = (int) $this->_request->getParam('payment', false);

        if (!$orderId || !$paymentId) {
            return false;
        }

        $order = $this->order->loadByIncrementId($orderId);

        if (!$order) {
            return  false;
        }

        $paymentResponse = $order->getPayment()->getAdditionalInformation('paymentResponse');

        if (!$paymentResponse || (int) $paymentResponse['id'] !== $paymentId) {
            return false;
        }


        if (isset($paymentResponse['point_of_interaction'])
            && isset($paymentResponse['point_of_interaction']['transaction_data'])
        ) {
            if (isset($paymentResponse['point_of_interaction']['transaction_data']['qr_code_base64'])) {
                $base64Image = $paymentResponse['point_of_interaction']['transaction_data']['qr_code_base64'];
            }
        }

        if (!$base64Image) {
            return false;
        }

        $data = base64_decode($base64Image);

        $im = imagecreatefromstring($data);
        header('Content-Type: image/jpeg');
        imagejpeg($im);
        imagedestroy($im);

    }//end execute()


}//end class
