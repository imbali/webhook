<?php if (!defined('THINK_PATH')) exit();?><link rel="stylesheet" type="text/css" href="/Public/app/public/css/style.css" media="all">
<link rel="stylesheet" type="text/css" href="/Public/app/public/css/base.css" media="all">

<div class="span2">
    <div class="columns-mod">
        <div class="hd cf">
            <h5>系统信息</h5>
            <div class="title-opt">
            </div>
        </div>
        <div class="bd">
            <div class="sys-info">
                <table>
                    <tr>
                        <th>PHP版本</th>
                        <td><?php echo (PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th>ThinkPHP版本</th>
                        <td><?php echo (THINK_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th>服务器操作系统</th>
                        <!-- <td><?php echo (PHP_OS); ?></td> -->
                        <?php $os=explode(' ',php_uname()); ?>
                        <td><?php echo ($os[0]); ?>&nbsp;<?php echo ($os[2]); ?></td>
                    </tr>
                    <tr>
                        <th>运行环境</th>
                        <td><?php echo ($_SERVER['SERVER_SOFTWARE']); ?></td>
                    </tr>
                    <tr>
                        <th>MYSQL版本</th>
                        <?php $system_info_mysql = M()->query("select version() as v;"); ?>
                        <td><?php echo ($system_info_mysql["0"]["v"]); ?></td>
                    </tr>
                    <tr>
                        <th>上传限制</th>
                        <td><?php echo ini_get('upload_max_filesize');?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>