<?php

namespace Framework\Event;

/**
 * Class representing an event
 */
class Event
{
	private $eventName,
			$params;

	public function __construct($eventName, $params) {
		$this->eventName = $eventName;
		$this->params = $params;
	}

	/** Get name of Event
	 *
	 * @return string
	 */
	public function getEventName()
	{
		return $this->eventName;
	}

	/** Get param by paramName
	 *
	 * @param string $paramName
	 * @param string $default
	 * @return mixed $result
	 */
	public function get($paramName, $default = null)
	{
		if (isset($this->params[$paramName])) {
			$result = $this->params[$paramName];
		} else {
			$result = $default;
		}
		return $result;
	}

	/** Set value to some param
	 *
	 * @param string $paramName
	 * @param mixed $paramValue
	 */
	public function set($paramName, $paramValue)
	{
		$this->params[$paramName] = $paramValue;
	}
}
