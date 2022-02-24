<?php

namespace MercadoPago\Core\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Store\Switcher;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Helper\Cache;

/**
 * Config form FieldSet renderer
 */
class Payment extends Fieldset
{

    /**
     * Checkout Custom Card
     */
    const CHECKOUT_CUSTOM_CARD = 'custom_checkout';
  
    /**
     * Checkout Custom Pix
     */
    const CHECKOUT_CUSTOM_PIX= 'custom_checkout_pix';
  
    /**
     * Checkout Custom Ticket
     */
    const CHECKOUT_CUSTOM_TICKET = 'custom_checkout_ticket';
  
    /**
     * Checkout Custom Bank Transfer
     */
    const CHECKOUT_CUSTOM_BANK_TRANSFER = 'custom_checkout_bank_transfer';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var Config
     */
    protected $configResource;

    /**
     *
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     *
     * @var Switcher
     */
    protected $switcher;

    /**
     *
     * @var Data
     */
    protected $coreHelper;

    /**
     *
     * @var Cache
     */
    protected $cache;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $configResource
     * @param Switcher $switcher
     * @param Data $coreHelper
     * @param Cache $cache
     * @param TypeListInterface $cacheTypeList
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ScopeConfigInterface $scopeConfig,
        Config $configResource,
        Switcher $switcher,
        Data $coreHelper,
        Cache $cache,
        TypeListInterface $cacheTypeList,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->switcher = $switcher;
        $this->coreHelper = $coreHelper;
        $this->cache = $cache;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        //get id element
        $paymentId = $element->getId();

        //check available payment methods
        if ($this->hideInvalidCheckoutOptions($paymentId)) {
            $this->disablePayment($paymentId);
            return "";
        }

        return parent::render($element);
    }

    public function getPaymentMethods($accessToken)
    {
        $paymentMethods = $this->coreHelper->getMercadoPagoPaymentMethods($accessToken);

        return $paymentMethods;
    }

    /**
     * Disables the given payment if it is currently active
     * 
     * @param $paymentId
     */
    protected function disablePayment($paymentId)
    {
        $paymentIdWithoutPrefix = implode('_', array_slice(explode('_', $paymentId), 4));

        $paymentActivePath = $this->getPaymentPath($paymentIdWithoutPrefix);

        $statusPaymentMethod = $this->scopeConfig->isSetFlag(
            $paymentActivePath,
            ScopeInterface::SCOPE_STORE
        );

        //check is active for disable
        if ($paymentActivePath && $statusPaymentMethod) {
            $value = 0;

            if ($this->switcher->getWebsiteId() == 0) {
                $this->configResource->saveConfig($paymentActivePath, $value, 'default', 0);
            } else {
                $this->configResource->saveConfig(
                    $paymentActivePath,
                    $value,
                    'websites',
                    $this->switcher->getWebsiteId()
                );
            }
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        }
    }

    /**
     * @param  $paymentId
     * @return bool
     */
    protected function hideInvalidCheckoutOptions($paymentId)
    {
        $accessToken = $this->coreHelper->getAccessToken();

        if (!$this->coreHelper->isValidAccessToken($accessToken)) {
            return true;
        }

        $cacheKey = Cache::VALID_PAYMENT_METHODS;
        $validCheckoutOptions = json_decode($this->cache->getFromCache($cacheKey));
        if (!$validCheckoutOptions) {
            $validCheckoutOptions = $this->getAvailableCheckoutOptions($accessToken);
            $this->cache->saveCache($cacheKey, json_encode($validCheckoutOptions));
        }
        
        $paymentIdWithoutPrefix = implode('_', array_slice(explode('_', $paymentId), 4));

        return !in_array($paymentIdWithoutPrefix, $validCheckoutOptions);
    }

    /**
     * Get available checkout options based on payment methods of the used credentials
     *
     * @param string $accessToken
     * @return array
     */
    public function getAvailableCheckoutOptions($accessToken)
    {
        try {
            $availableCheckouts = array();
            $paymentMethods = $this->getPaymentMethods($accessToken);

            foreach ($paymentMethods['response'] as $paymentMethod) {
                switch (strtolower($paymentMethod['payment_type_id'])) {
                    case 'credit_card':
                    case 'debid_card':
                    case 'prepaid_card':
                        if (!in_array(self::CHECKOUT_CUSTOM_CARD, $availableCheckouts)) {
                            $availableCheckouts[] = self::CHECKOUT_CUSTOM_CARD;
                        }
                        break;

                    case 'atm':
                    case 'ticket':
                        if (!in_array(self::CHECKOUT_CUSTOM_TICKET, $availableCheckouts)) {
                            $availableCheckouts[] = self::CHECKOUT_CUSTOM_TICKET;
                        }
                        break;

                    case 'bank_transfer':
                        if (!in_array(self::CHECKOUT_CUSTOM_PIX, $availableCheckouts) && strtolower($paymentMethod['id']) === 'pix') {
                            $availableCheckouts[] = self::CHECKOUT_CUSTOM_PIX;
                        }
                        if (!in_array(self::CHECKOUT_CUSTOM_BANK_TRANSFER, $availableCheckouts) && strtolower($paymentMethod['id']) !== 'pix') {
                            $availableCheckouts[] = self::CHECKOUT_CUSTOM_BANK_TRANSFER;
                        }
                        break;
                }
            }

            return $availableCheckouts;
        } catch (\Exception $e) {
            $this->coreHelper->log('Payment Fieldset getAvailableCheckoutOptions error: ' . $e->getMessage());
            return [];
        }
    }

    public function getPaymentPath($paymentId)
    {
        switch ($paymentId) {
            case (self::CHECKOUT_CUSTOM_CARD):
                return ConfigData::PATH_CUSTOM_ACTIVE;

            case (self::CHECKOUT_CUSTOM_TICKET):
                return ConfigData::PATH_CUSTOM_TICKET_ACTIVE;

            case (self::CHECKOUT_CUSTOM_PIX):
                return ConfigData::PATH_CUSTOM_PIX_ACTIVE;

            case (self::CHECKOUT_CUSTOM_BANK_TRANSFER):
                return ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE;
        }
    }
}
