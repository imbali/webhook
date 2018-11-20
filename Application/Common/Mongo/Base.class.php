<?php
namespace Common\Mongo;

use MongoDB\Client;

/**
 * Mongodb基类
 */

class Base{

    /**
     * The MongoDB database handler.
     *
     * @var \MongoDB\Database
     */
    protected $db;

    /**
     * The MongoDB connection handler.
     *
     * @var \MongoDB\Client
     */
    protected $connection;

    // 默认的mongodb库配置
    protected $config = 'MONGODB_CONFIG';


    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct(array $config = [])
    {
        if (empty($config)) {
            $config = C($this->config);
        }

        // Build the connection string
        $dsn = $this->getDsn($config);

        // You can pass options directly to the MongoDB constructor
        $options = isset($config['options']) ? $config['options'] : [];

        // Create the connection
        $this->connection = $this->createConnection($dsn, $config, $options);

        // Select database
        if (isset($config['database']) && !empty($config['database'])) {
            $this->db = $this->connection->selectDatabase($config['database']);
        }
    }

    /**
     * Create a new MongoDB connection.
     *
     * @param  string  $dsn
     * @param  array   $config
     * @param  array   $options
     * @return \MongoDB\Client
     */
    protected function createConnection($dsn, array $config, array $options)
    {
        // By default driver options is an empty array.
        $driverOptions = [];

        if (isset($config['driver_options']) && is_array($config['driver_options'])) {
            $driverOptions = $config['driver_options'];
        }

        // Check if the credentials are not already set in the options
        if (!isset($options['username']) && !empty($config['username'])) {
            $options['username'] = $config['username'];
        }
        if (!isset($options['password']) && !empty($config['password'])) {
            $options['password'] = $config['password'];
        }

        return new Client($dsn, $options, $driverOptions);
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // Check if the user passed a complete dsn to the configuration.
        if (! empty($config['dsn'])) {
            return $config['dsn'];
        }

        // Treat host option as array of hosts
        $hosts = is_array($config['host']) ? $config['host'] : [$config['host']];

        foreach ($hosts as &$host) {
            // Check if we need to add a port to the host
            if (strpos($host, ':') === false && ! empty($config['port'])) {
                $host = $host . ':' . $config['port'];
            }
        }

        // Check if we want to authenticate against a specific database.
        $auth_database = isset($config['options']) && ! empty($config['options']['database']) ? $config['options']['database'] : null;

        return 'mongodb://' . implode(',', $hosts) . ($auth_database ? '/' . $auth_database : '');
    }

    /**
     * 切换当前操作的数据库
     *
     * @param  string  $database
     * @return $this
     */
    public function db($database)
    {
        $this->db = $this->connection->selectDatabase($database);
        return $this;
    }

    public function database($database)
    {
        $this->db = $this->connection->selectDatabase($database);
        return $this;
    }

    /**
     * Get a MongoDB collection.
     *
     * @param  string   $name|$table|$collection
     * @return Collection
     */
    public function getCollection($name)
    {
        return $this->db->selectCollection($name);
    }

    public function collection($collection)
    {
        return $this->getCollection($collection);
    }

    public function table($table)
    {
        return $this->getCollection($table);
    }


    /**
     * Get the MongoDB database object.
     *
     * @return \MongoDB\Database
     */
    public function getMongoDB()
    {
        return $this->db;
    }

    /**
     * return MongoDB object.
     *
     * @return \MongoDB\Client
     */
    public function getMongoClient()
    {
        return $this->connection;
    }

    /**
     * Disconnect from the underlying MongoDB connection.
     */
    public function disconnect()
    {
        unset($this->connection);
    }

    /**
     * Dynamically pass methods to the connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->db, $method], $parameters);
    }

}