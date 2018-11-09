<?php

/**
 * Helper Class For handling POST HTTP request
 *
 * Class Stacc_Recommender_Helper_Httprequest
 */
class Stacc_Recommender_Helper_Httprequest extends Mage_Core_Helper_Abstract
{

    /**
     * Variable for storing logger instance
     *
     * @var Stacc_Recommender_Helper_Logger
     */
    private $_logger;

    /**
     * Variable for storing environment instance
     *
     * @var Stacc_Recommender_Helper_Environment
     */
    private $_environment;


    /**
     * Stacc_Recommender_Helper_Httprequest constructor.
     *
     * @param Stacc_Recommender_Helper_Environment|null $environment
     * @param Stacc_Recommender_Helper_Logger|null $logger
     */
    public function __construct(Stacc_Recommender_Helper_Environment $environment = null, Stacc_Recommender_Helper_Logger $logger = null)
    {
        $this->_environment = is_null($environment) ? Mage::helper('recommender/environment') : $environment;
        $this->_logger = is_null($logger) ? Mage::helper('recommender/logger') : $logger;
    }

    /**
     * Send POST Request to API
     *
     * @param $data
     * @param $url
     * @param int $timeout
     * @return mixed
     */
    public function postData($data, $url, $timeout = 5000)
    {
        try {
            $environment = $this->_environment;
            $client_id = $environment->getClientId();
            $api_key = $environment->getApiKey();

            // Init request
            $options = array(
                CURLOPT_USERPWD => $client_id . ":" . $api_key,
                CURLOPT_FRESH_CONNECT => 1,
                CURLOPT_TIMEOUT_MS => $timeout
            );

            $ch = new Varien_Http_Adapter_Curl();
            $ch->setOptions($options);

            // Send request
            $ch->write(Zend_Http_Client::POST, $url, '1.1', ['Content-Type: application/json'], json_encode($data));

            $output = $ch->read();

            // Get the response code and output
            $httpCode = Zend_Http_Response::extractCode($output);
            $output = Zend_Http_Response::extractBody($output);

            if ($httpCode != 200) {
                $this->_logger->logError("Received error from HTTP request", ['error' => strval($httpCode)]);
            }

            $errPost = $ch->getError();

            if ($errPost) {
                $this->_logger->logError("HTTP Request ended in error", ['error' => strval($errPost)]);
            }

            // Close request
            $ch->close();

            return $output;
        } catch (Exception $exception) {
            $this->_logger->logCritical("Helper/Httprequest->postData() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{}";
        }

    }

    /**
     * @return Stacc_Recommender_Helper_Environment
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    /**
     * @return Stacc_Recommender_Helper_Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

}