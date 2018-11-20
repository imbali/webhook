<?php
namespace Center\Sdk;

abstract class PayApp{

    protected $keys = []; // app_id和app_key等信息

    public function __construct($keys)
    {
        $this->keys = keys;
    }

    abstract public function check();

}
