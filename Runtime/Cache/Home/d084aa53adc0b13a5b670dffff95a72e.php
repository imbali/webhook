<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title><?php echo C('PLATFORM_NAME');?></title>
        <meta http-equiv="Cache-Control" content="no-transform" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="stylesheet" href="/Public/statics/bootstrap-3.3.5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/Public/statics/bootstrap-3.3.5/css/bootstrap-theme.min.css" />
    <link rel="stylesheet" href="/Public/statics/font-awesome-4.4.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="/Public/app/public/css/base.css" />
</head>
<body>
<div class="bjy-admin-nav">
    <a href="<?php echo U('Home/Server/index');?>"><i class="fa fa-home"></i> 服务器管理</a>
    &gt;
    Mongodb管理
</div>
<ul id="myTab" class="nav nav-tabs">
    <li class="active">
        <a href="#home" data-toggle="tab">Mongodb实例</a>
    </li>
   <li>
        <a href="javascript:;" onclick="add()">添加Mongodb实例</a>
    </li>
</ul>
<div id="myTabContent" class="tab-content">
   <div class="tab-pane fade in active" id="home">
        <table class="table table-striped table-bordered table-hover table-condensed">
            <tr>
                <th>ID</th>
                <th>实例名称</th>
                <th>实例的连接地址</th>
                <th>实例的连接端口</th>
                <th>管理员帐号</th>
                <th>管理员密码</th>
                <th>操作</th>
            </tr>
            <?php if(is_array($mongodb_data) || is_object($mongodb_data)): foreach($mongodb_data as $key=>$v): ?><tr>
                    <td><?php echo ($v['id']); ?></td>
                    <td><?php echo ($v['title']); ?></td>
                    <td><?php echo ($v['host']); ?></td>
                    <td><?php echo ($v['port']); ?></td>
                    <td><?php echo ($v['username']); ?></td>
                    <td><?php echo ($v['password']); ?></td>
                    <td>
                        <a href="javascript:location='<?php echo U('Home/Server/db', array('id'=>$v['id']));?>'">数据库授权</a>&nbsp;|&nbsp;
                        <a href="javascript:;" sId="<?php echo ($v['id']); ?>" sTitle="<?php echo ($v['title']); ?>" sHost="<?php echo ($v['host']); ?>" sPort="<?php echo ($v['port']); ?>" sUser="<?php echo ($v['username']); ?>" sPass="<?php echo ($v['password']); ?>" onclick="edit(this)">修改</a>&nbsp;|&nbsp;
                        <a href="javascript:if(confirm('确定删除？'))location='<?php echo U('Home/Server/deleteMongodb', array('id'=>$v['id']));?>'">删除</a>
                    </td>
                </tr><?php endforeach; endif; ?>
        </table>
   </div>
</div>

<!-- 添加菜单模态框开始 -->
<div class="modal fade" id="machine-add" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    &times;
                </button>
                <h4 class="modal-title" id="myModalLabel">
                    添加Mongodb实例
                </h4>
            </div>
            <!-- <div class="modal-body"> -->
            <div>
                <form id="add-form" class="form-inline" action="<?php echo U('Home/Server/addMongodb');?>" method="post">
                </form>
            </div>
        </div>
    </div>
</div>
<!-- 添加菜单模态框结束 -->

<!-- 修改菜单模态框开始 -->
<div class="modal fade" id="machine-edit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    &times;
                </button>
                <h4 class="modal-title" id="myModalLabel">
                    修改Mongodb实例
                </h4>
            </div>
            <div class="modal-body">
                <form id="edit-form" class="form-inline" action="<?php echo U('Home/Server/editMongodb');?>" method="post">

                </form>
            </div>
        </div>
    </div>
</div>
<!-- 修改菜单模态框结束 -->

<div id="mongodb-table">
    <table class="table table-striped table-bordered table-hover table-condensed">
        <tr>
            <th>Mongodb实例名称：</th>
            <td>
                <input class="form-control" type="text" name="title">
            </td>
        </tr>
        <tr>
            <th>实例连接地址：</th>
            <td>
                <input class="form-control" type="text" name="host">
            </td>
        </tr>
        <tr>
            <th>实例连接端口：</th>
            <td>
                <input class="form-control" type="text" name="port">
            </td>
        </tr>
        <tr>
            <th>管理员帐号：</th>
            <td>
                <input class="form-control" type="text" name="username">
            </td>
        </tr>
        <tr>
            <th>管理员密码：</th>
            <td>
                <input class="form-control" type="text" name="password">
            </td>
        </tr>
        <tr>
            <th></th>
            <td>
                <input class="btn btn-success" type="submit" value="确定">
            </td>
        </tr>
    </table>
</div>


<!-- 引入bootstrjs部分开始 -->
<script src="/Public/statics/js/jquery-1.10.2.min.js"></script>
<script src="/Public/statics/bootstrap-3.3.5/js/bootstrap.min.js"></script>
<script src="/Public/app/public/js/base.js"></script>
<!-- 引入jquery-validation部分开始 -->
<!-- <script src="/Public/statics/js/validation/jquery.validate.min.js"></script> -->
<script src="/Public/statics/js/validation/jquery.validate.js"></script>
<script src="/Public/statics/js/validation/additional-methods.js"></script>
<script src="/Public/statics/js/validation/messages_zh.js"></script>

<script>

$(document).ready(function(){

    $('#mongodb-table').hide();

    /*表单输入验证*/
    var validationInfo = {
        rules: {
            title: "required",
            host: {
                required: true,
                isHost: true,
            },
            port: {
                required: true,
                isPort: true,
            },

        },
        messages: {}
    };

    $('#add-form').validate(validationInfo);
    $('#edit-form').validate(validationInfo);

});

// 添加菜单
function add(){
    $('#add-form').children().remove();
    $("#mongodb-table").children().clone(true).appendTo("#add-form");
    $("#add-form input[type=submit]").val('添加');
    $('#machine-add').modal('show');
}

// 修改菜单
function edit(obj){
    $('#edit-form').children().remove();
    $('#edit-form').append('<input type="hidden" name="id">');
    $("#mongodb-table").children().clone(true).appendTo("#edit-form");
    $("#edit-form input[type=submit]").val('修改');
    var sId     = $(obj).attr('sId');
    var sTitle  = $(obj).attr('sTitle');
    var sHost   = $(obj).attr('sHost');
    var sPort   = $(obj).attr('sPort');
    var sUser   = $(obj).attr('sUser');
    var sPass   = $(obj).attr('sPass');
    $("#edit-form input[name='id']").val(sId);
    $("#edit-form input[name='title']").val(sTitle);
    $("#edit-form input[name='host']").val(sHost);
    $("#edit-form input[name='port']").val(sPort);
    $("#edit-form input[name='username']").val(sUser);
    $("#edit-form input[name='password']").val(sPass);
    $('#machine-edit').modal('show');
}
</script>
</body>
</html>