<?php
/**
 * @category   ShoppingFlux
 * @package    Profileolabs_Shoppingflux
 * @author Vincent Enjalbert @ Web-cooking.net
 */
class Profileolabs_Shoppingflux_Block_Tracking_Buyline extends Mage_Core_Block_Text {

    protected function _toHtml() {
       
        $this->addText('
			<!-- BEGIN Shopping flux Tracking -->
			  <script type="text/javascript" src="http://tracking.shopping-flux.com/gg.js"></script>
			<!-- END Shopping flux Tracking -->
			        ');
        return parent::_toHtml();
    }

}
