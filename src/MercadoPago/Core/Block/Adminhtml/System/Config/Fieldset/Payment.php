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

    const CHECKOUT_CONFIG_PREFIX = 'payment_us_mercadopago_configurations_';

    /**
     * checkout types
     */
    const CHECKOUT_CUSTOM_CARD = self::CHECKOUT_CONFIG_PREFIX . 'custom_checkout';
    const CHECKOUT_CUSTOM_PIX= self::CHECKOUT_CONFIG_PREFIX . 'custom_checkout_pix';
    const CHECKOUT_CUSTOM_TICKET = self::CHECKOUT_CONFIG_PREFIX . 'custom_checkout_ticket';
    const CHECKOUT_CUSTOM_BANK_TRANSFER = self::CHECKOUT_CONFIG_PREFIX . 'custom_checkout_bank_transfer';

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
     * @param array $data
     * @param Data $coreHelper
     * @param Cache $cache
     * @param TypeLIstInterface $cacheTypeList
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ScopeConfigInterface $scopeConfig,
        Config $configResource,
        Switcher $switcher,
        array $data = [],
        Data $coreHelper,
        Cache $cache,
        TypeLIstInterface $cacheTypeList
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
            $this->coreHelper->log('disabling ' . $paymentId);
            $this->disablePayment($paymentId);
            return "";
        }

        return parent::render($element);
    }

    public function getPaymentMethods()
    {
        $accessToken = $this->coreHelper->getAccessToken();

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
        $paymentActivePath = $this->getPaymentPath($paymentId);

        $this->coreHelper->log('$paymentActivePath ' . $paymentActivePath);

        $statusPaymentMethod = $this->scopeConfig->isSetFlag(
            $paymentActivePath,
            ScopeInterface::SCOPE_STORE
        );

        $this->coreHelper->log('$statusPaymentMethod ' . $statusPaymentMethod);

        //check is active for disable
        if ($paymentActivePath && $statusPaymentMethod) {
            $this->coreHelper->log('true');
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
        if (!$this->coreHelper->getAccessToken()) {
            return true;
        }

        $cacheKey = Cache::VALID_PAYMENT_METHODS;
        $validCheckoutOptions = json_decode($this->cache->getFromCache($cacheKey));
        if (!$validCheckoutOptions) {
            $validCheckoutOptions = $this->getAvailableCheckoutsOptions();
            $this->cache->saveCache($cacheKey, json_encode($validCheckoutOptions));
        }

        return !in_array($paymentId, $validCheckoutOptions);
    }

    /**
     * Get available checkout options based on payment methods of the used credentials
     *
     * @param string $accessToken
     * @return array
     */
    public function getAvailableCheckoutsOptions()
    {
        try {
            $availableCheckouts = array();
            $paymentMethods = $this->getPaymentMethods();

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
        } catch (Exception $e) {
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
