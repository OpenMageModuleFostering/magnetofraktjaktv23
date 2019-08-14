<?php
class Fraktjakt_Fraktjaktmodule_Model_Source_Method
{
    public function toOptionArray()
    {
        $shipmeth = Mage::getSingleton('Fraktjakt_Fraktjaktmodule_Model_Carrier_Shippingmethod');
        $arr = array();
        foreach ($shipmeth->getMethod() as $v) {
            $arr[] = array('value'=>$v, 'label'=>$v);
        }
        return $arr;
    }
}