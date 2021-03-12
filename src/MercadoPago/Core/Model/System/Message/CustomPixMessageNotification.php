<?php


namespace MercadoPago\Core\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;

/**
 * Class CustomPixMessageNotification
 */
class CustomPixMessageNotification implements MessageInterface
{
    /**
     * Message identity key
     */
    const MESSAGE_IDENTITY = 'custom_pix_notification';


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
        return true;

    }//end isDisplayed()


    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getText()
    {
        return __('Pix message');

    }//end getText()


    /**
     * @inheritDoc
     */
    public function getSeverity()
    {
        self::SEVERITY_NOTICE;

    }//end getSeverity()


}//end class
