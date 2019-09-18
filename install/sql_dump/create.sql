DROP TABLE IF EXISTS `<DB_PREFIX>test`;
CREATE TABLE IF NOT EXISTS `<DB_PREFIX>test` (
  `id` smallint(6) NOT NULL auto_increment,
  `user_name` varchar(30) NOT NULL default '',
  `password` varchar(50) NOT NULL default '',
  `account_type` varchar(12) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

INSERT INTO `<DB_PREFIX>test` (`id`, `user_name`, `password`, `account_type`)
VALUES(1, '<USER_NAME>', <PASSWORD>, 'admin');

