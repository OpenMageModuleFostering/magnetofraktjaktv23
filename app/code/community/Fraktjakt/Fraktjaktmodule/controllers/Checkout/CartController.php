<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shopping cart controller
 */
 
 require_once 'Mage/Checkout/controllers/CartController.php';
class Fraktjakt_Fraktjaktmodule_Checkout_CartController extends Mage_Checkout_CartController
{
	
	 /**
     * Initialize shipping information
     */
    public function estimatePostAction()
    {
        $country    = (string) $this->getRequest()->getParam('country_id');
        $postcode   = (string) $this->getRequest()->getParam('estimate_postcode');
        $city       = (string) $this->getRequest()->getParam('estimate_city');
        $regionId   = (string) $this->getRequest()->getParam('region_id');
        $region     = (string) $this->getRequest()->getParam('region');
		$street     = (string) $this->getRequest()->getParam('estimate_street');

        $this->_getQuote()->getShippingAddress()
            ->setCountryId($country)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setRegionId($regionId)
            ->setRegion($region)
			->setStreet($street)
            ->setCollectShippingRates(true);
        $this->_getQuote()->save();
        $this->_goBack();
		
		  Mage::log("yes I am here");
		
        parent::estimatePostAction();
    }
	
/*	public function addAction()
{
echo 'I successfully Override Cart Controller';
parent::addAction();
}
public function indexAction()
{
	  Mage::log("I successfully Override Cart Controller");
echo 'I successfully Override Cart Controller';
parent::estimatePostAction();
}*/
	
	
	}
