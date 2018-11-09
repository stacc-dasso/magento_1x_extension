<?php

/**
 * Helper Class for handling API and Store data for Extension
 *
 * Class Stacc_Recommender_Helper_Environment
 */
class Stacc_Recommender_Helper_Environment extends Mage_Core_Helper_Abstract
{
    /**
     * @var Mage_Core_Model_App|null
     */
    protected $_app;

    /**
     * @var Mage_Core_Model_Store
     */
    protected $_store;

    /**
     * @var Mage_Log_Model_Visitor
     */
    protected $_visitor;

    /**
     * USER_AGENT_CODE
     */
    const CLI_USER_AGENT = 'cli_executed_stacc_recommender_extension';

    /**
     * Path to STACC API
     *
     * @var array
     */
    private $_baseApiUrl = "https://recommender.stacc.cloud/";

    private $_apiPaths = array(
        "main_api" => "api/v2",
        "m1x_api" => "api/magento/1x"
    );

    /**
     * Array of endpoints for extension
     *
     * @var array
     */
    private $_endpoints = [
        'add_to_cart' => '/send_add_to_cart',
        'catalog_sync' => '/catalog_sync',
        'get_recs' => '/get_recs',
        'purchase' => '/send_purchase',
        'view' => '/send_view',
        'logs' => '/send_logs',
        'search' => '/send_search',
        'check' => '/check_credentials'
    ];

    /**
     * Timeout for extension events
     *
     * @var int
     */
    private $_timeout = 3000;

    /**
     * Stacc_Recommender_Helper_Environment constructor.
     *
     * @param Mage_Core_Model_App|null $app
     * @param Mage_Core_Model_Store|null $store
     * @param Mage_Log_Model_Visitor|null $visitor
     */

    public function __construct(Mage_Core_Model_App $app = null, Mage_Core_Model_Store $store = null, Mage_Log_Model_Visitor $visitor = null)
    {
        try {
            $this->_app = is_null($app) ? Mage::app() : $app;
            $this->_store = is_null($store) ? Mage::app()->getStore() : $store;
            $this->_visitor = is_null($visitor) ? Mage::getSingleton('log/visitor') : $visitor;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->__construct() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

    }

    /**
     * @return Mage_Core_Model_App
     */
    public function getApp()
    {
        return $this->_app;
    }

    /**
     * @return Mage_Core_Model_Abstract|Mage_Log_Model_Visitor|null
     */
    public function getVisitor()
    {
        return $this->_visitor;
    }

    /**
     * Returns the corresponding endpoint for the value
     *
     * @param $value
     * @return mixed
     */
    public function getEndpoint($value)
    {
        return $this->_endpoints[$value];
    }

    /**
     * Returns customer info
     *
     * @return array
     */
    public function identifyCustomer()
    {
        try {
            $log_visitor = $this->getVisitor();

            if ($log_visitor) {
                $session_id = $log_visitor->getData('session_id');
                $visitor_id = $log_visitor->getData('visitor_id');
                $customer_id = $log_visitor->getData('customer_id');
            } else {
                $session_id = "";
                $visitor_id = "";
                $customer_id = "";
            };

            $customer_id = $customer_id ? $customer_id : "";
            $visitor_id = $visitor_id ? $visitor_id : "";
            $session_id = $session_id ? $session_id : "";
            $customer_info = array(
                'session_id' => $session_id,
                'visitor_id' => $visitor_id,
                'customer_id' => $customer_id,
            );
            return $customer_info;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->identifyCustomer() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }
    }

    /**
     * Returns Currency Code
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        try {
            return Mage::app()->getStore()->getCurrentCurrencyCode();
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getCurrencyCode() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns STACC API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        try {
            return $this->_baseApiUrl . $this->_apiPaths["main_api"];
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->getApiUrl() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns STACC M1 URL
     *
     * @return string
     */
    public function getM1Url()
    {
        try {
            return $this->_baseApiUrl . $this->_apiPaths["m1x_api"];
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->getM1Url() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Return endpoint URL for getting Recommendations
     *
     * @return string
     */
    public function getRecommendationsURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('get_recs');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getRecommendationsURL() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Return endpoint URL for the View event
     *
     * @return string
     */
    public function getViewEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('view');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getViewEventURL() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Return endpoint URL for the Search event
     *
     * @return string
     */
    public function getSearchEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('search');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getSearchEventURL() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Return endpoint URL for the Add to Cart event
     *
     * @return string
     */
    public function getAddToCartEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('add_to_cart');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getAddToCartEventURL() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Return endpoint URL for the Purchase event
     *
     * @return string
     */
    public function getPurchaseEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('purchase');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getPurchaseEventURL() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Return endpoint URL for the Catalog Syncing event
     *
     * @return string
     */
    public function getCatalogSyncURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('catalog_sync');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getCatalogSyncURL() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns endpoint URL for logs
     * @return string
     */
    public function getLogsURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('logs');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getLogsURL() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns endpoint URL for checking credentials
     *
     * @return string
     */
    public function getCheckCredentialsURL()
    {
        try {
            return $this->getM1Url() . $this->getEndpoint('check');
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getCheckCredentialsURL(() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns STACC Client ID that is set in admin panel
     *
     * @return mixed
     */
    public function getClientId()
    {
        try {
            return Mage::getStoreConfig('stacc_recommender/stacc_recommender/stacc_shop_id', $this->getStore());
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getClientId() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns STACC API Key that is set in admin panel
     *
     * @return mixed
     */
    public function getApiKey()
    {
        try {
            return Mage::getStoreConfig('stacc_recommender/stacc_recommender/stacc_api_key', $this->getStore());
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getApiKey() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns extension version from config.xml
     *
     * @return string
     */
    public function getVersion()
    {
        try {
            return (string)Mage::getConfig()->getNode()->modules->Stacc_Recommender->version;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getVersion() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Return store language code
     *
     * @return string
     */
    public function getLang()
    {
        try {
            return $this->getApp()->getStore()->getCode();
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getLang() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns website name
     *
     * @return string
     */
    public function getWebsite()
    {
        try {

            return $this->getApp()->getWebsite()->getName();
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getWebsite() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns Magentos store object
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        try {
            return $this->_store;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getStore() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Returns Language code
     *
     * @return string
     */
    public function getLocaleCode()
    {
        try {
            return $this->getApp()->getLocale()->getLocaleCode();
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getLocaleCode() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Return user agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        try {
            return $this->getApp()->getRequest()->getHeader('User-Agent') ?: self::CLI_USER_AGENT;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Helper/Environment->getUserAgent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }
}
