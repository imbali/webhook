<?php
namespace Common\Util\Log;

class Db
{

    protected $config = array(
        'log_time_format'   => 'U',
        'must_login'        => true,
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
        $user = $this->config['must_login'] ? session('user') : null;
        if (!$user) return;
        $table = $destination ?: Common\Mongo\TableName::USER_LOG;
        $collection = O()->table($table);
        /* 创建主键索引 */
        $collection->createIndexes([
            [ 'key' => ['id'=>1], 'unique' => true ],
            [ 'key' => ['module'=>1, 'controller'=>1, 'action'=>1] ],
            [ 'key' => ['user_id'=>1] ],
        ]);
        $id = O('Globals')->getIncId($table.'_id');
        $data = [
            'id'            => $id,
            'module'        => MODULE_NAME,
            'controller'    => CONTROLLER_NAME,
            'action'        => ACTION_NAME,
            'user_id'       => intval($user['id']),
            'username'      => $user['username'],
            'message'       => $log,
            'time'          => date($this->config['log_time_format']),
        ];
        $collection->insertOne($data);
    }
}
