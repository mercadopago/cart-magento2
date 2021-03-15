<?php

namespace MercadoPago\Core\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Model\Core;

/**
 * Class CustomPixMessageNotification
 */
class CustomPixMessageNotification implements MessageInterface
{
    /**
     * Message identity key
     */
    const MESSAGE_IDENTITY = 'custom_pix_notification';

    const ALLOWED_SITE_ID = 'MLB';

    const PAYMENT_ID_METHOD_PIX = 'pix';

    /**
     * @var Core
     */
    protected $coreModel;


    /**
     * CustomPixMessageNotification constructor.
     *
     * @param Core $coreModel
     */
    public function __construct(Core $coreModel)
    {
        $this->coreModel = $coreModel;

    }//end __construct()


    /**
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;

    }//end getIdentity()


    /**
     * @return boolean
     */
    public function isDisplayed()
    {
        if (false === $this->canConfigurePixGateway()) {
            return false;
        }

        if (true === $this->pixAvalaiblePaymentPix()) {
            return false;
        }

        return true;

    }//end isDisplayed()


    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getText()
    {
        return __('Please note that to receive payments via Pix at our checkout, you must have a Pix key registered in your Mercado Pago account.');

    }//end getText()


    /**
     * @inheritDoc
     */
    public function getSeverity()
    {
        self::SEVERITY_NOTICE;

    }//end getSeverity()


    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function canConfigurePixGateway()
    {
        $data = $this->coreModel->getUserMe();
        $user = $data['response'];

        if (false === empty($user['site_id']) && self::ALLOWED_SITE_ID === $user['site_id']) {
            return true;
        }

        return false;

    }//end canConfigurePixGateway()


    /**
     * @return boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function pixAvalaiblePaymentPix()
    {
        $data     = $this->coreModel->getPaymentMethods();
        $payments = $data['response'];

        foreach ($payments as $payment) {
            if (false === empty($payment['id']) && self::PAYMENT_ID_METHOD_PIX === $payment['id']) {
                return true;
            }
        }

        return false;

    }//end pixAvalaiblePaymentPix()


}//end class
