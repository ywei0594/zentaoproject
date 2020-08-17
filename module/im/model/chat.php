<?php
class chat extends model
{
    /**
     * Get a chat by gid.
     *
     * @param  string $gid
     * @param  bool   $members
     * @access public
     * @return object
     */
    public function getByGid($gid = '', $getMembers = false)
    {
        $chat = $this->dao->select('*')->from(TABLE_IM_CHAT)->where('gid')->eq($gid)->fetch();

        if($chat)
        {
            $chat = $this->format($chat);
            if($getMembers) $chat->members = $this->getMembers($gid);
        }

        return $chat;
    }

    /**
     * Get chat list.
     *
     * @param  bool   $public
     * @access public
     * @return array
     */
    public function getList($public = true)
    {
        $chats = $this->dao->select('*')->from(TABLE_IM_CHAT)
            ->where('public')->eq($public)
            ->beginIF($public)->andWhere('dismissDate')->eq('0000-00-00 00:00:00')->fi()
            ->fetchAll();

        return $this->format($chats);
    }

    /**
     * Get chat gid list by userID.
     *
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function getGidListByUserID($userID = 0)
    {
        $systemChatGidList = $this->dao->select('gid')->from(TABLE_IM_CHAT)
            ->where('type')->eq('system')
            ->fetchPairs('gid');
        $gidList = $this->dao->select('t1.gid')->from(TABLE_IM_CHAT)->alias('t1')
            ->leftJoin(TABLE_IM_CHATUSER)->alias('t2')->on('t2.cgid=t1.gid')
            ->where('t2.user')->eq($userID)
            ->andWhere('t2.quit')->eq('0000-00-00 00:00:00')
            ->fetchPairs('gid');
        return array_merge($systemChatGidList, $gidList);
    }

    /**
     * Get chat list by userID.
     *
     * @param  int    $userID
     * @param  bool   $star
     * @access public
     * @return array
     */
    public function getListByUserID($userID = 0, $star = false)
    {
        $chats = $this->dao->select('t1.*, t2.star, t2.hide, t2.mute, t2.freeze, t2.category')
            ->from(TABLE_IM_CHAT)->alias('t1')
            ->leftjoin(TABLE_IM_CHATUSER)->alias('t2')->on('t1.gid=t2.cgid')
            ->where('t2.quit')->eq('0000-00-00 00:00:00')
            ->andWhere('t2.user')->eq($userID)
            ->beginIF($star)->andWhere('t2.star')->eq($star)->fi()
            ->fetchAll();

        $systemChat = $this->dao->select('*, 0 as star, 0 as hide, 0 as mute, 0 as freeze')
            ->from(TABLE_IM_CHAT)
            ->where('type')->eq('system')
            ->fetch();
        $chats[] = $systemChat;

        return $this->format($chats);
    }

	/**
	 * Check if user is committer of a chat.
	 *
	 * @param  object    $message
	 * @param  int       $userID
	 * @param  object    $object
	 * @access public
	 * @return object|bool  $output | true
	 */
	public function isCommitter($message, $userID, $chat)
	{
        $members = explode('&', $message->cgid);

		$output = new stdclass();
		$output->result = 'fail';
		$output->users   = $userID;

        if(!$chat)
		{
			$output->data = new stdclass();
			$output->data->gid      = $message->cgid;
			$output->data->messages = $this->lang->im->notExist;
			return $output;
		}

        if(count($members) == 2 and !in_array($userID, $members))
		{
            $output->message = $this->lang->im->notInChat;
			return $output;
		}

		if(!empty($chat->dismissDate))
		{
			$output->data = new stdclass();
			$output->data->gid      = $message->cgid;
			$output->data->messages = $this->lang->im->chatHasDismissed;
			return $output;
		}

		/* Check if user is in the group. */
		if($chat->type == 'group' and $message->type == 'normal')
		{
			$members = $this->getMembers($chat->gid);
			if(!in_array($message->user, $members))
			{
				$output->data = new stdclass();
				$output->data->gid      = $message->cgid;
				$output->data->messages = $this->lang->im->notInGroup;
				return $output;
			}
        }

		/* Check if user is in committers. */
		if(!empty($chat->committers))
		{
			$committers = explode(',', $chat->committers);
			if(!in_array($userID, $committers))
			{
				$output->data = new stdclass();
				$output->data->gid      = $message->cgid;
				$output->data->messages = $this->lang->im->cantChat;
				return $output;
			}
		}

		return true;
	}

    /**
     * Get group pairs of all chat.
     *
     * @access public
     * @return array
     */
    public function getGroupPairs()
    {
        return $this->dao->select('gid, name')->from(TABLE_IM_CHAT)
            ->where('type')->eq('group')
            ->andWhere('dismissDate')->eq('0000-00-00 00:00:00')
            ->fetchPairs();
    }

    /**
     * Get user pairs of one chat group.
     *
     * @param  string $gid
     * @access public
     * @return array
     */
    public function getUserPairs($gid = '')
    {
        $userIdList = $this->dao->select('user')->from(TABLE_IM_CHATUSER)
            ->where('quit')->eq('0000-00-00 00:00:00')
            ->beginIF($gid)->andWhere('cgid')->eq($gid)->fi()
            ->fetchPairs();

        return $this->dao->select('id, realname')->from(TABLE_USER)->where('id')->in($userIdList)->fetchPairs();
    }

    /**
     * Create a chat.
     *
     * @param  string $gid
     * @param  string $name
     * @param  string $type
     * @param  array  $members
     * @param  int    $subjectID
     * @param  bool   $public
     * @param  int    $userID
     * @access public
     * @return object
     */
    public function create($gid = '', $name = '', $type = '', $members = array(), $subjectID = 0, $public = false, $userID = 0)
    {
        $user = $this->loadModel('im')->user->getByID($userID);

        $chat = new stdclass();
        $chat->gid         = $gid;
        $chat->name        = $name;
        $chat->type        = $type;
        $chat->subject     = $subjectID;
        $chat->createdBy   = !empty($user->account) ? $user->account : '';
        $chat->createdDate = helper::now();

        if($public) $chat->public = 1;

        $this->dao->insert(TABLE_IM_CHAT)->data($chat)->exec();

        /* Add members to chat. */
        foreach($members as $member) $this->join($gid, $member);

        return $this->getByGid($gid, true);
    }

    /**
     * Update a chat.
     *
     * @param  object $chat
     * @param  int    $userID
     * @access public
     * @return object
     */
    public function update($chat = null, $userID = 0)
    {
        if($chat)
        {
            $user = $this->loadModel('im')->user->getByID($userID);
            $chat->editedBy   = !empty($user->account) ? $user->account : '';
            $chat->editedDate = helper::now();
            $this->dao->update(TABLE_IM_CHAT)->data($chat)->where('gid')->eq($chat->gid)->batchCheck($this->config->im->require->edit, 'notempty')->exec();
        }

        /* Return the changed chat. */
        return $this->getByGid($chat->gid, true);
    }

   /**
     * Init the system chat.
     *
     * @access public
     * @return bool
     */
    public function initSystemChat()
    {
        $chat = $this->dao->select('*')->from(TABLE_IM_CHAT)->where('type')->eq('system')->fetch();
        if(!$chat)
        {
            $chat = new stdclass();
            $chat->gid         = imModel::createGID();
            $chat->name        = $this->lang->im->systemGroup;
            $chat->type        = 'system';
            $chat->createdBy   = 'system';
            $chat->createdDate = helper::now();

            $this->dao->insert(TABLE_IM_CHAT)->data($chat)->exec();
        }
        return !dao::isError();
    }

    /**
     * Join a chat.
     *
     * @param  string $gid
     * @param  int    $userID
     * @param  bool   $join
     * @access public
     * @return bool
     */
    public function join($gid = '', $userID = 0)
    {
        $data = $this->dao->select('*')->from(TABLE_IM_CHATUSER)->where('cgid')->eq($gid)->andWhere('user')->eq($userID)->fetch();
        if($data)
        {
            /* If user hasn't quit the chat then return. */
            if($data->quit == '0000-00-00 00:00:00') return true;

            /* If user has quited the chat then update the record. */
            $data = new stdclass();
            $data->join = helper::now();
            $data->quit = '0000-00-00 00:00:00';
            $this->dao->update(TABLE_IM_CHATUSER)->data($data)->where('cgid')->eq($gid)->andWhere('user')->eq($userID)->exec();

            return !dao::isError();
        }

        /* Create a new record of user's chat info. */
        $data = new stdclass();
        $data->cgid = $gid;
        $data->user = $userID;
        $data->join = helper::now();
        $this->dao->insert(TABLE_IM_CHATUSER)->data($data)->exec();

        /* Update order field. */
        $id = $this->dao->lastInsertID();
        $this->dao->update(TABLE_IM_CHATUSER)->set('`order`')->eq($id)->where('id')->eq($id)->exec();

        return !dao::isError();
    }

    /**
     * leave a chat.
     *
     * @param  int    $gid
     * @param  int    $userID
     * @access public
     * @return bool
     */
    public function leave($gid, $userID)
    {
        $this->dao->update(TABLE_IM_CHATUSER)->set('quit')->eq(helper::now())->where('cgid')->eq($gid)->andWhere('user')->eq($userID)->exec();
        return !dao::isError();
    }

    /**
     * Format chats.
     *
     * @param  mixed  $chats  object | array
     * @access public
     * @return object | array
     */
    public function format($chats)
    {
        $isObject = false;
        if(is_object($chats))
        {
            $isObject = true;
            $chats    = array($chats);
        }

        foreach($chats as $chat)
        {
            if(!$chat) continue;
            $chat->id             = (int)$chat->id;
            $chat->subject        = (int)$chat->subject;
            $chat->createdDate    = $chat->createdDate == '0000-00-00 00:00:00' ? 0 : strtotime($chat->createdDate);
            $chat->editedDate     = $chat->editedDate == '0000-00-00 00:00:00' ? 0 : strtotime($chat->editedDate);
            $chat->lastActiveTime = $chat->lastActiveTime == '0000-00-00 00:00:00' ? 0 : strtotime($chat->lastActiveTime);
            $chat->dismissDate    = $chat->dismissDate == '0000-00-00 00:00:00' ? 0 : strtotime($chat->dismissDate);

            if($chat->type == 'one2one') $chat->name = '';

            if(isset($chat->star))   $chat->star   = (bool)$chat->star;
            if(isset($chat->hide))   $chat->hide   = (bool)$chat->hide;
            if(isset($chat->mute))   $chat->mute   = (bool)$chat->mute;
            if(isset($chat->public)) $chat->public = (bool)$chat->public;
            if(isset($chat->freeze)) $chat->freeze = (bool)$chat->freeze;
        }

        if($isObject) return reset($chats);

        return $chats;
    }

    /**
     * Set admins of a chat.
     *
     * @param  string $gid
     * @param  array  $admins
     * @access public
     * @return object
     */
    public function setAdmins($gid = '', $admins = array())
    {
        $chat = $this->getByGid($gid);
        $adminList = explode(',', $chat->admins);
        $adminList = array_unique(array_merge($adminList, $admins));
        $adminList = implode(',', $adminList);
        $this->dao->update(TABLE_IM_CHAT)->set('admins')->eq($adminList)->where('gid')->eq($gid)->exec();

        return $this->getByGid($gid, true);
    }

    /**
     * Remove admins of a chat.
     *
     * @param  string $gid
     * @param  array  $admins
     * @access public
     * @return void
     */
    public function removeAdmins($gid = '', $users = array())
    {
        $chat = $this->getByGid($gid);
        $adminList = explode(',', $chat->admins);
        $adminList = array_diff($adminList, $users);
        $adminList = implode(',', $adminList);
        $this->dao->update(TABLE_IM_CHAT)->set('admins')->eq($adminList)->where('gid')->eq($gid)->exec();
        return $this->getByGid($gid, true);
    }

    /**
     * Star or unstar a chat.
     *
     * @param  string $gid
     * @param  int    $userID
     * @access public
     * @return object
     */
    public function star($star = '1', $gid = '', $userID = 0)
    {
        $this->dao->update(TABLE_IM_CHATUSER)
            ->set('star')->eq($star)
            ->where('cgid')->eq($gid)
            ->andWhere('user')->eq($userID)
            ->exec();

        return $this->getByGid($gid, true);
    }

    /**
     * Hide or display a chat.
     *
     * @param  string   $hide
     * @param  string   $gid
     * @param  int      $userID
     * @access public
     * @return bool
     */
    public function hide($hide = '1', $gid = '', $userID = 0)
    {
        $this->dao->update(TABLE_IM_CHATUSER)
            ->set('hide')->eq($hide)
            ->where('cgid')->eq($gid)
            ->andWhere('user')->eq($userID)
            ->exec();

        return !dao::isError();
    }

    /**
     * Mute or unmute a chat.
     *
     * @param  string   $mute
     * @param  string   $gid
     * @param  int      $userID
     * @access public
     * @return bool
     */
    public function mute($mute = '1', $gid = '', $userID = 0)
    {
        $this->dao->update(TABLE_IM_CHATUSER)
            ->set('mute')->eq($mute)
            ->where('cgid')->eq($gid)
            ->andWhere('user')->eq($userID)
            ->exec();

        return !dao::isError();
    }

    /**
     * Freeze or unfreeze a chat.
     *
     * @param  string   $freeze
     * @param  string   $gid
     * @param  int      $userID
     * @access public
     * @return bool
     */
    public function freeze($freeze = '1', $gid = '', $userID = 0)
    {
        $this->dao->update(TABLE_IM_CHATUSER)
            ->set('freeze')->eq($freeze)
            ->where('cgid')->eq($gid)
            ->andWhere('user')->eq($userID)
            ->exec();

        return !dao::isError();
    }

    /**
     * Set category for a chat
     *
     * @param  array  $gids
     * @param  string $category
     * @param  int    $userID
     * @access public
     * @return void
     */
    public function setCategory($gids = array(), $category = '', $userID = 0)
    {
        $this->dao->update(TABLE_IM_CHATUSER)
            ->set('category')->eq($category)
            ->where('cgid')->in($gids)
            ->andWhere('user')->eq($userID)
            ->exec();

        return !dao::isError();
    }

    /**
     * Get member list of one chat.
     *
     * @param  string $gid
     * @access public
     * @return array
     */
    public function getMembers($gid)
    {
        $chat = $this->getByGid($gid);
        if(!$chat) return array();

        if($chat->type == 'system')
        {
            $memberList = $this->dao->select('id')->from(TABLE_USER)->where('deleted')->eq('0')->fetchPairs();
        }
        else
        {
            $memberList = $this->dao->select('user')
                ->from(TABLE_IM_CHATUSER)
                ->where('quit')->eq('0000-00-00 00:00:00')
                ->beginIF($gid)->andWhere('cgid')->eq($gid)->fi()
                ->fetchPairs();
        }

        $members = array();
        foreach($memberList as $member) $members[] = (int)$member;

        return $members;
    }
}
