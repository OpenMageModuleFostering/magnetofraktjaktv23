<?php


$installer = $this;
$installer->startSetup();
$eav = new Mage_Eav_Model_Entity_Setup('sales_setup');
$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
	$cr = Mage::getSingleton('core/resource')
         ->getConnection('core_read');


// Get  entity model id 'sales/order'
$sql = 'SELECT entity_type_id FROM '.$this->getTable('eav_entity_type').' WHERE entity_type_code="catalog_product"';
$row = $cr->fetchRow($sql);

	$attribute_model        = Mage::getModel('eav/entity_attribute');
	$pattributes = array();
	


	
mage::log(__FILE__ . __LINE__ );
	$attribute_code = 'dimension_units';
	$attribute_id = $attribute_model->getIdByCode('catalog_product', $attribute_code);
	
	if(!isset($attribute_id) || !is_numeric($attribute_id) || $attribute_id == 0)
	{
			//mage::log(__CLASS__ ." osdfdi " . Fraktjakt_Fraktjaktmodule_Model_Source_Weightunits::KG);
		// Create EAV-attribute for the order comment.
		$c = array (
		  'entity_type_id'  => $row['entity_type_id'],
		  'attribute_code'  => $attribute_code,
		  'backend_type'    => 'varchar',     // MySQL-Datatype
		  'source_model'    => 'Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits',
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

		  'frontend_label'  => 'Dimension Units',
		  'default_value'	=> 'cm',
		  'note'  => 'Select the appropriate unit of measure',
		);
		$attribute = new Mage_Eav_Model_Entity_Attribute();
		$attribute->loadByCode($c['entity_type_id'], $c['attribute_code'])
				  ->setStoreId(0)
				  ->addData($c);
		$attribute->save();
			$eav->updateAttribute('catalog_product', $attribute_code, 'source_model', 'Fraktjakt_Fraktjaktmodule_Model_Source_Dimentionunits');
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
	
mage::log(__FILE__ . __LINE__ );
	  Mage::app('default');
mage::log(__FILE__ . __LINE__ );
	$attrib_model_setup = Mage::getModel('eav/entity_setup', 'eav_setup');
	$entityTypeId = $row['entity_type_id'];
	$attr_group = 'General';
	mage::log(__FILE__ . __LINE__ );
	
   $sets = $cr->fetchAll('select * from '.$this->getTable('eav/attribute_set').' where entity_type_id=?', $row['entity_type_id']);
		foreach($sets as $set)
		{	
			foreach($pattributes  as $attributeCode)
			{
				$attrib_model_setup->addAttributeToSet($entityTypeId, $set['attribute_set_id'], $attr_group, $attributeCode, $sortOrder);
			}
		}
		
	mage::log(__FILE__ . __LINE__ );	
		
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
