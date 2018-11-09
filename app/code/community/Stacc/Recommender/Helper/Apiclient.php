<?php

/**
 * Helper class for communicating with the STACC API
 *
 * Class Stacc_Recommender_Helper_Apiclient
 */
class Stacc_Recommender_Helper_Apiclient extends Mage_Core_Helper_Abstract
{

    /**
     * Environment singleton
     *
     * @var Stacc_Recommender_Helper_Environment
     */
    private $_environment;

    /**
     * HttpRequest singleton
     *
     * @var Stacc_Recommender_Helper_Httprequest
     */
    private $_httpRequest;

    /**
     * Logger singleton
     *
     * @var Stacc_Recommender_Helper_Logger
     */
    private $_logger;

    /**
     * Stacc_Recommender_Helper_Logger constructor
     *
     * @param $environment
     * @param $httpRequest
     * @param $logger
     */
    public function __construct(Stacc_Recommender_Helper_Environment $environment = null, Stacc_Recommender_Helper_Httprequest $httpRequest = null, Stacc_Recommender_Helper_Logger $logger = null)
    {
        $this->_environment = is_null($environment) ? Mage::helper('recommender/environment') : $environment;
        $this->_httpRequest = is_null($httpRequest) ? Mage::helper('recommender/httprequest') : $httpRequest;
        $this->_logger = is_null($logger) ? Mage::helper('recommender/logger') : $logger;
    }

    /**
     * Returns Stacc Recommender Helper called environment that provides neccessary data, like urls
     *
     * @return Stacc_Recommender_Helper_Environment
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    /**
     * Returns Stacc Recommender Helper Httprequest that provides method for sending data to the API
     *
     * @return Stacc_Recommender_Helper_Httprequest
     */
    public function getHttpRequest()
    {
        return $this->_httpRequest;
    }

    /**
     * Returns Stacc Recommender Helper Logger to provide logger for functions
     *
     * @return Stacc_Recommender_Helper_Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Retrieve recommendations from STACC Recommender API
     *
     * @param $productId
     * @param $blockId
     * @return array
     */
    public function askRecommendations($productId, $blockId)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();
            $customerInfo = $environment->identifyCustomer();
            $website = $environment->getWebsite();
            $storeCode = $environment->getStore()->getCode();

            //  Get recommendations from the API
            $data = [
                'stacc_id' => (string)$customerInfo['visitor_id'],
                'item_id' => (string)$productId,
                'block_id' => (string)$blockId,
                'website' => $website,
                'store' => $storeCode,
                'properties' => $this->getProperties()
            ];

            $url = $environment->getRecommendationsURL();
            $output = $httpRequest->postData($data, $url, $environment->getTimeout());

            $json_output = json_decode($output);

            if (isset($json_output->items)) {
                return $json_output->items;
            } else {
                return array();
            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->askRecommendations() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }

    }

    /**
     * Send Search Event To STACC Recommender API
     *
     * @param $query
     * @param array $filters
     * @return mixed
     */
    public function sendSearchEvent($query, $filters = array())
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();
            $customerInfo = $environment->identifyCustomer();
            $website = $environment->getWebsite();
            $storeCode = $environment->getStore()->getCode();

            $data = [
                "stacc_id" => (string)$customerInfo['visitor_id'],
                "query" => (string)$query,
                "filters" => $filters,
                "website" => $website,
                'store' => $storeCode,
                "properties" => $this->getProperties()
            ];

            $url = $environment->getSearchEventURL();
            $output = $httpRequest->postData($data, $url, $environment->getTimeout());

            return $output;
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->sendSearchEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return false;
        }

    }

    /**
     * Send View Event To STACC Recommender API
     *
     * @param $productId
     * @return mixed
     */
    public function sendViewEvent($productId)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();
            $customerInfo = $environment->identifyCustomer();
            $website = $environment->getWebsite();
            $storeCode = $environment->getStore()->getCode();
            $data = [
                'stacc_id' => (string)$customerInfo['visitor_id'],
                'item_id' => (string)$productId,
                'website' => $website,
                'store' => $storeCode,
                'properties' => $this->getProperties()
            ];

            $url = $environment->getViewEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->sendViewEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return false;
        }
    }

    /**
     * Send Add To Cart Event To STACC Recommender API
     *
     * @param $productId
     * @return mixed
     */
    public function sendAddToCartEvent($productId)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();
            $customerInfo = $environment->identifyCustomer();
            $website = $environment->getWebsite();
            $storeCode = $environment->getStore()->getCode();
            $data = [
                'stacc_id' => (string)$customerInfo['visitor_id'],
                'item_id' => (string)$productId,
                'website' => $website,
                'store' => $storeCode,
                'properties' => $this->getProperties()
            ];

            $url = $environment->getAddToCartEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->sendAddToCartEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return false;
        }
    }

    /**
     * Send Purchase Event To STACC Recommender API
     *
     * @param $itemsArray
     * @return mixed
     */
    public function sendPurchaseEvent($itemsArray)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();
            $customerInfo = $environment->identifyCustomer();
            $website = $environment->getWebsite();
            $storeCode = $environment->getStore()->getCode();

            $data = [
                'stacc_id' => (string)$customerInfo['visitor_id'],
                'item_list' => $itemsArray,
                'currency' => $environment->getCurrencyCode(),
                'website' => $website,
                'store' => $storeCode,
                'properties' => $this->getProperties(),

            ];

            $url = $environment->getPurchaseEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->sendPurchaseEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{}";
        }
    }

    /**
     * Sync products to STACC API
     *
     * @param $bulk
     * @return mixed
     */
    public function sendProducts($bulk)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();

            $url = $environment->getCatalogSyncURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($bulk, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->sendProducts() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{data: false, error: true}";
        }
    }

    /**
     * Send logs to STACC API
     *
     * @param $logs
     * @return string
     */
    public function sendLogs($logs)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();
            $data = [
                'logs' => $logs,
                'properties' => [
                    'user_agent' => $environment->getUserAgent(),
                ]
            ];
            $url = $environment->getLogsURL();
            return $httpRequest->postData($data, $url);
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->sendLogs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return false;
        }
    }

    /**
     * Send request to STACC API
     *
     * @param $data
     * @return string
     */
    public function sendCheckCredentials($data)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();

            $url = $environment->getCheckCredentialsURL();

            $timeout = $environment->getTimeout();
            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->sendCheckCredentials() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return false;
        }
    }

    /**
     * Returns properties about website and extension for the events
     *
     * @return array
     */
    private function getProperties()
    {
        try {
            $environment = $this->getEnvironment();
            $customerInfo = $environment->identifyCustomer();
            $website = $environment->getWebsite();
            $userAgent = $environment->getUserAgent();
            $store = $environment->getStore()->getName();
            $lang = $environment->getLang();
            $currencyCode = $environment->getCurrencyCode();
            $extVersion = $environment->getVersion();
            $localeCode = $environment->getLocaleCode();
            return array_merge(
                $customerInfo,
                [
                    'website' => $website,
                    'user_agent' => $userAgent,
                    'store' => $store,
                    'lang' => $lang,
                    'lang_code' => $localeCode,
                    'currency' => $currencyCode,
                    'extension_version' => $extVersion,
                    'cookie_allowed' => Mage::getSingleton('core/cookie')->get("user_allowed_save_cookie")
                ]
            );
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Apiclient->getProperties() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }
    }

}
