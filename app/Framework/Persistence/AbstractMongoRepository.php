<?php
namespace Framework\Persistence;

use Framework\Factory\AdapterFactory;
/**
 
 */
abstract class AbstractMongoRepository
{
	/**
	 * @var MongoAdapter
	 */
	protected $adapter;

	protected
			$collectionName = '',
			$dbHost = '',
			$dbName = '',
			$collectionObject,
			$dbObject;

	/**
	 * Acceptable options: dbHost, dbName, user, pass
	 *
	*/
	abstract public function setOptions();

	public function __construct()
	{
		$options = $this->setOptions();
		$options['driver'] = 'mongo';

		$this->adapter = AdapterFactory::create($options);
		$this->dbName = $this->adapter->getDbName();
		$this->dbHost= $this->adapter->getDbHost();
		$this->collectionName = (isset($options['collectionName']) ? $options['collectionName'] : '');
		$this->collectionObject = $this->adapter->getMongo()->{$this->dbName}->{$this->collectionName};
		$this->dbObject = $this->adapter->getMongo()->selectDB($this->dbName);
	}

	/**
	* @param array $conditions
	 * @return \MongoCursor
	 */
	public function findBy(array $conditions = array(), array $columns = array())
	{
		$cursor = $this->collectionObject->find($conditions, $columns);
		return $cursor;
	}

	/**
	* @param array $conditions
	 * @return \MongoCursor
	 */
	public function findOneBy(array $conditions = array(), array $columns = array())
	{
		$cursor = $this->collectionObject->findOne($conditions, $columns);
		return $cursor;
	}

	public function execute(\MongoCode $code, $params)
	{
		return $this->adapter->getMongo()->selectDB($this->dbName)->execute($code, $params);
	}

	/** Insert element in current collection
	 *
	 * @param array $params
	 */
	public function insertElement(array $params)
	{
		return $this->collectionObject->insert($params);
	}

	/** Insert element in current collection
	 *
	 * @param array $params
	 */
	public function distinct($key, array $params)
	{
		return $this->collectionObject->distinct($key, $params);
	}

	public function aggregate(array $params)
	{
		return $this->collectionObject->aggregate($params);
	}

	public function removeElement($elementId)
	{
		$this->collectionObject->remove(array('_id' => new \MongoId($elementId)), array('justOne' => true));
	}
}
