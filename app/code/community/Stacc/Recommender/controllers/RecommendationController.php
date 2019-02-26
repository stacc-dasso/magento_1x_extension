<?php

/**
 * Controller for Stacc Recommender Block
 *
 * Class Stacc_Recommender_RecommendationController
 */
class Stacc_Recommender_RecommendationController extends Mage_Core_Controller_Front_Action
{
    /**
     * For store id verification to run on entered id
     */
    const TYPE_STORE = "store";

    /**
     * For product id verification to run on enterd id
     */
    const TYPE_PRODUCT = "product";

    /**
     * Creates Recommendations Block to display in frontend
     */
    public function getAction()
    {
        try {
            $productId = $this->validateProductId($this->getRequest()->getParam('productId'));
            $blockId = $this->validateBlockId($this->getRequest()->getParam('blockId'));
            $template = $this->validateTemplate($this->getRequest()->getParam('template'));

            $block = Mage::app()->getLayout()->createBlock('recommender/recommendation')->setTimestamp(microtime(true))->setProductId($productId)->setBlockId($blockId)->setTemplate($template);

            $this->getResponse()->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true)->setBody($block->toHtml());
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->recAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Function to keep the extensions compatibility with older version in case of caching.
     * To be removed in future
     */
    public function recAction()
    {
        $this->getAction();
    }

    /**
     * Validates product ID. Product ID can be any integer.
     * If product ID doesn't match with an existing product, no recommendations will be shown.
     *
     * @param $productId
     * @return int
     * @throws Exception
     */
    private function validateProductId($productId)
    {
        try {
            return $this->verifyId($productId, $this::TYPE_PRODUCT);
        } catch (Exception $exception) {
            throw new Exception("\"" . $productId . "\" is not a valid product ID.");
        }
    }

    /**
     * Validates block ID. Block ID can be any string.
     * If block ID doesn't match a known block ID, no recommendations will be shown.
     *
     * @param $blockId
     * @return string
     */
    private function validateBlockId($blockId)
    {
        return (string)$blockId;
    }

    /**
     * Validates template path string. Template path must follow the pattern "recommender/recommendation_block_*.phtml".
     * If template path has invalid structure, the referred template will not be shown.
     *
     * @param $template
     * @return string
     * @throws Exception
     */
    private function validateTemplate($template)
    {
        $template = (string)$template;
        preg_match('/(recommender\/recommendation_block_[a-z0-9]+\.phtml)|(catalog\/product\/list\/upsell.phtml)/i', $template, $matches);
        if (sizeof($matches) > 0) {
            return $template;
        } else {
            throw new Exception("\"" . $template . "\" is not a valid template.");
        }
    }

    /**
     * Method to check connection with the extension
     */
    public function checkAction()
    {
        try {
            $urlHash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            if ($this->auth_api($urlHash)) {
                $this->getResponse()->setBody($timestamp);
            } else {
                Mage::helper('recommender/logger')->logError("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->checkAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            $this->getResponse()->setBody("");
            return null;
        }
    }

    /**
     *  Method for triggering function that will sync products
     */
    public function syncAction()
    {
        try {
            set_time_limit(300);
            $urlHash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            $storeId = $this->verifyId($this->getRequest()->getParam('s'), $this::TYPE_STORE);

            if ($this->auth_api($urlHash)) {

                $sync = Mage::getModel('recommender/sync', Mage::app())->syncProducts($storeId);
                $this->getResponse()->setBody($timestamp . " " . $sync->getResponse()["data"]["transmitted"] . "/" . $sync->getResponse()["data"]["total"]);
            } else {
                Mage::helper('recommender/logger')->logError("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->syncAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    public function sheetAction()
    {
        try {
            set_time_limit(300);
            $urlHash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            $storeId = $this->verifyId($this->getRequest()->getParam('s'), $this::TYPE_STORE);
            $page = $this->getRequest()->getParam('pg');

            if ($this->auth_api($urlHash)) {

                $syncPage = Mage::getModel('recommender/sync', Mage::app())->syncAPage($page, $storeId);
                $this->getResponse()->setBody(json_encode(array_merge($syncPage,['timestamp'=>$timestamp])));
            } else {
                Mage::helper('recommender/logger')->logError("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->sheetAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    public function pagesAction()
    {
        try {
            $urlHash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            $storeId = $this->verifyId($this->getRequest()->getParam('s'), $this::TYPE_STORE);
            if ($this->auth_api($urlHash)) {

                $pagesArr = Mage::getModel('recommender/sync', Mage::app())->getAmountOfPages($storeId);
                $this->getResponse()->setBody(json_encode(array_merge($pagesArr, ['timestamp' => $timestamp])));
            } else {
                Mage::helper('recommender/logger')->logError("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->pagesAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Method for triggering function that will send logs
     */
    public function logsAction()
    {
        try {
            $urlHash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            if ($this->auth_api($urlHash)) {
                $logs = Mage::getModel('recommender/logdispatcher')->sendLogs();
                $this->getResponse()->setBody($timestamp . " " . (int)$logs->getResponse());
            } else {
                Mage::helper('recommender/logger')->logError("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->logsAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Method for getting product data
     */
    public function productAction()
    {
        try {
            $url_hash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');
            $productId = $this->verifyId($this->getRequest()->getParam('p'), $this::TYPE_PRODUCT);

            if ($this->auth_api($url_hash)) {

                $response = [
                    "product" => array(),
                    "timestamp" => $timestamp
                ];

                if ($productId) {
                    $collection = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('entity_id', array('in' => $productId))
                        ->joinField('qty',
                            'cataloginventory/stock_item',
                            'qty',
                            'product_id=entity_id',
                            null,
                            'left')
                        ->joinField('is_in_stock',
                            'cataloginventory/stock_item',
                            'is_in_stock',
                            'product_id=entity_id',
                            null,
                            'left');

                    $bulk = Mage::getModel("recommender/sync")->getModifiedProductsAsBulk($collection);
                } else {
                    $bulk = [];
                }

                $response["product"] = $bulk;

                $this->getResponse()->setBody(json_encode($response));
            } else {
                Mage::helper('recommender/logger')->logError("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->productAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Method for getting stores data
     */
    public function storesAction()
    {
        try {
            $url_hash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            if ($this->auth_api($url_hash)) {
                $stores = Mage::app()->getStores();
                $storeData = array("timestamp" => $timestamp);
                foreach (array_keys($stores) as $storeId) {
                    $store = Mage::app()->getStore($storeId);
                    $storeCodes[] = $store->getCode();
                    $storeInfo["id"] = $storeId;
                    $storeInfo["name"] = $store->getName();
                    $storeInfo["storeInUrl"] = $store->getStoreInUrl();
                    $storeInfo['store_data'] = $store->getData();
                    $storeInfo["website"] = [$store->getWebsite()->getId() => $store->getWebsite()->getData()];
                    $storeInfo["group"] = [$store->getGroup()->getId() => ["name" => $store->getGroup()->getName(), "id" => $store->getGroup()->getId(), "data" => $store->getGroup()->getData()]];
                    $storeData[$storeId] = $storeInfo;
                }
                $this->getResponse()->setBody(json_encode($storeData));
            } else {
                Mage::helper('recommender/logger')->logError("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->storesAction() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Verify if the data entered is numerical id if not returns null
     *
     * @param $id
     * @param $type
     * @return null
     */
    private function verifyId($id, $type = "")
    {
        try {
            if (isset($id)) {
                if ($type == $this::TYPE_STORE) {
                    $store = Mage::getModel('core/store')->load($id);
                    if ((int)$id && $store->getId()) {
                        return $id;
                    }
                } else if ($type == $this::TYPE_PRODUCT) {
                    $product = Mage::getResourceSingleton('catalog/product')->getProductsSku(array($id));
                    if ((int)$id && $product) {
                        return $id;
                    }
                } else {
                    if ((int)$id) {
                        return $id;
                    }
                    return null;
                }
            }
            return null;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->auth_api() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Method that checks the hash of url
     *
     * @param $hash
     * @return bool
     */
    private function auth_api($hash)
    {
        try {
            $environment = Mage::helper("recommender/environment");
            $mainHash = hash("sha256", $environment->getClientId() . $environment->getApiKey());

            return $mainHash == $hash;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("controllers/RecommendationController->auth_api() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }
}