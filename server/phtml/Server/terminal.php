<h2 class="pageheader"><b>Terminal to Remote Server</b></h2>

<div class="pagesummary">
This tools allows you direct access to you Server via your Browser to a Shell Terminal.
Only to be used by advanced users.  functionality is limited.  You can not use VI or any
programs that require static input, like VI or VIM.
</div>

<div class="poll_button">
    <button onclick="poll('apt-get','update')">apt-get update</button> -
    <button onclick="poll('apt-get','upgrade')">apt-get upgrade</button> -
    <button onclick="poll('pscreen',' ')">pScreen Start</button>
    <hr />
    nohup <input id="cmd" type="input" /> <button onclick="var cmd=$('cmd').value; if (cmd && poll('nohup', cmd))">Enter</button>
    <?=$screen?>
</div>



