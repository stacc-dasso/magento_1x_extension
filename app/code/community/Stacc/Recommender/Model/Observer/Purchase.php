<?php

/**
 * Model Class for Handling Magento Purchase Event Data
 *
 * Class Stacc_Recommender_Model_Observer_Purchase
 */
class Stacc_Recommender_Model_Observer_Purchase
{


    /**
     * Observe function to watch Magento Purchase Event
     *
     * @param $observer
     */
    public function observe($observer)
    {
        if (get_class($observer) == "Mage_Core_Model_Observer" || get_class($observer) == "Varien_Event_Observer") {
            try {
                $response = false;

                if (method_exists($observer, "getEvent")) {

                    $order = $observer->getEvent()->getOrder();
                    $data = $this->getData($order->getAllVisibleItems());
                    $response = Mage::helper('recommender/apiclient')->sendPurchaseEvent($data);
                }

                if (!$response) {
                    Mage::helper('recommender/logger')->logError("Failed to sync purchase event", array($response));
                }
            } catch (Exception $exception) {
                Mage::helper('recommender/logger')->logCritical("Model/Observer/Purchase->observer() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            }
        } else {
            Mage::helper('recommender/logger')->logError("Wrong element type received in purchase event", array(get_class($observer)));
        }
    }

    /**
     * Helper function. Sets data to be sent to STACC on purchase event.
     *
     * @param $allItems
     * @return array
     */
    private function getData($allItems)
    {

        try {
            $collection = $allItems;
            $items = array();
            $store = Mage::app()->getStore();
            foreach ($collection as $product) {
                $prod = [
                    'item_id' => $product->getProductId(),
                    'quantity' => $product->getQtyOrdered(),
                    'price' => $product->getPrice(),
                    'properties' => [
                        'formatted_price' => $product->getRowTotal() - $product->getDiscountAmount() + $product->getTaxAmount(),
                        'sku' => $product->getSku(),
                        'tax_amount' => $product->getTaxAmount(),
                        'currency' => Mage::helper('recommender/environment')->getCurrencyCode(),
                        'current_crcy' => $store->getCurrentCurrencyCode(),
                        'lang' => $store->getCode()
                    ]
                ];
                $items[] = $prod;

            }
            return $items;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/Observer/Purchase->getData() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }
    }
}
