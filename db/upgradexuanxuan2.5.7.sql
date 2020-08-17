ALTER TABLE `zt_im_chatuser` ADD `freeze` ENUM('0', '1') NOT NULL DEFAULT '0'  AFTER `mute`;
ALTER TABLE `zt_im_message` CHANGE `contentType` `contentType` ENUM('text', 'plain', 'emotion', 'image', 'file', 'code', 'object')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'text';
ALTER TABLE `zt_im_client` CHANGE `version` `version` char(30) NOT NULL DEFAULT '';

-- DROP TABLE IF EXISTS `zt_im_queue`;
CREATE TABLE IF NOT EXISTS `zt_im_queue` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `type` char(30) NOT NULL,
  `content` text NOT NULL,
  `addDate` datetime NOT NULL,
  `processDate` datetime NOT NULL,
  `result` text NOT NULL,
  `status` char(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
