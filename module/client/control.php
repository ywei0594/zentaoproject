<?php
/**
 * The control file of client module of XXB.
 *
 * @copyright   Copyright 2009-2020 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZOSL (https://zpl.pub/page/zoslv1.html)
 * @author      Gang Liu <liugang@cnezsoft.com>
 * @package     client
 * @version     $Id$
 * @link        https://xuanim.com
 */
class client extends control
{
    /**
     * Index page.
     *
     * @access public
     * @return void
     */
    public function index()
    {
        $blocks = $this->loadModel('block')->getBlockList('xxb');

        /* Init block when vist index first. */
        if(empty($blocks))
        {
            if($this->loadModel('block')->initBlock('xxb')) die(js::reload());
        }

        foreach($blocks as $key => $block)
        {
            $block->params = json_decode($block->params);
            if(empty($block->params)) $block->params = new stdclass();

            $sign = $this->config->requestType == 'PATH_INFO' ? '?' : '&';
            $block->blockLink = $this->createLink('block', 'print' . $block->block . 'block', "index={$block->id}");
        }

        $this->view->title          = $this->lang->client->common;
        $this->view->blocks         = $blocks;
        $this->view->currentVersion = $this->client->getCurrentVersion();
        $this->view->versionApiUrl  = sprintf($this->config->client->upgradeApi, '');
        $this->display();
    }

    /**
     * Browse client list.
     *
     * @access public
     * @return void
     */
    public function browse()
    {
        $this->view->title   = $this->lang->client->update;
        $this->view->clients = $this->client->getList();
        $this->display();
    }

    /**
     * Create a client.
     *
     * @access public
     * @return void
     */
    public function create()
    {
        if($_POST)
        {
            $this->client->create();
            if(dao::isError()) $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => inlink('browse')));
        }

        $this->view->title = $this->lang->client->create;
        $this->display();
    }

    /**
     * Download remote package.
     * @param string $version
     * @param string $link
     * @param string $os
     * @return string
     */
    public function download($version = '', $link = '', $os = '')
    {
        set_time_limit(0);
        $result = $this->client->downloadZipPackage($version, $link);
        if($result == false) $this->send(array('result' => 'fail', 'message' => $this->lang->client->downloadFail));
        $client = $this->client->edit($version, $result, $os);
        if($client == false) $this->send(array('result' => 'fail', 'message' => $this->lang->client->saveClientError));
        $this->send(array('result' => 'success', 'client' => $client, 'message' => $this->lang->saveSuccess, 'locate' => inlink('browse')));
    }

    /**
     * Edit a version.
     *
     * @param  int    $clientID
     * @access public
     * @return void
     */
    public function edit($clientID)
    {
        if($_POST)
        {
            $this->client->update($clientID);
            if(dao::isError()) $this->send(array('result' => 'fail', 'message' => dao::getError()));

            $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => inlink('browse')));
        }

        $this->view->title  = $this->lang->client->edit;
        $this->view->client = $this->client->getByID($clientID);
        $this->display();
    }

    /**
     * View changelog of a client update
     *
     * @param  int    $clientID
     * @access public
     * @return void
     */
    public function changelog($clientID)
    {
        $client             = $this->client->getByID($clientID);
        $this->view->client = $client;
        $this->view->title  = $this->lang->client->changeLog . ' ' . $client->version;
        $this->display();
    }

    /**
     * Delete a client.
     *
     * @param  int    $clientID
     * @access public
     * @return void
     */
    public function delete($clientID)
    {
        $this->dao->delete()->from(TABLE_IM_CLIENT)->where('id')->eq($clientID)->exec();
        if(dao::isError()) $this->send(array('result' => 'fail', 'message' => dao::getError()));

        $this->send(array('result' => 'success'));
    }

    /**
     * Check upgrade.
     *
     * @access public
     * @return void
     */
    public function checkUpgrade()
    {
        $currentVersion = $this->client->getCurrentVersion();
        $apiUrl         = sprintf($this->config->client->upgradeApi, "-$currentVersion->version");
        $jsonData       = file_get_contents($apiUrl);
        $serverVersions = json_decode($jsonData, false);

        $this->view->title          = $this->lang->client->checkUpgrade;
        $this->view->serverVersions = $serverVersions;
        $this->view->versions       = $serverVersions;
        $this->view->currentVersion = $currentVersion;
        $this->view->path           = $this->app->dataRoot;
        $this->display();
    }

    /**
     * Download xuan client.
     *
     * @access public
     * @param  string $action
     * @param  string $os
     * @return void
     */
    public function downloadClient($action = 'check', $os = '')
    {
        if($_POST)
        {
            $os = $this->post->os;
            die(js::locate($this->createLink('client', 'downloadClient', "action=getPackage&os=$os"), 'self'));
        }

        if($action == 'check')
        {
            $error     = false;
            $errorInfo = '';

            $clientDir = $this->app->wwwRoot . 'data/client/' . $this->config->xuanxuan->version . '/';
            if(!is_dir($clientDir))
            {
                $result = mkdir($clientDir, 0755, true);
                if($result == false)
                {
                    $error = true;
                    $errorInfo = sprintf($this->lang->client->errorInfo->dirNotExist, $clientDir, $clientDir);
                }
            }

            if(!is_writable($clientDir))
            {
                $error = true;
                $errorInfo = sprintf($this->lang->client->errorInfo->dirNotWritable, $clientDir, $clientDir);
            }

            $this->view->error     = $error;
            $this->view->errorInfo = $errorInfo;

            if(!$error) die(js::locate($this->createLink('client', 'downloadClient', "action=selectPackage")));
        }

        if($action == 'selectPackage')
        {
            $os = 'win64';
            $agentOS = helper::getOS();
            if(strpos($agentOS, 'Windows') !== false) $os = 'win64';
            if(strpos($agentOS, 'Linux') !== false)   $os = 'linux64';
            if(strpos($agentOS, 'Mac') !== false)     $os = 'mac';

            $this->view->os = $os;
        }

        if($action == 'getPackage')
        {
            $this->view->os      = $os;
            $this->view->account = $this->app->user->account;
        }

        if($action == 'clearTmpPackage')
        {
            $account = $this->app->user->account;
            $tmpDir  = $this->app->wwwRoot . 'data/client/' . "$account/";

            if(is_dir($tmpDir))
            {
                $zfile = $this->app->loadClass('zfile');
                $zfile->removeDir($tmpDir);
            }
        }

        if($action == 'downloadPackage')
        {
            $account   = $this->app->user->account;
            $clientDir = $this->app->wwwRoot . 'data/client/' . "$account/";

            $clientFile = $clientDir . 'xuanxuan.zip';
            $zipContent = file_get_contents($clientFile);
            if(is_dir($clientDir))
            {
                $zfile = $this->app->loadClass('zfile');
                $zfile->removeDir($clientDir);
            }

            $output = $this->fetch('file', 'sendDownHeader', array('fileName' => "xuanxuan." . $os . '.zip', 'zip', $zipContent));
        }

        $this->view->action = $action;
        $this->display();
    }

    /**
     * Ajax get client package.
     *
     * @param  string $os
     * @access public
     * @return void
     */
    public function ajaxGetClientPackage($os = '')
    {
        set_time_limit (0);
        session_write_close();

        $response = array();
        $response['result']  = 'success';
        $response['message'] = '';

        $clientDir = $this->app->wwwRoot . 'data/client/';
        if(!is_dir($clientDir)) mkdir($clientDir, 0755, true);

        $version    = $this->config->xuanxuan->version;
        $packageDir = $clientDir . "/$version/";
        if(!is_dir($packageDir)) mkdir($packageDir, 0755, true);

        $account = $this->app->user->account;
        $tmpDir = $clientDir . "/$account/";
        if(!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

        $needCache   = false;
        $clientName  = "xuanxuan." . $version . "." . $os . ".zip";
        $packageFile = $packageDir . $clientName;
        if(!file_exists($packageFile))
        {
            $url       = "http://dl.cnezsoft.com/xuanxuan/$version/";
            $xxFile    = $url . $clientName . "?t=" . rand();
            $needCache = true;
        }
        else
        {
            $xxFile = $packageFile;
        }

        $clientFile = $tmpDir . 'xuanxuan.zip';
        if($xxHd = fopen($xxFile, "rb"))
        {
            if($clientHd = fopen($clientFile, "wb"))
            {
                while(!feof($xxHd))
                {
                    $result = fwrite($clientHd, fread($xxHd, 1024 * 8 ), 1024 * 8 );
                    if($result == false)
                    {
                        $response['result']  = 'fail';
                        $response['message'] = sprintf($this->lang->client->errorInfo->manualOpt, $xxFile);
                        $this->send($response);
                    }
                }
            }
            else
            {
                $response['result'] = 'fail';
                $response['message'] = sprintf($this->lang->client->errorInfo->manualOpt, $xxFile);
                $this->send($response);
            }
            fclose($xxHd);
            fclose($clientHd);
        }
        else
        {
            $response['result'] = 'fail';
            $response['message'] = sprintf($this->lang->client->errorInfo->manualOpt, $xxFile);
            $this->send($response);
        }

        if($needCache) file_put_contents($packageFile, file_get_contents($clientFile));

        $this->send($response);
    }

    /**
     * Ajax get client package size.
     *
     * @access public
     * @return void
     */
    public function ajaxGetPackageSize()
    {
        $account     = $this->app->user->account;
        $packageFile = $this->app->wwwRoot . 'data/client/' . $account . '/xuanxuan.zip';

        $size = 0;
        if(file_exists($packageFile))
        {
            $size = filesize($packageFile);
            $size = $size ? round($size / 1048576, 2) : 0;
        }

        $response = array();
        $response['result'] = 'success';
        $response['size'] = $size;

        $this->send($response);
    }

    /**
     * Ajax set client config to client package.
     *
     * @param  string $os
     * @access public
     * @return void
     */
    public function ajaxSetClientConfig($os = '')
    {
        $response['result'] = 'success';

        $account   = $this->app->user->account;
        $clientDir = $this->app->wwwRoot . 'data/client/' . "$account/";
        if(!is_dir($clientDir)) mkdir($clientDir, 0755, true);

        /* write login info into config file. */
        $loginInfo = new stdclass();
        $loginInfo->ui = new stdclass();
        $loginInfo->ui->defaultUser = new stdclass();
        $loginInfo->ui->defaultUser->server  = commonModel::getSysURL();;
        $loginInfo->ui->defaultUser->account = $this->app->user->account;
        $loginInfo = json_encode($loginInfo);

        $loginFile = $clientDir . 'config.json';
        file_put_contents($loginFile, $loginInfo);

        define('PCLZIP_TEMPORARY_DIR', $clientDir);
        $this->app->loadClass('pclzip', true);
        $clientFile = $clientDir . 'xuanxuan.zip';
        $archive    = new pclzip($clientFile);

        if($os == 'mac')
        {
            $result = $archive->add($loginFile, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, 'xuanxuan.app/Contents/Resources/build-in');
        }
        else
        {
            $result = $archive->add($loginFile, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, 'xuanxuan/resources/build-in');
        }

        if($result == 0)
        {
            $response['result']  = 'fail';
            $response['message'] = $archive->errorInfo(true);
            $this->send($response);
        }

        $this->send($response);
    }
}
