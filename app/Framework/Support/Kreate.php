<?php
namespace Framework\Support;

class Kreate
{
	use \Framework\Support\KreateCliTrait;

	protected function resolveMethod($command)
	{
		if (contains($command, ':')) {
			$func = create_function('$c', 'return strtoupper($c[1]);');
			$command = preg_replace_callback('/:([a-z])/i', $func, $command);
		}

		return $command;
	}

	protected function up(array $args)
	{
		$filename = base_dir . 'down';
		if (file_exists($filename)) {
			unlink($filename);
		}

		return array(
			'text' => 'UP',
			'color' => 'green'
		);
	}

	protected function down(array $args)
	{
		fopen(base_dir . 'down', 'w');
		return array(
			'text' => 'DOWN',
			'color' => 'red'
		);
	}

	public function run(array $args)
	{
		$method = $this->resolveMethod(array_shift($args));
		if (! method_exists($this, $method)) {
			return array(
				'text' => 'Unknown command. Try --help.',
				'color' => 'red',
			);
		}

		return $this->$method($args);
	}
}
