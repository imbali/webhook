<?php
namespace Common\Api;

abstract class Api{

    /**
     * 构造方法
     */
    public function __construct(){
        $this->_init();
    }

    /**
     * 抽象方法
     */
    abstract protected function _init();

}
