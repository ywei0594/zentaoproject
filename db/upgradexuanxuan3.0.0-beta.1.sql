DELETE FROM `zt_im_messagestatus` WHERE `status` = 'sent';
DELETE `zt_im_messagestatus` FROM `zt_im_messagestatus` LEFT JOIN `zt_user` ON `zt_im_messagestatus`.`user` = `zt_user`.`id` WHERE `zt_user`.`deleted` = '1'
