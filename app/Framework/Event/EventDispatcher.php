<?php

namespace Framework\Event;

/**
 * Class wich takes care for event firing, executing listeners, attaching listeners and subscribers
 * Basically the heart of the whole event system
 *
 */
class EventDispatcher
{
	/**
	 * Holds all registered listeners
	 * @var array
	 */
	static $listeners = array(
		'WEB' => array()
	);

	/**
	 * Executes a specific listener
	 *
	 * @param string $listener
	 * @param Event $event the event that was fired
	 */
	static private function executeListener($listener, Event $event)
	{
		if (! is_string($listener) &&  is_callable($listener)) {
			return $listener($event);
		}

		list($class, $method) = explode(':', $listener);

		if (is_subclass_of('EventSubscriber\\' . $class, 'Framework\\Event\\EventSubscriber')) {
			$instance = EventSubscriberManager::get($class);
		} else {
			$instance = new $class();
		}

		return $instance->$method($event);
	}

	/**
	 * Registeres a new listener
	 *
	 * @param string $eventName The event name
	 * @param string $listener
	 */
	static public function listen($eventName, $handler, $context, $priority = 0)
	{
		if (! isset(self::$listeners[$context][$eventName])) {
			self::$listeners[$context][$eventName] = array();
		}

		self::$listeners[$context][$eventName][] = array(
			'priority' => $priority,
			'handler' => $handler
		);
	}

	/**
	 * Fires an event
	 *
	 * @param string $eventName Event to be fired
	 * @param array $eventParams Event specific params
	 */
	static public function fire($eventName, $eventParams= array())
	{
		$execResult = null;

		if (isset(self::$listeners[APP_CONTEXT][$eventName])) {

			$eventListeners = self::$listeners[APP_CONTEXT][$eventName];

			$insertOrder = array();
			$priority = array();
			foreach ($eventListeners as $key => $value) {
				$insertOrder[$key] = $key;
				$priority[$key] = $value['priority'];
			}

			array_multisort($priority, SORT_DESC, $insertOrder, SORT_ASC, $eventListeners);

			$event = new Event($eventName, $eventParams);
			foreach ($eventListeners as $listener) {
				$execResult = self::executeListener($listener['handler'], $event);

				if ($execResult === false) {
					break;
				}

				if ($execResult instanceof Event) {
					$event = $execResult;
				}
			}
			unset($event);
			return $execResult;
		}
	}

	/**
	 * Register all subscribers in the app/EventSubscriber folder
	 */
	public static function registerAllSubscribers()
	{
		$dir = app_dir . 'EventSubscriber/*.php';
		$subscribers = glob($dir);
		foreach ($subscribers as $subscriber) {
			$name = preg_replace('/\.php/', '', basename($subscriber));
			self::subscribe($name);
		}
	}

	/**
	 * Makes sibscription for a particular event subscriber
	 *
	 * @param string $subscriberName The full class name of the event subscriber
	 */
	static public function subscribe($subscriberName)
	{
		$subscriber = EventSubscriberManager::get($subscriberName);

		$eventList = $subscriber->getEventList();

		foreach ($eventList as $context => $contextEventList) {

			foreach ($contextEventList as $eventName => $listeners) {
				if(! is_array($listeners)) {
					$listeners = array($listeners);
				}
				foreach ($listeners as $listener) {
					if (is_string($listener['handler'])) {
						$handlerName = $subscriberName . ':' . $listener['handler'];
						$handlerExists = false;
						if (isset(self::$listeners[$context][$eventName])) {
							foreach (self::$listeners[$context][$eventName] as $existingListener) {
								if ($existingListener['handler'] == $handlerName) {
									$handlerExists = true;
								}
							}
						}

						if (! $handlerExists) {
							self::listen($eventName, $handlerName, $context, $listener['priority']);
						}
					} elseif (is_callable($listener['handler'])){
						// @TODO Exception - Adding callable event listener through EventSubscriber is prohibited
					}
				}
			}
		}
	}

	/** Unsubscribe some context listeners from event
	 *
	 * @param string $context
	 * @param string $eventName
	 * @param string $handlerName
	 */
	static public function unsubscribe($context, $eventName, $handlerName = null)
	{
		if (! is_null($handlerName)) {
			foreach (self::$listeners[$context][$eventName] as $handlerKey => $existingListener) {
				if ($existingListener['handler'] == $handlerName) {
					unset(self::$listeners[$context][$eventName][$handlerKey]);
				}
			}
		} else {
			unset(self::$listeners[$context][$eventName]);
		}
	}
}
