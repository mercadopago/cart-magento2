<?php

namespace MercadoPago\Core\Model\System\Message;

use Exception;
use Magento\Backend\Block\Store\Switcher;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\MessageInterface;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Model\Core;

/**
 * Class CustomPixMessageNotification
 */
class CustomPixMessageNotification implements MessageInterface
{
    const MESSAGE_IDENTITY = 'custom_pix_notification';

    const ALLOWED_SITE_ID = 'MLB';

    const PAYMENT_ID_METHOD_PIX = 'pix';

    const PIX_INFORMATION_LINK = 'https://www.mercadopago.com.br/stop/pix?url=https%3A%2F%2Fwww.mercadopago.com.br%2Fadmin-pix-keys%2Fmy-keys&authentication_mode=required';

    /**
     * @var Core
     */
    protected $coreModel;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $configResource;

    /**
     * @var Switcher
     */
    protected $switcher;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * CustomPixMessageNotification constructor.
     *
     * @param Core $coreModel
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $configResource
     * @param Switcher $switcher
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        Core                 $coreModel,
        ScopeConfigInterface $scopeConfig,
        Config               $configResource,
        Switcher             $switcher,
        TypeListInterface    $cacheTypeList,
        Pool                 $cacheFrontendPool
    )
    {
        $this->coreModel = $coreModel;
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->switcher = $switcher;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    } //end __construct()

    /**
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    } //end getIdentity()

    /**
     * @return boolean
     */
    public function isDisplayed()
    {
        try {
            if (false === $this->canConfigurePixGateway()) {
                return false;
            }

            if (true === $this->pixAvailablePaymentPix()) {
                return false;
            }
        } catch (Exception $exception) {
            return false;
        }

        $this->hidePix();

        return true;
    } //end isDisplayed()

    /**
     * @return string
     */
    public function getText()
    {
        return sprintf(
            'Mercado Pago: %s <a href="%s" target="_blank">%s</a>.',
            __('Please note that to receive payments via Pix at our checkout, you must have a Pix key registered in your Mercado Pago account.'),
            self::PIX_INFORMATION_LINK,
            __('Read more')
        );
    } //end getText()

    /**
     * @return void
     */
    public function getSeverity()
    {
        self::SEVERITY_NOTICE;
    } //end getSeverity()

    /**
     * @return boolean
     * @throws LocalizedException
     */
    protected function canConfigurePixGateway()
    {
        $data = $this->coreModel->getUserMe();
        $user = $data['response'];

        if (false === empty($user['site_id']) && self::ALLOWED_SITE_ID === $user['site_id']) {
            return true;
        }

        return false;
    } //end canConfigurePixGateway()

    /**
     * @return boolean
     * @throws LocalizedException
     */
    protected function pixAvailablePaymentPix()
    {
        $data = $this->coreModel->getPaymentMethods();
        $payments = $data['response'];

        foreach ($payments as $payment) {
            if (false === empty($payment['id']) && self::PAYMENT_ID_METHOD_PIX === $payment['id']) {
                return true;
            }
        }

        return false;
    } //end pixAvailablePaymentPix()

    /**
     * @param $paymentActivePath
     */
    protected function disablePayment($paymentActivePath)
    {
        $statusPaymentMethod = $this->scopeConfig->isSetFlag(
            $paymentActivePath,
            ScopeInterface::SCOPE_STORE
        );

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
    } //end disablePayment()

    protected function hidePix()
    {
        $this->disablePayment(ConfigData::PATH_CUSTOM_PIX_ACTIVE);
        $this->cleanConfigCache();
    } //end hidePix()

    protected function cleanConfigCache()
    {
        $this->cacheTypeList->cleanType('config');
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    } //end cleanConfigCache()
}//end class
