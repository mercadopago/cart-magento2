<?php

namespace MercadoPago\Core\Controller\Api;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;

class Subtotals extends Action
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * Coupon constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->_registry = $registry;
    }

    /**
     * * Fetch coupon info
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $total = $this->getRequest()->getParam('cost');
        $quote = $this->_checkoutSession->getQuote();
        $this->_registry->register('mercadopago_total_amount', $total);
        $this->quoteRepository->save($quote->collectTotals());
    }
}
