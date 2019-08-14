<?php
/**
 * Shopping Flux
 * @category   ShoppingFlux
 * @package    Profileolabs_Shoppingflux
 * @author kassim belghait - Vincent Enjalbert
 */
class Profileolabs_Shoppingflux_Model_Export_Source_Attributes
{
    
    protected $_exceptions = array(
        'store',
        'websites',
        'attribute_set',
        'type',
        'news_from_date',
        'news_to_date',
        'gallery',
        'url_key',
        'url_path',
        'minimal_price',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'category_ids',
        'options_container',
        'required_options',
        'has_options',
        'tax_class_id',
        'custom_layout_update',
        'page_layout',
        'recurring_profile',
        'is_recurring',
    );
    
    public function toOptionArray()
    {
        $attributes = array();
    	$attributesArray= Mage::getSingleton('profileolabs_shoppingflux/export_convert_parser_product')->getExternalAttributes();
        foreach($attributesArray as $k=>$v) {
            if(!in_array($k, $this->_exceptions)) {
                $attributes[] = array('value'=>$k, 'label'=>$v);
            }
        }
    	array_unshift($attributes, array("value"=>"none","label"=>""));
        return $attributes;        
    }
}
