<?php
namespace Framework\Persistence;

use Framework\Persistence\Exception\DatabaseException;
use Framework\Factory\AdapterFactory;

class UnitOfWork
{
	private static $queries = array();

	/**
	 * Sorts the queries by hosts, databases and type (create, update, delete)
	 *
	*/
	private static function sortQueries()
	{
		$unsorted = self::$queries;
		$sorted = array();
		foreach ($unsorted as $host => $names) {
			foreach ($names as $name => $types) {
				ksort($types);
				$sorted[$host][$name] = call_user_func_array('array_merge', $types);
			}
		}
		self::$queries = $sorted;
	}

	/**
	 * Builds delayed query and adds it to the queue
	 *
	* @param array $params
	 */
	public static function createDelayedQuery(array $params)
	{
		$query = new DelayedQuery($params);
		$dbInfo = $query->getDbInfo();
		$dbHost = $dbInfo['dbHost'];
		$dbName = $dbInfo['dbName'];
		self::$queries[$dbHost][$dbName][$query->getType()][] = $query;
	}

	/**
	 * Execute the query queue
	 *
	* @throws DatabaseException
	 */
	public static function flush()
	{
		self::sortQueries();
		$all = self::$queries;
		self::$queries = array();
		try {
			foreach ($all as $host => $names) {
				foreach ($names as $name => $queries) {
					$adapterCfg = array(
						'dbHost' => $host,
						'dbName' => $name,
					);
					$adapter = AdapterFactory::create($adapterCfg);
					$adapter->beginTransaction();
					foreach ($queries as $q) {
						$q->exec();
					}
					$adapter->commit();
				}
			}
		} catch (DatabaseException $e) {
			$adapter->rollBack($e);
			throw $e;
		}
	}

	/**
	 * Checks if there are any queries in the queue
	 *
	* @return boolean
	 */
	public static function hasQueries()
	{
		return (! empty(self::$queries));
	}

	/**
	 * Empty the queue
	 *
	*/
	public static function clear()
	{
		self::sortQueries();
		$all = self::$queries;
		self::$queries = array();

		foreach ($all as $host => $names) {
			foreach ($names as $name => $queries) {
				foreach ($queries as $q) {
					$q->restore();
				}
			}
		}
	}
}
