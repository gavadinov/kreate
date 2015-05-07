<?php

namespace Framework\Event;

/**
 * Abstraction of an Event subscriber
 *
 */
abstract class EventSubscriber
{
	const HIGHEST_EVENT_PRIORITY = 999;
	const SECOND_EVENT_PRIORITY = 998;
	const THIRD_EVENT_PRIORITY = 997;

	private $listenerPool = array(
		'WEB' => array(),
		'IOS' => array()
	);

	/**
	 * Configure listeners with events and handlers
	 */
	abstract public function init();

	public function __construct()
	{
		$this->init();
	}

	/** Return all subscribed event listeners
	 *
	 * @return array
	 */
	public function getEventList()
	{
		return $this->listenerPool;
	}

	/** Add listener in listener pool
	 *
	 *
	 * @param string $context
	 * @param string $event
	 * @param string $eventHandler
	 */
	public function addListener($context, $event, $eventHandler, $priority = 0)
	{
		if ($context == 'ANY') {
			foreach ($this->listenerPool as $context => $value) {
				$this->listenerPool[$context][$event][] = array(
					'priority' => $priority,
					'handler' => $eventHandler
				);
			}
		} else {
			$this->listenerPool[$context][$event][] = array(
					'priority' => $priority,
					'handler' => $eventHandler
			);
		}
	}
}
