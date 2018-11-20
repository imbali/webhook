<?php
namespace Center\Pay\Lib;

use Center\Controller\PSdkController;
use Common\Mongo\TableName;
//use Center\Mongo\MongoMod;

/* K3game - 第三方支付购买商品的处理流程 */
class K3gamePay extends PSdkController{

	public function send()
	{
        $this->def();

        $orderIdInfo = O()->table('order_id')->findOne(['order_id'=>$this->get('cp_order_id')]);
        if (!$orderIdInfo) {
            $this->doLog($this->classname,$this->PLog['SHOP'], $this->PCode['S_ORDERID_MISS'] . ':' . json_encode($this->details, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            return $this->PCode['S_ORDERID_MISS'];
        }
        $this->set('platform_id', $orderIdInfo['platform_id']);	//运营平台ID
        $this->set('entry_id', $orderIdInfo['entry_id']);		//入口服ID
        $this->set('sdk_id', $orderIdInfo['sdk_id']);		//sdkID
        $this->set('channel_id', $orderIdInfo['channel_id']);		//channelID

		//服务器ID确定(处理已合服的)
//		$serverInfo = $this->dbMod->server()->getServerInfo($this->get('server_id'));
		$serverInfo = O()->table('server')->findOne(['id'=>$this->get('server_id')]);

		if (!$serverInfo) {
//			$this->logger->doLog(PLog::K3GAME, $this->details, PCode::K3_SC_ERROR);
//	    	return PCode::K3_SC_ERROR;
            $this->doLog($this->classname, $this->PLog['K3GAME'], $this->PCode['K3_SC_ERROR'] . ':' . json_encode($this->details, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            return $this->PCode['K3_SC_ERROR'];
		}
		if ($serverInfo['merged'] == '1' && $serverInfo['id'] != $serverInfo['server_id']) {
			//已经合服的
			$this->set('server_id', $serverInfo['server_id']);
		}
		//连接mongodb
        $this->mongo = $this->getMongo($this->get('server_id'));
		if (!$this->mongo) return $this->PCode['K3_CONNECT_ERROR'];

		//角色对应查找(user_id + role_id)
//		$userIsExists = $mongo->userCheckK3($this->get('user_id'), $this->get('role_id'));
		$userIsExists = $this->mongo->userCheckK3($this->get('role_id'));
		if (!$userIsExists) {
//			$this->logger->doLog(PLog::K3GAME, $this->details, PCode::K3_ROLE_EMPTY);
//			return PCode::K3_ROLE_EMPTY;
            $this->doLog($this->classname, $this->PLog['K3GAME'], $this->PCode['K3_ROLE_EMPTY'] . ':' . json_encode($this->details, JSON_UNESCAPED_UNICODE), $destination =TableName::ORDERS_ERROR_LOG);
            return $this->PCode['K3_ROLE_EMPTY'];
		}
		//设置订单信息
		$this->set('goods_num', 1);	//购买 数量

		//调用统一发货流程（操作数据库部分）
//		$deliver = new Deliver($this->logger);
//		return $deliver->send();
        return $this->deliver();
	}

}