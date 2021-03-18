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

/**
 * Config form FieldSet renderer
 */
class Payment
    extends Fieldset
{
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
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $configResource
     * @param Switcher $switcher
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ScopeConfigInterface $scopeConfig,
        Config $configResource,
        Switcher $switcher,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->switcher = $switcher;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        //get id element
        $paymentId = $element->getId();
        //get country (Site id for Mercado Pago)
        $siteId = strtoupper(
            $this->scopeConfig->getValue(
                ConfigData::PATH_SITE_ID,
                ScopeInterface::SCOPE_STORE
            )
        );

        //check is bank transfer
        if ($this->hideBankTransfer($paymentId, $siteId)) {
            return "";
        }

        //check is bank transfer
        if ($this->hidePix($paymentId, $siteId)) {
            return "";
        }

        return parent::render($element);
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
                $this->configResource->saveConfig($paymentActivePath, $value, 'websites',
                    $this->switcher->getWebsiteId());
            }
        }
    }

    /**
     * @param $paymentId
     * @param $siteId
     * @return bool
     */
    protected function hideBankTransfer($paymentId, $siteId)
    {
        if (strpos($paymentId, 'custom_checkout_bank_transfer') !== false) {
            //hide payment method if not Chile or Colombia
            if ($siteId !== "MLC" && $siteId !== "MCO") {
                $this->disablePayment(ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE);
                return true;
            }
        }
        return false;
    }

    /**
     * @param $paymentId
     * @param $siteId
     * @return bool
     */
    protected function hidePix($paymentId, $siteId)
    {
        if (strpos($paymentId, 'custom_checkout_pix') !== false) {
            if ($siteId !== "MLB") {
                $this->disablePayment(ConfigData::PATH_CUSTOM_PIX_ACTIVE);
                return true;
            }
        }
        return false;
    }
}
