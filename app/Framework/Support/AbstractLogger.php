<?php

namespace Framework\Support;

abstract class AbstractLogger
{
    abstract public function log($message, $format = array());

    abstract public function warning($message, $format = array());

    abstract public function error($message, $format = array());

    public function __construct()
    {

    }
}
