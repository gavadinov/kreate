<?php

require_once '../app/globals.php';
require_once app_dir . 'Framework/Support/helpers.php';
require_once app_dir . 'Framework/Bootstrap/autoload.php';

abstract class BaseCommand
{
    protected
            $helpMode,
            $args;

    public function __construct($args, $helpMode = false)
    {
        $this->args = $args;
        $this->helpMode = $helpMode;
    }

    public static function out($str)
    {
        echo $str . "\n";
    }

    public static function in($str)
    {
        echo $str . ': ';
        $line =  trim(fgets(STDIN));
        return $line;
    }

    public static function clr($str, $color = 'yellow')
    {
        $colors = array(
            'black' => '0;30',
            'dark_gray' => '1;30',
            'blue' => '0;34',
            'light_blue' => '1;34',
            'green' => '0;32',
            'light_green' => '1;32',
            'cyan' => '0;36',
            'light_cyan' => '1;36',
            'red' => '0;31',
            'light_red' => '1;31',
            'purple' => '0;35',
            'light_purple' => '1;35',
            'brown' => '0;33',
            'yellow' => '1;33',
            'light_gray' => '0;37',
            'white' => '1;37'
        );
        $clr = $colors[$color];
        return "\033[{$clr}m{$str}\033[0m";
    }

    public static function hlp($method, $description, $args)
    {
        $pair = explode('::', $method);
        $cmdName .= lcfirst(str_replace('command', '', $pair[1]));
        $str = self::clr($cmdName);
        self::out("  {$str} - {$description}");
        foreach ($args as $d) {
            self::out("\t - {$d}");
        }
        self::out();
    }

    public static function hdr($str, $strong = false)
    {
        $count = strlen($str);
        if($strong) {
            $dashes = str_repeat('=', 69);
        } else {
            $dashes = str_repeat('-', 69);
        }

        self::out("{$dashes}\n{$str}\n{$dashes}");
    }

    public static function err($str)
    {
        self::out("\n\033[0;31m\033[47mERROR: {$str}\033[0m\n");
    }

    protected function arg($index, $default = null)
    {
        if (isset($this->args[$index])) {
            return $this->args[$index];
        } else {
            return $default;
        }
    }

    protected function setArg($index, $value)
    {
        $this->args[$index] = $value;
    }

    public function showHelp()
    {
        $module = strtolower(str_replace('CommandModule', '', get_class($this)));
        $methods = get_class_methods(get_class($this));

        self::out(self::clr($module));

        $sorted = array();
        foreach ($methods as $method) {
            if (strpos($method, 'command') === 0) {
                $sorted[$method] = $method;
            }
        }
        ksort($sorted);
        foreach ($sorted as $method) {
            $this->$method();
        }
    }

    protected function underscoreToCamel($text)
    {
        $camel = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($text))));
        return $camel;
    }

    protected function createComment($text)
    {
        $comment = "/**\n";
        $lines = explode('|', $text);
        foreach($lines as $line) {
            $comment .= " * {$line}\n";
        }
        $comment .= " */\n";
        return $comment;
    }
}
