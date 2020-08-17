-- DROP TABLE IF EXISTS `zt_im_conference`;
CREATE TABLE IF NOT EXISTS `zt_im_conference` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `rid` char(24) NOT NULL DEFAULT '',
  `cgid` char(40) NOT NULL DEFAULT '',
  `status` enum('closed','open') NOT NULL DEFAULT 'closed',
  `participants` text NOT NULL,
  `openedBy` mediumint(8) NOT NULL DEFAULT 0,
  `openedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS `zt_im_conferenceaction`;
CREATE TABLE IF NOT EXISTS `zt_im_conferenceaction` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `rid` char(24) NOT NULL DEFAULT '',
  `type` enum('create','join','leave','close') NOT NULL DEFAULT 'create',
  `user` mediumint(8) NOT NULL DEFAULT 0,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
