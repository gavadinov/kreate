<?php

namespace Framework\Http;

use Framework\Support\Profiler;
use Framework\Config\AppConfig;
class Response
{
	private $headers = array(),
			$content,
			$contentType = 'text/html',
			$charset = 'utf-8',
			$isJson = false,
			$status = array(
				'message' => 'OK',
				'code' => 200,
			);

	/*
	 * Singleton instance of the request
	 */
	private static $instance;

	private function parseContent()
	{
		if ($this->isJson) {
			$this->setContentType('application/json');
			$content = $this->content;
			if (AppConfig::resolveEnv() != AppConfig::ENV_LIVE) {
				$content['profiler'] = Profiler::getSummary();
			}

			return json_encode($content);
		}
		return $this->content;
	}

	/**
	 * Singleton implementation
	 *
	 * @return Response
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{}

	/**
	 * Perform HTTP header redirect
	 *
	 *
	 * @param string $url
	 * @param number $code
	 */
	public function redirect($url, $code = 301)
	{
		$this->setStatus($code, 'Redirect');
		$this->setHeader('Location: ' . $url);
		$this->send();
		die;
	}

	/**
	 * Set the http status
	 *
	 * @param int $code
	 * @param string $message
	 * @return Response
	 */
	public function setStatus($code, $message)
	{
		$this->status = array(
			'message' => $message,
			'code' => $code
		);
		return $this;
	}

	/**
	 * Set the isJson field
	 *
	 * @param bool $isJson
	 * @return Response
	 */
	public function setIsJson($isJson)
	{
		$this->isJson = $isJson;
		return $this;
	}

	/**
	 * Set http header
	 *
	 * @param string $header
	 * @return Response
	 */
	public function setHeader($header)
	{
		$this->headers[] = $header;
		return $this;
	}

	/**
	 * Set the content type
	 *
	 * @param string $type
	 * @return Response
	 */
	public function setContentType($type)
	{
		$this->contentType = $type;
		return $this;
	}

	/**
	 * Set the content
	 *
	 * @param string $content
	 * @return Response
	 */
	public function setContent($content)
	{
		$this->content = $content;
		return $this;
	}

	/**
	 *
	 *
	 * @return string
	 * @return Response
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Sends the response back to the client
	 *
	 */
	public function send()
	{
		Profiler::stop();
		$content = $this->parseContent();

		if (! Request::getInstance()->isInConsole) {
			header("HTTP/1.1 {$this->status['code']}: {$this->status['message']}", true, $this->status['code']);
			foreach ($this->headers as $header) {
				header($header);
			}

			header('Content-type: ' . $this->contentType . '; charset=' . $this->charset, true);
		}

		if ($content) {
			if (AppConfig::get('zipResponse', false) && $this->status['code'] != 500) {
				ob_start(AppConfig::get('zipResponse'));
			}

			echo $content;
		}

		die;
	}
}
