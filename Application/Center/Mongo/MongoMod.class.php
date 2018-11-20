<?php
namespace Center\Mongo;

class MongoMod {

    protected $_mongo = null;
    protected $_conf  = null;
    protected $_isCon = false;

    //一些表
    protected $tb_order  = 'orders';
    protected $tb_role   = 'role';
    protected $tb_vip    = 'vip';
//    protected $tb_assets = 'assets';
    protected $tb_assets = 'bagAsset';
    protected $tb_recharge_way = 'recharge_way';
    protected $tb_recharge = 'recharge';
    protected $tb_push = 'push';

    // 构造函数
    public function __construct($config)
    {
        $this->_conf = $config;
        $this->_conf['db'] = isset($config['db']) ? $config['db'] : GAME_DB_NAME;
        $this->_doLink();
    }

    //连接mongo
    protected function _doLink()
    {
        if ($this->_mongo === null) {
            $this->_mongo = new MongoD($this->_conf);
            if ($this->_mongo->getError()===null) {
                $this->_isCon = true;
                return true;
            }
            $this->_mongo = false;
            return false;
        }
        return true;
    }

    public function isConnect()
    {
        return $this->_isCon;
    }

    public function getRecharge($order_id)
    {
        if (!$this->_doLink()) return false;
        $query = array('order_id'=>$order_id);
//        $fields = array('order_id'=>1,'id'=>1,'cost'=>1,'diamond'=>1,'get'=>1,'product_id'=>1,'product_num'=>1,'level'=>1,'vip'=>1,'time'=>1);
        $fields = array('order_id'=>1,'id'=>1,'money'=>1,'product_id'=>1,'product_num'=>1,'time'=>1);
        return $this->_mongo->findOne($this->tb_recharge_way, $query, $fields);
    }

    public function isOrderExist($order_id)
    {
        if (!$this->_doLink()) return false;
        return $this->_mongo->fetchOne($this->tb_order, array('order_id'=>$order_id), 'order_id');
    }

    public function addOrder(array $data)
    {
        //必须参数检查
        $order_id = isset($data['order_id']) ? (string)$data['order_id'] : null;
        $role_id  = isset($data['role_id'])  ? intval($data['role_id'])  : null;
        $account  = isset($data['account'])  ? (string)$data['account']  : null;
        if ( is_null($order_id) || is_null($role_id) || is_null($account) ) {
            return false;
        }
        //连接mongodb
        if (!$this->_doLink()) return false;
        $this->_mongo->ensureIndex($this->tb_order, array('order_id'=>1), array('unique'=>true)); //创建索引
        $this->_mongo->ensureIndex($this->tb_order, array('role_id'=>1), array('unique'=>false)); //创建索引
        //入库数据(所有平台统一格式)
        $payData = array(
            'order_id'      => $order_id,   //游戏订单号,没有则为平台订单号
            'role_id'       => $role_id,    //角色ID
            'account'       => $account,    //角色帐号
//            'trans_id'      => isset($data['trans_id'])  ? (string)$data['trans_id'] : '',  //平台订单号
            'goods_id'      => isset($data['goods_id'])  ? (string)$data['goods_id'] : '',  //商品ID
            'goods_num'     => isset($data['goods_num']) ? intval($data['goods_num']) : 0,  //商品数量
            'diamond'         => isset($data['diamond']) ? intval($data['diamond']) : 0,  //充值金币数
            'gift'         => isset($data['gift']) ? intval($data['gift']) : 0,  //赠送金币数
//            'money'         => isset($data['money']) ? (string)$data['money'] : '', //充值金额
//            'currency'      => isset($data['currency']) ? (string)$data['currency'] : '',  //支付货币代码
            'status'        => 0, //订单状态
            'order_time'    => isset($data['order_time']) ? intval($data['order_time']) : 0, //订单时间(UNIX时间戳)
            'create_time'   => TIMESTAMP, //入库时间(UNIX时间戳)
            'pay_type'      => isset($data['pay_type']) ? intval($data['pay_type']) : PayType::GAME_SHOP, //发货模式
        );
        return $this->_mongo->insert($this->tb_order, $payData, array('safe'=>true));
    }

    protected function getRole($role_id)
    {
        $query = array('id'=>intval($role_id));
//        $fields = array('account'=>1,'userId'=>1,'level'=>1,'sdkId'=>1,'channelId'=>1,'entryId'=>1);
        $fields = array('account'=>1,'level'=>1);
        return $this->_mongo->findOne($this->tb_role, $query, $fields);
    }

    protected function getVip($role_id)
    {
        $query = array('id'=>intval($role_id));
        $fields = array('level'=>1,'vipExp'=>1);
        return $this->_mongo->findOne($this->tb_vip, $query, $fields);
    }

    protected function getAssets($role_id)
    {
        $query = array('id'=>intval($role_id));
        $fields = array('item'=>1);
        return $this->_mongo->findOne($this->tb_assets, $query, $fields);
    }

    //验证兑换码时查询角色信息
//    public function getCodeRole($role_id)
//    {
//        //连接mongodb
//        if (!$this->_doLink()) return false;
//        $roleInfo = $this->getRole($role_id);
//        if (!$roleInfo) return false;
//        $vipInfo  = $this->getVip($role_id);
//        $data = array(
//            'role_id'   => intval($role_id),
//            'account'   => isset($roleInfo['account'])  ? $roleInfo['account']  : 0,
//            'entry_id'  => isset($roleInfo['entryId'])  ? $roleInfo['entryId']  : 0,
//            'level'     => isset($roleInfo['level'])    ? $roleInfo['level']    : 0,
//            'viplv'     => isset($vipInfo['level'])     ? $vipInfo['level']     : 0,
//        );
//        return $data;
//    }

    // 充值时查询角色信息
    public function getPayRole($role_id)
    {
        if (!$this->_doLink()) return false;
        $roleInfo = $this->getRole($role_id);
        if (!$roleInfo) return false;
        $vipInfo    = $this->getVip($role_id);
        $assetsInfo = $this->getAssets($role_id);
//        $accountInfo = O()->table('account')->findOne(['account'=>$roleInfo['account']]);
        $data = array(
            'role_id'       => intval($role_id),
            'account'       => $roleInfo['account'],
//            'user_id'       => $accountInfo['userId'],
            'level'         => $roleInfo['level'],
//            'sdk_id'        => $accountInfo['sdkId'],
//            'channel_id'    => $accountInfo['channelId'],
//            'entry_id'      => $roleInfo['entryId'],
            'viplv'         => isset($vipInfo['level']) ? $vipInfo['level'] : 0, //当前VIP等级
            'vip_exp'       => isset($vipInfo['vipExp']) ? $vipInfo['vipExp'] : 0, //VIP总经验
            'stone_total'   => isset($assetsInfo['item'][1]['num'])&&$assetsInfo['item'][1]['id']=='3' ? $assetsInfo['item'][1]['num'] : 0,
        );
        return $data;
    }

    // 查询是否角色的首充
    public function isFirstPay($role_id)
    {
        if (!$this->_doLink()) return false;
        $info = $this->_mongo->findOne($this->tb_recharge, ['id'=>intval($role_id)], ['firstRecharge'=>1]);
//        return isset($info['log'])&&!empty($info['log'])&&is_array($info['log']) ? true : false;
        return isset($info['firstRecharge'])&&!empty($info['firstRecharge'])&&$info['firstRecharge']==1 ? false : true;
    }

    // efunfun角色信息
    public function userEfunfun($user_id)
    {
        if (!$this->_doLink()) return false;
        $query = array('userId'=>trim($user_id), 'isDel'=>0);
        $fields = array('id'=>1,'nick'=>1,'level'=>1,'force'=>1);
        $info = $this->_mongo->find($this->tb_role, $query, $fields);
        $result = [];
        if ($info) {
            $idArr = [];
            foreach ($info as $val) $idArr[] = $val['id'];
            //查找资源
            $assets = $this->_mongo->find($this->tb_assets, ['id' => ['$in' => $idArr]], ['id'=>1, 'item'=>1]);
            foreach ($info as $role) {
                $line = $role;
                $line['gold'] = $line['diamond'] = 0;
                foreach ($assets as $v) {
                    if ($role['id'] == $v['id']) {
                        $line['diamond']    = isset($v['item']['1']) ? $v['item']['1']['num'] : 0; //钻石
                        $line['gold'] = isset($v['item']['3']) ? $v['item']['3']['num'] : 0; //金币
                    }
                }
                $result[] = $line;
            }
        }
        return $result;
    }

    // efunfun用户角色定位
    public function userCheckEfunfun($user_id, $role_id)
    {
        if (!$this->_doLink()) return false;
        $query = array('id'=>intval($role_id), 'userId'=>trim($user_id), 'isDel'=>0);
        $fields = array('id'=>1);
        $info = $this->_mongo->findOne($this->tb_role, $query, $fields);
        return $info ? true : false;
    }

    // efun角色信息
    public function userEfun($user_id)
    {
        if (!$this->_doLink()) return false;
        $query = array('userId'=>trim($user_id), 'isDel'=>0);
        $fields = array('id'=>1,'nick'=>1,'level'=>1, 'entryId'=>1);
        $info = $this->_mongo->find($this->tb_role, $query, $fields);
        return $info;
    }

    // efun用户角色定位
    public function userCheckEfun($user_id, $role_id)
    {
        if (!$this->_doLink()) return false;
        $query = array('id'=>intval($role_id), 'userId'=>trim($user_id), 'isDel'=>0);
        $fields = array('id'=>1);
        $info = $this->_mongo->findOne($this->tb_role, $query, $fields);
        return $info ? true : false;
    }

    // k3game用户角色定位
    public function userCheckK3($role_id)
    {
        if (!$this->_doLink()) return false;
        $query = array('id'=>intval($role_id), 'isDel'=>0);
        $fields = array('id'=>1);
        $info = $this->_mongo->findOne($this->tb_role, $query, $fields);
        return $info ? true : false;
    }

    // 获取IOS消息推送所需要的devicetoken(全服)
    public function getDeviceToken(array $target)
    {
        if (!$this->_doLink()) return false;

        $funcId = function($arr) {
            if (!$arr) return [];
            foreach ($arr as $v) $res[] = $v['id'];
            return $res;
        };
        $idArr = $idArrLevel = $idArrViplv = [];
        if (isset($target['level']) && !empty($target['level'])) {
            $idArrLevel = $this->_mongo->find($this->tb_role, ['level'=>$target['level']], ['id'=>1]);
            $idArrLevel = $funcId($idArrLevel);
        }
        if (isset($target['viplv']) && !empty($target['viplv'])) {
            $idArrViplv = $this->_mongo->find($this->tb_vip, ['level'=>$target['viplv']], ['id'=>1]);
            $idArrViplv = $funcId($idArrViplv);
        }

        // 求交集
        if ($idArrLevel && $idArrViplv) {
            $idArr = array_intersect($idArrLevel, $idArrViplv);
        }
        elseif ($idArrLevel) {
            $idArr = $idArrLevel;
        }
        elseif ($idArrViplv) {
            $idArr = $idArrViplv;
        }
        // 查询条件
        $query = [];
        if ($idArr) $query['id'] = ['$in' => array_values($idArr)];
        if (isset($target['login']) && !empty($target['login'])) $query['time'] = $target['login'];
        // var_dump($query);
        $data = $this->_mongo->find($this->tb_push, $query, ['id'=>1,'ios_id'=>1]);
        if (!$data) return false;

        $result = $tokenArr = [];
        foreach ($data as $v) {
            if (!preg_match('~^[a-f0-9]{64}$~i', $v['ios_id'])) continue;
            $tokenArr[$v['id']]['token'] = $v['ios_id'];
        }
        if ($tokenArr) {
            // 获取子渠道号
            $idChannel = $this->_mongo->find($this->tb_role, ['id'=>['$in'=>array_keys($tokenArr)]], ['id'=>1,'channelId'=>1]);
            foreach ($idChannel as $v) $tokenArr[$v['id']]['channelId'] = $v['channelId'];
            foreach ($tokenArr as $val) $result[$val['channelId']][] = $val['token'];
        }
        return $result;
    }

    // k3角色信息
    public function userK3($account)
    {
        if (!$this->_doLink()) return false;
        $query = array('account'=>trim($account), 'isDel'=>0);
        $fields = array('id'=>1,'name'=>1,'level'=>1);
        $info = $this->_mongo->find($this->tb_role, $query, $fields);
        return $info;
    }

}