<?php
$lang->im->settings = '喧喧设置';
$lang->im->debug    = '调试功能';

$lang->im->version         = '版本';
$lang->im->backendLang     = '服务器端语言';
$lang->im->key             = '密钥';
$lang->im->systemGroup     = '系统';
$lang->im->url             = '访问地址';
$lang->im->pollingInterval = '轮询间隔';
$lang->im->createKey       = '重新生成密钥';
$lang->im->connector       = '、';
$lang->im->viewDebug       = '查看调试信息';
$lang->im->log             = '日志';
$lang->im->xxdStatus       = 'XXD状态';
$lang->im->debugInfo       = '调试信息';
$lang->im->serverInfo      = '服务器信息';
$lang->im->errorInfo       = '错误提示';
$lang->im->xxbConfigError  = 'XXB参数设置不正确。';

$lang->im->debugStatus[0] = '不启用';
$lang->im->debugStatus[1] = '启用';

$lang->im->xxdServer       = '喧喧服务器';
$lang->im->createKey       = '重新生成密钥';
$lang->im->downloadXXD     = '下载XXD服务端';
$lang->im->listenIP        = '监听IP';
$lang->im->chatPort        = '客户端通讯端口';
$lang->im->uploadFileSize  = '上传文件大小';
$lang->im->downloadPackage = '下载完整包';
$lang->im->downloadConfig  = '只下载配置文件';
$lang->im->changeSetting   = '修改配置';
$lang->im->downloadConf    = '下载配置';

$lang->im->day    = '天';
$lang->im->hours  = '小时';
$lang->im->minute = '分钟';
$lang->im->secs   = '秒';

$lang->im->notAdmin         = '不是系统管理员。';
$lang->im->notSystemChat    = '不是系统会话。';
$lang->im->notGroupChat     = '不是多人会话。';
$lang->im->notPublic        = '不是公开会话。';
$lang->im->cantChat         = '没有发言权限。';
$lang->im->chatHasDismissed = '讨论组已被解散';
$lang->im->needLogin        = '用户没有登录。';
$lang->im->notExist         = '会话不存在。';
$lang->im->changeRenameTo   = '将会话名称更改为';
$lang->im->multiChats       = '消息不属于同一个会话。';
$lang->im->notInGroup       = '用户不在此讨论组内。';
$lang->im->notInChat        = '无法向与您无关的会话发送消息。';
$lang->im->notSameUser      = '无法作为他人发送消息。';
$lang->im->errorKey         = '<strong>密钥</strong> 应该为数字或字母的组合，长度为32位。';
$lang->im->defaultKey       = '请勿使用默认<strong>密钥</strong>。';
$lang->im->debugTips        = '<br>使用管理员账号%s并访问此页面。';
$lang->im->noLogFile        = '没有日志文件。';
$lang->im->noFopen          = '未启用fopen函数，请按以下路径自行查看日志文件：%s。';

$lang->im->xxdServerTip   = '喧喧服务器地址为完整的协议+地址+端口，示例：http://192.168.1.35 或 http://www.xxb.com ，不能使用127.0.0.1。';
$lang->im->xxdServerEmpty = '喧喧服务器地址为空。';
$lang->im->xxdServerError = '喧喧服务器地址不能为 127.0.0.1。';
$lang->im->xxdSchemeError = '服务器地址应该以<strong>http://</strong>或<strong>https://</strong>开头。';
$lang->im->xxdPortError   = '服务器地址应该包含有效的端口号，默认为<strong>11443</strong>。';
$lang->im->xxdPollIntTip  = '轮询间隔单位为秒，最小为 5 秒，默认为 60 秒，示例：60。';
$lang->im->xxdPollIntErr  = '轮询间隔应为一个最小为 5 的正整数。';
$lang->im->errorSSLCrt    = 'SSL证书内容不能为空';
$lang->im->errorSSLKey    = 'SSL证书私钥不能为空';
$lang->im->errorXXCLow    = '客户端版本太低,请在喧喧官网下载最新版。';

$lang->im->broadcast = new stdclass();
$lang->im->broadcast->createChat       = '@%s 创建了讨论组 [%s](#/chats/groups/%s)。';
$lang->im->broadcast->joinChat         = '@%s 加入了讨论组。';
$lang->im->broadcast->leaveChat        = '@%s 退出了当前讨论组。';
$lang->im->broadcast->renameChat       = '@%s 将讨论组名称更改为 [%s](#/chats/groups/%s)。';
$lang->im->broadcast->inviteUser       = '@%s 邀请 %s 加入了讨论组。';
$lang->im->broadcast->dismissChat      = '@%s 解散了当前讨论组。';
$lang->im->broadcast->createConference = '@%s 发起了会议。';
$lang->im->broadcast->closeConference  = '@%s 结束了会议。';

$lang->im->xxd = new stdclass();
$lang->im->xxd->os             = '操作系统';
$lang->im->xxd->ip             = '监听IP';
$lang->im->xxd->chatPort       = '客户端通讯端口';
$lang->im->xxd->commonPort     = '通用端口';
$lang->im->xxd->https          = 'HTTPS';
$lang->im->xxd->uploadFileSize = '上传文件大小';
$lang->im->xxd->maxOnlineUser  = '最大在线人数';
$lang->im->xxd->sslcrt         = '证书内容';
$lang->im->xxd->sslkey         = '证书私钥';
$lang->im->xxd->max            = '最大';

$lang->im->httpsOptions['on']  = '启用';
$lang->im->httpsOptions['off'] = '不启用';

$lang->im->osList['win_i386']      = 'Windows 32位';
$lang->im->osList['win_x86_64']    = 'Windows 64位';
$lang->im->osList['linux_i386']    = 'Linux 32位';
$lang->im->osList['linux_x86_64']  = 'Linux 64位';
$lang->im->osList['darwin_x86_64'] = 'macOS';

$lang->im->placeholder = new stdclass();
$lang->im->placeholder->xxd = new stdclass();
$lang->im->placeholder->xxd->ip             = '监听的服务器ip地址，没有特殊需要直接填写0.0.0.0';
$lang->im->placeholder->xxd->chatPort       = '与聊天客户端通讯的端口';
$lang->im->placeholder->xxd->commonPort     = '通用端口，该端口用于客户端登录时验证，以及文件上传下载使用';
$lang->im->placeholder->xxd->https          = '启用https';
$lang->im->placeholder->xxd->uploadFileSize = '上传文件的大小';
$lang->im->placeholder->xxd->maxOnlineUser  = '最大在线人数';
$lang->im->placeholder->xxd->sslcrt         = '请将证书内容复制到此处';
$lang->im->placeholder->xxd->sslkey         = '请将证书密钥复制到此处';

$lang->im->notify = new stdclass();
$lang->im->notify->setReceiver = '没有设置接收者类型，只能是用户或者是某个讨论组。';
$lang->im->notify->setGroup    = '应当设置接收讨论组。';
$lang->im->notify->setUserList = '应当设置接收者用户列表。';
$lang->im->notify->setSender   = '应当设置发送方信息。';
$lang->im->notify->setTitle    = '请提供通知信息的标题。';

$lang->im->xxdConfigNote = array();
$lang->im->xxdConfigNote['zh']['ip'] = '# 监听的IP地址，不要使用127.0.0.1。';
$lang->im->xxdConfigNote['en']['ip'] = '# The ip listened. Do not use 127.0.0.1.';

$lang->im->xxdConfigNote['zh']['commonPort'] = '# 登录和附件上传接口(http)，确保防火墙开放此端口。';
$lang->im->xxdConfigNote['en']['commonPort'] = '# Port for user login and file uploaded(http)';

$lang->im->xxdConfigNote['zh']['chatPort'] = '# 聊天消息通讯端口(websocket)，确保防火墙开放此端口。';
$lang->im->xxdConfigNote['en']['chatPort'] = '# Port for chat(websocket).';

$lang->im->xxdConfigNote['zh']['https'] = '# HTTPS(on|off)。使用HTTPS可以保证消息全程加密。';
$lang->im->xxdConfigNote['en']['https'] = '# on|off. Use https to encryt all messages.';

$lang->im->xxdConfigNote['zh']['uploadPath'] = '# 附件保存的目录。默认存放在xxd/files/。';
$lang->im->xxdConfigNote['en']['uploadPath'] = '# Default upload path is xxd/files.';

$lang->im->xxdConfigNote['zh']['uploadFileSize'] = '# 上传文件的大小，以M为单位。';
$lang->im->xxdConfigNote['en']['uploadFileSize'] = '# The Max size for uploaded files(M).';

$lang->im->xxdConfigNote['zh']['pollingInterval'] = '# 轮询时间，单位为秒，最小值为 5。';
$lang->im->xxdConfigNote['en']['pollingInterval'] = '# Interval of polling, should be a number equal to or greater than 5.';

$lang->im->xxdConfigNote['zh']['maxOnlineUser'] = '# 在线用户上限，0为无限制。';
$lang->im->xxdConfigNote['en']['maxOnlineUser'] = '# Max online users, 0 means no limit.';

$lang->im->xxdConfigNote['zh']['logPath'] = '# 程序运行日志的保存路径。';
$lang->im->xxdConfigNote['en']['logPath'] = '# Path of saved log files.';

$lang->im->xxdConfigNote['zh']['certPath'] = '# 证书的保存路径。';
$lang->im->xxdConfigNote['en']['certPath'] = '# Path of saved certificate.';

$lang->im->xxdConfigNote['zh']['debug'] = '# Debug级别，可设置0|1|2';
$lang->im->xxdConfigNote['en']['debug'] = '# Debug level，0|1|2';

$lang->im->xxdConfigNote['zh']['backend'] = "# xxd可以对接多个后台程序。每一个后台程序由入口文件 + 私钥组成。\n# 客户端登录时如果没有指定后台程序，会默认登录到第一个后台程序。";
$lang->im->xxdConfigNote['en']['backend'] = "# xxd can integrate with multi backends. Every backend has an entry and a key. \n# The client will login to the first backend if the user doesn't specify the backend.";
