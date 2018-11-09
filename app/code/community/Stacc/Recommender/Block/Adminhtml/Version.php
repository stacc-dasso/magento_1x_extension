<?php

/**
 * Class Stacc_Recommender_Block_Adminhtml_Version
 */
class Stacc_Recommender_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Variable for holding the value that is passed into the function
     *
     * @var
     */
    private $_element;

    /**
     * Method to override _getElementHtml and display suitable value instead of form field element
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {

        try {
            if ($element) {
                $this->_element = $element;
            }
            $error = "";
            $environment = Mage::helper('recommender/environment');
            $apiKey = $environment->getApiKey();
            $shopID = $environment->getClientId();

            if ($apiKey && $shopID) {
                $data = [
                    "media_url" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "catalog/product/",
                    "js" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS),
                    "skins" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN),
                    "base_url" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
                ];
                $request = Mage::helper('recommender/apiclient')->sendCheckCredentials($data);

                $error = $this->requestResponse($request);
            }

            return (string)$environment->getVersion() . $error;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Block/Adminhtml/Version->_getElementHtml() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns message to display depending on request result
     *
     * @param $request
     * @return string
     */
    private function requestResponse($request)
    {
        if ($request != '{}') {
            if ($request && !json_decode($request)->verification) {
                return "<br/><span style='color:red'>" . $this->__("Please check your API Key and Shop ID") . "</span>";
            }
            if (!$request) {
                return "<br/><span style='color:red'>" . $this->__("Can't connect to STACC server") . "</span>";
            }
            return "<br/><span style='color:red'>" . $this->__("Failed to verify the Shop ID and the API Key") . "</span>";
        } else {
            return "<br/><span style='color:green'>" . $this->__("Shop ID and API Key verified") . "</span>";
        }
    }
}