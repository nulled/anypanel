<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" href="client/MultiSelect/css/MultiSelect.css" />
<link rel="stylesheet" href="client/umessagebox/css/uMessagebox.css" />
<link rel="stylesheet" href="client/css/main.css?<?=$this->asset_random?>" />

<!--
<script src="client/js/MooTools-Core-1.5.1-compressed.js"></script>
<script src="client/js/MooTools-More-1.5.1-compressed.js"></script>
-->

<script src="client/js/MooTools-Core-1.5.1.js"></script>
<script src="client/js/MooTools-More-1.5.1.js"></script>

<script src="client/js/php.js?<?=$this->asset_random?>"></script>
<script src="client/js/main.js?<?=$this->asset_random?>"></script>
<script src="client/MultiSelect/Source/MultiSelect-yui-compressed.js"></script>
<script src="client/umessagebox/js/libs/uMessagebox.js"></script>
<script src="client/FloatingTips/Source/FloatingTips.js"></script>

<title id="browser_title"><?=ucfirst($_SESSION['role'])?> Control Panel</title>

</head>
<?php flush(); ?>
<body>

<div id="container" class="container">

    <div id="head" class="head">
    </div>

    <div id="menu" class="menu">
    </div>

    <div id="main_content" class="main_content">
      <h2 class="pageheader">Welcome to <?=ucfirst($_SESSION['role'])?> Panel</h2>
      <div class="pagesummary" style="width:90%">
        <?php if ($_SESSION['role'] == 'client'): ?>
                  Client Panel
        <?php elseif ($_SESSION['role'] == 'admin'): ?>
                  Administrator Panel
        <?php elseif ($_SESSION['role'] == 'reseller'): ?>
                  Reseller Panel
        <?php endif ?>
      </div>
    </div>

    <div id="foot" class="foot">
        All Rights Reserved &copy; <?=date('Y')?> - <a onclick="javascript:windowScroll.toTop()">Scroll Back To Top</a>
    </div>
</div>

<div id="clickshield" class="clickshield_down">
    <div id="clickshield_msg" class="clickshield_msg">
        <p class="clickshield_title">Loading for 0 seconds.</p>
    </div>
    <div id="poll_msg" class="poll_msg">
        <pre class="poll_title">Polling...</pre>
    </div>
    <div id="cmd_msg" class="cmd_msg">
        <button onclick="$('cmd').value=''">Clear</button>
        <input id="cmd" type="input" size="70" />
        <button onclick="var cmd=$('cmd').value; if (cmd && ! polling) poll('pscreen', cmd)">Enter</button>
        <button onclick="pollEnd('Hit Enter')">End Polling</button>
    </div>
</div>

</body>
</html>