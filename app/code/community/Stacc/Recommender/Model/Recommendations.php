<?php

/**
 * Model Class for Handling STACC Recommendations data
 *
 * Class Stacc_Recommender_Model_Recommendations
 */
class Stacc_Recommender_Model_Recommendations extends Mage_Core_Model_Abstract
{
    /**
     * Retrieve data from STACC Recommender API
     *
     * @param $productId
     * @param $blockId
     * @return mixed
     */
    public function getRecommendations($productId, $blockId)
    {
        $recommendations = array();

        try {
            $productIds = Mage::helper('recommender/apiclient')->askRecommendations($productId, $blockId);

            $collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $productIds))
                ->load();

            foreach ($productIds as $productId) {
                if ($product = $collection->getItemById($productId)) {
                    $recommendations[] = $product;
                }
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/Recommendations.php->getRecommendations() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $recommendations;
    }

}
