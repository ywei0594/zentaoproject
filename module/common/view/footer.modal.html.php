<?php
/**
 * The common modal footer view file of XXB.
 *
 * @copyright   Copyright 2009-2020 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZOSL (https://zpl.pub/page/zoslv1.html)
 * @author      Xiying Guan <guanxiying@xirangit.com>
 * @package     XXB
 * @version     $Id$
 * @link        https://xuanim.com
 */
?>
<?php if(helper::isAjaxRequest()):?>
    </div>
  </div>
</div>
<?php if(isset($pageJS)) js::execute($pageJS);?>
<?php else:?>
<?php include  $this->app->getAppRoot() . 'module/common/view/footer.lite.html.php';?>
<?php endif;?>
