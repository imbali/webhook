<?php
namespace Center\Pay\Lib;

use Center\Controller\PSdkController;
use Common\Mongo\TableName;

/* K3game 获取服务器列表和用户角色列表 */
class K3gameApi{

    protected $classname   = null; //Mysql操作类
    protected $params  = null; //请求详情
//    protected $logger  = null; //写流水日志类的对象
//    protected $logFile = null; //发奖励日志

    // 构造函数，初始化一些参数
    public function __construct( array $params)
    {
//        $this->dbMod   = new MysqlMod();
        $this->params  = $params;
        $this->classname = __CLASS__;
//        $this->logger  = $logger;
//        $this->logFile = 'user';
    }

    protected function _errorReturn($msg)
    {
        $ret = [
            'code'      => 1,
            'message'   => (string)$msg
        ];
        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }


    public function server()
    {
        $logFile = 'getServerList';
        $params  = $this->params;
        if (!isset($params['user_id']) || empty($params['user_id']) || !isset($params['game_id']) || empty($params['game_id'])) {
            die($this->_errorReturn('参数错误'));
        }

        $game_id_to_platform_id = [
            '7008'      => 1,
            '4001'      => 1,
            '206002'    => 2,
        ];
        $platform_id = isset($game_id_to_platform_id[$params['game_id']]) ? $game_id_to_platform_id[$params['game_id']] : null;
        if (!$platform_id) {
//            $this->logger->doLog($logFile, $params, 'game_id错误');
            $this->doLog($this->classname, $logFile, 'game_id错误:' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_errorReturn('game_id错误'));
        }

//        $serverList = $this->dbMod->server()->getServerListK3($platform_id);
        $serverRes = O()->table('server')->find(['platform_id'=>$platform_id])->toArray();
        $serverList = array();
        foreach ($serverRes as $v){
            $serverList[] = array(
                'server_id'     => $v['id'],
                'server_name'   => $v['title'],
            );
        }
        if ($serverList) {
            $ret = [
                'code'      => 0,
                'message'   => 'success',
                'serverlist'    => $serverList,
            ];
            $data = [
                'serverlist' => $serverList,
                'data'       => $params,
            ];
            //日志
//            $this->logger->doLog($logFile.'-success', $params, $ret);
            $this->doLog($this->classname, $logFile.'-success', json_encode($data, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_SUCCESS_LOG);
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        else {
//            $this->logger->doLog($logFile, $params, '获取服务器列表失败');
            $this->doLog($this->classname, $logFile, '获取服务器列表失败:' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_errorReturn('获取服务器列表失败'));
        }
    }


    public function user()
    {
        $logFile = 'getRoleList';
        $params  = $this->params;
        if (!isset($params['user_id']) || empty($params['user_id']) || !isset($params['server_id']) || empty($params['server_id'])) {
            die($this->_errorReturn('参数错误'));
        }

        //服务器ID确定(处理已合服的)
//        $serverInfo = $this->dbMod->server()->getServerInfo($params['server_id']);
        $serverInfo = O()->table('server')->findOne(['id'=>intval($params['server_id'])]);
        if (!$serverInfo) {
            //服务器信息错误
//            $this->logger->doLog($logFile, $params, '服务器ID有误');
            $this->doLog($this->classname, $logFile, '服务器ID有误1:' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_errorReturn('服务器ID有误1'));
        }
        if ($serverInfo['merged'] == '1' && $serverInfo['id'] != $serverInfo['server_id']) {
            //已经合服的
//            $serverInfo = $this->dbMod->server()->getServerDb($serverInfo['server_id']);
            $serverInfoRes = O()->table('server')->findOne(['id'=>$serverInfo['server_id']]);
//            $machineRes = O()->table('machine')->findOne(['id'=>$serverInfoRes['machine_id']]);
            if (!$serverInfoRes) {
                //服务器信息错误
//                $this->logger->doLog($logFile, $params, '服务器ID有误');
                $this->doLog($this->classname, $logFile, '服务器ID有误2:' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
                die($this->_errorReturn('服务器ID有误2'));
            }
            $serverInfo = $serverInfoRes;
        }
        $mongo = $this->getMongo($serverInfo['id']);
//        $dbConf = [
//            'host' => $serverInfo['db_host'],
//            'port' => $serverInfo['db_port'],
//        ];
//        $mongo = new MongoMod($dbConf);
        if (!$mongo->isConnect()) {
//            $this->logger->doLog($logFile, $params, '连接mongodb失败');
            $this->doLog($this->classname, $logFile, '连接mongodb失败:' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_errorReturn('查询用户角色失败'));
        }

        $account = getaccount('k3ga', $params['user_id']);
        $userInfo = $mongo->userK3($account);
        if (!$userInfo) {
//            $this->logger->doLog($logFile, $params, '该user_id没有角色');
            $this->doLog($this->classname, $logFile, '该user_id没有角色:' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_errorReturn('该user_id没有角色'));
        }

        $result = [];
        foreach ($userInfo as $value) {
            $info = [
                'role_id'   => intval($value['id']),
                'role_name' => (string)$value['name'],
            ];
            $result[] = $info;
        }

        $ret = [
            'code'      => 0,
            'message'   => 'success',
            'rolelist'  => $result,
        ];

        $data = [
            'rolelist' => $result,
            'data'     => $params,
        ];

        //日志
//        $this->logger->doLog($logFile.'-success', $params, $ret);
        $this->doLog($this->classname, $logFile.'-success', json_encode($data, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_SUCCESS_LOG);

        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    protected function doLog($identity,$log,$message,$table){
        $save = [
            'identity' => $identity,
            'log' => $log,
            'message' => $message,
            'time' => time(),
        ];
        $id = O('Globals')->getIncId($table.'c_id');
        if (!$id) return false;
        $data = ['id' => intval($id)] + $save;
        $collection = O()->table($table);
        $result = $collection->insertOne($data);
        return $result;
    }
}