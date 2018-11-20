<?php

/**
 * 启用多语言功能
 **/

return array(

    // 'app_begin' => array('Behavior\CheckLang'),
    // 如果是3.2.1版本 需要改成
    'app_begin' => array('Behavior\CheckLangBehavior'),

);