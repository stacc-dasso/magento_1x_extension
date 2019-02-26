<?php


/**
 * Model Class for Product Syncing
 *
 * Class Stacc_Recommender_Model_Sync
 */
class Stacc_Recommender_Model_Sync extends Mage_Core_Model_Abstract
{
    /**
     * @var Stacc_Recommender_Helper_Logger
     */
    private $_logger;

    /**
     * @var Stacc_Recommender_Helper_Environment
     */
    private $_environment;

    /**
     * @var Stacc_Recommender_Helper_Apiclient
     */
    private $_apiclient;

    /**
     * @var int
     */
    private $_productsPerPage = 250;

    /**
     * @var int
     */
    private $_curPage = 1;

    /**
     * @var int
     */
    private $_storeId = null;

    /**
     * @var Mage_Core_Model_App
     */
    private $_app;

    /**
     * @var
     */
    private $_startTime;

    /**
     * @var
     */
    private $_endTime;

    /**
     * @var array
     */
    private $_webIdName = array();

    /**
     * @var array
     */
    private $_catIdName = array();

    /**
     * @var array
     */
    private $_storeCodes = array();

    /**
     * @var array
     */
    protected $response = array();


    /**
     * Constructor for creating more testable application
     *
     * Stacc_Recommender_Model_Sync constructor.
     *
     * @param Mage_Core_Model_App|null $app
     * @param Stacc_Recommender_Helper_Environment|null $environment
     * @param Stacc_Recommender_Helper_Logger|null $logger
     * @param Stacc_Recommender_Helper_Apiclient|null $apiclient
     */
    public function __construct(
        Mage_Core_Model_App $app = null,
        Stacc_Recommender_Helper_Environment $environment = null,
        Stacc_Recommender_Helper_Logger $logger = null,
        Stacc_Recommender_Helper_Apiclient $apiclient = null)
    {
        $this->_app = is_array($app) || is_null($app) ? Mage::app() : $app;
        $this->_environment = is_null($environment) ? Mage::helper('recommender/environment') : $environment;
        $this->_logger = is_null($logger) ? Mage::helper('recommender/logger') : $logger;
        $this->_apiclient = is_null($apiclient) ? Mage::helper('recommender/apiclient') : $apiclient;

    }

    /**
     * Sync products catalog, can use storeId and id (product) as parameters to specify sync
     *
     * @param null $storeId
     * @return $this
     */
    public function syncProducts($storeId = null)
    {

        $syncData = array("errors" => 0, "transmitted" => 0, "count" => 0, "pages" => 0);

        try {
            $this->initSync($storeId);

            do {
                // Stop sending if fails 10 times
                if ($syncData["errors"] == 10) {
                    break;
                }

                $syncData = $this->processAndSendPage($syncData);

            } while ($this->getCurrentPage() <= $syncData["pages"]);

            $this->setEndTime(microtime(true));

            $sync_time = $this->getEndTime() - $this->getStartTime();

            $this->_logger->logNotice("Synchronization finished, took $sync_time seconds, transmitted " . $syncData["transmitted"] . "/" . $syncData["count"] . " products, " . $syncData["errors"] . " errors");

        } catch (Exception $exception) {

            Mage::helper('recommender/logger')->logCritical("Model/Sync->syncProducts() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            $syncData["errors"]++;

        }

        return $this;
    }

    /**
     * @return Mage_Core_Model_App
     */
    public function getApp()
    {
        try {
            return $this->_app;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/Sync->getApp() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * @param $storeId
     */
    private function setStoreId($storeId)
    {
        if (!is_null($storeId)) {
            $this->_storeId = $storeId;
        }
    }

    /**
     * Returns Apiclient for sending data
     *
     * @return Stacc_Recommender_Helper_Apiclient
     */
    public function getApiclient()
    {
        return $this->_apiclient;
    }

    /**
     * Returns Logger that will log errors in the functions
     *
     * @return Stacc_Recommender_Helper_Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Returns Environment helper that will provide neccessary data
     *
     * @return Stacc_Recommender_Helper_Environment
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    /**
     * Sets time when sync was started
     *
     * @param $startTime
     */
    private function setStartTime($startTime)
    {
        if (!is_null($startTime)) {
            $this->_startTime = $startTime;
        }
    }

    /**
     * Returns time when sync was started
     *
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->_startTime;
    }

    /**
     * Sets time when sync was ended
     *
     * @param $endTime
     */
    private function setEndTime($endTime)
    {
        if (!is_null($endTime)) {
            $this->_endTime = $endTime;
        }
    }

    /**
     * Returns time when sync was ended
     *
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->_endTime;
    }

    /**
     * Set products amount for page
     *
     * @param $productsPerPage
     */
    public function setProductsPerPage($productsPerPage)
    {
        if ($productsPerPage) {
            $this->_productsPerPage = $productsPerPage;
        }
    }

    /**
     * Returns the amount of products per page
     *
     * @return int
     */
    public function getProductsPerPage()
    {
        return $this->_productsPerPage;
    }

    /**
     * Set Current page for modified syncing
     *
     * @param $curPage
     */
    private function setCurrentPage($curPage)
    {
        if ($curPage) {
            $this->_curPage = $curPage;
        }
    }

    /**
     * Returns currentPage
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->_curPage;
    }

    /**
     * Set the list of storeCodes
     *
     * @param $storeCodes
     */
    private function setStoreCodes($storeCodes)
    {
        if (is_array($storeCodes)) {
            $this->_storeCodes = $storeCodes;
        }
    }

    /**
     * Returns array of store codes
     *
     * @return array
     */
    public function getStoreCodes()
    {
        return $this->_storeCodes;
    }

    /**
     * Maps Web Id to the WebIdNames Array
     *
     * @param $webIdKey
     * @param $webName
     */
    private function setWebIdName($webIdKey, $webName)
    {
        if (!is_null($webIdKey) && !is_null($webName)) {
            $this->_webIdName[$webIdKey] = $webName;
        }

    }

    /**
     * Returns array of Web Names mapped to Id
     *
     * @return array
     */
    public function getWebIdName()
    {
        return $this->_webIdName;
    }

    /**
     * Maps Category id to catIdName array
     *
     * @param $catIdKey
     * @param $catName
     */
    private function setCatIdName($catIdKey, $catName)
    {
        if (!is_null($catIdKey) && !is_null($catName)) {
            $this->_catIdName[$catIdKey] = $catName;
        }
    }

    /**
     * Returns array of Category Names mapped to ids
     *
     * @return array
     */
    public function getCatIdName()
    {
        return $this->_catIdName;
    }

    /**
     * Setup syncing
     *
     * @param $storeId
     */
    private function initSync($storeId)
    {
        try {
            $this->setStartTime(microtime(true));

            $this->_logger->logNotice("Running products synchronization");

            $this->setStoreId($storeId);
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/Sync->initSync() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
    }

    /**
     * Process products and send them to the API
     *
     * @param $syncData
     * @return mixed
     */
    private function processAndSendPage($syncData)
    {
        try {
            $pageStartTime = microtime(true);

            $currentPage = $this->getCurrentPage();
            $this->_logger->logInfo("Sending page " . $currentPage . " started");

            $productCollection = $this->getProductsCollection();

            if ($syncData["count"] == 0) {
                $syncData["count"] = $productCollection->getSize();
                $syncData["pages"] = $productCollection->getLastPageNumber();
            }

            $dataBulk = $this->getModifiedProductsAsBulk($productCollection);

            $data_json = array(
                "bulk" => $dataBulk,
                "properties" => [
                    "current_page" => $currentPage,
                    "total_pages" => $syncData["pages"],
                    "amount_of_products" => $syncData["count"],
                    "extension_version" => $this->getEnvironment()->getVersion(),
                    "store" => $this->getStoreId()
                ]);
            $syncResponse = $this->getApiclient()->sendProducts($data_json);

            if ($syncResponse != "{}") {
                $this->_logger->logError("Can't send products", ['error' => strval($this->response["data"])]);
                $syncData["errors"]++;
            } else {
                $syncData["transmitted"] += count($dataBulk);
            }
            $this->response["data"] = ["total" => $syncData["count"], "transmitted" => $syncData["transmitted"]];
            $productCollection->clear();

            $pageTime = microtime(true) - $pageStartTime;
            $this->_logger->logInfo("Sending page " . $currentPage . " finished, took $pageTime seconds");

            $currentPage++;
            $this->setCurrentPage($currentPage);

            return $syncData;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/Sync->processAndSendPage() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }
    }

    /**
     * Create products collection of existing products for processing
     *
     * @return mixed
     */
    private function getProductsCollection()
    {

        try {
            // Build product collection for sync
            $productCollection = Mage::getModel("catalog/product")
                ->getCollection();
            if (!is_null($this->getStoreId())) {
                $productCollection
                    ->setStore($this->getStoreId())
                    ->addStoreFilter($this->getStoreId());
            }
            $productCollection
                ->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                ->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->addAttributeToSelect('*')
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
                    'left')
                ->setOrder('product_id')
                ->setPageSize($this->getProductsPerPage())
                ->setCurPage($this->getCurrentPage());


            return $productCollection;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/Sync->getProductsCollection() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }
    }

    public function getAmountOfPages($storeId = null)
    {
        $this->setStoreId($storeId);
        if ($this->getProductsCollection()) {
            return ["products_amount" => $this->getProductsCollection()->getSize(), "per_page" => $this->getProductsPerPage(), "amount_of_pages" => $this->getProductsCollection()->getLastPageNumber()];
        }
        return false;
    }

    public function syncAPage($page, $storeId = null)
    {
        $transmitted = 0;
        $errors = 0;
        $this->initSync($storeId);
        $this->setCurrentPage($page);
        $products = $this->getProductsCollection();
        $dataBulk = $this->getModifiedProductsAsBulk($products);
        $amount = count($dataBulk);
        $data_json = array(
            "bulk" => $dataBulk,
            "properties" => [
                "current_page" => $page,
                "total_pages" => 1,
                "amount_of_products" => $amount,
                "extension_version" => $this->getEnvironment()->getVersion(),
                "store" => $this->getStoreId()
            ]);
        $syncResponse = $this->getApiclient()->sendProducts($data_json);
        if ($syncResponse != "{}") {
            $errors += 1;
            $this->_logger->logError("Can't send products", ['error' => strval($syncResponse)]);
        } else {
            $transmitted = $amount;
        }
        $this->response["data"] = ["total" => $amount, "transmitted" => $transmitted];

        $this->setEndTime(microtime(true));

        $sync_time = $this->getEndTime() - $this->getStartTime();

        $this->_logger->logNotice("Synchronization finished, took $sync_time seconds, transmitted " . $transmitted . "/" . $amount . " products, " . $errors . " errors");

        return array("errors" => $errors, "transmitted" => $transmitted, "count" => $amount, "pages" => 1);
    }

    /**
     * Function to build bulk of products with additional info
     *
     * @param $productCollection
     * @return array
     */
    public function getModifiedProductsAsBulk($productCollection)
    {
        $dataBulk = array();

        try {
            $count = 0;
            foreach ($productCollection as $product) {

                $version = $this->getEnvironment()->getVersion();

                $productId = $product->getId();

                $webNames = $this->generateWebsitesList($product->getWebsiteIds());
                $categoryNames = $this->generateCategoryList($product->getCategoryIds());
                $storeData = $this->generateStores($product);

                // Build product structure to send
                $newProduct = array(
                    'item_id' => $productId,
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'currency' => Mage::helper('recommender/environment')->getCurrencyCode(),
                    'stores' => $this->getStoreCodes(),
                    'properties' => array_merge(
                        $product->getData(),
                        array(
                            'state' => $product->getStatus(),
                            'currency' => $product->getCurrencyCode(),
                            'final_price' => $product->getFinalPrice(),
                            'tax_amount' => $product->getTaxAmount(),
                            'tax_rate' => $product->getTaxRate(),
                            'prodcing' => $product->getPrice(),
                            'imgUrl' => $this->generateImgUrl($product),
                            'websites' => $webNames,
                            'category_ids' => $product->getCategoryIds(),
                            'category_names' => $categoryNames,
                            'version' => $version,
                            'upsell_products' => $product->getUpSellProductIds(),
                            'crosssell_products' => $product->getCrossSellProductIds(),
                            'related_products' => $product->getRelatedProductIds(),
                            'parent_id' => $this->generateParentIDs($productId),
                            'stores' => $storeData,
                            'isSalable' => $product->isSalable()
                        )
                    )
                );

                $dataBulk[] = $newProduct;
            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->getModifiedProductsAsBulk() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $dataBulk;
    }

    /**
     * Function to generate Array of parent products
     *
     * @param $productId
     * @return array
     */
    private function generateParentIDs($productId)
    {
        $parent_ids = array();

        try {

            // Get parent ids
            $grouped = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($productId);
            $configurable = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
            $bundled = Mage::getModel('bundle/product_type')->getParentIdsByChild($productId);

            $parent_ids = array_merge($grouped, $configurable, $bundled);
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->generateParentIDs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $parent_ids;
    }

    /**
     * Function that generates Array with the info of all the store related to the product
     *
     * @param $product
     * @return array
     */
    private function generateStores($product)
    {
        $storeData = array();
        try {
            $storeIds = $product->getStoreIds();
            $storeCodes = array();
            // Check if product has storeIds()
            if (!empty($storeIds)) {
                $storeInfo = array();
                foreach ($storeIds as $storeId) {
                    $store = $this->getApp()->getStore($storeId);
                    $storeCodes[] = $store->getCode();
                    $storeInfo["website"] = [$store->getWebsite()->getId() => $store->getWebsite()->getData()];
                    $storeInfo["name"] = $store->getName();
                    $storeInfo["id"] = $storeId;
                    $storeInfo["storeInUrl"] = $store->getStoreInUrl();
                    $storeInfo["group"] = [$store->getGroup()->getId() => ["name" => $store->getGroup()->getName(), "id" => $store->getGroup()->getId(), "data" => $store->getGroup()->getData()]];
                    $storeInfo['store_data'] = $store->getData();
                    $storeData[$storeId] = $storeInfo;
                }
                $this->setStoreCodes($storeCodes);
            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->generateStores() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $storeData;
    }

    /**
     * Function to generate simple imageUrl for the product
     *
     * @param $product
     * @return string
     */
    private function generateImgUrl($product)
    {
        $imgUrl = "";

        try {
            $attribute = $product->getResource()->getAttribute('media_gallery');

            $backend = $attribute->getBackend();

            $backend->afterLoad($product);

            $imgUrl = "";
            foreach ($product->getMediaGalleryImages() as $image) {
                $imgUrl = $image->getUrl();
                break;
            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->generateImgUrl() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
        return $imgUrl;
    }

    /**
     * Function to generate Array of product categories
     *
     * @param $categoryIds
     * @return array
     */
    private function generateCategoryList($categoryIds)
    {
        $categoryNames = array();

        try {
            // Check if product has categoryIds()
            if (!empty($categoryIds)) {
                foreach ($categoryIds as $catId) {
                    // check if key _$catId doesn't exist in $catIdName Array
                    if (!array_key_exists("_$catId", $this->getCatIdName())) {
                        $this->populateCatIdNameDict($categoryIds);
                        // populate dict for $catIdName
                    }
                    $catIdName = $this->getCatIdName();
                    $categoryNames[] = $catIdName["_$catId"];
                }

            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->generateCategoryList() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
        return $categoryNames;
    }


    /**
     * Populate Category Dict
     *
     * @param $categoryIds - list of category ids
     * @return mixed - updated dictionary id-name
     */
    private function populateCatIdNameDict($categoryIds)
    {
        try {
            $categoryCollection = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect(array('name', 'is_active'))
                ->addAttributeToFilter('entity_id', $categoryIds);

            foreach ($categoryCollection as $cat) {
                $innerCatId = $cat->getId();
                if (!array_key_exists("_$innerCatId", $this->getCatIdName())) {
                    $catName = $cat->getName();
                    $isActive = $cat->getIsActive();
                    if ($isActive) {
                        $this->setCatIdName("_$innerCatId", $catName);
                    } else {
                        $this->setCatIdName("_$innerCatId", "inactive_$catName");
                        $catIdName["_$innerCatId"] = "inactive_$catName";
                    }
                }
            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->populateIdNameDict() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
        return $this->getCatIdName();
    }


    /**
     * Function to generate Array of product Websites
     *
     * @param $webIds
     * @return array
     */
    private function generateWebsitesList($webIds)
    {
        $webNames = array();

        try {
            // Check if product has webIds
            if (!empty($webIds)) {
                foreach ($webIds as $webId) {

                    // check if key _$webId doesn't exist in $webIdName Array
                    if (!array_key_exists("_$webId", $this->getWebIdName())) {
                        // populate dict for $webIdName
                        $this->populateWebsiteIdNameDict($webIds);
                    }
                    $webIdName = $this->getWebIdName();
                    $webNames[] = $webIdName["_$webId"];
                }
            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->generateCategoryList() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $webNames;
    }

    /**
     * Populate Website Name Dict
     *
     * @param $websiteIds
     * @return mixed
     */
    private function populateWebsiteIdNameDict($websiteIds)
    {
        try {
            foreach ($websiteIds as $websiteId) {
                if (!array_key_exists("_$websiteId", $this->getWebIdName())) {
                    $website = $this->getApp()->getWebsite($websiteId);
                    if (isset($website)) {
                        $websiteName = $website->getName();
                        $this->setWebIdName("_$websiteId", $websiteName);
                    }
                }

            }
        } catch (Exception $exception) {
            $this->_logger->logCritical("Model/Sync->populateWebsiteIdNameDict() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
        return $this->getWebIdName();
    }

    /**
     * Return the syncing response
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

}