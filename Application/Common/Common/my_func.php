<?php

/**
* @param string $string 原文或者密文
* @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
* @param string $key 密钥
* @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
* @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
*/
function com_encrypt1($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;

    // 密匙
    $key = md5($key ? $key : AUTH_KEY);

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        // 验证数据有效性，请看未加密明文的格式
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}

/**
 * @param string $string 原文或者密文
 * @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
 * @param string $key 密钥
 * @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
 * @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
 */
function com_encrypt($string, $operation = 'DECODE', $key = '', $expiry = 3600) {
    $ckey_length = 4;
    // 随机密钥长度 取值 0-32;
    // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
    // 当此值为 0 时，则不产生随机密钥

    //$key = md5($key ? $key : EABAX::getAppInf('KEY'));
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE')
    {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16))
        {
            return substr($result, 26);
        }
        else
        {
            return '';
        }
    }
    else
    {
        $ret_str = $keyc.str_replace('=', '', base64_encode($result));
        return $ret_str;
    }
}

/**
* @param string $string 需要加密解密的字符串
* @param string $operation 判断是加密还是解密，E表示加密，D表示解密
* @param string $key 密钥
* @return string 处理后的 原文或者密文
*/
function my_encrypt($string,$operation,$key='')
{
    $key=md5($key);
    $key_length=strlen($key);
      $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
    $string_length=strlen($string);
    $rndkey=$box=array();
    $result='';
    for($i=0;$i<=255;$i++){
           $rndkey[$i]=ord($key[$i%$key_length]);
        $box[$i]=$i;
    }
    for($j=$i=0;$i<256;$i++){
        $j=($j+$box[$i]+$rndkey[$i])%256;
        $tmp=$box[$i];
        $box[$i]=$box[$j];
        $box[$j]=$tmp;
    }
    for($a=$j=$i=0;$i<$string_length;$i++){
        $a=($a+1)%256;
        $j=($j+$box[$a])%256;
        $tmp=$box[$a];
        $box[$a]=$box[$j];
        $box[$j]=$tmp;
        $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
    }
    if($operation=='D'){
        if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8)){
            return substr($result,8);
        }else{
            return'';
        }
    }else{
        return str_replace('=','',base64_encode($result));
    }
}

/**
* Make an HTTP request.
*
* @param string $url        A request url like "https://example.com/xx.json?example1=&example2=".
* @param string $method     Request method is "GET" or "POST".
* @param mixed  $params     A query array or string.
* @param string $protocol   协议 默认http
* @param int    $enc_type   如果 $params是字符串并且enc_type=false,则不进行任何urlencode
*                           如果 enc_type 是 PHP_QUERY_RFC1738，则编码将会以 » RFC 1738 标准和 application/x-www-form-urlencoded 媒体类型进行编码，空格会被编码成加号（+）。
*                           如果 enc_type 是 PHP_QUERY_RFC3986，将根据 » RFC 3986 编码，空格会被百分号编码（%20）。
* @param int    $timeout
*
* @return API results.
*/
function do_curl($url, $method, $params='', $protocol='http', $enc_type=PHP_QUERY_RFC1738, $timeout=10)
{
    $ch = curl_init();
    if (is_array($params)) {
        $query_string = http_build_query($params,'','',$enc_type);
    }
    else {
        if ($enc_type===false) {
            $query_string = $params;
        }else{
            $query_string = $enc_type===PHP_QUERY_RFC3986 ? rawurlencode($params) : urlencode($params);
        }
    }
    if (strtoupper($method)==='POST') {
        curl_setopt($ch, CURLOPT_URL, $url); //请求地址
        curl_setopt($ch, CURLOPT_POST, true); //发送POST请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string); //通过POST发送的数据
    }
    else {
        curl_setopt(self::$ch, CURLOPT_URL, "$url?$query_string");   //请求地址
    }
    curl_setopt($ch, CURLOPT_HEADER, false); //关闭头文件信息输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //以文件流的形式返回获取的数据
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //允许curl执行的最大秒数
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); //发起连接前的最大等待时间
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //设置HTTP头字段的数组
    if ($protocol==='https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $result = curl_exec($ch);   //执行一个curl会话
    //$errno = curl_errno($ch);
    //if ($errno) $result = $errno;
    $errstr = curl_error($ch); //返回一个包含当前会话错误信息的字符串
    if ($errstr) $result = $errstr;
    curl_close($ch);
    return $result;
}

/**
 * 实例化Mongodb模型类
 * @param mixed (string)$name|(array)$config      名称|配置
 * @param string $config    配置
 * @return Object
 */
function O($name = '', $config = [])
{
    if (empty($name)) {
        return new Common\Mongo\Base($config);
    }

    if (is_array($name)) {
        return new Common\Mongo\Base($name);
    }

    static $_mongo = [];
    if (isset($_mongo[$name])) {
        return $_mongo[$name];
    }

    // 自动加载公共模块下面的模型
    if (!C('APP_USE_NAMESPACE')) {
        import('Common/Mongo/' . $name);
        $class = $name;
    } else {
        $class = '\\Common\\Mongo\\' . $name;
    }
    $model = class_exists($class) ? new $class($config) : new Common\Mongo\Base($config);
    $_mongo[$name] = $model;
    return $model;
}

/**
 * 重构数组索引
 * @param array  $arr      数组
 * @param string $field    字段名
 * @return array
 */
function reset_array_index(array $arr, $field='id')
{
    $tmp = [];
    foreach ($arr as $val) {
        if (!isset($val[$field])) {
            continue;
        }
        $tmp[$val[$field]] = $val;
    }
    return $tmp;
}



/**
 * mongodb数据入库前的数据类型转换(string to int)
 * @param string $table    表名
 * @param array  $arr      数据
 * @return array
 */
function format_table_fields($table, array $arr)
{
    switch ($table) {
        case 'server':
            $fields = ['id', 'serial_num', 'server_id', 'platform_id', 'entry', 'status', 'visible', 'type', 'machine_id', 'db_id', 'merged'];
            break;
        case 'sdk':
            $fields = ['id', 'platform_id', 'cdn_id', 'os'];
            break;
        case 'channel':
            $fields = ['id', 'sdk_id', 'status'];
            break;
        case 'solar':
//            $fields = ['id','type','machine_id'];
            $fields = ['id','sol_team','teamforgame','sol_fight','fightforgame','fightforteam','sol_chat','chatforgame','group_id','machine_id'];
            break;
        case 'solar_conf':
            $fields = ['id','sol_fight','sol_chat','sol_all'];
            break;
        case 'notice':
            $fields = ['id','platform_id'];
            break;
        case 'loginmsg':
            $fields = ['id','pop'];
            break;
        case 'maintain':
            $fields = ['id','platform_id','notice_id','action'];
            break;
        case 'log':
            $fields = ['id','platform_id','status'];
            break;
        case 'review':
            $fields = ['id','platform_id','channel_id','entry_id','cdn_id','allow_ctrl','status'];
            break;
        default:
            return $arr;
            break;
    }
    foreach ($fields as $field) {
        if (isset($arr[$field])) {
            $arr[$field] = intval($arr[$field]);
        }
    }
    return $arr;
}

/**
 * 取得随机字符串
 * @param int $length 随机字符长度
 * @param int $amount 随机字符的个数
 * @param int $mode   随机字符类型
 * @return string|array 取得的字符串,个数大于一时返回数组
 */
function get_random_code($length=8, $amount=1, $mode=0)
{
    // G('begin');
    $digits = '1234567890';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $symbol = '!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
    switch (strtoupper($mode)) {
        case '1':
            $str = $digits;
            break;
        case '2':
            $str = $lowercase;
            break;
        case '3':
            $str = $uppercase;
            break;
        case '4':
            $str = $uppercase . $lowercase;
            break;
        case '5':
            $str = $uppercase . $digits;
            break;
        case '6':
            $str = $lowercase . digits;
            break;
        case '7':
            $str = $uppercase . $lowercase . $digits;
            break;
        case '8':
            $str = $uppercase . $lowercase . $digits . $symbol;
            break;
        default:
            $str = $uppercase . $lowercase . $digits;
            break;
    }

    $strlen = strlen($str);
    if (round($strlen/$length) < 2) {
        for ($i=0; $i<ceil($length/$strlen); $i++) {
            $str .= $str;
        }
    }
    if ($amount > 1) {
        for ($i=0; $i<$amount; $i++) {
            $str = str_shuffle($str);
            $result[] = substr($str, 0, $length);
        }
    }
    else {
        $str = str_shuffle($str);
        $result = substr($str, 0, $length);
    }

    // G('end');
    // // 性能分析
    // echo '程序耗时：' . G('begin', 'end', 6) . 's';
    // echo '内存消耗：' . G('begin', 'end', 'm') . 'kb';

    /*返回结果*/
    return $result;
}

/**
 * 取得服务器机器信息
 * @param array $server_info 服务器信息
 * @param int $type 0为游戏服；1为跨服
 * @param int $mode   随机字符类型
 * @return string|array 取得的字符串,个数大于一时返回数组
 */
function formatServerIp($server_info, $type = 0)
{
    $machine_list = reset_array_index(O()->table('machine')->find()->toArray());
    foreach ($server_info as $key => $value) {
        $arrSer[$value['id']] = $value;
        $arrSer[$value['id']]['server_ip'] = $machine_list[$value['machine_id']]['public_ip'];
        $arrSer[$value['id']]['domain'] = $machine_list[$value['machine_id']]['domain'];
        if ($type == 1) $arrSer[$value['id']]['private_ip'] = $machine_list[$value['machine_id']]['private_ip'];
    }
    return $server_info;
}

