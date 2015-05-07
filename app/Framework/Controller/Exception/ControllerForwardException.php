<?php
namespace Framework\Controller\Exception;

use Framework\Http\Request;

use Framework\Routing\Routes;

class ControllerForwardException extends \Exception
{
	private
			$route,
			$params,
			$forwardTo = '';

	/**
	 * @param string $forwardTo
	 * @example 'Controller@Method'
	 * @param mixed $params
	 */
	public function __construct($forwardTo, $params = null, $noBeforeExecute = false)
	{
		$this->params = $params;
		if (contains($forwardTo, '@')) {
			$this->route = Routes::getRouteByCallback($forwardTo);
			$this->forwardTo = $forwardTo;
		} else {
			$this->route = Routes::getRouteByName($forwardTo);
			$this->forwardTo = $this->route->callback;
		}

		if ($this->route) {
			Request::getInstance()->setParam('currRoute', $this->route);
		}
		if ($noBeforeExecute === true) {
			Request::getInstance()->setParam('noBeforeExecute', true);
		}
	}

	public function getTo()
	{
		return $this->forwardTo;
	}

	public function getParams()
	{
		return $this->params;
	}
}
