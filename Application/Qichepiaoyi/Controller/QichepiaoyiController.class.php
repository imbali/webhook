<?php

namespace Qichepiaoyi\Controller;

use Common\Controller\CenterBaseController;
use Common\Util\Log;
use Common\Mongo\TableName;


/**
 * 游戏服务器接口
 */
class QichepiaoyiController extends CenterBaseController
{
    protected $appid = "wx703b519c64a30870";
    protected $secret = "6810dfb748041685c3b63222dd7f774d";
    protected $expiry = 1296000;
    protected $keyConfig = ['diaNum','lastLoginTime','loginCount','playTimes','coin','usingCar','carGotList','rewardGotList','dayout','topScore','topScoreVer','topScoreTime','maxCombo','maxComboVer','maxComboTime','relay','relayVer','relayTime', 'pieceInfos', 'carVideoInfos'];
    protected $db_config = [                              /* Mongodb数据库配置 */
        'host'     => '172.16.16.5',
        'port'     => 20051,
        'database' => 'Qichepiaoyi',
        'username' => 'root',
        'password' => 'root',
        'options' => [
            'database' => 'admin' // sets the authentication database required by mongo 3
        ],
    ];

    public function _initialize()
    {
        parent::_initialize();
        session_destroy();
    }

    public function authentication()
    {
        $post = I();

        $appid = $this->appid;
        $secret = $this->secret;
//        $this->doLog($post["data"],'code_log');
        $loginData = htmlspecialchars_decode($post["data"]) ;
        $dataLogin = json_decode($loginData,true);

        $code = $dataLogin["code"];
//        $this->doLog($dataLogin,'code_log');
        if(!$code){
            $ret = [
                'retCode' => 0,
                'data' => 'code错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
//        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code';
//        die(json_encode($url, JSON_UNESCAPED_UNICODE));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
//        if (!empty($data)){
//            curl_setopt($curl, CURLOPT_POST, 1);
//            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
//        return $output;
        $res = json_decode($output,true);
        $this->doLog(json_encode($res, JSON_UNESCAPED_UNICODE),'login_log');
        if(isset($res['openid'])){
            $data = [
                'retCode' => 1,
                'data' => [
                    'openid' => $res['openid'],
                ],
            ];
            $db = O('',$this->db_config);
            $collection = $db->table('user');
            $opid = $collection->findOne(['openid'=>$res['openid']]);

            if($opid){
                $data['data']['lastLoginTime'] = $opid['logintime'];    //返回的最后登录时间
                $res['logintime'] = time();                     //更新数据库中的登录时间
                $result = $collection->updateOne(['id' => intval($opid['id'])], ['$set' => $res]);
                $param = [
                    'auth' => AUTHOR,
                    'userid'=>$opid['id'],
                ];
                $userid = $opid['id'];
            }else{
                $data['data']['lastLoginTime'] = 0;                     //第一次登录
                /* 创建主键索引 */
                $collection->createIndexes([
                    [ 'key' => ['id'=>1], 'unique' => true ],
                ]);
                $id = O('Globals',$this->db_config)->getIncId('user_id');
                $res['id'] = $id;
                $res['createtime'] = time();
                $res['logintime'] = time();
//                $res['type'] = $this->type;
                $result = $collection->insertOne($res);
                $param = [
                    'auth' => AUTHOR,
                    'userid'=>$id
                ];
                $userid = $id;
            }
            //$param = [
            //    'auth' => AUTHOR,
            //    'userid'=>$id
            //];
            $token = com_encrypt(json_encode($param),'ENCODE',AUTH_KEY,1296000 );
            $data['data']['token'] = $token;
            $data['data']['token_time'] = date('Y-m-d H:i:s', time()+1296000);

            $res = O('',$this->db_config)->table('login_user')->findOne(['userid'=>$userid]);
            if($res){
                $userData = [];

                foreach ($this->keyConfig as $v){
                    if(isset($res[$v])){
                        $userData[$v] = $res[$v];
                    }
                }
                $data['data']['userInfo'] = $userData;
            }else{
                $collection = O('',$this->db_config)->table('login_user');
                $collection->createIndex(['userid'=>1]);
                $insertData = ['userid'=>$userid];
                $result = $collection->insertOne($insertData);
            }

            $ip = get_client_ip();
            Vendor('cityip.City');
            $cityobj = new \City();
            $ipCityData = $cityobj->find($ip);
            $ipArea = [
                'country' => $ipCityData[0],
                'province' => $ipCityData[1],
                'city' => $ipCityData[2],
                'sp' => $ipCityData[4],
            ];
            $data['data']['ipArea'] = $ipArea;

            $config = O('',$this->db_config)->table('config')->findOne();
            unset($config['_id']);
            $data['data']['config'] = $config;
            $data['data']['time'] = time();

        }else{
            $data = [
                'retCode' => 0,
                'errcode' => $res['errcode'],
                'errmsg' => $res['errmsg'],
            ];
        }
        //$this->doLog(json_encode($data, JSON_UNESCAPED_UNICODE),'login_code_log');
        die(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function decodeData()
    {
        $post = I();
        $data = $post["data"];
        $checkdata = checkdata($data);
        $userid = $checkdata['userid'];
        $dataEncode = $checkdata['data'];

//        $openid = $post['openid'];
        $data = $dataEncode['data'];
        $iv = $dataEncode['iv'];

        $collection = O('',$this->db_config)->table('user');
        $opid = $collection->findOne(['id'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }        

        $session_key = $opid['session_key'];

        $encryptedData = base64_decode($data);
        $aeskey = base64_decode($session_key);
        $ivRes = base64_decode($iv);
        //$res = mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$aeskey,$encryptedData,MCRYPT_MODE_CBC,$ivRes);
        //dump($res);die;
        $rypted = openssl_decrypt($encryptedData, 'aes-128-cbc', $aeskey, OPENSSL_RAW_DATA, $ivRes);
        //dump(json_decode($rypted,true));die;
        $ret = [
            'retCode' => 1,
            'data' => json_decode($rypted,true),
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function login(){
        $post = I();
        $data = $post["data"];
        $checkdata = checkdata($data);
        $userid = $checkdata['userid'];
        $dataLogin = $checkdata['data'];
        $opid = O('',$this->db_config)->table('user')->findOne(['id'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $nickName = $dataLogin['nickName'];
        $avatarUrl = $dataLogin['avatarUrl'];
        $province = $dataLogin['province'];
        $city = $dataLogin['city'];
        $gender = $dataLogin['gender'];
        
        $res = O('',$this->db_config)->table('login_user')->findOne(['userid'=>$userid]);
        if($res){
            
            $data = [
                //'openid' => $opid['openid'],
                'nickName' => $nickName,
                'avatarUrl' => $avatarUrl,
                'province' => $province,
                'city' => $city,
                'gender' => $gender,
                'login_time' => time(),
            ];
            $result2 = O('',$this->db_config)->table('login_user')->updateOne(['userid' => intval($res['userid'])], ['$set' => $data]);

        }else{
            $collection = O('',$this->db_config)->table('login_user');
//            $collection->createIndexes([
//                    [ 'key' => ['id'=>1], 'unique' => true ],
//                    [ 'key' => ['userid'=>1] ],
//                    [ 'key' => ['score'=>1] ],
//                ]);
            $collection->createIndex(['userid'=>1]);
//            $id = O('Globals',$this->db_config)->getIncId('login_user_id');
            $data = [
                'userid' => $userid,
                'openid' => $opid['openid'],
                'nickName' => $nickName,
                'avatarUrl' => $avatarUrl,
                'province' => $province,
                'city' => $city,
                'gender' => $gender,
                'createTime' => time(),
                'login_time' => time(),
            ];
            $result3 = $collection->insertOne($data);
        }

        $userData = [];
        foreach ($this->keyConfig as $v){
            if(isset($res[$v])){
                $userData[$v] = $res[$v];
            }
        }

        $config = O('',$this->db_config)->table('config')->findOne();
        unset($config['_id']);
        $userData['config'] = $config;
        $userData['time'] = time();

        $ret = [
            'retCode' => 1,
            'data' => $userData,
        ];     
        header('Cache-Control: no-store, max-age=0, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        flush();
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function UpdateInfo()
    {
        $post = I();
        $data = $post["data"];
        $checkdata = checkdata($data);
        $userid = $checkdata['userid'];
        $dataLogin = $checkdata['data'];
        $opid = O('',$this->db_config)->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $userData = [];
        foreach ($this->keyConfig as $v){
            if(isset($dataLogin[$v])){
                $userData[$v] = $dataLogin[$v];
            }
        }

        $result = O('',$this->db_config)->table('login_user')->updateOne(['userid' => intval($opid['userid'])], ['$set' => $userData]);

        $ret = [
            'retCode' => 1,
            'data' => '成功',
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function GetServerTime()
    {
        $ret = [
            'retCode' => 1,
            'time' => time(),
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function log()
    {
//        $post = I();
//        $data = $post['data'];
//        if(!$data){
//            $ret = [
//                'retCode' => 0,
//                'data' => '参数错误',
//            ];
//            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
//        }
//
//        $setContent = htmlspecialchars_decode($data) ;
//        $contentSet = json_decode($setContent,true);
//        $uid = $contentSet['uid'];
//        $die_floor = $contentSet['die_floor'];
//        $die_score = $contentSet['die_score'];
//
//        $collection = O()->table('kfball_log');
//
////        $collection->createIndexes([
////            [ 'key' => ['id'=>1], 'unique' => true ],
////        ]);
////        $id = O('Globals')->getIncId('kfball_log_id');
//
//        $res = [
////            'id' => intval($id),
//            'uid' => $uid,
//            'die_floor' => $die_floor,
//            'die_score' => $die_score,
//            'time' => time(),
//        ];

//        $contentSet = ['id'=>intval($id)] + $contentSet;
//        $contentSet['userid'] = $opres['id'];
//        $contentSet['type'] = $this->type;
//        $contentSet['time'] = time();
//        $result = $collection->insertOne($res);

        $ret = [
            'retCode' => 1,
            'data' => '成功',
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function SetScoreData()
    {
        $post = I();

        $data = $post["data"];
        if(!$data){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $scoreData = htmlspecialchars_decode($data) ;
        $dataScore = json_decode($scoreData,true);
        //$this->doLog(json_encode($dataScore, JSON_UNESCAPED_UNICODE),'kfball_scoredata_log');
        $token = $dataScore['token'];
        $tokenRes = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY, 1296000), true);
//        $res = $this->decode($token);
        if($tokenRes['auth'] !== AUTHOR){
            $ret = [
                'retCode' => 0,
                'data' => 'token验证失败',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $userid = intval($tokenRes['userid']);
        $opres = O('',$this->db_config)->table('user')->findOne(['id'=>$userid]);
        if(!$opres){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $score = intval($dataScore['score']);
        $floor = intval($dataScore['floor']);
        $coin  = intval($dataScore['coin']);

        $collection = O('',$this->db_config)->table('login_user');
        $scoreRes = $collection->findOne(['userid'=>$userid]);
        if($scoreRes){
            $res = [
                'coin' => $coin,
            ];
            $scoreOld = $scoreRes['score'];
            $floorOld = $scoreRes['floor'];

			if($score > 100000){
				$res['illegalScore'] = $score;
				$res['score'] = $scoreOld;
			}elseif($score > $scoreOld){
                $res['score'] = $score;
            }
            if($floor > $floorOld){
                $res['floor'] = $floor;
            }

            $result = $collection->updateOne(['id' => intval($scoreRes['id'])], ['$set' => $res]);
        }
        $ret = [
            'retCode' => 1,
            'data' => '成功',
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function GetRankData()
    {
        $post = I();
        $data = $post["data"];
        if(!$data){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $getData = htmlspecialchars_decode($data);
        $dataGet = json_decode($getData,true);
        //dump($dataGet);
        //$this->doLog(json_encode($dataGet, JSON_UNESCAPED_UNICODE),'kfball_getdata_log');
        $token = $dataGet['token'];

        $tokenRes = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY, 0), true);
//        $res = $this->decode($token);
        if($tokenRes['auth'] !== AUTHOR){
            $ret = [
                'retCode' => 0,
                'data' => 'token验证失败',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $userid = intval($tokenRes['userid']);
        $opres = O('',$this->db_config)->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opres){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $page = intval($dataGet['page']);
        $type = $dataGet['type'];

        if($type != 'world'){
            $search = $opres[$type];

            $count = O('',$this->db_config)->table('login_user')->count([$type=>$search]);
            $maxpage = intval(ceil($count/7));

            if($page > $maxpage){
                $page = $maxpage;
            }elseif ($page < 1){
                $page = 1;
            }

            $limit = 7;
            $skip = $limit*$page-7;

            $rankData = O('',$this->db_config)->table('login_user')->find([$type=>$search],['sort'=>['score'=>-1],'limit'=>$limit,'skip'=>$skip])->toArray();
        }else{
            $count = O('',$this->db_config)->table('login_user')->count();
            $maxpage = intval(ceil($count/7));

            if($page > $maxpage){
                $page = $maxpage;
            }elseif ($page < 1){
                $page = 1;
            }

            $limit = 7;
            $skip = $limit*$page-7;

            $rankData = O('',$this->db_config)->table('login_user')->find([],['sort'=>['score'=>-1],'limit'=>$limit,'skip'=>$skip])->toArray();
        }

        $rankList = [];
        $index = $skip;
        foreach($rankData as $k=>$v){
            $index = $index+1;
            $rankList[] = [
                'index' => $index,
                'openid' => $v['openid'],
                'nickName' => $v['nickName'],
                'avatarUrl' => $v['avatarUrl'],
                'province' => $v['province'],
                'city' => $v['city'],
                'gender' => $v['gender'],
                'score' => $v['score'],
            ];
        }
        $ret = [
            'retCode' => 1,
            'rankList' => $rankList,
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function SetKVData()
    {
        $post = I();
        $data = $post["data"];
        if(!$data){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $kvData = htmlspecialchars_decode($data);
        $dataKv = json_decode($kvData,true);
        //$this->doLog(json_encode($dataGet, JSON_UNESCAPED_UNICODE),'kfball_getdata_log');
        $token = $dataKv['token'];

        $tokenRes = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY, 0), true);
        if($tokenRes['auth'] !== AUTHOR){
            $ret = [
                'retCode' => 0,
                'data' => 'token验证失败',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $userid = intval($tokenRes['userid']);
        $opid = O('',$this->db_config)->table('user')->findOne(['id'=>$userid]);
//        $opres = O()->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $dataRes = $dataKv['data'];
        $collection = O('',$this->db_config)->table('kfball_kvdata');

        foreach ($dataRes as $k=>$v){
            $collection->createIndexes([
                [ 'key' => [$k=>1] ],
            ]);
        }
        $userres = $collection->findOne(['userid'=>$userid]);
        if($userres){
            $result = $collection->updateOne(['id' => intval($userres['id'])], ['$set' => $dataRes]);
        }else{
            $collection->createIndexes([
                [ 'key' => ['id'=>1], 'unique' => true ],
                [ 'key' => ['userid'=>1] ],
            ]);
            $id = O('Globals',$this->db_config)->getIncId('kfball_kvdata_id');
            $dataRes = ['id'=>intval($id),'userid'=>$userid] + $dataRes;
            $result = $collection->insertOne($dataRes);
        }

        $ret = [
            'retCode' => 1,
            'data' => '成功',
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function GetKVData()
    {
        $post = I();
        $data = $post["data"];
        if(!$data){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $kvData = htmlspecialchars_decode($data);
        $dataKv = json_decode($kvData,true);
        //dump($dataGet);
        //$this->doLog(json_encode($dataGet, JSON_UNESCAPED_UNICODE),'kfball_getdata_log');
        $token = $dataKv['token'];
        $tokenRes = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY, 0), true);
//        $res = $this->decode($token);
        if($tokenRes['auth'] !== AUTHOR){
            $ret = [
                'retCode' => 0,
                'data' => 'token验证失败',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $userid = intval($tokenRes['userid']);
        $opid = O('',$this->db_config)->table('user')->findOne(['id'=>$userid]);
//        $opres = O()->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $dataRes = $dataKv['data'];

        $collection = O('',$this->db_config)->table('kfball_kvdata');
        $userres = $collection->findOne(['userid'=>$userid]);
        $returnData = [];
        foreach ($dataRes as $k=>$v){
            $returnData[$v] = $userres[$v];
        }

        $ret = [
            'retCode' => 1,
            'data' => $returnData,
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    //日志记录
    protected function doLog($message,$table){
        $save = [
            'message' => $message,
            'time' => time(),
        ];
        $id = O('Globals',$this->db_config)->getIncId($table.'c_id');
        if (!$id) return false;
        $data = ['id' => intval($id)] + $save;
        $collection = O('',$this->db_config)->table($table);
        $result = $collection->insertOne($data);
        return $result;
    }

//    public function getCustomerMsg()
//    {
//        $post = I();
//        $signature = $post["signature"];
//        $timestamp = $post["timestamp"];
//        $nonce = $post["nonce"];
//        $echostr = $post["echostr"];
//        if($this->checkSignature($signature,$timestamp,$nonce)){
//            die($echostr);
//        }
//    }

    public function getCustomerMsg(){
//        header('Content-Type: application/json; charset=utf-8');
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr) && is_string($postStr)) {
            $postArr = json_decode($postStr, true);
            if($postArr['MsgType'] == 'event'){
                $content = '充钱才能变强!';
                $output = $this->send_message($postArr['FromUserName'],'text',$content);
                $this->doLog(json_encode($output,JSON_UNESCAPED_UNICODE),'send_message_log');
            }
        }
//        $a = json_encode($post);
        $this->doLog($postStr,'CustomerMsg_log');
        die('success');
    }

    public function send_message($fromUsername, $msgType, $content)
    {
//        header("Content-Type:text/html;Charset=utf-8");
        $data         = array(
            "touser" => $fromUsername,
            "msgtype" => $msgType,
            "text" => array("content" => $content)
        );
        $json         = json_encode($data,JSON_UNESCAPED_UNICODE);
        $access_token = $this->get_access_token($this->appid, $this->secret);     /*    * POST发送https请求客服接口api    */
        $url          = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;    //以'json'格式发送post的https请求
        $curl         = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($json)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function get_access_token($appId = '', $appSecret = '')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
        $result = file_get_contents($url);        $result = json_decode($result,true);
        $accesstoken = $result['access_token'];
        return $accesstoken;
    }

    public function checkSignature($signature,$timestamp,$nonce)
    {
//        $post = I();
//        $signature = $post["signature"];
//        $timestamp = $post["timestamp"];
//        $nonce = $post["nonce"];
//        $echostr = $post["echostr"];

        $token = 'daed4501e2315e8d1a51e23r1f1t';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if ($tmpStr == $signature ) {
            return true;
        } else {
            return false;
        }
    }
}
