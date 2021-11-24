<?php

namespace MercadoPago\Core\Lib;

/**
 * MercadoPago Integration Library
 * Access MercadoPago for payments integration
 *
 * @author hcasatti
 * 
 * @codeCoverageIgnore
 *
 */
class Api implements ApiInterface
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
    private $sandbox = false;

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

        /*if ($i > 2 || $i < 1) {
            throw new \Exception('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN');
        }*/

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
    public function sandbox_mode($enable = null)
    {
        if (!is_null($enable)) {
            $this->sandbox = $enable === true;
        }

        return $this->sandbox;
    }

    public function set_access_token($access_token)
    {
        $this->ll_access_token = $access_token;
    }

    /**
     * Get Access Token for API use
     *
     * @return false|mixed
     * @throws \Exception
     */
    public function get_access_token()
    {
        if (isset($this->ll_access_token) && !is_null($this->ll_access_token)) {
            return $this->ll_access_token;
        }

        $app_client_values = $this->build_query([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials'
        ]);

        $access_data = RestClient::post("/oauth/token", $app_client_values, "application/x-www-form-urlencoded");

        if ($access_data["status"] != 200) {
            throw new \Exception($access_data['response']['message'], $access_data['status']);
        }

        $this->access_data = $access_data['response'];

        return $this->access_data['access_token'];
    }

    /**
     * Get information for specific authorized payment
     * @param id
     * @return array(json)
     * @throws \Exception
     */
    public function get_authorized_payment($id)
    {
        $access_token = $this->get_access_token();

        return RestClient::get("/authorized_payments/" . $id, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Refund accredited payment
     * @param int $id
     * @return array(json)
     * @throws \Exception
     */
    public function refund_payment($id)
    {
        $access_token = $this->get_access_token();

        return RestClient::post("/v1/payments/$id/refunds", [], null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Cancel preapproval payment
     * @param int $id
     * @return array(json)
     * @throws \Exception
     */
    public function cancel_preapproval_payment($id)
    {
        $access_token = $this->get_access_token();

        $cancel_status = [
            "status" => "cancelled"
        ];

        return RestClient::put("/preapproval/" . $id, $cancel_status, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Create a checkout preference
     * @param array $preference
     * @return array(json)
     * @throws \Exception
     */
    public function create_preference($preference)
    {
        $access_token = $this->get_access_token();

        $extra_params = [
            'platform: ' . $this->_platform, 'so;',
            'type: ' . $this->_type,
            'Authorization: Bearer ' . $access_token
        ];

        return RestClient::post("/checkout/preferences", $preference, "application/json", $extra_params);
    }

    /**
     * Update a checkout preference
     * @param string $id
     * @param array $preference
     * @return array(json)
     * @throws \Exception
     */
    public function update_preference($id, $preference)
    {
        $access_token = $this->get_access_token();

        return RestClient::put("/checkout/preferences/{$id}", $preference, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Get a checkout preference
     * @param string $id
     * @return array(json)
     * @throws \Exception
     */
    public function get_preference($id)
    {
        $access_token = $this->get_access_token();

        return RestClient::get("/checkout/preferences/{$id}", null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Create a preapproval payment
     * @param array $preapproval_payment
     * @return array(json)
     * @throws \Exception
     */
    public function create_preapproval_payment($preapproval_payment)
    {
        $access_token = $this->get_access_token();

        return RestClient::post("/preapproval", $preapproval_payment, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Get a preapproval payment
     * @param string $id
     * @return array(json)
     * @throws \Exception
     */
    public function get_preapproval_payment($id)
    {
        $access_token = $this->get_access_token();

        return RestClient::get("/preapproval/{$id}", null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Update a preapproval payment
     * @param $id
     * @param string $preapproval_payment
     * @return array
     * @throws \Exception
     */
    public function update_preapproval_payment($id, $preapproval_payment)
    {
        $access_token = $this->get_access_token();

        return RestClient::put("/preapproval/" . $id, $preapproval_payment, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Create a custon payment
     * @param array $preference
     * @return array(json)
     * @throws \Exception
     */
    public function create_custon_payment($info)
    {
        $access_token = $this->get_access_token();

        return RestClient::post("/checkout/custom/create_payment", $info, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * @param $payer_email
     * @return mixed
     */
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

    /**
     * @param $email
     * @return array
     * @throws \Exception
     */
    public function create_customer($email)
    {
        $access_token = $this->get_access_token();

        $request = [
            "email" => $email
        ];

        return RestClient::post("/v1/customers", $request, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * @param $email
     * @return array
     * @throws \Exception
     */
    public function search_customer($email)
    {
        $access_token = $this->get_access_token();

        return RestClient::get("/v1/customers/search?email=" . $email, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * @param $customer_id
     * @param $token
     * @param null $payment_method_id
     * @param null $issuer_id
     * @return array
     * @throws \Exception
     */
    public function create_card_in_customer($customer_id, $token, $payment_method_id = null, $issuer_id = null)
    {
        $access_token = $this->get_access_token();

        $request = [
            "token" => $token,
            "issuer_id" => $issuer_id,
            "payment_method_id" => $payment_method_id
        ];

        return RestClient::post("/v1/customers/" . $customer_id . "/cards", $request, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * @param $customer_id
     * @param $token
     * @return array
     * @throws \Exception
     */
    public function get_all_customer_cards($customer_id, $token)
    {
        $access_token = $this->get_access_token();

        return RestClient::get("/v1/customers/" . $customer_id . "/cards", null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * @param $transaction_amount
     * @param $payer_email
     * @param $coupon_code
     * @return array
     * @throws \Exception
     */
    public function check_discount_campaigns($transaction_amount, $payer_email, $coupon_code)
    {
        $access_token = $this->get_access_token();
        $url = "/discount_campaigns?transaction_amount=$transaction_amount&payer_email=$payer_email&coupon_code=$coupon_code";
        return RestClient::get($url, null, ["Authorization: Bearer " . $access_token]);
    }
    
    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function get_merchant_order($id)
    {
        $access_token = $this->get_access_token();

        return RestClient::get(
            "/merchant_orders/{$id}",
            null,
            ["Authorization: Bearer " . $access_token]
        );
    }

    /**
     * @param $id
     * @param $order
     * @return array
     * @throws \Exception
     */
    public function update_merchant_order($id, $order)
    {
        $access_token = $this->get_access_token();

        return RestClient::put(
            "/checkout/preferences/{$id}",
            $order,
            null,
            ["Authorization: Bearer " . $access_token]
        );
    }

    /* Generic resource call methods */

    /**
     * Generic resource get
     * @param $uri
     * @param null $params
     * @param bool $authenticate
     * @return array
     * @throws \Exception
     */
    public function get($uri, $params = null, $authenticate = true)
    {
        $params = is_array($params) ? $params : [];

        $access_token = null;
        if ($authenticate !== false) {
            $access_token = $this->get_access_token();
        }

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        return RestClient::get($uri, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Generic resource post
     * @param $uri
     * @param $data
     * @param null $params
     * @return array
     * @throws \Exception
     */
    public function post($uri, $data, $params = null)
    {
        $params = is_array($params) ? $params : [];

        $access_token = $this->get_access_token();

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $extra_params = [
            'platform: ' . $this->_platform, 'so;',
            'type: ' . $this->_type,
            'Authorization: Bearer ' . $access_token
        ];

        return RestClient::post($uri, $data, "application/json", $extra_params);
    }

    /**
     * Generic resource put
     * @param $uri
     * @param $data
     * @param null $params
     * @return array
     * @throws \Exception
     */
    public function put($uri, $data, $params = null)
    {
        $params = is_array($params) ? $params : [];

        $access_token = $this->get_access_token();

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        return RestClient::put($uri, $data, null, ["Authorization: Bearer " . $access_token]);
    }

    /**
     * Generic resource delete
     * @param $uri
     * @param null $params
     * @return array
     * @throws \Exception
     */
    public function delete($uri, $params = null)
    {
        $params = is_array($params) ? $params : [];

        $access_token = $this->get_access_token();

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        return RestClient::delete($uri, null, ["Authorization: Bearer " . $access_token]);
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
