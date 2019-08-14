<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('increasingly_analytics_bundle')} (

  `id` int NOT NULL auto_increment,

  `bundle_id` int NOT NULL default 0,

  `product_ids` varchar(100) NOT NULL,

  `increasingly_visitor_id` varchar(100) NOT NULL,

  `discount_price` decimal(12,4) NULL,

  'total_price' decimal(12,4) NOT NULL,

   PRIMARY KEY  (`id`)

);

");

$installer->endSetup(); 
