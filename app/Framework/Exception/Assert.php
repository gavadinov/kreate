<?php
namespace Framework\Exception;

class Assert extends \Exception
{
	const WRONG_DATA_TYPE = 1;
	const WRONG_DATA_VALUE = 2;
	const WRONG_METHOD_CALLED = 3;
	const UNMAPPED_VALUE = 4;
	const MISSING_PARAMS = 5;
	const FAILED_OPERATION = 6;
	const CHEATING = 7;

	private
		$on,
		$data;

	public function getOn()
	{
		return $this->on;
	}

	public function setOn($on)
	{
		$this->on = $on;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setData($data = array())
	{
		$this->data = $data;
	}

	/**
	 * Fire assert
	 *
	* @param string $message
	 * @param int $type
	 * @param array $data
	 */
	public static function fire($message, $type, $data = array())
	{
		if (! is_array($data)) {
			$data = array($data);
		}
		$bt = debug_backtrace();
		$called = array_shift($bt);
		if (endsWith($called['file'], 'Assert.php')) {
			$called = array_shift($bt);
		}

		$fileArr = explode('/', $called['file']);
		$file = array_pop($fileArr);
		$line = $called['line'];
		$data['trace'] = $bt;
		$on = ' : ' . $file . '(' . $line . ')';
		$message = $message . $on;

		$ex = new self($message, $type);
		$ex->setData($data);
		throw $ex;
	}

	/**
	 * Fire assert if condition is evaluated TRUE
	 *
	* @param bool $condition
	 * @param string $message
	 * @param int $type
	 * @param array $data
	 */
	public static function fireIf($condition, $message, $type, $data = array())
	{
		if ($condition) {
			self::fire($message, $type, $data);
		}
	}

	/**
	 * Fire assert if condition is evaluated FALSE
	 *
	* @param bool $condition
	 * @param string $message
	 * @param int $type
	 * @param array $data
	 */
	public static function fireUnless($condition, $message, $type, $data = array())
	{
		if (! $condition) {
			self::fire($message, $type, $data);
		}
	}
}
