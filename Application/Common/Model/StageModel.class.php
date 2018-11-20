<?php
namespace Common\Model;
use Common\Model\BaseModel;
/**
 * 权限规则model
 */
class StageModel extends BaseModel{

    // 自动验证
    protected $_validate = [
        ['id',         'number',   '后台ID必须是整数'],
        ['title',      'require',  '后台名称必填'],
        ['address',    'require',  '后台地址必填'],
    ];

    public function addData($data){
        // 去除键值首尾的空格
        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        if ($this->create($data)) {
            return [$this->add($data), '添加成功'];
        }
        else {
            return [false, $this->getError()];
        }
    }

    public function deleteData($map){
        if (empty($map)) {
            die('where为空的危险操作');
        }
        $result = $this->where($map)->setField('is_del', 1);
        return $result;
    }

}
