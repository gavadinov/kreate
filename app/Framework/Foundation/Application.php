<?php

namespace Framework\Foundation;

use \Framework\Http\Request;
use \Framework\Http\Response;
use \Framework\Routing\Router;
use \Framework\Routing\Exception\RoutingException;
use \Framework\Routing\Exception\RouteNotFoundException;
use Controller\ErrorController;
use Framework\Routing\Exception\InvalidCallbackException;
use Framework\Controller\AbstractController;
use Framework\Factory\ControllerFactory;
use Framework\Support\Profiler;
use Framework\Controller\Exception\ControllerForwardException;
use Framework\Foundation\Exception\AppException;
use Framework\Config\AppConfig;
use Controller\AjaxErrorController;
use Framework\Persistence\UnitOfWork;
use Framework\Event\EventDispatcher;

class Application
{
	protected
			$request,
			$content,
			$response,
			$controller,
			$controllerMethod,
			$before = array(),
			$after = array(),
			$exceptionHandlers = array();

	private static $instance;

	private function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * @param AbstractController $controller
	 * @param string $method
	 * @param string $params
	 * @throws InvalidCallbackException
	 * @return string
	 */
	private function execController(AbstractController $controller, $method, $params)
	{
		if (! method_exists($controller, $method)) {
			$message = 'The controller class ' . $controller->getFullName() . ' does not have a method ' . $method;
			throw new InvalidCallbackException($message);
		}

		Profiler::frameworkCall($controller->getFullName(), $method);

		Request::getInstance()->setParam('controller', $controller);
		Request::getInstance()->setParam('method', $method);

		try {
			if (! Request::getInstance()->getParam('noBeforeExecute', false)) {
				$controller->before($method);
			} else {
				Request::getInstance()->setParam('noBeforeExecute', false);
			}

			$eventParams = array(
				'controller' => $controller,
				'controllerName' => $controller->getName(),
				'method' => $method,
				'params' => $params,
			);
			EventDispatcher::fire('framework.call', $eventParams);

			$result = $controller->$method($params);
			$result = $controller->after($result);
		} catch (\Exception $e) {
			$controller->handleException($e);
		}

		return $result;
	}

	/**
	 * @param RoutingException $e
	 * @throws \Framework\Routing\Exception\RouteNotFoundException
	 */
	private function handleRoutingException(RoutingException $e)
	{
		if ($e instanceof RouteNotFoundException) {
			if (Request::getInstance()->isAjax()) {
				$errorController = new AjaxErrorController();
			} else {
				$errorController = new ErrorController();
			}

			if (! AppConfig::get('render404', false) && \Framework\Http\Request::getInstance()->isAjax()) {
				Response::getInstance()->setStatus(404, 'ROUTE NOT FOUND')->send();
			} else {
				$this->content = $errorController->error404();
			}
		} else {
			throw $e;
		}
	}

	/**
	 * Forward the request to another controller
	 *
	 * @param ControllerForwardException $forwardException
	 * @throws \Exception
	 */
	private function handleForwardException(ControllerForwardException $forwardException)
	{
		$request = Request::getInstance();
		$forwardChain = $request->getParam('forwardChain', array());
		if (count($forwardChain) > 5) {
			throw new AppException('Too many forwards');
		}

		$forwardChain[] = array(
			'controller' => $this->controller,
			'method' => $request->getParam('method'),
		);
		$request->setParam('forwardChain', $forwardChain);
		$request->setParam('isForward', true);

		list($controller, $method) = Router::parseCallbackForController($forwardException->getTo());

		try {
			$this->run($controller, $method, $forwardException->getParams());
		} catch (\Exception $e) {
			$this->handleException($e);
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

	/**
	 * Executes application before events
	 *
	 */
	private function fireBefore()
	{
		foreach ($this->before as $function) {
			$function->__invoke($this->request);
		}
	}

	/**
	 * Executes application after events
	 *
	 */
	private function fireAfter()
	{
		foreach ($this->after as $function) {
			$function->__invoke($this->request, $this->response);
		}
	}

	/**
	 * Singleton
	 *
	 * @return Application
	 */
	public static function getInstance($request = null)
	{
		if (! isset(self::$instance)) {
			self::$instance = new self($request);
		}

		return self::$instance;
	}

	public function setContent($content)
	{
		$this->content = $content;
	}

	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Fire the application before execute and resolve route
	 *
	 *
	 */
	public function setup()
	{
		$this->fireBefore();

		return chain(new Router($this->request))->resolve();
	}

	/**
	 * Make the thing run
	 *
	 */
	public function run($controllerName, $method, $params = null)
	{
		$this->controller = ControllerFactory::create($controllerName);
		$this->content = $this->execController($this->controller, $method, $params);
	}

	/**
	 * Fire the application after execute and send the response back to the user
	 *
	 */
	public function shutdown()
	{
		$this->fireAfter();
		$this->prepareResponse();
		$this->response->send();
	}

	/**
	 * @param \Closure $handler
	 */
	public function registerExceptionHandler(\Closure $handler)
	{
		$this->exceptionHandlers[] = $handler;
	}

	/**
	 * Passes the exception to the chain of handlers
	 *
	 *
	 * @param \Exception $e
	 * @throws \Exception
	 */
	public function handleException(\Exception $e)
	{
		if ($e instanceof RoutingException) {
			$this->handleRoutingException($e);
			return;
		} else if ($e instanceof ControllerForwardException) {
			$this->handleForwardException($e);
			return;
		}

		$success = false;
		foreach ($this->exceptionHandlers as $handler) {
			$result = $handler->__invoke($e);
			if ($result !== false && ! is_null($result)) {
				$this->content = $this->controller->after($result);
				return;
			}
		}

		$this->renderKernelPanicAlert($e);
	}

	/**
	 * Register before execute handlers
	 *
	 * @param \Closure $before
	 */
	public function registerBefore(\Closure $before)
	{
		$this->before[] = $before;
	}

	/**
	 * Register after execute handlers
	 *
	 * @param \Closure $after
	 */
	public function registerAfter(\Closure $after)
	{
		$this->after[] = $after;
	}

	/**
	 * Returns general error string
	 *
	 * @param Exception $exception
	 */
	public function renderKernelPanicAlert(\Exception $e)
	{
		UnitOfWork::clear();
		if (Request::getInstance()->isAjax()) {
			$controllerName = 'AjaxError';
		} else {
			$controllerName = 'Error';
		}
		try {
			$this->run($controllerName, 'kernelPanic', $e);
		} catch (\Exception $ex) {
			$this->handleException($ex);
		}
		$this->shutdown();
	}

	/**
	 * Checks if the request path ends in a single trailing slash and
	 * redirect it using a 301 response code if it does.
	 *
	 */
	public function redirectIfTrailingSlash()
	{
		if (Request::getInstance()->isInConsole) return;

		$uri = $this->request->uri;

		if ($uri != '/' && endsWith($uri, '/') && ! endsWith($uri, '//')) {
			$path = $this->request->fullUri;
			$path = rtrim($path, '/');
			Response::getInstance()->redirect($path);
		}
	}
}
