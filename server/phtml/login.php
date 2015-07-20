<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link rel="icon" href="favicon.ico" />
<link rel="stylesheet" href="client/css/main.css?<?=$this->asset_random?>" />

<!--
<script src="client/js/MooTools-Core-1.5.1-compressed.js"></script>
<script src="client/js/MooTools-More-1.5.1-compressed.js"></script>
-->

<script src="client/js/MooTools-Core-1.5.1.js"></script>
<script src="client/js/MooTools-More-1.5.1.js"></script>

<title>AnyPanel - Login</title>
<script>
window.addEvent('domready', function() {
    $('myForm').getElements('[type=text], [type=password]').each(function(el) { new OverText(el, { wrap: false }); });
    $('myButton').addEvent('click', function() {
        this.disabled = true;
        $('myForm').submit();
    });
});
</script>
<style>
.maincontent {
    margin: 20px auto;
    width: 640px;
    border: 1px solid #C2C2C2;
    border-radius: 10px;
    text-align: center;
    padding: 5px;
}
</style>
</head>
<?php flush(); ?>
<body>

    <form id="myForm" name="login" action="index.php" method="post" autocomplete="off">
        <div class="maincontent">
            <div id="head" class="head">
                <a href="index.php"><img src="client/img/title_anypanel.png" border="0" width="178" height="48" /></a>
            </div>
            <?php
                if ($notValid = urldecode($notValid))
                {
                    $cssClass = (stristr($notValid, 'ERROR')) ? 'submit_status submit_status_error' : 'submit_status submit_status_noerror';
                    echo '<div class="' . $cssClass . '">' . $notValid . '</div>';
                }
            ?>
            <input type="text" autocomplete="off" name="username" value="<?=$username?>" size="10" maxlength="40" title="Username" />
            <input type="password" autocomplete="off" name="password" value="<?=$password?>" size="10" maxlength="40" title="Password" />
            <button id="myButton">Login</button>
            <br /><br />
                <?=$select_roles?>
            <br /><br />
	          Username: <b>demo</b> / Password: <b>demo</b>
	        <br /><br />
            <div id="foot" class="foot">
                All Rights Reserved &copy; <?=date('Y')?>
            </div>
        </div>
        <input type="hidden" name="submitted" value="login" />
    </form>

</body>
</html>
