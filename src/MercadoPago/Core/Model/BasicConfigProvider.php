<?php

namespace MercadoPago\Core\Model;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Action\Context;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\RestClient;

/**
 * Class BasicConfigProvider
 * @package MercadoPago\Core\Model
 */
class BasicConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = Basic\Payment::CODE;
    protected $_scopeConfig;
    protected $_methodInstance;
    protected $_checkoutSession;
    protected $_assetRepo;
    protected $_productMetaData;
    protected $_coreHelper;
    protected $_context;

    /**
     * BasicConfigProvider constructor.
     * @param Context $context
     * @param PaymentHelper $paymentHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param Repository $assetRepo
     * @param ProductMetadataInterface $productMetadata
     * @param Data $coreHelper
     * @throws LocalizedException
     */
    public function __construct(
        Data                     $coreHelper,
        Context                  $context,
        Repository               $assetRepo,
        Session                  $checkoutSession,
        PaymentHelper            $paymentHelper,
        ScopeConfigInterface     $scopeConfig,
        ProductMetadataInterface $productMetadata
    )
    {
        $this->_context = $context;
        $this->_assetRepo = $assetRepo;
        $this->_coreHelper = $coreHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_checkoutSession = $checkoutSession;
        $this->_productMetaData = $productMetadata;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        try {
            if (!$this->_methodInstance->isAvailable()) {
                return [];
            }

            $bannerInfo = $this->makeBannerCheckout();
            $country = strtoupper($this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

            return [
                'payment' => [
                    $this->methodCode => [
                        'active' => $this->_scopeConfig->getValue(
                            ConfigData::PATH_BASIC_ACTIVE,
                            ScopeInterface::SCOPE_STORE
                        ),
                        'logEnabled' => $this->_scopeConfig->getValue(
                            ConfigData::PATH_ADVANCED_LOG,
                            ScopeInterface::SCOPE_STORE
                        ),
                        'max_installments' => $this->_scopeConfig->getValue(
                            ConfigData::PATH_BASIC_MAX_INSTALLMENTS,
                            ScopeInterface::SCOPE_STORE
                        ),
                        'auto_return' => $this->_scopeConfig->getValue(
                            ConfigData::PATH_BASIC_AUTO_RETURN,
                            ScopeInterface::SCOPE_STORE
                        ),
                        'exclude_payments' => $this->_scopeConfig->getValue(
                            ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS,
                            ScopeInterface::SCOPE_STORE
                        ),
                        'order_status' => $this->_scopeConfig->getValue(
                            ConfigData::PATH_BASIC_ORDER_STATUS,
                            ScopeInterface::SCOPE_STORE
                        ),
                        'logoUrl' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mp_logo.png"),
                        'actionUrl' => $this->_context->getUrl()->getUrl(Basic\Payment::ACTION_URL),
                        'banner_info' => $bannerInfo,
                        'loading_gif' => $this->_assetRepo->getUrl('MercadoPago_Core::images/loading.gif'),
                        'redirect_image' => $this->_assetRepo->getUrl("MercadoPago_Core::images/redirect_checkout.png"),
                        'module_version' => $this->_coreHelper->getModuleVersion(),
                        'platform_version' => $this->_productMetaData->getVersion(),
                        'mercadopago_mini' => $this->_assetRepo->getUrl("MercadoPago_Core::images/mercado-pago-mini.png"),
                        'fingerprint_link' => $this->_coreHelper->getFingerPrintLink($country),
                    ],
                ],
            ];
        } catch (Exception $e) {
            $this->_coreHelper->log("BasicConfigProvider ERROR: " . $e->getMessage(), 'BasicConfigProvider');
            return [];
        }
    }

    /**
     * Make payment methods banner
     *
     * @return array|void
     */
    public function makeBannerCheckout()
    {
        $accessToken = $this->_scopeConfig->getValue(
            ConfigData::PATH_ACCESS_TOKEN,
            ScopeInterface::SCOPE_WEBSITE
        );

        $maxInstallments = $this->_scopeConfig->getValue(
            ConfigData::PATH_BASIC_MAX_INSTALLMENTS,
            ScopeInterface::SCOPE_STORE
        );

        $excludePaymentMethods = $this->_scopeConfig->getValue(
            ConfigData::PATH_BASIC_EXCLUDE_PAYMENT_METHODS,
            ScopeInterface::SCOPE_STORE
        );

        $excludePaymentMethods = is_string($excludePaymentMethods) ? explode(",", $excludePaymentMethods) : [];

        try {
            $debit = 0;
            $credit = 0;
            $ticket = 0;
            $choMethods = [];

            $paymentMethods = $this->_coreHelper->getMercadoPagoPaymentMethods($accessToken);

            foreach ($paymentMethods['response'] as $pm) {
                if (!in_array($pm['id'], $excludePaymentMethods)) {
                    $choMethods[] = $pm;
                    if ($pm['payment_type_id'] == 'credit_card') {
                        $credit += 1;
                    } elseif ($pm['payment_type_id'] == 'debit_card' || $pm['payment_type_id'] == 'prepaid_card') {
                        $debit += 1;
                    } else {
                        $ticket += 1;
                    }
                }
            }

            return [
                "debit" => $debit,
                "credit" => $credit,
                "ticket" => $ticket,
                "installments" => $maxInstallments,
                "checkout_methods" => $choMethods,
            ];
        } catch (Exception $e) {
            $this->_coreHelper->log(
                "makeBannerCheckout:: An error occurred at the time of obtaining the payment methods banner: " . $e
            );
        }
    }
}
