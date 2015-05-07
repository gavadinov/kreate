<?php
namespace Framework\Persistence;

trait EntityWrapperTrait
{
	/**
	 * @see \Framework\Persistence\EntityInterface::save()
	 */
	public function save($immediately = false)
	{
		$this->entity->save($immediately);
		return $this;
	}

	/**
	 * @see \Framework\Persistence\EntityInterface::create()
	 */
	public function create($immediately = false)
	{
		$this->entity->create($immediately);
		return $this;
	}

	/**
	 * @see \Framework\Persistence\EntityInterface::delete()
	 */
	public function delete($immediately = false)
	{
		$this->entity->delete($immediately);
	}

	/**
	 * @see \Framework\Persistence\EntityInterface::toArray()
	 */
	public function toArray()
	{
		return $this->entity->toArray();
	}

	/**
	 * @see \Framework\Persistence\EntityInterface::getId()
	 */
	public function getId()
	{
		return $this->entity->getId();
	}

	/**
	 * @see \Framework\Persistence\EntityInterface::updateFrom()
	 */
	public function updateFrom(\Framework\Persistence\AbstractEntity $entity)
	{
		$this->entity->updateFrom($entity);
	}
}
