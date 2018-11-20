<?php
namespace Common\Controller;
use Common\Controller\BaseController;
/**
 * Home基类控制器
 */
class HomeBaseController extends BaseController{

    /**
     * 初始化方法
     */
    public function _initialize(){
        parent::_initialize();
        /* 检查是否登录 */
        if (!check_login()) {
            $this->error('请先登录再访问', U('Home/Public/login'));
        }
        /* 检查权限 */
        $user = session('user');
        if ($user['id']===0 && $user['username']===ADMIN_USERNAME && C('PERMIT_ADMIN_LOGIN')===true) {
            // 超级管理员
            $result = true;
        }
        else {
            $rule_name = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
            $auth=new \Think\Auth();
            $result = $auth->check($rule_name, $user[id]);//检查用户是否拥有权限
//            $auth_list = S('auth_list','auth_list_'. $user['id']); //用户拥有的权限;
//            $result = in_array(strtolower($rule_name), $auth_list) ? true : false;
//            $result = true;
        }
        if (!$result) {
            $this->error('您没有权限访问');
        }
    }

    /**
     * 拦截空方法 自动加载html
     * @param  string $methed_name 空方法
     */
    public function _empty($methed_name){
        $this->display($methed_name);
        exit(0);
    }

    //重写,兼容以前3.1版本的
//    public function ajaxReturn($result,$message='',$status=0,$type,$json_option)
//    {
//        $data = null;
//        $data['data'] = $result; //将数据作为data
//        $data['info'] = $message; //将$message作为info
//        $data['status'] = $status; //将$status作为status
//
//        return parent::ajaxReturn($data,$type='',$json_option=0);
//    }

}

