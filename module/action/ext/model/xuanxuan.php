<?php
/**
 * Get actions since last two xxd poll.
 *
 * @param  string $objectType
 * @param  int    $objectID
 * @param  string $action
 * @access public
 * @return array
 */
public function getListSinceLastPoll($objectType = null, $objectID = null, $action = '')
{
    $lastPoll = date(DT_DATETIME1, strtotime($this->config->xxd->lastPoll . ' - ' . $this->config->xuanxuan->pollingInterval . ' second'));
    $actions = $this->dao->select('*')->from(TABLE_ACTION)
        ->where('date')->gt($lastPoll)
        ->beginIF($objectType)->andWhere('objectType')->eq($objectType)->fi()
        ->beginIF($objectID)->andWhere('objectID')->eq($objectID)->fi()
        ->beginIF($action)->andWhere('action')->eq($action)->fi()
        ->orderBy('`date`_desc')
        ->fetchAll('id');

    $histories = $this->getHistory(array_keys($actions));

    foreach($actions as $actionID => $action)
    {
        if(isset($histories[$actionID]))
        {
            $action->history = $histories[$actionID];
            $actions[$actionID] = $action;
        }
    }

    return $actions;
}
