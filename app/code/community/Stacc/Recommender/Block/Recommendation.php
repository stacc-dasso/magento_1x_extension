<?php

/**
 * Class for Recommendations, extending Upsell for maintaining some wanted functionality from Upsell
 *
 * Class Stacc_Recommender_Block_Recommendation
 */
class Stacc_Recommender_Block_Recommendation extends Mage_Catalog_Block_Product_List_Upsell
{

    /**
     * Variable for storing Product Id
     *
     * @var
     */
    private $_productId;

    /**
     * Variable for storing timestamp of block created
     *
     * @var
     */
    private $_timestamp;

    /**
     * Variable for storing Block Id
     *
     * @var
     */
    private $_blockId;
    /**
     * Variable for storing recommendedProducts
     *
     * @var
     */
    private $_recommendations = array();

    /**
     * Request recommended products from Recommendations Model
     *
     * @return $this|Mage_Catalog_Block_Product_List_Upsell
     */
    protected function _prepareData()
    {
        try {
            $this->_recommendations = Mage::getModel('recommender/recommendations')->getRecommendations($this->getProductId(), $this->getBlockId());

            $collection = new Varien_Data_Collection();

            foreach ($this->_recommendations as $row) {
                if (!$collection->getItemById($row->getId())) {
                    $collection->addItem($row);
                }
            }

            $this->_itemCollection = $collection;
        } catch (Exception $exception) {

            Mage::helper('recommender/logger')->logCritical("Block/Recommendations.php->_prepareData() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $this;
    }

    /**
     * Set Product Id for the block
     *
     * @param $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->_productId = $productId;
        return $this;
    }

    /**
     * Returns Product Id
     *
     * @return mixed
     */
    public function getProductId()
    {
        return $this->_productId;
    }

    /**
     * Set Block Id, which is the Element Id from Container
     *
     * @param $blockId
     * @return $this
     */
    public function setBlockId($blockId)
    {
        $this->_blockId = $blockId;
        return $this;
    }

    /**
     * Returns Block Id
     *
     * @return mixed
     */
    public function getBlockId()
    {
        return $this->_blockId;
    }

    /**
     * Returns timestamp of block
     *
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->_timestamp;
    }

    /**
     * Sets timestamp for block
     *
     * @param $timestamp
     * @return $this
     */
    public function setTimestamp($timestamp)
    {
        $this->_timestamp = $timestamp;
        return $this;
    }

    /**
     * Return recommendations
     *
     * @return mixed
     */
    public function getRecommendations()
    {
        return $this->_recommendations;
    }
}
