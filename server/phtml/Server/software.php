<?php if ($program AND $manpage): ?>

<h3 class="pageheader">Manpage for <?=$program?></h3><hr />
<div class="popupmsg">
    <pre><?=$manpage?></pre>
</div>

<?php elseif ($apt_get): ?>

<h3 class="pageheader">Output for <?=$action?></h3><hr />
<div class="popupmsg">
    <pre><?=$apt_get?></pre>
</div>

<?php elseif ($program AND $dpkg AND $info): ?>

<h3 class="pageheader"><?=$program?></h3><hr />
<div class="popupmsg">
    <pre><?=$info?></pre>
</div>

<?php else: ?>

<h3 class="pageheader">Software Programs, Man Pages and Package Details</h3>
<div class="pagesummary">
<i>Note:</i> <b>Directory</b>, <b>Directories</b>, <b>Path</b> and <b>Paths</b> all mean the same thing from here onward.
<hr />
<p>
The <i>Environment Variable</i> <b>PATH</b> is used to locate programs when the full path to the program is not given. Example: When
you enter the command <b>ls</b>, your <b>PATH</b> variable is used to locate where <b>ls</b> is on your System.  It does this by
breaking up (parsing) <b>PATH</b> into directories or paths (seperated by a <b>:</b> or colon), and uses those directories to search
for your program.
</p>
<a onclick="$('path_details').toggleClass('hide')">For more Details - Click Here</a>
<p id="path_details" class="hide">
The order in which the paths appear in your <b>PATH</b> environment variable is the <i>same order</i> that your System will use to locate your
program command. If your program is not found in any of the directories defined by <b>PATH</b>, a
<i>command not found</i> message will be dislayed. The order in which the paths are searched is directly based on the order as they appear in
your <b>PATH</b> variable. This allows you to save time, by only typing the command/program name, knowing that <b>PATH</b> will locate where
it is on your Computer, along with the order commands will be searched.
<br /><br />
It is important to <u>not change</u> <b>PATH</b> from it's default setting.  A standard of how programs are searched and installed is directly
related to this all important Environment Variable, called <b>PATH</b>.  If you do add a path, typically it is added to the end of the paths
already assigned.  You can break your System by changing your <b>PATH</b> variable.  Change it only if you know what you are doing.
<br /><br />
If you need to run a program not located in your <b>PATH</b> directories, you must type the full path way. Example: The standard <b>ls</b>
program is located in <b>/bin</b>, which means <b>/bin/ls</b> is executed when you just type <b>ls</b>.  If you want to run a customized
<i>ls</i> program that you placed in the path <i>/opt</i>, you must type <b>/opt/ls</b> to run that particular <i>ls</i> program, because
<i>/opt</i>, as you can see, is not set as a directory to be searched based on your <b>PATH</b> environment variable.
<br /><br />
<b>/usr/local/bin</b> is the place to put customized software, like the custom <i>ls</i> program we just theorized about.  This way,
you can tinker with compile customization settings of programs, and keep them <b>seperate</b> from where the Package System has installed
things. As you can see, by looking at <b>PATH</b> <i>/usr/local/bin</i> is searched <i>BEFORE</i> /usr, /bin and so on.  So, keep this in
mind when compiling your own software! It is highly discouraged to place ANY programs in /usr, /bin, /sbin and so on, because your Systems
Package System installs in those locations.
<br /><br />
Unlike most Control panels, that put programs where ever they want to, AnyPanel uses the native package systems to place Software.
If software is downloaded, and compiled, the results are placed in the de facto location of <i>/usr/local/</i>.  This is how Linux
was designed, and AnyPanel does not change this.
</p>
</div>

<h3 class="pageheader">Install Software with APT-GET</h3><hr />
<div class="pagesummary">

    <button onclick="ajaxGET('index.php?module=Server&method=software&action=update',1)">Update</button>
    <br /><br />
    <button onclick="ajaxGET('index.php?module=Server&method=software&action=upgrade',1)">Upgrade</button>

</div>

<table class="tableA">
    <tr class="row">
        <td class="td_center nowrap" colspan="2">
            <?="<b>PATH:</b> ".$epath?><br />
            <select size="1" onchange="var p=this.options[this.selectedIndex].value;ajaxGET('index.php?module=Server&method=software&path='+p);">
                <option value=""<?=$this->IsMarked($path, '', 1)?>>--- Choose a Path ---</option>
                <?php if (is_array($env_paths)): ?>
                    <?php foreach ($env_paths as $_path => $_path_count): ?>
                    <option value="<?=$_path?>"<?=$this->IsMarked($path, $_path, 1)?>><?=$_path_count?></option>
                    <?php endforeach; ?>
                <option value="listinstalledpackages"<?=$this->IsMarked($path, 'listinstalledpackages', 1)?>>- List Installed Packages -</option>
                <?php endif; ?>
            </select>
        </td>
    </tr>
<?php if ($list_pkgs): ?>
    <tr>
        <td class="td_left" colspan="2">
            <div class="pretext"><pre><?=$list_pkgs?></pre></div>
        </td>
    </tr>
<?php elseif ($programs): ?>
    <tr>
        <td class="td_center" colspan="2">
            <?="$num programs in $path"?>
            <hr />
        </td>
    </tr>
    <tr class="row">
        <td class="td_right"><b>Click for ManPage</b></td>
        <td class="td_center"><b>Click for Package Details<b></td>
    </tr>
    <tr>
        <td colspan="2"><hr /></td>
    </tr>
<?php foreach ($programs as $program_path => $program_name): ?>
    <tr class="row">
        <td class="td_right"><a href="javascript:void(0)" title="<?=$files[$program_name]?>" onclick="ajaxGET('index.php?module=Server&method=software&action=manpage&program=<?=rawurlencode($program_name)?>',1)"><?=$program_name?></a>&nbsp;:</td>
        <td class="td_left"><input type="text" value="<?=$program_path?>" size="45" readonly="readonly" onclick="ajaxGET('index.php?module=Server&method=software&action=dpkg&program=<?=rawurlencode($program_path)?>',1)" /></td>
    </tr>
<?php endforeach; ?>
<?php endif; ?>
</table>

<?php endif; ?>