<?php
namespace Framework\Controller;

use Framework\Controller\Exception\AjaxException;
use Framework\Http\Response;
use Framework\Persistence\EntityInterface;
use Framework\Http\Request;

abstract class AbstractAjaxController extends AbstractController
{
	protected static
			$before = array(),
			$after = array();

	protected $isJson = true;

	protected $unsetNullValues = true;

	/**
	 * Remove fields with empty/null values from response
	 *

	 * @param Ambigous <array, StdClass> $haystack
	 * @return Ambigous <array, StdClass>
	 */
	protected function unsetNullValues($haystack)
	{
		if ($haystack instanceof EntityInterface) {
			$haystack = $haystack->toArray();
		}

		if (! is_array($haystack)) {
			return $haystack;
		}

		$isAssoc = isAssoc($haystack);
		foreach ($haystack as $key => $value) {
			if (is_array($value)) {
				if (! empty($value)) {
					$haystack[$key] = $this->unsetNullValues($value);
					if (empty($haystack[$key])) {
						unset($haystack[$key]);
					}
				} else {
					unset($haystack[$key]);
				}
			} else if (is_object($value) ) {
				if ($value instanceof EntityInterface) {
					$value = $value->toArray();
				} else {
					$value = (array) $value;
				}

				if (! empty($value)) {
					$haystack[$key] = $this->unsetNullValues($value);
					if (empty($haystack[$key])) {
						unset($haystack[$key]);
					} else {
						$haystack[$key] = (object) $haystack[$key];
					}
				} else {
					unset($haystack[$key]);
				}
			} else if (is_null($haystack[$key]) ) {
				unset($haystack[$key]);
			}
		}
		if (! $isAssoc && key($haystack) == 0) {
			return array_values($haystack);
		}
		return $haystack;
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

		$this->fireAfter($data);

		if ($this->unsetNullValues && $data) {
			$data = $this->unsetNullValues($data);
		}

		if (empty($data['result'])) {
			$data['result'] = new \stdClass();
		}

		return $data;
	}

	/**
	 * Handle ajax exceptions
	 *

	 * @see \Framework\Controller\AbstractController::handleException()
	 */
	public function handleException(\Exception $e)
	{
		if ($e instanceof AjaxException) {

		} else {
			parent::handleException($e);
		}
	}
}
