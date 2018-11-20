<?php

namespace Center\Controller;

use Common\Controller\CenterBaseController;
use Common\Util\Log;
use Common\Mongo\TableName;


/**
 * 游戏服务器接口
 */
class KfballController extends CenterBaseController
{
    protected $appid = "wxdd586b2f7e9df847";
    protected $secret = "f6718924e94a443b06bf18969411c8d1";
    protected $type = "kfball";
    protected $expiry = 1296000;

    public function _initialize()
    {
        parent::_initialize();
    }

    public function getContent()
    {
        $post = I();
        $token = $post['token'];
        $res = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY, 0), true);
//        $res = $this->decode($token);
        if($res['auth'] !== AUTHOR){
            $ret = [
                'retCode' => 0,
                'data' => 'token验证失败',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $data = O()->table('weixin')->findOne(['id'=>1]);
        $ret = [
            'retCode' => 0,
            'data' => $data['content'],
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function authentication()
    {
        $post = I();

        $appid = $this->appid;
        $secret = $this->secret;
        $loginData = htmlspecialchars_decode($post["authData"]) ;
        $dataLogin = json_decode($loginData,true);

        $code = $dataLogin["code"];
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
        //$this->doLog(json_encode($res, JSON_UNESCAPED_UNICODE),'login_log');
        if(isset($res['openid'])){
            $data = [
                'retCode' => 1,
                'data' => [
                    'openid' => $res['openid'],
                ],
            ];
            $db = O();
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
            }else{
                $data['data']['lastLoginTime'] = 0;                     //第一次登录
                /* 创建主键索引 */
                $collection->createIndexes([
                    [ 'key' => ['id'=>1], 'unique' => true ],
                ]);
                $id = O('Globals')->getIncId('user_id');
                $res['id'] = $id;
                $res['createtime'] = time();
                $res['logintime'] = time();
//                $res['type'] = $this->type;
                $result = $collection->insertOne($res);
                $param = [
                    'auth' => AUTHOR,
                    'userid'=>$id
                ];
            }
            //$param = [
            //    'auth' => AUTHOR,
            //    'userid'=>$id
            //];
            $token = com_encrypt(json_encode($param),'ENCODE',AUTH_KEY,1296000 );
            $data['data']['token'] = $token;
            $data['data']['token_time'] = date('Y-m-d H:i:s', time()+1296000) ;

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
        $data = $post["encodeData"];
        if(!$data){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }


        $encodeData = htmlspecialchars_decode($data) ;
        $dataEncode = json_decode($encodeData,true);
//        $this->doLog(json_encode($dataEncode, JSON_UNESCAPED_UNICODE),'encode_log');
        $token = $dataEncode['token'];
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

//        $openid = $post['openid'];
        $data = $dataEncode['data'];
        $iv = $dataEncode['iv'];

        $collection = O()->table('user');
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
        $data = $post["loginData"];
        if(!$data){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $loginData = htmlspecialchars_decode($data) ;
        $dataLogin = json_decode($loginData,true);
        //$this->doLog(json_encode($dataLogin, JSON_UNESCAPED_UNICODE),'login_user_log');
        $token = $dataLogin['token'];
        $tokenRes = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY, 1296000), true);
        if($tokenRes['auth'] !== AUTHOR){
            $ret = [
                'retCode' => 0,
                'data' => 'token验证失败',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $userid = intval($tokenRes['userid']);
        $opid = O()->table('user')->findOne(['id'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
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

        $nickName = $dataLogin['nickName'];
        $avatarUrl = $dataLogin['avatarUrl'];
        $province = $dataLogin['province'];
        $city = $dataLogin['city'];
        $gender = $dataLogin['gender'];
//        $logintime = time();

        $result = O()->table('login_user')->findOne();
        if($result){
            $kfballKey = O()->table('kfball_key')->findOne();
            $scoreKey = 'sk' . $kfballKey['score'];
            $relayKey = 'rk' . $kfballKey['relay'];
        }else{
            $scoreKey = 'sk1';
            $relayKey = 'rk1';
            $param = [
                'score' => 1,
                'relay' => 1,
            ];
            O()->table('kfball_key')->insertOne($param);
        }

        $res = O()->table('login_user')->findOne(['userid'=>$userid]);
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

            $lastlogin = $res['login_time'];
            $today = strtotime(date('Y-m-d'));
            if($lastlogin >= $today){
                $isNewDayLogin = 0;
                $loginDays = $res['loginDays'];
            }else{
                $isNewDayLogin = 1;
                $loginDays = $res['loginDays'] + 1;
                $data['loginDays'] = $loginDays;
            }

            $createTime = time() - $res['time'];
            if($createTime > 172800){
                $isOldUser = 1;
            }else{
                $isOldUser = 0;
            }

            $result2 = O()->table('login_user')->updateOne(['id' => intval($res['id'])], ['$set' => $data]);
            $coin = $res['coin'];
            $bestScore = $res['bestScore'];
            $shareRewardCoinTimes = $res['shareRewardCoinTimes'];
            $hardTimes = $res['hardTimes'];
            $roateTimes = $res['roateTimes'];
            $timeTimes = $res['timeTimes'];
            $skin = $res['skin'];
            $effect = $res['effect'];
            $super = $res['super'];
            $se = $res['se'];

            if($hardTimes === null){
                $hardTimes = 0;
            }

            if($roateTimes === null){
                $roateTimes = 0;
            }

            if($timeTimes === null){
                $timeTimes = 0;
            }
        }else{
            $collection = O()->table('login_user');
            $collection->createIndexes([
                [ 'key' => ['id'=>1], 'unique' => true ],
                [ 'key' => ['userid'=>1] ],
                [ 'key' => ['score'=>1] ],
            ]);
            $id = O('Globals')->getIncId('login_user_id');
            $data = [
                'id' => $id,
                'userid' => $userid,
                'openid' => $opid['openid'],
                'nickName' => $nickName,
                'avatarUrl' => $avatarUrl,
                'province' => $province,
                'city' => $city,
                'gender' => $gender,
                'coin' => 10,
                'bestScore' => 0,
                'shareRewardCoinTimes' => 0,
                'hardTimes' => 0,
                'roateTimes' => 0,
                'timeTimes' => 0,
                'score' => 0,
                'floor' => 0,
                'loginDays' => 1,
                'time' => time(),
                'login_time' => time(),
            ];
            $result3 = $collection->insertOne($data);
            $coin = 10;
            $bestScore = 0;
            $shareRewardCoinTimes = 0;
            $isNewDayLogin = 1;
            $hardTimes = 0;
            $roateTimes = 0;
            $timeTimes = 0;
            $skin = null;
            $effect = null;
            $super = null;
            $se = null;
            $loginDays = 1;
            $isOldUser = 0;
        }

        $config = O('',$this->db_config)->table('config')->findOne();
        unset($config['_id']);
        $ret = [
            'retCode' => 1,
            'data' => [
                'coin' => $coin,
                'bestScore' => $bestScore,
                'scoreKey' => $scoreKey,
                'relayKey' => $relayKey,
                'isNewDayLogin' => $isNewDayLogin,
                'shareRewardCoinTimes' => $shareRewardCoinTimes,
                'hardTimes' => $hardTimes,
                'roateTimes' => $roateTimes,
                'timeTimes' => $timeTimes,
                'skin' => $skin,
                'effect' => $effect,
                'super' => $super,
                'se' => $se,
                'ipArea' => $ipArea,
                'loginDays' => $loginDays,
                'isOldUser' => $isOldUser ,
                'shareConfig' => $config,
                'nowTime' => time(),
                'VERIFY_VER'=>'8.3.0',
                'ALL_VERIFY_VER'=>'8.0.0;8.0.1',
                'WX_SHARE_CALLBACK'=>'open',
                'CROSS_AD_OPEN'=>'close'
            ],
        ];
        header('Cache-Control: no-store, max-age=0, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        flush();
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

        $data = $post["scoreData"];
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
        $opres = O()->table('user')->findOne(['id'=>$userid]);
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

        $collection = O()->table('login_user');
        $scoreRes = $collection->findOne(['userid'=>$userid]);
        if($scoreRes){
            $res = [
                'coin' => $coin,
            ];
            $scoreOld = $scoreRes['score'];
            $floorOld = $scoreRes['floor'];

			if($score > 300000){
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
        $data = $post["getRankParam"];
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
        $opres = O()->table('login_user')->findOne(['userid'=>$userid]);
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

            $count = O()->table('login_user')->count([$type=>$search]);
            $maxpage = intval(ceil($count/7));

            if($page > $maxpage){
                $page = $maxpage;
            }elseif ($page < 1){
                $page = 1;
            }

            $limit = 7;
            $skip = $limit*$page-7;

            $rankData = O()->table('login_user')->find([$type=>$search],['sort'=>['score'=>-1],'limit'=>$limit,'skip'=>$skip])->toArray();
        }else{
            $count = O()->table('login_user')->count();
            $maxpage = intval(ceil($count/7));

            if($page > $maxpage){
                $page = $maxpage;
            }elseif ($page < 1){
                $page = 1;
            }

            $limit = 7;
            $skip = $limit*$page-7;

            $rankData = O()->table('login_user')->find([],['sort'=>['score'=>-1],'limit'=>$limit,'skip'=>$skip])->toArray();
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
        $data = $post["kvData"];
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
        $opid = O()->table('user')->findOne(['id'=>$userid]);
//        $opres = O()->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $dataRes = $dataKv['data'];
        $collection = O()->table('kfball_kvdata');

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
            $id = O('Globals')->getIncId('kfball_kvdata_id');
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
        $data = $post["kvData"];
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
        $opid = O()->table('user')->findOne(['id'=>$userid]);
//        $opres = O()->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $dataRes = $dataKv['data'];

        $collection = O()->table('kfball_kvdata');
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

    public function GetServerTime()
    {
        $ret = [
            'retCode' => 1,
            'time' => time(),
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function GetAvatar()
    {
        $post = I();
        if(!isset($post["gender"])){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $gender = intval($post["gender"]);
        $collection = O()->table('login_user');
        $count = $collection->count();
        $randNum = rand(1,$count);
        $result = $collection->findOne(['id'=>['$gte'=>$randNum],'gender'=>$gender],['projection'=>['avatarUrl'=>1]]);
        if(!$result){
            $result = $collection->findOne(['id'=>['$lt'=>$randNum],'gender'=>$gender],['projection'=>['avatarUrl'=>1]]);
        }
        $ret = [
            'retCode' => 1,
            'avatarUrl' => $result['avatarUrl'],
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function SetUserData()
    {
        $post = I();
        $data = $post["userData"];
        if(!$data){
            $ret = [
                'retCode' => 0,
                'data' => '参数错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        $setData = htmlspecialchars_decode($data);
        $dataSetUser = json_decode($setData,true);
        //dump($dataGet);
        //$this->doLog(json_encode($dataGet, JSON_UNESCAPED_UNICODE),'kfball_getdata_log');
        $token = $dataSetUser['token'];
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
//        $opid = O()->table('user')->findOne(['id'=>$userid]);
        $opres = O()->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opres){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $dataSet = $dataSetUser['data'];

        $update = [];
        if(isset($dataSet['shareRewardCoinTimes'])){
            $update['shareRewardCoinTimes'] = intval($dataSet['shareRewardCoinTimes']);
        }

        if(isset($dataSet['coin'])){
            $update['coin'] = intval($dataSet['coin']);
        }

        if(isset($dataSet['hardTimes'])){
            $update['hardTimes'] = intval($dataSet['hardTimes']);
        }

        if(isset($dataSet['roateTimes'])){
            $update['roateTimes'] = intval($dataSet['roateTimes']);
        }

        if(isset($dataSet['timeTimes'])){
            $update['timeTimes'] = intval($dataSet['timeTimes']);
        }

        if(isset($dataSet['score'])){
            $update['score'] = intval($dataSet['score']);
        }

        if(isset($dataSet['floor'])){
            $update['floor'] = intval($dataSet['floor']);
        }

        if(isset($dataSet['skin'])){
            $update['skin'] = $dataSet['skin'];
        }

        if(isset($dataSet['effect'])){
            $update['effect'] = $dataSet['effect'];
        }

        if(isset($dataSet['super'])){
            $update['super'] = $dataSet['super'];
        }

        if(isset($dataSet['se'])){
            $update['se'] = $dataSet['se'];
        }
        if($update){
            $result = O()->table('login_user')->updateOne(['userid' => $userid], ['$set' => $update]);
        }

        $ret = [
            'retCode' => 0,
            'data' => '成功',
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function SetKVDataByOID()
    {
        $post = I();
        $data = $post["kvData"];
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

        $opid = O()->table('user')->findOne(['id'=>$userid]);

        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $dataRes = $dataKv['data'];
        $dataRes['setOpenID'] = $dataKv['setOpenID'];

        $collection = O()->table('kfball_kvdatabyoid');

        $userres = $collection->findOne(['userid'=>$userid]);
        if($userres){
            $result = $collection->updateOne(['userid' => $userid], ['$set' => $dataRes]);
        }else{
            $collection->createIndexes([
//                [ 'key' => ['id'=>1], 'unique' => true ],
                [ 'key' => ['userid'=>1] ],
            ]);
//            $id = O('Globals')->getIncId('kfball_kvdata_id');
            $dataRes = ['userid'=>$userid] + $dataRes;
            $result = $collection->insertOne($dataRes);
        }

        $ret = [
            'retCode' => 1,
            'data' => '成功',
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function GetShareKVData()
    {
        $post = I();
        $data = $post["kvData"];
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
        $opid = O()->table('user')->findOne(['id'=>$userid]);
//        $opres = O()->table('login_user')->findOne(['userid'=>$userid]);
        if(!$opid){
            $ret = [
                'retCode' => 0,
                'data' => 'userid错误',
            ];
            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        $dataRes = $dataKv['data'];

        $collection = O()->table('kfball_kvdatabyoid');
        $userres = $collection->findOne(['userid'=>$userid]);

        unset($userres['_id']);
        unset($userres['userid']);
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
        $id = O('Globals')->getIncId($table.'c_id');
        if (!$id) return false;
        $data = ['id' => intval($id)] + $save;
        $collection = O()->table($table);
        $result = $collection->insertOne($data);
        return $result;
    }

    public function token(){
        $post = I();
        $token = $post['token'];
        $a = urldecode($token);
        dump(222);
        dump($a);
        //$token = "933028rOSN8W5gZ2ZHfqJvQAohJRfq%252BWTR2RhI8iwzVpCBBrKUuDqLUSvKRubulqWDFTMYgaT1QMrBOGSw70Ddl7pA";
        $tokenRes = com_encrypt($token, 'DECODE', AUTH_KEY, 1296000);
        dump($tokenRes);
    }

    public function checkdata()
    {
        $ip = get_client_ip();
//        dump($ip);
//        $ip = "192.168.1.1";
        Vendor('cityip.City');
        $cityobj = new \City();
        $city = $cityobj->find($ip);
        dump($city[2]);
    }
}
