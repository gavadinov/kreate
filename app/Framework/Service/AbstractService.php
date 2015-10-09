<?php
namespace Framework\Service;

use Framework\Factory\RepositoryFactory;
abstract class AbstractService
{
	protected $singleton = false;

	/**
	 *
	 * @return \Framework\Persistence\AbstractMySqlRepository
	 */
	protected function createRepo()
	{
		return RepositoryFactory::create($this->getName());
	}

	public function __construct(array $params = array())
	{}

	public function getName()
	{
		$nameArr = explode('\\', get_class($this));
		return preg_replace('/Service/', '', end($nameArr));
	}

	public function isSingleton()
	{
		return $this->singleton;
	}
}
