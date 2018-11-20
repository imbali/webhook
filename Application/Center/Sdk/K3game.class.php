<?php
namespace Center\Sdk;

use Center\Controller\SdkController;

class K3game extends SdkController {

    public function login(array $args)
    {
        $userid = isset($args['userid']) ? $args['userid'] : null;

        if ( is_null($userid) ) {
            die(err_return_entry($this->callback,'参数不全'));
        }

        // 统一登录流程
        return $this->_doLogin($args, $userid);
    }

}