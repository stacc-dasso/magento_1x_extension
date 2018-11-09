<?php

/**
 * Model Class for Handling Magento Add To Cart Event Data
 *
 * Class Stacc_Recommender_Model_Observer_AddToCart
 */
class Stacc_Recommender_Model_Observer_Cart
{

    /**
     * Observe method to watch Magento Add to Cart Event
     *
     * @param $observer
     */

    public function observe($observer)
    {
        if (get_class($observer) == "Mage_Core_Model_Observer" || get_class($observer) == "Varien_Event_Observer") {

            try {
                $product_id = "";
                $response = false;

                if (method_exists($observer, "getEvent")) {
                    $product_id = $observer->getEvent()->getProduct()->getId();
                    $response = Mage::helper('recommender/apiclient')->sendAddToCartEvent($product_id);
                }

                if (!$response) {
                    Mage::helper('recommender/logger')->logError("Failed to sync add to cart event", array($response));
                }
            } catch (Exception $exception) {
                Mage::helper('recommender/logger')->logCritical("Model/Observer/AddToCart->observe() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode(), $product_id));
            }
        } else {
            Mage::helper('recommender/logger')->logError("Wrong element type received in cart event", array(get_class($observer)));
        }
    }
}
