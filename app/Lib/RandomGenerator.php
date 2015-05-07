<?php
namespace Lib;

/**
 
 */
abstract class RandomGenerator
{
	public static $lastRoll;

	/**
	 * Get random value between two numbers
	 *
	* @param number $min
	 * @param number $max
	 * @return number
	 */
	public static function roll($min = 1, $max = 100)
	{
		$roll = mt_rand($min, $max);
		self::$lastRoll = $roll;
		return $roll;
	}

	/**
	 * Calculate percent chance
	 *
	* @param int $percent
	 * @return boolean
	 */
	public static function checkSuccessPercent($percent)
	{
		return (self::roll() <= (int) $percent);
	}
}
