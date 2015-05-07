<?php

namespace Framework\Event;

class EventSubscriberManager
{
	static private $instances;

	/** Create and get or get already created instance of subscriber
	 *
	 * @param string $name
	 * @return object
	 */
	static public function get($name)
	{
		$name = 'EventSubscriber\\' . $name;
		if(isset(self::$instances[$name])) {
			$subscriber = self::$instances[$name];
		} else {
			if (class_exists($name)) {
				if (is_subclass_of($name, 'Framework\\Event\\EventSubscriber')) {
					$subscriber = new $name();
					self::$instances[$name] = $subscriber;
				} else {
					// @TODO Exception - Must extends EventSubscriber
				}
			} else {
				// @TODO Exception - Unknown eventsubscriber name
			}
		}

		return $subscriber;
	}

	/** Get all subscribers instances
	 *
	 * @return array
	 */
	static public function getInstanceSet()
	{
		return self::$instances;
	}
}
