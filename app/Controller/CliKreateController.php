<?php
namespace Controller;

use Framework\Controller\AbstractCliController;
use Lib\Kreate;

class CliKreateController extends AbstractCliController
{
	public function kreate()
	{
		$result = (new Kreate())->run($this->args);

		if (isset($result['color'])) {
			$result = $this->color($result['text'], $result['color']);
		}

		return $result;
	}
}
