<?php
namespace Framework\Persistence;

use Framework\Persistence\Exception\CannotConnectException;
use Framework\Config\AppConfig;
use Framework\Support\Profiler;

class MongoAdapter
{
	protected
			$mongo,
			$dbName,
			$dbHost;

	public function __construct($dbName, $dbHost)
	{
		$this->dbName = $dbName;
		$this->dbHost = $dbHost;
		$port = AppConfig::get('mongoPort', 27017);

		$this->dsn = "mongodb://{$dbHost}/{$dbName}";
	}

	public function connect($username = '', $password = '')
	{
		try {
			$this->mongo = new \MongoClient($this->dsn, array(
				'username' => $username,
				'password' => $password,
			));
		} catch (\Exception $ex) {
			throw $ex;
			throw new CannotConnectException("DSN: {$this->dsn}");
		}

		Profiler::addDbConnection(array(
			'dsn' => $this->dsn
		));

		return $this;
	}

	public function getDbName()
	{
		return $this->dbName;
	}

	public function getDbHost()
	{
		return $this->dbHost;
	}

	/**
	* @return \MongoClient
	 */
	public function getMongo()
	{
		return $this->mongo;
	}
}
