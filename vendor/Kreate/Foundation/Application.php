<?php

namespace Kreate\Foundation;

use \Kreate\Support\Config;
use \Kreate\Http\Request;
use \Kreate\Http\Response;
use \Kreate\Routing\Router;

class Application
{
    protected
            $config,
            $request,
            $response,
            $before = array(),
            $after = array();


    public function __construct(Config $config, Request $request)
    {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Make the thing run
     */
    public function run()
    {
        $this->fireBefore();

        $result = chain(new Router($this->request))->dispatch();
        $this->prepareResponse($result);

        $this->fireAfter();
    }

    public function shutdown()
    {
        $this->response->send();
    }

    /**
     * Executes application before events
     */
    public function fireBefore()
    {
        foreach ($this->before as $function) {
            $function->__invoke();
        }
    }

    /**
     * Executes application after events
     */
    public function fireAfter()
    {
        foreach ($this->after as $function) {
            $function->__invoke($this->response);
        }
    }

    /**
    * Determine if we are running in the console.
    *
    * @return bool
    */
    public function isInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Checks if the request path ends in a single trailing slash and
     * redirect it using a 301 response code if it does.
     */
    public function redirectIfTrailingSlash()
    {
        if ($this->isInConsole()) return;

        $path = $this->request->uri;

        if ($path != '/' && endsWith($path, '/') && ! endsWith($path, '//')) {
            $path = rtrim($path, '/');
            Response::getInstance()->prepareRedirect($path)->send();
        }
    }

    /**
     *
     *
     * @param string $content
     */
    private function prepareResponse($content)
    {
        $response = Response::getInstance();
        $response->setContent($content);
        $this->response = $response;
    }
}
