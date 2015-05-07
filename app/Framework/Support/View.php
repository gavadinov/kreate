<?php
namespace Framework\Support;

use Framework\Config\AppConfig;

class View
{
	/**
	 * Include the view inside a master layout
	 *
	* @param string $name
	 * @param array $data
	 */
	public static function make($name = false, $data = array(), $layout = null)
	{
		if ($name) {
			$view = self::getPath($name);
		}

		extract($data);
		if (empty($layout)) {
			$layout = AppConfig::get('layout', 'master');
		}
		$path = app_dir . 'Views/' . $layout . '.phtml';

		ob_start();
		include $path;
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	/**
	 * Return the view as a string
	 *
	* @param string $name
	 * @param array $data
	 * @return string
	 */
	public static function partial($name, $data = array())
	{
		$path = self::getPath($name);

		extract($data);

		ob_start();
		include $path;
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	/**
	 * Get view path
	 *
	* @param unknown $name
	 * @throws \Exception
	 * @return string
	 */
	public static function getPath($name)
	{
		$name = preg_replace('/\./', '/', $name);
		$path = app_dir . 'Views/' . $name .'.phtml';
		if (! file_exists($path)) {
			throw new \Exception('No view with the name: ' . $path);
		}
		return $path;
	}
}
