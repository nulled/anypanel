<h2 class="pageheader">Manage Your Sites</h2>

<div class="pagesummary" style="width:800px">
    Create/Remove/Enable/Disable your Nginx Server Sites.
</div>

<?php if ($this->debug_config): ?>
<a href="javascript:void(0)" onclick="var id=document.getElementById('locs').style; if (id.display=='block') id.display='none'; else id.display='block';">parameters</a>
<div id="locs" style="font-size:10px;display:none">
    <pre class="pretext">
        <?=print_r($this->debug_config, 1)?>
    </pre>
</div>
<?php endif; ?>

<?=$this->SubmitStatus($this->notValid)?>

<?php if (! $this->html): ?>
    <h3>List of Available Sites</h3>
    <?=$site_list?>
<?php else: ?>
    <?=$this->js_selects?>
    <h5 class="pageheader" style="width:800px">
        <b>Drag and Drop</b>
        <br />Main, Events and Http parameters to sections of nginx.conf file.
        <br />Click on comments to change then or erase them.
    </h5>

    <div class="pageheader" id="items" style="width:800px">
        <span class="item" id="server_select">Server</span>
        <span class="item" id="location_select">Location</span>
    </div>

    <div class="pagesummary" style="width:810px">

    <?=$buttons?> <button id="authsave">Save</button> | <button id="authcancel">Cancel</button> <b>Editing: <i><?=$site_filename?></i></b>

    <form style="width:800px" id="myform" class="tableA" name="myForm" action="index.php" method="post" autocomplete="off">

        <div id="cart_main">
            <?=$this->html?>
        </div>
        <input id="config_result" type="hidden" name="config_result" value="" />
        <input type="hidden" name="submitted" value="save" />
        <input type="hidden" name="file" value="<?=$file?>" />

    </form>

    </div>
<?php endif; ?>