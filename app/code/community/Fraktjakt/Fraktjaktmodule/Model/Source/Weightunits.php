<?php
class Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	const KG = 'kg';
	const LB = 'lb';
	const OZ = 'oz';
	const GR = 'gr';
    public function toOptionArray()
    {
        $arr = array();
		 $arr[] = array('value'=> self::KG, 'label'=>'kg');
		 $arr[] = array('value'=> self::GR, 'label'=>'grams');
		 $arr[] = array('value'=> self::LB, 'label'=>'LB');
		 $arr[] = array('value'=> self::OZ, 'label'=>'Oz');
        return $arr;
    }
	
	public function getAllOptions()
    {
        return $this->toOptionArray();
    }


    public function toOptionHash()
    {
        $source = $this->_getSource();
        return $source ? $source->toOptionHash() : array();
    }

}