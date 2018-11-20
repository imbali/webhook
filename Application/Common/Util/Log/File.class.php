<?php
namespace Common\Util\Log;

class File
{

    protected $config = array(
        'log_time_format' => ' c ',
        'log_file_size'   => 2097152,
        'log_path'        => RUNTIME_PATH . 'AppLogs/' . MODULE_NAME . '/' . CONTROLLER_NAME . '/',
    );

    // 实例化并传入参数
    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 日志写入接口
     * @access public
     * @param string $log 日志信息
     * @param string $destination  写入目标
     * @return void
     */
    public function write($log, $destination = '')
    {
        $now = date($this->config['log_time_format']);
        if (empty($destination)) {
            $destination = $this->config['log_path'] . date('Y_m_d') . '.log';
        }
        // 自动创建日志目录
        $log_dir = dirname($destination);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && floor($this->config['log_file_size']) <= filesize($destination)) {
            rename($destination, dirname($destination) . '/' . basename($destination, '.log') . '-' . date('H_i_s') . '.log');
        }
        $message = "[{$now}] " . get_client_ip(0, true) . ' ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . ' ' . (file_get_contents("php://input") ?: '');
        error_log($message."\r\n{$log}\r\n\r\n", 3, $destination);
    }
}
