<?php

namespace Framework\Support;

use Framework\Support\Exception\ProfilerException;
use Framework\Http\Request;
use Framework\Factory\RepositoryFactory;
use Framework\Event\EventDispatcher;

class Profiler
{
	const TIMER_QUERY = 'qwr_timer';
	const TIMER_FRAMEWORK = 'framework_timer';

	private static $data = array();

	private static $queries = array();
	private static $dbConnections = array();

	private static $miniTimers = array();

	private static $settings;

	private static $frameworkCalls = array();

	private static $sleepSeconds = 0;


	private static function getSettings()
	{
		if (! isset(self::$settings)) {
			// @TODO set settings
		}

		return self::$settings;
	}

	private static function prepareSql($sql, $params)
	{
		foreach ($params as $key => $value) {
			$sql = preg_replace('/:' . $key . '/', $value, $sql);
			$sql = preg_replace('/\?/', $value, $sql, 1);
		}
		return $sql;
	}

	public static function addQuery($params)
	{
		if (Request::getInstance()->isInConsole) {
			return;
		}
		$sql = $params['sql'];
		$bindParams = (! empty($params['bindParams']) ? $params['bindParams'] : array());
		$host = $params['host'];
		$database = $params['database'];
		$timer = (isset($params['timer']) ? self::prepareMs($params['timer']) : 0);

		if ($bindParams) {
			$sql = self::prepareSql($sql, $bindParams);
		}

		self::$queries[$host][$database][] = array(
			'sql' => $sql,
			'time' => $timer,
		);
		if (empty(self::$data['queriesTime'])) {
			self::$data['queriesTime'] = 0;
		}
		self::$data['queriesTime'] += $timer;
	}

	public static function addDbConnection($params)
	{
		$dsn = $params['dsn'];
		self::$dbConnections[] = $dsn;
	}

	private static function getMiniTimers()
	{
		$result = array();
		foreach (self::$miniTimers as $name => $data) {
			if ($name == self::TIMER_QUERY) continue;
			$result[$name] = self::prepareMs(isset($data['time']) ? $data['time'] : 0);
		}
		return $result;
	}

	public static function prepareMs($time)
	{
		return round($time*1000, 2) . 'ms';
	}

	public static function start()
	{
		// @TODO Implementation after we know what we want to do
		self::$data = array(
			'startTime' => microtime(true),
			'userIpAddress' => getRealIpAddress()
		);
	}

	public static function stop()
	{
		self::$data['stopTime'] = microtime(true);

		EventDispatcher::fire('profiler.stop');
	}

	public static function startMiniTimer($timerName, $asFloat = true)
	{
		self::$miniTimers[$timerName] = array(
			'asFloat' => $asFloat,
			'start' => microtime($asFloat)
		);
	}

	public static function stopMiniTimer($timerName)
	{
		if (isset(self::$miniTimers[$timerName])) {
			$asFloat = false;
			if (isset(self::$miniTimers[$timerName]['asFloat'])) {
				$asFloat = self::$miniTimers[$timerName]['asFloat'];
			}
			self::$miniTimers[$timerName]['stop'] = microtime($asFloat);
			self::$miniTimers[$timerName]['time'] = self::$miniTimers[$timerName]['stop'] - self::$miniTimers[$timerName]['start'];
		} else {
			throw new ProfilerException('There is not started timer with name ' . $timerName . '!');
		}
	}

	public static function getMiniTimerResult($timerName)
	{
		if (isset(self::$miniTimers[$timerName])) {
			if (isset(self::$miniTimers[$timerName]['time'])) {
				return self::$miniTimers[$timerName]['time'];
			} else {
				throw new ProfilerException('Minitimer ' . $timerName . ' is not stoped!');
			}
		} else {
			throw new ProfilerException('There is not started timer with name ' . $timerName . '!');
		}
	}

	public static function getDataParam($name)
	{
		if (isset(self::$data[$name])) {
			return self::$data[$name];
		} else {
			throw new ProfilerException('Profiler parameter ' . $name . ' is not defined!');
		}
	}

	public static function frameworkCall($class, $method)
	{
		self::$frameworkCalls[] = array(
			'class' => $class,
			'method' => $method
		);
	}

	public static function addSleepSeconds($time)
	{
		self::$sleepSeconds += $time;
	}

	public static function getSummary()
	{
		if (! isset(self::$data['stopTime'])) {
			throw new ProfilerException('Request timer is not stoped!');
		} else {
			$loadingTime = self::$data['stopTime'] - self::$data['startTime'] - self::$sleepSeconds;
			$request = array(
				'fullTime' => self::prepareMs($loadingTime),
				'memUsage' => readMemory(memory_get_peak_usage()),
				'loadingTime' => $loadingTime,
			);
		}

		$result = array(
			'request' => $request,
			'frameworkCalls' => self::$frameworkCalls,
			'miniTimers' => self::getMiniTimers(),
			'connections' => self::$dbConnections,
			'queries' => self::$queries,
		);

		return $result;
	}

	public static function getQueriesCount()
	{
		$count = 0;
		foreach (self::$queries as $host) {
			foreach ($host as $realm) {
				$count += count($realm);
			}
		}

		return $count;
	}
}
