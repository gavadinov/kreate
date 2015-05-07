<?php
namespace Framework\Controller;

use Framework\Controller\Exception\ControllerForwardException;
use Framework\Factory\ServiceFactory;

abstract class AbstractController
{
	public $env = '*';

	protected static
			$before = array(),
			$after = array();

	/**
	 * Executes application before events
	*/
	protected function fireBefore($method)
	{
		foreach (static::$before as $function) {
			$function->__invoke($this, $method);
		}
		foreach (self::$before as $function) {
			$function->__invoke($this, $method);
		}
	}

	/**
	 * Executes application after events
	*/
	protected function fireAfter(&$result)
	{
		foreach (static::$after as $function) {
			$function->__invoke($result, $this);
		}
		foreach (self::$after as $function) {
			$function->__invoke($result, $this);
		}
	}

	/**
	* @return \Framework\Service\AbstractService
	 */
	protected function createService()
	{
		return ServiceFactory::create($this->getName());
	}

	/**
	* @param string $to
	 * @example 'Controller@Method'
	 * @param mixed $params
	 */
	public function forward($to, $params = null, $noBeforeExecute = false)
	{
		throw new ControllerForwardException($to, $params, $noBeforeExecute);
	}

	public function getFullName()
	{
		return get_class($this);
	}

	public function getName()
	{
		$nameArr = explode('\\', $this->getFullName());
		return preg_replace('/Controller/', '', end($nameArr));
	}

	public function before($method)
	{
		$this->fireBefore($method);
	}

	public function after($result)
	{
		$this->fireAfter($result);
		return $result;
	}

	/**
	 * Register before execute handlers
	* @param \Closure $before
	 */
	public static function registerBefore(\Closure $before)
	{
		static::$before[] = $before;
	}

	/**
	 * Register after execute handlers
	* @param \Closure $after
	 */
	public static function registerAfter(\Closure $after)
	{
		static::$after[] = $after;
	}

	public function handleException(\Exception $e)
	{
		throw $e;
	}
}
