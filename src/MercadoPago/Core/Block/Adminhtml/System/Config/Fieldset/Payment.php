<?php

namespace MercadoPago\Core\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Store\Switcher;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;

/**
 * Config form FieldSet renderer
 */
class Payment extends Fieldset
{
    const SHOW_PAYMENT_METHOD = 1;

    const HIDE_PAYMENT_METHOD = 2;

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
     * @var Switcher
     */
    protected $switcher;

    /**
     *
     * @var Data
     */
    protected $coreHelper;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $configResource
     * @param Switcher $switcher
     * @param array $data
     * @param Data $coreHelper
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ScopeConfigInterface $scopeConfig,
        Config $configResource,
        Switcher $switcher,
        array $data = [],
        Data $coreHelper
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data, $coreHelper);
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->switcher = $switcher;
        $this->coreHelper = $coreHelper;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        //get id element
        $paymentId = $element->getId();

        //check is bank transfer
        if ($this->hideBankTransfer($paymentId)) {
            return "";
        }

        //check is pix
        if ($this->hidePix($paymentId)) {
            return "";
        }

        return parent::render($element);
    }

    public function getPaymentMethods() {
        $accessToken = $this->scopeConfig->getValue(
            ConfigData::PATH_ACCESS_TOKEN,
            ScopeInterface::SCOPE_WEBSITE
        );

        $paymentMethods = $this->coreHelper->getMercadoPagoPaymentMethods($accessToken);

        return $paymentMethods;
    }

    /**
     * @param $paymentActivePath
     */
    protected function disablePayment($paymentActivePath)
    {
        $statusPaymentMethod = $this->scopeConfig->isSetFlag(
            $paymentActivePath,
            ScopeInterface::SCOPE_STORE
        );

        //check is active for disable
        if ($statusPaymentMethod) {
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
        }
    }

    /**
     * @param  $paymentId
     * @return bool
     */
    protected function hideBankTransfer($paymentId)
    {
        if (strpos($paymentId, 'custom_checkout_bank_transfer') !== false) {
            $paymentMethods = $this->getPaymentMethods();

            if ($paymentMethods) {
                foreach ($paymentMethods['response'] as $pm) {
                    if ($pm['payment_type_id'] === 'bank_transfer' && strtolower($pm['id']) !== 'pix') {
                        return false;
                    }
                }
            }

            $this->disablePayment(ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE);
            return true;
        }
        return false;
    }

    /**
     * @param  $paymentId
     * @return bool
     */
    protected function hidePix($paymentId)
    {
        if (strpos($paymentId, 'custom_checkout_pix') !== false) {

            $siteId = strtoupper(
                $this->scopeConfig->getValue(
                    ConfigData::PATH_SITE_ID,
                    ScopeInterface::SCOPE_STORE
                )
            );

            if ($siteId !== "MLB") {
                $this->disablePayment(ConfigData::PATH_CUSTOM_PIX_ACTIVE);
                return true;
            }
        }

        return false;
    }
}
