<?php

namespace MercadoPago\Core\Lib;
/**
 * MercadoPago Integration Library
 * Access MercadoPago for payments integration
 *
 * @author hcasatti
 *
 */
class Api
{

    /**
     *
     */
    const version = "0.3.3";

    /**
     * @var mixed
     */
    private $client_id;
    /**
     * @var mixed
     */
    private $client_secret;
    /**
     * @var mixed
     */
    private $ll_access_token;
    /**
     * @var
     */
    private $access_data;
    /**
     * @var bool
     */
    private $sandbox = FALSE;

    /**
     * @var null
     */
    private $_platform = null;
    /**
     * @var null
     */
    private $_so = null;
    /**
     * @var null
     */
    private $_type = null;


    /**
     * \MercadoPago\Core\Lib\Api constructor.
     */
    public function __construct()
    {
        $i = func_num_args();

        if ($i > 2 || $i < 1) {
            throw new \Exception('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN');
        }

        if ($i == 1) {
            $this->ll_access_token = func_get_arg(0);
        }

        if ($i == 2) {
            $this->client_id = func_get_arg(0);
            $this->client_secret = func_get_arg(1);
        }
    }

    /**
     * @param null $enable
     *
     * @return bool
     */
    public function sandbox_mode($enable = NULL)
    {
        if (!is_null($enable)) {
            $this->sandbox = $enable === TRUE;
        }

        return $this->sandbox;
    }

    /**
     * Get Access Token for API use
     */
    public function get_access_token()
    {
        if (isset ($this->ll_access_token) && !is_null($this->ll_access_token)) {
            return $this->ll_access_token;
        }

        $app_client_values = $this->build_query(array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials'
        ));

        $access_data = \MercadoPago\Core\Lib\RestClient::post("/oauth/token", $app_client_values, "application/x-www-form-urlencoded");

        if ($access_data["status"] != 200) {
            throw new \Exception ($access_data['response']['message'], $access_data['status']);
        }

        $this->access_data = $access_data['response'];

        return $this->access_data['access_token'];
    }

    /**
     * Get information for specific authorized payment
     * @param id
     * @return array(json)
     */
    public function get_authorized_payment($id)
    {
        $access_token = $this->get_access_token();

        $authorized_payment_info = \MercadoPago\Core\Lib\RestClient::get("/authorized_payments/" . $id, null, ["Authorization: Bearer " . $access_token]);
        return $authorized_payment_info;
    }

    /**
     * Refund accredited payment
     * @param int $id
     * @return array(json)
     */
    public function refund_payment($id)
    {
        $access_token = $this->get_access_token();

        $response = \MercadoPago\Core\Lib\RestClient::post("/v1/payments/$id/refunds", array(), null, ["Authorization: Bearer " . $access_token]);
        return $response;
    }

    /**
     * Cancel preapproval payment
     * @param int $id
     * @return array(json)
     */
    public function cancel_preapproval_payment($id)
    {
        $access_token = $this->get_access_token();

        $cancel_status = array(
            "status" => "cancelled"
        );

        $response = \MercadoPago\Core\Lib\RestClient::put("/preapproval/" . $id, $cancel_status, null, ["Authorization: Bearer " . $access_token]);
        return $response;
    }

    /**
     * Create a checkout preference
     * @param array $preference
     * @return array(json)
     */
    public function create_preference($preference)
    {
        $access_token = $this->get_access_token();

        $extra_params = array(
            'platform: ' . $this->_platform, 'so;',
            'type: ' . $this->_type,
            'Authorization: Bearer ' . $access_token);
        $preference_result = \MercadoPago\Core\Lib\RestClient::post("/checkout/preferences", $preference, "application/json", $extra_params);
        return $preference_result;
    }

    /**
     * Update a checkout preference
     * @param string $id
     * @param array $preference
     * @return array(json)
     */
    public function update_preference($id, $preference)
    {
        $access_token = $this->get_access_token();

        $preference_result = \MercadoPago\Core\Lib\RestClient::put("/checkout/preferences/{$id}", $preference, null, ["Authorization: Bearer " . $access_token]);
        return $preference_result;
    }

    /**
     * Get a checkout preference
     * @param string $id
     * @return array(json)
     */
    public function get_preference($id)
    {
        $access_token = $this->get_access_token();

        $preference_result = \MercadoPago\Core\Lib\RestClient::get("/checkout/preferences/{$id}", null, ["Authorization: Bearer " . $access_token]);
        return $preference_result;
    }

    /**
     * Create a preapproval payment
     * @param array $preapproval_payment
     * @return array(json)
     */
    public function create_preapproval_payment($preapproval_payment)
    {
        $access_token = $this->get_access_token();

        $preapproval_payment_result = \MercadoPago\Core\Lib\RestClient::post("/preapproval", $preapproval_payment, null, ["Authorization: Bearer " . $access_token]);
        return $preapproval_payment_result;
    }

    /**
     * Get a preapproval payment
     * @param string $id
     * @return array(json)
     */
    public function get_preapproval_payment($id)
    {
        $access_token = $this->get_access_token();

        $preapproval_payment_result = \MercadoPago\Core\Lib\RestClient::get("/preapproval/{$id}", null, ["Authorization: Bearer " . $access_token]);
        return $preapproval_payment_result;
    }

    /**
     * Update a preapproval payment
     * @param string $preapproval_payment , $id
     * @return array(json)
     */

    public function update_preapproval_payment($id, $preapproval_payment)
    {
        $access_token = $this->get_access_token();

        $preapproval_payment_result = \MercadoPago\Core\Lib\RestClient::put("/preapproval/" . $id, $preapproval_payment, null, ["Authorization: Bearer " . $access_token]);
        return $preapproval_payment_result;
    }

    /**
     * Create a custon payment
     * @param array $preference
     * @return array(json)
     */
    public function create_custon_payment($info)
    {
        $access_token = $this->get_access_token();

        $preference_result = \MercadoPago\Core\Lib\RestClient::post("/checkout/custom/create_payment", $info, null, ["Authorization: Bearer " . $access_token]);
        return $preference_result;
    }


    public function get_or_create_customer($payer_email)
    {
        $customer = $this->search_customer($payer_email);
        if ($customer['status'] == 200 && $customer['response']['paging']['total'] > 0) {
            $customer = $customer['response']['results'][0];
        } else {
            $customer = $this->create_customer($payer_email)['response'];
        }
        return $customer;
    }

    public function create_customer($email)
    {

        $access_token = $this->get_access_token();

        $request = array(
            "email" => $email
        );

        $customer = \MercadoPago\Core\Lib\RestClient::post("/v1/customers", $request, null, ["Authorization: Bearer " . $access_token]);

        return $customer;
    }

    public function search_customer($email)
    {

        $access_token = $this->get_access_token();

        $customer = \MercadoPago\Core\Lib\RestClient::get("/v1/customers/search?email=" . $email, null, ["Authorization: Bearer " . $access_token]);
        return $customer;
    }

    public function create_card_in_customer($customer_id, $token, $payment_method_id = null, $issuer_id = null)
    {

        $access_token = $this->get_access_token();

        $request = array(
            "token" => $token,
            "issuer_id" => $issuer_id,
            "payment_method_id" => $payment_method_id
        );

        $card = \MercadoPago\Core\Lib\RestClient::post("/v1/customers/" . $customer_id . "/cards", $request, null, ["Authorization: Bearer " . $access_token]);

        return $card;
    }

    public function get_all_customer_cards($customer_id, $token)
    {

        $access_token = $this->get_access_token();

        $cards = \MercadoPago\Core\Lib\RestClient::get("/v1/customers/" . $customer_id . "/cards", null, ["Authorization: Bearer " . $access_token]);

        return $cards;
    }

    public function check_discount_campaigns($transaction_amount, $payer_email, $coupon_code)
    {

        $access_token = $this->get_access_token();
        $url = "/discount_campaigns?transaction_amount=$transaction_amount&payer_email=$payer_email&coupon_code=$coupon_code";
        $discount_info = \MercadoPago\Core\Lib\RestClient::get($url, null, ["Authorization: Bearer " . $access_token]);

        return $discount_info;
    }

    /**
     * @return bool
     */
    public function is_valid_access_token()
    {
        if (empty($this->ll_access_token)) {
            return false;
        }

        try {
            $response = $this->get("/v1/payment_methods");

            if (empty($response)) {
                return false;
            }

            if ((isset($response['status'])) && ($response['status'] == 401 || $response['status'] == 400)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /* Generic resource call methods */

    /**
     * Generic resource get
     * @param uri
     * @param params
     * @param authenticate = true
     */
    public function get($uri, $params = null, $authenticate = true)
    {
        $params = is_array($params) ? $params : array();

        $access_token = null;
        if ($authenticate !== false) {
            $access_token = $this->get_access_token();
        }

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $result = \MercadoPago\Core\Lib\RestClient::get($uri, null, ["Authorization: Bearer " . $access_token]);
        return $result;
    }

    /**
     * Generic resource post
     * @param uri
     * @param data
     * @param params
     */
    public function post($uri, $data, $params = null)
    {
        $params = is_array($params) ? $params : array();

        $access_token = $this->get_access_token();

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $extra_params = array(
            'platform: ' . $this->_platform, 'so;',
            'type: ' . $this->_type,
            'Authorization: Bearer ' . $access_token);
        $result = \MercadoPago\Core\Lib\RestClient::post($uri, $data, "application/json", $extra_params);
        return $result;
    }

    /**
     * Generic resource put
     * @param uri
     * @param data
     * @param params
     */
    public function put($uri, $data, $params = null)
    {
        $params = is_array($params) ? $params : array();

        $access_token = $this->get_access_token();

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $result = \MercadoPago\Core\Lib\RestClient::put($uri, $data, null, ["Authorization: Bearer " . $access_token]);
        return $result;
    }

    /**
     * Generic resource delete
     * @param uri
     * @param data
     * @param params
     */
    public function delete($uri, $params = null)
    {
        $params = is_array($params) ? $params : array();

        $access_token = $this->get_access_token();

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $result = \MercadoPago\Core\Lib\RestClient::delete($uri, null, ["Authorization: Bearer " . $access_token]);
        return $result;
    }

    /* **************************************************************************************** */

    /**
     * @param $params
     *
     * @return string
     */
    private function build_query($params)
    {
        if (function_exists("http_build_query")) {
            return http_build_query($params, "", "&");
        } else {
            $elements = [];
            foreach ($params as $name => $value) {
                $elements[] = "{$name}=" . urlencode($value);
            }

            return implode("&", $elements);
        }
    }

    /**
     * @param null $platform
     */
    public function set_platform($platform)
    {
        $this->_platform = $platform;
    }

    /**
     * @param null $so
     */
    public function set_so($so = '')
    {
        $this->_so = $so;
    }

    /**
     * @param null $type
     */
    public function set_type($type)
    {
        $this->_type = $type;
    }

}

