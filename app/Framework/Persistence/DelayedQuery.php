<?php
namespace Framework\Persistence;

use Framework\Exception\TerminalException;
class DelayedQuery
{
	const TYPE_CREATE= 1;
	const TYPE_UPDATE = 2;
	const TYPE_DELETE = 9;

	protected
			$repository,
			$entity,
			$method,
			$data,
			$id,
			$type;

	public function __construct($params)
	{
		$this->repository = $params['repository'];
		$this->entity = $params['entity'];
		$this->method = $params['method'];
		$this->data = (isset($params['data']) ? $params['data'] : array());
		$this->id = (isset($params['id']) ? $params['id'] : null);
		$this->type = $params['type'];
	}

	public function exec()
	{
		$method = $this->method;
		switch ($this->type) {
			case self::TYPE_DELETE:
				$this->repository->$method($this->id);
				break;

			case self::TYPE_UPDATE:
				$this->data = $this->entity->generateSaveData();
				if (! empty($this->data)) {
					$this->repository->$method($this->id, $this->data);
				}
				break;

			case self::TYPE_CREATE:
				$this->data = $this->entity->generateCreateData();
				if (! empty($this->data)) {
					$id = $this->repository->$method($this->data);
					$this->entity->setId($id);
				}
				break;

			default:
				throw new TerminalException('Trying to execute unknown type of Delayed Query: ' . $this->type . ' / ' . $method);
			break;
		}

		$this->entity->setDelayed($this->type, false);
		$this->entity->storeInitialValues();
	}

	public function restore()
	{
		$this->entity->restoreInitialValues();
	}

	public function getDbInfo()
	{
		$info = array();
		$info['dbHost']= $this->repository->getDbHost();
		$info['dbName'] = $this->repository->getDbName();

		return $info;
	}

	public function getEntity()
	{
		return $this->entity;
	}

	public function getType()
	{
		return $this->type;
	}
}
