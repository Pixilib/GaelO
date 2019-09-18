CREATE TABLE IF NOT EXISTS `<DB_PREFIX>test` (
  `id` smallint(6) NOT NULL auto_increment,
  `user_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '',
  `password` varchar(50) CHARACTER SET latin1 NOT NULL default '',
  `account_type` varchar(12) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

ALTER TABLE `<DB_PREFIX>test` ADD `date_created` DATETIME NULL DEFAULT NULL;