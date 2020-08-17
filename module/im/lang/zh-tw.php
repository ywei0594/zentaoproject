<?php
$lang->im->settings = '喧喧設置';
$lang->im->debug    = '調試功能';

$lang->im->version         = '版本';
$lang->im->backendLang     = '伺服器端語言';
$lang->im->key             = '密鑰';
$lang->im->systemGroup     = '系統';
$lang->im->url             = '訪問地址';
$lang->im->pollingInterval = '輪詢間隔';
$lang->im->createKey       = '重新生成密鑰';
$lang->im->connector       = '、';
$lang->im->viewDebug       = '查看調試信息';
$lang->im->log             = '日誌';
$lang->im->xxdStatus       = 'XXD狀態';
$lang->im->debugInfo       = '調試信息';
$lang->im->serverInfo      = '伺服器信息';
$lang->im->errorInfo       = '錯誤提示';
$lang->im->xxbConfigError  = 'XXB參數設置不正確。';

$lang->im->debugStatus[0] = '不啟用';
$lang->im->debugStatus[1] = '啟用';

$lang->im->xxdServer       = '喧喧伺服器';
$lang->im->createKey       = '重新生成密鑰';
$lang->im->downloadXXD     = '下載XXD服務端';
$lang->im->listenIP        = '監聽IP';
$lang->im->chatPort        = '客戶端通訊連接埠';
$lang->im->uploadFileSize  = '上傳檔案大小';
$lang->im->downloadPackage = '下載完整包';
$lang->im->downloadConfig  = '只下載配置檔案';
$lang->im->changeSetting   = '修改配置';
$lang->im->downloadConf    = '下載配置';

$lang->im->day    = '天';
$lang->im->hours  = '小時';
$lang->im->minute = '分鐘';
$lang->im->secs   = '秒';

$lang->im->notAdmin         = '不是系統管理員。';
$lang->im->notSystemChat    = '不是系統會話。';
$lang->im->notGroupChat     = '不是多人會話。';
$lang->im->notPublic        = '不是公開會話。';
$lang->im->cantChat         = '沒有發言權限。';
$lang->im->chatHasDismissed = '討論組已被解散';
$lang->im->needLogin        = '用戶沒有登錄。';
$lang->im->notExist         = '會話不存在。';
$lang->im->changeRenameTo   = '將會話名稱更改為';
$lang->im->multiChats       = '消息不屬於同一個會話。';
$lang->im->notInGroup       = '用戶不在此討論組內。';
$lang->im->notInChat        = '無法向與您無關的會話發送消息。';
$lang->im->notSameUser      = '無法作為他人發送消息。';
$lang->im->errorKey         = '<strong>密鑰</strong> 應該為數字或字母的組合，長度為32位。';
$lang->im->defaultKey       = '請勿使用預設<strong>密鑰</strong>。';
$lang->im->debugTips        = '<br>使用管理員賬號%s並訪問此頁面。';
$lang->im->noLogFile        = '沒有日誌檔案。';
$lang->im->noFopen          = '未啟用fopen函數，請按以下路逕自行查看日誌檔案：%s。';

$lang->im->xxdServerTip   = '喧喧伺服器地址為完整的協議+地址+連接埠，示例：http://192.168.1.35 或 http://www.xxb.com ，不能使用127.0.0.1。';
$lang->im->xxdServerEmpty = '喧喧伺服器地址為空。';
$lang->im->xxdServerError = '喧喧伺服器地址不能為 127.0.0.1。';
$lang->im->xxdSchemeError = '伺服器地址應該以<strong>http://</strong>或<strong>https://</strong>開頭。';
$lang->im->xxdPortError   = '伺服器地址應該包含有效的連接埠號，預設為<strong>11443</strong>。';
$lang->im->xxdPollIntTip  = '輪詢間隔單位為秒，最小為 5 秒，預設為 60 秒，示例：60。';
$lang->im->xxdPollIntErr  = '輪詢間隔應為一個最小為 5 的正整數。';
$lang->im->errorSSLCrt    = 'SSL證書內容不能為空';
$lang->im->errorSSLKey    = 'SSL證書私鑰不能為空';
$lang->im->errorXXCLow    = '客戶端版本太低,請在喧喧官網下載最新版。';

$lang->im->broadcast = new stdclass();
$lang->im->broadcast->createChat       = '@%s 創建了討論組 [%s](#/chats/groups/%s)。';
$lang->im->broadcast->joinChat         = '@%s 加入了討論組。';
$lang->im->broadcast->leaveChat        = '@%s 退出了當前討論組。';
$lang->im->broadcast->renameChat       = '@%s 將討論組名稱更改為 [%s](#/chats/groups/%s)。';
$lang->im->broadcast->inviteUser       = '@%s 邀請 %s 加入了討論組。';
$lang->im->broadcast->dismissChat      = '@%s 解散了當前討論組。';
$lang->im->broadcast->createConference = '@%s 發起了會議。';
$lang->im->broadcast->closeConference  = '@%s 結束了會議。';

$lang->im->xxd = new stdclass();
$lang->im->xxd->os             = '操作系統';
$lang->im->xxd->ip             = '監聽IP';
$lang->im->xxd->chatPort       = '客戶端通訊連接埠';
$lang->im->xxd->commonPort     = '通用連接埠';
$lang->im->xxd->https          = 'HTTPS';
$lang->im->xxd->uploadFileSize = '上傳檔案大小';
$lang->im->xxd->maxOnlineUser  = '最大在綫人數';
$lang->im->xxd->sslcrt         = '證書內容';
$lang->im->xxd->sslkey         = '證書私鑰';
$lang->im->xxd->max            = '最大';

$lang->im->httpsOptions['on']  = '啟用';
$lang->im->httpsOptions['off'] = '不啟用';

$lang->im->osList['win_i386']      = 'Windows 32位';
$lang->im->osList['win_x86_64']    = 'Windows 64位';
$lang->im->osList['linux_i386']    = 'Linux 32位';
$lang->im->osList['linux_x86_64']  = 'Linux 64位';
$lang->im->osList['darwin_x86_64'] = 'macOS';

$lang->im->placeholder = new stdclass();
$lang->im->placeholder->xxd = new stdclass();
$lang->im->placeholder->xxd->ip             = '監聽的伺服器ip地址，沒有特殊需要直接填寫0.0.0.0';
$lang->im->placeholder->xxd->chatPort       = '與聊天客戶端通訊的連接埠';
$lang->im->placeholder->xxd->commonPort     = '通用連接埠，該連接埠用於客戶端登錄時驗證，以及檔案上傳下載使用';
$lang->im->placeholder->xxd->https          = '啟用https';
$lang->im->placeholder->xxd->uploadFileSize = '上傳檔案的大小';
$lang->im->placeholder->xxd->maxOnlineUser  = '最大在綫人數';
$lang->im->placeholder->xxd->sslcrt         = '請將證書內容複製到此處';
$lang->im->placeholder->xxd->sslkey         = '請將證書密鑰複製到此處';

$lang->im->notify = new stdclass();
$lang->im->notify->setReceiver = '沒有設置接收者類型，只能是用戶或者是某個討論組。';
$lang->im->notify->setGroup    = '應當設置接收討論組。';
$lang->im->notify->setUserList = '應當設置接收者用戶列表。';
$lang->im->notify->setSender   = '應當設置發送方信息。';
$lang->im->notify->setTitle    = '請提供通知信息的標題。';

$lang->im->xxdConfigNote = array();
$lang->im->xxdConfigNote['zh']['ip'] = '# 監聽的IP地址，不要使用127.0.0.1。';
$lang->im->xxdConfigNote['en']['ip'] = '# The ip listened. Do not use 127.0.0.1.';

$lang->im->xxdConfigNote['zh']['commonPort'] = '# 登錄和附件上傳介面(http)，確保防火牆開放此連接埠。';
$lang->im->xxdConfigNote['en']['commonPort'] = '# Port for user login and file uploaded(http)';

$lang->im->xxdConfigNote['zh']['chatPort'] = '# 聊天消息通訊連接埠(websocket)，確保防火牆開放此連接埠。';
$lang->im->xxdConfigNote['en']['chatPort'] = '# Port for chat(websocket).';

$lang->im->xxdConfigNote['zh']['https'] = '# HTTPS(on|off)。使用HTTPS可以保證消息全程加密。';
$lang->im->xxdConfigNote['en']['https'] = '# on|off. Use https to encryt all messages.';

$lang->im->xxdConfigNote['zh']['uploadPath'] = '# 附件保存的目錄。預設存放在xxd/files/。';
$lang->im->xxdConfigNote['en']['uploadPath'] = '# Default upload path is xxd/files.';

$lang->im->xxdConfigNote['zh']['uploadFileSize'] = '# 上傳檔案的大小，以M為單位。';
$lang->im->xxdConfigNote['en']['uploadFileSize'] = '# The Max size for uploaded files(M).';

$lang->im->xxdConfigNote['zh']['pollingInterval'] = '# 輪詢時間，單位為秒，最小值為 5。';
$lang->im->xxdConfigNote['en']['pollingInterval'] = '# Interval of polling, should be a number equal to or greater than 5.';

$lang->im->xxdConfigNote['zh']['maxOnlineUser'] = '# 在綫用戶上限，0為無限制。';
$lang->im->xxdConfigNote['en']['maxOnlineUser'] = '# Max online users, 0 means no limit.';

$lang->im->xxdConfigNote['zh']['logPath'] = '# 程序運行日誌的保存路徑。';
$lang->im->xxdConfigNote['en']['logPath'] = '# Path of saved log files.';

$lang->im->xxdConfigNote['zh']['certPath'] = '# 證書的保存路徑。';
$lang->im->xxdConfigNote['en']['certPath'] = '# Path of saved certificate.';

$lang->im->xxdConfigNote['zh']['debug'] = '# Debug級別，可設置0|1|2';
$lang->im->xxdConfigNote['en']['debug'] = '# Debug level，0|1|2';

$lang->im->xxdConfigNote['zh']['backend'] = "# xxd可以對接多個後台程序。每一個後台程序由入口檔案 + 私鑰組成。\n# 客戶端登錄時如果沒有指定後台程序，會預設登錄到第一個後台程序。";
$lang->im->xxdConfigNote['en']['backend'] = "# xxd can integrate with multi backends. Every backend has an entry and a key. \n# The client will login to the first backend if the user doesn't specify the backend.";
