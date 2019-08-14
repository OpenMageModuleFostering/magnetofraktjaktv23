<?php
class Fraktjakt_Fraktjaktmodule_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {



    public function  __construct() {
		$url = 'http://www.fraktjakt.se/orders/list?webshop=1';

        $this->_addButton('testbutton', array(
            'label'     => Mage::helper('Sales')->__('Administrera frakten'),
            'onclick'   => "setLocation('$url')",
            'class'     => 'go'
        ), 0, 100, 'header', 'header');

        parent::__construct();

    }
}
?>