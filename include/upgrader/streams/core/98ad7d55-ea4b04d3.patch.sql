/**
 * @version v1.11
 * @signature ea4b04d3a9a6afac9a4c8904ac0296db
 * @title Time tracking feature
 *
 *  - Adds required fields to ticket table
 *  - Adds required fields to ticket_thread table
 *  - Inserts Time Type into Custom Lists
 *	- Inserts standard Time Types into Custom List Items
 *	- Inserts osTicket configuration options
 */

ALTER TABLE `%TABLE_PREFIX%ticket`
  ADD `time_spent` INT( 11 ) NOT NULL DEFAULT '0' AFTER `isanswered`;
 
ALTER TABLE `%TABLE_PREFIX%thread_entry`
  ADD `time_spent`  INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `flags`,
  ADD `time_type` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `time_spent`,
  ADD `time_bill` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `time_bill`;

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%ticket_hardware` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `ticket_id` int(11) default NULL,
  `description` varchar(255) default NULL,
  `qty` int(11) NOT NULL default '0',
  `unit_cost` DECIMAL(15,2) NOT NULL default '0',
  `total_cost` DECIMAL(15,2) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'ea4b04d3a9a6afac9a4c8904ac0296db'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
