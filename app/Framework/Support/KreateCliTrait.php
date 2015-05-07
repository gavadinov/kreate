<?php
namespace Framework\Support;

trait KreateCliTrait
{
	protected
			$args,
			$name;

	/**
	 *
	 * @author lubomir.gavadinov
	 * @param string $str
	 * @param string $color
	 * @return string
	 */
	protected function color($str, $color = 'yellow')
	{
		$colors = array(
			'black' => '0;30',
			'dark_gray' => '1;30',
			'blue' => '0;34',
			'light_blue' => '1;34',
			'green' => '0;32',
			'light_green' => '1;32',
			'cyan' => '0;36',
			'light_cyan' => '1;36',
			'red' => '0;31',
			'light_red' => '1;31',
			'purple' => '0;35',
			'light_purple' => '1;35',
			'brown' => '0;33',
			'yellow' => '1;33',
			'light_gray' => '0;37',
			'white' => '1;37'
		);
		$clr = $colors[$color];
		return "\033[{$clr}m{$str}\033[0m";
	}

	/**
	 *
	 * @author lubomir.gavadinov
	 * @param string $str
	 */
	protected function out($str)
	{
		echo $str . PHP_EOL;
	}

	public function __construct()
	{
		global $argv;
		$this->name = array_shift($argv);
		$this->args = $argv;
	}

	/**
	 * Detach from the terminal
	 *
	 *
	 * @author lubomir.gavadinov
	 * @return number
	 */
	public function detach()
	{
		if (empty($this->args)) {
			$command = $this->name;
		} else {
			$command = $this->name . '\ ' . implode('\ ', $this->args);
		}

		$test = exec("ps axf | grep {$command} | grep -v grep | wc -l");
		if (end($this->args) !== '--multi' && $test > 1) {
			echo "Too many {$command} processes. Count : {$test}" . PHP_EOL;
			die();
	}

	declare(ticks = 1);
	$pid = pcntl_fork();
	if ($pid == - 1) {
		die("could not fork");
	} else if ($pid) {
		die(); // we are the parent
	}
	// detatch from the controlling terminal
	if (posix_setsid() == - 1) {
		die("could not detach from terminal");
	}
	}
}
