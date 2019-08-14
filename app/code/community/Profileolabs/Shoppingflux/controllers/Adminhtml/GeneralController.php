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
        
        public function testAction() {
            ini_set('display_errors',1);
            error_reporting(-1);
            //$write = Mage::getSingleton('core/resource')->getConnection('core_write');
            //$query = "delete from " . Mage::getConfig()->getTablePrefix() . 'core_resource' . " where code = 'profileolabs_shoppingflux_setup' ";
            //$write->query($query);
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $select = $read->select()
                            ->distinct()
                            ->from(Mage::getConfig()->getTablePrefix() . 'core_resource', array('code', 'version', 'data_version'))
                            ->where('code = ?', 'profileolabs_shoppingflux_setup');
            $values = $read->fetchAll($select);
            foreach($values as $value) {
                var_dump($value);
            }
        }
        
	
}