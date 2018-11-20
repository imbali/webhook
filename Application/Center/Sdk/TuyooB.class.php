<?php
namespace Center\Sdk;

use Center\Controller\SdkController;

class TuYooB extends SdkController {

//    public function login(array $args)
//    {
//        $userid = isset($args['userid']) ? $args['userid'] : null;
////        $passwd = isset($args['passwd']) ? $args['passwd'] : null;
//
//        if ( is_null($userid) ) {
//            die(err_return_entry($this->callback,'参数不全'));
//        }
////        if ( $passwd !== '123456' ) {
////                   die(err_return_entry($this->callback,'密码错误'));
////        }
//
//        // 统一登录流程
//        return $this->_doLogin($args, $userid);
//    }

    public function login()
    {
        $args = empty($_POST) ? $_GET : $_POST;

        // 检查必须参数(测试)
//        if ( !isset($args['channel']) ) {
//            die('参数有误!');
//        }


        $userid = isset($args['userid']) ? $args['userid'] : null;
//        dump($userid);die;
//        $passwd = isset($args['passwd']) ? $args['passwd'] : null;

//        if (is_null($userid) || is_null($passwd)) {
        if (is_null($userid)) {
            die(err_return_entry($this->callback, '参数不全'));
        }
        /*
                if ( $passwd !== '123456' ) {
                   die(err_return_entry($this->callback,'密码错误'));
                }
        */
        if (!$this->_checkActive($userid)) {
            die(err_return_entry($this->callback, '激活码无效'));
        }
        // 统一登录流程
        return $this->_doLogin($args, $userid);
    }

}
