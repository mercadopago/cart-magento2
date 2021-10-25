<?php

namespace MercadoPago\Core\Controller\CustomPix;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Sales\Api\Data\OrderInterface;

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
    } //end __construct()

    /**
     * Controller action
     */
    public function execute()
    {
        $orderId   = $this->_request->getParam('order', false);
        $paymentId = (int) $this->_request->getParam('payment', false);

        $base64ImageString = $this->getImageBase64String($orderId, $paymentId);

        if (!$base64ImageString) {
            return false;
        }

        $image = $this->getImage($base64ImageString);

        return $this->getRawResponse($image);
    } //end execute()

    /**
     * @param $orderId
     * @param $paymentId
     * @return false|string
     */
    protected function getImageBase64String($orderId, $paymentId)
    {
        if (!$orderId || !$paymentId) {
            return false;
        }

        $order = $this->order->loadByIncrementId($orderId);

        if (!$order) {
            return false;
        }

        $paymentResponse = $order->getPayment()->getAdditionalInformation('paymentResponse');

        if (!$paymentResponse || (int) $paymentResponse['id'] !== $paymentId) {
            return false;
        }

        $base64Image = false;

        if (isset($paymentResponse['point_of_interaction'])
            && isset($paymentResponse['point_of_interaction']['transaction_data'])
        ) {
            if (isset($paymentResponse['point_of_interaction']['transaction_data']['qr_code_base64'])) {
                $base64Image = $paymentResponse['point_of_interaction']['transaction_data']['qr_code_base64'];
            }
        }
        return $base64Image;
    } //end getImageBase64String()

    /**
     * @param  $base64ImageString
     * @return false|string
     */
    protected function getImage($base64ImageString)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $data = base64_decode($base64ImageString);

        $im = imagecreatefromstring($data);
        ob_start();
        imagejpeg($im);
        $image = ob_get_contents();
        imagedestroy($im);
        ob_end_clean();
        // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
        return $image;
    } //end getImage()

    /**
     * @param  $content
     * @return Raw
     */
    protected function getRawResponse($content)
    {
        $result = new Raw();
        $result->setHeader('Content-Type', 'image/jpeg');
        $result->setContents($content);

        return $result;
    } //end getRawResponse()
}//end class
