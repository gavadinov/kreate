<?php
namespace Framework\Persistence;

/**
 * Cache entities
 *
 *
 */
class EntityPool
{
	private static $instance;

	private $entityPool = array();

	/**
	 *
	 * @return \Framework\Persistence\EntityManager
	 */
	public static function getInstance()
	{
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function add(EntityInterface $entity)
	{
		$name = parseClassname($entity);
		$this->entityPool[$name][$entity->getId()] = $entity;
	}

	public function get($name, $id)
	{
		return get($this->entityPool, "{$name}.{$id}");
	}

	public function flushSingle($name, $id)
	{
		if (isset($this->entityPool[$name][$id])) {
			unset($this->entityPool[$name][$id]);
		}
	}

	public function flush()
	{
		$this->entityPool = array();
	}
}
