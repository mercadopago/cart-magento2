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
     * @param \Magento\Framework\App\Action\Context      $context
     * @param \MercadoPago\Core\Helper\Data              $coreHelper
     * @param \MercadoPago\Core\Model\Core               $coreModel
     * @param \Magento\Checkout\Model\Session            $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Registry                $registry
     * @param \Magento\Store\Model\StoreManagerInterface                $storeManager
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

    if($action == 'check'){
      $coupon_id = $this->getRequest()->getParam('coupon_id');

      if (empty($coupon_id)) {
        $response = array(
          "status"   => 400,
          "response" => array(
            "error"         => "invalid_id",
            "message"       => __("Invalid coupon code"),
          )
        );
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));

        return;
      }

      //         https://github.com/mercadolibre/mkttools-api/blob/4506f73054e46c437c2d1dc9f191d08e33dda718/webserver/web-app/doc/coupon.json

      $responseApi = $this->coreModel->validCoupon($coupon_id);
      $responseBuyer = array();

      error_log("HERE: " . json_encode($responseApi));

      if($responseApi['status'] == 200 || $responseApi['status'] == 201 ){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode);
        $currencySymbol = $currency->getCurrencySymbol();

        $responseBuyer = $responseApi;
        $couponAmount = $responseApi['response']['coupon_amount'];
        $transactionAmount = $responseApi['response']['params']['transaction_amount'];
        $amountWithDiscount = ($transactionAmount - $couponAmount);

        $html = "";
        $html .= __('You will save <b>%1 %2</b> with discount from %3.', $currencySymbol, $couponAmount, $responseApi['response']['name']) . "<br/>";
        $html .= __('Total of your purchase: <b>%1 %2</b>', $currencySymbol , $transactionAmount) . "<br/>";
        $html .= __('Total of your purchase with discount: <b>%1 %2*</b>', $currencySymbol, $amountWithDiscount) . "<br/>";
        $html .= __('* Uppon payment approval and installment rate.') . "<br/>";
        $html .= "<a href='https://api.mercadolibre.com/campaigns/" . $responseApi['response']['id'] . "/terms_and_conditions?format_type=html' target='_blank'>" . __('Terms and Conditions of Use') . "</a>";

        $responseBuyer['message_to_user'] = $html;


        //set value in session
        $this->checkoutSession->setData('mercadopago_discount_amount', (float) $couponAmount);

      }else{
        if(isset($responseApi['response']) && isset($responseApi['response']['message']) && isset($responseApi['response']['error'])){
          $responseBuyer = array(
            "status"   => 400,
            "response" => array(
              "error"         => $responseApi['response']['error'],
              "message"       => __($responseApi['response']['message'])
            )
          );
        }else{
          $responseBuyer = array(
            "status"   => 400,
            "response" => array(
              "error"         => "unidentified_error",
              "message"       => __("An error has occurred, please try again. If the error persists, please contact the seller.")
            )
          );
        }
      }


      $this->getResponse()->setHeader('Content-type', 'application/json');
      $this->getResponse()->setBody(json_encode($responseBuyer));
      return;


    }else if($action == 'remove'){
      // remove value session
      $this->checkoutSession->setData('mercadopago_discount_amount', 0);

      $responseBuyer = array(
        "status"   => 200
      );
      $this->getResponse()->setHeader('Content-type', 'application/json');
      $this->getResponse()->setBody(json_encode($responseBuyer));
    }

    //       $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    //       $session = $_objectManager->get('Magento\Checkout\Model\Session');

    //       //             $a = $this->_registry->registry('mercadopago_discount_amount');
    //       $a = $session->getData("mercadopago_discount_amount");

    //       error_log("ANTES COUPON AMOUNT: " . $a);


    //       $type = $this->getRequest()->getParam('test');

    //     $response = $this->getArrayErrorResponse();
    //     $jsonData = json_encode($response);

    //       if($type == "insere"){
    //         $a = $session->setData('mercadopago_discount_amount', (float) 10);
    //         //         $this->_registry->register('mercadopago_discount_amount', (float) 10 );

    //       }else{

    //         $a = $session->setData('mercadopago_discount_amount', 0);
    //         //         $this->_registry->register('mercadopago_discount_amount', null );
    //       }


    //       $a = $session->getData("mercadopago_discount_amount");

    //       error_log("COUPON AMOUNT: " . $a);
    //       header( 'HTTP/1.1 400 BAD REQUEST' );
    //       $this->getResponse()->setStatusCode(400);
    //     $this->getResponse()->setHeader('Content-type', 'application/json');
    //     $this->getResponse()->setBody($jsonData);
    //     return;


    //         $coupon_id = $this->getRequest()->getParam('coupon_id');

    //         $this->coreHelper->log("execute discount: " . $coupon_id, 'mercadopago-custom.log');

    //         if (!empty($coupon_id)) {
    //             $response = $this->coreModel->validCoupon($coupon_id);
    //         } else {
    //             $response = $this->getArrayErrorResponse();
    //         }
    //         if ($response['status'] != 200 && $response['status'] != 201) {
    //             $response = $this->getArrayErrorResponse();
    //         }
    //         //save value to DiscountCoupon collect
    //         $this->_registry->register('mercadopago_discount_amount', (float)$response['response']['coupon_amount']);
    //         $quote = $this->_checkoutSession->getQuote();
    //         $this->quoteRepository->save($quote->collectTotals());
    //         $jsonData = json_encode($response);
    //         $this->getResponse()->setHeader('Content-type', 'application/json');
    //         $this->getResponse()->setBody($jsonData);
  }

  /**
     * Return array with error response params
     *
     * @return array
     */
  protected function getArrayErrorResponse()
  {
    $result = [
      "status"   => 400,
      "response" => [
        "error"         => "invalid_id",
        "message"       => "invalid id",
        "coupon_amount" => 0
      ]
    ];

    return $result;
  }

}