<?php
namespace Framework\Persistence;

use \PDO;
use Framework\Factory\AdapterFactory;
use Framework\Persistence\Exception\DatabaseException;
use Framework\Exception\Assert;
use Framework\Config\AppConfig;

/**
 *
 *
 */
abstract class AbstractMySqlRepository
{
	/**
	 * @var MysqlAdapter
	 */
	protected $adapter;

	protected
			$tableName = '',
			$entityClassName = '',
			$entityName = '',
			$cacheEntities = true,
			$primaryKeyName = '',
			$dbHost = '',
			$dbName = '';


	/**
	 * Acceptable options: dbHost, dbName, user, pass, tableName, primaryKeyName, entityClassName, cacheEntities
	 *
	 *
	 */
	abstract public function setOptions();

	/**
	 * Checks the if repo setup is suitable to work with finders
	 * Used in the finders themselves and check if the tableName an primaryKey props are setuped
	 * IMPORTANT: This method do not check if tableName and primaryKey are acctually presented in the database.
	 * It only checks if they have any value
	 *
	 * @param string $method the method which envoked the check
	 * @throws DatabaseException If the setup is not correct
	 */
	private function checkSetup($method)
	{
		if ($this->tableName == '' || $this->primaryKeyName == '') {
			throw new DatabaseException(
				"Could not use {$method} of a repository with undefined table or primary key (" . __CLASS__ . ')');
		}
	}

	/**
	 * SET expression
	 * Generates SET expression using specified array of columns
	 *
	 * @param array
	 * @return boolean string
	 */
	private function buildSetExpressionFromColumns($columns)
	{
		if (count($columns) == 0) {
			throw new DatabaseException('Cannot build Set Expression, no columns specified', 1030);
		}

		foreach ($columns as $column) {
			$set[] = "`{$column}` = :{$column}";
		}

		return implode(", ", $set);
	}

	private function buildUpdateWhereExpressionFromConditions($conditions)
	{
		if (count($conditions) == 0) {
			return false;
		}
		$and = '';
		$where = '';
		foreach ($conditions as $column => $value) {
			$and = ' AND ';
			if (is_null($value)) {
				$where .= $and . $column . ' IS NULL';
			} else {
				$where .= $and . $column . ' = :' . $column .'2';
			}

		}
		return $where;
	}

	/**
	 * Simpe WHERE expression builder
	 * Generates WHERE clause which is a conjuction of all parameters names that are passed in $conditions
	 * It uses :placeholder-s in order the maintain auto-bind, but do not preform the actual binding.
	 *
	 * @param array $conditions
	 * @return boolean string
	 * @example <code>
	 *          $w = $this->buildWhereExpressionFromConditions(array(
	 *          'foo' => 'bar',
	 *          'baz' => 124,
	 *          'pop' => array('comparator' => '<', 'value' => 5)
	 *          ));
	 *          echo $w;
	 *          The above code will output something like:
	 *          'foo = :foo AND baz = :baz AND pop < :pop'
	 */
	protected function buildWhereExpressionFromConditions(&$conditions)
	{
		if (count($conditions) == 0) {
			return false;
		}
		$and = '';
		$where = '';
		$addConditions = array();
		foreach ($conditions as $column => $value) {
			if (is_array($value)) {
				if (isset($value['comparator']) && isset($value['value'])) {
					if (endsWith($value['value'], '()')) {
						$bind = $value['value'];
						unset($conditions[$column]);
					} else {
						$bind = ':' . $column;
						$conditions[$column] = $value['value'];
					}
					$where .= $and . $column . ' ' . $value['comparator'] . ' ' . $bind;
				} else {
					$valuesAsString = '';
					foreach ($value as $key => $singleValue) {
						$valuesAsString .= ':' . $column . $key . ',';
						$addConditions[$column . $key] = $singleValue;
					}
					unset($conditions[$column]);
					$valuesAsString = rtrim($valuesAsString, ',');
					$where .= $and . $column . " IN ({$valuesAsString})";
				}
			} else {
				$where .= $and . $column . ' = :' . $column;
			}
			$and = ' AND ';
		}
		$conditions = array_merge($conditions, $addConditions);
		return $where;
	}

	protected function buildInExpression(array $params)
	{
		$result = implode(',', $params);
		$result = '(' . rtrim($result, ',') . ')';

		return $result;
	}

	/**
	 * Creates FORCE INDEX expression
	 *
	 * @param string $columnName
	 * @return string
	 */
	private function buildForceIndex($columnName)
	{
		$fi = '';
		if ($columnName) {
			$fi = "FORCE INDEX ({$columnName})";
		}
		return $fi;
	}

	/**
	 * Create a between expression.
	 * This supports non consecutive numbers. For example the set 1,2,3,4,5,8 will be builded as:
	 * ($columnName BETWEEN 1 AND 5 OR $columnName = 8)
	 *
	 * @param array $data An array with numbers (can be non consecutive)
	 * @param string $columnName
	 */
	protected function buildBetweenExpression($data, $columnName) {
		sort($data);
		$count = count($data);
		$sets = array();
		$currentSet = 0;

		for ($i = 0; $i < $count; $i++) {
			if (empty($sets[$currentSet])) {
				$sets[$currentSet][] = $data[$i];
				continue;
			}

			if ($data[$i] != $data[$i - 1] + 1) {
				// end current set
				$currentSet++;
			}

			$sets[$currentSet][] = $data[$i];
		}

		$query = "";

		// Only one set, just one BETWEEN
		if (count($sets) == 1) {
			$query = "{$columnName} BETWEEN {$sets[0][0]} AND {$sets[0][$count - 1]}";
			return $query;
		}

		$innerQuery = array();

		foreach ($sets as $setData) {
			$setCount = count($setData);

			if ($setCount == 1) {
				$innerQuery[] = "{$columnName} = {$setData[0]}";
				continue;
			}

			$innerQuery[] = "{$columnName} BETWEEN {$setData[0]} AND {$setData[$setCount - 1]}";
		}

		$query = "(" . implode(" OR ", $innerQuery) . ")";

		return $query;
	}

	/**
	 * Build `column1, column2 VALUES value1, value2` expression
	 *
	 *
	 * @param array $bindParams
	 * @throws DatabaseException
	 * @return multitype:string
	 */
	protected function buildInsert(array $bindParams)
	{
		if (count($bindParams) == 0) {
			throw new DatabaseException('Bind params not provided.');
		}

		$comma = '';
		$cols = '';
		$placeholders = '';
		foreach ($bindParams as $key => $value) {
			$cols .= $comma . "`{$key}`";
			$placeholders .= $comma . ":{$key}";
			$comma = ', ';
		}
		$placeholders = '(' . $placeholders . ')';
		$cols = '(' . $cols . ')';

		return "{$cols} VALUES {$placeholders}";
	}

	/**
	 * Execute Insert statement and return the inserted id
	 *
	 *
	 * @param string $sql
	 * @param array $bindParams
	 * @return int|boolean
	 */
	protected function execCreate($sql, array $bindParams = array())
	{
		$this->adapter->exec($sql, $bindParams);

		if ($this->adapter->queryOk) {
			return $this->adapter->getPdo()->lastInsertId();
		} else {
			return false;
		}
	}

	public function __construct()
	{
		$options = $this->setOptions();
		$options['driver'] = 'mysql';

		$this->adapter = AdapterFactory::create($options);
		$this->dbName = $this->adapter->getDbName();
		$this->dbHost= $this->adapter->getDbHost();
		$this->tableName = get($options, 'tableName', '');
		$this->primaryKeyName = get($options, 'primaryKeyName', '');
		$this->cacheEntities = get($options, 'cacheEntities', AppConfig::get('CacheEntities', false));
		$this->entityName = ucfirst(get($options, 'entityClassName', ''));
		$this->entityClassName = (isset($options['entityClassName']) ? 'Entity\\' . ucfirst($options['entityClassName']) : '');
	}

	public function getTableName()
	{
		return $this->tableName;
	}

	public function getPkName()
	{
		return $this->primaryKeyName;
	}

	public function getDbName()
	{
		return $this->dbName;
	}

	public function getDbHost()
	{
		return $this->dbHost;
	}

	public function getAdapter()
	{
		return $this->adapter;
	}

	/**
	 * Inserts new record in the db table
	 * It uses the bind params to populate the columns of the record.
	 *
	 * @param array $bindParams
	 * @return mixed Returns the id of the new record or false on failure
	 */
	public function create($bindParams)
	{
		$this->checkSetup(__METHOD__);

		$values = $this->buildInsert($bindParams);

		$sql = "INSERT INTO `{$this->tableName}` {$values}";

		return $this->execCreate($sql, $bindParams);
	}

	/**
	 * Same as create() but with INSERT IGNORE
	 *
	 *
	 * @param array $bindParams
	 * @throws DatabaseException
	 */
	public function createIgnore(array $bindParams)
	{
		$this->checkSetup(__METHOD__);

		$values = $this->buildInsert($bindParams);

		$sql = "INSERT IGNORE INTO `{$this->tableName}` {$values}";

		return $this->execCreate($sql, $bindParams);
	}

	/**
	 * Update record in the db table
	 * It uses the bind params to populate the columns of the record.
	 *
	 * @param array $bindParams
	 * @return boolean
	 */
	public function update($id, $bindParams)
	{
		$this->checkSetup(__METHOD__);

		if (count($bindParams) == 0) {
			throw new DatabaseException('Bind params not provided.');
		}

		$comma = '';
		$placeholders = '';
		foreach ($bindParams as $key => $value) {
			$placeholders .= $comma . "`{$key}` = " . ":{$key}";
			$comma = ', ';
		}

		$sql = "UPDATE `{$this->tableName}` SET {$placeholders} WHERE " . $this->primaryKeyName . " = :" . $this->primaryKeyName . "_key";

		$bindParams[$this->primaryKeyName . "_key"] = $id;

		$this->exec($sql, $bindParams);

		if ($this->adapter->queryOk) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Delete a record from the db table
	 *
	 *
	 * @param int $id
	 */
	public function delete($id)
	{
		$this->checkSetup(__METHOD__);

		$sql = "DELETE FROM
		{$this->tableName}
		WHERE
		{$this->primaryKeyName} = {$id}";

		$this->adapter->exec($sql);

		return $this->adapter->queryOk;
	}

	/**
	 * Update a record columns by primary key and changed values
	 *
	 * @param integer $primaryKey A value of the primary key used in WHERE clause
	 * @param array $bindParams array('set' => Name=>Value pair of the parameter to set/bind, 'where' => Name=>Value for the where statement
	 * @return boolean
	 */
	public function saveEntity($primaryKey, $bindParams)
	{
		$this->checkSetup(__METHOD__);
		if (empty($bindParams['set'])) {
			return true;
		}
		$set = $this->buildSetExpressionFromColumns(array_keys($bindParams['set']));
		$where = $this->buildUpdateWhereExpressionFromConditions($bindParams['where']);

		$sql = "UPDATE `{$this->tableName}` SET {$set} WHERE `{$this->primaryKeyName}` = :pk {$where}";
		$bindParamsReal = $bindParams['set'];

		$bindParamsReal['pk'] = $primaryKey;
		foreach ($bindParams['where'] as $colum => $value) {
			if (! is_null($value)) {
				$bindParamsReal[$colum.'2'] = $value;
			}
		}
		$this->adapter->exec($sql, $bindParamsReal);
		if ($this->adapter->affectedRows == 0) {
			ob_start();
			debug_print_backtrace();
			$backtrace = ob_get_contents();
			ob_end_clean();
			Assert::fire('Failed update', Assert::FAILED_OPERATION, array('query' => $sql, 'params' => $bindParamsReal, 'back_trace' => $backtrace));
		}

		return true;
	}

	/**
	 * Find a record from the table by its ID
	 *
	 *
	 * @param int $id
	 * @param boolean $assoc
	 * @return array
	 */
	public function find($id, $assoc = true)
	{
		$this->checkSetup(__METHOD__);

		$sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKeyName} = :id";
		if ($assoc) {
			return $this->adapter->exec($sql, array('id' => $id))->fetch(PDO::FETCH_ASSOC);
		} else {
			return $this->adapter->exec($sql, array('id' => $id))->fetch();
		}
	}

	/**
	 * Load an entity from the database
	 *
	 *
	 * @param unknown $id
	 * @param bool $selectForUpdate
	 * @throws DatabaseException
	 * @return AbstractEntity
	 */
	public function fetch($id, $selectForUpdate = false)
	{
		if (! class_exists($this->entityClassName)) {
			throw new DatabaseException('Could not use loaders to repo which does not have entityClassName info.');
		}

		$entityPool = EntityPool::getInstance();

		if ($this->cacheEntities && $entityPool->get($this->entityName, $id)) {
			return $entityPool->get($this->entityName, $id);
		}

		$forUpdate = '';

		if ($selectForUpdate === true) {
			$forUpdate = ' FOR UPDATE';
		}

		$sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKeyName} = :id{$forUpdate}";

		$stm = $this->adapter->getPdo()->prepare($sql);

		$stm->setFetchMode(PDO::FETCH_CLASS, $this->entityClassName);
		$stm->execute(array(
			'id' => $id
		));

		$object = $stm->fetch();

		if ($this->cacheEntities && $object) {
			$entityPool->add($object);
		}

		return $object;
	}

	/**
	 * Returns an array of rows that matches several conditions using AND
	 * Uses auto-binding
	 *
	 * @param array $conditions Array of key, value pairs. Each key is the name of the column and the value is the
	 *        condition which this column supose to match
	 * @param mixed $assoc Whether to hydrate as associative array (true), indexed array(false) or as
	 *        object(PDO::FETCH_CLASS)
	 * @param string $forceIndex Force index
	 * @param string $sortBy Sort by column
	 * @param string $sortDir Sort direction (asc/desc)
	 * @return array An array representing the result set
	 */
	public function findBy($conditions, $assoc = true, $forceIndex = null, $sortBy = null, $sortDir = 'asc', $limit = null, $offset = 0)
	{
		$this->checkSetup(__METHOD__);

		if (false === $where = $this->buildWhereExpressionFromConditions($conditions)) {
			return array();
		}

		$fi = $this->buildForceIndex($forceIndex);

		$sort = '';
		if(! is_null($sortBy)) {
			$sort = " ORDER BY {$sortBy} " . strtoupper($sortDir);
		}

		$limitBy = '';
		if (! is_null($limit)) {
			$limit = (int) $limit;
			$offset = (int) $offset;
			$limitBy = " LIMIT {$limit} OFFSET {$offset} ";
		}

		$sql = "SELECT * FROM {$this->tableName} {$fi} WHERE {$where}{$sort}{$limitBy}";
		if ($assoc === PDO::FETCH_CLASS) {
			if (! class_exists($this->entityClassName)) {
				throw new DatabaseException('Could not use loaders to repo which do not have entityClassName info.');
			}
			return $this->adapter->exec($sql, $conditions)->fetchAll(PDO::FETCH_CLASS, $this->entityClassName);
		} else if ($assoc) {
			return $this->adapter->exec($sql, $conditions)->fetchAll(PDO::FETCH_ASSOC);
		} else {
			return $this->adapter->exec($sql, $conditions)->fetchAll();
		}
	}

	/**
	 * Fetches all records from a specific table
	 *
	 * @param mixed $fetchStyle PDO::FETCH_CLASS or boolean
	 * @throws Exception
	 * @return array
	 */
	public function findAll($fetchStyle = true)
	{
		$this->checkSetup(__METHOD__);

		$sql = "SELECT * FROM `{$this->tableName}` ORDER BY `{$this->primaryKeyName}` ASC";

		if ($fetchStyle === PDO::FETCH_CLASS) {
			if (! class_exists($this->entityClassName)) {
				throw new DatabaseException('Could not use loaders to repo which do not have entityClassName info.');
			}
			return $this->adapter->exec($sql)->fetchAll(PDO::FETCH_CLASS, $this->entityClassName);
		} elseif ($fetchStyle) {
			return $this->adapter->exec($sql)->fetchAll(PDO::FETCH_ASSOC);
		} else {
			return $this->adapter->exec($sql)->fetchAll();
		}
	}

	/**
	 * Returns a row that matches several conditions using AND
	 *
	 * @param array $conditions Array of key, value pairs. Each key is the name of the column and the value is the
	 *        condition which
	 *        this column supose to match
	 * @param bool $assoc Whether to hydrate as associative array (true), indexed array(false) or as
	 *        object(PDO::FETCH_CLASS)
	 * @param string $forceIndex If presented, the db will be forced to use the specified column as index
	 * @throws Exception
	 * @return AbstractEntity array array or object representing the row
	 */
	public function findOneBy($conditions, $assoc = true, $forceIndex = null)
	{
		$this->checkSetup(__METHOD__);

		if (false === $where = $this->buildWhereExpressionFromConditions($conditions)) {
			return array();
		}

		$fi = $this->buildForceIndex($forceIndex);

		$sql = "SELECT * FROM {$this->tableName} {$fi} WHERE {$where}";
		if ($assoc === PDO::FETCH_CLASS) {
			if (! class_exists($this->entityClassName)) {
				throw new DatabaseException('Could not use loaders to repo which do not have entityClassName info.');
			}
			$stm = $this->adapter->exec($sql, $conditions);
			$stm->setFetchMode(PDO::FETCH_CLASS, $this->entityClassName);
			return $stm->fetch();
		} elseif ($assoc) {
			return $this->adapter->exec($sql, $conditions)->fetch(PDO::FETCH_ASSOC);
		} else {
			return $this->adapter->exec($sql, $conditions)->fetch();
		}
	}

	/**
	 * Returns the value of a column
	 * Returns the literal value of the first column of the first row of the resultset by using PDO::FETCH_COLUMN fetch
	 * style
	 *
	 * @param string $sql SQL statement. Using of binding params is alowed
	 * @param array $bindParams Binding params which will be used for auto-binding
	 * @return mixed The value of the colum
	 */
	function getSqlValue($sql, array $bindParams = array())
	{
		$sth = $this->adapter->exec($sql, $bindParams);
		if ($sth) {
			return $sth->fetch(PDO::FETCH_COLUMN, 0);
		}
		return;
	}

	/**
	 * Executes a statement and returns the resultset
	 * This method will fetch the resultset by using PDO::FETCH_ASSOC fetch style
	 *
	 * @param string $sql SQL statement. Using of binding params is alowed
	 * @param array $bindParams Binding params which will be used for auto-binding
	 * @return array null an array representing the resultset on succes and null on failure
	 */
	public function selectAssoc($sql, array $bindParams = array())
	{
		$sth = $this->adapter->exec($sql, $bindParams);

		if ($sth) {
			return $sth->fetchAll(PDO::FETCH_ASSOC);
		}
		return;
	}

	/**
	 *
	 *
	 *
	 * @param string $sql
	 * @param array $bindParams
	 * @return array
	 */
	public function selectColumn($sql, array $bindParams = array())
	{
		$sth = $this->adapter->exec($sql, $bindParams);

		if($sth) {
			return $sth->fetchAll(PDO::FETCH_COLUMN);
		}
		return;
	}

	/**
	 *
	 *
	 *
	 * @param string $sql
	 * @param array $bindParams
	 * @return array
	 */
	public function selectPair($sql, array $bindParams = array())
	{
		$sth = $this->adapter->exec($sql, $bindParams);

		if($sth) {
			return $sth->fetchAll(PDO::FETCH_KEY_PAIR);
		}

		return;
	}

	/**
	 * Executes a statement and returns the first row
	 * This method will fetch the row by using PDO::FETCH_ASSOC fetch style
	 *
	 * @param string $sql SQL statement. Using of binding params is alowed
	 * @param array $bindParams Binding params which will be used for auto-binding
	 * @return array null an array representing the row on succes and null on failure
	 */
	public function selectRowAssoc($sql, array $bindParams = array()) {
		$sth = $this->adapter->exec($sql, $bindParams);

		if ($sth) {
			return $sth->fetch(PDO::FETCH_ASSOC);
		}
		return;
	}

	/**
	 * Executes a statement and returns the resultset
	 * This method will fetch the resultset by using PDO::FETCH_CLASS fetch style
	 *
	 *
	 * @param string $sql
	 * @param array $bindParams
	 * @return array<AbstractEntity>
	 */
	public function selectEntities($sql, array $bindParams = array()) {
		$sth = $this->adapter->exec($sql, $bindParams);

		if ($sth) {
			return $sth->fetchAll(\PDO::FETCH_CLASS, $this->entityClassName);
		}
		return;
	}

	/**
	 * Executes a statement
	 *
	 *
	 * @param string $sql
	 * @param array $bindParams
	 */
	public function exec($sql, array $bindParams = array()) {
		$this->adapter->exec($sql, $bindParams);
	}

	/**
	 * Proxy for MysqlAdapter::beginTransaction
	 *
	 *
	 */
	public function beginTransaction()
	{
		return $this->adapter->beginTransaction();
	}

	/**
	 * Proxy for MysqlAdapter::commit
	 *
	 *
	 */
	public function commit()
	{
		return $this->adapter->commit();
	}

	/**
	 * Proxy for MysqlAdapter::rollback
	 *
	 *
	 */
	public function rollback(DatabaseException $ex)
	{
		return $this->adapter->rollBack($ex);
	}

	/**
	 * Get table's indexes
	 *
	 *
	 * @return Ambigous <multitype:, void>
	 */
	public function getIndexes()
	{
		$sql = "SHOW KEYS FROM {$this->tableName}";

		return $this->selectAssoc($sql);
	}

	/**
	 * Get table's primary key
	 *
	 *
	 * @return Ambigous <multitype:, void>
	 */
	public function getPrimaryKey()
	{
		$sql = "SHOW KEYS FROM {$this->tableName} WHERE Key_name = 'PRIMARY";

		return $this->selectAssoc($sql);
	}


	/** Delete record by conditions
	 *
	 * @param array $params
	 * @return affectedRows
	 */
	public function deleteByConditions(array $params)
	{
		$this->checkSetup(__METHOD__);

		$whereExpr = $this->buildWhereExpressionFromConditions($params);

		if ($whereExpr !== false) {
			$sql = 'DELETE FROM ' . $this->tableName . ' WHERE ' . $whereExpr;

			$this->exec($sql, $params);

			return $this->adapter->affectedRows;
		}

		return false;
	}
}
