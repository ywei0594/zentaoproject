<?php
class message extends model
{
    /**
     * Get message list.
     *
     * @param  array   $idList
     * @param  object  $pager
     * @param  string  $startDate
     * @param  int     $userID
     * @access public
     * @return array
     */
    public function getList($idList = array(), $pager = null, $startDate = '', $userID = null)
    {
        $messages = $this->dao->select('*')
            ->from(TABLE_IM_MESSAGE)
            ->where('1')
            ->beginIF($idList)->andWhere('id')->in($idList)->fi()
            ->beginIF($startDate)->andWhere('date')->ge($startDate)->fi()
            ->beginIF($userID != null)->andWhere('user')->eq($userID)->fi()
            ->orderBy('id_desc')
            ->beginIF($pager != null)->page($pager)->fi()
            ->fetchAll();

        return $this->format($messages);
    }

    /**
     * Format messages.
     *
     * @param  mixed  $messages  object | array
     * @access public
     * @return object | array
     */
    public function format($messages)
    {
        $isObject = false;
        if(is_object($messages))
        {
            $isObject = true;
            $messages = array($messages);
        }

        $messageList = array();
        foreach($messages as $message)
        {
            $message->id      = (int)$message->id;
            $message->order   = (int)$message->order;
            $message->user    = (int)$message->user;
            $message->date    = strtotime($message->date);
            $message->deleted = isset($message->deleted) ? (bool)$message->deleted : false;
            $messageList[$message->gid] = $message;
        }

        if($isObject) return reset($messageList);

        return $messageList;
    }

    /**
     * Create messages.
     *
     * @param  array  $messageList
     * @param  int    $userID
     * @access public
     * @return array
     */
    public function create($messageList = array(), $userID = 0)
    {
        $idList   = array();
        $chatList = array();
        foreach($messageList as $message)
        {
            $message = (object) $message;
            $msg     = $this->dao->select('*')->from(TABLE_IM_MESSAGE)->where('gid')->eq($message->gid)->fetch();
            if($msg)
            {
                if($msg->contentType == 'image' || $msg->contentType == 'file')
                {
                    $this->dao->update(TABLE_IM_MESSAGE)->set('content')->eq($message->content)->where('id')->eq($msg->id)->exec();
                }
                $idList[] = $msg->id;
            }
            elseif(!$msg)
            {
                if(!(isset($message->user) && $message->user)) $message->user = $userID;
                if(!(isset($message->date) && $message->date)) $message->date = helper::now();

                $this->dao->insert(TABLE_IM_MESSAGE)->data($message)->exec();
                $idList[] = $this->dao->lastInsertID();
            }
            $chatList[$message->cgid] = $message->cgid;
        }
        if(empty($idList)) return array();

        $this->dao->update(TABLE_IM_CHAT)->set('lastActiveTime')->eq(helper::now())->where('gid')->in($chatList)->exec();

        return $this->getList($idList);
    }

    /**
     * Get message list by cgid.
     *
     * @param  string $cgid
     * @param  object $pager
     * @param  string $startDate
     * @access public
     * @return array
     */
    public function getListByCgid($cgid = '', $pager = null, $startDate = '')
    {
        $messages = $this->dao->select('*')->from(TABLE_IM_MESSAGE)
            ->where('cgid')->eq($cgid)
            ->beginIF($startDate)->andWhere('date')->ge($startDate)->fi()
            ->orderBy('id_desc')
            ->beginIF($pager != null)->page($pager)->fi()
            ->fetchAll();

        return $this->format($messages);
    }

    /**
     * Get offline messages.
     *
     * @param  int    $userID
     * @access public
     * @return array
     */
    public function getOfflineList($userID = 0)
    {
        $messages = $this->dao->select('t2.*')->from(TABLE_IM_MESSAGESTATUS)->alias('t1')
            ->leftJoin(TABLE_IM_MESSAGE)->alias('t2')->on('t2.id = t1.message')
            ->where('t1.user')->eq($userID)
            ->andWhere('t1.status')->eq('waiting')
            ->andWhere('t2.type')->ne('notify')
            ->orderBy('t2.order desc, t2.id desc')
            ->fetchAll();
        if(empty($messages)) return array();

        $this->dao->delete()->from(TABLE_IM_MESSAGESTATUS)
            ->where('user')->eq($userID)
            ->andWhere('status')->eq('waiting')
            ->exec();

        return $this->format($messages);
    }

    /**
     * Get history list.
     *
     * @param  object   $user
     * @param  string   $device
     * @access public
     * @return void
     */
    public function getHistoryList($user, $device = 'desktop')
    {
        $gids      = $this->loadModel('im')->chat->getGidListByUserID($user->id);
        $messages  = array();
        $startDate = $this->loadModel('setting')->getItem("owner={$user->account}&module=common&section=lastLogin&key={$device}");

        if(!empty($startDate) && !empty($gids))
        {
            $messages = $this->dao->select('*')->from(TABLE_IM_MESSAGE)
                ->where('cgid')->in($gids)
                ->beginIF($startDate)->andWhere('date')->ge($startDate)->fi()
                ->orderBy('id_desc')->limit(500)
                ->fetchAll();

            if(empty($messages)) return null;

            $messages = $this->format($messages);
        }

        return $messages;
    }


    /**
     * Create a output of broadcast.
     *
     * @param  string $type
     * @param  object $chat
     * @param  array  $onlineUsers
     * @param  int    $userID
     * @param  array  $members
     * @access public
     * @return object
     */
    public function createBroadcast($type, $chat, $onlineUsers, $userID, $members = array())
    {
        $adminUsers = array();

        $message = new stdclass();
        $message->gid         = imModel::createGID();
        $message->cgid        = $chat->gid;
        $message->type        = 'broadcast';
        $message->contentType = 'text';
        $message->content     = $this->getBroadcastContent($type, $chat, $userID, $members);
        $message->date        = helper::now();
        $message->user        = $userID;
        $message->order       = 1;

        /* If quit a chat, only send broadcast to the admins or the created user of chat. */
        if($type == 'leaveChat')
        {
            if($chat->admins) $adminUsers = explode(',', trim($chat->admins, ','));
            if(!$adminUsers)
            {
                $user = $this->loadModel('user')->getByAccount($chat->createdBy);
                if($user) $adminUsers = array($user->id);
            }
            $users       = $this->loadModel('im')->user->getList($status = 'online', $adminUsers);
            $onlineUsers = array_keys($users);
        }

        /* Save broadcast to im_message. */
        $messages     = $this->create(array($message), $userID);
        $offlineUsers = $this->loadModel('im')->user->getList($status = 'offline', $adminUsers);
        $this->saveOfflineList($messages, array_keys($offlineUsers));

        $output = new stdclass();
        $output->method = 'messagesend';

        if(dao::isError())
        {
            $output->result  = 'fail';
            $output->message = 'Send message failed.';
        }
        else
        {
            $output->result = 'success';
            $output->users  = $onlineUsers;
            $output->data   = $messages;
        }

        return $output;
    }

	/**
     * Get content of broadcast.
     *
     * @param  string $type
     * @param  object $chat
     * @param  int    $userID
     * @param  array  $members
     * @access public
     * @return string
     */
    public function getBroadcastContent($type, $chat, $userID, $members)
    {
        $user = $this->loadModel('im')->userGetByID($userID);

        if($type == 'createChat' or $type == 'renameChat')
        {
            $nameInMarkdown = preg_replace('/([#\\`*_{}\[\]\(\)\+\-\.!])/i', '\\\\$1', $chat->name);
            return sprintf($this->lang->im->broadcast->$type, $user->account, $nameInMarkdown, $chat->gid);
        }

        if($type == 'inviteUser')
        {
            $memberIDs = array();
            foreach($members as $member) $memberIDs[] = '@#' . $member;
            $memberIDs = implode($this->lang->im->connector, $memberIDs);

            return sprintf($this->lang->im->broadcast->$type, $user->account, $memberIDs);
        }

        return sprintf($this->lang->im->broadcast->$type, $user->account);
    }

    /**
     * Retract one message.
     *
     * @param  string $gid
     * @access public
     * @return array
     */
    public function retract($gid = '')
    {
        $message = $this->dao->select('id, gid, cgid, user, date, `order`, deleted, type, contentType')->from(TABLE_IM_MESSAGE)->where('gid')->eq($gid)->fetch();

        $messageLife = (strtotime(helper::now()) - strtotime($message->date)) / 60;
        if($messageLife <= $this->config->im->retract->validTime)
        {
            $message->deleted = 1;
            $this->dao->update(TABLE_IM_MESSAGE)->set('deleted')->eq($message->deleted)->where('gid')->eq($gid)->exec();
        }

        $message->date  = strtotime($message->date);
        $message->order = (int)($message->order);
        $message->id    = (int)($message->id);
        $message->user  = (int)($message->user);

        return array($message);
    }

    /**
     * Save offline messages.
     *
     * @param  array  $messages
     * @param  array  $users
     * @access public
     * @return bool
     */
    public function saveOfflineList($messages = array(), $users = array())
    {
        /* Prevent deleted users from being stored in TABLE_MESSAGESTATUS. */
        $deletedUsers = $this->dao->select('id')->from(TABLE_USER)->where('deleted')->eq('1')->fetchPairs();
        $users = array_values(array_diff($users, $deletedUsers));

        foreach($messages as $message)
        {
            $this->batchCreateMessageStatus($users, $message->id, 'waiting');
        }
        return !dao::isError();
    }

    /**
     * Save message status.
     *
     * @param  array    $users
     * @param  int      $message
     * @param  string   $status
     * @access public
     * @return bool
     */
    public function saveStatus($users, $message, $status = 'waiting')
    {
        if(empty($users) || empty($message)) return false;

        foreach($users as $user)
        {
            $data = new stdClass();
            $data->user    = $user;
            $data->message = $message;
            $data->status  = $status;
            $this->dao->replace(TABLE_IM_MESSAGESTATUS)->data($data)->exec();
        }

        return !dao::isError();
    }

    /**
     * Get notify.
     * @access public
     * @return array
     */
    public function getNotifyList()
    {
        $onlineUsers = $this->loadModel('im')->user->getList('online');
        if(empty($onlineUsers)) return array();
        $onlineUsers = array_keys($onlineUsers);

        $msgQuery = $this->dao->select('t2.*')->from(TABLE_IM_MESSAGESTATUS)->alias('t1')
                ->leftJoin(TABLE_IM_MESSAGE)->alias('t2')->on('t2.id = t1.message')
                ->where('t1.status')->eq('waiting')
                ->andWhere('t2.type')->eq('notify')
                ->andWhere('t1.user')->in($onlineUsers)
                ->groupBy('t1.message')
                ->processSQL();
        $msgQuery .= ' oRdEr bY NULL';
        $messages = $this->dao->query($msgQuery)->fetchAll();

        if(empty($messages)) return array();

        $notifications = $this->formatNotify($messages);
        $data          = array();
        $messages      = array();
        foreach($notifications as $message)
        {
            foreach($onlineUsers as $userID)
            {
                if((empty($message->user) && empty($message->users)) || in_array($userID, $message->users))
                {
                    $messages[$userID][] = $message->id;
                    $data[$userID][]     = $message;
                }
            }
        }

        foreach($messages as $userID => $message)
        {
            $this->dao->delete()->from(TABLE_IM_MESSAGESTATUS)
                ->where('message')->in($message)
                ->andWhere('user')->eq($userID)
                ->exec();
        }
        return $data;
    }

    /**
     * Get offline notify.
     * @param $userID
     * @return array
     */
    public function getNotifyByUserID($userID)
    {
        $messages = $this->dao->select('t2.*')->from(TABLE_IM_MESSAGESTATUS)->alias('t1')
                ->leftjoin(TABLE_IM_MESSAGE)->alias('t2')->on("t2.id = t1.message")
                ->where('t1.user')->eq($userID)
                ->andWhere('t1.status')->eq('waiting')
                ->andWhere('t2.type')->eq('notify')
                ->fetchAll();

        if(empty($messages)) return array();
        $notifications = $this->formatNotify($messages);

        $messages = array();
        foreach($notifications as $message) $messages[] = $message->id;

        $this->dao->delete()->from(TABLE_IM_MESSAGESTATUS)
            ->where('message')->in($messages)
            ->andWhere('user')->eq($userID)
            ->exec();
        return $notifications;
    }

    /**
     * Foramt messages for notify.
     * @param object $messages
     * @access public
     * @return array
     */
    public function formatNotify($messages)
    {
        $notifications = array();
        foreach($messages as $message)
        {
            $data = new stdClass();
            $messageData = json_decode($message->data);
            $data->id          = $message->id;
            $data->gid         = $message->gid;
            $data->cgid        = $message->cgid;
            $data->type        = $message->type;
            $data->content     = $message->deleted ? '' : $message->content;
            $data->date        = strtotime($message->date);
            $data->contentType = $message->contentType;
            $data->title       = $messageData->title;
            $data->subtitle    = $messageData->subtitle;
            $data->url         = $messageData->url;
            $data->actions     = $messageData->actions;
            $data->sender      = $messageData->sender;
            $data->users       = $messageData->target;

            $notifications[] = $data;
        }
        return $notifications;
    }

    /**
     * Insert message for notify.
     * @param array  $target
     * @param string $title
     * @param string $subtitle
     * @param string $content
     * @param string $contentType
     * @param string $url
     * @param array  $actions
     * @param int    $sender
     * @access public
     * @return bool
     */
    public function createNotify($target = '', $title = '', $subtitle = '', $content = '', $contentType = 'text', $url = '', $actions = array(), $sender = 0)
	{
        /* Check if target is a chat gid or a list of users. */
        if(is_array($target))
        {
            $cgid = 'notification';
        }
        else
        {
            $cgid   = $target;
            $target = $this->dao->select('user')->from(TABLE_IM_CHATUSER)
                    ->where('cgid')->eq($target)
                    ->andWhere('quit')->eq('0000-00-00 00:00:00')
                    ->fetchPairs('user');
        }
        $users = $this->loadModel('im')->user->getList('', $target);

		$info = array();
		$info['title']    = $title;
		$info['subtitle'] = $subtitle;
		$info['url']	  = $url;
		$info['actions']  = $actions;
		$info['sender']	  = $sender;
		$info['target']	  = array_keys($users);

		$notify = new stdClass();
		$notify->gid		 = imModel::createGID();
		$notify->cgid		 = $cgid;
		$notify->user		 = 0;
		$notify->date		 = helper::now();
		$notify->order		 = 0;
		$notify->type		 = 'notify';
		$notify->content     = $content;
		$notify->contentType = $contentType;
		$notify->data		 = json_encode($info);

		$this->dao->insert(TABLE_IM_MESSAGE)->data($notify)->exec();
		$message = $this->dao->lastInsertID();
		$this->batchCreateMessageStatus($info['target'], $message, 'waiting');
        return !dao::isError();
    }

    /**
     * Add offline messages according to the gid of messages that failed to be sent.
     * @param array $sendfail
     * @access public
     * @return bool
     */
    public function sendFailures($sendfail = array())
    {
        foreach($sendfail as $userID => $gid)
        {
            if(empty($gid)) continue;
            $idList   = $this->dao->select('id')->from(TABLE_IM_MESSAGE)->where('gid')->in($gid)->fetchPairs();
            $messages = $this->getList($idList);
            $this->saveOfflineList($messages, array($userID));
        }
        return !dao::isError();
    }

    /**
     * Get message count for block.
     *
     * @param  string $date
     * @access public
     * @return array
     */
    public function getCountForBlock()
    {
        $totalSum = $this->dao->select("SUM(deleted = 0)")->from(TABLE_IM_MESSAGE)->fetchPairs();
        $daySum   = $this->dao->select("SUM(deleted = 0 AND date > '".date('Y-m-d H:i', strtotime('-1 day'))."')")->from(TABLE_IM_MESSAGE)->fetchPairs();
        $hourSum  = $this->dao->select("SUM(deleted = 0 AND date > '".date('Y-m-d H:i', strtotime('-1 hour'))."')")->from(TABLE_IM_MESSAGE)->fetchPairs();

        $count = new stdClass();
        $count->total = reset($totalSum);
        $count->day   = reset($daySum);
        $count->hour  = reset($hourSum);

        if(empty($count->total))
        {
            $count->total = 0;
            $count->day = 0;
            $count->hour = 0;
        }

        return $count;
    }

    /**
     * Batch create message status and store in TABLE_MESSAGESTATUS.
     *
     * @param  array   $users
     * @param  int     $message
     * @param  string  $status
     * @access public
     * @return void
     */
    public function batchCreateMessageStatus($users, $message, $status = 'waiting')
    {
        if(empty($users) || empty($message)) return false;
        foreach($users as $user)
        {
            $data = new stdClass();
            $data->user    = $user;
            $data->message = $message;
            $data->status  = $status;
            $this->dao->replace(TABLE_IM_MESSAGESTATUS)->data($data)->exec();
        }
        return !dao::isError();
    }
}
