<?php
class Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	const MM = 'mm';
	const CM = 'cm';
	const IN = 'in';
	const FT = 'ft';
	const M = 'm';
	const KM = 'km';
	
    public function toOptionArray()
    {
        $arr = array();
		 $arr[] = array('value'=> self::CM, 'label'=>'cm');
		 $arr[] = array('value'=> self::MM, 'label'=>'mm');
		 $arr[] = array('value'=> self::IN, 'label'=>'tum');
		 $arr[] = array('value'=> self::FT, 'label'=>'fot');
		 $arr[] = array('value'=> self::M, 'label'=>'meter');
		 $arr[] = array('value'=> self::KM, 'label'=>'kilometer');
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