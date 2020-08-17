<?php include '../../common/view/header.lite.html.php';?>
<div id='' class='modal-content'>
  <?php if($action == 'check'):?>
  <div class='modal-body'>
    <div class='alert alert-info'>
      <p><?php echo $errorInfo;?></p>
    </div>
    <div class='text-center'><?php echo html::a($this->createLink('client', 'downloadClient', "action=check"), $lang->refresh, "class='btn btn-primary btn-wide'");?></div>
  </div>
  <?php endif;?>
  <?php if($action == 'selectPackage'):?>
  <div class='modal-body'>
    <form method='post'>
      <table class='table table-form'>
        <tr>
          <th class='w-80px'><?php echo $lang->client->version;?></th>
          <td><?php echo $config->xuanxuan->version;?></td>
        </tr>
        <tr>
          <th><?php echo $lang->client->os;?></th>
          <td><?php echo html::select('os', $lang->client->osList, $os, "class='form-control'");?></td>
        </tr>
        <tr class='text-center'>
          <td colspan='2'><?php echo html::submitButton($lang->client->download, 'btn btn-primary', 'formTarget="iframe-ajaxModal"');?></td>
        </tr>
      </table>
    </form>
  </div>
  <?php endif;?>

  <?php if($action == 'getPackage'):?>
  <?php js::set('os', $os);?>
  <div class='modal-body'>
    <ul>
      <li id='downloading'>                  <?php echo $lang->client->downloading;?><span>0</span>M</li>
      <li id='downloaded' class='hidden'>    <?php echo $lang->client->downloaded;?></li>
      <li id='setting' class='hidden'>       <?php echo $lang->client->setting;?></li>
      <li id='setted' class='hidden'>        <?php echo $lang->client->setted;?></li>
      <li id='configError'  class='hidden'>  <?php echo $lang->client->errorInfo->configError;?></li>
      <li id='downloadError' class='hidden'> <?php echo $lang->client->errorInfo->downloadError;?></li>
    </ul>
    <div id='hasError' class='alert alert-info hidden'></div>
    <div id='clearTmp' class='text-center hidden'><?php echo html::a($this->createLink('client', 'downloadClient', "action=clearTmpPackage"), $lang->confirm, "class='btn btn-primary btn-wide' data-dismiss='modal'");?></div>
  </div>
  <script>
    $(document).ready(function()
    {
        getClient();
    })

    function getClient()
    {
        var link = createLink('client', 'ajaxGetClientPackage', 'os=' + v.os);
        progress = setInterval("showPackageSize()", 1000);
        $.getJSON(link, function(response)
        {
            if(response.result == 'success')
            {
                clearInterval(progress);
                $('#downloading').addClass('hidden');
                $('#downloaded').removeClass('hidden');
                $('#setting').removeClass('hidden');

                var link = createLink('client', 'ajaxSetClientConfig', 'os=' + v.os);
                $.getJSON(link, function(response)
                {
                    if(response.result == 'success')
                    {
                        $('#setted').removeClass('hidden');
                        var link = createLink('client', 'downloadClient', "action=downloadPackage" + '&os=' + v.os);
                        window.top.location.href = link;
                    }
                    else
                    {
                        $('#downloading').addClass('hidden');
                        $('#configError').removeClass('hidden');
                        $('#hasError').removeClass('hidden');
                        $('#clearTmp').removeClass('hidden');
                        $('#hasError').html(response.message);
                    }
                });
            }
            else
            {
                clearInterval(progress);
                $('#downloading').addClass('hidden');
                $('#downloadError').removeClass('hidden');
                $('#hasError').removeClass('hidden');
                $('#clearTmp').removeClass('hidden');
                $('#hasError').html(response.message);
            }
        });
    }

    function showPackageSize()
    {
        var link = createLink('client', 'ajaxGetPackageSize', 'os=' + v.os);
        $.getJSON(link, function(response)
        {
            if(response.result == 'success')
            {
                $('#downloading span').text(response.size);
            }
            else
            {
                $('#downloading span').text(0);
            }
        });
    }
  </script>
  <?php endif;?>
</div>
<?php include '../../common/view/footer.modal.html.php';?>
