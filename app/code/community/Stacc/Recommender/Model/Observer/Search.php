<?php

/**
 * Model Class for Handling Magento Search Event Data
 *
 * Class Stacc_Recommender_Model_Observer_Search
 */
class Stacc_Recommender_Model_Observer_Search
{
    /**
     * Observe method to watch Magento Search Event
     *
     * @param $observer
     */
    public function observe($observer)
    {
        if (get_class($observer) == "Mage_Core_Model_Observer" || get_class($observer) == "Varien_Event_Observer") {
            try {
                $response = false;
                if (method_exists($observer, "getEvent")) {

                    $query = Mage::helper('catalogsearch')->getQueryText();
                    $response = Mage::helper('recommender/apiclient')->sendSearchEvent($query);
                }

                if (!$response) {
                    Mage::helper('recommender/logger')->logError("Failed to sync search event", array($response));

                }
            } catch (Exception $exception) {
                Mage::helper('recommender/logger')->logCritical("Model/Observer/Search->observe() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            }
        } else {
            Mage::helper('recommender/logger')->logError("Wrong element type received in search event", array(get_class($observer)));
        }
    }
}