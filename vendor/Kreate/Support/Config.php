<?php

namespace Kreate\Support;

class Config implements \ArrayAccess
{
    protected $config = array();

    public function __construct($file)
    {
        $this->config = require kreate_config_dir . $file;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function offsetExists($offset)
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->config[$offset])) {
            return $this->config[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception('Config is readonly');
    }

    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }

}
