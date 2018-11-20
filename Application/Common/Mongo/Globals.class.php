<?php
namespace Common\Mongo;

use MongoDB\Operation;
/**
 * 保存全局自增ID
 */

class Globals extends Base{

    protected $collection = 'globals';

    public function getIncId(string $field)
    {
        $query  = ['key'    => (string)$field];
        $update = ['$inc'   => ['value' => 1]];
        $options = [
            'upsert'        => true,
            'projection'    => ['_id' => 0, 'value' => 1],
            'returnDocument' => Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
        ];
        $collection = $this->table($this->collection);
        /* 创建唯一索引 */
        $collection->createIndex(['key'=>1], ['unique'=>1]);
        $result = $collection->findOneAndUpdate($query, $update, $options)->getArrayCopy();
        return $result['value'] ?: false;
    }

}