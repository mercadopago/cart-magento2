<?php

namespace MercadoPago\Core\Plugin;

/**
 * Class MinificationIsExcludedPlugin
 *
 * @package MercadoPago\Core\Plugin
 */
class MinificationIsExcludedPlugin
{
    public function __construct()
    {
    }

    public function aroundGetExcludes(\Magento\Framework\View\Asset\Minification $minification, callable $proceed, $contentType)
    {
        $returnValue = $proceed($contentType);
        if ($contentType == 'js') {
            $returnValue[] = 'mercadopago.js';
        }

        return $returnValue;
    }
}
