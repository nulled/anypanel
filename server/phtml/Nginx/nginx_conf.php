<h2 class="pageheader">Nginx Configuration</h2>

<?=$this->js_selects?>

<?php if ($this->debug_config): ?>
<a href="javascript:void(0)" onclick="var id=document.getElementById('locs').style; if (id.display=='block') id.display='none'; else id.display='block';">parameters</a>
<div id="locs" style="font-size:10px;display:none">
    <pre class="pretext">
        <?=print_r($this->debug_config, 1)?>
    </pre>
</div>
<?php endif; ?>

<div class="pagesummary" style="width:850px">
    Main configuration file for Nginx Web Server.
</div>

<?=$this->SubmitStatus($this->notValid)?>

<h5 class="pageheader" style="width:640px">
    <b>Drag and Drop</b>
    <br />Main, Events and Http parameters to sections of nginx.conf file.
    <br />Click on comments to change then or erase them.
</h5>

<div class="pageheader" id="items">
    <span class="item" id="main_select">Main</span>
    <span class="item" id="events_select">Events</span>
    <span class="item" id="http_select">HTTP</span>
</div>

<button id="authsave">Save</button> | <button id="authcancel">Cancel</button>

<form id="myform" class="tableA" style="width:800px;" name="myForm" action="index.php" method="post" autocomplete="off">

    <div id="cart_main">
        <?=$this->html?>
    </div>
    <input id="config_result" type="hidden" name="config_result" value="" />
    <!-- <input type="hidden" name="file" value="nginx.conf" /> -->
    <input type="hidden" name="submitted" value="save" />

</form>

<button id="authsave">Save</button> | <button id="authcancel">Cancel</button>
