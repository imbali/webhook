<?php


/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
//function get_client_ip($type = 0,$adv=false) {
//    $type       =  $type ? 1 : 0;
//    static $ip  =   NULL;
//    if ($ip !== NULL) return $ip[$type];
//    if($adv){
//        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
//            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
//            $pos    =   array_search('unknown',$arr);
//            if(false !== $pos) unset($arr[$pos]);
//            $ip     =   trim($arr[0]);
//        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
//            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
//        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
//            $ip     =   $_SERVER['REMOTE_ADDR'];
//        }
//    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
//        $ip     =   $_SERVER['REMOTE_ADDR'];
//    }
//    // IP地址合法验证
//    $long = sprintf("%u",ip2long($ip));
//    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
//    return $ip[$type];
//}

function get_client_ip($type = 0) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
        $ip=$_SERVER['HTTP_X_REAL_IP'];
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
    }else{
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}


/**
+----------------------------------------------------------
 * IP定位
+----------------------------------------------------------
 * @param string $ip
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function get_ip_location($ip) {
    $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . urlencode($ip); //淘宝的开放接口
    $json = file_get_contents($url);
    $data = json_decode($json, JSON_UNESCAPED_UNICODE);
    if ($data['code'] === 0) { //请求成功
        $val = $data['data'];
        $address = $val['country'] . $val['area'] . $val['region'] . $val['city'] . $val['county'] . $val['isp'];
    } else {
        $address = L('PUBLIC_ADDR');
    }
    return $address;
}

