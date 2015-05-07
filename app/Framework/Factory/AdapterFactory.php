<?php
namespace Framework\Factory;

use Framework\Config\AppConfig;

use Framework\Persistence\MysqlAdapter;
use Framework\Persistence\MongoAdapter;

class AdapterFactory
{
	private static $adapters = array();

	private static function createMysqlAdapter(array $cfg = array())
	{
		$host = $cfg['dbHost'];
		$dbName = $cfg['dbName'];

		if (empty(self::$adapters['mysql'][$host][$dbName])) {
			$user = (isset($cfg['user']) ? $cfg['user'] : AppConfig::get('mysqlUser'));
			$pass = (isset($cfg['pass']) ? $cfg['pass'] : AppConfig::get('mysqlPassword'));

			$adapter = chain(new MysqlAdapter($dbName, $host))->connect($user, $pass);
			self::$adapters['mysql'][$host][$dbName] = $adapter;
		}

		return self::$adapters['mysql'][$host][$dbName];
	}

	private static function createMongoAdapter(array $cfg = array())
	{
		$host = $cfg['dbHost'];
		$dbName = $cfg['dbName'];

		if (empty(self::$adapters['mongo'][$host][$dbName])) {
			$user = (isset($cfg['user']) ? $cfg['user'] : AppConfig::get('mongoUser'));
			$pass = (isset($cfg['pass']) ? $cfg['pass'] : AppConfig::get('mongoPassword'));

			$adapter = chain(new MongoAdapter($dbName, $host))->connect($user, $pass);
			self::$adapters['mongo'][$host][$dbName] = $adapter;
		}

		return self::$adapters['mongo'][$host][$dbName];
	}

	/**
	 * Adapter factory
	 *
	* @param string $name
	 * @return \Framework\Persistence\Adapter
	 */
	public static function create(array $cfg = array())
	{
		$driver = (isset($cfg['driver']) ? $cfg['driver'] : 'mysql');
		$method = 'create' . ucfirst($driver) . 'Adapter';

		$defaultHost = AppConfig::get('mysqlDefaultHost');
		$defaultDbName = AppConfig::get('mysqlDefaultDbName');

		$cfg['dbHost'] = (isset($cfg['dbHost']) ? $cfg['dbHost'] : $defaultHost);
		$cfg['dbName'] = (isset($cfg['dbName']) ? $cfg['dbName'] : $defaultDbName);

		return self::$method($cfg);
	}
}
