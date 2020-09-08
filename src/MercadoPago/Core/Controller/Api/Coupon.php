<?php

namespace MercadoPago\Core\Controller\Api;


/**
 * Class Coupon
 *
 * @package Mercadopago\Core\Controller\Notifications
 */
class Coupon
    extends \Magento\Framework\App\Action\Action

{
    /**
     * @var \MercadoPago\Core\Helper\
     */
    protected $coreHelper;

    /**
     * @var \MercadoPago\Core\Model\Core
     */
    protected $coreModel;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    protected $_storeManager;


    /**
     * Coupon constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \MercadoPago\Core\Helper\Data $coreHelper
     * @param \MercadoPago\Core\Model\Core $coreModel
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \MercadoPago\Core\Model\Core $coreModel,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
        $this->coreHelper = $coreHelper;
        $this->coreModel = $coreModel;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
    }

    /**
     * Fetch coupon info
     *
     * Controller Action
     */
    public function execute()
    {

        $action = $this->getRequest()->getParam('action');

        if ($action == 'check') {
            $coupon_id = $this->getRequest()->getParam('coupon_id');
            $payer_email = $this->getRequest()->getParam('payer_email');


            if (empty($coupon_id)) {
                $response = array(
                    "status" => 400,
                    "response" => array(
                        "error" => "invalid_id",
                        "message" => __("Invalid coupon code"),
                    )
                );
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody(json_encode($response));

                return;
            }

            $responseApi = $this->coreModel->validCoupon($coupon_id, $payer_email);
            $responseBuyer = array();

            if ($responseApi['status'] == 200 || $responseApi['status'] == 201) {

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
                $currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode);
                $currencySymbol = $currency->getCurrencySymbol();

                $responseBuyer = $responseApi;
                $couponAmount = $responseApi['response']['coupon_amount'];
                $transactionAmount = $responseApi['response']['params']['transaction_amount'];
                $amountWithDiscount = (float)($transactionAmount - $couponAmount);

                $html = "";
                $html .= __('You will save <b>%1 %2</b> with discount from %3.', $currencySymbol, number_format($couponAmount, 2), $responseApi['response']['name']) . "<br/>";
                $html .= __('Total of your purchase: <b>%1 %2</b>', $currencySymbol, number_format($transactionAmount, 2)) . "<br/>";
                $html .= __('Total of your purchase with discount: <b>%1 %2*</b>', $currencySymbol, number_format($amountWithDiscount, 2)) . "<br/>";
                $html .= __('* Uppon payment approval.') . "<br/>";
                $html .= "<a href='https://api.mercadolibre.com/campaigns/" . $responseApi['response']['id'] . "/terms_and_conditions?format_type=html' target='_blank'>" . __('Terms and Conditions of Use') . "</a>";

                $responseBuyer['message_to_user'] = $html;

                //set value in session
                $this->checkoutSession->setData('mercadopago_discount_amount', (float)$couponAmount);

            } else {

                $responseBuyer = array(
                    "status" => 400,
                    "response" => array(
                        "error" => "unidentified_error",
                        "message" => __("An error has occurred, please try again. If the error persists, please contact the seller.")
                    ),
                    "data" => array(
                        "coupon_code" => $coupon_id
                    )
                );

                if (isset($responseApi['response']) && isset($responseApi['response']['message']) && isset($responseApi['response']['error'])) {
                    $responseBuyer["response"]["error"] = $responseApi['response']['error'];
                    $responseBuyer["response"]["message"] = __($responseApi['response']['message']);
                }

                $this->coreHelper->log("Coupon::execute - Response API - Check", 'mercadopago-custom.log', $responseApi);

            }


            $this->coreHelper->log("Coupon::execute - Response to buyer - Check", 'mercadopago-custom.log', $responseBuyer);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($responseBuyer));
            return;


        } else if ($action == 'remove') {
            // remove value session
            $this->checkoutSession->setData('mercadopago_discount_amount', 0);

            $responseBuyer = array(
                "status" => 200
            );

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($responseBuyer));
            return;
        }
    }
}