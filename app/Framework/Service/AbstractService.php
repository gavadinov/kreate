<?php
namespace Framework\Service;

abstract class AbstractService
{
	protected $singleton = false;

	public function getIsSingleton()
	{
		return $this->singleton;
	}
}
