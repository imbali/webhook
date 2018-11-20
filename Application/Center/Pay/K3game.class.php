<?php
namespace Center\Pay;

use Center\Controller\PSdkController;
use Common\Mongo\TableName;
use Center\Pay\Lib\K3gamePay;
use Center\Pay\Lib\K3gameApi;

class K3game extends K3gamePay {

    protected $_secret = [
        // gameId => [ gameKey, applicationId ]
        '7008' => ['decc2e06a44e61f12a030bc4951563eb', ['com.k3sea.eternity']],   //
        '4001' => ['ffc58105bf6f8a91aba0fa2d99e6f106', ['com.k3id.eternity', 'com.k3id.eternity2']],    // 印尼
        '206002' => ['b2b512cfebbb62e60c3617d6676e5feb', ['com.k3sea.eternity']],   //
        '201023' => ['95642e9083fb566dfe3a090857a5f9cf', ['com.k3sea.eternity']],   //
    ];

    public function __construct($HttpReq)
    {
        $this->_init( $HttpReq, __CLASS__ );
    }

    public function process()
    {
        $this->doLog($this->classname,'debug', http_build_query($this->_params),$destination =TableName::ORDERS_ERROR_LOG); // 调试

        //执行SDK的验证
        if (!$this->_checkParams()) {
            die($this->_retJson(1));
        }

        //设置订单信息
        $params = $this->_params;
        $orderInfo = [
            'order_id'      => $params['order_id'],                     //平台订单号(因为SDK支持多次购买,游戏订单号可能不唯一,所以用SDK订单号为准)
            'trans_id'      => $params['order_id'],                     //平台订单号
            'cp_order_id'      => $params['cp_order_id'],                     //平台订单号
            'money'         => intval($params['usd_amount']*100),       //美分 (用美金，因为对账用的美金)
            'currency'      => $params['currency_code'],                //币种
            'usd_amount'    => intval($params['pay_amount']*100),       //保留原始金额（其实是与money字段对调）
            'user_id'       => $params['user_id'],                      //平台帐号ID
            'server_id'     => intval($params['server_id']),            //服务器ID
            'role_id'       => intval($params['role_id']),              //角色ID
            'diamond'         => intval($params['game_coin']),            //游戏币数量
            'goods_id'      => $params['goods_id'],
            'os'            => $params['device_type'] == '2' ? 1 : 2,   //操作系统
            'order_time'    => intval($params['timestamp']),                    //订单时间
            'details'       => json_encode($params, JSON_UNESCAPED_UNICODE),    //订单详情
        ];
        foreach ($orderInfo as $key => $val) {
            $this->set($key, $val);
        }
        // 设置发货模式 (内购为2,按商品ID发货)
        $pay_type = intval($params['goods_type']) == 1 ? $this->PayType['K3GAME_EXT'] : $this->PayType['K3GAME_IN'];
        $this->set('pay_type', $pay_type);

        // 继续发货流程
//        $ext = new K3gamePay();
        $ret = $this->send();

        // 返回处理结果
        if ($ret === true) {
            // 成功
            die($this->_retJson(0, $params['cp_order_id']));
        }
//        elseif (in_array($ret, [PCode::K3_ROLE_EMPTY, PCode::D_ROLE_EMPTY])) {
        elseif (in_array($ret, [$this->PCode['K3_ROLE_EMPTY'], $this->PCode['D_ROLE_EMPTY']])) {
            // 角色信息有误
            die($this->_retJson(6));
        }
//        elseif (in_array($ret, [PCode::D_MYSQL_CONFLICT, PCode::D_MONGO_CONFLICT])) {
        elseif (in_array($ret, [$this->PCode['D_MYSQL_CONFLICT'], $this->PCode['D_MONGO_CONFLICT']])) {
            // 重复的也返回成功
            die($this->_retJson(0, $params['cp_order_id']));
        }
        elseif (in_array($ret, [$this->PCode['K3_SC_ERROR'], $this->PCode['K3_CONNECT_ERROR'], $this->PCode['D_MYSQL_FAIL'], $this->PCode['D_MONGO_FAIL']])) {
            // 系统异常(服务器ID有误或者MONGODB连接失败等)
            die($this->_retJson(7));
        }

        // 其他错误
        die($this->_retJson(10));
    }

    //SDK参数验证过程
    private function _checkParams()
    {
        $params = $this->_params;
        //必须的参数检测
        $fields = array(
            'order_id',         //平台唯一订单号
            'game_id',          //平台登记的游戏ID
            'application_id',   //平台登记的游戏包ID
            'user_id',          //我方平台用户ID
            'device_type',      //设备系统，数值为1代表安卓，2代表iOS
            'pay_amount',       //实际付款金额的数目，保留两位小数，例如5USD，此字段为数字5。
            'currency_code',    //实际付款金额的币种，例如5USD，此字段为USD.currency_code+pay_amount为对账依据
            'usd_amount',       //实际收款折换成美元金额数目，保留两位小数，此字段根据pay_amount和currency_code则算，单位为美金，此金额仅供参考，不做为对账依据
            'channel_id',       //充值渠道：1,Googleplay，2,appstore，其它为第三方支付
            'server_id',        //游戏区服ID
            // 'Server_name',      //固定传servername(避免服务器名称有特殊字符导致出错)
            'role_id',          //游戏角色ID
            'role_name',        //固定传rolename(避免角色名称有特殊字符导致出错)
            'goods_type',       //商品类型，数值为1代表购买金币，2代表购买非金币商品(内购全部为2)
            'game_coin',        //所需发放游戏币数目
            'goods_id',         //商品ID
            'sign',             //签名，规则为md5（game_id+order_id+user_id+game_key）
            'timestamp',        //时间戳
            'cp_order_id',      //CP订单号,回传参数
            'cp_callback',      //CP透传参数,回传参数
        );
        //必须的参数检测
        foreach ($fields as $field) {
            if (!isset($params[$field])) {
//                $this->_logger->doLog(PLog::IN_SDK, $params, PLog::I_ARGS);
                $this->doLog($this->classname,$this->PLog['IN_SDK'], $this->PLog['I_ARGS'] . ':' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
                return false;
            }
        }
        //检查gameid和appid
        $gameId = $params['game_id'];
        if (!isset($this->_secret[$gameId])) {
//            $this->_logger->doLog(PLog::IN_SDK, $params, PLog::I_APPID);
            $this->doLog($this->classname,$this->PLog['IN_SDK'], $this->PLog['I_APPID'] . ':' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_retJson(2));
        }
        $gameSecret = $this->_secret[$gameId];
        if (isset($gameSecret[1]) && !in_array($params['application_id'], $gameSecret[1])) {
//            $this->_logger->doLog(PLog::IN_SDK, $params, PLog::I_APPID);
            $this->doLog($this->classname,$this->PLog['IN_SDK'], $this->PLog['I_APPID'] . ':' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_retJson(3));
        }

        //验证加密Sign
        if ($params['sign'] != $this->_getSign($gameSecret[0])) {
//            $this->_logger->doLog(PLog::IN_SDK, $params, PLog::I_SIGN);
            $this->doLog($this->classname,$this->PLog['IN_SDK'], $this->PLog['I_SIGN'] . ':' . json_encode($params, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            die($this->_retJson(4));
        }

        return true;
    }

    private function _getSign($key)
    {
        $params     = $this->_params;
        $game_id    = $params['game_id'];
        $order_id   = $params['order_id'];
        $user_id    = $params['user_id'];

        $sourceStr = $game_id . $order_id . $user_id . $key;
        $signCheck = md5($sourceStr);
        return $signCheck;
    }

    private function _retJson($code=1, $cp_order_id='')
    {
        $code = (string)$code;
        $errCode = [
            '0' => '成功',
            '1' => '参数错误',
            '2' => 'game_id错误',
            '3' => 'application_id错误',
            '4' => 'sign错误',
            // '5' => '订单信息错误',
            '6' => '角色信息错误',
            '7' => '系统异常',
            '10' => '失败',
        ];
        $ret = [
            'code'      => $code,
            'message'   => isset($errCode[$code]) ? $errCode[$code] : $errCode['10'],
        ];
        if (!empty($cp_order_id)) {
            $ret['cp_order_id'] = $cp_order_id;
        }
        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    public function user()
    {
        $ext = new K3gameApi($this->_params);
        return $ext->user();
    }

    public function server()
    {
        $ext = new K3gameApi($this->_params);
        return $ext->server();
    }

}
