<?php

/**
 * Orders getted here
 * 
 * @category ShoppingFlux
 * @package    Profileolabs_Shoppingflux
 * @author kassim belghait, vincent enjalbert @ web-cooking
 *
 */
class Profileolabs_Shoppingflux_Model_Manageorders_Order extends Varien_Object {

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer = null;

    /**
     * Config Data of Module Manageorders
     * @var Profileolabs_Shoppingflux_Model_Manageorders_Config
     */
    protected $_config = null;
    protected $_paymentMethod = 'shoppingflux_purchaseorder';
    protected $_shippingMethod = 'shoppingflux_shoppingflux';
    protected $_nb_orders_imported = 0;
    protected $_nb_orders_read = 0;
    protected $_ordersIdsImported = array();
    protected $_orderIdsAlreadyImported = null;
    protected $_result;
    protected $_resultSendOrder = "";
    protected $_isUnderVersion14 = null;

    /**
     * Product model
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_productModel;

    public function getResultSendOrder() {
        return $this->_resultSendOrder;
    }

    public function isUnderVersion14() {
        if (is_null($this->_isUnderVersion14)) {
            $this->_isUnderVersion14 = $this->getHelper()->isUnderVersion14();
        }
        return $this->_isUnderVersion14;
    }

    /**
     * Retrieve product model cache
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProductModel() {
        if (is_null($this->_productModel)) {
            $productModel = Mage::getModel('profileolabs_shoppingflux/manageorders_product');
            $this->_productModel = Mage::objects()->save($productModel);
        }
        return Mage::objects()->load($this->_productModel);
    }

    public function getOrderIdsAlreadyImported() {
        if (is_null($this->_orderIdsAlreadyImported)) {
            $orders = Mage::getModel('sales/order')->getCollection()
                    ->addAttributeToFilter('from_shoppingflux', 1)
                    ->addAttributeToSelect('order_id_shoppingflux');

            $this->_orderIdsAlreadyImported = array();
            foreach ($orders as $order) {
                $this->_orderIdsAlreadyImported[] = $order->getOrderIdShoppingflux();
            }
        }

        return $this->_orderIdsAlreadyImported;
    }

    public function isAlreadyImported($idShoppingflux) {
        $alreadyImported = $this->getOrderIdsAlreadyImported();
        if (in_array($idShoppingflux, $alreadyImported))
            return true;

        return false;
    }

    public function getSession() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getQuote($storeId=null) {
        return $this->getSession()->getQuote();
    }

    /**
     * Retrieve config
     * @return Profileolabs_Shoppingflux_Model_Manageorders_Config
     */
    public function getConfig() {
        if (is_null($this->_config)) {
            $this->_config = Mage::getSingleton('profileolabs_shoppingflux/config');
        }

        return $this->_config;
    }

    /**
     * Get orders and create it
     */
    public function manageOrders() {
        $stores = Mage::app()->getStores();

        if (Mage::app()->getStore()->getCode() != 'admin')
            Mage::app()->setCurrentStore('admin');
        
        $apiKeyManaged = array();
        
        //old module version compliance. The goal is to use default store, as in previous versions, if api key is set in global scope.
        $defaultStoreId = Mage::app()->getDefaultStoreView()->getId();
        if(key($stores) != $defaultStoreId) {
            $tmpStores = array($defaultStoreId=>$stores[$defaultStoreId]);
            foreach($stores as $store) {
                if($store->getId() != $defaultStoreId) {
                    $tmpStores[$store->getId()] = $store;
                }
            } 
            $stores = $tmpStores;
       }
        //old module version compliance end
        
        
        foreach ($stores as $_store) {
            $storeId = $_store->getId();
            if ($this->getConfig()->isOrdersEnabled($storeId)) {
                $apiKey = $this->getConfig()->getApiKey($storeId);
                
                if(!$apiKey || in_array($apiKey, $apiKeyManaged)) 
                        continue;
                $apiKeyManaged[] = $apiKey;
                
                $wsUri = $this->getConfig()->getWsUri();
              
                //$isUnderVersion14 = $this->getHelper()->isUnderVersion14();	

                /* @var $service Profileolabs_Shoppingflux_Model_Service */
                $service = new Profileolabs_Shoppingflux_Model_Service($apiKey, $wsUri);
                ini_set("memory_limit", "512M");
                try {

                    /* @var $this->_result Varien_Simplexml_Element */
                    $this->_result = $service->getOrders();
                    
                    $this->_nb_orders_imported = 0;
                } catch (Exception $e) {
                    Mage::logException($e);
                    $message = Mage::helper('profileolabs_shoppingflux')->__('Orders can not getted.'); //." ".Mage::helper('profileolabs_shoppingflux')->__('Error').": ".$e->getMessage();
                    $this->getHelper()->log($message);
                    //Mage::throwException($message);

                    Mage::throwException($e);
                }

                //We parse result
                //$nodes = current($this->_result->children());
                $nodes = $this->_result->children();
                foreach ($nodes as $childName => $child) {

                    $orderSf = $this->getHelper()->asArray($child);


                    if ($this->isAlreadyImported($orderSf['IdOrder']))
                        continue;


                    $this->_nb_orders_read++;

                    $this->createAllForOrder($orderSf, $storeId);

                    if ($this->_nb_orders_imported == $this->getConfig()->getLimitOrders($storeId))
                        break;
                }

                try {
                    if ($this->_nb_orders_imported > 0) {
                        
                        $result = $service->sendValidOrders($this->_ordersIdsImported);


                        if ($result) {
                            if ($result->error) {
                                Mage::throwException($result->error);
                            }

                            $this->_resultSendOrder = $result->status;
                        } else {
                            $this->getHelper()->log("Error in order ids validated");
                            Mage::throwException("Error in order ids validated");
                        }
                    }
                } catch (Exception $e) {
                    $this->getHelper()->log($e->getMessage());
                    Mage::throwException($e);
                }
            }
        }
        return $this;
    }

    /**
     * Inititalize the quote with minimum requirement
     * @param array $orderSf
     */
    protected function _initQuote(array $orderSf, $storeId) {

        if(is_null($storeId)) {//just in case.. 
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }
       
        $this->_getQuote()->setStoreId($storeId);
        
        //Super mode is setted to bypass check item qty ;)
        $this->_getQuote()->setIsSuperMode(true);

        //Set boolean shopping flux and shipping prices in session for shopping method
        $this->getSession()->setIsShoppingFlux(true);
        //$this->getSession()->setShippingPrice((float)$orderSf['TotalShipping']);

        $this->_getQuote()->setCustomer($this->_customer);
    }

    /**
     * Create or Update customer with converter
     * @param array $data Data From ShoppingFlux
     */
    protected function _createCustomer(array $data, $storeId) {
        try {

            /* @var $convert_customer Profileolabs_Shoppingflux_Model_Manageorders_Convert_Customer */
            $convert_customer = Mage::getModel('profileolabs_shoppingflux/manageorders_convert_customer');

            $this->_customer = $convert_customer->toCustomer(current($data['BillingAddress']), $storeId);
            $billingAddress = $convert_customer->addresstoCustomer(current($data['BillingAddress']), $storeId, $this->_customer);

            $this->_customer->addAddress($billingAddress);

            $shippingAddress = $convert_customer->addresstoCustomer(current($data['ShippingAddress']), $storeId, $this->_customer, 'shipping');
            $this->_customer->addAddress($shippingAddress);
            $customerGroupId = $this->getConfig()->getCustomerGroupIdFor($data['Marketplace'], $storeId);
            if ($customerGroupId) {
                $this->_customer->setGroupId($customerGroupId);
            }
            $this->_customer->save();
        } catch (Exception $e) {
            Mage::throwException($e);
        }
    }

    public function createAllForOrder($orderSf, $storeId) {
        try {

            //$this->_quote = null;
            $this->_customer = null;


            //Create or Update customer with addresses
            $this->_createCustomer($orderSf, $storeId);

            $this->_initQuote($orderSf, $storeId);

            //Add products to quote with data from ShoppingFlux
            $this->_addProductsToQuote($orderSf);

            $order = null;
            if (!$this->isUnderVersion14())
                $order = $this->_saveOrder($orderSf);
            else
                $order = $this->_saveOrder13($orderSf);


            $this->_nb_orders_imported++;

            if (!is_null($order) && $order->getId())
                $this->_changeDateCreatedAt($order, $orderSf['OrderDate']);

            //Erase session for the next order
            $this->getSession()->clear();
        } catch (Exception $e) {
            $this->getHelper()->log($e->getMessage(), $orderSf['IdOrder']);
            //Erase session for the next order
            $this->getSession()->clear();
        }
    }

    protected function _changeDateCreatedAt($order, $date) {
        try {

            $order->setCreatedAt($date);
            //$order->setUpdatedAt($date);
            $order->save();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException($message);
        }
    }

    /**
     * Add products to quote with data from ShoppinfFlux
     * @param array $orderSf
     */
    protected function _addProductsToQuote(array $orderSf) {
        $totalAmount = $orderSf['TotalAmount'];
        $productsSf = current($orderSf['Products']);
        $productsToIterate = current($productsSf);
        
        
        
        
        if (!$this->_customer->getDefaultBilling() || !$this->_customer->getDefaultShipping())
            $this->_customer->load($this->_customer->getId());

        $customerAddressBillingId = $this->_customer->getDefaultBilling();
        $customerAddressShippingId = $this->_customer->getDefaultShipping();

        //Set billing Address
        $addressBilling = $this->_getQuote()->getBillingAddress();
        //Make sure addresses will be saved without validation errors
        $addressBilling->setShouldIgnoreValidation(true);
        $customerAddressBilling = Mage::getModel('customer/address')->load($customerAddressBillingId);
        $addressBilling->importCustomerAddress($customerAddressBilling)->setSaveInAddressBook(0);

        //Set shipping Address
        $addressShipping = $this->_getQuote()->getShippingAddress();
        //Make sure addresses will be saved without validation errors
        $addressShipping->setShouldIgnoreValidation(true);
        $customerAddressShipping = Mage::getModel('customer/address')->load($customerAddressShippingId);
        $addressShipping->importCustomerAddress($customerAddressShipping)->setSaveInAddressBook(0);
        $addressShipping->setSameAsBilling(0);


        //Convert shipping price by tax rate
        $shippingPrice = (float) $orderSf['TotalShipping'];
        $this->getSession()->setShippingPrice($shippingPrice);
        if (!Mage::helper('tax')->shippingPriceIncludesTax() && Mage::helper('tax')->getShippingTaxClass(null)) {
            $percent = null;
            $pseudoProduct = new Varien_Object();
            $pseudoProduct->setTaxClassId(Mage::helper('tax')->getShippingTaxClass(null));

            $taxClassId = $pseudoProduct->getTaxClassId();
            if (is_null($percent)) {
                if ($taxClassId) {
                    $request = Mage::getSingleton('tax/calculation')->getRateRequest($addressShipping, $addressBilling, null, null);
                    $request->setProductClassId($taxClassId);
                    $request->setCustomerClassId($this->_getQuote()->getCustomerTaxClassId());
                    $percent = Mage::getSingleton('tax/calculation')->getRate($request);

                    if ($percent !== false || !is_null($percent)) {

                        $shippingPrice = $shippingPrice - ($shippingPrice / (100 + $percent) * $percent);
                        $this->getSession()->setShippingPrice($shippingPrice);
                    }
                }
            }

            //Mage::log("including tax = ".$includingTax." shipping price = ".$shippingPrice,null,'test_shipping_price.log');
        }

        //Set shipping Mehtod and collect shipping rates
        $addressShipping->setShippingMethod($this->_shippingMethod)->setCollectShippingRates(true);
        
        
        

        foreach ($productsToIterate as $key => $productSf) {

            $sku = $productSf['SKU'];

            if (($productId = $this->getProductModel()->getResource()->getIdBySku($sku)) != false) {
                $product = Mage::getModel('profileolabs_shoppingflux/manageorders_product')->load($productId); // $this->getProductModel()->reset()->load($productId);

                $request = new Varien_Object(array('qty' => $productSf['Quantity']));
                if ($product->getTypeId() == 'simple' && $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {

                    $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
                            ->getParentIdsByChild($product->getId());

                    if (count($parentIds)) {
                        $parentId = current($parentIds);

                        $attributesConf = $this->getHelper()->getAttributesConfigurable($parentId);
                        $superAttributes = array();

                        foreach ($attributesConf as $attribute) {

                            $attributeCode = $attribute['attribute_code'];
                            $attributeId = $attribute['attribute_id'];

                            $superAttributes[$attributeId] = $product->getData($attributeCode);
                        }

                        $product = Mage::getModel('profileolabs_shoppingflux/manageorders_product')->load($parentId);

                        $request->setData('super_attribute', $superAttributes);
                    }
                }


                $item = $this->_getQuote()->addProduct($product, $request);

                if (!is_object($item)) {
                    $this->getSession()->clear();
                    Mage::throwException("le produit sku = " . $sku . " n'a pas pu être ajouté! Id = " . $product->getId() . " Item = " . (string) $item);
                }


                //Save the quote with the new product
                $this->_getQuote()->save();

                
                $unitPrice = $productSf['Price'];
                if($this->getConfig()->applyTax() && !Mage::helper('tax')->priceIncludesTax()) {
                    $taxClassId = $product->getTaxClassId();
                    $request = Mage::getSingleton('tax/calculation')->getRateRequest($addressShipping, $addressBilling, null, null);
                    $request->setProductClassId($taxClassId);
                    $request->setCustomerClassId($this->_getQuote()->getCustomerTaxClassId());
                    $percent = Mage::getSingleton('tax/calculation')->getRate($request);
                    $unitPrice = $unitPrice / (1+$percent/100);
                }
                
                
                //Modify Item price
                $item->setCustomPrice($unitPrice);
                $item->setOriginalCustomPrice($unitPrice);
                $item->save();

                if (is_object($parentItem = $item->getParentItem())) {
                    $parentItem->setCustomPrice($unitPrice);
                    $parentItem->setOriginalCustomPrice($unitPrice);
                    $parentItem->save();
                }

                //Mage::log(print_r($item->debug(),true),null,'debug_items.log');
            } else {

                $this->getSession()->clear();
                Mage::throwException("le produit sku = " . $sku . " n'existe plus en base!");
            }
        }
        

        $this->_getQuote()->collectTotals();
        $this->_getQuote()->save();

        //Set payment method
        /* @var $payment Mage_Sales_Quote_Payment */
        $this->_getQuote()->getShippingAddress()->setPaymentMethod($this->_paymentMethod);
        $payment = $this->_getQuote()->getPayment();
        $dataPayment = array('method' => $this->_paymentMethod, 'marketplace' => $orderSf['Marketplace']);
        $payment->importData($dataPayment);
        //$addressShipping->setShippingMethod($this->_shippingMethod)->setCollectShippingRates(true);

        $this->_getQuote()->collectTotals();
        $this->_getQuote()->save();
    }

    /**
     * Save the new order with the quote
     * @param array $orderSf
     */
    protected function _saveOrder(array $orderSf) {
        $orderIdShoppingFlux = (string) $orderSf['IdOrder'];
        $additionalData = array("from_shoppingflux" => 1,
            "marketplace_shoppingflux" => $orderSf['Marketplace'],
            "fees_shoppingflux" => (float) (isset($orderSf['Fees'])?$orderSf['Fees']:0),
            "order_id_shoppingflux" => $orderIdShoppingFlux);

        /* @var $service Mage_Sales_Model_Service_Quote */
        $service = Mage::getModel('sales/service_quote', $this->_getQuote());
        $service->setOrderData($additionalData);
        $order = false;
        if (method_exists($service, "submitAll")) {

            $service->submitAll();
            $order = $service->getOrder();
        } else {
            $order = $service->submit();
        }

        if ($order) {
            $newStatus = $this->getConfig()->getConfigData('shoppingflux_mo/manageorders/new_order_status', $order->getStoreId());
            if($newStatus) {
                $order->setStatus($newStatus);
                $order->save();
            }


            
            $this->_saveInvoice($order);
            
            
            $processingStatus = $this->getConfig()->getConfigData('shoppingflux_mo/manageorders/processing_order_status', $order->getStoreId());
            if($processingStatus) {
                $order->setStatus($processingStatus);
                $order->save();
            }

            //Set array with shopping flux ids
            $this->_ordersIdsImported[$orderIdShoppingFlux] = $orderSf['Marketplace'];

            return $order;
        }

        return null;
    }

    protected function _saveOrder13(array $orderSf) {
        $orderIdShoppingFlux = (string) $orderSf['IdOrder'];
        $additionalData = array("from_shoppingflux" => 1,
            "marketplace_shoppingflux" => $orderSf['Marketplace'],
            "fees_shoppingflux" => (float) (isset($orderSf['Fees'])?$orderSf['Fees']:0.0),
            "order_id_shoppingflux" => $orderIdShoppingFlux);


        $billing = $this->_getQuote()->getBillingAddress();
        $shipping = $this->_getQuote()->getShippingAddress();

        $this->_getQuote()->reserveOrderId();
        $convertQuote = Mage::getModel('sales/convert_quote');
        /* @var $convertQuote Mage_Sales_Model_Convert_Quote */

        $order = $convertQuote->addressToOrder($shipping);

        $order->addData($additionalData);

        /* @var $order Mage_Sales_Model_Order */
        $order->setBillingAddress($convertQuote->addressToOrderAddress($billing));
        $order->setShippingAddress($convertQuote->addressToOrderAddress($shipping));

        $order->setPayment($convertQuote->paymentToOrderPayment($this->_getQuote()->getPayment()));

        foreach ($this->_getQuote()->getAllItems() as $item) {
            $orderItem = $convertQuote->itemToOrderItem($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }

        /**
         * We can use configuration data for declare new order status
         */
        Mage::dispatchEvent('checkout_type_onepage_save_order', array('order' => $order, 'quote' => $this->getQuote()));
        //Mage::throwException(print_r($order->getData(),true));
        //die("<pre> DIE = ".print_r($order->getData()));
        $order->place();

        $order->setCustomerId($this->_getQuote()->getCustomer()->getId());

        $order->setEmailSent(false);
        $order->save();

        Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order' => $order, 'quote' => $this->getQuote()));

        $this->_getQuote()->setIsActive(false);
        $this->_getQuote()->save();

        ///////////////////////////////////////////////////////////////////////////

        if ($order) {

            $this->_saveInvoice($order);

            //Set array with shopping flux ids
            $this->_ordersIdsImported[$orderIdShoppingFlux] = $orderSf['Marketplace'];

            return $order;
        }

        return null;
    }

    /**
     * Create and Save invoice for the new order
     * @param Mage_Sales_Model_Order $order
     */
    protected function _saveInvoice($order) {
        Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order' => $order, 'quote' => $this->_getQuote()));

        if (!$this->getConfig()->createInvoice()) {
            return $this;
        }

        //Prepare invoice and save it
        $path = Mage::getBaseDir() . "/app/code/core/Mage/Sales/Model/Service/Order.php";
        $invoice = false;
        if (file_exists($path))
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        else
            $invoice = $this->_initInvoice($order);

        if ($invoice) {
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
           
            
            
            $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();
        }
    }

    /**
     * Initialize invoice
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order_Invoice $invoice
     */
    protected function _initInvoice($order) {

        $convertor = Mage::getModel('sales/convert_order');
        $invoice = $convertor->toInvoice($order);
        $update = false;
        $savedQtys = array();
        $itemsToInvoice = 0;
        /* @var $orderItem Mage_Sales_Model_Order_Item */
        foreach ($order->getAllItems() as $orderItem) {

            if (!$orderItem->isDummy() && !$orderItem->getQtyToInvoice() && $orderItem->getLockedDoInvoice()) {
                continue;
            }

            if ($order->getForcedDoShipmentWithInvoice() && $orderItem->getLockedDoShip()) {
                continue;
            }

            if (!$update && $orderItem->isDummy() && !empty($savedQtys) && !$this->_needToAddDummy($orderItem, $savedQtys)) {
                continue;
            }
            $item = $convertor->itemToInvoiceItem($orderItem);

            if (isset($savedQtys[$orderItem->getId()])) {
                $qty = $savedQtys[$orderItem->getId()];
            } else {
                if ($orderItem->isDummy()) {
                    $qty = 1;
                } else {
                    $qty = $orderItem->getQtyToInvoice();
                }
            }
            $itemsToInvoice += floatval($qty);
            $item->setQty($qty);
            $invoice->addItem($item);

            if ($itemsToInvoice <= 0) {
                Mage::throwException($this->__('Invoice without products could not be created.'));
            }
        }


        $invoice->collectTotals();

        return $invoice;
    }

    /**
     * Get Helper
     * @return Profileolabs_Shoppingflux_Model_Manageorders_Helper_Data
     */
    public function getHelper() {
        return Mage::helper('profileolabs_shoppingflux');
    }

    public function getNbOrdersImported() {
        return $this->_nb_orders_imported;
    }

}