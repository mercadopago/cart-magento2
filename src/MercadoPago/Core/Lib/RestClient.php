<?php

namespace MercadoPago\Core\Lib;

use MercadoPago\Core\Helper\ConfigData;
use Magento\Framework\App\ObjectManager;

/**
 * MercadoPago cURL RestClient
 *
 * @codeCoverageIgnore
 */
class RestClient
{
    /**
     * API URL
     */
    const API_BASE_URL = "https://api.mercadopago.com";

    /**
     * Product Id
     */
    const PRODUCT_ID = "BC32CANTRPP001U8NHO0";

    /**
     * Platform Id
     */
    const PLATAFORM_ID = "BP1EF6QIC4P001KBGQ10";

    /**
     * @param       $uri
     * @param       $method
     * @param       $content_type
     * @param array $extra_params
     *
     * @return resource
     * @throws Exception
     */
    private static function get_connect($uri, $method, $content_type, $extra_params = [])
    {
        if (!extension_loaded("curl")) {
            throw new \Exception("cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.");
        }

        $connect = curl_init(self::API_BASE_URL . $uri);

        curl_setopt($connect, CURLOPT_USERAGENT, "Mercadopago Plugin for Magento 2.3 and 2.4");
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($connect, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $header_opt = ["Accept: application/json", "Content-Type: " . $content_type];

        //set x_product_id
        if ($method == 'POST') {
            $header_opt[] = "x-product-id: " . self::PRODUCT_ID;
            $header_opt[] = 'x-platform-id:' . self::PLATAFORM_ID;
            $header_opt[] = 'x-integrator-id:' . self::getIntegratorID();
        }

        if (count($extra_params) > 0) {
            $header_opt = array_merge($header_opt, $extra_params);
        }

        curl_setopt($connect, CURLOPT_HTTPHEADER, $header_opt);

        return $connect;
    }

    /**
     * @param $connect
     * @param $data
     * @param $content_type
     *
     * @throws Exception
     */
    private static function set_data(&$connect, $data, $content_type)
    {
        if ($content_type == "application/json") {
            if (gettype($data) == "string") {
                json_decode($data, true);
            } else {
                $data = json_encode($data);
            }

            if (function_exists('json_last_error')) {
                $json_error = json_last_error();
                if ($json_error != JSON_ERROR_NONE) {
                    throw new \Exception("JSON Error [{$json_error}] - Data: {$data}");
                }
            }
        }

        curl_setopt($connect, CURLOPT_POSTFIELDS, $data);
    }

    /**
     * @param $method
     * @param $uri
     * @param $data
     * @param $content_type
     * @param $extra_params
     *
     * @return array
     * @throws Exception
     */
    private static function exec($method, $uri, $data, $content_type, $extra_params)
    {

        $connect = self::get_connect($uri, $method, $content_type, $extra_params);
        if ($data) {
            self::set_data($connect, $data, $content_type);
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        if ($api_result === false) {
            throw new \Exception(curl_error($connect));
        }

        $response = [
            "status" => $api_http_code,
            "response" => json_decode($api_result, true)
        ];

        self::$check_loop = 0;

        curl_close($connect);

        return $response;
    }

    /**
     * @param        $uri
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function get($uri, $content_type = "application/json", $extra_params = [])
    {
        return self::exec("GET", $uri, null, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param        $data
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function post($uri, $data, $content_type = "application/json", $extra_params = [])
    {
        return self::exec("POST", $uri, $data, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param        $data
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function put($uri, $data, $content_type = "application/json", $extra_params = [])
    {
        return self::exec("PUT", $uri, $data, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function delete($uri, $content_type = "application/json", $extra_params = [])
    {
        return self::exec("DELETE", $uri, null, $content_type, $extra_params);
    }

    /**************
     *
     * Error implementation tracking
     *
     ***************/

    static $module_version = "";
    static $url_store = "";
    static $email_admin = "";
    static $country_initial = "";
    static $check_loop = 0;

    public static function getIntegratorID()
    {
        $objectManager = ObjectManager::getInstance();
        return $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue(ConfigData::PATH_ADVANCED_INTEGRATOR);
    }

    public static function setModuleVersion($module_version)
    {
        self::$module_version = $module_version;
    }

    public static function setUrlStore($url_store)
    {
        self::$url_store = $url_store;
    }

    public static function setEmailAdmin($email_admin)
    {
        self::$email_admin = $email_admin;
    }

    public static function setCountryInitial($country_initial)
    {
        self::$country_initial = $country_initial;
    }
}
