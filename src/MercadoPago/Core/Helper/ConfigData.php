<?php

namespace MercadoPago\Core\Helper;

class ConfigData
{
    //credentials path
    const PATH_ACCESS_TOKEN = 'payment/mercadopago/access_token';
    const PATH_PUBLIC_KEY = 'payment/mercadopago/public_key';

    //configuration hidden path
    const PATH_SITE_ID = 'payment/mercadopago/site_id';
    const PATH_SPONSOR_ID = 'payment/mercadopago/sponsor_id';

    //custom method credit and debit card
    const PATH_CUSTOM_ACTIVE = 'payment/mercadopago_custom/active';
    const PATH_CUSTOM_BINARY_MODE = 'payment/mercadopago_custom/binary_mode';
    const PATH_CUSTOM_STATEMENT_DESCRIPTOR = 'payment/mercadopago_custom/statement_descriptor';
    const PATH_CUSTOM_BANNER = 'payment/mercadopago_custom/banner_checkout';
    const PATH_CUSTOM_COUPON = 'payment/mercadopago_custom/coupon_mercadopago';
    const PATH_CUSTOM_GATEWAY_MODE = 'payment/mercadopago_custom/gateway_mode';
    const PATH_CUSTOM_WALLET_BUTTON = 'payment/mercadopago_custom/wallet_button';

    //custom method ticket
    const PATH_CUSTOM_TICKET_ACTIVE = 'payment/mercadopago_customticket/active';
    const PATH_CUSTOM_TICKET_COUPON = 'payment/mercadopago_customticket/coupon_mercadopago';
    const PATH_CUSTOM_TICKET_BANNER = 'payment/mercadopago_customticket/banner_checkout';
    const PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS = 'payment/mercadopago_customticket/excluded_payment_methods';

    //custom method bank transfer
    const PATH_CUSTOM_BANK_TRANSFER_ACTIVE = 'payment/mercadopago_custom_bank_transfer/active';
    const PATH_CUSTOM_BANK_TRANSFER_BANNER = 'payment/mercadopago_custom_bank_transfer/banner_checkout';
    const PATH_CUSTOM_BANK_TRANSFER_REDIRECT_PAYER = 'payment/mercadopago_custom_bank_transfer/redirect_payer';

    //basic method
    const PATH_BASIC_ACTIVE = 'payment/mercadopago_basic/active';
    const PATH_BASIC_TITLE = 'payment/mercadopago_basic/title';
    const PATH_BASIC_URL_FAILURE = 'payment/mercadopago_basic/url_failure';
    const PATH_BASIC_MAX_INSTALLMENTS = 'payment/mercadopago_basic/max_installments';
    const PATH_BASIC_AUTO_RETURN = 'payment/mercadopago_basic/auto_return';
    const PATH_BASIC_EXCLUDE_PAYMENT_METHODS = 'payment/mercadopago_basic/excluded_payment_methods';
    const PATH_BASIC_STATEMENT_DESCRIPTION = 'payment/mercadopago_basic/statement_desc';
    const PATH_BASIC_EXPIRATION_TIME_PREFERENCE = 'payment/mercadopago_basic/exp_time_pref';
    const PATH_BASIC_ORDER_STATUS = 'payment/mercadopago_basic/order_status';
    const PATH_BASIC_BINARY_MODE = 'payment/mercadopago_basic/binary_mode';
    const PATH_BASIC_GATEWAY_MODE = 'payment/mercadopago_basic/gateway_mode';

    //custom method pix
    const PATH_CUSTOM_PIX_ACTIVE = 'payment/mercadopago_custom_pix/active';
    const PATH_CUSTOM_PIX_BANNER = 'payment/mercadopago_custom_pix/banner_checkout';
    const PATH_CUSTOM_PIX_EXPIRATION_MINUTES = 'payment/mercadopago_custom_pix/expiration_minutes';

    //order configuration
    const PATH_ORDER_APPROVED = 'payment/mercadopago/order_status_approved';
    const PATH_ORDER_IN_PROCESS = 'payment/mercadopago/order_status_in_process';
    const PATH_ORDER_PENDING = 'payment/mercadopago/order_status_pending';
    const PATH_ORDER_REJECTED = 'payment/mercadopago/order_status_rejected';
    const PATH_ORDER_CANCELLED = 'payment/mercadopago/order_status_cancelled';
    const PATH_ORDER_CHARGED_BACK = 'payment/mercadopago/order_status_chargeback';
    const PATH_ORDER_IN_MEDIATION = 'payment/mercadopago/order_status_in_mediation';
    const PATH_ORDER_REFUNDED = 'payment/mercadopago/order_status_refunded';
    const PATH_ORDER_PARTIALLY_REFUNDED = 'payment/mercadopago/order_status_partially_refunded';
    const PATH_ORDER_REFUND_AVAILABLE = 'payment/mercadopago/refund_available';
    const PATH_ORDER_CANCEL_AVAILABLE = 'payment/mercadopago/cancel_payment';

    //advanced configuration
    const PATH_ADVANCED_LOG = 'payment/mercadopago/logs';
    const PATH_ADVANCED_CATEGORY = 'payment/mercadopago/category_id';
    const PATH_ADVANCED_SUCCESS_PAGE = 'payment/mercadopago/use_successpage_mp';
    const PATH_ADVANCED_CONSIDER_DISCOUNT = 'payment/mercadopago/consider_discount';
    const PATH_ADVANCED_INTEGRATOR = 'payment/mercadopago/integrator_id';
    const PATH_ADVANCED_SAVE_TRANSACTION = 'payment/mercadopago/save_transaction';
    const PATH_ADVANCED_EMAIL_CREATE = 'payment/mercadopago/email_order_create';
    const PATH_ADVANCED_EMAIL_UPDATE = 'payment/mercadopago/email_order_update';
}
