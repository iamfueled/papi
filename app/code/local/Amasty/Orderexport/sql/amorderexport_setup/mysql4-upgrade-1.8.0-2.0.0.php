<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('amorderexport/profile')}` ADD `email_use` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `ftp_delete_local` ,
    ADD `email_address` VARCHAR( 128 ) NOT NULL AFTER `email_use` ,
    ADD `email_subject` VARCHAR( 255 ) NOT NULL AFTER `email_address` ,
    ADD `email_compress` TINYINT( 1 ) UNSIGNED NOT NULL AFTER `email_subject` ;
");

$installer->endSetup();