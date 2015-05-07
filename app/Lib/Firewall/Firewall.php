<?php

namespace Lib\Firewall;

use Framework\Controller\AbstractController;
use Lib\Firewall\Conditions\Chain;
use Framework\Http\Request;
use Lib\MessageHandler;

class Firewall
{
	private $globalConditions = array();

	/**
	 *
	 * @var \Lib\Firewall\Conditions\Chain
	 */
	private $conditionsChain;

	private static $instance;

	/**
	 * Run the chain of responsibility with condition processors
	 *
	* @param unknown $condition
	 */
	private function resolveCondition($condition, array $data = array())
	{
		return $this->conditionsChain->resolve($condition, $data);
	}

	/**
	 * Evaluate white and black lists and return if the action is accessible
	 *
	* @param array $actions
	 * @param AbstractController $controller
	 * @param string $method
	 * @return boolean
	 */
	private function resolveLists(array $lists, AbstractController $controller, $method)
	{
		$controllerName = $controller->getName();
		$currAction = $controllerName . '@' . $method;

		foreach ($lists as $listName => $action) {
			if ($listName == 'whiteList') {
				$success = true;
			} else {
				$success = false;
			}

			if (in_array('*', $action)) {
				return $success;
			}

			$allowed = ! $success;
			foreach ($action as $allowedAction) {
				$input = array(
					'post' => array(),
					'get' => array(),
				);
				if (is_array($allowedAction)) {
					$post = (empty($allowedAction['post']) ? array() : $allowedAction['post']);
					$get= (empty($allowedAction['get']) ? array() : $allowedAction['get']);
					$input = array(
						'post' => $post,
						'get' => $get,
					);
					$allowedAction = $allowedAction['action'];
				}

				$pattern = '/' . $allowedAction . '/';
				if (preg_match($pattern, $currAction)) {
					$allowed = $success;

					foreach ($input as $method => $inputValues) {
						foreach ($inputValues as $key => $value) {
							$inputValue = \Framework\Support\Input::$method($key);
							if ($inputValue !== $value) {
								$allowed = ! $success;
							}
						}
					}
				}
			}

			if (! $allowed) {
				return false;
			}
		}

		return true;
	}

	private function initGlobalConditions()
	{

	}

	private function __construct()
	{
		$this->conditionsChain = Chain::getInstance();
		$this->initGlobalConditions();
	}

	/**
	* @return \Lib\Firewall\Firewall
	 */
	public static function getInstance()
	{
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function check(AbstractController $controller, $method)
	{
		if (! empty($controller->conditions)) {
			$conditions = array_merge($this->globalConditions, $controller->conditions);
		} else {
			$conditions = $this->globalConditions;
		}

		$data = array(
			'controller' => $controller,
			'method' => $method,
			'action' => $controller->getName() . '@' . $method,
		);

		foreach ($conditions as $condition => $lists) {
			if ($this->resolveCondition($condition, $data) && ! $this->resolveLists($lists, $controller, $method)) {
				$msg = Request::getInstance()->getParam('accessMessage', 'Forbidden');
				MessageHandler::setAndThrow($msg);
			}
		}
	}

	/**
	 * Resolve which tabs should be visible in the current module
	 *
	* @param AbstractController $controller
	 * @param array $tabs
	 * @return array
	 */
	public function resolveTabs(AbstractController $controller, array $tabs = array())
	{
		if (empty($controller->tabsList)) {
			return $tabs;
		}

		foreach ($controller->tabsList as $condition => $lists) {
			if ($this->resolveCondition($condition)) {
				if (! empty($lists['whiteList'])) {
					$tabs = array_intersect_key($tabs, array_flip($lists['whiteList']));
				}
				foreach ($lists['blackList'] as $remove) {
					$tabs = array_diff_key($tabs, array_flip($lists['blackList']));
				}
			}
		}

		return $tabs;
	}
}
