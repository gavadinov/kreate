<?php
namespace Lib;

class MessageHandler
{
	// if you change these make a change in the css file
	const NOTICE_TYPE_POSITIVE = 'positive';
	const NOTICE_TYPE_NEUTRAL = 'neutral';
	const NOTICE_TYPE_NEGATIVE = 'negative';

	public static $instance;

	private $container = array();

	public function __construct()
	{
		$this->container[MessageHandler::NOTICE_TYPE_POSITIVE] = array();
		$this->container[MessageHandler::NOTICE_TYPE_NEUTRAL] = array();
		$this->container[MessageHandler::NOTICE_TYPE_NEGATIVE] = array();
	}

	/**
	 * The class is singleton, so this is the usual stuff
	 *
	 * @return MessageHandler
	 */
	static function getInstance()
	{
		if (! self::$instance) {
			self::$instance = new MessageHandler();
		}
		return self::$instance;
	}

	/**
	 * Sets new message
	 *
	 * @param string $text Text of the message
	 * @param string $type Type of the message
	 *        Could be one of the following constants:
	 *        MessageHandler::NOTICE_TYPE_NEUTRAL - Information message
	 *        MessageHandler::NOTICE_TYPE_POSITIVE - Success message
	 *        MessageHandler::NOTICE_TYPE_NEGATIVE - Error message
	 */
	public static function set($text, $type = MessageHandler::NOTICE_TYPE_NEUTRAL, $code = null)
	{
		$instance = self::getInstance();
		if (! in_array($text, $instance->container[$type])) {
			if (is_null($code)) {
				$instance->container[$type][] = $text;
			} else {
				$instance->container[$type][$code] = $text;
			}
		}
	}

	/**
	 * Shorthand for MessageHandler:set($text, MessageHandler::NOTICE_TYPE_NEGATIVE)
	 *
	 * @param string $text
	 * @see MessageHandler::set();
	 */
	public static function setError($text, $code = null)
	{
		self::set($text, MessageHandler::NOTICE_TYPE_NEGATIVE, $code);
	}

	/**
	 * Shorthand for MessageHandler::set($text, MessageHandler::NOTICE_TYPE_POSITIVE)
	 *
	 * @param string $text
	 * @see MessageHandler::set();
	 */
	public static function setMessage($text, $code = null)
	{
		self::set($text, MessageHandler::NOTICE_TYPE_POSITIVE, $code);
	}

	/**
	 * Shorthand for MessageHandler::set($text, MessageHandler::NOTICE_TYPE_NEUTRAL)
	 *
	 * @param string $text
	 * @see MessageHandler::set() for $type constants
	 */
	public static function setInfoMessage($text, $code = null)
	{
		self::set($text, MessageHandler::NOTICE_TYPE_NEUTRAL, $code);
	}

	/**
	 * Gets all collected messages of a specific type
	 *
	 * @param string $type Type of the message
	 *        If null the whole contaner with all types will be returned
	 * @return array Array of messages
	 * @see MessageHandler::set() for $type constants
	 */
	public static function get($type = null)
	{
		$instance = self::getInstance();
		if (! $type) {
			foreach ($instance->container as $type => $messagesPool) {
				if (! empty($messagesPool)) {
					$messages[$type] = $messagesPool;
				}
			}
			return $messages;
		} else {
			return $instance->container[$type];
		}
	}

	/**
	 * Gets a message by its type and code
	 *
	 * @param integer $code
	 * @param integer $type
	 * @return <void,string>
	 */
	public static function getByCodeAndType($code, $type)
	{
		$instance = self::getInstance();
		return $instance->container[$type][$code];
	}

	public static function getErrorByCode($code)
	{
		return self::getByCodeAndType($code, MessageHandler::NOTICE_TYPE_NEGATIVE);
	}

	public static function getInfoMessageByCode($code)
	{
		return self::getByCodeAndType($code, MessageHandler::NOTICE_TYPE_NEUTRAL);
	}

	public static function getMessageByCode($code)
	{
		return self::getByCodeAndType($code, MessageHandler::NOTICE_TYPE_POSITIVE);
	}

	/**
	 * Checks is there are messages of a specific type
	 *
	 * @param string $type Type of the message
	 *        If ommited this will check if there is message of any type
	 * @return boolean
	 * @see MessageHandler::set() For message types
	 */
	public static function hasMessagesOfType($type = null)
	{
		$instance = self::getInstance();
		if (! $type) {
			foreach ($instance->container as $value) {
				if (count($value) > 0) {
					return true;
				}
			}
			return false;
		} else {
			return count($instance->container[$type]) > 0;
		}
	}

	/**
	 * Check if there are messages of type MessageHandler::NOTICE_TYPE_NEGATIVE
	 */
	public static function hasErrors()
	{
		return self::hasMessagesOfType(MessageHandler::NOTICE_TYPE_NEGATIVE);
	}

	public static function hasMessages()
	{
		return self::hasMessagesOfType(null);
	}

	public static function hasOneOrMoreCodes()
	{
		$codes = func_get_args();
		$result = false;
		foreach($codes as $code) {
			$result = $result || self::hasCode($code);
		}
		return $result;
	}

	/**
	 * Check if given code exists in the container
	* @param integer $code
	 * @return boolean
	 */
	public static function hasCode($code)
	{
		$instance = self::getInstance();

		if (isset($instance->container[MessageHandler::NOTICE_TYPE_POSITIVE][$code])
			|| isset($instance->container[MessageHandler::NOTICE_TYPE_NEUTRAL][$code])
			|| isset($instance->container[MessageHandler::NOTICE_TYPE_NEGATIVE][$code])
		) {
			return true;
		}

		return false;
	}

	/**
	 * Return the messages for the response
	 * Should be done in after process
	* @return array $messages
	 */
	public static function returnMessages()
	{
		$instance = self::getInstance();
		$messages = array();

		foreach($instance->container as $type => $messagesPool) {
			if (empty($messagesPool)) {
				continue;
			}

			foreach ($messagesPool as $code => $text) {
				$messages[] = (object) array(
					'id' => $code,
					'type' => $type,
					'text' => $text
				);
			}
		}
		return $messages;
	}

	/**
	 * Set an error message in the pool and throw an exception
	 * If in dev mode - include entire $exception object in the error
	* @param string $text
	 * @param \Exception $exception
	 * @throws \Exception
	 */
	public static function setAndThrow($text, \Exception $exception)
	{
		$instance = self::getInstance();
		$exception->setMessage($text);

		$instance->setError($text, $exception->getCode());

		throw $exception;
	}

	/**
	 * Clears messages by type
	 * If type not provided - clears all messages
	* @param string $type
	 */
	public static function clearMessages($type = null)
	{
		$instance = self::getInstance();
		if (is_null($type)) {
			$instance->container[MessageHandler::NOTICE_TYPE_NEGATIVE] = array();
			$instance->container[MessageHandler::NOTICE_TYPE_NEUTRAL] = array();
			$instance->container[MessageHandler::NOTICE_TYPE_POSITIVE] = array();
		} else {
			$instance->container[$type] = array();
		}
	}

	/**
	 * Clear message by code and type
	* @param integer $code
	 * @param string $type
	 */
	public static function clearByCodeAndType($code, $type)
	{
		$instance = self::getInstance();
		unset($instance->container[$type][$code]);
	}

	public static function clearErrorByCode($code) {
		self::clearByCodeAndType($code, MessageHandler::NOTICE_TYPE_NEGATIVE);
	}

	public static function clearInfoMessageByCode($code) {
		self::clearByCodeAndType($code, MessageHandler::NOTICE_TYPE_NEUTRAL);
	}

	public static function clearMessageByCode($code) {
		self::clearByCodeAndType($code, MessageHandler::NOTICE_TYPE_POSITIVE);
	}

}

