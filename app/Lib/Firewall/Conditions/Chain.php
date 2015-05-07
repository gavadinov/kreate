<?php
namespace Lib\Firewall\Conditions;

class Chain
{
	private static $instance;

	protected $list = array(

	);

	protected $processors = array();

	private function __construct()
	{
		foreach ($this->list as $name) {
			$processorName = 'Lib\Firewall\Conditions\\' . ucfirst($name);
			$processor = new $processorName();
			if ($processor instanceof FirewallProcessor) {
				$this->processors[] = $processor;
			}
		}
	}

	/**
	 *
	* @return \Lib\Firewall\Conditions\Chain
	 */
	public static function getInstance()
	{
		if (! isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function resolve($condition, array $data = array())
	{
		foreach ($this->processors as $processor) {
			$result = $processor->process($condition, $data);
			if ($result) {
				return $result;
			}
		}
	}
}
