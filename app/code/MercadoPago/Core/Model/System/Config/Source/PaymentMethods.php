<?php
namespace MercadoPago\Core\Model\System\Config\Source;

/**
 * Class PaymentMethods
 *
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class PaymentMethods
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * path to access token config
     *
     * @var string
     */
    const XML_PATH_ACCESS_TOKEN = 'payment/mercadopago_custom/access_token';
    /**
     * path to client id config
     *
     * @var string
     */
    const XML_PATH_CLIENT_ID = 'payment/mercadopago_standard/client_id';
    /**
     * path to client secret config
     *
     * @var string
     */
    const XML_PATH_CLIENT_SECRET = 'payment/mercadopago_standard/client_secret';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $coreHelper;

    protected $_switcher;

    /**
     * PaymentMethods constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MercadoPago\Core\Helper\Data                      $coreHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Backend\Block\Store\Switcher $switcher
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->coreHelper = $coreHelper;
        $this->_switcher = $switcher;
    }

    /**
     * Return available payment methods
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];

        //default empty value
        $methods[] = ["value" => "", "label" => ""];
        $accessToken = $this->scopeConfig->getValue(self::XML_PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $clientId = $this->scopeConfig->getValue(self::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $clientSecret = $this->scopeConfig->getValue(self::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $meHelper = $this->coreHelper;

        if (empty($accessToken) && !$meHelper->isValidClientCredentials($clientId, $clientSecret)) {
            return $methods;
        }

        //if accessToken is empty uses clientId and clientSecret to obtain it
        if (empty($accessToken)) {
            $accessToken = $meHelper->getAccessToken();
        }

        $country = $this->scopeConfig->getValue('payment/mercadopago/country', 'website', $this->_switcher->getWebsiteId());

        $meHelper->log("Get payment methods by country... ", 'mercadopago');
        $meHelper->log("API payment methods: " . "/v1/payment_methods?access_token=" . $accessToken, 'mercadopago');
        $response = \MercadoPago\Core\Lib\RestClient::get('/sites/'. strtoupper($country) .'/payment_methods?marketplace=NONE');

        $meHelper->log("API payment methods", 'mercadopago', $response);

        if (isset($response['error'])) {
            return $methods;
        }

        $response = $response['response'];

        foreach ($response as $m) {
            if (isset($m['id']) && $m['id'] != 'account_money') {
                $methods[] = [
                    'value' => $m['id'],
                    'label' => __($m['name'])
                ];
            }
        }

        return $methods;
    }
}
