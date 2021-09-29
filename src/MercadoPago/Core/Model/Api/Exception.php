<?php

namespace MercadoPago\Core\Model\Api;

/**
 * Exception which thrown by MercadoPago API in case of processable error codes
 * Class MercadoPago_Core_Model_Api_Exception
 *
 * @package MercadoPago\Core\Model\Api\Exception
 */
class Exception extends \Magento\Framework\Exception\LocalizedException
{

    /**
     * Generic message to show by default
     */
    const GENERIC_USER_MESSAGE = "We could not process your payment in this moment. Please check the form data and retry later";

    const GENERIC_API_EXCEPTION_MESSAGE = "We could not process your payment in this moment. Please retry later";

    /**
     * @var array to map messages
     */
    protected $_messagesMap;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;


    /**
     * Constructor
     *
     * @param \Magento\Framework\Phrase $phrase
     */
    public function __construct(
        \Magento\Framework\Phrase $phrase,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($phrase);
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Get error message which can be displayed to website user
     *
     * @return string
     */
    public function getUserMessage($error = null)
    {
        if (!empty($error)) {
            //             if ($this->_scopeConfig->isSetFlag('payment/mercadopago/debug_mode', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE)) {
            //                 return $error['description'];
            //             } else {
            $code = $error['code'];
            if (isset($this->_messagesMap[$code])) {
                return __($this->_messagesMap[$code]);
            }
            //             }
        }

        return __(self::GENERIC_USER_MESSAGE);
    }
}
