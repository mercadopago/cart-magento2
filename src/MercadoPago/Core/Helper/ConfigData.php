<?php

namespace MercadoPago\Core\Helper;

class ConfigData
{
  //credentials path
  const PATH_ACCESS_TOKEN    = 'payment/mercadopago_credentials/access_token';
  const PATH_PUBLIC_KEY      = 'payment/mercadopago_credentials/public_key';

  //configuration hidden path
  const PATH_SITE_ID         = 'payment/mercadopago/site_id';
  const PATH_SPONSOR_ID      = 'payment/mercadopago/sponsor_id';

  //custom method credit and debit card
  const PATH_CUSTOM_BINARY_MODE   = 'payment/mercadopago/custom_checkout/binary_mode';
  const PATH_CUSTOM_STATEMENT_DESCRIPTOR   = 'payment/mercadopago/custom_checkout/statement_descriptor';
  const PATH_CUSTOM_BANNER   = 'payment/mercadopago/custom_checkout/banner_checkout';
  const PATH_CUSTOM_COUPON   = 'payment/mercadopago/custom_checkout/coupon_mercadopago';

  //custom method ticket
  const PATH_CUSTOM_TICKET_COUPON   = 'payment/mercadopago_customticket/coupon_mercadopago';
  const PATH_CUSTOM_TICKET_BANNER   = 'payment/mercadopago_customticket/banner_checkout';
  const PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS   = 'payment/custom_checkout_ticket/excluded_payment_methods';


  //advanced configuration
  const PATH_ADVANCED_LOG  = 'payment/mercadopago_advanced/logs';
  const PATH_ADVANCED_CATEGORY  = 'payment/mercadopago_advanced/category_id';

}