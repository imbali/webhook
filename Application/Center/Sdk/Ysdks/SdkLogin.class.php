<?php
namespace Center\Sdk\Ysdks;
use Center\Sdk\LoginApp;

class SdkLogin extends LoginApp{

    protected $server_name = 'ysdk.qq.com';             // 正式环境
    protected $server_name_test = 'ysdktest.qq.com';    // 调试环境

    /* 初始化方法 */
    protected function init() {
        dump($this->keys);
    }

    /* 登录检查 */
    public function check(array $args) {
        return 'fdfdfdfdfdfd';
    }

}
