<?php
namespace Center\Sdk;

abstract class LoginApp{

    protected $keys = []; // app_id和app_key等信息

    public function __construct($keys) {
        $this->keys = $keys;
        $this->init();
    }

    abstract protected function init();

    abstract public function check(array $args);

}
