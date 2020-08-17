<?php
/**
 * The index view file of client module of RanZhi.
 *
 * @copyright   Copyright 2009-2020 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZOSL (https://zpl.pub/page/zoslv1.html)
 * @author      Tingting Dai <daitingting@xirangit.com>
 * @package     client
 * @version     $Id$
 * @link        http://www.ranzhico.com
 */
?>
<?php include "../../common/view/header.html.php";?>
<?php include "../../common/view/version.html.php";?>
<?php js::set('currentVersion', !empty($currentVersion) ? $currentVersion->version : '0.0.0');?>
<?php js::set('versionApiUrl', $versionApiUrl);?>
<div id='dashboardWrapper'>
  <div class='panels-container dashboard' id='dashboard' data-confirm-remove-block='<?php echo $lang->block->confirmRemoveBlock;?>'>
    <div class='row'>
      <?php foreach($blocks as $key => $block):?>
      <div class='col-xs-<?php echo $block->grid;?> pull-left'>
        <div class='panel <?php if(isset($block->params->color)) echo 'panel-' . $block->params->color;?>' id='block<?php echo $block->id;?>' data-id='<?php echo $key;?>' data-blockid='<?php echo $block->id?>' data-name='<?php echo $block->title;?>' data-url='<?php echo $block->blockLink;?>' <?php if(!empty($block->height)) echo "data-height='$block->height'";?>>
          <div class='panel-heading'>
            <div class='panel-actions'>
              <button class="btn btn-mini refresh-panel" type='button'><i class="icon-repeat"></i></button>
            </div>
            <?php echo $block->title?>
          </div>
          <div class='panel-body no-padding'></div>
        </div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>
<div id='noticeBox'>
  <div id="noticeGoUpgrade" class="alert alert-success with-icon alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
    <i class="icon icon-cog"></i>
    <div class="content">
      <p>
        <?php echo $lang->client->xxcNotice;?>&nbsp;<small class='text-danger version'></small>
        <?php echo html::a(helper::createLink('client', 'checkUpgrade'), $lang->client->goUpdate . '<i class="icon icon-double-angle-right"></i>', 'class="small"');?>
      </p>
    </div>
  </div>
</div>
<?php include "../../common/view/footer.html.php"; ?>
