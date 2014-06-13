<?php

namespace Kreate\Http;

class Request
{
    protected
            $uri,
            $fullUrl,
            $get,
            $post,
            $file,
            $cookie,
            $type;

    /*
     * Singleton instance of the request
     */
    private static $instance;


    private function __construct()
    {
        $this->uri = $_SERVER['REQUEST_URI'];

        $this->fullUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        $this->get = $_GET;
        $this->post = $_POST;
        $this->file = $_FILES;
        $this->cookie = $_COOKIE;

        $this->resolveType();
    }

    public function __get($name)
    {
        return (isset($this->$name) ? $this->$name : null);
    }

    /**
     * Singleton
     *
     * @return Request
     */
    public function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function isAjax()
    {
        return (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    public function resolveType()
    {
        $type = (isset($_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']);
        $this->type = strtolower($type);
    }
}
