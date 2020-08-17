<?php
/**
 * Model file of im module.
 *
 * @copyright Copyright 2009-2020 QingDao Nature Easy Soft Network Technology Co,LTD (www.cnezsoft.com)
 * @author    Xiying Guan <guanxiying@cnezsoft.com>
 * @package   im
 * @license   ZOSL (http://zpl.pub/page/zoslv1.html)
 * @version   $Id$
 * @Link      http://xuan.im
 */
class imModel extends model
{
    /**
     * __construct function load chat, user, message, extension models.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $modelPath = dirname(__FILE__) . DS . "model" . DS;

        helper::import($modelPath . 'chat.php');
        helper::import($modelPath . 'message.php');
        helper::import($modelPath . 'user.php');
        helper::import($modelPath . 'conference.php');

        $this->chat = new chat();
        $this->user = new user();
        $this->message = new message();
        $this->conference = new conference();
    }

    /**
     * Get apiScheme info.
     *
     * @param  string $key
     * @access public
     * @return mix
     */
    public function getApiScheme($key = '')
    {
        $schemeFile = $this->app->getAppRoot() . 'module/im/apischeme.json';
        $scheme = json_decode(trim(file_get_contents($schemeFile)), true);
        if(!$key) return $scheme;
        return zget($scheme, $key, '');
    }

    /**
     * Send formatted output.
     *
     * @param  array|object    $data Example: array('result' => 'success', 'data' => $messages, 'users' => $userIdList, ...);
     * @access public
     * @return void
     */
    public function sendOutput($output, $schemeName = 'RAW')
    {
        $output = $this->formatOutput($output, $schemeName);
        die($this->app->encrypt($output));
    }

    /**
     * Send formatted output group.
     *
     * @param  array|object    $data Example: array('result' => 'success', 'data' => $messages, 'users' => $userIdList, ...);
     * @access public
     * @return void
     */
    public function sendOutputGroup($outputs)
    {
        $firstOutput;
        $formatted = array();
        foreach($outputs as $output)
        {
            if(empty($firstOutput)) $firstOutput = $output;
            $formatted[] = $this->formatOutput($output, strtolower($output->method) . 'Response', $returnRaw = true);
        }

        $userID = isset($firstOutput->userID) ? $firstOutput->userID : 0;
        $method = isset($firstOutput->method) ? $firstOutput->method : '';
        $result = isset($firstOutput->result) ? $firstOutput->result : 'success';
        $finalOutput = $this->appendResponseHeader($formatted, $userID, $firstOutput->users, $method, $result);
        die($this->app->encrypt($finalOutput));
    }

    /**
     * Format output data.
     *
     * @param  array|object    $data Example: array('result' => 'success', 'data' => $messages, 'users' => $userIdList, ...);
     * @param  string          $map
     * @param  bool            $returnRaw
     * @param  bool            $prependName
     * @access public
     * @return object
     */
    public function formatOutput($data, $map = 'RAW', $returnRaw = false, $prependName = true)
    {
        if(!empty($this->app->debug)) $this->app->log("format output($map): " . json_encode($data));

        $output = new stdclass();

        foreach($data as $key => $value)
        {
            if($key == 'users' && !is_array($value)) $value = array((int) $value);
            $output->$key = $value;
        }

        $output->userID  = zget($data, 'userID', '0');
        $output->result  = zget($data, 'result', 'success');
        $output->method  = zget($data, 'method', $this->app->getMethodName());
        $output->rid     = zget($this->app->input, 'rid');

        if($map != 'RAW')
        {
            $maps = zget($this->config->maps, $map);
            $data = self::encodeOutput($output, $maps);
        }
        else
        {
            $map = strtolower($output->method) . 'Response';
        }

        $data = $prependName ? array($map, $data) : $data;

        if(!empty($this->app->debug)) $this->app->log("encoded output($map): " . json_encode($data));
        if($returnRaw) return $data;

        $users = isset($output->users) ? $output->users : array();

        return $this->appendResponseHeader($data, $output->userID, $users, $output->method, $output->result);
    }

    /**
     * Encode output.
     *
     * @param  int    $output
     * @param  int    $map
     * @static
     * @access public
     * @return void
     */
    public static function encodeOutput($output, $map)
    {
        if(empty($map)) return $output;

        $output = (array) $output;

        /* If map is not final map array, decode with map's dataType setting.*/
        if(isset($map['name']) and isset($map['dataType'])) $map = $map['dataType'];
        if(isset($map['name']) and !isset($map['dataType'])) $map = array($map);

        foreach($map as $key => $prop)
        {
            $indexName = $prop['name'];

            if($prop['type'] == 'basic')
            {
                if(isset($prop['options']))
                {
                    $options = array_flip($prop['options']);
                    $data[$key] = zget($options, zget($output, $indexName, ''));
                }
                else
                {
                    $data[$key] = zget($output, $indexName, '');
                }
                continue;
            }

            if($prop['type'] == 'object')
            {
                if(isset($output[$indexName])) $data[$key] = self::encodeOutput($output[$indexName], $prop['dataType']);
                continue;
            }

            if($prop['type'] == 'list')
            {
                $tmpOutput = array();
                foreach($output[$indexName] as $item)
                {
                    $tmpOutput[] = self::encodeOutput($item, $prop['dataType']);
                }

                $data[$key] = $tmpOutput;
            }
        }
        return $data;
    }

    /**
     * Batch encode output.
     *
     * @param  array    $array
     * @param  array    $map
     * @static
     * @access public
     * @return array
     */
    public static function batchEncodeOutput($array, $map)
    {
        if(empty($array)) return array();
        foreach($array as $output) $data[] = self::encodeOutput($output, $map);
        return $data;
    }

    /**
     * Append header for xxd to response.
     *
     * @param  array    $output
     * @param  string   $from    current user id
     * @param  string   $to      id list of users to notify : 123,2,3,4,76,423
     * @param  string   $method
     * @param  string   $result
     * @access public
     * @return string
     */
    public function appendResponseHeader($output, $from, $to = 0, $method = '', $result = 'success')
    {
        if(empty($to)) $to = $this->app->input['userID'];
        elseif(is_array($to)) $to = implode(',', $to);

        if(!$method) $method = strtolower($this->app->getMethodName());

        $device = $this->app->input['device'];
        $lang   = $this->app->input['lang'];
        if(!isset($from) || empty($from)) $from = $this->app->input['userID'];

        $response = "{$to}\n";
        $response .= "{$from}\n";
        $response .= "$method\n";
        $response .= "success\n";
        $response .= "{$device}\n";
        $response .= "{$lang}\n";
        $response .= json_encode($output);

        return $response;
    }

    /**
     * Get output data of user list.
     *
     * @param  array  $idList
     * @param  int    $userID
     * @param  bool   $returnRaw
     * @access public
     * @return object
     */
    public function getUserListOutput($idList = array(), $userID, $returnRaw = false)
    {
        $output = new stdclass();
        $users = $this->user->getList($status = '', $idList, $idAsKey = false);
        if(dao::isError())
        {
            $output->result  = 'fail';
            $output->message = 'Get userlist failed.';
            return $this->formatOutput($output, 'messageResponsePack', $returnRaw);
        }
        else
        {
            $output->result = 'success';
            $output->users  = $userID;
            $output->data   = $users;
            $output->method = empty($idList) ? 'usergetlist' : 'usergetdeleted';

            if(empty($idList))
            {
                $this->app->loadLang('user');
                $roles = $this->lang->user->roleList;

                $allDepts = $this->loadModel('dept')->getListByType('dept');
                $depts = array();
                foreach($allDepts as $id => $dept)
                {
                    $depts[$id] = array('name' => $dept->name, 'order' => (int)$dept->order, 'parent' => (int)$dept->parent);
                }
                $output->roles = $roles;
                $output->depts = $depts;
            }
            else
            {
                $output->partial = $idList;
            }
        }
        return $this->formatOutput($output, 'usergetlistResponse', $returnRaw);
    }

    /**
     * Get output data of chat list.
     *
     * @param  int    $userID
     * @param  bool   $returnRaw
     * @access public
     * @return object
     */
    public function getChatListOutput($userID, $returnRaw = false)
    {
        $chatList = $this->chat->getListByUserID($userID);

        foreach($chatList as $chat)
        {
            if($chat) $chat->members = $this->chat->getMembers($chat->gid);
        }
        if(dao::isError()) return $this->formatOutput(array('result' => 'fail', 'message' => 'Get chat list fail.'), 'messageResponsePack', $returnRaw);

        return $this->formatOutput(array('result' => 'success', 'method' => 'chatgetlist', 'data' => $chatList, 'users' => $userID), 'chatgetlistResponse', $returnRaw);
    }

    /**
     * Get output data of offline messages.
     *
     * @param  int    $userID
     * @param  bool   $returnRaw
     * @access public
     * @return object
     */
    public function getOfflineMessagesOutput($userID, $returnRaw = false)
    {
        $messages = $this->message->getOfflineList($userID);
        if(empty($messages)) return $this->formatOutput(array('result' => 'fail', 'message' => 'Get offline messages list fail.'), 'messageResponsePack', $returnRaw);

        if(dao::isError()) return $this->formatOutput(array('result' => 'fail', 'message' => 'Get offline messages list fail.'), 'messageResponsePack', $returnRaw);

        return $this->formatOutput(array('result' => 'success', 'method' => 'messagesend', 'data' => $messages, 'users' => $userID), 'messagesendResponse', $returnRaw);
    }

    /**
     * Get output data of offline notify.
     *
     * @param  int    $userID
     * @param  bool   $returnRaw
     * @access public
     * @return object
     */
    public function getOfflineNotifyOutput($userID, $returnRaw = false)
    {
        $messages = $this->message->getNotifyByUserID($userID);
        if(empty($messages)) return null;

        if(dao::isError()) return $this->formatOutput(array('result' => 'fail', 'message' => 'Get offline notification list fail.'), 'messageResponsePack', $returnRaw);

        return $this->formatOutput(array('result' => 'success', 'method' => 'syncnotifications', 'data' => $messages, 'users' => $userID), 'syncnotificationsResponse', $returnRaw);
    }

    /**
     * Get output data of open conferences.
     *
     * @param  int     $userID
     * @param  boolean $returnRaw
     * @access public
     * @return object
     */
    public function getOpenConferencesOutput($userID, $returnRaw = false)
    {
        $userChatList = $this->chat->getListByUserID($userID);
        $conferences = $this->conference->getOpenConferencesByChatList($userChatList, $userID);
        if(empty($conferences)) return null;

        if(dao::isError()) return $this->formatOutput(array('result' => 'fail', 'message' => 'Get open conferences list fail.'), 'messageResponsePack', $returnRaw);

        return $this->formatOutput(array('result' => 'success', 'method' => 'syncconferences', 'data' => $conferences, 'users' => $userID), 'syncconferencesResponse', $returnRaw);
    }

	/**
	 * Create gid.
	 * @access public
	 * @return string
	 */
	public static function createGID()
	{
        $id = md5(microtime() . mt_rand());
        return substr($id, 0, 8) . '-' . substr($id, 8, 4) . '-' . substr($id, 12, 4) . '-' . substr($id, 16, 4) . '-' . substr($id, 20, 12);
	}

    /**
     * Download xxd.
     *
     * @param  object $setting
     * @param  string $type
     * @access public
     * @return void
     */
    public function downloadXXD($setting, $downloadType)
    {
        set_time_limit(0);
        $system          = $this->getSystem($setting->os);
        $version         = $this->config->xuanxuan->version;
        $xxdDirectory    = $this->app->tmpRoot . 'xxd' . DS . $version;
        $basePackage     = $xxdDirectory . DS . $system .  ".base.zip";
        $xxdFileName     = 'xxd.' . $version . ".$system" .  ".zip";
        $downloadCDNLink = $this->config->im->xxdDownloadUrl . $version . "/xxd." . $version . ".$system" .  ".zip";

        if(!is_dir($xxdDirectory)) mkdir($xxdDirectory, 0777, true);
        if(!file_exists($basePackage) && $downloadType == 'package')
        {
            $agent = $this->app->loadClass('snoopy');
            $agent->fetch($downloadCDNLink);
            if(!empty(json_decode($agent->results)->error)) return array('result' => 'fail', 'message' => "$basePackage is not exists");
            $fopenPackage = fopen($basePackage, "w");
            fwrite($fopenPackage, $agent->results);
        }

        $data                  = new stdClass();
        $data->xxdDirectory    = $xxdDirectory;
        $data->sslcrt          = $setting->sslcrt;
        $data->sslkey          = $setting->sslkey;
        $data->basePackage     = $basePackage;
        $data->xxdFileName     = $xxdFileName;
        $data->host            = trim($this->getServer(), '/') . (zget($this->config->xuanxuan, 'backend', 'xxb') == 'ranzhi' ? dirname($this->config->webRoot) : $this->config->webRoot);
        $data->ip              = $setting->ip;
        $data->commonPort      = $setting->commonPort;
        $data->chatPort        = $setting->chatPort;
        $data->https           = $setting->https;
        $data->uploadPath      = 'files/';
        $data->uploadFileSize  = $setting->uploadFileSize;
        $data->pollingInterval = isset($this->config->xuanxuan->pollingInterval) ? $this->config->xuanxuan->pollingInterval : 60;
        $data->maxOnlineUser   = isset($setting->maxOnlineUser) ? $setting->maxOnlineUser : 0;
        $data->logPath         = 'log/';
        $data->certPath        = 'cert/';
        $data->debug           = 0;
        $data->key             = $this->config->xuanxuan->key;

        if($downloadType == 'config')
        {
            $configContent = $this->createXxdConfigFile($data);
            if(!empty($configContent)) $this->loadModel('file')->sendDownHeader('xxd.conf', 'conf', $configContent['zh']);
        }
        elseif($downloadType == 'package')
        {
            $packageFileName = $this->createXxdPackage($data);
            if(!empty($packageFileName)) return array('result' => 'success', 'message' => helper::createLink('im', 'downloadXxdPackage', "xxdFileName=$xxdFileName"));
        }

        return array('result' => 'fail', 'message' => 'error');
    }

    /**
     * create xxd config file
     *
     * @param object $setting
     * @access public
     * @return string
     */
    public function createXxdConfigFile($setting)
    {
        $configParamsList = $this->config->im->xxdConfig;

        // Replace template variable.
        $lineMaxLength = 0;
        foreach($configParamsList as  $configParams)
        {
            if($configParams == 'host' || $configParams == 'key')
            {
                $config[$configParams] = $setting->$configParams;
            }
            else
            {
                $config[$configParams] = $configParams . '=' . $setting->$configParams;
                if($configParams == 'uploadFileSize') $config[$configParams] .= 'M';
            }
            $lineMaxLength = strlen($configParams) > $lineMaxLength ? strlen($configParams) : $lineMaxLength;
        }
        $lineMaxLength += 10;

        // Add parameter notes
        $contentZH = '[server]' . "\n";
        $contentEN = '[server]' . "\n";
        foreach($config as $type => $configValue)
        {
            if($type == 'host' || $type == 'key') continue;
            $configValue  = str_replace(PHP_EOL, '', $configValue);
            $configlength = strlen($configValue);

            for($i = 0; $i < ($lineMaxLength - $configlength); $i++) $configValue .= ' ';
            $contentZH .= $configValue . $this->lang->im->xxdConfigNote['zh'][$type] . "\n";
            $contentEN .= $configValue . $this->lang->im->xxdConfigNote['en'][$type] . "\n";
        }

        // Add backend
        $backend     = "\n" . '[backend]' . "\n";
        $backendFoot = 'default=' . $config['host'] . 'x.php,' . $config['key'];
        $backendFoot = str_replace(PHP_EOL, '', $backendFoot) . "\n";
        $backendZH   = $backend . $this->lang->im->xxdConfigNote['zh']['backend'] . "\n" . $backendFoot;
        $backendEN   = $backend . $this->lang->im->xxdConfigNote['en']['backend'] . "\n" . $backendFoot;
        $contentZH  .= $backendZH;
        $contentEN  .= $backendEN;

        return array('zh' => $contentZH, 'en' => $contentEN);
    }

    /**
     * create xxd package
     *
     * @param object
     * @access public
     * @return string
     */
    public function createXxdPackage($setting)
    {
        $configContent = $this->createXxdConfigFile($setting);
        if(empty($configContent)) return false;

        // unzip package
        $this->app->loadClass('pclzip', true);
        $basePackage = new pclzip($setting->basePackage);
        $result  = $basePackage->extract(
            PCLZIP_OPT_PATH, $setting->xxdDirectory
        );
        if($result == 0) $basePackage->errorInfo(true);

        // Replace config file.
        $baseFilePath = $result[0]['filename'];
        $packageName  = $result[0]['stored_filename'];
        unlink($baseFilePath . 'config/xxd.conf');
        unlink($baseFilePath . 'config/xxd.en.conf');
        file_put_contents($baseFilePath . 'config/xxd.conf', $configContent['zh']);
        file_put_contents($baseFilePath . 'config/xxd.en.conf', $configContent['en']);

        // https add certificate
        if(isset($setting->https) && $setting->https == 'on')
        {
            if(!is_dir($baseFilePath . 'cert')) mkdir($baseFilePath . 'cert', 0777);
            file_put_contents($baseFilePath . 'cert/xxd.crt', $setting->sslcrt);
            file_put_contents($baseFilePath . 'cert/xxd.key', $setting->sslkey);
        }

        // zip xxd file
        chdir($setting->xxdDirectory);
        $xxdZipName = $setting->xxdDirectory . "/" . $setting->xxdFileName;
        $xxdZip     = new pclzip($xxdZipName);
        $xxdResult  = $xxdZip->create($packageName, PCLZIP_OPT_TEMP_FILE_ON);
        if($xxdResult == 0) return false;
        return $xxdResult[0]['filename'];
    }

    /**
     * revise operating system name.
     *
     * @param string $os name
     * @access public
     * @return string
     */
    public function getSystem($os)
    {
        return zget($this->config->im->osMap, $os, 'win64');
    }

    /**
     * Get server.
     *
     * @access public
     * @return string
     */
    public function getServer()
    {
        if(!empty($this->config->xuanxuan->server)) return $this->config->xuanxuan->server;

        return commonModel::getSysURL();
    }

    /**
     * UploadFile a file.
     *
     * @param  string $fileName
     * @param  string $path
     * @param  int    $size
     * @param  int    $time
     * @param  int    $userID
     * @param  string $users
     * @param  object $chat
     * @access public
     * @return int
     */
    public function uploadFile($fileName, $path, $size, $time, $userID, $users, $chat)
    {
        $user      = $this->user->getByID($userID);
        $extension = $this->loadModel('file')->getExtension($fileName);

        $file = new stdclass();
        $file->pathname    = $path;
        $file->title       = rtrim($fileName, ".$extension");
        $file->extension   = $extension;
        $file->size        = $size;
        $file->objectType  = 'chat';
        $file->objectID    = $chat->id;
        $file->createdBy   = !empty($user->account) ? $user->account : '';
        $file->createdDate = date(DT_DATETIME1, $time);

        $this->dao->insert(TABLE_FILE)->data($file)->exec();

        $fileID = $this->dao->lastInsertID();
        $path  .= md5($fileName . $fileID . $time);
        $this->dao->update(TABLE_FILE)->set('pathname')->eq($path)->where('id')->eq($fileID)->exec();

        return $fileID;
    }

    /**
     * Get list of users who changed password but did not re-login.
     *
     * @access public
     * @return array
     */
    public function getKickList()
    {
        $actions = array();
        $actionObjectIDs = array();
        /* Check edited user actions since last poll. */
        $editedActions = $this->loadModel('action')->getListSinceLastPoll('user', null, 'edited');
        foreach($editedActions as $action)
        {
            /* Process action with history, if action user is not in the list yet. */
            if(isset($action->history) && !in_array($action->objectID, $actionObjectIDs))
            {
                foreach($action->history as $history)
                {
                    /* If found history of password change in current action, push into the list and continue checking other actions. */
                    if($history->field == 'password')
                    {
                        $actions[$action->objectID] = $action;
                        $actionObjectIDs[] = (int)$action->objectID;
                        continue 2;
                    }
                }
            }
        }

        /* Check xuan login actions since last poll. */
        $checkedLoginObjectIDs = array();
        $loginActions = $this->loadModel('action')->getListSinceLastPoll('user', null, 'loginxuanxuan');

        foreach($loginActions as $action)
        {
            if(in_array((int)$action->objectID, $actionObjectIDs) && !in_array($action->objectID, $checkedLoginObjectIDs) && strtotime($action->date) < strtotime($actions[$action->objectID]->date))
            {
                $actionObjectIDs = array_diff($actionObjectIDs, array((int)$action->objectID));
            }
        }

        return $actionObjectIDs;
    }

    /**
     * Save xxd start time.
     *
     * @access public
     * @return void
     */
    public function setXxdStartTime()
    {
        $this->loadModel('setting')->setItem('system.common.xxd.start', helper::now());
        return !dao::isError();
    }

    /**
     * update last poll.
     *
     * @access public
     * @return void
     */
    public function updateLastPoll()
    {
        $this->loadModel('setting')->setItem('system.common.xxd.lastPoll', helper::now());
    }

    /**
     * check xxb config.
     *
     * @access public
     * @return bool
     */
    public function checkXXBConfig()
    {
        $xxbConfig      = $this->config->xuanxuan;
        $notEmptyFields = array('key', 'server', 'ip', 'chatPort', 'commonPort');

        foreach($notEmptyFields as $field) if(empty($xxbConfig->$field)) return false;
        if($xxbConfig->https == 'on' && (empty($xxbConfig->sslcrt) || empty($xxbConfig->sslkey))) return false;

        return true;
    }

    /**
     * Get xxd run time.
     *
     * @access public
     * @return Y/H
     */
    public function getXxdRunTime($timestamp, $count = 0)
    {
        if($count > 1) return;

        if($timestamp > 86400)
        {
            return floor($timestamp / 86400) . $this->lang->im->day . $this->getXxdRunTime($timestamp%86400, ++$count);
        }
        else if($timestamp > 3600)
        {
            return floor($timestamp / 3600) . $this->lang->im->hours . $this->getXxdRunTime($timestamp%3600, ++$count);
        }
        else if($timestamp > 60)
        {
            return floor($timestamp / 60) . $this->lang->im->minute . $this->getXxdRunTime($timestamp%60, ++$count);
        }
        else
        {
            return $timestamp . $this->lang->im->secs;
        }
    }

    /**
     * Get xxd status.
     *
     * @access public
     * @return string
     */
    public function getXxdStatus()
    {
        $this->app->loadLang('client');
        $now          = helper::now();
        $xxdStatus    = 'offline';
        $polling      = empty($this->config->xuanxuan->pollingInterval) ? 60 : $this->config->xuanxuan->pollingInterval;
        $lastPoll     = $this->loadModel('setting')->getItem("owner=system&module=common&section=xxd&key=lastPoll");
        $xxdStartDate = zget($this->config->xxd, 'start', $this->lang->client->noData);

        if((strtotime($now) - strtotime($xxdStartDate) < $polling) || (strtotime($now) - strtotime($lastPoll)) < (3 + $polling))
        {
            $xxdStatus = 'online';
        }
        else if((strtotime($now) - strtotime($lastPoll)) > (3 + $polling))
        {
            $xxdStatus = 'offline';
        }

        return $xxdStatus;
    }

    /**
     * Get signed time.
     * Other program can extend this function.
     *
     * @param  string $account
     * @access public
     * @return string | int
     */
    public function getSignedTime($account = '')
    {
        return 0;
    }

    /**
     * Get extension list.
     * @param $userID
     * @return array
     */
    public function getExtensionList($userID)
    {
        $entries    = array();
        $allEntries = array();
        $time       = time();
        $baseURL    = commonModel::getSysURL();
        $entryList  = $this->dao->select('*')->from(TABLE_ENTRY)->orderBy('`order`, id')->fetchAll();
        $files      = $this->dao->select('id, pathname, objectID')->from(TABLE_FILE)->where('objectType')->eq('entry')->fetchAll('objectID');

        foreach($entryList as $entry)
        {
            $data = new stdclass();
            $data->id     = $entry->id;
            $data->url    = strpos($entry->login, 'http') !== 0 ? str_replace('../', $baseURL . $this->config->webRoot, $entry->login) : $entry->login;
            $allEntries[] = $data;
        }

        $_SERVER['SCRIPT_NAME'] = str_replace('x.php', 'index.php', $_SERVER['SCRIPT_NAME']);
        foreach($entryList as $entry)
        {
            if($entry->status != 'online') continue;
            if(strpos(',' . $entry->platform . ',', ',xuanxuan,') === false) continue;

            $token = '';
            if(isset($files[$entry->id]->pathname))
            {
                $token = '&time=' . $time . '&token=' . md5($files[$entry->id]->pathname . $time);
            }
            $data = new stdClass();
            $data->entryID     = (int)$entry->id;
            $data->name        = $entry->code;
            $data->displayName = $entry->name;
            $data->abbrName    = $entry->abbr;
            $data->webViewUrl  = strpos($entry->login, 'http') !== 0 ? str_replace('../', $baseURL . $this->config->webRoot, $entry->login) : $entry->login;
            $data->download    = empty($entry->package) ? '' : $baseURL . helper::createLink('file', 'download', "fileID={$entry->package}&mouse=" . $token);
            $data->md5         = empty($entry->package) ? '' : md5($entry->package);
            $data->logo        = empty($entry->logo)    ? '' : $baseURL . $this->config->webRoot . ltrim($entry->logo, '/');

            if($entry->sso) $data->data = $allEntries;

            $entries[] = $data;
        }

        return $entries;
    }

    /**
     * __call functions defined in model.
     *
     * @param  string    $function
     * @param  array     $arguments
     * @access public
     * @return void
     */
    public function __call($function, $arguments)
    {
        $modelList = array('chat', 'user', 'message', 'conference');

        foreach($modelList as $model)
        {
            if(strpos(strtolower($function), $model) === 0)
            {
                $trimedFunction = substr($function, strlen($model));
                if(is_callable(array($this->$model, $trimedFunction))) return call_user_func_array(array($this->$model, $trimedFunction), $arguments);
            }
        }

        $this->app->triggerError("Method im::$function not exists.", __FILE__, __LINE__, $exit = true);
    }
}
