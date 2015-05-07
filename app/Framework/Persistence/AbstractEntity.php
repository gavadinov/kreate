<?php
namespace Framework\Persistence;

use Framework\Exception\TerminalException;
use Framework\Factory\RepositoryFactory;
use Framework\Config\AppConfig;
use Framework\Exception\Assert;

/**
 *
 *
 
 */
abstract class AbstractEntity implements EntityInterface, \JsonSerializable
{

	/**
	 * General key-value storage
	 *
	 * @var array
	 */
	protected static $propertyMaps;

	protected
			$propertyMapsAndValues,
			$delayed = array(),
			$forDeletion = false,
			$dbState = array(),
			$propertyTypes = array();



	/**
	 * Cast numeric values to proper type
	 * NULL is kept
	*/
	protected function castNumericValues()
	{
		$this->preparePropertyMap();
		foreach ($this->propertyTypes as $type => $values) {
			foreach ($values as $value) {
				if (! is_null($this->$value)) {
					$castFunc = $type . 'val';
					$this->$value = $castFunc($this->$value);
				}
			}
		}
	}

	/**
	 * Prepares class property map.
	 * It uses static property to ensure the map is prepared only once per instantiated class
	 * You can extend to explicitly define the property map if you consider that the automatic procedure will
	 * be a performance issue.
	*/
	protected function preparePropertyMap($skipPk = true)
	{
		$reflection = new \ReflectionClass($this);

		if (isset(AbstractEntity::$propertyMaps[$reflection->getName()]) && $skipPk) {
			return;
		}

		$props = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);

		AbstractEntity::$propertyMaps[$reflection->getName()] = array();

		foreach ($props as $prop) {
			$skip = array(
				'propertyMapsAndValues',
				'delayed',
				'forDeletion',
				'dbState',
				'propertyTypes',
				'defaults',
			);
			if ($skipPk) {
				$skip[] = $this->getPkName();
			}
			if (in_array($prop->getName(), $skip) || $prop->isStatic()) {
				continue;
			}

			AbstractEntity::$propertyMaps[$reflection->getName()][] = $prop->getName();
		}
	}

	/**
	 *
	 * Load and store currently loaded entity properties and values in the beginig of the request.
	 * The stored data is used to check the changed properties and use only them in the save method
	*/
	public function storeInitialValues()
	{
		$this->preparePropertyMap();
		foreach (AbstractEntity::$propertyMaps[get_class($this)] as $prop) {
			$this->propertyMapsAndValues[get_class($this)][$prop] = $this->$prop;
		}
	}

	/**
	 * restore entity value to last saved in DB
	*/
	public function restoreInitialValues()
	{
		$this->preparePropertyMap();
		foreach ($this->propertyMapsAndValues[get_class($this)] as $prop => $value) {
			$this->$prop = $value;
		}
	}

	/**
	 * Generates key=>value array for all columns and values in the db table.

	 
	 * @return array
	 */
	public function generateSaveData()
	{
		$this->castNumericValues();

		$data = array();
		$propMap = AbstractEntity::$propertyMaps[get_class($this)];
		foreach ($propMap as $propName) {
			$getter = 'get' . ucfirst(underScoreToCamelCase($propName));
			if ($this->$getter() !== $this->propertyMapsAndValues[get_class($this)][$propName]) {
				$data['set'][$propName] = $this->$getter();
				$data['where'][$propName] = $this->propertyMapsAndValues[get_class($this)][$propName];
			}
		}
		return $data;
	}

	/**
	 * Generates key=>value array for all columns in the db table.
	 * You can extend to explicitly prepare the save data if you consider that the automatic procedure will
	 * produce performance issue.
	* @return array
	 */
	public function generateCreateData()
	{
		$data = array();
		$propMap = AbstractEntity::$propertyMaps[get_class($this)];
		foreach ($propMap as $propName) {
			$getter = 'get' . ucfirst(underScoreToCamelCase($propName));
			$value = $this->$getter();
			if (! is_null($value)) {
				$data[$propName] = $value;
			} else if (isset($this->defaults) && isset($this->defaults[$propName])) {
				$data[$propName] = $this->defaults[$propName];
			}
		}
		return $data;
	}

	/**
	 * Returns the primary key name
	 * You can extend this if your entity class and table does not fit the naming standart
	*/
	protected function getPkName()
	{
		$className = get_class($this);
		return strtolower($className . '_id');
	}

	/**
	 * Must return the related repository
	*/
	protected function getRepo()
	{
		return RepositoryFactory::create(parseClassname(get_class($this)));
	}

	/**
	 * Constructor
	* @param array $data
	 */
	public function __construct($data = array())
	{
		if (count($data) > 0) {
			if (isset($data[$this->getPkName()])) {
				$this->setId($data[$this->getPkName()]);
			}
			$this->preparePropertyMap();

			$propMap = AbstractEntity::$propertyMaps[get_class($this)];
			foreach ($propMap as $propName) {
				if (isset($data[$propName])) {
					$this->{$propName} = $data[$propName];
				}
			}
		}

		$this->castNumericValues();
		$this->storeInitialValues();
	}

	/**
	 * Sets a value to the primary key property
	 * You can extend this if your entity class and table does not fit the naming standart
	 *
	 * @param integer $id Value for the primary key
	 
	 */
	public function setId($id)
	{
		$setter = 'set' . ucfirst(underScoreToCamelCase($this->getPkName()));

		$this->$setter($id);
	}

	/**
	 * Returns the value of the primary key
	 * You can extend this if your entity class and table does not fit the naming standart
	*/
	public function getId()
	{
		$getter = 'get' . ucfirst(underScoreToCamelCase($this->getPkName()));

		return $this->$getter();
	}


	public function getDelayed($type)
	{
		return (isset($this->delayed[$type]) && $this->delayed[$type]) ? true : false;
	}

	/**
	 * Sets the delayed property which determines if
	 *
	* @param boolean $val
	 */
	public function setDelayed($type, $val = false)
	{
		$this->delayed[$type] = $val ? true : false;
	}

	/**
	 * Update current entity with values from another of the same type
	* @param mixed $entity And entity of the same type to update from
	 */
	public function updateFrom(AbstractEntity $entity, $keepOldMap = true)
	{
		$this->preparePropertyMap();

		$reflection = new \ReflectionClass($this);
		$propertyMap = AbstractEntity::$propertyMaps[$reflection->getName()];

		if (get_class($this) !== get_class($entity)) {
			throw new TerminalException(
				'Trying to load entity of type ' . get_class($entity) . ' into entity of type' . get_class($this));
		}

		foreach ($propertyMap as $propertyName) {
			$method = explode("_", $propertyName);
			$setter = "set";
			$getter = "get";
			foreach ($method as $word) {
				$setter .= ucfirst($word);
				$getter .= ucfirst($word);
			}
			if ($keepOldMap) {
				$this->propertyMapsAndValues[$reflection->getName()][$propertyName] = $entity->$propertyName;
			}

			$this->$setter($entity->$getter());
		}
	}

	/**
	 * Update an entity in the db
	* @param bool $immediately If the save should be immediately executed or added to the queries stack
	 * @throws TerminalException if save fails
	 * @return AbstractEntity
	 */
	public function save($immediately = false)
	{
		if (! $immediately && $this->getDelayed(DelayedQuery::TYPE_UPDATE)) {
			return true;
		}

		if ($this->forDeletion) {
			$msg = 'Entity: ' . get_class($this) . ' with id: ' . $this->getId() . ' is already marked for deletion.';
			Assert::fire($msg, Assert::FAILED_OPERATION);
		}

		$this->preparePropertyMap();

		$data = $this->generateSaveData();
		if ($immediately || ! AppConfig::get('UnitOfWork', false) || empty($data)) {
			$this->setDelayed(DelayedQuery::TYPE_UPDATE, false);
			$this->getRepo()->saveEntity($this->getId(), $data);
			$this->storeInitialValues();
		} else {
			$queryParams = array(
				'repository' => $this->getRepo(),
				'method' => 'saveEntity',
				'id' => $this->getId(),
				'entity' => $this,
				'type' => DelayedQuery::TYPE_UPDATE,
			);
			UnitOfWork::createDelayedQuery($queryParams);
			$this->setDelayed(DelayedQuery::TYPE_UPDATE, true);
		}

		return $this;
	}

	/**
	 * Create an entity in the db
	 *
	* @param string $immediately
	 * @throws TerminalException
	 * @return \Framework\Persistence\AbstractEntity
	 */
	public function create($immediately = false)
	{
		if (! $immediately && $this->getDelayed(DelayedQuery::TYPE_CREATE)) {
			return true;
		}

		if ($this->forDeletion) {
			$msg = 'Entity: ' . get_class($this) . ' with id: ' . $this->getId() . ' is already marked for deletion.';
			Assert::fire($msg, Assert::FAILED_OPERATION);
		}

		if ($this->getId()) {
			$this->preparePropertyMap(false);
		} else {
			$this->preparePropertyMap();
		}

		$data = $this->generateCreateData();
		if ($immediately || ! AppConfig::get('UnitOfWork', false) || empty($data)) {
			$id = $this->getRepo()->create($data);
			$this->setDelayed(DelayedQuery::TYPE_CREATE, false);
			if ($id !== false) {
				$this->setId($id);
			} else {
				$className = get_class($this);
				throw new TerminalException("Cannot insert entity {$className} into the db. Probably DB problem.");
			}
		} else {
			$queryParams = array(
				'repository' => $this->getRepo(),
				'method' => 'create',
				'entity' => $this,
				'type' => DelayedQuery::TYPE_CREATE,
			);
			UnitOfWork::createDelayedQuery($queryParams);
			$this->setDelayed(DelayedQuery::TYPE_CREATE, true);
		}

		return $this;
	}

	/**
	 * Delete entity from the db
	* @throws TerminalException
	 */
	public function delete($immediately = false)
	{
		if (! $immediately && $this->getDelayed(DelayedQuery::TYPE_DELETE)) {
			return true;
		}

		if ($this->forDeletion) {
			$msg = 'Entity: ' . get_class($this) . ' with id: ' . $this->getId() . ' is already marked for deletion.';
			Assert::fire($msg, Assert::FAILED_OPERATION);
		}

		if ($this->getId()) {
			$this->forDeletion = true;
			if ($immediately || ! AppConfig::get('UnitOfWork', false)) {
				$this->setDelayed(DelayedQuery::TYPE_DELETE, false);
				return $this->getRepo()->delete($this->getId());
			} else {
				$queryParams = array(
					'repository' => $this->getRepo(),
					'method' => 'delete',
					'id' => $this->getId(),
					'entity' => $this,
					'type' => DelayedQuery::TYPE_DELETE,
				);
				UnitOfWork::createDelayedQuery($queryParams);
				$this->setDelayed(DelayedQuery::TYPE_DELETE, true);
			}
		} else {
			$className = get_class($this);
			throw new TerminalException("Cannot delete entity {$className} from the DB. It was not yet saved to the DB");
		}
	}

	/**
	 * Converts the entity to an associative array
	 *
	* @return array
	 */
	public function toArray()
	{
		$return = array();
		$return[underScoreToCamelCase($this->getPkName())] = $this->getId();
		foreach (AbstractEntity::$propertyMaps[get_class($this)] AS $propertyName) {
			$camelName = underScoreToCamelCase($propertyName);
			$methodName = 'get' . $camelName;

			if (method_exists($this, $methodName)) {
				$return[$camelName] = $this->$methodName();
			} else {
				$return[$camelName] = $this->$propertyName;
			}
		}
		return $return;
	}

	/**
	 * @see JsonSerializable::jsonSerialize()
	* @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}
