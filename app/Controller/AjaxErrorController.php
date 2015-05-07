<?php
namespace Controller;

use Framework\Controller\AbstractAjaxController;
use Framework\Http\Response;
use Framework\Config\AppConfig;
use Framework\Routing\Url;
use Framework\Http\Request;

class AjaxErrorController extends AbstractAjaxController
{
	public function before($method)
	{
	}

	/**
	 *
	 * @see \Framework\Controller\AbstractController::after()
	 */
	public function after($result)
	{
		Response::getInstance()->setIsJson($this->isJson);
		$data = array(
			'result' => $result
		);

		foreach (Request::getInstance()->getParam('ajaxData', array()) as $key => $value) {
			$data[$key] = $value;
		}

		return $data;
	}

	/**
	 * Force refresh the app
	 *
	 	 * @return string
	 */
	public function maintenance()
	{
		Request::getInstance()->setParam('ajaxData.forceRefresh', Url::generate('game'));
		return new \stdClass();
	}

	/**
	 *
	 
	 * @return string
	 */
	public function error404()
	{
		return "Page Not Found";
	}

	public function kernelPanic(\Exception $e)
	{
		Response::getInstance()->setStatus(500, 'Server Error');

		if (APP_ENV != AppConfig::ENV_LIVE) {
			$message = $e->getMessage() . PHP_EOL . PHP_EOL;
			$message .= 'STACK TRACE: ' . PHP_EOL;
			foreach ($e->getTrace() as $step) {
				$message .= '‣' . $step['file'] . ': ' . $step['line'] . PHP_EOL . PHP_EOL;
			}
		} else {
			$message = 'Something went wrong';
		}

		return array(
			'message' => $message
		);
	}
}
