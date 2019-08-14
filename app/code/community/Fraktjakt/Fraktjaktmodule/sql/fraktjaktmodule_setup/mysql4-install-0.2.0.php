<?php


$installer = $this;
$installer->startSetup();
$eav = new Mage_Eav_Model_Entity_Setup('sales_setup');
$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
	$cr = Mage::getSingleton('core/resource')
         ->getConnection('core_read');
		 
 $sql = ' CREATE TABLE IF NOT EXISTS `fraktjakt_cache` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` varchar(255) NOT NULL,
  `request_hash` varchar(255) NOT NULL,
  `response` text NOT NULL,
  `response_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
   KEY `session_id` (`session_id`),
  KEY `response_id` (`response_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';



$row = $cw->query($sql);

$sql = '  CREATE TABLE IF NOT EXISTS `fraktjakt_packing` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`order_id` INT( 10 ) NOT NULL ,
`response_data` text NOT NULL ,
`shipping_products` text NOT NULL ,
UNIQUE (
`order_id`
)
) ENGINE = MYISAM ;';


$row = $cw->query($sql);


// Get  entity model id 'sales/order'
$sql = 'SELECT entity_type_id FROM '.$this->getTable('eav_entity_type').' WHERE entity_type_code="catalog_product"';
$row = $cr->fetchRow($sql);

	$attribute_model        = Mage::getModel('eav/entity_attribute');
	$pattributes = array();
	$attribute_code = 'length';
	$attribute_id = $attribute_model->getIdByCode('catalog_product', $attribute_code);
	
	if(!isset($attribute_id) || !is_numeric($attribute_id) || $attribute_id == 0)
	{
		// Create EAV-attribute for the order comment.
		$c = array (
		  'entity_type_id'  => $row['entity_type_id'],
		  'attribute_code'  => $attribute_code,
		  'backend_type'    => 'decimal',     // MySQL-Datatype
		  'frontend_input'  => 'text', // Type of the HTML form element
		  'is_global'       => '1',
		  'is_visible'      => '1',
		  'is_filterable'   => '0',
		  'apply_to'		=> 'simple,configurable',
		  'is_visible_on_front'		=> 0,
		  'is_comparable'   => '0',
		  'is_searchable'   => '0',
		  'is_required'     => '0',
		  'is_user_defined' => '0',
		  'frontend_label'  => 'Length',
		  'note'  => 'Length of shippable product',
		);
		$attribute = new Mage_Eav_Model_Entity_Attribute();
		$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
				  ->setStoreId(0)
				  ->addData($c);
		$attribute->save();
		$pattributes[] = $c['attribute_code'];
	}
	
	$attribute_code = 'width';
	$attribute_id = $attribute_model->getIdByCode('catalog_product', $attribute_code);
	
	if(!isset($attribute_id) || !is_numeric($attribute_id) || $attribute_id == 0)
	{
		// Create EAV-attribute for the order comment.
		$c = array (
		  'entity_type_id'  => $row['entity_type_id'],
		  'attribute_code'  => $attribute_code,
		  'backend_type'    => 'decimal',     // MySQL-Datatype
		  'frontend_input'  => 'text', // Type of the HTML form element
		  'is_global'       => '1',
		  'is_visible'      => '1',
		  'is_filterable'   => '0',
		  'apply_to'		=> 'simple,configurable',
		  'is_visible_on_front'		=> 0,
		  'is_comparable'   => '0',
		  'is_searchable'   => '0',
		  'is_required'     => '0',
		  'is_user_defined' => '0',

		  'frontend_label'  => 'Width',
		  'note'  => 'Width of shippable product',
		);
		$attribute = new Mage_Eav_Model_Entity_Attribute();
		$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
				  ->setStoreId(0)
				  ->addData($c);
		$attribute->save();
		$pattributes[] = $c['attribute_code'];
	}

	
	$attribute_code = 'height';
	$attribute_id = $attribute_model->getIdByCode('catalog_product', $attribute_code);
	
	if(!isset($attribute_id) || !is_numeric($attribute_id) || $attribute_id == 0)
	{
		// Create EAV-attribute for the order comment.
		$c = array (
		  'entity_type_id'  => $row['entity_type_id'],
		  'attribute_code'  => $attribute_code,
		  'backend_type'    => 'decimal',     // MySQL-Datatype
		  'frontend_input'  => 'text', // Type of the HTML form element
		  'is_global'       => '1',
		  'is_visible'      => '1',
		  'is_filterable'   => '0',
		  'apply_to'		=> 'simple,configurable',
		  'is_visible_on_front'		=> 0,
		  'is_comparable'   => '0',
		  'is_searchable'   => '0',
		  'is_required'     => '0',
		  'is_user_defined' => '0',

		  'frontend_label'  => 'Height',
		  'note'  => 'Height of shippable product',
		);
		$attribute = new Mage_Eav_Model_Entity_Attribute();
		$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
				  ->setStoreId(0)
				  ->addData($c);
		$attribute->save();
		$pattributes[] = $c['attribute_code'];
	}

	$attribute_code = 'weight_measure';
	$attribute_id = $attribute_model->getIdByCode('catalog_product', $attribute_code);
	
	if(!isset($attribute_id) || !is_numeric($attribute_id) || $attribute_id == 0)
	{
		//	mage::log(__CLASS__ ." osdfdi " . Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits::KG);
		// Create EAV-attribute for the order comment.
		$c = array (
		  'entity_type_id'  => $row['entity_type_id'],
		  'attribute_code'  => $attribute_code,
		  'backend_type'    => 'varchar',     // MySQL-Datatype
		  'source_model'    => 'Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits',
		  'frontend_input'  => 'select', // Type of the HTML form element
		  		  'backend_table'	=> '',
		  'frontend_model'	=> '',
		  'is_global'       => '1',
		  'is_visible'      => '1',
		  'is_filterable'   => '0',
		  'apply_to'		=> 'simple,configurable',
		  'is_visible_on_front'		=> '0',
		  'is_comparable'   => '0',
		  'is_searchable'   => '0',
		  'is_required'     => '0',
		  'is_user_defined' => '0',

		  'frontend_label'  => 'Weight Units',
		  'default_value'	=> 'kg',
		  'note'  => 'Select the appropriate unit of measure',
		);
		$attribute = new Mage_Eav_Model_Entity_Attribute();
		$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
				  ->setStoreId(0)
				  ->addData($c);
		$attribute->save();
			$eav->updateAttribute('catalog_product', $attribute_code, 'source_model', 'Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits');
		$pattributes[] = $c['attribute_code'];
	}
	$attribute_code = 'readytoship';
	$attribute_id = $attribute_model->getIdByCode('catalog_product', $attribute_code);
	
	if(!isset($attribute_id) || !is_numeric($attribute_id) || $attribute_id == 0)
	{
		// Create EAV-attribute for the order comment.
		$c = array (
		  'entity_type_id'  => $row['entity_type_id'],
		  'attribute_code'  => $attribute_code,
		  'backend_type'    => 'int',     // MySQL-Datatype
		  'source_model'    => 'eav/entity_attribute_source_boolean',
		  'backend_table'	=> '',
		  'frontend_model'	=> '',
		  'frontend_input'  => 'select', // Type of the HTML form element
		  'is_global'       => '1',
		  'is_visible'      => '1',
		  'is_filterable'   => '0',
		  'apply_to'		=> 'simple,configurable',
		  'is_visible_on_front'		=> '0',
		  'is_comparable'   => '0',
		  'is_searchable'   => '0',
		  'is_required'     => '0',
		  'is_user_defined' => '0',

		  'frontend_label'  => 'Item is Ready to Ship as is',
	//	  'default_value'	=> Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits::KG,
		  'note'  => 'Does this item need further packaging? (*Or is it ready to ship in its current box?)',
		);
		$attribute = new Mage_Eav_Model_Entity_Attribute();
		$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
				  ->setStoreId(0)
				  ->addData($c);
		$attribute->save();
		
		$eav->updateAttribute('catalog_product', $attribute_code, 'source_model', 'eav/entity_attribute_source_boolean');
		$pattributes[] = $c['attribute_code'];
	}
	

	  Mage::app('default');

//	$attrib_model_setup = Mage::getModel('eav/entity_setup');
	$attrib_model_setup = Mage::getModel('eav/entity_setup', 'eav_setup');
	$entityTypeId = $row['entity_type_id'];
	$attr_group = 'General';
	
	
   $sets = $cr->fetchAll('select * from '.$this->getTable('eav/attribute_set').' where entity_type_id=?', $row['entity_type_id']);
		foreach($sets as $set)
		{	
			foreach($pattributes  as $attributeCode)
			{
				$attrib_model_setup->addAttributeToSet($entityTypeId, $set['attribute_set_id'], $attr_group, $attributeCode, $sortOrder);
			}
		}
		
		
		
	// if they had the free extension installed
	// lets copy the settings.
	if(Mage::getStoreConfig('carriers/fraktjakt_fraktjaktmodule/active') || strlen(Mage::getStoreConfig('carriers/fraktjakt_fraktjaktmodule/title')))
	{
		$table = $this->getTable('core/config_data');
		$config = $cr->fetchAll('select * from '.$table.' where path like \'%carriers/fraktjakt_fraktjaktmodule%\' ');
		foreach($config as $piece)
		{
			$value = $piece['value'];
			$config_id = 'NULL';
			$scope = $piece['scope'];
			$scope_id = $piece['scope_id'];
			$path = $piece['path'];
			if($path == 'carriers/fraktjakt_fraktjaktmodule/active')
			{
				$value = 0;
			}
			$sql = "insert into {$table} values (NULL, '{$scope}', '{$scope_id}', '{$path}', ?)";
			$cw->query($sql,$value);
		}
	}
$installer->endSetup();