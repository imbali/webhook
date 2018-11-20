<?php
namespace Center\Mongo;
/**
 * Desc: php操作mongodb的封装类
 * Author zhifeng
 * Date: 2015/04/15
 */

class MongoD {

    protected $_conn  = null;//Mongodb连接
    protected $_mongo = null;//当前选择的数据库对象
    protected $_error = null;//当前的错误信息

    /**
     * 构造函数
     * @param array $conf 服务器配置,默认为:
     * array(
     * 'host'=>'localhost', // 主机名或IP地址
     * 'port'=>27017, // 端口
     * 'db'=>'u3d', // 数据库
     * )
     */
    public function __construct(array $conf) {
        $host = isset($conf['host']) ? $conf['host'] : 'localhost';
        $port = isset($conf['port']) ? $conf['port'] : 27017;
        $username = $conf['username'];
        $password = $conf['password'];
        $db = $conf['db'];
        $connect_string = sprintf("mongodb://%s:%s@%s:%s/%s", $username, $password, $host, $port, $db);
//        $connect_string = sprintf("mongodb://%s:%s", $host, $port);
        try {
            $mongo = class_exists('MongoClient') ? new \MongoClient($connect_string) : new \Mongo($connect_string);
            $this->_conn = $mongo;
            //选定数据库
            $this->_mongo = $this->_conn->selectDB($conf['db']);;
        } catch (\MongoConnectionException $e) {
            $this->_error = $e->getMessage();
        }
    }

    /**
     * 创建索引：如索引已存在，则返回。
     * @param string $colName 表名
     * @param array $keys 索引-array("id"=>1)-在id字段建立升序索引
     * @param array $options 其它条件-是否唯一索引等
     * @return boolean
     */
    public function ensureIndex($colName, $keys, $options=array()) {
        $options['unique'] = isset($options['unique']) ? $options['unique'] : true;
        try {
            $this->_mongo->$colName->ensureIndex($keys, $options);
            return true;
        } catch (MongoCursorException $e) {
            $this->_error = $e->getMessage();
            return false;
        }
    }

    /**
     * 获取自增ID
     * @param string $query 获取自增ID时的查询条件
     * @param string $field 自增ID的key
     * @return int 自增ID
     */
    public function genIncrId($query, $field, $collection='ids') {
        $command['findAndModify'] = $collection; //集合名称
        $command['query'] = array('name'=>$query);
        $command['update'] = array('$inc'=>array($field=>1));
        $command['upsert'] = true;//若是第一次创建，upsert一定要写上，否则，不会出现自增id
        $command['new'] = true;
        $data = $this->_mongo->command($command);
        return isset($data['ok'])&&$data['value'][$field] ? $data['value'][$field] : false;
    }

    /**
     * 向集合(表)中插入新文档
     * 1:类似mysql中的: insert into $colName set id=1,name='name1';
     * @param string $colName 集合名
     * @param array $data 数据,如: array('id'=>1,'name'=>'name1')
     * @param array $options 插入的选项
     *   safe 是否安全操作 false:不等待服务器的响应直接返回 true:等待服务器的响应(数据非常重要时推荐)
     *   fsync 操作后是否立即更新到碰盘,默认情况下由服务器决定
     * @return boolean
     */
    public function insert($colName, $data, $options=array()) {
        $options['safe']  = isset($options['safe'])  ? $options['safe']  : false;
        $options['fsync'] = isset($options['fsync']) ? $options['fsync'] : false;
        try {
            return $this->_mongo->$colName->insert($data, $options);
        } catch (MongoCursorException $e) {
            $this->_error = $e->getMessage();
            return false;
        }
    }

    /**
     * 统计文档记录数
     * @param string $colName 集合名
     * @param array $query 查询条件
     * @param int $limit 指定返回记录的上限
     * @param int $skip 指定在开始统计前，需要跳过的结果数目
     * @return 表的记录数
     */
    public function count($colName, $query=array(), $limit=0, $skip=0) {
        return $this->_mongo->$colName->count($query, $limit, $skip);
    }

    /**
     * 更新集合文档记录
     * 1：类似mysql中的: update $colName set name='mongo' where id=10;
     * @param string $colName 集合名
     * @param array $query 查询条件,如果为空数组则更新所有记录
     * @param array $newData 要更新的文档记录
     * @param array $option 更新操作的选项
     *   upsert 如果查询条件不存在时，是否以查询条件和要更新的字段一起新建一个集合
     *   multiple 是否更新找到的所有记录
     *   safe 是否安全删除 false:不等待服务器的响应直接返回 true:等待服务器的响应(数据非常重要时推荐)
     *   fsync 操作后是否立即更新到碰盘,默认情况下由服务器决定
     * @return boolean
     */
    public function update($colName, $query, $newData, $options=array()) {
        //设定默认参数值
        $options['upsert']   = isset($options['upsert'])   ? $options['upsert']   : false;
        $options['multiple'] = isset($options['multiple']) ? $options['multiple'] : false;
        $options['safe']     = isset($options['safe'])     ? $options['safe']     : false;
        $options['fsync']    = isset($options['fsync'])    ? $options['fsync']    : false;
        try {
            return $this->_mongo->$colName->update($query, $newData, $options);
        } catch (MongoCursorException $e) {
            $this->_error = $e->getMessage();
            return false;
        }
    }

    /**
     * 删除集合中的文档记录
     * 1：类似mysql中的: delete from $colName where id=1;
     * @param string $colName 集合名
     * @param array $query 查询条件,如果为空数组的话，则会删除所有记录．
     * @param array $options 删除选项
     *   justOne 是否删除所以条例查询的记录,默认为 false,当为 true时，类似效果 delete from tab where id=1 limit 1;
     *   safe 是否安全操作 false:不等待服务器的响应直接返回 true:等待服务器的响应(数据非常重要时推荐)
     *   fsync 操作后是否立即更新到碰盘,默认情况下由服务器决定
     * @return boolean
     */
    public function remove($colName, $query, $options=array()) {
        //设定默认参数值
        $options['justOne'] = isset($options['justOne']) ? $options['justOne'] : false;
        $options['safe']    = isset($options['safe'])    ? $options['safe']    : false;
        $options['fsync']   = isset($options['fsync'])   ? $options['fsync']   : false;
        try {
            return $this->_mongo->$colName->remove($query, $options);
        } catch (MongoCursorException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 查询文档集,返回二维数组
     * @param string $colName 集合名
     * @param array $query 查询条件
     * @param array $fields 结果集返回的字段, array():表示返回所有字段 array('id','name'):表示只返回字段 "id,name"
     * @param array $sort 排序字段, array('id'=>1):表示按id字段升序 array('id'=>-1):表示按id字段降序 array('id'=>1, 'age'=>-1):表示按id升序后再按age降序
     * @param int $limit 取多少条记录
     * @param int $skip 跳过多少条(从多少条开始)
     * @return array
     */
    public function find($colName, $query, $fields=array(), $sort=array(), $limit=0, $skip=0) {
        $fields['_id'] = isset($fields['_id']) ? $fields['_id'] : false; //默认不返回'_id'
        //得到集合游标
        $cursor = $this->_mongo->$colName->find($query, $fields);
        // 排序
        if ($sort) $cursor->sort($sort);
        // 跳过记录数
        if ($skip>0) $cursor->skip($skip);
        // 取多少行记录
        if ($limit>0) $cursor->limit($limit);
        //结果集
        $result = array();
        try {
            while ($cursor->hasNext()) {
                $result[] = $cursor->getNext();
            }
        } catch (MongoConnectionException $e) {
            $this->_error = $e->getMessage();
            return false;
        } catch (MongoCursorTimeoutException $e) {
            $this->_error = $e->getMessage();
            return false;
        }
        return $result;
    }

    /**
     * 返回集合中的一条记录(一维数组)
     * @param string $colName 集合名
     * @param array $query 查询条件
     * @param array $fields 结果集返回的字段, array():表示返回所有字段 array('id','name'):表示只返回字段 "id,name"
     * @return array or false
     */
    public function findOne($colName, $query, $fields=array()) {
        $fields['_id'] = isset($fields['_id']) ? $fields['_id'] : false; //默认不返回'_id'
        return $this->_mongo->$colName->findOne($query, $fields);
    }

    /**
     * 返回符合条件的文档中字段的值
     * @param string $colName 集合名
     * @param array $query 查询条件
     * @param string $fields 要取其值的字段,默认为 "_id" 字段,类似mysql中的自增主键
     * @return mixed
     */
    public function fetchOne($colName,$query=array(), $field='_id') {
        $fields = $field==='_id' ? array('_id'=>true) : array('_id'=>false, "$field"=>true);
        $ret = $this->findOne($colName, $query, $fields);
        return isset($ret[$field]) ? $ret[$field] : false;
    }

    /**
     *  在数据库服务器上运行JavaScript
     * @param string $jsCode js代码，例如一个function
     * @param array $args 传递给jsCode的参数
     * @return mixed
     */
    public function execute($jsCode, $args=array()) {
        $response = $this->_mongo->execute($jsCode, $args);
        if ( isset($response['ok']) && intval($response['ok'])===1 ) {
            return $response['retval'];
        } else {
            $this->_error = $response['retval'];
            return false;
        }
    }

    /**
     * 获取当前错误信息
     * 返回值：当前错误信息
     */
    public function getError() {
        return $this->_error;
    }

    /**
     * 关闭连接
     */
    public function close() {
        if (!is_null($this->_conn)) $this->_conn->close();
    }

    /**
     * 析构方法
     */
    public function __destruct() {
        $this->close();
    }

}
