<?php
namespace Center\Pay;

use Center\Controller\PSdkController;

class Test extends PSdkController {

    public function __construct($HttpReq)
    {
        $this->_init( $HttpReq, 'Test' );

    }

    public function process()
    {
        //执行SDK的验证
//        if (!$this->_checkParams()) die('fail');
        //设置订单信息
        $params = $this->_params;
        $orderInfo = [
            'order_id'		=> $params['orderId'],             //游戏订单号
            'trans_id'		=> $params['platformOrder'],       //平台订单号
            'money'			=> $params['Rmbs'] * 100,    //金额
            'order_time'	=> TIMESTAMP,                      //订单时间
            'details'		=> json_encode($params, JSON_UNESCAPED_UNICODE),	//订单详情
        ];
        foreach ($orderInfo as $key => $val) {
            $this->set($key, $val);
        }

        //继续游戏内购的发货流程
//        $shop = new Shop($this->_logger);
//        $shop->send();
        $this->shop();
        die('success');
    }

    //SDK参数验证过程
    private function _checkParams()
    {
        $params = $this->_params;
        $fields = array(
            'apiver',           //SDK服务端支付API版本
            'appId',            //cp应用ID
            'appInfo',          //
            'chargedDiamonds',  //
            'chargedRmbs',       //充值金额（元）
            'clientId',         //
            'consumeCoin',      //
            'consumeId',        //
            'orderId',          //CP透传订单号
            'platformOrder',    //SDK订单号
            'prodCount',        //商品数量
            'code',             //回调签名
            'userId',           //SDK用户ID
        );
        //必须的参数检测
        foreach ($fields as $field) {
            if (!isset($params[$field])) {
                $this->_logger->doLog(PLog::IN_SDK, $params, PLog::I_ARGS);
                return false;
            }
        }
        //对比应用KEY
        $appId = $params['appId'];
        if ( !isset($this->_secret[$appId]) ) {
            $this->_logger->doLog(PLog::IN_SDK, $params, PLog::I_APPID);
            return false;
        }
        //验证加密Sign
        $appKey = $this->_secret[$appId];
        if ($params['code'] != $this->_getSign($appKey)) {
            $this->_logger->doLog(PLog::IN_SDK, $params, PLog::I_SIGN);
            return false;
        }
        return true;
    }

    private function _getSign($appKey)
    {
        $params = $this->_params;
        unset($params['code']);
        ksort($params);
        $signArr = [];
        foreach ($params as $key => $val) {
            $signArr[] = $key . '=' . $val;
        }
        $signStr = $appKey . join('&', $signArr) . $appKey;
        return md5($signStr);
    }
}