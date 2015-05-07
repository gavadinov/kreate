<?php
namespace Framework\Controller;

class AbstractCliController extends AbstractController
{
	use \Framework\Support\KreateCliTrait {
		\Framework\Support\KreateCliTrait::__construct as private __kreateCliConstruct;
	}

	public function before($method) {}

	public function after($result)
	{
		return $result . PHP_EOL;
	}

	public function __construct()
	{
		$this->__kreateCliConstruct();
	}
}
