<?php

/**
 * Model Class for Handling Magento View Event Data
 * Class Stacc_Recommender_Model_Observer_View
 */
class Stacc_Recommender_Model_Observer_View
{
    /**
     * Observe method to watch Magento View Event
     *
     * @param $observer
     */
    public function observe($observer)
    {
        if (get_class($observer) == "Mage_Core_Model_Observer" || get_class($observer) == "Varien_Event_Observer") {
            try {
                $response = false;
                if (method_exists($observer, "getEvent")) {
                    if ($observer->getEvent()->getProduct()) {
                        $productId = $observer->getEvent()->getProduct()->getId();
                    } else {
                        $productId = Mage::registry('current_product')->getId();
                    }

                    $response = Mage::helper('recommender/apiclient')->sendViewEvent($productId);
                }


                if (!$response) {
                    Mage::helper('recommender/logger')->logError("Failed to sync view event", array($response));
                }
            } catch (Exception $exception) {
                Mage::helper('recommender/logger')->logCritical("Model/Observer/View->observe() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            }
        } else {
            Mage::helper('recommender/logger')->logError("Wrong element type received in view event", array(get_class($observer)));
        }
    }
}
