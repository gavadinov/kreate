<?php

namespace Kreate\Foundation;

use \Kreate\Support\Config;
use \Kreate\Http\Request;
use \Kreate\Http\Response;
use \Kreate\Routing\Router;
use \Kreate\Routing\Exception\RoutingException;
use \Kreate\Routing\Exception\RouteNotFoundException;
use Kreate\Routing\Exception\InvalidCallbackException;

class Application
{
    protected
            $config,
            $request,
            $content,
            $response,
            $before = array(),
            $after = array(),
            $exceptionHandlers = array();

    private static $instance;

    private function __construct(Config $config, Request $request)
    {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Singleton
     *
     * @return Application
     */
    public static function getInstance($config = null, $request = null)
    {
        if (! isset(self::$instance)) {
            self::$instance = new self($config, $request);
        }

        return self::$instance;
    }

    /**
     * Make the thing run
     */
    public function run()
    {
        $this->fireBefore();

        $this->content = chain(new Router($this->request))->dispatch();
    }

    /**
     * Fire the application after execute and send the response back to the user
     */
    public function shutdown()
    {
        $this->prepareResponse();
        $this->fireAfter();
        $this->response->send();
    }

    public function handleException(\Exception $e)
    {
        if ($e instanceof RoutingException) {
            $this->handleRoutingException($e);
            return;
        }
        $success = false;
        foreach ($this->exceptionHandlers as $handler) {
            $success = $handler->__invoke($e);
            if ($success) break;
        }

        if (! $success) {
            throw $e;
        }
    }

    public function handleRoutingException(RoutingException $e)
    {
        $errorController = new \ErrorsController();
        if ($e instanceof RouteNotFoundException) {
            $this->content = $errorController->error404();
        } else {
            $this->content = $e->getMessage();
        }
    }

    /**
     * Executes application before events
     */
    public function fireBefore()
    {
        foreach ($this->before as $function) {
            $function->__invoke($this->request);
        }
    }

    /**
     * Executes application after events
     */
    public function fireAfter()
    {
        foreach ($this->after as $function) {
            $function->__invoke($this->request, $this->response);
        }
    }

    /**
     * Register before execute handlers
     *
     * @param \Closure $before
     */
    public function before(\Closure $before)
    {
        $this->before[] = $before;
    }

    /**
     * Register after execute handlers
     *
     * @param \Closure $after
     */
    public function after(\Closure $after)
    {
        $this->after[] = $after;
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
     * Prepares the Response object
     *
     * @param string $content
     */
    private function prepareResponse($content = null)
    {
        $content = (isset($content) ? $content : $this->content);
        $response = Response::getInstance();
        $response->setContent($content);
        $this->response = $response;
    }
}
