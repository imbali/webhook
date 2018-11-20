<?php
namespace Common\Controller;
use Common\Controller\BaseController;
/**
 * 通用基类控制器
 */
class PublicBaseController extends BaseController{
    /**
     * 初始化方法
     */
    public function _initialize(){
        parent::_initialize();
    }

    /**
     * 后台用户登录
     */
    public function login() {
        $client_ip = get_client_ip();

        if (C('LOGIN_LIMIT_IP')) {
            // 配置文件中的IP
            $allowIp = C('ALLOW_IP');
            if (!in_array($client_ip, $allowIp)) {
                // 数据库中配置的IP
                $allowIpConf = D('IpAllow')->getAll();
                if (!in_array($client_ip, $allowIpConf)) {
//                    $this->error('当前IP地址不允许登录');
                  die('当前IP地址不允许登录!');
                }
            }
        }

        if (check_login()) {
            $this->redirect(MODULE_NAME.'/Index/index');
        }

        if (IS_POST || !empty(I('get.token'))) {
            $post = I('post.');

            /* 判断是否快速登录 */
            $isFastLogin = IS_POST ? false : true;

            /* 正常登录,检测验证码 */
            if ($isFastLogin===false && C('IS_CHECK_VERIFY_CODE') !== false) {
                if(!check_verify($post['verify'])){
                    $this->error('验证码输入错误！');
                }
            }

            /* 上次登录失败的时间间隔检查 */
            $loginTime = session('?loginTime') ? session('loginTime') : NOW_TIME;
            $timeGap = NOW_TIME - $loginTime;
            if ($timeGap > 3600) {
                session(null);
            }
            /* 验证登录尝试次数 */
            $tryTimes = session('?tryTimes') ? session('tryTimes') : 0;
            if ($tryTimes>4) {
                // 超出允许登录失败的次数
                session('tryTimes', ++$tryTimes);
                $this->error('登录失败次数过多！请在['.ceil((3600-$timeGap)/60).'分钟]之后尝试！');
            }

            /* 初始化登录结果为失败 */
            $loginSuccess = false;

            /* 检查用户类型 */
            if ($isFastLogin === false && $post['username'] === ADMIN_USERNAME) {
                /* 超级管理员 */
                if (C('PERMIT_ADMIN_LOGIN')===true && in_array(MODULE_NAME, C('PERMIT_ADMIN_MODULE'))) {
                    if ($post['password'] === ADMIN_PASSWORD) {
                        $userData = [
                            'id'        => 0,
                            'username'  => ADMIN_USERNAME,
                            'avatar'    => '',
                        ];
                        $loginSuccess = true;
                    }
                }else{
                    $this->error('不允许超级管理员登入！请使用其他帐号');
                }
            }
            else{
                /* 普通用户 */
                if ($isFastLogin === true) {
                    /* 快速登录验证流程 */
                    $token = I('get.token');
                    $userData = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY), true); // 验证token
                    // $loginSuccess = isset($userData['id']) && !empty($userData['id']) ? true : false;
                    if (isset($userData['id'])) {
                        $loginSuccess = true;
                        if ($userData['id'] === 0) { //超级管理员，检查是否允许快速登录
                            if (C('PERMIT_ADMIN_LOGIN')!==true || !in_array(MODULE_NAME, C('PERMIT_ADMIN_MODULE'))) {
                                $loginSuccess = false;
                            }
                        }
                    }
                }
                else {
                    $map = [
                        'username'  => $post['username'],
                        'password'  => md5($post['password']),
                        'deploy_id' => array('in' , [0,DEPLOYMENT_ID]),
                    ];
                    if (strtolower(MODULE_NAME) === 'home' && LOGIN_INTERFACE_USED === true) {
                        $url = str_replace('/index.php', '', LOGIN_STAGE_ADDR) . U('/Admin/Public/user');
                        $token = com_encrypt(json_encode($map), 'ENCODE', AUTH_KEY, 3600);
                        $result = do_curl($url, 'POST', $token, 'http', false);
                        $userData = json_decode($result, true);
                    }else{
                        $userData = M('Users')->where($map)->find();
                    }
                    if (!empty($userData)) {
                        $loginSuccess = true;
                    }
                }
            }
            /* 检查登录情况 */
            if ($loginSuccess === true) {

                /* 登录成功继续统一登录流程 */
                // session处理
                session(null);
                $auth = [
                    'id'        => $userData['id'],
                    'username'  => $userData['username'],
                    'avatar'    => $userData['avatar'],
                    'loginTime' => NOW_TIME,
                ];
                session('user', $auth);
                $user_id = session('user.id');

                /* 其他流程: TODO */
                // 因为Home模块不直接连接权限主数据库，通过接口获取权限信息
                if (strtolower(MODULE_NAME) === 'home' && LOGIN_INTERFACE_USED === true) {
                    $url = str_replace('/index.php', '', LOGIN_STAGE_ADDR) . U('/Admin/Public/auth');
                    $params = [
                        'user_id' => $user_id, //用户唯一ID
                    ];
                    $token = com_encrypt(json_encode($params), 'ENCODE', AUTH_KEY, 3600);
                    $result = do_curl($url, 'POST', $token, 'http', false);
                    $ret = json_decode($result, true);
                    if (empty($ret)) {
                        $this->error('网络错误，请重新登录！');
                    }
                    /* 缓存权限: TODO */
                    $expire = ['expire' => C('SESSION_OPTIONS.expire')];
                    // S('auth_all', $ret['all'], $expire); //所有的权限清单
                    S('auth_list_'.$user_id, $ret['had'], $expire); //用户拥有的权限

                    S('nav_list_'.$user_id,  $ret['nav'], $expire); //菜单
                } else {
                    /* 写登录日志: TODO */
                }

                //更新登录时间
                D('Users')->updateLastLoginTime($auth['id']);

//                //登录日志
                $content = '登录成功';
                $remark = get_ip_location($client_ip);
                do_log($content, $client_ip, $remark);

                /* 跳转主界面 */
                $this->success('登录成功、前往管理后台', U(MODULE_NAME.'/Index/index'), 3);
            }
            else {
                session('tryTimes', ++$tryTimes);
                session('loginTime', NOW_TIME);
                $this->error('账号或密码错误，你'.($tryTimes>=5 ? '已经被禁止登录！' : '还有'.(5-$tryTimes).'次机会！'),
                    $isFastLogin===true ? U(MODULE_NAME.'/Index/index') : '');
            }
        }
        else{
            $this->display(TMPL_PATH.'/Public/login.html');
        }
    }

    /* 退出登录 */
    public function logout(){
        $url = MODULE_NAME.'/Public/login';
        if(check_login()){
            /* 清理缓存: TODO */
            $user_id = session('user.id');
            /* 销毁session */
            session('[destroy]');
            $this->success('退出成功、前往登录页面...', U($url));
        } else {
            $this->redirect($url);
        }
    }

    /*
     * 登录验证码
     */
    public function verify() {
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

}

