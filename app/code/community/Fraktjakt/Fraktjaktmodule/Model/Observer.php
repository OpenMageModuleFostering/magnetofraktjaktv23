<?php

class Fraktjakt_Fraktjaktmodule_Model_Observer
{
	
	 protected $_code = 'fraktjakt_fraktjaktmodule';
	
	
	 public function getConfig($key)
    {
        return Mage::getStoreConfig('carriers/'.$this->_code.'/'.$key);
    }
	public function getStoreLocale()
    {
        return Mage::app()->getLocale()->getLocaleCode();
    }
    public function returnSwedish()
    {
        return ($this->getconfig('returnlang') == 1 ||
            ($this->getconfig('returnlangstore') == 1 &&  stristr($this->getStoreLocale(),'sv_')));
    }

	 public function salestest($observer)
    {
		
		
		$incrementid = $observer->getEvent()->getOrder()->getIncrementId();      
    $order = Mage::getModel('sales/order')->loadByIncrementId($incrementid);
	 //Mage::log("madhurima2222");
	 
       
		
		/* $shipment_collection = Mage::getResourceModel('sales/order_shipment_collection');
        $shipment_collection->addAttributeToFilter('order_id', $incrementid);
        $shipment_collection->load();*/

    
            

            
			
			/**
             * Check order existing
	             */
            if (!$order->getId()) {
				 Mage::throwException('The order no longer exists.');
                return false;
            }
	            /**
	             * Check shipment is available to create separate from invoice
             */
	            if ($order->getForcedDoShipmentWithInvoice()) {
	               // $this->_getSession()->addError($this->__('Cannot do shipment for the order separately from invoice.'));
					 Mage::throwException('Cannot do shipment for the order separately from invoice.');
	                return false;
	            }
            /**
	             * Check shipment create availability
	             */
          /*  if (!$order->canShip()) {
				 Mage::throwException('Cannot do shipment for the order.'); 
				
                return false;
	            }*/
			
			
			
			$shipment_collection = Mage::getResourceModel('sales/order_shipment_collection');
        $shipment_collection->addAttributeToFilter('order_id', $orderId);
        $shipment_collection->load();
			
        $firstItem = $shipment_collection->getFirstItem();
			if(count($shipment_collection) > 1)
        {
			
			Mage::throwException('already shiped.'); 
		}
		else
		{
			
			
			
            // Is the order shipable?
            if($order->canShip())
            {
             
			 
			 		 
	        
        $store_currency = Mage::app()->getStore()-> getCurrentCurrencyCode();
	
			
			 $shippingAddress = $order->getShippingAddress();
			 
			 
 
 $rates = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()
->getShippingRatesCollection();


foreach ($rates as $rate) {

 $_rate = $rate->getData();
 
 
  
   
if ( strcmp ( trim($_rate['code']), trim(Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod())) == 0  )
			   {
				   list($mtitle,$md,$mag,$sid,$spid) = preg_split('/[@]/',$_rate['method_description']);
			   }	 


}
		
		
		 $items = $order->getAllVisibleItems();

    $itemsInOrder = array();

    foreach($items as $item) {

        $product = $item->getProduct();
		//Mage::log($product);
        //echo "<pre>";
		//print_r($product);
       // $itemsInOrder[] = $product->name;
		
		/* $items_xml = "\n
        <commodity>
  <name>{$product->name}</name>
  <quantity>{$item->getQtyOrdered()}</quantity>
  <quantity_units>{$product->dimension_units}</quantity_units>
  <description>{$product->short_description}</description>
  <country_of_manufacture>{$product->country_of_manufacture}</country_of_manufacture>
  <weight>{$product->weight}</weight>
  <unit_price>{$product->price}</unit_price>
</commodity>
";*/
$quantity = round($item->getQtyOrdered());
		
		 $items_xml = "\n
        <commodity>
  <name>{$product->name}</name>
  <quantity>{$quantity}</quantity>
</commodity>
";
		
		
		
		
		
		$return_lang = 'EN';
        if($this->returnSwedish())
        {
            $return_lang = 'sv';
        }
			
			
		$consignorID = Mage::getStoreConfig('carriers/'.$this->_code.'/consignorID');
		$consignorkey = Mage::getStoreConfig('carriers/'.$this->_code.'/consignorkey');
			 
			  $xml_bits = '<?xml version="1.0" encoding="iso-8859-1"?>';
  $xml_bits.='<OrderSpecification>  ';
  $xml_bits.= "
  <consignor>
    <id>".$consignorID."</id>
    <key>".$consignorkey."</key>
    <currency>".$store_currency."</currency>
    <language>".$return_lang."</language>
	<encoding>iso-8859-1</encoding>
  </consignor>
   <shipment_id>".$sid."</shipment_id>
    <shipping_product_id>".$spid."</shipping_product_id>
	 <reference> order #".$order->getId()."</reference>
  <commodities>
  ".$items_xml."
  </commodities>
  <recipient>
  <company_to>".$shippingAddress->getCompany()."</company_to>
  <name_to>". trim($shippingAddress->getFirstname() . " " . $shippingAddress->getLastname()) ."</name_to>
  <telephone_to>".$shippingAddress->getTelephone()."</telephone_to>
  <email_to>".$shippingAddress->getEmail()."</email_to>
  </recipient>
  <booking>
<pickup_date>".date('Y-m-d')."</pickup_date>
<driving_instruction>testing testing</driving_instruction>
<user_notes>testing</user_notes>
</booking>
</OrderSpecification>
";		
			 
			  Mage::log($xml_bits);
			 
			 
			 
			 
			
		
		
		
		
		
		
		
		
		
		
		
		
		
		
	 if($this->getConfig('test_mode') == 1)
        {
             $url = "http://api2.fraktjakt.se/orders/order_xml";
        }
		else
		{
			 $url = "http://api1.fraktjakt.se/orders/order_xml";
		}
		
	   $httpHeaders = array(
        "Expect: ", // Disable the 100-continue header
        "Accept-Charset: ISO 8859-1",
        "Content-type: application/x-www-form-urlencoded"
      );
	   $httpPostParams = array(
          'md5_checksum' => md5($xml_bits),
          'xml' => utf8_encode($xml_bits)
        );
		
		 if (is_array($httpPostParams)) {
          foreach ($httpPostParams as $key => $value) {
            $httpPostParams[$key] = $key .'='. urlencode($value);
          }
          $httpPostParams = implode('&', $httpPostParams); 
        }
	  
	  
	  
        $ch = curl_init();
     
		curl_setopt($ch, CURLOPT_FAILONERROR, 0); // fail on errors
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1); // forces a non-cached connection
        if ($httpHeaders) curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders); // set http headers
        curl_setopt($ch, CURLOPT_POST, 1); // initialize post method
        curl_setopt($ch, CURLOPT_POSTFIELDS,urlencode($xml_bits));
        curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $httpPostParams); // variables to post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // timeout after 30s
        $qresult = curl_exec($ch);
		Mage::log(curl_error($ch));
		Mage::log($qresult);
		
		
		
		
		
        if (curl_errno($ch))
        {
            $tmp = $qresult;
            $qresult =  "<error>999</error>\n";
            $qresult =  "<statusmessage>Error communicating with Fraktjakt_Fraktjaktmodule " .
                curl_error($ch)."</statusmessage>\n".$tmp;
        }
        else
        {
			
			
			 $xml_results = simplexml_load_string($qresult);
			$xpath = $xml_results->xpath('code');
		  $requestID = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '2';
			
			if($requestID == '2')
			{
				
				
				$xpath = $xml_results->xpath('error_message');
		  $emessage = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
		  if(!empty($emessage))
		  {
			  // Mage::getSingleton('chekout/session')->addError($emessage);
			    Mage::throwException($emessage); 
		  }
		  else
		  {
			  // Mage::getSingleton('chekout/session')->addError('Error communicating with Fraktjakt_Fraktjaktmodule');
			  Mage::throwException('Error communicating with Fraktjakt_Fraktjaktmodule'); 
		  }
				 
				
				// curl_close($ch);
				//exit;
				 
			}
			else if($requestID == '1')
			{
			$xpath = $xml_results->xpath('warning_message');
		  $wmessage = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';	
		  
		  if(!empty($wmessage))
		  {
			  // Mage::getSingleton('chekout/session')->addError($wmessage);
			  Mage::throwException($wmessage); 
		  }
		  else
		  {
			   Mage::throwException('Error communicating with Fraktjakt_Fraktjaktmodule'); 
			   //Mage::getSingleton('chekout/session')->addError('Error communicating with Fraktjakt_Fraktjaktmodule');
		  }
		  curl_close($ch);
				//exit;
			}
			else if($requestID == '0')
			{
				$xpath = $xml_results->xpath('shipment_id');
		  $shipmentTrackingNumber = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
			// Mage::log(print_r($product));
			//$shipmentid = Mage::getModel('sales/order_shipment_api')->create($order->getIncrementId(), array());
			
			
			//$shipmentid = Mage::getModel('sales/order_shipment_api')->create( $incrementid, array());
			
			
			
			 /*$shipment = $observer->getEvent()->getShipment();
			 $track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($wshipment_id) //tracking number / awb number
                        ->setCarrierCode($order->getShippingCarrier()->getCarrierCode()) //carrier code
                        ->setTitle('Fraktjakt_Fraktjaktmodule'); //carrier title
                    $shipment->addTrack($track);*/
					
					
					try { 
					
					
						$shipment = Mage::getModel('sales/service_order', $order)
                            ->prepareShipment($this->_getItemQtys($order));
					
				
 
            /**
             * Carrier Codes can be like "ups" / "fedex" / "custom",
             * but they need to be active from the System Configuration area.
             * These variables can be provided custom-value, but it is always
             * suggested to use Order values
             */
            /*$shipmentCarrierCode = 'SPECIFIC_CARRIER_CODE';
            $shipmentCarrierTitle = 'Fraktjakt_Fraktjaktmodule';
 
            $arrTracking = array(
                'carrier_code' => isset($shipmentCarrierCode) ? $shipmentCarrierCode : $order->getShippingCarrier()->getCarrierCode(),
                'title' => isset($shipmentCarrierTitle) ? $shipmentCarrierTitle : $order->getShippingCarrier()->getConfigData('title'),
                'number' => $shipmentTrackingNumber,
				'url' => $shipmentTrackingNumber,
            );
 
            $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);*/
			Mage::log('Previous All data by Madhurima');
			 //$data = $this->getRequest()->getPost('shipment');
			   $ship_data = $shipment->getOrder()->getData();
			 Mage::log($ship_data);
			 
			 // Mage::log($data);
			 //exit;
			
			if (empty($shipmentTrackingNumber)) {
	                        //Mage::throwException($this->__('Tracking number cannot be empty.'));
							 Mage::throwException('Tracking number cannot be empty.'); 
                   }
			//print_r($order);
			$track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($shipmentTrackingNumber)
                    ->setCarrierCode( $this->_code)
					->setUrl("http://www.fraktjakt.se/trace/list_shipment/$shipmentTrackingNumber")
                    ->setTitle(Mage::getStoreConfig('carriers/'.$this->_code.'/'.$key));
					
            $shipment->addTrack($track);
			
 
            // Register Shipment
            $shipment->register();
	          /*  $comment = '';
            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
	                    isset($data['comment_customer_notify']),
	                    isset($data['is_visible_on_front'])
                );
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
	            }
	 
	            if (!empty($data['send_email'])) {
	                $shipment->setEmailSent(true);
	            }
	 
	            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
	            $responseAjax = new Varien_Object();
	            $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];
	 
	            if ($isNeedCreateLabel && $this->_createShippingLabel($shipment)) {
	                $responseAjax->setOk(true);
	            }*/
				//$this->_createShippingLabel($shipment);
 $customerEmailComments='';
            // Save the Shipment
            $this->_saveShipment($shipment, $order, $customerEmailComments);
					}
            catch (Exception $e) { echo 'Shipment creation failed on order '. $incrementid . ': ', $e->getMessage(); }
 
            // Finally, Save the Order
           // $this->_saveOrder($order);
					
					
					
					
					
					
					
					
					
					
					
					
			}
			
			
			
			
			
            curl_close($ch);
        }


            
            unset($spostalcode);
            unset($items);
            unset($city);
            unset($prov);
			unset($streetline);
            unset($country);
            unset($postal);



        unset($ch);
        unset($tmp);
        unset($xml);
		 	 
			 
            }
			
			
			}
            //END Handle Shipment
		}
        
		
        return $this;
		
		}
		
		/**
 * Get the Quantities shipped for the Order, based on an item-level
 * This method can also be modified, to have the Partial Shipment functionality in place
 *
 * @param $order Mage_Sales_Model_Order
 * @return array
 */
protected function _getItemQtys(Mage_Sales_Model_Order $order)
{
    $qty = array();
 
    foreach ($order->getAllItems() as $_eachItem) {
        if ($_eachItem->getParentItemId()) {
            $qty[$_eachItem->getParentItemId()] = $_eachItem->getQtyOrdered();
        } else {
            $qty[$_eachItem->getId()] = $_eachItem->getQtyOrdered();
        }
    }
 
    return $qty;
}
 
/**
 * Saves the Shipment changes in the Order
 *
 * @param $shipment Mage_Sales_Model_Order_Shipment
 * @param $order Mage_Sales_Model_Order
 * @param $customerEmailComments string
 */
protected function _saveShipment(Mage_Sales_Model_Order_Shipment $shipment, Mage_Sales_Model_Order $order, $customerEmailComments = '')
{
	$customerEmailComments='';
    $shipment->getOrder()->setIsInProcess(true);
    $transactionSave = Mage::getModel('core/resource_transaction')
                           ->addObject($shipment)
                           ->addObject($order)
                           ->save();
						   
		$ship_data = $shipment->getOrder()->getData();				   
 $customerEmail=$ship_data['customer_email'];
    $emailSentStatus = $shipment->getData('email_sent');
    if (!is_null($customerEmail)) {
        $shipment->sendEmail(true, $customerEmailComments);
        $shipment->setEmailSent(true);
    }
 
    return $this;
}




 
/**
 * Saves the Order, to complete the full life-cycle of the Order
 * Order status will now show as Complete
 *
 * @param $order Mage_Sales_Model_Order
 */
protected function _saveOrder(Mage_Sales_Model_Order $order)
{
    $order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
    $order->setData('status', Mage_Sales_Model_Order::STATE_COMPLETE);
 
    $order->save();
 
    return $this;
}
		
} 