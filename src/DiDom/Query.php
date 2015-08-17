<?php

namespace DiDom;

use InvalidArgumentException;

class Query
{
    /**
     * Types of expressions.
     * 
     * @string
     */
    const TYPE_XPATH = 'TYPE_XPATH';
    const TYPE_CSS   = 'TYPE_CSS';

    /**
     * @var array
     */
    protected static $compiled = array();

    /**
     * Transform CSS expression to XPath.
     *
     * @param  string $expression XPath expression or CSS selector
     * @param  string $type the type of the expression
     * @return string
     */
    public static function compile($expression, $type = self::TYPE_CSS)
    {
        if ($type == Query::TYPE_XPATH) {
            return $expression;
        }

        if (array_key_exists($expression, static::$compiled)) {
            return static::$compiled[$expression];
        }

        $xpath = static::cssToXpath($expression);

        static::$compiled[$expression] = $xpath;

        return $xpath;
    }

    /**
     * Transform CSS expression to XPath.
     *
     * @param  string $selector
     * @return string
     */
    protected static function cssToXpath($selector)
    {
        $path = (string) $selector;

        if (strstr($path, ',')) {

            $paths       = explode(',', $path);
            $expressions = array();

            foreach ($paths as $path) {

                $xpath = static::cssToXpath(trim($path));

                if (is_string($xpath)) {
                    $expressions[] = $xpath;
                } elseif (is_array($xpath)) {
                    $expressions = array_merge($expressions, $xpath);
                }
            }

            return implode('|', $expressions);
        }

        $paths    = array('//');
        $path     = preg_replace('|\s+>\s+|', '>', $path);
        $segments = preg_split('/\s+/', $path);

        foreach ($segments as $key => $segment) {
            $pathSegment = static::tokenize($segment);

            if (0 == $key) {
                if (0 === strpos($pathSegment, '[contains(')) {
                    $paths[0] .= '*' . ltrim($pathSegment, '*');
                } else {
                    $paths[0] .= $pathSegment;
                }

                continue;
            }
            if (0 === strpos($pathSegment, '[contains(')) {
                foreach ($paths as $pathKey => $xpath) {

                    $paths[$pathKey] .= '//*' . ltrim($pathSegment, '*');
                    $paths[]          = $xpath . $pathSegment;
                }
            } else {
                foreach ($paths as $pathKey => $xpath) {
                    $paths[$pathKey] .= '//' . $pathSegment;
                }
            }
        }

        if (1 == count($paths)) {
            return $paths[0];
        }

        $xpath = implode('|', $paths);

        return $xpath;
    }

    /**
     * Tokenize CSS expressions to XPath.
     *
     * @param  string $expression
     * @return string
     */
    protected static function tokenize($expression)
    {
        // Child selectors
        $expression = str_replace('>', '/', $expression);

        // IDs
        $expression = preg_replace('|#([a-z][a-z0-9_-]*)|i', '[@id=\'$1\']', $expression);
        $expression = preg_replace('|(?<![a-z0-9_-])(\[@id=)|i', '*$1', $expression);

        // arbitrary attribute strict equality
        $expression = preg_replace_callback(
            '|\[@?([a-z0-9_-]+)=[\'"]([^\'"]+)[\'"]\]|i',
            function ($matches) {
                return '[@' . strtolower($matches[1]) . "='" . $matches[2] . "']";
            },
            $expression
        );

        // arbitrary attribute contains full word
        $expression = preg_replace_callback(
            '|\[([a-z0-9_-]+)~=[\'"]([^\'"]+)[\'"]\]|i',
            function ($matches) {
                return "[contains(concat(' ', normalize-space(@" . strtolower($matches[1]) . "), ' '), ' "
                     . $matches[2] . " ')]";
            },
            $expression
        );

        // arbitrary attribute contains specified content
        $expression = preg_replace_callback(
            '|\[([a-z0-9_-]+)\*=[\'"]([^\'"]+)[\'"]\]|i',
            function ($matches) {
                return "[contains(@" . strtolower($matches[1]) . ", '"
                     . $matches[2] . "')]";
            },
            $expression
        );

        // Classes
        if (false === strpos($expression, "[@")) {
            $expression = preg_replace(
                '|\.([a-z][a-z0-9_-]*)|i',
                "[contains(concat(' ', normalize-space(@class), ' '), ' \$1 ')]",
                $expression
            );
        }

        /** ZF-9764 -- remove double asterisk */
        $expression = str_replace('**', '*', $expression);

        return $expression;
    }

    public static function getCompiled()
    {
        return static::$compiled;
    }
    
    public static function setCompiled($compiled)
    {
        if (!is_array($compiled)) {
            throw new InvalidArgumentException(sprintf('Query::setCompiled() expects parameter 1 to be array, %s given', gettype($compiled)));
        }

        static::$compiled = $compiled;
    }
}
