<?php
namespace Common\Controller;
use Common\Controller\BaseController;

/**
 * admin 基类控制器
 */
class AdminBaseController extends BaseController{
    /**
     * 初始化方法
     */
    public function _initialize(){
        parent::_initialize();
        /* 检查是否登录 */
        if (!check_login()) {
            $this->error('请先登录再访问', U('Admin/Public/login'));
        }
        /* 检查权限 */
        $user = session('user');
        if ($user['id']===0 && $user['username']===ADMIN_USERNAME && C('PERMIT_ADMIN_LOGIN')===true) {
            // 超级管理员
            $result = true;
        }
        else {
            $auth = new \Think\Auth();
            $rule_name = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
            $result = $auth->check($rule_name, $user['id']);
        }
        if (!$result) {
            $this->error('您没有权限访问');
        }
    }

}

