<?php

namespace flycar\Controller;

use Common\Controller\CenterBaseController;
use Common\Util\Log;
use Common\Mongo\TableName;


/**
 * 游戏服务器接口
 */
class WeixinController extends CenterBaseController
{
    protected $appid = "wx83f8ddb02ca14c3e";
    protected $secret = "e980ac40e95b8afa17e032745eb16194";

    public function _initialize()
    {
        parent::_initialize();
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

//        $tokenRes = json_decode(com_encrypt($token, 'DECODE', AUTH_KEY, 0), true);
//        if($tokenRes['auth'] !== AUTHOR){
//            $ret = [
//                'retCode' => 0,
//                'data' => 'token验证失败',
//            ];
//            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
//        }

//        $userid = intval($tokenRes['userid']);
        $userid = 1;
//        $opid = O()->table('user')->findOne(['id'=>$userid]);
//        if(!$opid){
//            $ret = [
//                'retCode' => 0,
//                'data' => 'userid错误',
//            ];
//            die(json_encode($ret, JSON_UNESCAPED_UNICODE));
//        }

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
        foreach ($dataRes as $v){
            $returnData[] = $userres[$v];
        }

        $ret = [
            'retCode' => 1,
            'data' => $returnData,
        ];
        die(json_encode($ret, JSON_UNESCAPED_UNICODE));
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

    public function login()
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

        $appid = $this->appid;
        $secret = $this->secret;
        $code = $post["code"];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
//        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
//        return $output;
        $res = json_decode($output,true);
        if(isset($res['openid'])){
            $data = [
                'retCode' => 1,
                'openid' => $res['openid'],
            ];
            $db = O();
            $collection = $db->table('login');
            $opid = $collection->findOne(['openid'=>$res['openid']]);
            if($opid){
                $result = $collection->updateOne(['id' => intval($opid['id'])], ['$set' => $res]);
            }else{
                /* 创建主键索引 */
                $collection->createIndexes([
                    [ 'key' => ['id'=>1], 'unique' => true ],
                ]);
                $result = $collection->insertOne($res);
            }
        }else{
            $data = [
                'retCode' => 0,
                'errcode' => $res['errcode'],
                'errmsg' => $res['errmsg'],
            ];
        }
        die(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function urlencode()
    {
//        $post = I();
        $data = '734b2Cbjxq+MtDqKTAmtSIWH9Db+zN1QlXY08aduJZKvjqgFYG+Ple3tIwVV+A+prxrKlg4+WgsfrWJ0IqRxgLhSLGcFaF0';
        dump($data);
        $a = urlencode($data);
        dump($a);
    }

    public function encode($string = '', $skey = 'weixin') {
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key < $strCount && $strArr[$key].=$value;
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
    }

    function decode($string = '', $skey = 'weixin') {
        $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key <= $strCount && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }

    public function updateUserData()
    {
//        O()->table('login_user')->updateMany([],['$set'=>['score'=>0,'floor'=>0]]);
        O()->table('success_log_copy')->updateMany([],['$set'=>['score'=>1,'floor'=>1]]);

    }

    public function updataKey()
    {

    }

}