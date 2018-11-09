<?php

/**
 * Class for Recommendations container
 *
 * Class Stacc_Recommender_Block_Container
 */
class Stacc_Recommender_Block_Container extends Mage_Core_Block_Template
{
    /**
     * Variable for Container Id
     *
     * @var
     */
    private $_elementId;

    /**
     * Variable for Template file name
     *
     * @var
     */
    private $_recommendationTemplate;

    /**
     * Default Template for recommendations
     *
     * @var string
     */
    private $_defaultRecommendationTemplate = 'recommendation_block_a.phtml';

    /**
     * Default value for element id, if id is not set
     *
     * @var string
     */
    private $_defaultElementId = 'stacc_product_default';

    /**
     * Returns Container Element ID
     *
     * @return string
     */
    public function getElementId()
    {
        return $this->_elementId ? $this->_elementId : $this->_defaultElementId;
    }
    
    /**
     * Sets Container Element Id for the block
     *
     * @param $elementId
     * @return $this
     */
    public function setElementId($elementId)
    {
        $this->_elementId = $elementId;
        return $this;
    }

    /**
     * Returns Template file name
     *
     * @return string
     */
    public function getRecommendationTemplate()
    {
        return $this->_recommendationTemplate ? $this->_recommendationTemplate : $this->_defaultRecommendationTemplate;
    }


    /**
     * Sets Template used in to Display Recommendations
     *
     * @param $template
     * @return $this
     */
    public function setRecommendationTemplate($template)
    {
        $this->_recommendationTemplate = $template;
        return $this;
    }

    /**
     * Returns extension version to for the block
     *
     * @return $this
     */
    public function getExtensionVersion()
    {
        try {
            return Mage::Helper("recommender/environment")->getVersion();
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Block/Container.php->getExtensionVersion() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }
}