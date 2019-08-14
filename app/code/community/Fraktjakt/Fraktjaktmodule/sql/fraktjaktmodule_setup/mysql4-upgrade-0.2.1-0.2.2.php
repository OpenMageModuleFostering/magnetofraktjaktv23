<?php


$installer = $this;
$installer->startSetup();
$eav = new Mage_Eav_Model_Entity_Setup('sales_setup');
$cw = Mage::getSingleton('core/resource')
         ->getConnection('core_write');
	$cr = Mage::getSingleton('core/resource')
         ->getConnection('core_read');



$sql = '  drop table if exists fraktjakt_packing';
$table = Mage::getSingleton('core/resource')->getTablename('fraktjakt_packing');
$row = $cw->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`order_id` INT( 10 ) NOT NULL ,
`quote_id` INT( 10 ) NOT NULL ,
`response_data` text NOT NULL 
) ENGINE = MYISAM ;";

$row = $cw->query($sql);

$sql = "alter table `{$table}` add index(order_id)";

$row = $cw->query($sql);
$sql = "alter table `{$table}` add index(quote_id)";

$row = $cw->query($sql);

$installer->endSetup();