<?php


$installer = $this;
$installer->startSetup();
$eav = new Mage_Eav_Model_Entity_Setup('sales_setup');
$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
	$cr = Mage::getSingleton('core/resource')
         ->getConnection('core_read');



$table = Mage::getSingleton('core/resource')->getTablename('fraktjakt_shipment_dimensions');

$sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`shipment_id` INT( 10 ) NOT NULL ,
`weight` decimal( 10,5 ) NOT NULL ,
`length` decimal( 10,5 ) NOT NULL ,
`width` decimal( 10,5 ) NOT NULL ,
`height` decimal( 10,5 ) NOT NULL 
) ENGINE = MYISAM ;";

$row = $cw->query($sql);

$sql = "alter table `{$table}` add index(shipment_id)";

$row = $cw->query($sql);


$installer->endSetup();