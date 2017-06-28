<?php
namespace MercadoPago\Core\Helper;

/**
 * Class Response
 *
 * Http codes
 *
 * @package MercadoPago\Core\Helper
 */
class Response
{
    /*
     * HTTP Response Codes
     */
    const HTTP_OK                 = 200;
    const HTTP_CREATED            = 201;
    const HTTP_MULTI_STATUS       = 207;
    const HTTP_BAD_REQUEST        = 400;
    const HTTP_UNAUTHORIZED       = 401;
    const HTTP_FORBIDDEN          = 403;
    const HTTP_NOT_FOUND          = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE     = 406;
    const HTTP_INTERNAL_ERROR     = 500;

    const INFO_MERCHANT_ORDER_NOT_FOUND     = 'Merchant Order not found';
    const INFO_STATUS_NOT_FINAL     = 'Status not final';
    const INFO_EXTERNAL_REFERENCE_NOT_FOUND     = 'External reference not found';
    const INFO_ORDER_CANCELED = 'The order is canceled';

    const TOPIC_RECURRING_PAYMENT = 'preapproval';
    const TOPIC_PAYMENT = 'payment';
}