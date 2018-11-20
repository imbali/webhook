<?php
namespace Common\Util;

/**
 * php-ansible 返回结果处理类
 */
class AnsibleResult
{

    protected $result = '';


    public function __construct($result)
    {
        $this->result = $result;
        $this->parse();
    }

    protected function parse()
    {
        $arr = explode(PHP_EOL, $this->result);
        foreach ($arr as $line) {
            // if (trim($line) == '') continue;
            echo $line.'<br/>';
        }
    }

}
