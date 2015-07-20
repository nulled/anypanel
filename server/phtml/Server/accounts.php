<h2 class="pageheader">Remote Server Management</h3>

<?php if (! $submitted): ?>

<div class="pagesummary" style="width:850px">
<ul style="margin:0 0 10px 0;">
    <li>Interactive-Keyboard</li>
    <li>RSA Private Host Key</li>
</ul>
Either method is secure, but RSA Host Keys are harder to Brute Force Attack.
Interactive Passwords are much easier to manage if you need to change passwords.
</div>

<?php if (is_array($servers) AND count($servers)): ?>

<h3 class="pageheader">Remote Servers Currently Added</h3>

<table class="tableA" style="<?=$table_list_style?>" id="hosts">
  <tr class="row nowrap">
      <td class="td_center">Selected</td>
      <td class="td_center">IP Address</td>
      <td class="td_center">Modules</td>
      <td class="td_center">Linux Distro</td>
      <td class="td_center">Terminal Prompts</td>
      <td class="td_center">Options</td>
  </tr>

<?php foreach ($servers as $host => $server): ?>
  <tr class="row">
      <td class="td_center td_data"><input id="<?=$host?>" type="radio" value="" <?=$this->IsMarked($_SESSION['host'], $host)?>/></td>
      <td class="td_center td_data"><?=$host?></td>
      <td class="td_center td_data"><?=$server['modules']?></td>
      <td class="td_center td_data"><?=$server['distro']?></td>
      <td class="td_center td_data"><?php list($p1, $p2) = explode(' ', $server['prompts']); if ($p1 AND $p2) echo "$p1<br />$p2"; ?></td>
      <td class="td_center td_data nowrap">
          <button onclick="ajaxGET('index.php?module=Server&method=accounts&submitted=load_edit&host=<?=$host?>',0,'Server/accounts.js');">Edit</button>
          <button onclick="if (confirm('Are You Sure You Want to Do This?\nClick OK To Delete.')) manageServer('<?=$host?>', 'delete');">Delete</button>
      </td>
  </tr>
<?php endforeach; ?>
</table>

<?php endif; ?>

<?php endif; ?>

<h4 class="pageheader"><?=$title_add_edit_server?></h3>

<?=$this->SubmitStatus($notValid)?>

<br />

<form id="myForm" action="index.php" method="post" autocomplete="off">
    <table id="table" class="tableA" style="<?=$table_add_style?>">

<?php if (! $authtype): ?>

        <tr class="row" id="tip">
            <td class="td_left"><input type="radio" name="auth" value="interactive_add" rel="Interactive Username and Password" />Interactive-Keyboard</td>
            <td class="td_left"><input type="radio" name="auth" value="privhostkey_add" rel="RSA 2048 Bit with Password Recommended (RSA only)" />RSA&nbsp;Private&nbsp;Host&nbsp;Key</td>
            <td class="td_left"><input id="authselect" type="button" value="Continue" /></td>
        </tr>

<?php elseif ($authtype == 'interactive'): ?>

        <tr class="row">
            <td class="td_left">Host IP Address</td>
            <td class="td_left">Account Name</td>
            <td class="td_left">Password</td>
            <td class="td_left">Password Confirm</td>
            <td class="td_left">Modules</td>
        </tr>
        <tr class="row" id="tip">
            <td class="td_left"><input type="text" autocomplete="off" name="newhost" value="<?=$newhost?>" size="14" rel="IPv4 Address (No Domains)" <?php if ($submitted == 'edit') echo 'readonly="readonly" '; ?>/></td>
            <td class="td_left"><input type="text" autocomplete="off" name="newaccount" value="<?=$newaccount?>" size="14" rel="Linux Account (Non-Root) Must Be 'sudo' enabled" /></td>
            <td class="td_left"><input type="password" autocomplete="off" name="newpass1" value="<?=$newpass1?>" size="14" rel="Account Password" /></td>
            <td class="td_left"><input type="password" autocomplete="off" name="newpass2" value="<?=$newpass2?>" size="14" rel="Password Confirm" /></td>
            <td class="td_left" nowrap>
                <?=$modulesHTML?>
            </td>
        </tr>
        <tr class="row">
            <td class="td_center" colspan="5">
                <br />
                <button id="authsave"><?=ucfirst($submitted)?></button>
                <button id="authcancel">Cancel</button>
            </td>
        </tr>

<?php elseif ($authtype == 'privhostkey'): ?>

        <tr class="row">
            <td class="td_left">IP Address</td>
            <td class="td_left">Linux Account</td>
            <td class="td_left">RSA Private Host Key (DSA not supported)</td>
            <td class="td_left">RSA Password</td>
            <td class="td_left">Modules</td>
        </tr>

        <tr class="row" id="tip">
            <td class="td_left">
                <input type="text" autocomplete="off" name="newhost" value="<?=$newhost?>" size="14" rel="IPv4 Address (No Domains)"<?php if ($submitted == 'edit') echo ' readonly="readonly" '; ?>/>
            </td>
            <td class="td_right">
                <input type="text" autocomplete="off" name="newaccount" value="<?=$newaccount?>" size="14" rel="Linux Account (Non-Root) Must Be 'sudo' enabled" />
                <br />Password&nbsp;<br />
                <input type="password" autocomplete="off" name="newpass1" value="<?=$newpass1?>" size="14" rel="Password for Linux Account" />
                <br />Confirm Password&nbsp;<br />
                <input type="password" autocomplete="off" name="newpass2" value="<?=$newpass2?>" size="14" rel="Confirm Password for Linux Account" />
            </td>
            <td class="td_left">
                <textarea name="privkey" cols="40" rows="8" style="font:10px 'Courier New'" wrap="off"><?=$privkey?></textarea>
            </td>
            <td class="td_left">
                <input type="password" autocomplete="off" name="newpass3" value="<?=$newpass3?>" size="14" rel="RSA Password (If Any)" />
                <br />Confirm RSA Password<br />
                <input type="password" autocomplete="off" name="newpass4" value="<?=$newpass4?>" size="14" rel="Confirm RSA Password (If Any)" />
            </td>
            <td class="td_left" nowrap>
                <?=$modulesHTML?>
            </td>
        </tr>

        <tr class="row">
            <td class="td_center" colspan="5">
                <br />
                <button id="authsave"><?=ucfirst($submitted)?></button>
                <button id="authcancel">Cancel</button>
            </td>
        </tr>

<?php endif; ?>

    </table>
    <input id="authtype" type="hidden" name="authtype" value="<?=$authtype?>" />
    <input               type="hidden" name="submitted" value="<?=$submitted?>" />
</form>

<?=str_repeat('<br />', 5)?>
