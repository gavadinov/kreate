<?php
namespace Lib\Firewall\Conditions;

/**
 *
 
 */
interface FirewallProcessor
{
	function process($condition, array $params = array());
}
