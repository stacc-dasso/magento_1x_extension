<?php

/**
 * Model Class for Handling Stacc Recommender Config Save Event Data
 *
 * Class Stacc_Recommender_Model_Observer
 */
class Stacc_Recommender_Model_Observer_Config
{
    /**
     * Observe method to watch Stacc Recommender Config Save Event
     *
     * @param $observer
     */
    public function observe($observer)
    {
        if (get_class($observer) == "Mage_Core_Model_Observer" || get_class($observer) == "Varien_Event_Observer") {
            try {
                Mage::helper('recommender/logger')->logInfo("Saved Shop ID and API Key");
            } catch (Exception $exception) {
                Mage::helper('recommender/logger')->logCritical("Model/Observer/Config->observe() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            }
        } else {
            Mage::helper('recommender/logger')->logError("Wrong element type received in config event", array(get_class($observer)));
        }
    }
}
