<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>跳转提示 - bjyadmin</title>
        <meta http-equiv="Cache-Control" content="no-transform" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="stylesheet" href="/Public/statics/bootstrap-3.3.5/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/Public/statics/bootstrap-3.3.5/css/bootstrap-theme.min.css" />
    <link rel="stylesheet" href="/Public/statics/font-awesome-4.4.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="/Public/app/public/css/base.css" />
</head>
<body>

<div class="xb-h-100"></div>
<div class="xb-out">
    <ul class="bjy-public-jump">
        <li class="bjy-pj-word">
            <b><?php echo ($message); echo ($error); ?></b>
        </li>
        <li class="bjy-pj-word">
            页面将在<b id="wait"><?php echo ($waitSecond); ?></b>秒后<a id="href" href="<?php echo ($jumpUrl); ?>">跳转</a>
        </li>
    </ul>
</div>

<!-- 引入bootstrjs部分开始 -->
<script src="/Public/statics/js/jquery-1.10.2.min.js"></script>
<script src="/Public/statics/bootstrap-3.3.5/js/bootstrap.min.js"></script>
<script src="/Public/app/public/js/base.js"></script>
<script type="text/javascript">
(function(){
    var wait = document.getElementById('wait'),href = document.getElementById('href').href;
    var interval = setInterval(function(){
        var time = --wait.innerHTML;
        if(time <= 0) {
            location.href = href;
            clearInterval(interval);
        };
    }, 1000);
})();
</script>
</body>
</html>