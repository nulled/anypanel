<h1 class="pageheader">Hardware</h1>

<div class="pagesummary">
Server hardware can be viewed and expanded by clicking the menu Items below. It provides a Summary of your Servers
Hardware; Memory, CPU, Disk, Network, Graphics and Processes.
</div>

<div id="accordion" class="accordion">

<?php if ($ram): ?>
    <h2><a href="javascript:void(0)">System Memory and Hard Drive Swap Space</a></h2>
    <div id="content">
        <pre class="pre_text"><?=$ram?></pre>
    </div>
<?php endif; ?>

<?php if ($cpu): ?>
    <h2><a href="javascript:void(0)">CPU - Metrics and Detailed Capabilities</a></h2>
    <div id="content">
        <pre class="pre_text"><?=$cpu?></pre>
    </div>
<?php endif; ?>

<?php if ($top): ?>
    <h2><a href="javascript:void(0)">TOP - List of Running Processes</a></h2>
    <div id="content">
        <pre class="pre_text"><?=$top?></pre>
    </div>
<?php endif; ?>

<?php if ($load): ?>
    <h2><a href="javascript:void(0)">Server Load Average and Uptime</a></h2>
    <div id="content">
        <pre class="pre_text"><?=$load?></pre>
    </div>
<?php endif; ?>

<?php if ($df): ?>
    <h2><a href="javascript:void(0)">Partition Mounts Points and Usages</a></h2>
    <div id="content">
        <pre class="pre_text"><?=$df?></pre>
    </div>
<?php endif; ?>

<?php if ($if): ?>
    <h2><a href="javascript:void(0)">Network Interfaces and IP Addresses</a></h2>
    <div id="content">
        <pre class="pre_text"><?=$if?></pre>
    </div>
<?php endif; ?>
</div>