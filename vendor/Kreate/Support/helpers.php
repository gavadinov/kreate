<?php

/**
 * Dump and Die
 *
 * @param  dynamic  mixed
 * @return void
 */
function dd()
{
    echo '<pre>';
    array_map(function($x) { var_dump($x); }, func_get_args());
    die;
}

/**
 * Pretty Print
 *
 * @param  dynamic  mixed
 * @return void
 */
function pp()
{
    echo '<pre>';
    array_map(function($x) { print_r($x); }, func_get_args());
}

/**
 * Return the passed object. Useful for chaining.
 *
 * @param Object $object
 * @return Object
 */
function chain($object)
{
    return $object;
}

/**
 * Checks if a string ends with a given substring
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function endsWith($haystack, $needle)
{
    if ($needle == substr($haystack, -strlen($needle))) return true;
    return false;
}

/**
 * Checks if a string starts with a given substring
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function startsWith($haystack, $needle)
{
    if ($needle != '' && strpos($haystack, $needle) === 0) return true;
    return false;
}

/**
 * Checks if a string contains a given substring
 *
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function contains($haystack, $needle)
{
    if ($needle != '' && strpos($haystack, $needle) !== false) return true;
    return false;
}