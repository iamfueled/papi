<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('giftvoucher_history')}
CHANGE `order_increment_id` `order_increment_id` varchar(127) default '';

");

$installer->endSetup(); 