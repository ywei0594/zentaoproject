<?php
class user extends model
{
    /**
     * Extends identify a user with plain password function.
     *
     * @param   string $account     the account
     * @param   string $password    the password    the plain password or the md5 hash
     * @access  public
     * @return  object|bool|string  if is valid user, return the user object.
     *                              if no valid user, return false.
     *                              if user is locked, return locked status as string.
     */
    public function identify($account, $password)
    {
        $user = $this->loadModel('user')->identify($account, $password);
        if(!$user)
        {
            $user     = $this->loadModel('user')->identify($account, $password);
        }
        return $user;
    }

    /**
     * Get a user.
     *
     * @param  int    $id
     * @access public
     * @return object
     */
    public function getByID($id = 0)
    {
		$user = $this->dao->select('id, account, realname, avatar, role, dept, clientStatus, gender, email, mobile, phone,  qq, deleted')
			->from(TABLE_USER)
			->where('id')->eq($id)
			->fetch();
        if(!$user) return array();

        return $this->format($user);
    }

    /**
     * Get user list by id or account.
     *
     * @param  string $status
     * @param  array  $characters    can be an array of uids or accounts, single type only.
     * @param  bool   $idAsKey
     * @access public
     * @return array
     */
    public function getList($status = '', $characters = array(), $idAsKey = true)
    {
        $dao = $this->dao->select('id, account, realname, avatar, role, dept, clientStatus, gender, email, mobile, phone,  qq, deleted')
            ->from(TABLE_USER)
            ->where(1)
            ->beginIF(empty($characters))
            ->andWhere('deleted')->eq('0')
            ->fi()
            ->beginIF($status && $status == 'online')->andWhere('clientStatus')->ne('offline')->fi()
            ->beginIF($status && $status != 'online')->andWhere('clientStatus')->eq($status)->fi()
            ->beginIF($characters &&  is_numeric(current($characters)))->andWhere('id')->in($characters)->fi()
            ->beginIF($characters && !is_numeric(current($characters)))->andWhere('account')->in($characters)->fi();

        $users = $idAsKey ? $dao->fetchAll('id') : $dao->fetchAll();

        return $this->format($users);
    }

    /**
     * Update a user.
     *
     * @param  object $user
     * @access public
     * @return object
     */
    public function update($user = null)
    {
        if(empty($user->id)) return null;

        $data = array();
        foreach($this->config->im->user->canEditFields as $field)
        {
            if(!empty($user->$field)) $data[$field] = $user->$field;
        }
        if(!empty($user->account) && !empty($user->password)) $data['password'] = $user->password;
        if(empty($data)) return null;

        $data['clientLang'] = $this->session->clientLang;
        $this->dao->update(TABLE_USER)->data($data)->where('id')->eq($user->id)->exec();
        return $this->getByID($user->id);
    }

    /**
     * Format users.
     *
     * @param  mixed  $users  object | array
     * @access public
     * @return object | array
     */
    public function format($users)
    {
        $isObject = false;
        if(is_object($users))
        {
            $isObject = true;
            $users    = array($users);
        }

        foreach($users as $user)
        {
            $user->id     = (int)$user->id;
            $user->dept   = (int)$user->dept;
            $user->avatar = !empty($user->avatar) ? commonModel::getSysURL() . $user->avatar : $user->avatar;
            $user->status = $user->clientStatus;
            if(isset($user->deleted)) $user->deleted = (bool)$user->deleted;
            if(!isset($user->signed)) $user->signed  = 0;
        }

        if($isObject) return reset($users);

        return $users;
    }

    /**
     * Reset user status.
     *
     * @param  string $status
     * @access public
     * @return bool
     */
    public function resetStatus($status = 'offline')
    {
        $this->dao->update(TABLE_USER)->set('clientStatus')->eq($status)->exec();
        return !dao::isError();
    }

    /**
     * Set user status to offline.
     *
     * @param  array $users
     * @access public
     * @return bool
     */
    public function setOffline($users = array())
    {
        if(empty($users)) return true;
        $this->dao->update(TABLE_USER)->set('clientStatus')->eq('offline')->where('id')->in($users)->exec();
        return !dao::isError();
    }

    /**
     * Check if has user changes.
     *
     * @return string
     */
    public function hasChanges()
    {
        if(isset($this->config->xuanxuan->pollingInterval))
        {
            $timeStr = "- {$this->config->xuanxuan->pollingInterval} seconds";
        }
        else
        {
            $timeStr = '- 60 seconds';
        }

        $data = $this->dao->select('id')->from(TABLE_ACTION)
            ->where('objectType')->eq('user')
            ->andWhere('action')->in('created,edited,deleted')
            ->andWhere('date')->gt(date(DT_DATETIME1, strtotime($timeStr)))
            ->fetch();
        return empty($data) ? 'no' : 'yes';
    }
}
