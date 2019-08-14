<?php
/**
 * Shopping Flux
 * @category   ShoppingFlux
 * @package    Profileolabs_Shoppingflux
 * @author vincent enjalbert - web cooking
 */
class Profileolabs_Shoppingflux_Adminhtml_GeneralController extends Mage_Adminhtml_Controller_Action
{
	public function userdefinedAction() {
            $installer = Mage::getResourceModel('catalog/setup','profileolabs_shoppingflux_setup');
            $installer->updateAttribute('catalog_product', 'shoppingflux_default_category', 'is_user_defined', 1);
            $installer->updateAttribute('catalog_product', 'shoppingflux_product', 'is_user_defined', 1);
        }
        
        
	public function nuserdefinedAction() {
            $installer = Mage::getResourceModel('catalog/setup','profileolabs_shoppingflux_setup');
            $installer->updateAttribute('catalog_product', 'shoppingflux_default_category', 'is_user_defined', 0);
            $installer->updateAttribute('catalog_product', 'shoppingflux_product', 'is_user_defined', 0);
        }
        
	
}