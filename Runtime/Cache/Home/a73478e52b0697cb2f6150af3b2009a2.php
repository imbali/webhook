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
    <a href="<?php echo U('Home/Server/machine');?>"><i class="fa fa-home"></i> 服务器管理</a>
    &gt;
    游戏服管理
</div>
<ul id="myTab" class="nav nav-tabs">
   <li class="active">
         <a href="#home" data-toggle="tab">游戏服列表</a>
   </li>
   <li>
        <a href="javascript:;" onclick="add()">添加游戏服</a>
    </li>
</ul>
<div id="myTabContent" class="tab-content">
   <div class="tab-pane fade in active" id="home">
        <table class="table table-striped table-bordered table-hover table-condensed">
            <tr>
                <th>ID</th>
                <th>编号</th>
                <th>进程ID</th>
                <th>游戏服名称</th>
                <th>所属平台</th>
                <th>入口服</th>
                <th>状态</th>
                <th>可见类型</th>
                <th>游戏服类型</th>
                <th>所在机器</th>
                <th>网关端口</th>
                <th>mongodb</th>
                <th>开服时间</th>
                <th>是否合服</th>
                <th>合服时间</th>
                <th>操作</th>
            </tr>
            <?php if(is_array($data) || is_object($data)): foreach($data as $key=>$v): ?><tr>
                    <td><?php echo ($v['id']); ?></td>
                    <td><?php echo ($v['serial_num']); ?></td>
                    <td>
                        <?php if($v['merged'] == 1): ?>(<?php echo ($v['server_id']); ?>)&nbsp;<?php echo ($data[$v['server_id']]['title']); else: echo ($v['server_id']); endif; ?>
                    </td>
                    <td><?php echo ($v['title']); ?></td>
                    <td>(<?php echo ($v['platform_id']); ?>)&nbsp;<?php echo ($platform_data[$v['platform_id']]['title']); ?></td>
                    <td>(<?php echo ($v['entry']); ?>)&nbsp;<?php if($v["entry"] == 1): ?>是<?php else: ?>否<?php endif; ?></td>
                    <td>(<?php echo ($v['status']); ?>)&nbsp;<?php echo ($status_data[$v['status']]); ?></td>
                    <td>(<?php echo ($v['visible']); ?>)&nbsp;<?php echo ($visible_data[$v['visible']]); ?></td>
                    <td>(<?php echo ($v['type']); ?>)&nbsp;<?php echo ($type_data[$v['type']]); ?></td>
                    <td>(<?php echo ($v['machine_id']); ?>)&nbsp;<?php echo ($machine_data[$v['machine_id']]['title']); ?></td>
                    <td><?php echo ($v['gateway']); ?></td>
                    <td>(<?php echo ($v['db_id']); ?>)&nbsp;<?php echo ($db_data[$v['db_id']]['title']); ?></td>
                    <td><?php echo ($v['create_time']); ?></td>
                    <td>(<?php echo ($v['merged']); ?>)&nbsp;<?php echo ($merged_data[$v['merged']]); ?></td>
                    <td><?php echo ($v['merge_time']); ?></td>
                    <td>
                        <a href="javascript:;" sId="<?php echo ($v['id']); ?>" sInfo='<?php echo (json_encode($v,JSON_UNESCAPED_UNICODE)); ?>' onclick="edit(this)">修改</a>&nbsp;|&nbsp;
                        <a href="javascript:if(confirm('确定删除？'))location='<?php echo U('Home/Server/delete', array('id'=>$v['id']));?>'">删除</a>
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
                    添加游戏服
                </h4>
            </div>
            <div class="modal-body">
                <form id="add-form" class="form-inline" action="<?php echo U('Home/Server/add');?>" method="post">
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
                    修改游戏服
                </h4>
            </div>
            <div class="modal-body">
                <form id="edit-form" class="form-inline" action="<?php echo U('Home/Server/edit');?>" method="post">
                </form>
            </div>
        </div>
    </div>
</div>
<!-- 修改菜单模态框结束 -->

<div id="server-table" hidden>
    <table class="table table-striped table-bordered table-hover table-condensed">
        <tr>
            <th>ID：</th>
            <td>
                <input class="form-control" type="text" name="id">
            </td>
        </tr>
        <tr>
            <th>编号：</th>
            <td>
                <input class="form-control" type="text" name="serial_num">
            </td>
        </tr>
        <tr>
            <th>进程ID/游戏服ID：</th>
            <td>
                <select class="form-control" name="server_id">
                    <option value="">请选择...</option>
                    <?php if(is_array($data) || is_object($data)): foreach($data as $key=>$item): if(!isset($item['merged']) || $item['merged'] == '0'): ?><option value="<?php echo ($item["id"]); ?>">(<?php echo ($item["id"]); ?>)&nbsp;<?php echo ($item["title"]); ?></option><?php endif; endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>游戏服名称：</th>
            <td>
                <input class="form-control" type="text" name="title">
            </td>
        </tr>
        <tr>
            <th>所属平台：</th>
            <td>
                <select class="form-control" name="platform_id">
                    <option value="">请选择...</option>
                    <?php if(is_array($platform_data) || is_object($platform_data)): foreach($platform_data as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>">(<?php echo ($item["id"]); ?>)&nbsp;<?php echo ($item["title"]); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>入口服：</th>
            <td>
                <select class="form-control" name="entry">
                    <option value="">请选择...(是否在选服列表中可见)</option>
                    <option value="0">(0)&nbsp;否</option>
                    <option value="1">(1)&nbsp;是</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>状态：</th>
            <td>
                <select class="form-control" name="status">
                    <?php if(is_array($status_data) || is_object($status_data)): foreach($status_data as $key=>$item): ?><option value="<?php echo ($key); ?>">(<?php echo ($key); ?>)&nbsp;<?php echo ($item); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>可见类型：</th>
            <td>
                <select class="form-control" name="visible">
                    <?php if(is_array($visible_data) || is_object($visible_data)): foreach($visible_data as $key=>$item): ?><option value="<?php echo ($key); ?>">(<?php echo ($key); ?>)&nbsp;<?php echo ($item); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>游戏服类型：</th>
            <td>
                <select class="form-control" name="type">
                    <?php if(is_array($type_data) || is_object($type_data)): foreach($type_data as $key=>$item): ?><option value="<?php echo ($key); ?>">(<?php echo ($key); ?>)&nbsp;<?php echo ($item); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>所在机器：</th>
            <td>
                <select class="form-control" name="machine_id">
                    <option value="">请选择...</option>
                    <?php if(is_array($machine_data) || is_object($machine_data)): foreach($machine_data as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>">(<?php echo ($item["id"]); ?>)&nbsp;<?php echo ($item["title"]); ?>/<?php echo ($item["public_ip"]); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
         <tr>
            <th>网关端口：</th>
            <td>
                <input class="form-control" type="text" name="gateway">
            </td>
        </tr>
        <tr>
            <th>mongodb：</th>
            <td>
                <select class="form-control" name="db_id">
                    <option value="">请选择...</option>
                    <?php if(is_array($db_data) || is_object($db_data)): foreach($db_data as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>">(<?php echo ($item["id"]); ?>)&nbsp;<?php echo ($item["title"]); ?>/<?php echo ($item["database"]); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>开服时间：</th>
            <td>
                <input class="form-control" type="text" name="create_time">
            </td>
        </tr>
        <tr>
            <th>是否合服</th>
            <td>
                <select class="form-control" name="merged">
                    <?php if(is_array($merged_data) || is_object($merged_data)): foreach($merged_data as $key=>$item): ?><option value="<?php echo ($key); ?>">(<?php echo ($key); ?>)&nbsp;<?php echo ($item); ?></option><?php endforeach; endif; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>合服时间：</th>
            <td>
                <input class="form-control" type="text" name="merge_time">
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
<!-- 引入jquery-datetimepicker部分开始 -->
<link rel="stylesheet" type="text/css" href="/Public/statics/datetimepicker/jquery.datetimepicker.css" />
<script src="/Public/statics/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>


<script>

$(document).ready(function(){

    // $('#server-table').hide();

    /*表单输入验证*/
    var validationInfo = {
        rules: {
            id: {
                required: true,
                digits: true,
            },
            serial_num: {
                required: true,
                digits: true,
            },
            server_id: "required",
            title: "required",
            platform_id: "required",
            entry: "required",
            machine_id: "required",
            gateway: {
                required: true,
                isPort: true,
            },
            db_id: "required",
            create_time: {
                required: true,
                date: true,
            },
            merge_time: {
                date: true,
            },
        },
        messages: {
            server_id: "请选择进程ID/游戏服ID",
            platform_id: "请选择平台",
            entry: "请选择",
            machine_id: "请选择机器",
            db_id: "请选择mongodb",
        }
    };

    $('#add-form').validate(validationInfo);
    $('#edit-form').validate(validationInfo);

    $.datetimepicker.setLocale('ch');

});

// 添加菜单
function add(){
    $('#add-form').children().remove();
    $("#server-table").children().clone(true).appendTo("#add-form");
    /*删除非必须dom*/
    var delDom = ['server_id', 'merged', 'merge_time'];
    for ( var dom in delDom) {
        $("#add-form [name='"+delDom[dom]+"']").parent().parent().remove();
    }
    $("#add-form input[type=submit]").val('添加');

    $("#add-form input[name=create_time]").datetimepicker({format: "Y-m-d", timepicker: false});
    $('#machine-add').modal('show');
}

// 修改菜单
function edit(obj){
    $('#edit-form').children().remove();
    $("#server-table").children().clone(true).appendTo("#edit-form");
    $("#edit-form input[name='id']").attr("readonly", "readonly");
    $("#edit-form input[type=submit]").val('修改');

    var input_dom = ['id', 'serial_num', 'title', 'gateway', 'create_time', 'merge_time'];
    var select_dom = ['server_id', 'platform_id', 'entry', 'status', 'visible', 'type', 'machine_id', 'db_id', 'merged'];
    /*赋值*/
    var sInfo   = JSON.parse($(obj).attr('sInfo'));
    for (x in sInfo) {
        if ($.inArray(x, select_dom) != -1) {
            $("#edit-form select[name='" + x + "'] option[value='" + sInfo[x] + "']").attr("selected", 1);
        } else if($.inArray(x, input_dom) != -1) {
            var dom = $("#edit-form input[name='" + x + "']");
            dom.val(sInfo[x]);
            /*时间*/
            if ($.inArray(x, ['create_time', 'merge_time']) != -1) {
                dom.datetimepicker({format: "Y-m-d", timepicker: false});
            }
        }
    }

    $('#machine-edit').modal('show');
}

</script>
</body>
</html>