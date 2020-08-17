<?php
class im extends control
{
    /**
     * Server start.
     *
     * @access public
     * @return void
     */
    public function sysServerStart()
    {
        $this->im->setXxdStartTime();
        $this->im->userResetStatus();
        $this->im->chatInitSystemChat();
        $this->im->updateLastPoll();

        $output = new stdClass();
        $output->result  = 'success';
        $output->version = $this->config->version;

        die($this->app->encrypt($output));
    }

    /**
     * Get serverInfo api.
     *
     * @param  string    $account
     * @param  string    $password
     * @param  string    $apiVersion
     * @param  int       $userID
     * @param  string    $version
     * @param  string    $device
     * @access public
     * @return void
     */
    public function sysGetServerInfo($account, $password, $apiVersion = '', $userID = 0, $version = '', $device = 'desktop')
    {
        $user = $this->im->userIdentify($account, $password);
        if(!$user || $user == 'locked')
        {
            $output = new stdclass();
            $output->result = 'fail';
            $output->message = $this->lang->user->loginFailed;
            die($this->app->encrypt($output));
        }

        if($user == 'banned')
        {
            $output = new stdclass();
            $output->result = 'fail';
            $output->data   = 'banned';
            die($this->app->encrypt($output));
        }

        $data   = array();
        $result = $this->loadModel('client')->checkUpgrade($version);
        if($result !== false) $data = $result;

        $output = new stdclass();
        $output->result     = 'success';
        $output->users      = array($user->id);
        $output->update     = $data;
        $output->backend    = $this->config->xuanxuan->backend;
        $output->userID     = $user->id;
        $output->method     = 'sysgetserverinfo';
        $output->device     = $device;
        $output->lang       = zget($this->app->input, 'lang');
        $output->rid        = zget($this->app->input, 'rid');
        $output->version    = $this->config->version;
        $output->extPerm    = $this->loadModel('license')->getPropertyValue('permissions');
        $output->backendURL = $this->im->getServer() . $this->config->webRoot;

        /* Send owt configuration if available. */
        $owt = $this->loadModel('owt')->getConfiguration();
        if(!empty($owt)) $output->owt = $owt;

        /* Pushing related information for mobile devices.*/

        /* Send api scheme if client api version mismatch server api version. */
        $currentApiVersion = $this->config->maps['$version'];
        if($currentApiVersion != $apiVersion) $output->apiScheme = $this->im->getApiScheme();

        /* Send server local timestamp to client. */
        $output->serverTime = (int)(microtime(true) * 1000);

        die($this->app->encrypt($output));
    }

    /**
     * Login.
     *
     * @param  string   $account  user account
     * @param  string   $password encrypted password
     * @param  object   $options {simple: 0 | 1, status: 'online' | 'busy' | 'away'}
     * @param  int      $userID
     * @param  string   $version
     * @param  string   $device   desktop | mobile
     * @access public
     * @return void
     */
    public function userLogin($account = '', $password = '', $options = 0, $userID = 0, $version = '', $device = 'desktop')
    {
        $user = $this->im->userIdentify($account, $password);
        $lang = $this->app->input['lang'];

        if(!$user) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->user->loginFailed), 'messageResponsePack');

        if($user == 'locked' || $user == 'lockedPlus') $this->im->sendOutput(array('result' => 'fail', 'data' => $user));

        $loginInfo = new stdclass();
        $loginInfo->result = 'success';
        $loginInfo->users  = $user->id;
        $loginInfo->method = 'userlogin';
        $loginInfo->device = $device;
        $loginInfo->lang   = $lang;

        /* Save client status and client lang of the user.*/
        $userData = new stdclass();
        $userData->id           = $user->id;
        $userData->clientStatus = 'online';
        $userData->clientLang   = $this->session->clientLang;
        $user = $this->im->userUpdate($userData);
        $simple = $options && $options->simple;

        $this->loadModel('action')->create('user', $user->id, $simple ? 'reconnectXuanxuan' : 'loginXuanxuan', '', 'xuanxuan-v' . (empty($version) ? '?' : $version), $user->account);

        /* Append signed time, backendUrl and status to user. */
        $user->status     = 'online';

        $loginInfo->data = $user;

        $loginInfo = $this->im->formatOutput($loginInfo, 'userloginResponse', $returnRaw = true);

        if(!$simple)
        {
            $userList  = $this->im->getUserListOutput($idList = array(), $user->id, true);
            $chatList  = $this->im->getChatListOutput($user->id, true);
        }
        else
        {
            $userList  = array();
            $chatList  = array();
        }

        $notifies    = $this->im->getOfflineNotifyOutput($user->id, true);
        $messages    = $this->im->message->getOfflineList($user->id);
        $histories   = $this->im->message->getHistoryList($user, $device);
        $conferences = $this->im->getOpenConferencesOutput($user->id, true);

        // Skip histories in offline messages list
        if(!empty($messages) && !empty($histories))
        {
            foreach($messages as $message)
            {
                if(array_key_exists($message->gid, $histories))
                {
                    unset($histories[$message->gid]);
                }
            }
        }

        $output = array($loginInfo);
        if(!empty($userList))    $output[] = $userList;
        if(!empty($chatList))    $output[] = $chatList;
        if(!empty($notifies))    $output[] = $notifies;
        if(!empty($conferences)) $output[] = $conferences;
        if(!empty($messages))    $output[] = $this->im->formatOutput(array('result' => 'success', 'method' => 'messagesend', 'data' => $messages, 'users' => $userID), 'messagesendResponse', $returnRaw = true);
        if(!empty($histories))   $output[] = $this->im->formatOutput(array('result' => 'success', 'method' => 'chatgethistory', 'data' => $histories, 'users' => $user->id), 'chatgethistoryResponse', $returnRaw = true);

        $response = $this->im->appendResponseHeader($output, $user->id, $user->id);

        $this->loadModel('setting')->setItem($user->account . '.common.lastLogin.' . $device, date(DT_DATETIME1));

        die($this->app->encrypt($response));
    }

    /**
     * Logout.
     *
     * @param  bool   $normal
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function userLogout($normal = false, $userID = 0)
    {
        $users = array();
        $output = new stdClass();

        $onlineUserIdList = array_keys($this->im->userGetList($status = 'online'));
        if(!in_array($userID, $onlineUserIdList))
        {
            $output->result = 'fail';
            $output->data   = $this->im->userGetByID($userID);
            $output->users  = $onlineUserIdList;
            die($this->app->encrypt($output));
        }

        $user = new stdclass();
        $user->id           = $userID;
        $user->clientStatus = 'offline';
        $user = $this->im->userUpdate($user);

        $user->status = $user->clientStatus;
        $this->loadModel('action')->create('user', $userID, $normal ? 'logoutXuanxuan' : 'disconnectXuanxuan', '', 'xuanxuan', $user->account);

        $this->im->conferenceRemoveUserFromConferences($userID);

        session_destroy();

        $onlineUsers      = $this->im->userGetList($status = 'online');
        $onlineUserIdList = array_keys($onlineUsers);

        $output->result = 'success';
        $output->data   = $user;
        $output->users  = $onlineUserIdList;
        $this->im->sendOutput($output, 'userlogoutResponse');
    }

    /**
     * Get user list.
     *
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function userGetList($userID = 0)
    {
        $output = $this->im->getUserListOutput('', $userID);
        die($this->app->encrypt($output));
    }

    /**
     * Get deleted users with their user ids.
     *
     * @param  array  $idList
     * @param  int    $userID
     * @return void
     */
    public function userGetDeleted($idList, $userID)
    {
        $output = $this->im->getUserListOutput($idList, $userID);
        die($this->app->encrypt($output));
    }

    /**
     * Change a user.
     *
     * @param  array  $user
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function userUpdate($user = array(), $userID = 0)
    {
        $user = (object)$user;
        $user->id = $userID;
        if(isset($user->status) && !empty($user->status))
        {
            $user->clientStatus = $user->status;
            unset($user->status);
        }
        $user  = $this->im->userUpdate($user);

        if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Update user fail'), 'messageResponsePack');

        $users = $this->im->userGetList($status = 'online');

        $this->loadModel('action')->create('user', $userID, 'edited', '', 'xuanxuan', $user->account);

        $output = new stdclass();
        $output->result = 'success';
        $output->users  = array_keys($users);
        $output->data   = $user;

        $this->im->sendOutput($output, 'userupdateResponse');
    }

    /**
     * Upload or download settings.
     *
     * @param  string               $account
     * @param  string|array|object  $settings
     * @param  int                  $userID
     * @access public
     * @return void
     */
    public function userSyncSettings($account = '', $settings = '', $userID = 0)
    {
        $settingsObj  = new stdclass();
        $userSettings = json_decode($this->loadModel('setting')->getItem("owner=system&module=chat&section=settings&key=$account")) ?: new stdClass();

        if(is_object($settings))
        {
            /* Upload settings. */
            $settingsObj = $settings;
            foreach($settings as $key => $value) $userSettings->$key = $value;
            $this->setting->setItem("system.chat.settings.$account", helper::jsonEncode($userSettings));
        }
        elseif(is_array($settings))
        {
            /* Download the specified settings. */
            foreach($settings as $key) $settingsObj->$key = zget($userSettings, $key, '');
        }
        else
        {
            /* Download all settings. */
            $settingsObj = $userSettings;
        }

		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Save settings fail'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = array($userID);
		$output->data   = $settingsObj;
		$this->im->sendOutput($output, 'usersyncsettingsResponse');
    }

    /**
     * Set push device token to user table.
     *
     * @param  string $deviceToken
     * @param  string $deviceType android|ios
     * @param  int $userID
     * @access public
     * @return void
     */
    public function userSetDeviceToken($deviceToken = '', $deviceType = 'android', $userID = 0)
    {
        $result = $this->loadModel('user')->setDeviceToken($deviceToken, $deviceType, $userID);
        $this->im->sendOutput(array('result' => $result, 'users' => array($userID)), 'usersetdevicetokenResponse');
    }

    /**
     * Get public chat list.
     *
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatGetPublicList($userID = 0)
    {
        $chatList = $this->im->chatGetList();
        foreach($chatList as $chat) $chat->members = $this->im->chatGetMembers($chat->gid);

        if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Get public chat list fail'), 'messageResponsePack');

        $output = new stdclass();
        $output->result = 'success';
        $output->users  = $userID;
        $output->data   = $chatList;

        $this->im->sendOutput($output, 'chatgetpubliclistResponse');
    }

    /**
     * Get chat list of a user.
     *
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatGetList($userID = 0)
    {
        $output = $this->im->getChatListOutput($userID);
        $this->im->sendOutput($output);
    }

    /**
     * Get members of a chat.
     *
     * @param  string $gid
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatGetMembers($gid = '', $userID = 0)
    {
        $members = $this->im->chatGetMembers($gid);
        if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Get member list fail'));

        $output = new stdclass();
        $output->result = 'success';
        $output->users  = array($userID);

        $data = new stdclass();
        $data->gid     = $gid;
        $data->members = $members;

        $output->data = $data;
        $this->im->sendOutput($output);
    }

    /**
     * Create a chat.
     *
     * @param  string $gid
     * @param  string $name
     * @param  string $type
     * @param  array  $members
     * @param  int    $subjectID
     * @param  bool   $public    true: the chat is public | false: the chat isn't public.
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatCreate($gid = '', $name = '', $type = 'group', $members = array(), $subjectID = 0, $public = false, $userID = 0)
    {
        if($gid == 'notification' or $gid == 'littlexx') $this->im->sendOutput(array('result' => 'success', 'users' =>$userID));

        $chat = $this->im->chat->getByGid($gid, true);

        if(!$chat)
        {
            $name = strip_tags($name);
            $chat = $this->im->chat->create($gid, $name, $type, $members, $subjectID, $public, $userID);
        }

        $users = $this->im->userGetList($status = 'online', $chat->members);

        if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Create chat fail'), 'messageResponsePack');

        $output = new stdclass();
        $output->result = 'success';
        $output->method = $this->app->getMethodName();
        $output->users  = array_keys($users);
        $output->data   = $chat;

        if($type == 'group')
        {
            $broadcast = $this->im->messageCreateBroadcast('createChat', $chat, array_keys($users), $userID);
            if($broadcast) $output = array($output, $broadcast);

            $this->im->sendOutputGroup($output);
        }

        $this->im->sendOutput($output);
    }

    /**
     * Set admins of a chat.
     *
     * @param  string $gid
     * @param  array  $admins
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatSetAdmins($gid = '', $admins = array(), $userID = 0)
    {
        $user = $this->im->userGetByID($userID);
        if(!empty($user->admin) && $user->admin != 'super') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notAdmin), 'chatsetadminsResponse');

        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'chatsetadminsResponse');

        if($chat->type != 'system') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notSystemChat), 'chatsetadminsResponse');

        // TODO:: set admin and set committers
        $chat  = $this->im->chatSetAdmins($gid, $admins);
        $users = $this->im->userGetList($status = 'online', $chat->members);

        $output = new stdclass();
        $output->result = 'success';
        $output->users  = array_keys($users);
        $output->data   = $chat;
        $this->im->sendOutput($output, 'chatsetadminsResponse');
    }

    /**
     * Remove admins of a chat.
     *
     * @param  string $gid
     * @param  array  $admins
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatRemoveAdmins($gid = '', $users = array(), $userID = 0)
    {
        $user = $this->im->userGetByID($userID);
        if(!empty($user->admin) && $user->admin != 'super') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notAdmin), 'chatremoveadminsResponse');

        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'chatremoveadminsResponse');

        if($chat->type != 'system') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notSystemChat), 'chatremoveadminsResponse');

        // TODO:: set admin and set committers
        $chat  = $this->im->chatRemoveAdmins($gid, $users);
        $users = $this->im->userGetList($status = 'online', $chat->members);

        $output = new stdclass();
        $output->result = 'success';
        $output->users  = array_keys($users);
        $output->data   = $chat;
        $this->im->sendOutput($output, 'chatremoveadminsResponse');
    }

    /**
     * Join a chat.
     *
     * @param  string $gid
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatJoin($gid = '', $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');

        if($chat->type != 'group') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');
        if($chat->public == '0')   $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notPublic), 'messageResponsePack');

        $this->im->chatJoin($gid, $userID);

        $chat  = $this->im->chat->getByGid($gid, true);
        $users = $this->im->userGetList($status = 'online', $chat->members);
        $users = array_keys($users);
        $users[] = $userID;

        if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Join chat failed.'), 'messageResponsePack');

        $output = new stdclass();
        $output->result = 'success';
        $output->method = $this->app->getMethodName();
        $output->users  = $users;
        $output->data   = $chat;

        $broadcast = $this->im->messageCreateBroadcast('joinChat', $chat, $users, $userID);
        if($broadcast)
        {
            $output = array($output, $broadcast);
			$this->im->sendOutputGroup($output);
        }
        $this->im->sendOutput($output);
    }

    /**
     * Leave a chat.
     *
     * @param  string $gid
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatLeave($gid = '', $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');

        if($chat->type != 'group') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');

        $this->im->chatLeave($gid, $userID);
        if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Leave chat failed.'), 'messageResponsePack');

        $chat  = $this->im->chat->getByGid($gid, true);
        $users = $this->im->userGetList($status = 'online', $chat->members);
        $users = array_keys($users);

        $output = new stdclass();
        $output->result = 'success';
        $output->method = $this->app->getMethodName();
        $output->users  = $users;
        $output->data   = $chat;

        $broadcast = $this->im->messageCreateBroadcast('leaveChat', $chat, $users, $userID);
        if($broadcast)
        {
            $output = array($output, $broadcast);
			$this->im->sendOutputGroup($output);
        }

        $this->im->sendOutput($output, 'chatleaveResponse');
    }

    /**
     * Change the name of a chat.
     *
     * @param  string $gid
     * @param  string $name
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatRename($gid = '', $name = '', $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');

        if($chat->type != 'group' && $chat->type != 'system') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');

		$chat->name = strip_tags($name);
		$chat  = $this->im->chatUpdate($chat, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Rename chat fail.'), 'messageResponsePack');

		$users = $this->im->userGetList($status = 'online', $chat->members);

		$output = new stdclass();
        $output->result = 'success';
        $output->method = $this->app->getMethodName();
		$output->users  = array_keys($users);
		$output->data   = $chat;

		$broadcast = $this->im->messageCreateBroadcast('renameChat', $chat, array_keys($users), $userID);
		if($broadcast)
		{
			$output = array($output, $broadcast);
			$this->im->sendOutputGroup($output);
		}

		$this->im->sendOutput($output);
    }

    /**
     * Dismiss a chat
     *
     * @param  string $gid
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatDismiss($gid = '', $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');
        if($chat->type != 'group') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');

        $chat->dismissDate = helper::now();
        $chat  = $this->im->chatUpdate($chat, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Dismiss chat fail.'), 'messageResponsePack');

        $users = $this->im->userGetList($status = 'online', $chat->members);

		$output = new stdclass();
        $output->result = 'success';
        $output->method = $this->app->getMethodName();
		$output->users  = array_keys($users);
		$output->data   = $chat;

		$broadcast = $this->im->messageCreateBroadcast('dismissChat', $chat, array_keys($users), $userID);
		if($broadcast)
		{
			$output = array($output, $broadcast);
			$this->im->sendOutputGroup($output);
		}

		$this->im->sendOutput($output);
    }

    /**
     * Change the committers of a chat
     *
     * @param  string $gid
     * @param  string $committers
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatSetCommitters($gid = '', $committers = '', $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');
        if($chat->type != 'group' && $chat->type != 'system') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');

        if($committers == '$ADMINS')
        {
            if(empty($chat->admins))
            {
                $user = $this->loadModel('user')->getByAccount($chat->createdBy);
                $chat->committers = $user->id;
            }
            else
            {
                $chat->committers = $chat->admins;
            }
        }
        else
        {
            $chat->committers = $committers;
        }

        $chat  = $this->im->chatUpdate($chat, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Set chat committers fail.'), 'messageResponsePack');
        $users = $this->im->userGetList($status = 'online', $chat->members);

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = array_keys($users);
		$output->data   = $chat;

		$this->im->sendOutput($output, 'chatsetcommittersResponse');
	}

    /**
     * Change a chat to be public or not.
     *
     * @param  string   $gid
     * @param  string   $public 0|1
     * @param  int      $userID
     * @access public
     * @return void
     */
    public function chatSetVisibility($gid = '', $visible = '1', $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);

        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');
        if($chat->type != 'group') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');

        $chat->public         = $visible ? 1 : 0;
        $chat->lastActiveTime = empty($chat->lastActiveTime) ? '0000-00-00 00:00:00' : date('Y-m-d H:i:s', $chat->lastActiveTime);
        $chat  = $this->im->chatUpdate($chat, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Set chat visibility fail.'), 'messageResponsePack');

        $users = $this->im->userGetList($status = 'online', $chat->members);

        $output = new stdclass();
		$output->result = 'success';
        $output->users  = array_keys($users);
		$output->data   = $chat;

		$this->im->sendOutput($output, 'chatsetvisibilityResponse');
    }

	/**
	 * Star or cancel star a chat.
	 *
	 * @param  bool   $star true: star a chat | false: cancel star a chat.
	 * @param  string $gid
	 * @param  int    $userID
	 * @access public
	 * @return void
	 */
	public function chatStar($star = true, $gid = '', $userID = 0)
	{
		$chat = $this->im->chatStar($star, $gid, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Operate fail.'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = $userID;

		$output->data = new stdclass();
		$output->data->gid  = $gid;
		$output->data->star = $star;
		$this->im->sendOutput($output, 'chatstarResponse');
	}

    /**
     * Hide or display a chat.
     *
     * @param  bool   $hide true: hide a chat | false: display a chat.
     * @param  string $gid
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatHide($hide = true, $gid = '', $userID = 0)
    {
        $this->im->chatHide($hide, $gid, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Toggle chat fail.'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = $userID;

		$output->data = new stdclass();
		$output->data->gid  = $gid;
		$output->data->hide = $hide;

		$this->im->sendOutput($output, 'chathideResponse');
    }

    /**
     * Mute a chat.
     *
     * @param  string $gid
     * @param  bool   $mute true: mute a chat | false: cacel mute a chat.
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatMute($mute = true, $gid = '', $userID = 0)
    {
        $this->im->chatMute($mute, $gid, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Mute chat fail.'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = $userID;

		$output->data = new stdclass();
		$output->data->gid  = $gid;
		$output->data->mute = $mute;

		$this->im->sendOutput($output, 'chatmuteResponse');
    }

    /**
     * Freeze a chat.
     *
     * @param  string $gid
     * @param  bool   $freeze true: freeze a chat | false: unfreeze a chat.
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatFreeze($freeze = true, $gid = '', $userID = 0)
    {
        $this->im->chatFreeze($freeze, $gid, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Set chat freeze fail.'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = $userID;

		$output->data = new stdclass();
		$output->data->gid    = $gid;
		$output->data->freeze = $freeze;

		$this->im->sendOutput($output, 'chatfreezeResponse');
    }

    /**
     * Set category for a chat
     *
     * @param  array $gids
     * @param  string $category
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatSetCategory($gids = array(), $category = '', $userID = 0)
    {
		$chatList = $this->im->chat->setCategory($gids, $category, $userID);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Set chat category fail.'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = $userID;

		$output->data = new stdclass();
		$output->data->gids     = $gids;
		$output->data->category = $category;

		$this->im->sendOutput($output, 'chatsetcategoryResponse');
	}

    /**
     * Invite members to a chat or kick members from a chat.
     *
     * @param  string $gid
     * @param  array  $members
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatInvite($gid = '', $members = array(), $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');
        if($chat->type != 'group') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');

        foreach($members as $member) $this->im->chatJoin($gid, $member);

        $chat->members = $this->im->chatGetMembers($gid);
        $users = $this->im->userGetList($status = 'online', $chat->members);

		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Invite chat member fail.'), 'messageResponsePack');

		$output = new stdclass();
        $output->result = 'success';
        $output->method = $this->app->getMethodName();
		$output->users  = array_keys($users);
		$output->data   = $chat;

		$broadcast = $this->im->messageCreateBroadcast('inviteUser', $chat, array_keys($users), $userID, $members);
        if($broadcast)
        {
            $output = array($output, $broadcast);
			$this->im->sendOutputGroup($output);
        }

		$this->im->sendOutput($output);
    }

    /**
     * Kick members from a chat.
     *
     * @param  string $gid
     * @param  array  $users
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatKick($gid = '', $users = array(), $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid);
        if(!$chat) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notExist), 'messageResponsePack');

        if($chat->type != 'group') $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notGroupChat), 'messageResponsePack');

        foreach($users as $user) $this->im->chatLeave($gid, $user);

        $chat->members = $this->im->chatGetMembers($gid);
        $members = $this->im->userGetList($status = 'online', $chat->members);
        $members = array_keys($members);
        $members = array_merge($members, $users);

		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Kick chat member fail.'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = $members;
		$output->data   = $chat;

		$this->im->sendOutput($output, 'chatkickResponse');
    }

    /**
     * Get history messages of a chat.
     *
     * @param  string $gid
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @param  int    $recTotal
     * @param  bool   $continued
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function chatGetHistory($gid = '', $recPerPage = 20, $pageID = 1, $recTotal = 0, $continued = false, $startDate = 0, $userID = 0)
    {
        if($startDate) $startDate = date('Y-m-d H:i:s', $startDate);

        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        if($gid)
        {
            $messageList = $this->im->message->getListByCgid($gid,  $pager, $startDate);
        }
        else
        {
            $messageList = $this->im->message->getList($idList = array(), $pager, $startDate);
        }

        if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Get history fail'), 'messageResponsePack');

		$output = new stdclass();
		$output->result = 'success';
		$output->users  = array($userID);
		$output->data   = $messageList;

		$output->pager = new stdclass();
		$output->pager->recPerPage = $pager->recPerPage;
		$output->pager->pageID     = $pager->pageID;
		$output->pager->recTotal   = $pager->recTotal;
		$output->pager->gid        = $gid;
		$output->pager->continued  = $continued;
		$this->im->sendOutput($output, 'chatgethistoryResponse');
	}

    /**
     * Retract a message.
     *
     * @param  array  $messages
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function messageRetract($messages = array(), $userID = 0)
	{
		$chats = array();

        foreach($messages as $key => $message)
        {
            $message = (object) $message;
            $chats[$message->cgid] = $message->cgid;
            if(isset($message->type) && $message->type == 'broadcast') unset($messages[$key]);
        }

        $message     = (object) current($messages);
        $chat        = $this->im->chat->getByGid($message->cgid, $getMembers = true);
        $onlineUsers = $this->im->userGetList($status = 'online', $chat->members);

		$messages = $this->im->messageRetract($message->gid);
		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Retract message fail.'), 'messageResponsePack');

        $output = new stdclass();
		$output->result = 'success';
		$output->users  = array_keys($onlineUsers);
		$output->data   = $messages;
		$this->im->sendOutput($output, 'messageretractResponse');
	}

    /**
     * Send message to a chat.
     *
     * @param  array  $messages
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function messageSend($messages = array(), $userID = 0)
    {
        /* Check if the messages belong to the same chat. */
        $chats = array();
        foreach($messages as $key => $message)
        {
            $message = (object) $message;
            $chats[$message->cgid] = $message->cgid;
            if(isset($message->type) && $message->type == 'broadcast') unset($messages[$key]);
        }

        $message = (object) current($messages);

        $members = explode('&', $message->cgid);
        $isOne2OneChat = (count($members) == 2);

        if($message->user != $userID) $this->im->sendOutput(array('result' => 'fail', 'message' => $this->lang->im->notSameUser), 'messageResponsePack');

        $chat = $this->im->chat->getByGid($message->cgid, $getMembers = true);

        $newChat = false;
        if(!$chat && $isOne2OneChat)
        {
			$newChat = true;
            $chat    = $this->im->chatcreate($message->cgid, '', 'one2one', $members, 0, false, $userID);
            if(dao::isError())
            {
				$this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'Create chat fail.'), 'messageResponsePack');
            }
        }

		/* Check whether the logon user can send message in chat. */
		$isCommitter = $this->im->chatIsCommitter($message, $userID, $chat);
		if($isCommitter !== true) $this->im->sendOutput($isCommitter, 'messageResponsePack');

        $onlineUsers  = array($userID);
        $offlineUsers = array();
        $users = $this->im->userGetList($status = '', $chat->members);
        foreach($users as $id => $user)
        {
            if($id == $userID) continue;
            if($user->clientStatus == 'offline') $offlineUsers[] = $id;
            if($user->clientStatus != 'offline') $onlineUsers[]  = $id;
        }

		/* Create messages. */
		$messages = $this->im->messageCreate($messages, $userID);
		$this->im->messageSaveOfflineList($messages, $offlineUsers);

		/* push message to offline users */

		if(dao::isError()) $this->im->sendOutput(array('result' => 'fail', 'message' => 'Send message fail'), 'messageResponsePack');

		$output = new stdclass();
        $output->result = 'success';
        $output->method = 'messagesend';
		$output->users  = $onlineUsers;
		$output->data   = $messages;

		if($newChat)
		{
			$chatOutput = new stdclass();
			$chatOutput->module = 'im';
			$chatOutput->method = 'chatcreate';
			$chatOutput->result = 'success';
			$chatOutput->users  = $onlineUsers;
			$chatOutput->data   = $chat;

			$this->im->sendOutputGroup(array($chatOutput, $output));
        }

		$this->im->sendOutput($output, 'messagesendResponse');
    }

    /**
     * Create a conference for a chat.
     *
     * @param  string $chatID
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function conferenceCreate($chatID, $userID)
    {
        $chat = $this->im->chatGetByGid($chatID, $getMembers = true);
        if(empty($chat)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'No such chat.'), 'messageResponsePack');

        $conference = $this->im->conferenceCreate($chatID, $userID);
        if(empty($conference)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'Create conference fail.'), 'messageResponsePack');

        $participants = explode(',', $conference->participants);
        if(count($participants) > 1)
        {
            $conferenceAction = new stdClass();
            $conferenceAction->room         = $conference->rid;
            $conferenceAction->type         = 'join';
            $conferenceAction->participants = $conference->participants;
            $conferenceAction->date         = strtotime(helper::now());
            $conferenceAction->user         = (int)$userID;
            $this->im->sendOutput(array('result' => 'success', 'users' => $chat->members, 'data' => $conferenceAction), 'conferencejoinResponse');
        }

        $conferenceData = new stdClass();
        $conferenceData->cgid         = $chatID;
        $conferenceData->room         = $conference->rid;
        $conferenceData->status       = $conference->status;
        $conferenceData->openedBy     = (int)$userID;
        $conferenceData->openedDate   = strtotime(helper::now());
        $conferenceData->participants = $conference->participants;

        $conferenceAction = new stdClass();
        $conferenceAction->room         = $conferenceData->room;
        $conferenceAction->type         = 'create';
        $conferenceAction->participants = $conferenceData->participants;
        $conferenceAction->date         = $conferenceData->openedDate;
        $conferenceAction->user         = (int)$userID;

        $conferenceData->actions = array($conferenceAction);

        $output = new stdClass();
        $output->method = 'conferencecreate';
        $output->users  = $chat->members;
        $output->result = 'success';
        $output->data   = $conferenceData;

        $broadcast = $this->im->messageCreateBroadcast('createConference', $chat, $chat->members, $userID);
        if($broadcast) $output = array($output, $broadcast);

        $this->im->sendOutputGroup($output);
    }

    /**
     * Join a conference in a chat.
     *
     * @param  string $chatID
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function conferenceJoin($chatID, $userID)
    {
        $chat = $this->im->chatGetByGid($chatID, $getMembers = true);
        if(empty($chat)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'No such chat.'), 'messageResponsePack');

        $conference = $this->im->conferenceGetByChatID($chatID);
        if(empty($conference)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'No such conference.'), 'messageResponsePack');

        $participants = $this->im->conferenceAddParticipant($chatID, $userID);
        if(empty($participants)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'Could not join the conference.', 'data' => $chatID), 'messageResponsePack');

        $this->im->conferenceSaveAction($conference->rid, 'join', $userID);

        $conferenceAction = new stdClass();
        $conferenceAction->room         = $conference->rid;
        $conferenceAction->type         = 'join';
        $conferenceAction->participants = $participants;
        $conferenceAction->date         = strtotime(helper::now());
        $conferenceAction->user         = (int)$userID;

        $this->im->sendOutput(array('result' => 'success', 'users' => $chat->members, 'data' => $conferenceAction), 'conferencejoinResponse');
    }

    /**
     * Leave a conference in a chat.
     *
     * @param  string $chatID
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function conferenceLeave($chatID, $userID)
    {
        $chat = $this->im->chatGetByGid($chatID, $getMembers = true);
        if(empty($chat)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'No such chat.'), 'messageResponsePack');

        $conference = $this->im->conferenceGetByChatID($chatID);
        if(empty($conference)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'No such conference.'), 'messageResponsePack');

        $participants = $this->im->conferenceRemoveParticipant($chatID, $userID);
        $this->im->conferenceSaveAction($conference->rid, 'leave', $userID);

        $conferenceAction = new stdClass();
        $conferenceAction->room         = $conference->rid;
        $conferenceAction->type         = 'leave';
        $conferenceAction->participants = $participants;
        $conferenceAction->date         = strtotime(helper::now());
        $conferenceAction->user         = (int)$userID;

        $participants = explode(',', $participants);
        $participants = array_filter($participants);
        if(empty($participants) || count($participants) == 0)
        {
            $this->im->conferenceClose($chatID, $userID);
            $conferenceAction->type         = 'close';
            $conferenceAction->participants = array();

            $output = new stdClass();
            $output->method = 'conferenceclose';
            $output->users  = $chat->members;
            $output->result = 'success';
            $output->data   = $conferenceAction;

            $broadcast = $this->im->messageCreateBroadcast('closeConference', $chat, $chat->members, $userID);
            if($broadcast) $output = array($output, $broadcast);

            $this->im->sendOutputGroup($output);
        }

        $this->im->sendOutput(array('result' => 'success', 'users' => $chat->members, 'data' => $conferenceAction), 'conferenceleaveResponse');
    }

    /**
     * Close a conference in a chat.
     *
     * @param  string $chatID
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function conferenceClose($chatID, $userID)
    {
        $chat = $this->im->chatGetByGid($chatID, $getMembers = true);
        if(empty($chat)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'No such chat.'), 'messageResponsePack');

        $conference = $this->im->conferenceGetByChatID($chatID);
        if(empty($conference)) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'No such conference.'), 'messageResponsePack');

        $closeResult = $this->im->conferenceClose($chatID, $userID);
        if(!$closeResult) $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'Close conference failed.'), 'messageResponsePack');

        $conferenceAction = new stdClass();
        $conferenceAction->room         = $conference->rid;
        $conferenceAction->type         = 'close';
        $conferenceAction->participants = '';
        $conferenceAction->date         = strtotime(helper::now());
        $conferenceAction->user         = (int)$userID;

        $output = new stdClass();
        $output->method = 'conferenceclose';
        $output->users  = $chat->members;
        $output->result = 'success';
        $output->data   = $conferenceAction;

        $broadcast = $this->im->messageCreateBroadcast('closeConference', $chat, $chat->members, $userID);
        if($broadcast) $output = array($output, $broadcast);

        $this->im->sendOutputGroup($output);
    }

    /**
     * Get extensions.
     *
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function extensionGetList($userID = 0)
    {
        $output = new stdclass();
        $output->result = 'success';
        $output->data   = $this->im->getExtensionList($userID);
        $output->users  = array($userID);
		$this->im->sendOutput($output, 'extensiongetlistResponse');
    }

    /**
     * Get latest notification and offline user.
     * @param array $offline
     * @param array $sendfail
     * @access public
     * @return void
     */
    public function syncNotifications($offline = array(), $sendfail = array())
    {
        if(!empty($offline))  $this->im->userSetOffline($offline);
        if(!empty($sendfail)) $this->im->messageSendFailures($sendfail);

        $output = new stdClass();
        if(dao::isError())
        {
            $this->output->result  = 'fail';
            $this->output->message = 'Get notify fail.';
        }
        else
        {
            $output->result = 'success';
            $output->data   = $this->im->messageGetNotifyList();
        }
        die($this->app->encrypt($output));
    }

    /**
     * Check user change.
     *
     * @access public
     * @return void
     */
    public function syncUsers()
    {
        $this->im->updateLastPoll();

        $this->output = new stdClass();
        $this->output->result = 'success';
        $this->output->data   = $this->im->userHasChanges();
        die($this->app->encrypt($this->output));
    }

    /**
     * Upload file.
     *
     * @param  string $fileName
     * @param  string $path
     * @param  int    $size
     * @param  int    $time
     * @param  string $gid
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function fileUpload($fileName = '', $path = '', $size = 0, $time = 0, $gid = '', $userID = 0)
    {
        $chat = $this->im->chat->getByGid($gid, true);
        if(!$chat) die($this->app->encrypt(array('result' => 'fail', 'message' => $this->lang->im->notExist)));

        $users  = $this->im->userGetList($status = 'online', $chat->members);
        $fileID = $this->im->uploadFile($fileName, $path, $size, $time, $userID, $users, $chat);

		if(dao::isError()) die($this->app->encrypt(array('result' => 'fail', 'message' => 'Upload file fail.')));
		$output = new stdclass();
		$output->result = 'success';
		$output->users  = array_keys($users);
		$output->data   = $fileID;
        die($this->app->encrypt($output));
    }

    /**
     * Create, edit or delte todo
     * @param  object $todo
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function todoUpsert($todo, $userID = 0)
    {
        $user = $this->im->userGetByID($userID);
        if(is_object($todo))
        {
            if(isset($todo->id))
            {
                if($todo->delete)
                {
                    $todo = $this->loadModel('todo')->getById($todo->id);
                    if($todo->account != $user->account)
                    {
                        $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'Cannot delete todo item witch not yours.', 'data' => $todo), 'messageResponsePack');
                    }
                    else
                    {
                        $this->dao->delete()->from(TABLE_TODO)->where('id')->eq($todo->id)->exec();
                        if(dao::isError())
                        {
                            $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => dao::getError()), 'messageResponsePack');
                        }
                        else
                        {
                            $this->loadModel('action')->create('todo', $todo->id, 'deleted', '', 'xuanxuan', $user->account);

                            $output = new stdClass();
                            $output->result = 'success';
                            $output->users  = array($userID);
                            $output->data   = $todo;
                            $this->im->sendOutput($output, 'todoupsertResponse');
                        }
                    }
                }
                else
                {
                    $_POST = (array)$todo;
                    $changes = $this->loadModel('todo')->update($todo->id);
                    if(dao::isError())
                    {
                        $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => dao::getError()), 'messageResponsePack');
                    }
                    else
                    {
                        $actionID = $this->loadModel('action')->create('todo', $todo->id, 'edited', '', 'xuanxuan', $user->account);
                        $this->action->logHistory($actionID, $changes);

                        $output = new stdClass();
                        $output->result = 'success';
                        $output->users  = array($userID);
                        $output->data   = $todo;
                        $this->im->sendOutput($output, 'todoupsertResponse');
                    }
                }
            }
            else
            {
                $_POST  = (array)$todo;
                $todoID = $this->loadModel('todo')->create($todo->date, $user->account);
                if(dao::isError())
                {
                    $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => dao::getError()), 'messageResponsePack');
                }
                else
                {
                    $this->loadModel('action')->create('todo', $todoID, 'created', '', 'xuanxuan', $user->account);
                    $todo->id = $todoID;

                    $output = new stdClass();
                    $output->result = 'success';
                    $output->users  = array($userID);
                    $output->data   = $todo;
                    $this->im->sendOutput($output, 'todoupsertResponse');
                }
            }
        }
        else
        {
            $this->im->sendOutput(array('result' => 'fail', 'users' => $userID, 'message' => 'The todo param is not an object.'), 'messageResponsePack');
        }
    }

    /**
     * Get todo list.
     *
     * @param  string $mode
     * @param  string $orderBy
     * @param  string $status
     * @param  int    $recTotal
     * @param  int    $recPerPage
     * @param  int    $pageID
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function todoGetList($mode = 'all', $status = 'unclosed', $orderBy = 'date_asc', $recTotal = 0, $recPerPage = 20, $pageID = 1, $userID = 0)
    {
        $user = $this->im->userGetByID($userID);
        $this->app->loadClass('pager', $static = true);
        $pager = new pager($recTotal, $recPerPage, $pageID);

        if($mode == 'future')
        {
            $todos = $this->loadModel('todo')->getList('self', $user->account, 'future', empty($status) ? 'unclosed' : $status, $orderBy, $pager);
        }
        else if($mode == 'all')
        {
            $todos = $this->loadModel('todo')->getList('self', $user->account, 'all', empty($status) ? 'all' : $status, $orderBy, $pager);
        }
        else if($mode == 'undone')
        {
            $todos = $this->loadModel('todo')->getList('self', $user->account, 'before', empty($status) ? 'undone' : $status, $orderBy, $pager);
        }
        else
        {
            $todos = $this->loadModel('todo')->getList($mode, $user->account, 'all', empty($status) ? 'unclosed' : $status, $orderBy, $pager);
        }

        $this->output->data   = $todos;
        $this->output->result = 'success';
        $this->output->users  = array($userID);
        die($this->app->encrypt($this->output));
    }

    /**
     * Get chat group pairs.
     *
     * @access public
     * @return void
     */
    public function getGroupChats()
    {
        $response = array();
        $response['result'] = 'success';

        $groupPairs = $this->im->chatGetGroupPairs();
        if(dao::isError())
        {
            $response['result']  = 'fail';
            $response['message'] = dao::getError();
        }
        else
        {
            $response['data'] = $groupPairs;
        }

        die(json_encode($response));
    }

    /**
     * Get all user pairs or users of one chat group.
     *
     * @param  string $gid
     * @access public
     * @return void
     */
    public function getChatUsers($gid = '')
    {
        $response = array();
        $response['result'] = 'success';

        $userPairs = $this->im->chatGetUserPairs($gid);

        if(dao::isError())
        {
            $response['result']  = 'fail';
            $response['message'] = dao::getError();
        }
        else
        {
            $response['data'] = $userPairs;
        }

        die(json_encode($response));
    }

    /**
     * Send notification to users' notification center.
     *
     * @access public
     * @return void
     */
    public function sendNotification()
    {
        /* Parse input data. */
        $input = file_get_contents("php://input");
        $data = json_decode($input);

        $response = array('result' => 'success', 'message' => '');

        if(empty($data->users))
        {
            $response['result']  = 'fail';
            $response['message'] = $this->lang->im->notify->setUserList;
            die(json_encode($response));
        }

        if(empty($data->sender))
        {
            $response['result']  = 'fail';
            $response['message'] = $this->lang->im->notify->setSender;
            die(json_encode($response));
        }

        if(empty($data->title))
        {
            $response['result']  = 'fail';
            $response['message'] = $this->lang->im->notify->setTitle;
            die(json_encode($response));
        }

        $users       = $data->users;
        $sender      = $data->sender;
        $title       = $data->title;
        $subtitle    = $data->subtitle ?: '';
        $content     = $data->content ?: '';
        $contentType = $data->contentType ?: 'text';
        $url         = $data->url ?: '';
        $actions     = $data->actions ?: array();

        $result = $this->im->messageCreateNotify($users, $title, $subtitle, $content, $contentType, $url, $actions, $sender);
        if(!$result)
        {
            $response['result']  = 'fail';
            $response['message'] = dao::getError();
        }

        die(json_encode($response));
    }

    /**
     * Send notification to users' notification center.
     *
     * @access public
     * @return void
     */
    public function sendChatMessage()
    {
        /* Parse input data. */
        $input = file_get_contents("php://input");
        $data = json_decode($input);

        $response = array('result' => 'success', 'message' => '');

        if(empty($data->gid))
        {
            $response['result']  = 'fail';
            $response['message'] = $this->lang->im->notify->setGroup;
            die(json_encode($response));
        }

        if(empty($data->sender))
        {
            $response['result']  = 'fail';
            $response['message'] = $this->lang->im->notify->setSender;
            die(json_encode($response));
        }

        if(empty($data->title))
        {
            $response['result']  = 'fail';
            $response['message'] = $this->lang->im->notify->setTitle;
            die(json_encode($response));
        }

        $gid         = $data->gid;
        $sender      = $data->sender;
        $title       = $data->title;
        $subtitle    = $data->subtitle ?: '';
        $content     = $data->content ?: '';
        $contentType = $data->contentType ?: 'text';
        $url         = $data->url ?: '';
        $actions     = $data->actions ?: array();

        $result = $this->im->messageCreateNotify($gid, $title, $subtitle, $content, $contentType, $url, $actions, $sender);
        if(!$result)
        {
            $response['result']  = 'fail';
            $response['message'] = dao::getError();
        }

        die(json_encode($response));
    }

    /**
     * Sync foreign user data into xuan database.
     *
     * @access public
     * @return void
     */
    public function syncUsersData()
    {
        /* Parse input data, array of users. */
        $input = file_get_contents("php://input");
        $data = json_decode($input);

        $this->loadModel('user');

        /* Get existing user list. */
        $existingAccounts = array_map(function($u) {return $u->account;}, $this->user->getList($dept = null, $mode = 'all'));

        $response = array('result' => 'success', 'message' => '', 'fails' => array());

        /* Get departments mapping configuration. */
        $deptsMapping = isset($this->config->im->depts) ? json_decode($this->config->im->depts->mapping) : new stdClass();

        foreach($data as $user)
        {
            /* Try to find dept id in mapping configuration and replace. */
            if(isset($user->dept) && isset($deptsMapping->{$user->dept})) $user->dept = $deptsMapping->{$user->dept};

            if(in_array($user->account, $existingAccounts))
            {
                /* Update user with new data. */
                $result = $this->user->apiUpdate($user);
            }
            else
            {
                /* Create user with the data. */
                $result = $this->user->apiCreate($user);
            }

            /* Push account to fails on failure. */
            if(!$result) $response['fails'][] = $user->account;
        }

        if (!empty($response['fails']))
        {
            $response['result']  = 'fail';
            $response['message'] = dao::getError();
        }
        else
        {
            unset($response['fails']);
            unset($response['message']);
        }

        die(json_encode($response));
    }

    /**
     * Sync foreign department data into xuan database.
     *
     * @access public
     * @return void
     */
    public function syncDeptsData()
    {
        /* Parse post data, array of depts. */
        $input = file_get_contents("php://input");
        $data = json_decode($input);

        $response = array('result' => 'success', 'message' => '', 'fails' => array());

        /* Fetch depts mapping table. */
        $mapping = isset($this->config->im->depts) ? json_decode($this->config->im->depts->mapping) : new stdClass();

        $this->loadModel('dept');
        foreach($data as $dept)
        {
            /* Set type as dept. */
            $dept->type = 'dept';

            /* If parent is set, use parent's mapping id. */
            if($dept->parent != 0) $dept->parent = $mapping->{$dept->parent};

            /* Create or update a dept and get its record id. */
            $deptRecordID = $this->dept->apiUpsertDept($dept, isset($mapping->{$dept->id}) ? $mapping->{$dept->id} : null);

            /* Break on error. */
            if(!$deptRecordID)
            {
                $response['fails'][] = $dept->id;
                continue;
            }

            /* Store acutal record id in $mapping. */
            $mapping->{$dept->id} = $deptRecordID;
        }

        /* Save mapping configuration. */
        $this->loadModel('setting')->setItem('system.im.depts.mapping', json_encode($mapping));

        if (!empty($response['fails']))
        {
            $response['result']  = 'fail';
            $response['message'] = dao::getError();
        }
        else
        {
            unset($response['fails']);
            unset($response['message']);
        }

        die(json_encode($response));
    }

    /**
     * Debug xuanxuan.
     *
     * @access public
     * @return void
     */
    public function debug($source = 'x_php')
    {
        if(RUN_MODE != 'front') die('Access Denied');

        $this->view->title          = $this->lang->im->debug;
        $this->view->source         = $source;
        $this->view->xxdStatus      = $this->im->getXxdStatus();
        $this->view->checkXXBConfig = $this->im->checkXXBConfig();
        $this->display();
    }

    /**
     * Read content of log file and display.
     *
     * @access public
     * @return void
     */
    public function showLog()
    {
        $logFile = $this->app->getLogRoot() . 'xuanxuan.' . date('Ymd') . '.log.php';
        if(!file_exists($logFile)) $this->send(array('result' => 'fail', 'message' => $this->lang->im->noLogFile));

        if(!function_exists('fopen')) $this->send(array('result' => 'fail', 'message' => $this->lang->im->noFopen));

        $line = $this->config->im->logLine;
        $pos  = -2;
        $eof  = '';
        $log  = '';
        $fp   = fopen($logFile, 'r');
        while($line > 0)
        {
            while($eof != "\n")
            {
                if(!fseek($fp, $pos, SEEK_END))
                {
                    $eof = fgetc($fp);
                    $pos--;
                }
                else
                {
                    break;
                }
            }
            $log .= fgets($fp) . '<br>';
            $eof  = '';
            $line--;
        }

        $this->send(array('result' => 'success', 'logs' => $log));
    }

    /**
     * Update last polling time record.
     *
     * @access public
     * @return void
     */
    public function updateLastPoll()
    {
        $this->im->updateLastPoll();
        $this->im->sendOutput(array('result' => 'success'));
    }

    /**
     * Check for users that changed their password but did not re-login.
     *
     * @access public
     * @return void
     */
    public function checkPasswordChanges()
    {
        $output = new stdClass();
        $output->module = 'im';
        $output->method = 'checkpasswordchanges';
        $output->result = 'success';
        $output->data   = $this->im->getKickList();
        die($this->app->encrypt($output));
    }

    /**
     * Donwload XXD package.
     *
     * @param  string xxd package name.
     * @access public
     * @return void
     */
    public function downloadXxdPackage($xxdFileName)
    {
        set_time_limit(0);
        $version      = $this->config->xuanxuan->version;
        $xxdDirectory = $this->app->tmpRoot . 'xxd' . DS . $version;
        $xxdFile      = fopen($xxdDirectory . DS . $xxdFileName, 'rb');

        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize ($xxdDirectory . DS . $xxdFileName));
        Header("Content-Disposition: attachment; filename=" . $xxdFileName);

        echo fread($xxdFile, filesize($xxdDirectory . DS . $xxdFileName));
        fclose($xxdFile);
        die();
    }

    /**
     * Authorize and redirect user to url.
     *
     * @param  string $account
     * @param  string $token
     * @param  string $url
     * @return void
     */
    public function authorize($account = '', $token = '', $url = '')
    {
        if(empty($account) || empty($token)) die('Invalid params. Please provide account, token and url.');

        $user = $this->loadModel('user')->identifyByToken($account, $token);
        if(!$user) die('Invalid token.');

        if(empty($url)) die('Authorized, but no url to redirect to.');
        header("Location: $url");
        die();
    }
}
