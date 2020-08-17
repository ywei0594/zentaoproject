<?php
$config->im->logLine = 20;

$config->im->require = new stdclass();
$config->im->require->create = 'gid, name, type';
$config->im->require->edit   = 'gid, name, type';

$config->im->user = new stdclass();
$config->im->user->canEditFields = array('avatar', 'birthday', 'gender', 'email', 'skype', 'qq', 'mobile', 'phone', 'address', 'zipcode', 'clientStatus');

$config->im->retract = new stdclass();
$config->im->retract->validTime = 2;

$config->im->xxdDownloadUrl = "http://dl.cnezsoft.com/xuanxuan/";

$config->im->xxdConfig = array('ip', 'commonPort', 'chatPort', 'https', 'uploadPath', 'uploadFileSize', 'pollingInterval', 'maxOnlineUser', 'logPath', 'certPath', 'debug', 'host', 'key');

$config->im->osMap = array();
$config->im->osMap['win_i386']      = 'win32';
$config->im->osMap['win_x86_64']    = 'win64';
$config->im->osMap['linux_i386']    = 'linux.ia32';
$config->im->osMap['linux_x86_64']  = 'linux.x64';
$config->im->osMap['darwin_x86_64'] = 'mac';

