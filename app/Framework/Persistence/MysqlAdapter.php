<?php
namespace Framework\Persistence;

use Framework\Persistence\Exception\CannotConnectException;
use Framework\Persistence\Exception\DatabaseException;
use Framework\Support\Profiler;

class MysqlAdapter
{
	private $transactionLevel = 0;

	/**
	 * @var \PDO
	 */
	protected $pdo;
	protected
			$dbName,
			$dbHost,
			$dsn;

	public
		$queryOk = true,
		$lastStatementInfo = array(),
		$lastInsertId,
		$affectedRows,
		$currentDate = 0,
		$currentTimestamp = 0;

	public function __construct($dbName, $dbHost)
	{
		$this->dbName = $dbName;
		$this->dbHost = $dbHost;
		$this->dsn = "mysql:dbname={$dbName};host={$dbHost}";
	}

	public function connect($username = '', $password = '')
	{
		try {
			$this->pdo = new \PDO($this->dsn, $username, $password);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			$this->pdo->exec('SET NAMES utf8');
		} catch (\Exception $ex) {
			throw new CannotConnectException("DSN: {$this->dsn}");
		}

		Profiler::addDbConnection(array(
			'dsn' => $this->dsn
		));

		$this->getNows();

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

	public function getPdo()
	{
		return $this->pdo;
	}

	public function beginTransaction()
	{
		if ($this->transactionLevel == 0) {
			$this->pdo->beginTransaction();
			Profiler::addQuery(array(
				'sql' => ' >BEGIN TRANSACTION',
				'host' => $this->dbHost,
				'database' => $this->dbName,
			));
		}
		$this->transactionLevel ++;
	}

	public function commit()
	{
		$this->transactionLevel -- ;
		if ($this->transactionLevel == 0) {
			$this->pdo->commit();
			Profiler::addQuery(array(
				'sql' => ' >COMMIT TRANSACTION',
				'host' => $this->dbHost,
				'database' => $this->dbName,
			));
		}
	}

	public function rollBack(\Exception $ex)
	{
		$this->transactionLevel -- ;
		if ($this->transactionLevel == 0) {
			$this->pdo->rollBack();
		}
		throw $ex;
	}

	public function getLastInsertId()
	{
		return $this->pdo->lastInsertId();
	}

	/**
	 * Execute a query
	 *
	* @param string $sql
	 * @param array $parameters
	 * @throws DatabaseException
	 * @return PDOStatement
	 */
	public function exec($sql, $parameters = null)
	{
		Profiler::startMiniTimer(Profiler::TIMER_QUERY);
		$statement = $this->getPdo()->prepare($sql);

		try {
			$statement->execute($parameters);
			$this->queryOk = true;
		} catch (\PDOException $e) {
			$this->queryOk = false;
			throw new DatabaseException('Query failed: ' . $sql . '  | MESSAGE: ' . $e->getMessage());
		}

		Profiler::stopMiniTimer(Profiler::TIMER_QUERY);

		Profiler::addQuery(array(
			'sql' => $sql,
			'bindParams' => $parameters,
			'host' => $this->dbHost,
			'database' => $this->dbName,
			'timer' => Profiler::getMiniTimerResult(Profiler::TIMER_QUERY)
		));

		$this->lastStatementInfo = array(
			'sql' => $sql,
			'bindParams' => $parameters,
			'pdoStatement' => $statement,
		);

		if ($this->pdo->lastInsertId()) {
			$this->lastInsertId = $this->pdo->lastInsertId();
		}
		if ($statement && ! is_null($statement->rowCount())) {
			$this->affectedRows = $statement->rowCount();
		}

		return $statement;
	}

	/**
	 * Get current times according the MySQL server
	 *
	* @return \Framework\Persistence\MysqlAdapter
	 */
	public function getNows()
	{
		$sql = "SELECT NOW();";
		$sth = $this->exec($sql);
		if ($sth) {
			$data = $sth->fetch(\PDO::FETCH_NUM);
			if ($data) {
				$this->currentDate = $data[0];
				$this->currentTimestamp = strtotime($data[0]);
			}
		}

		return $this;
	}
}
