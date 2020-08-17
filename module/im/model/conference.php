<?php
class conference extends model
{
    /**
     * Get conference by the id of chat.
     *
     * @param  string $chatID
     * @access public
     * @return object
     */
    public function getByChatID($chatID)
    {
        return $this->dao->select('*')->from(TABLE_IM_CONFERENCE)->where('cgid')->eq($chatID)->fetch();
    }

    /**
     * Get conference by the id of room.
     *
     * @param  string $roomID
     * @access public
     * @return object
     */
    public function getByRoomID($roomID)
    {
        return $this->dao->select('*')->from(TABLE_IM_CONFERENCE)->where('rid')->eq($roomID)->fetch();
    }

    /**
     * Create or activate a conference.
     *
     * @param  string  $chatID
     * @param  int     $userID
     * @access public
     * @return object
     */
    public function create($chatID, $userID)
    {
        $date = helper::now();
        $conference = $this->getByChatID($chatID);
        if(!empty($conference))
        {
            if($conference->status == 'open')
            {
                $conference->participants = $this->addParticipant($chatID, $userID);
                $this->saveAction($conference->rid, 'join', $userID);
                return $conference;
            }
            $conference->status       = 'open';
            $conference->participants = "$userID";
            $conference->openedDate   = helper::now();
            $conference->openedBy     = $userID;
            $this->dao->update(TABLE_IM_CONFERENCE)
                ->set('status')->eq($conference->status)
                ->set('participants')->eq($conference->participants)
                ->set('openedDate')->eq($conference->openedDate)
                ->set('openedBy')->eq($conference->openedBy)
                ->where('id')->eq($conference->id)
                ->exec();
        }
        else
        {
            $roomInfo = $this->loadModel('owt')->createRoom($chatID);
            if(!isset($roomInfo)) return false;

            $roomInfo = json_decode($roomInfo);

            $conference = new stdClass();
            $conference->cgid         = $chatID;
            $conference->rid          = $roomInfo->id;
            $conference->status       = 'open';
            $conference->participants = "$userID";
            $conference->openedBy     = (int)$userID;
            $conference->openedDate   = $date;
            $this->dao->insert(TABLE_IM_CONFERENCE)->data($conference)->exec();
        }

        $this->saveAction($conference->rid, 'create', $userID);

        if(dao::isError()) return false;
        return $conference;
    }

    /**
     * Close a conference.
     *
     * @param  string  $chatID
     * @param  int     $userID
     * @access public
     * @return boolean
     */
    public function close($chatID, $userID)
    {
        $conference = $this->getByChatID($chatID);
        if($conference->status == 'closed') return false;

        $this->dao->update(TABLE_IM_CONFERENCE)
            ->set('status')->eq('closed')
            ->set('participants')->eq('')
            ->where('id')->eq($conference->id)
            ->exec();

        $this->saveAction($conference->rid, 'close', $userID);

        return !dao::isError();
    }

    /**
     * Add participant into a conference.
     *
     * @param  string        $chatID
     * @param  int           $userID
     * @access public
     * @return string|boolean
     */
    public function addParticipant($chatID, $userID)
    {
        $conference = $this->getByChatID($chatID);
        if($conference->status == 'closed') return false;

        $participants = explode(',', $conference->participants);
        $participants = array_filter($participants);

        if(!$this->isUnlimitedParticipants() && count($participants) > 2) return false;

        $participants[] = $userID;
        $participants = implode(',', $participants);

        $this->dao->update(TABLE_IM_CONFERENCE)
            ->set('participants')->eq($participants)
            ->where('id')->eq($conference->id)
            ->exec();

        if(dao::isError()) return false;
        return $participants;
    }

    /**
     * Remove participant from a conference.
     *
     * @param  string        $chatID
     * @param  int           $userID
     * @access public
     * @return array|boolean
     */
    public function removeParticipant($chatID, $userID)
    {
        $conference = $this->getByChatID($chatID);
        if($conference->participants == '') return false;

        $participants = explode(',', $conference->participants);
        $participants = array_diff($participants, array($userID));
        $participants = implode(',', $participants);

        $this->dao->update(TABLE_IM_CONFERENCE)
            ->set('participants')->eq($participants)
            ->where('id')->eq($conference->id)
            ->exec();

        if(dao::isError()) return false;
        return $participants;
    }

    /**
     * Get conference actions since last close.
     *
     * @param  object $conference
     * @access public
     * @return boolean
     */
    public function getActions($conference)
    {
        $actions = $this->dao->select('*')->from(TABLE_IM_CONFERENCEACTION)
                    ->where('rid')->eq($conference->rid)
                    ->andWhere('date')->gt($conference->openedDate)
                    ->fetchAll();

        if(!empty($actions))
        {
            foreach($actions as $action)
            {
                $action->user = (int)$action->user;
                $action->date = strtotime($action->date);
            }
        }

        if(dao::isError()) return false;
        return $actions;
    }

    /**
     * Save a conference action.
     *
     * @param  string  $roomID
     * @param  string  $type
     * @param  int     $userID
     * @access public
     * @return boolean
     */
    public function saveAction($roomID, $type, $userID)
    {
        $action = new stdClass();
        $action->rid  = $roomID;
        $action->type = $type;
        $action->user = $userID;
        $action->date = helper::now();
        $this->dao->insert(TABLE_IM_CONFERENCEACTION)->data($action)->exec();

        if(dao::isError()) return false;
        return $action;
    }

    /**
     * Remove user from all related conferences and close if necessary.
     *
     * @param  int  $userID
     * @access public
     * @return void
     */
    public function removeUserFromConferences($userID)
    {
        $userChats = $this->loadModel('im')->chat->getListByUserID($userID);
        foreach($userChats as $chat)
        {
            $chatConference = $this->getByChatID($chat->gid);
            if(!empty($chatConference) && $chatConference->status == 'open')
            {
                $participants = explode(',', $chatConference->participants);
                $participants = array_filter($participants);
                if(in_array($userID, $participants))
                {
                    if(count($participants) > 1)
                    {
                        $this->removeParticipant($chat->gid, $userID);
                    }
                    else
                    {
                        $this->close($chat->gid, $userID);
                    }
                }
            }
        }
    }

    /**
     * Clean conference participants and close conference if needed or return the conference.
     *
     * @param  string         $chatID
     * @param  int            $userID
     * @access public
     * @return object|boolean
     */
    public function cleanConference($chatID, $userID)
    {
        $conference = $this->getByChatID($chatID);
        if(empty($conference)) return false;

        $participants = explode(',', $conference->participants);
        $participants = array_filter($participants);

        $onlineUsers = array_keys($this->loadModel('im')->user->getList('online'));
        $participants = array_intersect($participants, $onlineUsers);

        if(count($participants) == 1 && in_array($userID, $participants))
        {
            $this->close($conference->cgid, $userID);
            return false;
        }

        $participants = implode(',', $participants);
        $conference->participants = $participants;

        return $conference;
    }

    /**
     * Get open conferences by given chat list.
     *
     * @param  array  $chatList
     * @param  int    $userID
     * @access public
     * @return array
     */
    public function getOpenConferencesByChatList($chatList, $userID)
    {
        $openConferenceList = array();
        foreach($chatList as $chat)
        {
            $chatConference = $this->cleanConference($chat->gid, $userID);
            if(!empty($chatConference) && $chatConference->status == 'open')
            {
                $actions = $this->getActions($chatConference);
                $chatConference->actions = empty($actions) ? array() : $actions;

                $chatConference->room       = $chatConference->rid;
                $chatConference->openedBy   = (int)$chatConference->openedBy;
                $chatConference->openedDate = strtotime($chatConference->openedDate);
                unset($chatConference->rid);

                $openConferenceList[] = $chatConference;
            }
        }
        return $openConferenceList;
    }

    /**
     * Check if there is participant limit in license.
     *
     * @access public
     * @return string|bool
     */
    public function isUnlimitedParticipants()
    {
        return $this->loadModel('license')->getPropertyValue('unlimitedParticipants');
    }
}
