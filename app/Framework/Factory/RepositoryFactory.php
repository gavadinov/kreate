<?php
namespace Framework\Factory;

use Framework\Persistence\AbstractMySqlRepository;
/**
 *
 
 */
class RepositoryFactory
{
	private static $reposiotires = array();

	/**
	 * Repository factory
	 *
	* @param string $name
	 * @return AbstractMySqlRepository
	 */
	public static function create($name)
	{
		$fqnPrefix = 'Repository\\';

		$fqn = $fqnPrefix . ucfirst($name) . 'Repository';
		if (! class_exists($fqn)) {
			$fqn = $fqnPrefix . 'Mongo\\' . ucfirst($name) . 'Repository';
		}

		if ( ! isset(self::$reposiotires[$fqn])) {
			self::$reposiotires[$fqn] = new $fqn();
		}

		return self::$reposiotires[$fqn];
	}
}
