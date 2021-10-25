<?php


namespace MercadoPago\Core\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

class CsrfValidatorSkip
{

    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
     * @return void
     */
    public function aroundValidate(\Magento\Framework\App\Request\CsrfValidator $subject, callable $proceed, RequestInterface $request, ActionInterface $action)
    {
        if ($request->getModuleName() == 'mercadopago') {
            return; // Skip CSRF check
        }
        return $proceed($request, $action);
    }
}
