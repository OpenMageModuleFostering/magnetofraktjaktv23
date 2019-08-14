<?php

class Fraktjakt_Fraktjaktmodule_Model_Carrier_Shippingmethod extends Mage_Shipping_Model_Carrier_Abstract
{

    protected $_code = 'fraktjakt_fraktjaktmodule';
    protected $_totalfinalp = 0;
	
	

    public function _help()
    {
        return Mage::helper('fraktjakt_fraktjaktmodule');
    }

    public function getConfig($key)
    {
        return Mage::getStoreConfig('carriers/'.$this->_code.'/'.$key);
    }

    // public function setStore($st)
    // {
    // $this->store = $st;
    // }

    public function getStoreLocale()
    {
        return Mage::app()->getLocale()->getLocaleCode();
    }

    public function returnSwedish()
    {
        return ($this->getconfig('returnlang') == 1 ||
            ($this->getconfig('returnlangstore') == 1 &&  stristr($this->getStoreLocale(),'sv_')));
    }

    public function log($data, $force =false)
    {
        if($force || $this->getConfig('test_mode') || $this->getConfig('debug'))
        {
            mage::log($data);
        }
    }

    private function getReadResource()
    {
        return Mage::getSingleton('core/resource')
            ->getConnection('core_read');
    }

    private function getWriteResource()
    {
        return Mage::getSingleton('core/resource')
            ->getConnection('core_write');
    }

    private function getSession()
    {
        return Mage::getSingleton('core/session');
    }

    private function getSessionId()
    {
        return $this->getSession()->getSessionId();
    }

    private function getCacheTable()
    {
        return 'fraktjakt_cache';
    }

    private function clearCache()
    {
        // really we only need to do this a few times a day for maintenance. It should be done in a cron script I suppose
        if(((int)rand()) %3)
        {
            $sql = " delete from ".$this->getCacheTable()." where to_days(created) != to_days(now()) ";
            $this->log($sql);
            $this->getWriteResource()->query($sql);
        }
    }

    private function addCache($itemshash, $response, $response_id)
    {
        if($this->getConfig('usecache'))
        {
            $session_id = $this->getSessionId();
            //$response = str_replace("'",'\\\'',serialize($response));
            $response = serialize($response);
            $sql = " insert into ".$this->getCacheTable()." set
				`session_id` = '{$session_id}',
				`request_hash` = '{$itemshash}', 
				`response` = ?, 
				`response_id` = '{$response_id}'
			";
            $this->log($sql.$response);
            $this->getWriteResource()->query($sql,$response);
        }
    }

    private function checkCache($itemshash)
    {
        if($this->getConfig('usecache'))
        {
            $session = $this->getSessionId();
            $this->clearCache();
            $sql = " select * from ".$this->getCacheTable()." where session_id = '{$session}' and request_hash = '{$itemshash}' order by created desc limit 1";
            //	$this->log($sql);
            $ret = $this->getReadResource()->fetchRow($sql);
            $this->log("we have cacheS!|".substr(print_r($ret['response'],1),0,85)."|");
            if(isset($ret['response']) && strlen($ret['response']))
            {
                return unserialize($ret['response']);
            }
        }
        return false;
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
      
	  
	    $error = false;
        // skip if not enabled
        if (!$this->getConfig('active'))
        {
            return false;
        }

        // weight
//        $request->getPackageWeight()

        if($this->getConfig('min_shipping_weight') > 0
            && ( $request->getPackageWeight() < $this->getConfig('min_shipping_weight')*2.204)
        )
        {
            $error = $this->_help()->__("Shipment is Under Weight for this method.");
            $this->log("under weight");
            if(!$this->getConfig('showmethod'))
            {
                return false;
            }
        }

        if($this->getConfig('max_shipping_weight') > 0
            && ( $request->getPackageWeight() > $this->getConfig('max_shipping_weight')*2.204)
        )
        {
            $error = $this->_help()->__("Shipment is Over Weight for this method.");
            $this->log("under weight");
            if(!$this->getConfig('showmethod'))
            {
                return false;
            }
        }

     if($this->getConfig('min_shipping_value') > 0
            && ( $request->getPackageValue() < $this->getConfig('min_shipping_value'))
        )
        {
            $error = $this->_help()->__("Shipment is Under Value for this method.");
            $this->log("under value");
            if(!$this->getConfig('showmethod'))
            {
                return false;
            }
        }

        if($this->getConfig('max_shipping_value') > 0
            && ( $request->getPackageValue() > $this->getConfig('max_shipping_value'))
        )
        {
            $error = $this->_help()->__("Shipment is Over Value for this method.");
            $this->log("over value");
            if(!$this->getConfig('showmethod'))
            {
                return false;
            }
        }

        $result = Mage::getModel('shipping/rate_result');

        $this->log("what is error ". print_r($error,1));
        if($error !== false)
        {
            $this->log("we has error");
            $emsg = $error;
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            //$error->setErrorMessage($errorTitle);
            $error->setErrorMessage($emsg);
            $result->append($error);
            return $result;
        }

        $items_xml = $this->_getItems($request);
        $xml_bits  = $this->_makeXML($request, $items_xml);


        if (!$xml_bits)
        {
            $this->log("failed items");
            $error = $this->_help()->__("Failed to get Item Query");
            if(!$this->getConfig('showmethod'))
            {
                return false;
            }
        }
        
        $items_hash = md5($items_xml);
        $isCache = false;
        $this->log("Can Post XML Request".$xml_bits);
        $testcache = $this->checkCache($items_hash);

        if(!$error && $this->getConfig('usecache') && $testcache !== false)
        {
			
			
            $this->log("Using Cache");
            $xml_result = $testcache;
            $isCache = true;
            unset($testcache);
        }
        else
        {
            $xml_result = $this->_postBits($xml_bits);
            $this->log("LIVE QUERY");
        }
		//print_r($xml_result);
		//$xml_result = $this->_postBits($xml_bits);
       try{
		    $this->_store_response($xml_result);
        $this->log("Can Post XML Request".print_r($xml_result,1));
		
        $xml_results = @simplexml_load_string($xml_result);
echo $xml_results;
if($xml_results){

        $expath = $xml_results->xpath('code');
//print_r($expath);
//echo $expath[0];
//exit;
        if(is_array($expath) && isset($expath[0]) && $expath[0] == '2')
        {
            $xpath = $xml_results->xpath('error_message');
            $error1 = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '0';
            if($error1 == ''  || $error1 == 0)
            {

                $error1 = $this->_help()->__("There was an unknown problem with Fraktjakt_Fraktjaktmodule");
                $errtmp = $this->getConfig('specificerrmsg');
                if(isset($errtmp))
                {
                    $error1 = $errtmp;

                }

            }

            // email error bits here


            if ($this->getConfig('erroremail') == 1) {
                $message = $this->_help()->__(" Fraktjakt_Fraktjaktmodule failed quoting\n\n %s" , print_r($xml_results,1) . "\n\n " . print_r($items_xml,1));
                $to  = Mage::getStoreConfig('trans_email/ident_support/email');
                $subject = $this->_help()->__('Fraktjakt_Fraktjaktmodule Quote Failure');
                $headers = 'From: ' .$to. "\r\n" .
                    'Reply-To: ' . $to . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();

                mail($to, $subject, $message, $headers);
            }

            $this->log('Unknown Error with Fraktjakt_Fraktjaktmodule: '.$error1, true);
            if(strlen($this->getConfig('failover_ratetitle')) && $this->getConfig('failover_rate') > 0)
            {
                $this->log('Using FAILOVER');
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->getConfig('title'));
                $method->setMethod('Regular');
                $method->setMethodTitle($this->getConfig('failover_ratetitle'));
                $method->setCost($this->getConfig('failover_rate'));
                $method->setPrice($this->getConfig('failover_rate'));
                $result->append($method);
                return $result;
                return $error;
            }
            $this->log('Returning Error');


            $error = Mage::getModel('shipping/rate_result_error');


            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            //$error->setErrorMessage($errorTitle);
            $error->setErrorMessage($error1);
            $result->append($error);
            return $result;
            return $error;

        }

        if($error !== false)
        {
            $this->log(__LINE__ . ' we have Returning Error');
            return $error;
        }

        unset($items_xml);
        unset($xml_bits);

        $xpath = $xml_results->xpath('id');
    $requestID = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '0';
	   
        if(!$isCache)
        {
            $this->addCache($items_hash, $xml_result, $requestID);
        }
        unset($xml_result);
        $xpath = $xml_results->xpath('tax_class');
		//$tax_class = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
		//echo "<pre>";
		//print_r($xml_result);
		//exit;
        $handling = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '0';
        $allowedMethods = explode(",", strtolower($this->getConfigData('allowed_methods')));
        mage::log(__CLASS__ . __FUNCTION__ . " all rates returned by Fraktjakt_Fraktjaktmodule " . print_r($xml_results,1));
		//print_r($xml_results->shipping_products);
		//exit;
        foreach($xml_results->shipping_products->shipping_product as $prod)
        {
			
			
			//echo "<pre>";
			//print_r($prod);
			
            $xpath = $prod->xpath('description');
          $name = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
		   $xpath = $prod->xpath('agent_link');
          $agent_link = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
		   //exit;
           /* if(!in_array(strtolower($name) ,$allowedMethods))
            {
                continue;
            }*/
			 //continue;
            $xpath = $prod->xpath('arrival_time');
            $delivery = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
			 $xpath = $prod->xpath('agent_info');
            $agent_info = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
            $xpath = $prod->xpath('price');
            $rate = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
			$xpath = $prod->xpath('tax_class');
            $tax_class = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';
			//$rate = $tax_class;
			$rate =  $rate+(($tax_class*$rate)/100);
			$xpath = $prod->xpath('id');
            $shippingid = ($xpath !== FALSE && isset($xpath[0])) ? (string)$xpath[0] : '';

            $method = Mage::getModel('shipping/rate_result_method');
			
			//print_r( $method);
            $method->setCarrier($this->_code);
            $method->setCarrierTitle(Mage::getStoreConfig('carriers/'.$this->_code.'/title'));
            $method->setMethod($name);
            $est_text = 'Est. Delivery';

            if($this->returnSwedish())
            {
                $est_text = 'Est. Leverans';
            }
			if(empty($delivery))
			{
				$delivery = "?";
			}
			if(empty($agent_info))
			{
				$agent_info = "Home delivery";
			}
            $method->setMethodTitle( $name);
            $method->setMethodDescription( $name."@".$delivery."@".$agent_info."@".$requestID."@".$shippingid."@".$agent_link);
            $method->setCost($rate);
            $method->setPrice($rate);
            $result->append($method);

        }
		return $result;
}
else
{
	if(strlen($this->getConfig('failover_ratetitle')) && $this->getConfig('failover_rate') > 0)
            {
                $this->log('Using FAILOVER');
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->getConfig('title'));
                $method->setMethod('Regular');
                $method->setMethodTitle($this->getConfig('failover_ratetitle'));
				$method->setMethodDescription($this->getConfig('failover_ratetitle'));
                $method->setCost($this->getConfig('failover_rate'));
                $method->setPrice($this->getConfig('failover_rate'));
                $result->append($method);
                return $result;
               
            }
}
		}
		catch (Exception $e) {
//print_r($result);
//exit;
		
	if(strlen($this->getConfig('failover_ratetitle')) && $this->getConfig('failover_rate') > 0)
            {
                $this->log('Using FAILOVER');
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->getConfig('title'));
                $method->setMethod('Regular');
                $method->setMethodTitle($this->getConfig('failover_ratetitle'));
                $method->setCost($this->getConfig('failover_rate'));
                $method->setPrice($this->getConfig('failover_rate'));
                $result->append($method);
                return $result;
               
            }
		}
        
    }


    public function _store_response($xml_result)
    {
        $quote_id = Mage::getSingleton('checkout/session')->getQuoteId();
        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $field = false;
        $value = false;
        $cw = Mage::getSingleton('core/resource')
            ->getConnection('core_write');
        $cr = Mage::getSingleton('core/resource')
            ->getConnection('core_read');

        $table = Mage::getSingleton('core/resource')->getTablename('fraktjakt_packing');

        $this->log("current quote id ".Mage::getSingleton('checkout/session')->getQuoteId());
        $this->log("current order id ".Mage::getSingleton('checkout/session')->getLastRealOrderId());
        // figure out if this will work to get the order id
        // what happens on mini checkout?
        // then how do we get it into the shipment creation
        if($order_id)
        {
            // updateing / inserting with order id
            $field = "order_id";
            $value = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            //$clear_query = "delete from {$table}  where $field <
        }
        elseif($quote_id)
        {
            $field = "quote_id";
            $value = Mage::getSingleton('checkout/session')->getQuoteId();
        }

        if($value == false)
        {
            $test = Mage::getSingleton('adminhtml/session_quote')->getQuoteId();
            if($test)
            {
                $field = "quote_id";
                $quote_id = $value = $test;
                //mage::log("Setting from admin Quoote " . $value);
            }
        }
        if($value == false || $value == '')
        {
            //mage::log(__CLASS__ . __LINE__ . "  " . $field . "exiting as we dont have value  " . $value);
            return;
        }

        $test = "select * from {$table} where {$field} = $value order by id desc limit 1";
        $row = $cr->fetchRow($test);
        $additional = '';
        if($order_id && $quote_id)
        {
            $additional = ", quote_id = {$quote_id}";
        }
        if(isset($row['id']))
        {
            $sql = "update {$table} set response_data = ? {$additional} where  {$field} = $value";
        }
        else
        {
            $sql = "insert into {$table} set response_data = ?, quote_id = '{$quote_id}', order_id = '{$order_id}'";
        }
        //mage::log(__CLASS__ . " saving ". $sql);
		//echo $xml_result;
		//echo $sql ;
		//exit;
		
        $cw->query($sql,$xml_result);
    }

    public function _postBits($xml)
    {
       
	     if($this->getConfig('test_mode') == 1)
        {
             $url = "http://api2.fraktjakt.se/fraktjakt/query_xml";
        }
		else
		{
			 $url = "http://api1.fraktjakt.se/fraktjakt/query_xml";
		}
	   $httpHeaders = array(
      // BOF: PHP bug #47906 circumvention (http://bugs.php.net/bug.php?id=47906)
        "Expect: ", // Disable the 100-continue header
      // EOF: PHP bug #47906 circumvention
        "Accept-Charset: ISO 8859-1",
        "Content-type: application/x-www-form-urlencoded"
      );
	   $httpPostParams = array(
          'md5_checksum' => md5($xml),
          'xml' => utf8_encode($xml)
        );
		
		 if (is_array($httpPostParams)) {
          foreach ($httpPostParams as $key => $value) {
            $httpPostParams[$key] = $key .'='. urlencode($value);
          }
          $httpPostParams = implode('&', $httpPostParams); 
        }
	  
	  
	  
	   /*echo "<pre>";
	   print_r($xml);*/
      $ch = curl_init();
       /* 
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,urlencode($xml));
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_PORT,30000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $qresult = curl_exec($ch);*/
		curl_setopt($ch, CURLOPT_FAILONERROR, 0); // fail on errors
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1); // forces a non-cached connection
        if ($httpHeaders) curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders); // set http headers
          curl_setopt($ch, CURLOPT_POST, 1); // initialize post method
          curl_setopt($ch, CURLOPT_POSTFIELDS,urlencode($xml));
        curl_setopt($ch, CURLOPT_URL,$url);
		 curl_setopt($ch, CURLOPT_POSTFIELDS, $httpPostParams); // variables to post
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
          curl_setopt($ch, CURLOPT_TIMEOUT, 30); // timeout after 30s
          $qresult = curl_exec($ch);
		
		
		/*curl_error($ch);
		print_r($qresult);
		exit;*/
        if (curl_errno($ch))
        {
            $tmp = $qresult;
            $qresult =  "<code>2</code>\n";
            $qresult =  "<error_message>Error communicating with Fraktjakt_Fraktjaktmodule " .
                curl_error($ch)."</error_message>\n".$tmp;
        }
        else
        {
            curl_close($ch);
        }


        unset($ch);
        unset($tmp);
        unset($xml);
        return $qresult;
    }

    public function _makeXML(Mage_Shipping_Model_Rate_Request $request, $items)
    {
        //mage::log("--- items --- " . print_r($items));
        $consignorID = Mage::getStoreConfig('carriers/'.$this->_code.'/consignorID');
		$consignorkey = Mage::getStoreConfig('carriers/'.$this->_code.'/consignorkey');
        $store_currency = Mage::app()->getStore()-> getCurrentCurrencyCode();

        //added var checks + instantiations for cases like !request
        $issues = 0;
        $hasaddress = false;
        $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
        if ($customerAddressId){
            $mainAddress = Mage::getModel('customer/address')->load($customerAddressId);
            $hasaddres = true;
        }

        //city
        if ($request->getDestCity()) {
            $city = $request->getDestCity();
        } elseif ($hasaddress) {
            $city = $mainAddress->getCity();
        } else {
            $city = "";
            //$issues++;
        }

        //region code
        if ($request->getDestRegionCode()) {
            $prov = $request->getDestRegionCode();
        } elseif ($hasaddress) {
            $prov = $mainAddress->getRegionId();
        } else {
            $prov = "";
            //$issues++;
        }

       //street line
if ($request->getDestStreet()) {
            $streetline = $request->getDestStreet();
        } elseif ($hasaddress) {
            $streetline = $mainAddress->getStreet1();
			$streetline1 = $mainAddress->getStreet2();
        } else {
            $streetline = "";
			$streetline1 = "";
            //$issues++;
        }

    

        //country code
        if ($request->getDestCountryId()) {
            $destCountry = $request->getDestCountryId();
        } elseif ($hasaddress) {
            $destCountry = $mainAddress->getCountryId();
        } else {
            $destCountry = "";
            $issues++;
        }


        $country = Mage::getModel('directory/country')->load($destCountry)->getIso2Code();

        $postal = '';

        //postal code
        if (($request->getDestPostcode()) && ($request->getDestPostcode() != "-")) {
            $postal = $request->getDestPostcode();
        } elseif ($hasaddress) {
            $postal = $mainAddress->getPostcode();
        } else {
            $postal = "";
            $issues++;
        }



        if ($request->getOrigPostcode())
        {
            $spostalcode = $request->getOrigPostcode();
        }
        else
        {
            $spostalcode = Mage::getStoreConfig('shipping/origin/postcode', $this->getStore());
        }

        // later this can be switched based on customers selected language on site (EN || SV)
        $return_lang = 'EN';
        if($this->returnSwedish())
        {
            $return_lang = 'sv';
        }
        //mage::log($issues);
        if ($issues == 0) {
			

            $xml_bits = '<?xml version="1.0" encoding="iso-8859-1"?>';
			 $xml_bits.='
<shipment xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <value>'.sprintf("%01.2f", $this->_totalfinalp).'</value>
  ';
  $xml_bits.= "
  <consignor>
    <id>".$consignorID."</id>
    <key>".$consignorkey."</key>
    <currency>".$store_currency."</currency>
    <language>".$return_lang."</language>
	<encoding>ISO 8859-1</encoding>
  </consignor>
  <no_agents>0</no_agents>
  <agents_in>1</agents_in>
  <parcels>
  ".$items."
  </parcels>
  <address>
  <street_address_1>".$streetline."</street_address_1>
  <street_address_2>".$streetline1."</street_address_2>
  <postal_code>".$postal."</postal_code>
  <city_name>".$city."</city_name>
  <residential>1</residential>
  <country_code>".$country."</country_code>
  <country_subdivision_code>F</country_subdivision_code>
  </address>
  <referrer_code></referrer_code>
</shipment>
";



       /*echo  $xml_bits;
	   exit;*/
            unset($return_lang);
			unset($consignorID);
			unset($consignorkey);
            unset($spostalcode);
            unset($items);
            unset($city);
            unset($prov);
			unset($streetline);
			unset($streetline1);
            unset($country);
            unset($postal);

            return $xml_bits;
        } else {
            unset($return_lang);
          	unset($consignorID);
			unset($consignorkey);
            unset($spostalcode);
            unset($items);
            unset($city);
            unset($prov);
			unset($streetline);
			unset($streetline1);
            unset($country);
            unset($postal);

            return false;
        }

    }

    private function setDefaults()
    {

        $this->_weight_low = 1;
        if(is_numeric($this->getConfig('weight_low')))
        {
            $this->_weight_low = $this->getConfig('weight_low');
        }

        $this->_default_widthlow = 1;
        if(is_numeric($this->getConfig('default_widthlow')))
        {
            $this->_default_widthlow = $this->getConfig('default_widthlow');
        }

        $this->_default_widthhigh = 1;
        if(is_numeric($this->getConfig('default_widthhigh')))
        {
            $this->_default_widthhigh = $this->getConfig('default_widthhigh');
        }

        $this->_default_heightlow = 1;
        if(is_numeric($this->getConfig('default_heightlow')))
        {
            $this->_default_heightlow = $this->getConfig('default_heightlow');
        }

        $this->_default_heighthigh = 1;
        if(is_numeric($this->getConfig('default_heighthigh')))
        {
            $this->_default_heighthigh = $this->getConfig('default_heighthigh');
        }
        $this->_default_lengthlow = 1;

        if(is_numeric($this->getConfig('default_lengthlow')))
        {
            $this->_default_lengthlow = $this->getConfig('default_lengthlow');
        }

        $this->_default_lengthhigh = 1;
        if(is_numeric($this->getConfig('default_lengthhigh')))
        {
            $this->_default_lengthhigh = $this->getConfig('default_lengthhigh');
        }
    }

    public function _getItems(Mage_Shipping_Model_Rate_Request $request)
    {
        $this->_totalPrice = 0.0;
		$this->_package_width = 0;
	    $this->_package_height = 0;
	    $this->_package_length = 0;
	    $this->shipping_weight = 0;
        $this->_items_xml = "";
        $this->setDefaults();
        $freeBoxes = 0;

        $this->setFreeBoxes($freeBoxes);


        foreach( $request->getAllItems() as $item )
        {
            $free_shipping = false;
            $free_shipping_children = false;

            if ($item->getProduct()->isVirtual() || $item->getParentItem())
            {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately())
            {
                foreach ($item->getChildren() as $child)
                {
                    if ($child->getFreeShipping() && !$child->getProduct()->isVirtual())
                    {
                        continue;
                    }
                    // we add item to list

                }
            }
            elseif ($item->getFreeShipping())
            {
                continue;
            }
            $this->_items_xml .= $this->getItemXml($item);
			$this->_totalfinalp .= $this->getItemTotalPrice($item);

        }
        /*$data =  "
        <itemsPrice>".sprintf("%01.2f", $this->_totalPrice)."</itemsPrice>
        <lineItems>".$this->_items_xml." </lineItems>
";*/
	    $data =  $this->_items_xml;
        $this->_items_xml  = '';
        $this->_totalPrice  = 0;
        return $data;
    }

    private function getConvertedWeight($w,$u)
    {
        $weight = $w;
        switch($u)
        {
            case Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits::LB:
                $weight = round($w*0.4535,2);
                break;
            case Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits::GR:
                $weight = round($w*0.001,2);
                break;
            case Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits::OZ:
                $weight = round($w*0.028349,2);
                break;
            case Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits::KG:
            default:
                $weight = $w;
                break;
        }
        return $weight;
    }

    private function getConvertedMeasure($w,$u)
    {
        $unit = $w;
        switch($u)
        {
            case Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits::MM:
                $unit = round($w*0.1,0);
                break;
            case Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits::FT:
                $unit = round($w*30.48,0);
                break;
            case Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits::IN:
                $unit = round($w*2.54,0);
                break;
		    case Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits::M:
                $unit = round($w*100,0);
                break;
			case Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits::KM:
                $unit = round($w*100000,0);
                break;	
            case Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits::CM:
            default:
                $unit = $w;
                break;
        }
        return $unit;
    }
	
	
	  private function getItemTotalPrice($item)
    {
		 $qty =  (int)$item->getQty() ? (int)$item->getQty() : 1;
        $product = Mage::getModel('catalog/product')->load( $item->getProductId() );



        //$this->_totalPrice += $product->getFinalPrice() * $qty;
        if($this->getConfig('product_cost') && $product->getCost() > 0)
        {
            $this->_totalPrice = $product->getCost() * $qty;
        }
        else
        {
            $this->log("p p " . $product->getFinalPrice() . " and i P  ". $product->getPrice());
            $this->_totalPrice = $product->getFinalPrice() * $qty;
        }
		
		return $this->_totalPrice;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	

    private function getItemXml($item)
    {
        // Get quanity for each Item and multiply by volume
        $qty =  (int)$item->getQty() ? (int)$item->getQty() : 1;
        $product = Mage::getModel('catalog/product')->load( $item->getProductId() );



        //$this->_totalPrice += $product->getFinalPrice() * $qty;
        if($this->getConfig('product_cost') && $product->getCost() > 0)
        {
            $this->_totalPrice += $product->getCost() * $qty;
        }
        else
        {
            $this->log("p p " . $product->getFinalPrice() . " and i P  ". $product->getPrice());
            $this->_totalPrice += $product->getFinalPrice() * $qty;
        }

        $height = $this->getConvertedMeasure($product->getHeight(), $product->getDimensionUnits());
        //$weight = $this->getConvertedWeight($product->getWeight(),$product->getWeightUnits())*$qty;
        $weight = $this->getConvertedWeight($product->getWeight(),$product->getWeightMeasure());
        $width =  $this->getConvertedMeasure($product->getWidth(), $product->getDimensionUnits());
        $length = $this->getConvertedMeasure($product->getLength(), $product->getDimensionUnits());
        $title = substr($product->getSku().';'.preg_replace('/[^a-z0-9\s\'\.\_]/i','',substr($product->getName(),0,32)." ..."),0,32);
        $Readytoship = $product->getReadytoship();

        if(intval($length) < 1 || intval($width) < 1 || intval($height) < 1 )
        {
            $length = $width = $height = 1;
        }

        $Readytoship = "";
        if($product->getReadytoship() != 1)
        {
            $Readytoship = '';
			
        }

        if(ceil($weight) <= 0.0000)
        {
            $weight = .7; //default to 7.7 kg
        }

        if($height < 1 || !is_numeric($height))
        {
            $height = $this->_default_heightlow; // just a low default
            if($weight >= $this->_weight_low) // less than 1k no height  (default 2)
            {
                $height = $this->_default_heighthigh;
            }

        }

        if($width < 1 || !is_numeric($width))
        {
            $width = $this->_default_widthlow; // just a low default
            if($weight >= $this->_weight_low)
            {			   // less than 1k no height  (default 2)
                $width = $this->_default_widthhigh;
            }
        }

        // Create default value for length should value be missing
        if($length < 1 || !is_numeric($length))
        {
            $length = $this->_default_lengthlow; // just a low default
            if($weight >= $this->_weight_low) // less than 1k no height  (default 2)
            {
                $length = $this->_default_lengthhigh;
            }
        }
$aweight = $weight;
/*$aheight = $height*$qty;*/

if($product->getReadytoship() != 1)
        {
           
		$this->_package_height += $height;  
		if($width > $this->_package_width) $this->_package_width = $width;  
		if($length > $this->_package_length) $this->_package_length = $length;
		$this->shipping_weight += $aweight;  
		    
	

			
			 $items_xml = "\n
        <parcel>
  <weight>{$this->shipping_weight}</weight>
  <length>{$this->_package_length}</length>
  <width>{$this->_package_width}</width>
  <height>{$this->_package_height}</height>
</parcel>
";
			
        }
		else
		{
			 $items_xml = "\n
        <parcel>
  <weight>{$aweight}</weight>
  <length>{$length}</length>
  <width>{$width}</width>
  <height>{$height}</height>
</parcel>
";
		}


       
        return $items_xml;
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $info = array();

        $result = $this->getTracking($tracking);

        if($result instanceof Mage_Shipping_Model_Tracking_Result)
        {
            if ($trackings = $result->getAllTrackings())
            {
                return $trackings[0];
            }
        }
        elseif (is_string($result) && !empty($result))
        {
            return $result;
        }

        return false;
    }

    public function getTracking($trackings)
    {
        if (!is_array($trackings))
        {
            $trackings = array($trackings);
        }
        return $this->_getCgiTracking($trackings);
		
		
		
		 /*$this->setXMLAccessRequest();
         $this->_getXmlTracking($trackings);*/
		
    }
	
	
	
	
	

    protected function _getCgiTracking($trackings)
    {
        $result = Mage::getModel('shipping/tracking_result');
        $defaults = $this->getDefaults();
        foreach($trackings as $tracking)
        {
            $status = Mage::getModel('shipping/tracking_result_status');
            $status->setCarrier('fraktjakt_fraktjaktmodule');
            $status->setCarrierTitle($this->getConfigData('title'));
            $status->setTracking($tracking);
            $status->setPopup(1);
            $status->setUrl("http://www.fraktjakt.se/trace/list_shipment/$tracking");
            $result->append($status);
        }


        $this->_result = $result;
        return $result;
    }



   
 


    public function getMethod()
    {
        $arr = array("Schenker Privpak AB - Privatpaket",
            "Bussgods - Bussbox 5 kg (emballage ingår)",
            "Bussgods - Privat",
            "Schenker AB - DB SCHENKERprivpak, Ombud - Standard",
            "Schenker AB - DB SCHENKERprivpak, Till jobbet (Leverans till privatpersonsarbetsplats) (inkl utkörning)",
            "Schenker AB - DB SCHENKERprivpak, Hem - Dag utan avisering och kvittens (inklutkörning)",
			"Bussgods - Bussgods Prio",
            "Schenker AB - DB SCHENKERprivpak, Hem- Dag med avisering (inkl utkörning)",
            "Schenker AB - DB SCHENKERprivpak, Hem- kväll med avisering (inkl utkörning)"
        );
        return $arr;
    }

    public function getServiceMap()
    {
        $arr = array(
            30 => "Schenker Privpak AB - Privatpaket",
            92 => "Bussgods - Bussbox 5 kg (emballage ingår)",
            25 => "Bussgods - Privat",
            84 => "Schenker AB - DB SCHENKERprivpak, Ombud - Standard",
            88 => "Schenker AB - DB SCHENKERprivpak, Till jobbet (Leverans till privatpersonsarbetsplats) (inkl utkörning)",
            85 => "Schenker AB - DB SCHENKERprivpak, Hem - Dag utan avisering och kvittens (inklutkörning)",
			100 => "Bussgods - Bussgods Prio",
            86 => "Schenker AB - DB SCHENKERprivpak, Hem- Dag med avisering (inkl utkörning)",
            87 => "Schenker AB - DB SCHENKERprivpak, Hem- kväll med avisering (inkl utkörning)"
        );
        return $arr;
    }
} 