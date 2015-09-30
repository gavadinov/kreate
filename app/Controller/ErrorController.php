<?php
namespace Controller;

use Framework\Controller\AbstractController;
use Framework\Http\Response;
use Framework\Config\AppConfig;
use Framework\Support\View;
use Framework\Http\Request;

class ErrorController extends AbstractController
{
	public function before($method)
	{
	}

	public function after($result)
	{
		return $result;
	}

	/**
	* @return string
	 */
	public function error404()
	{
		return "<h1>Page Not Found</h1>";
	}

	/**
	 * Render the Maintenance view
	 *
	* @return string
	 */
	public function maintenance()
	{
		return View::partial('system.maintenance', array(), 'emptyLayout');
	}

	public function kernelPanic(\Exception $e)
	{
		Response::getInstance()->setStatus(500, 'Server Error');
		$message = '<span style="color: red">Something went wrong';
		if (APP_ENV != AppConfig::ENV_LIVE) {
			$message = '<span style="color: red">ERROR!' . PHP_EOL;
			$message .= $e->getMessage() . '</span>' . PHP_EOL . PHP_EOL;
			foreach ($e->getTrace() as $step) {
				if (isset($step['file'])) {
					$message .= '‣' . $step['file'] . ': ' . $step['line'] . PHP_EOL . PHP_EOL;
				}
			}
		}
		if (Request::getInstance()->isInConsole) {
			$message = strip_tags($message);
			return $message;
		}

		return nl2br($message);
	}
}
