<?php
namespace Framework\Persistence;

interface EntityInterface
{
	function save($immediately = false);
	function create($immediately = false);
	function delete($immediately = false);
	function toArray();
	function getId();
	function updateFrom(AbstractEntity $entity);
}
