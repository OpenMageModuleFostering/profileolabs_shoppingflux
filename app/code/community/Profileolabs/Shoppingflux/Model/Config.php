<?php

/**
 * Shopping Flux Service
 * @category   ShoppingFlux
 * @package    Profileolabs_Shoppingflux
 * @author kassim belghait, vincent enjalbert @ web-cooking.net
 */
class Profileolabs_Shoppingflux_Model_Config extends Varien_Object {

    public function getConfigData($key, $storeId = null) {
        $dataKey = str_replace('/', '_', 'conf/' . $key . '/' . (is_null($storeId) ? '0' : $storeId));
        if (!$this->hasData($dataKey)) {
            $value = Mage::getStoreConfig($key, $storeId);
            $this->setData($dataKey, $value);
        }
        return $this->getData($dataKey);
    }

    public function getConfigFlag($key, $storeId = null) {
        $dataKey = str_replace('/', '_', 'flag/' . $key . '/' . (is_null($storeId) ? '0' : $storeId));
        if (!$this->hasData($dataKey)) {
            $value = Mage::getStoreConfigFlag($key, $storeId);
            $this->setData($dataKey, $value);
        }
        return $this->getData($dataKey);
    }

    /**
     * Return API KEY
     * @return string
     */
    public function getApiKey($storeId = null) {
        return $this->getConfigData('shoppingflux/configuration/api_key', $storeId);
    }
    
    public function getIdClient($storeId = null) {
        return $this->getConfigData('shoppingflux/configuration/login', $storeId);
    }
    
    public function getIdTracking($storeId = null) {
        return $this->getConfigData('shoppingflux/configuration/id_tracking', $storeId);
    }
    
    

    public function isBuylineEnabled($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/configuration/enable_buyline', $storeId);
    }

    /*
     * Define is in test mode
     * @return boolean
     */

    public function isSandbox() {
        return $this->getConfigFlag('shoppingflux/configuration/is_sandbox');
    }

    /**
     * Get WS URI
     * @return string uri of web service
     */
    public function getWsUri() {
        if ($this->isSandbox())
            return $this->getConfigData('shoppingflux/configuration/ws_uri_sandbox');
        return $this->getConfigData('shoppingflux/configuration/ws_uri_prod');
    }

    public function isExportEnabled($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/active', $storeId);
    }

    public function isExportFilteredByAttribute($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/filter_by_attribute', $storeId);
    }

    /**
     * Retrieve if export sold out products
     *
     * @return boolean
     */
    public function isExportSoldout($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/export_soldout', $storeId);
    }
    
 
    public function getVisibilitiesToExport($storeId = null) {
        return explode(',', $this->getConfigData('shoppingflux_export/general/export_visibility', $storeId));
    }

    /**
     * Retrieve limit of product in query
     * 
     * @return int
     */
    public function getExportProductLimit($storeId = null) {
        return (int) $this->getConfigData('shoppingflux_export/general/limit_product', $storeId);
    }

    public function getUseAllStoreCategories($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/all_store_categories', $storeId);
    }

    public function getUseAllStoreProducts($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/all_store_products', $storeId);
    }
    
    public function getUseOnlySFCategory($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/use_only_shoppingflux_category', $storeId);
    }
    
    public function getMaxCategoryLevel($storeId = null) {
        return $this->getConfigData('shoppingflux_export/general/max_category_level', $storeId);
    }
    
    public function getEnableEvents($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/enable_events', $storeId);
    }
    
    public function getManageConfigurables($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/manage_configurable', $storeId);
    }
    
    public function getManageCatalogRules($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/manage_catalog_rules', $storeId);
    }
    
    public function getManageMediaGallery($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/manage_media_gallery', $storeId);
    }
    
    
    
    /**
     * Return Attributes Knowed in array with key=>value
     * key = node adn value = inner text
     * @return array 
     */
    public function getMappingAttributesKnow($storeId = null) {
        return $this->getConfigData('shoppingflux_export/attributes_know', $storeId);
    }

    /**
     * Return Attributes Unknowed in array with key=>value
     * key = node adn value = inner text
     * @param int $storeId
     * @return array 
     */
    public function getMappgingAttributesUnKnow($storeId = null) {
        return $this->getConfigData('shoppingflux_export/attributes_unknow', $storeId);
    }

    /**
     * Return Attributes Unknowed in array with key=>value
     * key = node adn value = inner text
     * @param int $storeId
     * @return array 
     */
    public function getAdditionalAttributes($storeId = null) {
        $additionnal = explode(',',$this->getConfigData('shoppingflux_export/attributes_additionnal/list', $storeId));
        $unknow = $this->getMappgingAttributesUnKnow($storeId);
        $know = $this->getMappingAttributesKnow($storeId);
        //We do not want attributes that are already in known or unknown lists
        $additionnal = array_diff($additionnal, $know, $unknow);
        return $additionnal;
    }

    /**
     * Return ALL Attributes Knowed and Unknowed in array with key=>value
     * key = node adn value = inner text
     * @return array 
     * @param int $storeId
     */
    public function getMappingAllAttributes($storeId = null) {
        return array_merge($this->getMappingAttributesKnow($storeId), $this->getMappgingAttributesUnKnow($storeId));
    }
    
    public function getMemoryLimit() {
        $memoryLimit = intval($this->getConfigData('shoppingflux_export/general/memory_limit'));
        if($memoryLimit>10) {
            return $memoryLimit;
        }
        return 512;
    }
    
    public function isSyncEnabled($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/enable_sync', $storeId);
    }
    
    public function useManageStock($storeId = null) {
        return $this->getConfigFlag('shoppingflux_export/general/use_manage_stock', $storeId);
    }

    /** ORDERS * */
    public function isOrdersEnabled($storeId = null) {
        return $this->getConfigFlag('shoppingflux_mo/manageorders/enabled', $storeId);
    }

    /**
     * Get Limit orders
     * @return int 
     */
    public function getLimitOrders($storeId = null) {
        $limit = $this->getConfigData('shoppingflux_mo/manageorders/limit_orders', $storeId);
        if ($limit < 1)
            $limit = 10;
        return $limit;
    }
    
    public function getAddressLengthLimit($storeId = null) {
        $limit = $this->getConfigData('shoppingflux_mo/import_customer/limit_address_length');
        if(!$limit || $limit < 20)
            return false;
        return $limit;
    }

    /**
     * get customer Group
     */
    public function getCustomerGroupIdFor($marketplace = false, $storeId = null) {
        if ($marketplace) {
            $marketplace = strtolower($marketplace);
            $customerGroupId = $this->getConfigData('shoppingflux_mo/import_customer/' . $marketplace . "_group", $storeId);
            if ($customerGroupId)
                return $customerGroupId;
        }
        $customerGroupId = $this->getConfigData("shoppingflux_mo/import_customer/default_group", $storeId);
        if ($customerGroupId)
            return $customerGroupId;
        return false;
    }
    
    
    
    public function getShippingMethodFor($marketplace = false, $sfShippingMethod = false, $storeId = null) {
        $defaultShippingMethod = Mage::getStoreConfig('shoppingflux_mo/shipping_method/default_method', $storeId);
        if(!$marketplace) {
            return $defaultShippingMethod;
        }
        $marketplace = strtolower($marketplace);
        $marketplaceShippingMode = Mage::getStoreConfig('shoppingflux_mo/shipping_method/' . $marketplace . '_method', $storeId);
        if(!$sfShippingMethod) {
            return $marketplaceShippingMode?$marketplaceShippingMode:$defaultShippingMethod;
        }
        $sfShippingMethodCode = Mage::getModel('profileolabs_shoppingflux/manageorders_shipping_method')->getFullShippingMethodCodeFor($marketplace, $sfShippingMethod);
        $sfShippingMethodCode = preg_replace('%[^a-zA-Z0-9_]%', '', $sfShippingMethodCode);
        $shippingMethod = Mage::getStoreConfig('shoppingflux_mo/advanced_shipping_method/' . $sfShippingMethodCode, $storeId);
        return $shippingMethod?$shippingMethod:($marketplaceShippingMode?$marketplaceShippingMode:$defaultShippingMethod);
    }

    /**
     * Retrieve if we must to create invoice
     * 
     * @return boolean
     */
    public function createInvoice($storeId = null) {
        return $this->getConfigFlag('shoppingflux_mo/manageorders/create_invoice', $storeId);
    }

    /**
     * Retrieve if we must to apply tax
     * 
     * @return boolean
     */
    public function applyTax($storeId = null) {
        return true;
        //return $this->getConfigFlag('shoppingflux_mo/manageorders/apply_tax', $storeId);
    }

}