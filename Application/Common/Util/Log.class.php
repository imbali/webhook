<?php
namespace Common\Util;

/**
 * 日志处理类
 */
class Log
{

    const TYPE_REQ      = 'File';   //中心服请求记录
    const TYPE_DB       = 'Db';     //后台用户操作记录

    // 日志信息
    protected static $log = array();

    // 日志存储
    protected static $storage = null;

    // 初始化驱动
    protected static function _storage($config)
    {
        if (!self::$storage) {
            if (is_array($config)) {
                $type  = isset($config['type']) ? $config['type'] : 'File';
                unset($config['type']);
            } else {
                $type = (string)$config;
                $config = [];
            }
            $type          = $type ?: 'File';
            $class         = strpos($type, '\\') ? $type : 'Common\\Util\\Log\\' . ucwords(strtolower($type));
            self::$storage = new $class($config);
        }
        return self::$storage;
    }

    // 日志初始化
    public static function init($config = array())
    {
        self::_storage($config);
    }

    /**
     * 记录日志 并且会过滤未经设置的级别
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param boolean $record  是否强制记录
     * @return void
     */
    public static function record($message)
    {
        self::$log[] = " {$message}\r\n";
    }

    /**
     * 日志保存
     * @static
     * @access public
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     */
    public static function save($type = '', $destination = '')
    {
        if (empty(self::$log)) {
            return;
        }
        $message = implode('', self::$log);
        self::_storage($type)->write($message, $destination);
        // 保存后清空日志缓存
        self::$log = array();
    }

    /**
     * 日志直接写入
     * @static
     * @access public
     * @param string $message 日志信息
     * @param string $level  日志级别
     * @param integer $type 日志记录方式
     * @param string $destination  写入目标
     * @return void
     */
    public static function write($message, $type = '', $destination = '')
    {
        self::_storage($type)->write(" {$message}", $destination);
    }


}
