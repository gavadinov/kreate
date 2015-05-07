<?php
namespace Framework\Support;

/**
 * Wrapper for the SplObjectStorage class
 *
 
 */
class ObjectStorage extends \SplObjectStorage
{
	/**
	* @return array
	 */
	public function toArray()
	{
		$return = array();
		foreach ($this as $obj) {
			$return[] = $obj;
		}

		return $return;
	}
}
