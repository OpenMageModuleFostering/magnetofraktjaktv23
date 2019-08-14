<?php

class Fraktjakt_Fraktjaktmodule_Block_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	 protected $_code = 'fraktjakt_fraktjaktmodule';
 protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
		 $fraktjaktLogin = Mage::getStoreConfig('carriers/'.$this->_code.'/fraktjaktLogin');
		$fraktjaktPassword = Mage::getStoreConfig('carriers/'.$this->_code.'/fraktjaktPassword');
		
        $this->setElement($element);
        //$url = $this->getUrl('/'); //
		
		 if($this->getConfig('test_mode') == 1)
        {
            $url = 'http://api2.fraktjakt.se/account/login?login='.$fraktjaktLogin.'&password='.$fraktjaktPassword;

        }
		else
		{
			$url = 'http://www.fraktjakt.se/account/login?login='.$fraktjaktLogin.'&password='.$fraktjaktPassword;

		}
                $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Log in!')
					 ->setTarget('_blank')
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }	
	
	
	}